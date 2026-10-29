<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes rows to the system audit trail. Recording never breaks the action being recorded:
 * failures go to the log instead.
 */
final class Audit
{
    /** Fields whose values are never stored, only that they changed. */
    private const MASKED = ['password', 'remember_token', 'token', 'secret', 'signature_path', 'e_slip_path'];

    public static function record(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $changes = null,
        ?User $actor = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id' => $actor?->getKey(),
                'user_role' => $actor?->role?->role_name,
                'action' => $action,
                'module' => $module,
                'description' => Str::limit($description, 495),
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'changes' => $changes ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 495) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: ' . $e->getMessage(), ['action' => $action, 'module' => $module]);
        }
    }

    /** A printable, size-capped version of an attribute value, masked when sensitive. */
    public static function value(string $field, mixed $value): mixed
    {
        foreach (self::MASKED as $needle) {
            if (str_contains($field, $needle)) {
                return $value === null ? null : '••••••';
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return is_string($value) ? Str::limit($value, 200) : $value;
    }

    public static function userName(?User $user): string
    {
        if (! $user) {
            return 'System';
        }

        return $user->full_name ?: ($user->email ?: 'User #' . $user->getKey());
    }
}
