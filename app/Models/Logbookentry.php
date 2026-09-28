<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'logbook_upload_id',
        'row_number',
        'entry_date',
        'entry_time',
        'applicant_name',
        'program',
        'address',
        'issued_to',
        'relation',
        'purpose',
        'released_at',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(LogbookUpload::class, 'logbook_upload_id');
    }
}