<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Reports page setup an admin bookmarked so they can come back to it. */
class SavedReport extends Model
{
    protected $fillable = ['user_id', 'name', 'tab', 'settings', 'last_opened_at'];

    protected $casts = [
        'settings' => 'array',
        'last_opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
