<?php

namespace App\Models;

use App\Services\Workflow\Seal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail. Updates and deletes are refused at the model level, and each row
 * carries a hash chained to the previous row, so edits or deletions made directly in the
 * database are detectable (see Seal::auditChainIntact).
 */
class TransactionAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (TransactionAuditLog $log) {
            if (! $log->created_at) {
                $log->created_at = now();
            }

            $prev = static::query()
                ->where('workspace_id', $log->workspace_id)
                ->whereNotNull('hash')
                ->orderByDesc('id')
                ->value('hash');

            $log->prev_hash = $prev;
            $log->hash = Seal::auditHash($log, $prev);
        });

        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentWorkspace::class, 'workspace_id', 'workspace_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}