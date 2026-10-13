<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function officers()
    {
        return $this->hasMany(OrganizationOfficer::class);
    }

    /**
     * All students/officers currently set to this organization on their profile.
     */
    public function members()
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function programs()
    {
        return $this->belongsToMany(Program::class)->withTimestamps();
    }

    public function scopeForProgram($query, $programId)
    {
        return $query->whereHas('programs', fn ($p) => $p->where('programs.id', $programId));
    }
}