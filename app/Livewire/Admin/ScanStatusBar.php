<?php

namespace App\Livewire\Admin;

use App\Models\DocumentScanEvent;
use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ScanStatusBar extends Component
{
    public bool $expanded = false;
    public bool $hasError = false;

    #[On('scan-queued')]
    public function onScanQueued(): void
    {
        $this->expanded = true;
    }

    public function toggle(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /**
     * Manual escape hatch — if polling ever silently stalls again (e.g. a
     * transient error broke the JS interval), this gives the admin a way
     * to force one fresh query without needing a full page reload.
     */
    public function refreshNow(): void
    {
        $this->hasError = false;
        unset($this->events);
        unset($this->activeCount);
    }

    #[Computed]
    public function events()
    {
        try {
            $latestIds = DocumentScanEvent::query()
                ->selectRaw('MAX(id) as id')
                ->groupBy('workspace_id')
                ->pluck('id');

            if ($latestIds->isEmpty()) {
                return collect();
            }

            return DocumentScanEvent::with(['workspace.user', 'workspace.template'])
                ->whereIn('id', $latestIds)
                ->latest()
                ->limit(20)
                ->get();
        } catch (\Throwable $e) {
            // Never let a query error 500 the whole poll request — that's what
            // kills the client-side polling loop and freezes this overlay.
            // Log it, show a soft error state instead, and let the next poll
            // (or the manual refresh button) try again cleanly.
            Log::error('ScanStatusBar: failed to load events', ['message' => $e->getMessage()]);
            $this->hasError = true;
            return collect();
        }
    }

    #[Computed]
    public function activeCount(): int
    {
        try {
            return StudentDocumentWorkspace::where('processing', true)->count();
        } catch (\Throwable $e) {
            Log::error('ScanStatusBar: failed to count active scans', ['message' => $e->getMessage()]);
            return 0;
        }
    }

    #[On('scan-status-changed')]
    public function onScanStatusChanged(): void
    {
        unset($this->events);
    }

    public function render()
    {
        return view('livewire.admin.scan-status-bar');
    }
}