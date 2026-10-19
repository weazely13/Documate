<?php

namespace App\Services\Workflow;

use App\Models\Role;
use App\Models\SignatoryAssignment;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\SignatoryInviteNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Staff who only sign get an account created by the admin, then set their own password from an emailed link.
 * The raw token exists only in the email; the database keeps its SHA-256 hash.
 * Everything runs in one transaction: if the email cannot be sent, no account is left behind.
 */
class SignatoryInvitations
{
    public function invite(array $data, User $by): User
    {
        return DB::transaction(function () use ($data, $by) {
            $roleId = Role::where('role_name', 'Signatory')->value('id');
            if (! $roleId) {
                throw new \RuntimeException('The "Signatory" account role does not exist. Run: php artisan db:seed --class=AccountRoleSeeder');
            }

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => Str::lower(trim($data['email'])),
                'password' => Str::random(40), // nobody knows it; they choose their own from the link
                'role_id' => $roleId,
                'account_status' => 'active',
            ]);

            SignatoryAssignment::create([
                'user_id' => $user->id,
                'signatory_role_id' => $data['signatory_role_id'],
                'active_from' => $data['active_from'] ?: null,
                'active_until' => $data['active_until'] ?: null,
                'is_active' => true,
                'scope_type' => $data['scope_type'] ?? null,
                'scope_id' => $data['scope_id'] ?? null,
            ]);

            $this->issue($user, $by);

            return $user;
        });
    }

    /** $how = 'declined' (by the invitee) or 'cancelled' (by the admin). Only unused invitations can be closed. */
    public function close(int $invitationId, string $how): void
    {
        DB::transaction(function () use ($invitationId, $how) {
            $invitation = UserInvitation::with('user')->whereKey($invitationId)->lockForUpdate()->firstOrFail();

            if ($invitation->used_at || $invitation->declined_at || $invitation->cancelled_at) {
                throw new \RuntimeException('closed');
            }

            $invitation->forceFill([$how . '_at' => now()])->save();

            // The account was never set up, so nobody can sign through it, and the
            // roles it holds must not count as "someone can sign".
            $invitation->user?->signatoryAssignments()->update(['is_active' => false]);
            $invitation->user?->forceFill(['account_status' => 'inactive'])->save();
        });
    }

    public function resend(User $user, User $by): void
    {
        DB::transaction(function () use ($user, $by) {
            $wasClosed = UserInvitation::where('user_id', $user->id)->whereNull('used_at')
                ->where(fn ($q) => $q->whereNotNull('declined_at')->orWhereNotNull('cancelled_at'))
                ->exists();

            $this->issue($user, $by);

            if ($wasClosed) {
                $user->signatoryAssignments()->update(['is_active' => true]); // "Invite again"
            }
        });
    }

    private function issue(User $user, User $by): void
    {
        UserInvitation::where('user_id', $user->id)->whereNull('used_at')->delete();

        $raw = Str::random(48);

        UserInvitation::create([
            'user_id' => $user->id,
            'invited_by' => $by->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addDays((int) config('documate.workflow.signatory_invite_days', 7)),
        ]);

        $user->notify(new SignatoryInviteNotification(route('invite.accept', $raw), now()->addDays((int) config('documate.workflow.signatory_invite_days', 7))));
    }

    public static function findValid(string $token): ?UserInvitation
    {
        if (strlen($token) < 40 || strlen($token) > 80) {
            return null;
        }

        $invitation = UserInvitation::with('user')->where('token_hash', hash('sha256', $token))->first();

        return $invitation && $invitation->isUsable() ? $invitation : null;
    }
}