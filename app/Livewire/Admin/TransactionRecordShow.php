<?php

namespace App\Livewire\Admin;

use App\Models\StudentDocumentWorkspace;
use App\Models\StudentVerification;
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
            'template.currentVersion',
            'version',
        ]);
    }

    protected function resolveStatus(): string
    {
        $verification = $this->workspace->user?->latestVerification;
        $hasPdf = filled($this->workspace->generated_pdf_path);
        $hasUpload = filled($verification?->e_slip_path);
        $createdAt = $this->workspace->created_at instanceof Carbon
            ? $this->workspace->created_at
            : Carbon::parse($this->workspace->created_at);

        return match (true) {
            $hasPdf && $hasUpload && Str::lower((string) $verification?->status) === StudentVerification::STATUS_VERIFIED => 'Completed',
            $hasPdf && $hasUpload => 'For Appointment',
            $hasPdf => 'Waiting Upload',
            $createdAt->lt(now()->subDays(14)) => 'Missed',
            default => 'Pending',
        };
    }

    protected function appointmentStatus(string $status): string
    {
        return match ($status) {
            'Completed', 'Waiting Upload' => 'Attended',
            'For Appointment' => 'Scheduled',
            'Missed' => 'Missed',
            default => 'Pending',
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
            $user->program,
            $user->year_level ? ('Year ' . $user->year_level) : null,
        ]))) ?: 'No program on file';
    }

    protected function sessionLabel(): string
    {
        $hour = (int) optional($this->workspace->created_at)->format('H');

        return $hour >= 12 ? 'Afternoon' : 'Morning';
    }

    protected function appointmentDate(): string
    {
        return optional($this->workspace->created_at)
            ? Carbon::parse($this->workspace->created_at)->addDays(7)->format('F j, Y')
            : 'TBA';
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

    protected function timeline(string $status): array
    {
        $verification = $this->workspace->user?->latestVerification;
        $created = Carbon::parse($this->workspace->created_at);
        $saved = Carbon::parse($this->workspace->updated_at);
        $generated = $this->workspace->last_generated_at ? Carbon::parse($this->workspace->last_generated_at) : null;
        $uploaded = $verification?->created_at ? Carbon::parse($verification->created_at) : null;

        $timeline = [
            [
                'title' => 'Transaction Created',
                'date' => $created->format('n/j/Y g:i A'),
                'tone' => 'green',
            ],
            [
                'title' => 'Workspace Last Updated',
                'date' => $saved->format('n/j/Y g:i A'),
                'tone' => 'blue',
            ],
        ];

        if ($generated) {
            $timeline[] = [
                'title' => 'Generated PDF Saved',
                'date' => $generated->format('n/j/Y g:i A'),
                'tone' => 'blue',
            ];
        }

        if ($uploaded) {
            $timeline[] = [
                'title' => 'Supporting File Uploaded',
                'date' => $uploaded->format('n/j/Y g:i A'),
                'tone' => 'amber',
            ];
        }

        $timeline[] = [
            'title' => match ($status) {
                'Completed', 'Waiting Upload' => 'Appointment Attended',
                'For Appointment' => 'Appointment Scheduled',
                'Missed' => 'Appointment Missed',
                default => 'Awaiting Next Action',
            },
            'date' => $created->copy()->addDays(7)->format('n/j/Y 10:30 AM'),
            'tone' => match ($status) {
                'Missed' => 'red',
                'Pending' => 'slate',
                default => 'green',
            },
        ];

        return array_reverse($timeline);
    }

    public function render()
    {
        $user = $this->workspace->user;
        $verification = $user?->latestVerification;
        $status = $this->resolveStatus();
        $appointmentStatus = $this->appointmentStatus($status);
        $generatedPdfPath = $this->workspace->generated_pdf_path;
        $supportingFilePath = $verification?->e_slip_path;

        return view('livewire.admin.transaction-record-show', [
            'studentName' => $user ? $this->fullName($user) : 'Unknown student',
            'status' => $status,
            'appointmentStatus' => $appointmentStatus,
            'programLabel' => $this->formatProgram($user),
            'sessionLabel' => $this->sessionLabel(),
            'appointmentDate' => $this->appointmentDate(),
            'timeline' => $this->timeline($status),
            'generatedPdfUrl' => $generatedPdfPath ? Storage::disk('public')->url($generatedPdfPath) : null,
            'supportingFileUrl' => $supportingFilePath ? Storage::disk('public')->url($supportingFilePath) : null,
            'templatePreviewUrl' => $this->workspace->version?->image_path
                ? Storage::disk('public')->url($this->workspace->version->image_path)
                : null,
            'pdfMimeLabel' => $this->documentMimeLabel($generatedPdfPath),
            'supportingMimeLabel' => $this->documentMimeLabel($supportingFilePath),
            'supportingFileIsImage' => $this->isImageFile($supportingFilePath),
        ])->layout('layouts.app', [
            'title' => 'Transactions > ' . ($user ? $this->fullName($user) : 'Record') . ' - ' . ($this->workspace->template?->name ?: 'Document'),
        ]);
    }
}
