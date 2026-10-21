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
            'approved', 'cleared'  => ['bx-check-circle', 'text-emerald-600 bg-emerald-100'],
            'rejected', 'declined' => ['bx-x-circle', 'text-rose-600 bg-rose-100'],
            'pending'              => ['bx-time-five', 'text-amber-600 bg-amber-100'],
            default                => ['bx-info-circle', 'text-blue-600 bg-blue-100'],
        };

        return [
            'type' => 'clearance_status_updated',
            'status' => $this->status,
            'organization' => $this->organization,
            'semester_id' => $this->semester->id,
            'semester_label' => $this->semester->label(),
            'title' => 'Clearance ' . strtolower($this->status),
            'message' => "Your clearance status with {$this->organization} was updated to \"{$this->status}\" for {$this->semester->label()}.",
            'url' => route('student.clearance-status'),
            'icon' => $icon,
            'color' => $color,
            'actor_name' => null,
            'actor_avatar' => null,
        ];
    }
}