<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * System-wide audit trail (sign-ins, record changes, exports). Append-only: updates and deletes are
 * refused at the model level. Write through App\Support\Audit, not directly.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'login' => 'Sign in',
        'logout' => 'Sign out',
        'login_failed' => 'Failed sign-in',
        'created' => 'Create',
        'updated' => 'Update',
        'deleted' => 'Delete',
        'restored' => 'Restore',
        'exported' => 'Export',
        'workflow' => 'Workflow', // rows read from transaction_audit_logs
    ];

    /** Every module the trail records, so the filter offers them before any activity happens. Keep in step with $auditModule on Auditable models. */
    public const MODULES = [
        'Appointments',
        'Audit Trail',
        'Authentication',
        'Clearance',
        'Feedback & Issues',
        'Organizations',
        'Semesters & Verification',
        'Signatories',
        'Templates',
        'Transactions',
        'Users',
        'Verification',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->created_at ??= now();
        });

        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
