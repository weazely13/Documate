<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceStatus extends Model
{
    use HasFactory;

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
        'semester_id',
        'academic_year',
        'semester',
        'remarks',
        'tagged_at',
    ];

    protected $casts = [
        'tagged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taggedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }

    /**
     * Named semesterPeriod (not semester) — this table already has a
     * plain string column called `semester` (e.g. "First"), so the
     * relation needs a different name to avoid Eloquent attribute/
     * relation collisions.
     */
    public function semesterPeriod(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}