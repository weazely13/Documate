<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\DocumentScanEvent;
use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The AI queue in the top bar: every AI check the office runs in the background, in one list.
 *  - Document uploads: the photo scan (ProcessDocumentAnalysis), tracked by DocumentScanEvent.
 *  - Workflow review: the pre-check on submitted transactions (ReviewTransactionSubmission), ai_flag on the workspace.
 *  - Appointments: the purpose check on requests (ReviewAppointmentSubmission), ai_flag on the appointment.
 * Each item is normalised to one shape so the view can list them together.
 */
class ScanStatusBar extends Component
{
    public const KINDS = [
        'upload' => 'Uploads',
        'workflow' => 'Workflow',
        'appointment' => 'Appointments',
    ];

    public bool $expanded = false;
    public bool $hasError = false;

    /** '' = everything, else one of KINDS. */
    public string $kind = '';

    /** A check left "pending" longer than this is treated as stale, not running. */
    private const STALE_HOURS = 6;

    #[On('scan-queued')]
    public function onScanQueued(): void
    {
        $this->expanded = true;
    }

    public function toggle(): void
    {
        $this->expanded = ! $this->expanded;
    }

    public function setKind(string $kind): void
    {
        $this->kind = array_key_exists($kind, self::KINDS) ? $kind : '';
    }

    /** Manual escape hatch if polling ever stalls: one fresh query without a page reload. */
    public function refreshNow(): void
    {
        $this->hasError = false;
        unset($this->items, $this->queue);
    }

    #[On('scan-status-changed')]
    public function onScanStatusChanged(): void
    {
        unset($this->items, $this->queue);
    }

    /** Every recent AI check, newest first, each as [kind, key, running, tone, status, who, what, at, url]. */
    #[Computed]
    public function items(): Collection
    {
        try {
            return $this->uploads()->concat($this->workflow())->concat($this->appointments())
                ->sortByDesc(fn ($i) => [$i['running'] ? 1 : 0, $i['at']?->getTimestamp() ?? 0])
                ->values();
        } catch (\Throwable $e) {
            // Never let a query error 500 the poll request: that kills the polling loop and freezes the overlay.
            Log::error('ScanStatusBar: failed to load AI queue', ['message' => $e->getMessage()]);
            $this->hasError = true;

            return collect();
        }
    }

    /** What the queue worker holds right now: jobs being run and jobs waiting, per kind. */
    #[Computed]
    public function queue(): array
    {
        $map = [
            'ProcessDocumentAnalysis' => 'upload',
            'ReviewTransactionSubmission' => 'workflow',
            'ReviewAppointmentSubmission' => 'appointment',
        ];
        $out = ['running' => 0, 'waiting' => 0];

        try {
            DB::table('jobs')->select('payload', 'reserved_at')->orderBy('id')->limit(500)->get()
                ->each(function ($job) use ($map, &$out) {
                    $name = class_basename(json_decode($job->payload, true)['displayName'] ?? '');
                    if (isset($map[$name])) {
                        $out[$job->reserved_at ? 'running' : 'waiting']++;
                    }
                });
        } catch (\Throwable) {
            // No database queue (e.g. the sync driver): the per-item states still tell the story.
        }

        return $out;
    }

