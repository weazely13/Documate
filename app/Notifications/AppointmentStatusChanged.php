<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentStatusChanged extends Notification
{
    use Queueable;

    /**
     * @param Appointment $appointment
     * @param string      $action  'approved' | 'rejected' | 'rescheduled'
     * @param User|null   $actor   The staff/admin who made the change, if known.
     */
    public function __construct(
        public Appointment $appointment,
        public string $action,
        public ?User $actor = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $messages = [
            'approved' => 'Your appointment on ' . $this->appointment->appointment_date->format('F j, Y') . ' has been approved.',
            'rejected' => 'Your appointment was rejected: ' . $this->appointment->rejection_reason,
            'rescheduled' => 'Your appointment has been rescheduled to ' . $this->appointment->appointment_date->format('F j, Y') . '.',
        ];

        $icons = [
            'approved' => 'bx-check-circle',
            'rejected' => 'bx-x-circle',
            'rescheduled' => 'bx-calendar-alt',
        ];

        $colors = [
            'approved' => 'text-emerald-600 bg-emerald-100',
            'rejected' => 'text-rose-600 bg-rose-100',
            'rescheduled' => 'text-blue-600 bg-blue-100',
        ];

        return [
            'appointment_id' => $this->appointment->appointment_id,
            'action' => $this->action,

            'icon' => $icons[$this->action] ?? 'bx-calendar',
            'color' => $colors[$this->action] ?? 'text-slate-500 bg-slate-100',

            'actor_name' => $this->actor?->first_name,
            'actor_avatar' => $this->actor?->profile_picture
                ? asset('storage/' . $this->actor->profile_picture)
                : null,

            'message' => $messages[$this->action] ?? 'Your appointment status changed.',
        ];
    }
}