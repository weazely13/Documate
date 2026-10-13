<?php

namespace App\Models\Concerns;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Models\TransactionApproval;
use App\Models\TransactionAuditLog;
use App\Services\Workflow\Seal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * Lives at app/Models/Concerns/HasTransactionWorkflow.php. There must be ONLY ONE copy of this file.
 * Add `use HasTransactionWorkflow;` to App\Models\StudentDocumentWorkspace.
 */
trait HasTransactionWorkflow
{
    protected static function bootHasTransactionWorkflow(): void
    {
        static::saving(function ($ws) {
            $rawStatus = $ws->getRawOriginal('workflow_status');
            $frozen = [TransactionStatus::Completed->value, TransactionStatus::Voided->value];

            // 1. Once COMPLETED (or VOIDED) the record's identity and answers are frozen.
            //    The single allowed change: COMPLETED -> VOIDED, touching nothing else that is locked.
            if ($ws->exists && in_array($rawStatus, $frozen, true)) {
                $locked = [
                    'field_values', 'document_type', 'user_id', 'transaction_code',
                    'content_hash', 'verification_token', 'workflow_status', 'issued_at',
                ];

                $dirty = array_values(array_intersect($locked, array_keys($ws->getDirty())));
                $voiding = $rawStatus === TransactionStatus::Completed->value
                    && $ws->workflow_status === TransactionStatus::Voided
                    && $dirty === ['workflow_status'];

                if ($dirty && ! $voiding) {
                    throw new LogicException('A completed transaction cannot be modified.');
                }
            }

            // 2. Once anyone has signed, the answers they signed cannot change.
            if ($ws->exists
                && $ws->isDirty('field_values')
                && $ws->approvals()->where('status', ApprovalStatus::Approved->value)->exists()) {
                throw new LogicException('The form cannot be edited after a signature has been applied.');
            }

            // 3. The moment it becomes COMPLETED, fingerprint it and mint the public verification token.
            if ($ws->workflow_status === TransactionStatus::Completed && $rawStatus !== TransactionStatus::Completed->value) {
                $ws->content_hash = Seal::contentHash($ws);
                $ws->verification_token ??= Str::random(40);
                $ws->sealed_at = now();
            }
        });
    }

    public function initializeHasTransactionWorkflow(): void
    {
        $this->mergeCasts([
            'workflow_status' => TransactionStatus::class,
            'submitted_at' => 'datetime',
            'issued_at' => 'datetime',
            'sealed_at' => 'datetime',
            'voided_at' => 'datetime',
        ]);
    }
    /** Documents the VPSD office issued to this student whose date has not passed yet. */
    public static function officeNoticesFor(int $userId): \Illuminate\Support\Collection
    {
        $keys = collect(\App\Workflow\Documents\DocumentRegistry::all())
            ->filter(fn ($d) => $d->showsAsNotice())->keys()->all();

        if (! $keys) {
            return collect();
        }

        return static::query()
            ->where('user_id', $userId)
            ->whereIn('document_type', $keys)
            ->whereIn('workflow_status', ['under_review', 'approved', 'completed'])
            ->latest('updated_at')
            ->get()
            ->filter(function ($ws) {
                $doc = \App\Workflow\Documents\DocumentRegistry::get($ws->document_type);
                $date = $doc->noticeDate($ws->field_values ?? []);

                if ($date) {
                    return \Carbon\Carbon::parse($date)->endOfDay()->isFuture();
                }

                // No date on the document: keep it visible for 30 days after it was issued.
                $since = $ws->submitted_at ?? $ws->created_at;

                return $since ? $since->gt(now()->subDays(30)) : false;
            })
            ->values();
    }
    public function approvals(): HasMany
    {
        return $this->hasMany(TransactionApproval::class, 'workspace_id', 'workspace_id')->orderBy('step_order');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TransactionAuditLog::class, 'workspace_id', 'workspace_id')->orderBy('created_at')->orderBy('id');
    }

    public function scopeUsingWorkflow(Builder $query): Builder
    {
        return $query->whereNotNull('workflow_status');
    }

    public function usesWorkflow(): bool
    {
        return $this->workflow_status !== null;
    }

    public function currentApproval(): ?TransactionApproval
    {
        return $this->approvals()->where('status', ApprovalStatus::Pending->value)->first();
    }
}