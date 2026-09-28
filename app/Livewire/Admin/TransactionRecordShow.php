<?php

namespace App\Livewire\Admin;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class TransactionRecordShow extends Component
{
    public StudentDocumentWorkspace $workspace;

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        $this->workspace = $workspace->load([
            'user.latestVerification',
            'user.program',
            'template.currentVersion',
            'version',
            'appointments',
        ]);
    }

    /**
     * The single appointment this page treats as "the" appointment for this
     * transaction — latest non-retracted one, same rule used elsewhere in the app.
     */
    protected function latestAppointment()
    {
        return $this->workspace->appointments
            ->whereNotIn('status', ['retracted'])
            ->sortByDesc('created_at')
            ->first();
    }

    protected function resolveStatus(): string
    {
        $createdAt = $this->workspace->created_at instanceof Carbon
            ? $this->workspace->created_at
            : Carbon::parse($this->workspace->created_at);

        return match (true) {
            $this->workspace->isCompleted() => 'Completed',
            $this->workspace->isProcessing() => 'Processing',
            $this->workspace->hasAttendedAppointment() => 'Waiting Upload',
            $this->workspace->appointments()->whereIn('status', ['pending', 'approved'])->exists() => 'For Appointment',
            $createdAt->lt(now()->subDays(14)) => 'Missed',
            default => 'Pending',
        };
    }

    protected function appointmentStatus(?object $appointment): string
    {
        return match ($appointment?->status) {
            'attended' => 'Attended',
            'approved' => 'Scheduled',
            'pending' => 'Pending Approval',
            'rejected' => 'Rejected',
            'missed' => 'Missed',
            default => 'No Appointment',
        };
    }

    protected function fullName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->suffix,
        ])))) ?: ('User #' . $user->id);
    }

    protected function formatProgram(?User $user): string
    {
        if (! $user) {
            return 'No program on file';
        }

        return trim(implode(' ', array_filter([
            $user->program?->name,              // <-- just the name
            $user->year_level ? ('Year ' . $user->year_level) : null,
        ]))) ?: 'No program on file';
    }

    protected function documentMimeLabel(?string $path): string
    {
        if (! $path) {
            return 'Unavailable';
        }

        $extension = Str::upper(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'FILE';
    }

    protected function isImageFile(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    /**
     * Builds the full timeline for this transaction, now including the moment
     * the document was actually approved and the transaction marked complete
     * (i.e. when the VLM analysis passed and analyzed_at/completed_at were set) —
     * not just "workspace last updated," which doesn't tell an admin what happened.
     */
    protected function timeline(string $status, ?object $appointment): array
    {
        $created = Carbon::parse($this->workspace->created_at);
        $generated = $this->workspace->last_generated_at ? Carbon::parse($this->workspace->last_generated_at) : null;
        $uploaded = $this->workspace->supporting_uploaded_at ? Carbon::parse($this->workspace->supporting_uploaded_at) : null;
        $analyzed = $this->workspace->analyzed_at ? Carbon::parse($this->workspace->analyzed_at) : null;
        $completed = $this->workspace->completed_at ? Carbon::parse($this->workspace->completed_at) : null;

        $timeline = [
            [
                'title' => 'Transaction Created',
                'date' => $created->format('n/j/Y g:i A'),
                'tone' => 'blue',
            ],
        ];

        if ($generated) {
            $timeline[] = [
                'title' => 'Document PDF Generated',
                'date' => $generated->format('n/j/Y g:i A'),
                'tone' => 'blue',
            ];
        }

        if ($appointment) {
            $timeline[] = [
                'title' => match ($appointment->status) {
                    'approved' => 'Appointment Scheduled',
                    'attended' => 'Appointment Attended',
                    'rejected' => 'Appointment Rejected',
                    'missed' => 'Appointment Missed',
                    default => 'Appointment Requested',
                },
                'date' => optional($appointment->updated_at)->format('n/j/Y g:i A') ?? 'N/A',
                'tone' => match ($appointment->status) {
                    'attended', 'approved' => 'green',
                    'rejected', 'missed' => 'red',
                    default => 'amber',
                },
            ];
        }

        if ($uploaded) {
            $timeline[] = [
                'title' => 'Document Photo Uploaded',
                'date' => $uploaded->format('n/j/Y g:i A'),
                'tone' => 'amber',
            ];
        }

        // Only fires if analysis actually ran and was NOT approved — a rejected/
        // failed review is a distinct, meaningful timeline moment on its own.
        if ($analyzed && $this->workspace->analysis_status === 'rejected') {
            $timeline[] = [
                'title' => 'Document Review Rejected',
                'date' => $analyzed->format('n/j/Y g:i A'),
                'tone' => 'red',
            ];
        }

        // The actual completion moment: VLM analysis approved AND stored,
        // not just "workspace touched." This is the real answer to "when
        // was this transaction completed."
        if ($completed && $this->workspace->isCompleted()) {
            $timeline[] = [
                'title' => 'Transaction Completed — Document Approved',
                'date' => $completed->format('n/j/Y g:i A'),
                'tone' => 'green',
            ];
        }

        return collect($timeline)
            ->sortBy(fn ($item) => $item['date'] === 'N/A' ? '' : $item['date'])
            ->values()
            ->reverse()
            ->values()
            ->all();
    }

    public function render()
    {
        $user = $this->workspace->user;
        $appointment = $this->latestAppointment();

        $status = $this->resolveStatus();
        $appointmentStatus = $this->appointmentStatus($appointment);
        $generatedPdfPath = $this->workspace->generated_pdf_path;

        $supportingFilePath = $this->workspace->supporting_file_path;
        $supportingFileUrl = $supportingFilePath ? Storage::disk('public')->url($supportingFilePath) : null;

        $analysisData = $this->workspace->analysis_data ?? [];
        $isDocApproved = $this->workspace->analysis_status === 'approved';

        $isTechnicalFailure = $this->workspace->analysis_status === 'rejected'
            && !empty($analysisData['error_code'] ?? null);

        $hasContentAnalysis = array_key_exists('matches_document', $analysisData);

        // NOTE: field names below (appointment_date, time_slot, queue_number)
        // are assumed based on the app's established architecture — adjust
        // if your actual Appointment model uses different column names.
        $appointmentDateLabel = $appointment?->appointment_date
            ? Carbon::parse($appointment->appointment_date)->format('F j, Y')
            : 'Not yet scheduled';

        $appointmentTimeLabel = $appointment
            ? ($appointment->session ? ucfirst($appointment->session) . ' Session' : ($appointment->time_slot ?: 'Not specified'))
            : null;

        $queueNumberLabel = $appointment?->queue_number
            ? str_pad((string) $appointment->queue_number, 2, '0', STR_PAD_LEFT)
            : '—';

        return view('livewire.admin.transaction-record-show', [
            'studentName' => $user ? $this->fullName($user) : 'Unknown student',
            'status' => $status,
            'appointmentStatus' => $appointmentStatus,
            'appointment' => $appointment,
            'appointmentDateLabel' => $appointmentDateLabel,
            'appointmentTimeLabel' => $appointmentTimeLabel,
            'queueNumberLabel' => $queueNumberLabel,
            'programLabel' => $this->formatProgram($user),
            'timeline' => $this->timeline($status, $appointment),
            'generatedPdfUrl' => $generatedPdfPath ? Storage::disk('public')->url($generatedPdfPath) : null,
            'supportingFileUrl' => $supportingFileUrl,
            'templatePreviewUrl' => $this->workspace->version?->image_path
                ? Storage::disk('public')->url($this->workspace->version->image_path)
                : null,
            'pdfMimeLabel' => $this->documentMimeLabel($generatedPdfPath),
            'supportingMimeLabel' => $this->documentMimeLabel($supportingFilePath),
            'supportingFileIsImage' => $this->isImageFile($supportingFilePath),
            'analysisStatus' => $this->workspace->analysis_status,
            'analysisSummary' => $this->workspace->analysis_summary,
            'analysisData' => $analysisData,
            'isDocApproved' => $isDocApproved,
            'isTechnicalFailure' => $isTechnicalFailure,
            'hasContentAnalysis' => $hasContentAnalysis,
            'ocrText' => $this->workspace->ocr_text,
            'rejectionReason' => $this->workspace->rejection_reason,
            'processingStage' => $this->workspace->processing_stage,
        ])->layout('layouts.app', [
            'title' => 'Transactions > ' . ($user ? $this->fullName($user) : 'Record') . ' - ' . ($this->workspace->template?->name ?: 'Document'),
        ]);
    }
}