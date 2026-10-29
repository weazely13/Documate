<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    /** The office is closed every Saturday and Sunday, whatever the availability table says. */
    public static function isWeekend(CarbonInterface|string $date): bool
    {
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $d->isSaturday() || $d->isSunday();
    }

    /** Whether a session takes appointments on a date: never on weekends, otherwise as the office set it. */
    public static function sessionOpen(string $date, string $session): bool
    {
        if (self::isWeekend($date)) {
            return false;
        }

        return (bool) (self::whereDate('date', $date)->first()?->{$session . '_open'} ?? true);
    }

    public static function nowServingFor(string $date, string $session): ?int
    {
        return self::where('date', $date)->first()?->{'now_serving_' . $session};
    }
}
