<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReschedule extends Model
{
    protected $fillable = [
        'appointment_id', 'old_date', 'old_session', 'new_date', 'new_session', 'reason', 'rescheduled_by',
    ];

    protected $casts = [
        'old_date' => 'date',
        'new_date' => 'date',
    ];

    public function rescheduledBy()
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }
}