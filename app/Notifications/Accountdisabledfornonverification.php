<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountDisabledForNonVerification extends Notification
{
    use Queueable;

    public function __construct(public Setting $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_disabled_non_verification',
            'setting_id' => $this->period->id,

            // System-generated: no human actor, so the bell just shows a status icon.
            'icon' => 'bx-lock-alt',
            'color' => 'text-rose-600 bg-rose-100',
            'actor_name' => null,
            'actor_avatar' => null,

            'message' => 'Your account has been disabled because you did not complete verification before the deadline. Please log in and upload your e-slip to restore access.',
        ];
    }
}