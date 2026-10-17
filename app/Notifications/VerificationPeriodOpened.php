<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VerificationPeriodOpened extends Notification
{
    use Queueable;

    public function __construct(public Setting $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $deadline = $this->period->verification_end_date->format('F j, Y');

        return [
            'type' => 'verification_period_opened',
            'setting_id' => $this->period->id,
            'deadline' => $this->period->verification_end_date->toDateString(),

            // System-generated: no human actor, just the status icon.
            'icon' => 'bx-calendar-check',
            'color' => 'text-blue-600 bg-blue-100',
            'actor_name' => null,
            'actor_avatar' => null,

            'message' => "Account verification is now open. Please upload your e-slip by {$deadline} to avoid account suspension.",
        ];
    }
}