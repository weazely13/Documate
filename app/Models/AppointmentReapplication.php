<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReapplication extends Model
{
    protected $fillable = ['appointment_id', 'old_date', 'old_session', 'new_date', 'new_session'];

    protected $casts = [
        'old_date' => 'date',
        'new_date' => 'date',
    ];
}