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
    public const FLAGGED = ['attention', 'signing'];

    /** Paperless transactions past the draft stage. */
    public static function base(): Builder
    {
        return StudentDocumentWorkspace::query()
            ->usingWorkflow()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', 'draft');
    }

    /** attention = the office acts next, signing = with signatories, action_required = with the student. */
    public static function apply(Builder $q, string $queue): Builder
    {
        $staffKeys = self::staffKeys();

        return match ($queue) {
            'attention' => $q->where(fn ($w) => $w
                ->whereIn('workflow_status', ['submitted', 'approved'])
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereDoesntHave('approvals'))
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereIn('document_type', $staffKeys))),
            'signing' => $q->where('workflow_status', 'under_review')->whereHas('approvals')
                ->whereNotIn('document_type', $staffKeys),
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

    /** Open transactions in the flagged queues that have not moved for a week of school days. */
    public static function overdue(): Builder
    {
        $cutoff = WaitingTime::overdueCutoff();

        return self::base()
            ->where(fn ($w) => self::apply($w, 'attention')->orWhere(fn ($x) => self::apply($x, 'signing')))
            ->whereDoesntHave('auditLogs', fn ($a) => $a->whereNotNull('to_status')->where('created_at', '>', $cutoff))
            ->whereDoesntHave('approvals', fn ($a) => $a->where('acted_at', '>', $cutoff));
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
