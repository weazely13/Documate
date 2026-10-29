<?php

namespace App\Support;

use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionAuditLog;
use Illuminate\Support\Collection;

/**
 * Who issued a transaction to a student: a signatory or staff member (issued_by_role)
 * or the VPSD office (issued_by_office). Student-filed transactions have no issuer.
 */
class Issuers
{
    private const ACTIONS = ['transaction.issued_by_role', 'transaction.issued_by_office'];

    /**
     * Issuer per workspace id, loaded in one query.
     *
     * @param  iterable<StudentDocumentWorkspace>  $workspaces
     * @return array<int, array{name: string, role: string}>
     */
    public static function for(iterable $workspaces): array
    {
        $workspaces = collect($workspaces)->keyBy('workspace_id');
        if ($workspaces->isEmpty()) {
            return [];
        }

        return TransactionAuditLog::query()
            ->with(['actor.signatoryAssignments' => fn ($q) => $q->current()->with('role')])
            ->whereIn('workspace_id', $workspaces->keys())
            ->whereIn('action', self::ACTIONS)
            ->orderBy('id')
            ->get()
            ->unique('workspace_id')
            ->mapWithKeys(fn (TransactionAuditLog $log) => [
                $log->workspace_id => self::describe($workspaces[$log->workspace_id], $log),
            ])
            ->all();
    }

    public static function forOne(StudentDocumentWorkspace $ws): ?array
    {
        return self::for([$ws])[$ws->workspace_id] ?? null;
    }

    /** "Juan Dela Cruz (Dean)", or "VPSD office" when no person is known. */
    public static function label(?array $issuer): ?string
    {
        if (! $issuer) {
            return null;
        }

        return $issuer['role'] !== '' ? "{$issuer['name']} ({$issuer['role']})" : $issuer['name'];
    }

    private static function describe(StudentDocumentWorkspace $ws, TransactionAuditLog $log): array
    {
        $values = $ws->field_values ?? [];

        // Signatory-issued forms carry the issuer's snapshot taken at the time of issuing.
        $name = trim((string) ($values['reporter_name'] ?? ''));
        $role = trim((string) ($values['reporter_role'] ?? ''));

        if ($name === '' && $log->actor) {
            $name = $log->actor->full_name;
        }
        if ($role === '') {
            // Their signatory positions (e.g. "Dean, Adviser"), the same way the issuer snapshot is built.
            $role = $log->actor?->signatoryAssignments->pluck('role.label')->filter()->unique()->implode(', ') ?? '';
        }
        if ($role === '') {
            $role = $log->action === 'transaction.issued_by_office' ? 'VPSD office' : (string) $log->actor_role;
        }

        // No person on record: show the office itself.
        return $name === ''
            ? ['name' => 'VPSD office', 'role' => '']
            : ['name' => $name, 'role' => $role];
    }
}
