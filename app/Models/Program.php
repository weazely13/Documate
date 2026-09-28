<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'level', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(User::class, 'program_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBachelor($query)
    {
        return $query->where('level', 'bachelor');
    }

    public function scopeMaster($query)
    {
        return $query->where('level', 'master');
    }

    public function getLevelLabelAttribute(): string
    {
        return $this->level === 'master' ? "Master's" : "Bachelor's";
    }
}