<?php

namespace App\Support;

use App\Models\TransactionApproval;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;

/** The "Your signing roles" cards on Home: each role a person holds today, what waits on it, and what it signs. */
class SigningRoles
{
    /**
     * @return array{assignments: \Illuminate\Support\Collection, waitingByRole: \Illuminate\Support\Collection, docsByRole: array}
     */
    public static function for(User $user): array
    {
        $assignments = $user->signatoryAssignments()->current()->with('role')->get()
            ->sortBy(fn ($a) => $a->role->label ?? '')->values();

        $waitingByRole = TransactionApproval::assignableTo($user)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', '!=', $user->id))
            ->toBase()->selectRaw('signatory_role_id, COUNT(*) AS c')->groupBy('signatory_role_id')
            ->pluck('c', 'signatory_role_id');

        return [
            'assignments' => $assignments,
            'waitingByRole' => $waitingByRole,
            'docsByRole' => self::documentsByRole($assignments->map(fn ($a) => $a->role->key ?? null)->filter()->unique()->values()->all()),
        ];
    }

    /**
     * Student documents that need a given role somewhere in their signing chain, keyed by role key:
     * [roleKey => [['key' => ..., 'name' => ..., 'as' => step label], ...]].
     */
    public static function documentsByRole(array $roleKeys): array
    {
        $out = array_fill_keys($roleKeys, []);
        foreach (DocumentRegistry::all() as $d) {
            if ($d->retired() || $d->issuerIsStaff()) {
                continue;
            }
            foreach (rescue(fn () => $d->chain([]), [], false) as $step) {
                $key = $step['role'] ?? null;
                if ($key !== null && array_key_exists($key, $out) && ! collect($out[$key])->contains('key', $d->key())) {
                    $out[$key][] = ['key' => $d->key(), 'name' => $d->name(), 'as' => $step['label'] ?? null];
                }
            }
        }

        return array_map(fn ($list) => collect($list)->sortBy('name')->values()->all(), $out);
    }
}
