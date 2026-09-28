<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRejectionTemplate extends Model
{
    protected $fillable = ['label', 'message'];
}