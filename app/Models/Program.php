<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'college_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(User::class, 'program_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}