<?php

namespace App\Notifications;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Notifications\Notification;

/**
 * One database notification for the whole workflow. Payload: type, title, message, url.
 * If your notification bell reads different keys, copy the shape from AppointmentStatusChanged
 * and change only toArray().
 */
class WorkflowNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['type' => 'workflow', 'title' => $this->title, 'message' => $this->message, 'url' => $this->url];
    }

    private static function docName(StudentDocumentWorkspace $ws): string
    {
        return DocumentRegistry::find($ws->document_type)?->name() ?? 'document';
    }

    /** To the person who holds the role: a signature is waiting. */
    public static function forSigner(TransactionApproval $approval): self
    {
        $ws = $approval->workspace;
        $who = $ws->field_values['student_name'] ?? 'a student';

        $note = DocumentRegistry::find($ws->document_type)?->signingNote();

        return new self(
            'Signature needed',
            self::docName($ws) . " for {$who} ({$ws->transaction_code}) is waiting for your signature." . ($note ? " {$note}" : ''),
            route('signatory.show', $approval->id),
        );
    }

    /** To VPSD reviewers: something arrived or came back. */
    public static function forReviewers(StudentDocumentWorkspace $ws, string $what): self
    {
        $who = $ws->field_values['student_name'] ?? 'A student';

        return new self(
            $what === 'resubmitted' ? 'Transaction resubmitted' : 'New transaction submitted',
            self::docName($ws) . " {$ws->transaction_code} from {$who} is ready for review.",
            route('admin.workflow.show', $ws->workspace_id),
        );
    }

    /** To the student. */
    public static function forStudent(StudentDocumentWorkspace $ws, TransactionStatus $to): self
    {
        $doc = self::docName($ws);
        [$title, $text] = match ($to) {
            TransactionStatus::ActionRequired => ['Changes requested', "The VPSD office asked for changes to your {$doc} ({$ws->transaction_code})."],
            TransactionStatus::Rejected => ['Transaction rejected', "Your {$doc} ({$ws->transaction_code}) was rejected. Open it to see the reason."],
            TransactionStatus::Completed => ['Transaction completed', "Your {$doc} ({$ws->transaction_code}) is signed and ready to download."],
            TransactionStatus::Voided => ['Document voided', "Your {$doc} ({$ws->transaction_code}) was voided by the VPSD office. Open it to see why and to continue with the corrected draft."],
            default => ['Transaction updated', "Your {$doc} ({$ws->transaction_code}) was updated."],
        };

        return new self($title, $text, route('student.transactions.show', $ws->workspace_id));
    }
    /** To the student: the office issued a document for them. */
    public static function forIssued(StudentDocumentWorkspace $ws): self
    {
        $doc = DocumentRegistry::find($ws->document_type);
        $url = route('student.transactions.show', $ws->workspace_id);

        if ($doc?->issuerIsStaff()) {
            return new self(
                $doc->name() . ' issued',
                "A {$doc->name()} ({$ws->transaction_code}) was issued concerning you. Open it to read the details.",
                $url,
            );
        }

        $detail = $doc ? $doc->summary($ws->field_values ?? []) : '';

        return new self(
            'Issued by the VPSD office',
            self::docName($ws) . " ({$ws->transaction_code}) was issued for you" . ($detail ? ": {$detail}" : '') . '. It is waiting for signature.',
            $url,
        );
    }
}
