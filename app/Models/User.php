<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\StudentVerification;

#[Fillable([
    'student_number',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'sex',
    'date_of_birth',
    'email',
    'contact_number',
    'college',
    'program',
    'organization',
    'year_level',
    'academic_status',
    'password',
    'role_id',
    'profile_picture',
    'account_status',
    'verification_version',
    ])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $primaryKey = 'id';
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function verifications()
    {
        return $this->hasMany(StudentVerification::class);
    }

    public function latestVerification()
    {
        return $this->hasOne(StudentVerification::class)->latestOfMany();
    }

    public function clearanceStatus()
    {
        return $this->hasOne(ClearanceStatus::class)->latestOfMany();
    }

    public function clearanceStatuses()
    {
        return $this->hasMany(ClearanceStatus::class)
            ->orderByDesc('tagged_at')
            ->orderByDesc('id');
    }

    public function taggedClearanceStatuses()
    {
        return $this->hasMany(ClearanceStatus::class, 'tagged_by');
    }
}
