<?php

namespace App\Listeners;

use App\Models\DocumentScanEvent;
use Illuminate\Auth\Events\Logout;

class ClearDocumentScanEventsOnLogout
{
    public function handle(Logout $event): void
    {
        // Only relevant for admin-facing scan activity; skip the wipe if a
        // student/officer session ends, since they never see or drive this feed.
        $role = $event->user?->role?->role_name ?? null;

        if ($role !== 'Admin') {
            return;
        }

        DocumentScanEvent::query()->delete();
    }
}