<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogbookUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'original_filename',
        'headers',
        'row_count',
        'column_count',
        'uploaded_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }
}