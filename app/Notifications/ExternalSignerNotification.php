<?php

namespace App\Notifications;

use App\Models\TransactionApproval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use App\Services\Workflow\ExternalSigning;
use App\Workflow\Documents\DocumentRegistry;


/**
 * Sent to people with no DocuMate account, via Notification::route('mail', $email).
 * Two kinds: the invitation (with the signing link) and the one-time verification code.
 */
class ExternalSignerNotification extends Notification
{
    private function __construct(private MailMessage $mail) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->mail;
    }

    public static function invite(TransactionApproval $approval, string $url, Carbon $expires): self
    {
        $ws = $approval->workspace;
        $student = $ws->field_values['student_name'] ?? 'a student';
        $name = $approval->external_name ?: 'there';
        if (ExternalSigning::kindFor($approval) === 'review') {
            $doc = DocumentRegistry::get($ws->document_type)->name();

            return new self(
                (new MailMessage)
                    ->subject("Review and signature request: {$doc}")
                    ->greeting("Hello {$name},")
                    ->line("The Discipline Committee Chairman asks you, as a committee member, to review and sign a {$doc} for the Office of the Vice President for Student Development, Leyte Normal University.")
                    ->action('Open the report', $url)
                    ->line('You do not need an account. You will confirm this email address with a one-time code, read the report, and sign on screen.')
                    ->line('This link works until ' . $expires->format('F j, Y g:i A') . '. The report is confidential. Please do not forward this email.')
            );
        }
        if (ExternalSigning::kindFor($approval) === 'endorse') {
            $doc = DocumentRegistry::get($ws->document_type)->name();
            $org = $ws->field_values['non_academic_org'] ?? 'an organization';

            return new self(
                (new MailMessage)
                    ->subject("Recommending approval request: {$doc}")
                    ->greeting("Hello {$name},")
                    ->line("{$student} listed you as the adviser of {$org} and asked for your recommending approval of a {$doc} for the Office of the Vice President for Student Development, Leyte Normal University.")
                    ->action('Open the request', $url)
                    ->line('You do not need an account. You will confirm this email address with a one-time code, review the request, and sign on screen.')
                    ->line('This link works until ' . $expires->format('F j, Y g:i A') . '. Please do not forward it.')
                    ->line('If you are not this organization\'s adviser, ignore this email or choose "I cannot approve this request" on the page.')
            );
        }

        return new self(
            (new MailMessage)
                ->subject('Rating request from the LNU Office of the VP for Student Development')
                ->greeting("Hello {$name},")
                ->line("{$student} listed you as someone who personally knows them, and asked you to complete the Model Student Faculty/Peer Rating Form for the Office of the Vice President for Student Development, Leyte Normal University.")
                ->action('Open the rating form', $url)
                ->line('You do not need an account. You will confirm this email address with a one-time code, give your rating, and sign on screen.')
                ->line('This link works until ' . $expires->format('F j, Y g:i A') . '. Please do not forward it: it is meant for you only.')
                ->line('If you do not know this student, ignore this email or choose "I cannot rate this student" on the page.')
        );
    }

    public static function code(string $code, int $minutes): self
    {
        return new self(
            (new MailMessage)
                ->subject('Your DocuMate verification code')
                ->greeting('Your verification code')
                ->line("Enter this code on the rating page: **{$code}**")
                ->line("It expires in {$minutes} minutes and works once. If you did not ask for it, you can ignore this email.")
        );
    }
}