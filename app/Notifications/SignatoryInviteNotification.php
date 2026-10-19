<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class SignatoryInviteNotification extends Notification
{
    public function __construct(private string $url, private Carbon $expires) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Set up your DocuMate signing account')
            ->greeting('Hello ' . trim($notifiable->first_name . ' ' . $notifiable->last_name) . ',')
            ->line('The Office of the Vice President for Student Development, Leyte Normal University, set up a DocuMate account for you so you can review and sign student documents online.')
            ->action('Set your password', $this->url)
            ->line('For your security, this link will expire on ' . $this->expires->format('l, F j, Y \a\t g:i A') . ' and can be used only once.')
            ->line('If you were not expecting this invitation, please disregard this email or contact the VPSD office. No account changes will be made until you set a password.');

        return MailBranding::apply($mail);
    }
}