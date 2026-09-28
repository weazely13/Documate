<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAppointmentBooked extends Notification
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
        $isReapply = $this->appointment->reapplied_from_id !== null;
        $user = $this->appointment->user;

        // Name is bolded separately in the UI, so the message text starts right after it.
        $message = $isReapply
            ? 'reapplied for a missed appointment — new date: ' . $this->appointment->appointment_date->format('F j, Y') . '.'
            : 'booked an appointment for ' . $this->appointment->appointment_date->format('F j, Y') . '.';

        return [
            'appointment_id' => $this->appointment->appointment_id,

            'icon' => $isReapply ? 'bx-refresh' : 'bx-calendar-plus',
            'color' => $isReapply
                ? 'text-indigo-600 bg-indigo-100'
                : 'text-blue-600 bg-blue-100',

            'actor_name' => $user->first_name,
            'actor_avatar' => $user->profile_picture
                ? asset('storage/' . $user->profile_picture)
                : null,

            'message' => $message,
        ];
    }
}