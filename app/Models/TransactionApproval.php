<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Services\Workflow\Seal;
use App\Services\Workflow\SignatureStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TransactionApproval extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'opened_at' => 'datetime',
            'acted_at' => 'datetime',
            'response' => 'array'
        ];
    }
    public function signingRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SigningRequest::class, 'approval_id');
    }

    protected static function booted(): void
    {
        static::saving(function (TransactionApproval $a) {
            // A sealed (signed) approval can never be edited again.
            if ($a->exists && $a->getRawOriginal('seal') !== null) {
                throw new LogicException('A signed approval is immutable.');
            }

            // The moment an approval becomes APPROVED, freeze everything about it.
            if ($a->status === ApprovalStatus::Approved && $a->seal === null) {
               // Internal signers: the name comes from their account. External signers typed it themselves.
                if ($a->signer_type !== 'external') {
                    $signer = User::find($a->acted_by);
                    $a->signer_name = trim(implode(' ', array_filter([$signer?->first_name, $signer?->last_name])));
                }
                $a->content_hash = Seal::contentHash($a->workspace);

                if ($a->signature_path) {
                    [$a->signature_path, $a->signature_hash] = app(SignatureStore::class)->archive($a->signature_path);
                }

                $a->seal = Seal::sealApproval($a);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentWorkspace::class, 'workspace_id', 'workspace_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(SignatoryRole::class, 'signatory_role_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    /** The signatory inbox: open approvals whose role the user currently holds. */
    public function isAssignedTo(User $user): bool
    {
        return $this->signatory_role_id !== null
            && SignatoryAssignment::current()
                ->where('user_id', $user->id)
                ->where('signatory_role_id', $this->signatory_role_id)
                ->when($this->scope_type, fn ($q) => $q->where('scope_type', $this->scope_type)->where('scope_id', $this->scope_id))
                ->exists();
    }

    /** The signatory inbox: open approvals the user holds the role for, in the right scope. */
    public function scopeAssignableTo(Builder $query, User $user): Builder
    {
        $today = today()->toDateString();

        return $query->where('status', ApprovalStatus::Pending->value)
            ->whereExists(function ($s) use ($user, $today) {
                $s->selectRaw('1')->from('signatory_assignments as sa')
                    ->whereColumn('sa.signatory_role_id', 'transaction_approvals.signatory_role_id')
                    ->where('sa.user_id', $user->id)
                    ->where('sa.is_active', true)
                    ->where(fn ($d) => $d->whereNull('sa.active_from')->orWhere('sa.active_from', '<=', $today))
                    ->where(fn ($d) => $d->whereNull('sa.active_until')->orWhere('sa.active_until', '>=', $today))
                    ->where(fn ($d) => $d->whereNull('transaction_approvals.scope_type')
                        ->orWhere(fn ($m) => $m
                            ->whereColumn('sa.scope_type', 'transaction_approvals.scope_type')
                            ->whereColumn('sa.scope_id', 'transaction_approvals.scope_id')));
            });
    }
}