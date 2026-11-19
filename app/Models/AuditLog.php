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
        'Feedback',
        'Organizations',
        'Semesters & Verification',
        'Signatories',
        'Templates',
        'Transactions',
        'Users',
        'Verification',
    ];

    /** Modules renamed since: older entries keep the name they were written with, and the trail shows the new one. */
    public const RENAMED_MODULES = [
        'Feedback & Issues' => 'Feedback',
    ];

    /** The module as the trail shows it. */
    public static function moduleLabel(?string $module): string
    {
        return self::RENAMED_MODULES[$module] ?? (string) $module;
    }

    /** Every stored name that shows as this module: itself and its old names. */
    public static function moduleNames(string $module): array
    {
        return [$module, ...array_keys(self::RENAMED_MODULES, $module, true)];
    }

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
