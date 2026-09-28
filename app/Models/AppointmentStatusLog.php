<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentStatusLog extends Model
{
    protected $fillable = ['appointment_id', 'status', 'note', 'changed_by'];

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}