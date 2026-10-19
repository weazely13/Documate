<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

final class MailBranding
{
    public static function apply(MailMessage $mail): MailMessage
    {
        $path = public_path('images/DocumateLogo.png');

        if (is_file($path)) {
            $mail->withSymfonyMessage(fn ($m) => $m->embedFromPath($path, 'documate-logo.png', 'image/png'));
        }

        return $mail;
    }
}