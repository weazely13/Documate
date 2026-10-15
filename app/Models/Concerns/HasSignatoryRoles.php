<?php

namespace App\Models\Concerns;

use App\Models\SignatoryAssignment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Workflow\Documents\DocumentRegistry;

/** Add `use HasSignatoryRoles;` to App\Models\User. */
trait HasSignatoryRoles
{
    /** A dedicated signing account (role "Signatory"), as opposed to a student or admin who also holds a signatory role. */
    public function isSignatoryAccount(): bool
    {
        return ($this->role->role_name ?? null) === 'Signatory';
    }
    public function signatoryAssignments(): HasMany
    {
        return $this->hasMany(SignatoryAssignment::class);
    }
    public function isVpsd(): bool
    {
        return $this->holdsSignatoryRole('vpsd');
    }
    /** @return array<int> ids of the signatory roles this user holds today */
    public function currentSignatoryRoleIds(): array
    {
        return $this->signatoryAssignments()->current()->pluck('signatory_role_id')->all();
    }

    /** Accepts a role id or a role key such as 'guidance_counselor'. */
    public function holdsSignatoryRole(int|string $role): bool
    {
        return $this->signatoryAssignments()
            ->current()
            ->whereHas('role', fn ($q) => is_int($role) ? $q->whereKey($role) : $q->where('key', $role))
            ->exists();
    }

    public function isSignatory(): bool
    {
        return $this->signatoryAssignments()->current()->exists();
    }
    /** Documents this user may issue to a student because of a signatory role they hold. */
    public function issuableDocuments(): array
    {
        return array_filter(
            DocumentRegistry::all(),
            fn ($d) => $d->issuerIsStaff() && $d->canBeIssuedBy($this)
        );
    }
}
