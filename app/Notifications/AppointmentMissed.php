<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentMissed extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->appointment_id,

            // System-generated: no human actor, just the status icon.
            'icon' => 'bx-calendar-x',
            'color' => 'text-amber-600 bg-amber-100',
            'actor_name' => null,
            'actor_avatar' => null,

            'message' => 'You missed your appointment on ' . $this->appointment->appointment_date->format('F j, Y')
                . ' (' . ucfirst($this->appointment->session) . '). You can reapply for a new date.',
        ];
    }
}