<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'current_semester',   // legacy string column — no longer written to, kept for backward compat
        'academic_year',      // legacy string column — no longer written to, kept for backward compat
        'verification_start_date',
        'verification_end_date',
        'verification_version',
    ];

    protected $casts = [
        'verification_start_date' => 'date',
        'verification_end_date' => 'date',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(StudentVerification::class);
    }

    public function isOpen(): bool
    {
        $today = now()->startOfDay();

        return $today->betweenIncluded(
            $this->verification_start_date->startOfDay(),
            $this->verification_end_date->startOfDay()
        );
    }

    public function isExpired(): bool
    {
        return now()->startOfDay()->gt($this->verification_end_date->startOfDay());
    }

    public function label(): string
    {
        return $this->verification_start_date->format('M j, Y') . ' – ' . $this->verification_end_date->format('M j, Y');
    }
}