    /** Running checks, counted directly so the badge stays cheap while the panel is closed. */
    #[Computed]
    public function activeCount(): int
    {
        try {
            $since = now()->subHours(self::STALE_HOURS);

            return StudentDocumentWorkspace::where('processing', true)->count()
                + StudentDocumentWorkspace::where('ai_flag', 'pending')->where('updated_at', '>=', $since)->count()
                + Appointment::where('ai_flag', 'pending')->where('status', 'pending')->where('updated_at', '>=', $since)->count();
        } catch (\Throwable $e) {
            Log::error('ScanStatusBar: failed to count running checks', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    private function uploads(): Collection
    {
        $latestIds = DocumentScanEvent::query()->selectRaw('MAX(id) as id')->groupBy('workspace_id')->pluck('id');
        if ($latestIds->isEmpty()) {
            return collect();
        }

        return DocumentScanEvent::with(['workspace.user', 'workspace.template'])
            ->whereIn('id', $latestIds)->latest()->limit(20)->get()
            ->map(fn ($e) => [
                'kind' => 'upload',
                'key' => 'u' . $e->id,
                'running' => in_array($e->status, ['uploading', 'queued', 'scanning'], true)
                    && $e->created_at->gt(now()->subHours(self::STALE_HOURS)),
                'tone' => match ($e->status) {
                    'approved' => 'done',
                    'rejected', 'failed' => 'bad',
                    'uploading', 'queued', 'scanning' => 'run',
                    default => 'idle',
                },
                'status' => $e->statusLabel(),
                'who' => $e->workspace?->user?->full_name ?? 'Unknown student',
                'what' => $e->workspace?->template?->name ?: 'Document',
                'at' => $e->created_at,
                'url' => route('admin.document-uploads.show', $e->workspace_id),
            ]);
    }

    private function workflow(): Collection
    {
        return StudentDocumentWorkspace::with(['user', 'template'])
            ->whereNotNull('ai_flag')
            ->where(fn ($q) => $q->where('ai_flag', 'pending')->where('updated_at', '>=', now()->subHours(self::STALE_HOURS))
                ->orWhere('ai_reviewed_at', '>=', now()->subDays(2)))
            ->latest('updated_at')->limit(20)->get()
            ->map(fn ($ws) => [
                'kind' => 'workflow',
                'key' => 'w' . $ws->getKey(),
                'running' => $ws->ai_flag === 'pending',
                'tone' => match ($ws->ai_flag) {
                    'pending' => 'run',
                    'clean' => 'done',
                    'flagged' => 'warn',
                    default => 'bad',
                },
                'status' => match ($ws->ai_flag) {
                    'pending' => 'Checking the submission…',
                    'clean' => 'Pre-check passed',
                    'flagged' => 'Flagged for a closer look',
                    default => 'Pre-check failed',
                },
                'who' => $ws->user?->full_name ?? 'Unknown student',
                'what' => $ws->documentName() ?: ($ws->template?->name ?? 'Transaction'),
                'at' => $ws->ai_flag === 'pending' ? $ws->updated_at : ($ws->ai_reviewed_at ?? $ws->updated_at),
                'url' => route('admin.workflow.show', $ws->getKey()),
            ]);
    }

    private function appointments(): Collection
    {
        return Appointment::with('user')
            ->where(fn ($q) => $q->where('ai_flag', 'pending')->where('status', 'pending')->where('updated_at', '>=', now()->subHours(self::STALE_HOURS))
                ->orWhere('ai_reviewed_at', '>=', now()->subDays(2)))
            ->latest('updated_at')->limit(20)->get()
            ->map(fn ($a) => [
                'kind' => 'appointment',
                'key' => 'a' . $a->appointment_id,
                'running' => $a->ai_flag === 'pending',
                'tone' => match ($a->ai_flag) {
                    'pending' => 'run',
                    'clean' => 'done',
                    'flagged' => 'warn',
                    default => 'bad',
                },
                'status' => match ($a->ai_flag) {
                    'pending' => 'Checking the request…',
                    'clean' => 'No issues',
                    'flagged' => 'Requires checking',
                    default => 'Check failed',
                },
                'who' => $a->user?->full_name ?? 'Unknown student',
                'what' => $a->purpose ?: 'Appointment request',
                'at' => $a->ai_flag === 'pending' ? $a->updated_at : ($a->ai_reviewed_at ?? $a->updated_at),
                'url' => route('admin.appointments.show', $a->appointment_id),
            ]);
    }

    public function render()
    {
        return view('livewire.admin.scan-status-bar');
    }
}
