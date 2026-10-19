<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime', 'used_at' => 'datetime',
            'declined_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->declined_at === null
            && $this->cancelled_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}