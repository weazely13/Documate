<?php

namespace App\Support;

use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The VPSD office's queue of open paperless transactions, shared by Workflow Review
 * and the admin dashboard so both count the same things.
 */
class ReviewQueue
{
    /** Queues whose waiting time is the office's (or a signatory's) to act on, so it is flagged. */
    public const FLAGGED = ['issuance', 'attention', 'signing'];

    /** Paperless transactions past the draft stage. */
    public static function base(): Builder
    {
        return StudentDocumentWorkspace::query()
            ->usingWorkflow()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', 'draft');
    }

    /**
     * issuance = the office issues it next (fully signed, or a report a signatory issued),
     * attention = the office reviews it next, signing = with signatories, action_required = with the student.
     * A staff-issued document that is signed before the office gets it (signedBeforeOffice) is in Signing until then.
     */
    public static function apply(Builder $q, string $queue): Builder
    {
        $staffKeys = self::staffKeys();
        $signFirst = self::signFirstKeys();
        $officeNow = array_values(array_diff($staffKeys, $signFirst));   // staff-issued, straight to Issuance

        return match ($queue) {
            'issuance' => $q->where(fn ($w) => $w
                ->where('workflow_status', 'approved')
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereIn('document_type', $officeNow))),
            'attention' => $q->where(fn ($w) => $w
                ->where('workflow_status', 'submitted')
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereDoesntHave('approvals')
                    ->whereNotIn('document_type', $staffKeys))),
            'signing' => $q->where('workflow_status', 'under_review')->whereHas('approvals')
                ->whereNotIn('document_type', $officeNow),
            'action_required' => $q->where('workflow_status', 'action_required'),
        };
    }

    public static function query(string $queue): Builder
    {
        return self::apply(self::base(), $queue);
    }

    /**
     * Adds `status_changed_at` (last workflow status change) and `last_signed_at`
     * (last signature), which together say since when the transaction has been waiting.
     */
    public static function withWaitingSince(Builder $q): Builder
    {
        return $q
            ->withMax(['auditLogs as status_changed_at' => fn ($a) => $a->whereNotNull('to_status')], 'created_at')
            ->withMax(['approvals as last_signed_at' => fn ($a) => $a->whereNotNull('acted_at')], 'acted_at');
    }

    /** The later of the last status change and the last signature (needs withWaitingSince()). */
    public static function waitingSince(StudentDocumentWorkspace $ws): ?Carbon
    {
        return collect([$ws->last_signed_at, $ws->status_changed_at ?? $ws->submitted_at])
            ->filter()
            ->map(fn ($t) => Carbon::parse($t))
            ->max();
    }

    /**
     * Has waited at least $days school days: nothing has moved since (no status change, no signature), the same test
     * overdue() uses. With $under, also fewer than $under days: something moved, or it came in, after that cutoff.
     */
    public static function waited(Builder $q, ?int $atLeast, ?int $under): Builder
    {
        if ($atLeast !== null && $atLeast > 0) {
            $c = WaitingTime::cutoff($atLeast);
            $q->where('submitted_at', '<=', $c)
                ->whereDoesntHave('auditLogs', fn ($a) => $a->whereNotNull('to_status')->where('created_at', '>', $c))
                ->whereDoesntHave('approvals', fn ($a) => $a->where('acted_at', '>', $c));
        }
        if ($under !== null) {
            $c = WaitingTime::cutoff($under);
            $q->where(fn ($w) => $w->where('submitted_at', '>', $c)
                ->orWhereHas('auditLogs', fn ($a) => $a->whereNotNull('to_status')->where('created_at', '>', $c))
                ->orWhereHas('approvals', fn ($a) => $a->where('acted_at', '>', $c)));
        }

        return $q;
    }

    /** Open transactions in the flagged queues that have not moved for a week of school days. */
    public static function overdue(): Builder
    {
        $cutoff = WaitingTime::overdueCutoff();

        return self::base()
            ->where(fn ($w) => self::apply($w, 'issuance')->orWhere(fn ($x) => self::apply($x, 'attention'))->orWhere(fn ($x) => self::apply($x, 'signing')))
            ->whereDoesntHave('auditLogs', fn ($a) => $a->whereNotNull('to_status')->where('created_at', '>', $cutoff))
            ->whereDoesntHave('approvals', fn ($a) => $a->where('acted_at', '>', $cutoff));
    }

    /** Staff-issued document keys whose signers all sign before the office receives it. */
    private static function signFirstKeys(): array
    {
        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => $d->issuerIsStaff() && $d->signedBeforeOffice())
            ->keys()
            ->all();
    }

    /** Document keys a signatory or staff member issues (they skip the office's own signing queue). */
    private static function staffKeys(): array
    {
        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => $d->issuerIsStaff())
            ->keys()
            ->all();
    }
}
