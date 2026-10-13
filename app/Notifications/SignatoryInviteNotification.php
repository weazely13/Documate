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
        return (new MailMessage)
            ->subject('Set up your DocuMate signing account')
            ->greeting('Hello ' . trim($notifiable->first_name . ' ' . $notifiable->last_name) . ',')
            ->line('The Office of the Vice President for Student Development, Leyte Normal University, set up a DocuMate account for you so you can review and sign student documents online.')
            ->action('Set your password', $this->url)
            ->line('This link works until ' . $this->expires->format('F j, Y g:i A') . ' and can be used once. Please do not share it.')
            ->line('If you were not expecting this, you can ignore this email, or contact the VPSD office.');
    }
}