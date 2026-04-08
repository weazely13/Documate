<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClearanceStatus extends Model
{
    public const STATUS_CLEARED = 'Cleared';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_UNCLEARED = 'Uncleared';

    public const STATUSES = [
        self::STATUS_CLEARED,
        self::STATUS_PENDING,
        self::STATUS_UNCLEARED,
    ];

    protected $fillable = [
        'user_id',
        'tagged_by',
        'organization',
        'status',
        'academic_year',
        'semester',
        'remarks',
        'tagged_at',
    ];

    protected $casts = [
        'tagged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taggedByUser()
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }
}
