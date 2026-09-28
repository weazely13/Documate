<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeAvailability extends Model
{
    protected $fillable = [
        'date', 'morning_open', 'afternoon_open', 'morning_slots', 'afternoon_slots',
        'now_serving_morning', 'now_serving_afternoon', 'note',
    ];

    protected $casts = [
        'date' => 'date',
        'morning_open' => 'boolean',
        'afternoon_open' => 'boolean',
    ];

    public static function nowServingFor(string $date, string $session): ?int
    {
        return self::where('date', $date)->first()?->{'now_serving_' . $session};
    }
}