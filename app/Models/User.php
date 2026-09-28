<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\StudentVerification;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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
    'program_id',
    'organization_id',
    'year_level',
    'section', 
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
    public function verifications(): HasMany
    {
        return $this->hasMany(StudentVerification::class);
    }

    /**
     * null  = no verification period has ever been raised (nothing to show)
     * true  = verified for the most recently raised period
     * false = not verified for it
     */
    public function isVerifiedForCurrentPeriod(): ?bool
    {
        $period = Setting::query()->latest('id')->first();

        if (! $period) {
            return null;
        }

        return $this->verifications()
            ->where('setting_id', $period->id)
            ->where('status', StudentVerification::STATUS_VERIFIED)
            ->exists();
    }

    // =========================================================
    // Organization / Role Manager additions
    // =========================================================

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * The officer record for this user, if they hold an officer post.
     */
    public function officerRecord(): HasOne
    {
        return $this->hasOne(OrganizationOfficer::class);
    }

    public function isOfficer(): bool
    {
        return $this->officerRecord()->exists();
    }

    public function isAdmin(): bool
    {
        return Str::lower((string) ($this->role->role_name ?? '')) === 'admin';
    }
}