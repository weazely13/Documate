<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselorAvailability extends Model
{
    protected $fillable = ['user_id', 'available_date', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return ['available_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Earliest slot (not yet over) set by anyone who currently holds the Guidance Counselor role. */
    public static function nextSlot(): ?self
    {
        $role = SignatoryRole::where('key', 'guidance_counselor')->first();
        if (! $role) {
            return null;
        }

        return static::query()
            ->whereIn('user_id', $role->holders()->pluck('users.id'))
            ->where(fn ($q) => $q
                ->where('available_date', '>', today())
                ->orWhere(fn ($x) => $x->where('available_date', today())->where('end_time', '>', now()->format('H:i:s'))))
            ->orderBy('available_date')->orderBy('start_time')
            ->first();
    }
}