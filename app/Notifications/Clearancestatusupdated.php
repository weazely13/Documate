<?php

namespace App\Notifications;

use App\Models\Semester;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClearanceStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $organization,
        public Semester $semester,
    ) {}

    public function via(object $notifiable): array
    {
        // 'database' feeds your notification-bell Livewire component.
        // Swap/add channels (mail, broadcast) as needed.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        [$icon, $color] = match (strtolower($this->status)) {
            'approved', 'cleared'   => ['bx-check-circle', 'text-emerald-500 bg-emerald-50'],
            'rejected', 'declined' => ['bx-x-circle', 'text-rose-500 bg-rose-50'],
            'pending'              => ['bx-time-five', 'text-amber-500 bg-amber-50'],
            default                => ['bx-info-circle', 'text-blue-500 bg-blue-50'],
        };

        return [
            'type' => 'clearance_status_updated',
            'status' => $this->status,
            'organization' => $this->organization,
            'semester_id' => $this->semester->id,
            'semester_label' => $this->semester->label(),
            'message' => "Your clearance status with {$this->organization} was updated to \"{$this->status}\" for {$this->semester->label()}.",
            'url' => route('student.clearance-status'),
            'icon' => $icon,
            'color' => $color,
        ];
    }
}