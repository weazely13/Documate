<?php

namespace App\Notifications;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Notifications\Notification;
use App\Models\User;
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
        public string $icon = 'bx-bell',
        public string $color = 'text-blue-600 bg-blue-100',
        public ?User $actor = null,
        public bool $namePrefix = false,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'workflow',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'color' => $this->color,
            'name_prefix' => $this->namePrefix,
            'actor_name' => $this->actor?->first_name,
            'actor_avatar' => $this->actor?->profile_picture
                ? asset('storage/' . $this->actor->profile_picture)
                : null,
        ];
    }

    private static function docName(StudentDocumentWorkspace $ws): string
    {
        return DocumentRegistry::find($ws->document_type)?->name() ?? 'document';
    }

    /** To the person who holds the role: a signature is waiting. */
    public static function forSigner(TransactionApproval $approval, ?User $actor = null): self
    {
        $ws = $approval->workspace;
        $who = $ws->field_values['student_name'] ?? 'a student';
        $note = DocumentRegistry::find($ws->document_type)?->signingNote();

        return new self(
            'Signature needed',
            self::docName($ws) . " for {$who} ({$ws->transaction_code}) is waiting for your signature." . ($note ? " {$note}" : ''),
            route('signatory.show', $approval->id),
            'bx-pen',
            'text-amber-600 bg-amber-100',
            $actor ?? $ws->user,
        );
    }

    /** To VPSD reviewers: something arrived or came back. */
    public static function forReviewers(StudentDocumentWorkspace $ws, string $what): self
    {
        $doc = self::docName($ws);

        return new self(
            $what === 'resubmitted' ? 'Transaction resubmitted' : 'New transaction submitted',
            ($what === 'resubmitted' ? 'resubmitted' : 'submitted') . " a {$doc} ({$ws->transaction_code}) for review.",
            route('admin.workflow.show', $ws->workspace_id),
            $what === 'resubmitted' ? 'bx-refresh' : 'bx-file-blank',
            'text-blue-600 bg-blue-100',
            $ws->user,
            true, // put the actor's name in front of the message
        );
    }

    /** To the student. */
    public static function forStudent(StudentDocumentWorkspace $ws, TransactionStatus $to, ?User $actor = null): self
    {
        $doc = self::docName($ws);
        [$title, $text, $icon, $color] = match ($to) {
            TransactionStatus::ActionRequired => ['Changes requested', "The VPSD office asked for changes to your {$doc} ({$ws->transaction_code}).", 'bx-edit', 'text-amber-600 bg-amber-100'],
            TransactionStatus::Rejected       => ['Transaction rejected', "Your {$doc} ({$ws->transaction_code}) was rejected. Open it to see the reason.", 'bx-x-circle', 'text-rose-600 bg-rose-100'],
            TransactionStatus::Completed      => ['Transaction completed', "Your {$doc} ({$ws->transaction_code}) is signed and ready to download.", 'bx-check-circle', 'text-emerald-600 bg-emerald-100'],
            TransactionStatus::Voided         => ['Document voided', "Your {$doc} ({$ws->transaction_code}) was voided by the VPSD office. Open it to see why and to continue with the corrected draft.", 'bx-block', 'text-slate-600 bg-slate-200'],
            default                           => ['Transaction updated', "Your {$doc} ({$ws->transaction_code}) was updated.", 'bx-info-circle', 'text-blue-600 bg-blue-100'],
        };

        return new self($title, $text, route('student.transactions.show', $ws->workspace_id), $icon, $color, $actor);
    }
    /** To the student: the office issued a document for them. */
    public static function forIssued(StudentDocumentWorkspace $ws, ?User $actor = null): self
    {
        $doc = DocumentRegistry::find($ws->document_type);
        $url = route('student.transactions.show', $ws->workspace_id);
        $message = $doc?->issuedMessage($ws->field_values ?? []);

        if ($message) {
            return new self($doc->name() . ' issued', $message, $url, 'bx-file', 'text-blue-600 bg-blue-100', $actor);
        }

        if ($doc?->issuerIsStaff()) {
            return new self(
                $doc->name() . ' issued',
                "A {$doc->name()} ({$ws->transaction_code}) was issued concerning you. Open it to read the details.",
                $url,
                'bx-file',
                'text-blue-600 bg-blue-100',
                $actor,
            );
        }

        $detail = $doc ? $doc->summary($ws->field_values ?? []) : '';

        return new self(
            'Issued by the VPSD office',
            self::docName($ws) . " ({$ws->transaction_code}) was issued for you" . ($detail ? ": {$detail}" : '') . '. It is waiting for signature.',
            $url,
            'bx-file',
            'text-blue-600 bg-blue-100',
            $actor,
        );
    }
    /** To the student: the signer set the counselling schedule. */
    public static function forSchedule(StudentDocumentWorkspace $ws, ?User $actor = null): self
    {
        $doc = DocumentRegistry::find($ws->document_type);

        return new self(
            'Counselling schedule set',
            $doc?->issuedMessage($ws->field_values ?? []) ?? 'Your schedule was set.',
            route('student.transactions.show', $ws->workspace_id),
            'bx-calendar-check',
            'text-blue-600 bg-blue-100',
            $actor,
        );
    }
}
