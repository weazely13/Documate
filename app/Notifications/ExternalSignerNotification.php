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
        $doc = DocumentRegistry::get($ws->document_type);
        $student = $ws->field_values['student_name'] ?? 'a student';
        $org = $ws->field_values['non_academic_org'] ?? 'an organization';

        [$subject, $intro, $action, $disregard, $showStudent] = match (ExternalSigning::kindFor($approval)) {
            'review' => [
                "Action requested: review and sign {$doc->name()}",
                'The Discipline Committee Chairman respectfully requests your review and signature, as a member of the committee, on the confidential form below.',
                'Review and Sign the Report',
                'If you are not a member of the Discipline Committee, or you were not expecting this request, please disregard this email and do not forward it. No action will be taken on your behalf.',
                false,
            ],
            'endorse' => [
                "Action requested: recommending approval of {$doc->name()}",
                "{$student} has listed you as the adviser of {$org} and respectfully requests your recommending approval of the form below.",
                'Review and Sign the Request',
                "If you are not the adviser of {$org}, please disregard this email, or select “I cannot approve this request” on the page. No action will be taken on your behalf.",
                true,
            ],
            'consent' => [
                "Action requested: parent's consent for student assistantship",
                "{$student} has listed you as their parent/guardian and respectfully requests your consent, on the form below, for them to work as a student assistant of Leyte Normal University.",
                'Review and Sign the Consent',
                "If you are not the parent or guardian of {$student}, please disregard this email, or select “I cannot give consent” on the page. No action will be taken on your behalf.",
                true,
            ],
            'guardian' => [
                "Action requested: parent's signature on a student complaint",
                "{$student} has listed you as their parent/guardian and respectfully requests your signature on the confidential complaint form below.",
                'Review and Sign the Complaint',
                "If you are not the parent or guardian of {$student}, please disregard this email, or select “I cannot sign” on the page. No action will be taken on your behalf.",
                true,
            ],
            'witness' => [
                "Action requested: witness signature on a student complaint",
                "{$student} has named you as a witness and respectfully requests your signature on the confidential complaint form below.",
                'Review and Sign as Witness',
                "If you did not witness this matter, please disregard this email, or select “I cannot sign” on the page. No action will be taken on your behalf.",
                true,
            ],
            default => [
                "Action requested: {$doc->name()}",
                "{$student} has identified you as someone who personally knows them and respectfully requests that you complete the form below.",
                'Open the Rating Form',
                'If you are not familiar with this student, please disregard this email, or select “I cannot rate this student” on the page. No action will be taken on your behalf.',
                true,
            ],
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Dear ' . ($approval->external_name ?: 'Sir/Madam') . ',')
            ->line($intro)
            ->line('**Document:** ' . $doc->name())
            ->line('**Form No.:** ' . $doc->formCode())
            ->line('**Reference:** ' . $ws->transaction_code);

        if ($showStudent) {
            $mail->line('**Student:** ' . $student);
        }

        $mail->action($action, $url)
            ->line('No account is required. You will first confirm your email address with a one-time verification code, then review the document and sign it on screen.')
            ->line('**Link validity:** this link expires on ' . $expires->format('l, F j, Y \a\t g:i A') . '. If it has expired, please ask the VPSD office to issue a new one.')
            ->line($disregard)
            ->salutation('Respectfully, Office of the Vice President for Student Development, Leyte Normal University');

        return new self(MailBranding::apply($mail));
    }

    public static function code(string $code, int $minutes): self
    {
        return new self(MailBranding::apply(
            (new MailMessage)
                ->subject('Your DocuMate verification code')
                ->greeting('Your verification code')
                ->line("Enter this code on the rating page: **{$code}**")
                ->line("It expires in {$minutes} minutes and works once. If you did not ask for it, you can ignore this email.")
        ));
    }
}