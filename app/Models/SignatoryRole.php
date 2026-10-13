<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignatoryRole extends Model
{
    protected $fillable = ['key', 'label', 'description', 'is_active', 'scope'];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SignatoryAssignment::class);
    }

    /** Users who hold this role right now. */
    public function holders(): Builder
    {
        return User::query()->whereHas(
            'signatoryAssignments',
            fn (Builder $q) => $q->current()->where('signatory_role_id', $this->id)
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    /** Holders of this role for one scope (e.g. the adviser of organization 7). Unscoped roles ignore the arguments. */
    public function holdersFor(?string $scopeType = null, ?int $scopeId = null): Builder
    {
        return User::query()->whereHas(
            'signatoryAssignments',
            fn (Builder $q) => $q->current()
                ->where('signatory_role_id', $this->id)
                ->when($scopeType, fn (Builder $x) => $x->where('scope_type', $scopeType)->where('scope_id', $scopeId))
        );
    }
}
