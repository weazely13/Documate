<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatoryAssignment extends Model
{
    protected $fillable = ['user_id', 'signatory_role_id', 'active_from', 'active_until', 'is_active', 'scope_type', 'scope_id'];

    protected function casts(): array
    {
        return [
            'active_from' => 'date',
            'active_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(SignatoryRole::class, 'signatory_role_id');
    }

    /** Assignments that are switched on and inside their date window today. */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = today();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('active_until')->orWhere('active_until', '>=', $today));
    }
    public function getScopeNameAttribute(): ?string
    {
        return \App\Services\Workflow\SignatoryScope::name($this->scope_type, $this->scope_id);
    }
}
