<?php

namespace App\Models;

use App\Models\Concerns\Auditable;

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
use App\Models\Concerns\HasSignatoryRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    'address',
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
    use Auditable;

    protected string $auditModule = 'Users';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;
    use HasSignatoryRoles;

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
            'signature_enabled' => 'boolean'
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $u) {
            if (! $u->isForceDeleting()) {
                $u->signatoryAssignments()->update(['is_active' => false]); // a deleted user can't hold a signing role
            }
        });
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
        return $this->relationLoaded('officerRecord')
            ? $this->officerRecord !== null
            : $this->officerRecord()->exists();
    }

    /**
     * "First M. Last Suffix", the way the forms and PDFs print a name. Use $user->full_name
     * anywhere a person's name is shown so the middle initial is never dropped.
     */
    public function getFullNameAttribute(): string
    {
        return self::formatName($this->first_name, $this->middle_name, $this->last_name, $this->suffix);
    }

    public static function formatName(?string $first, ?string $middle, ?string $last, ?string $suffix = null): string
    {
        $middle = trim((string) $middle);
        $initial = $middle !== '' ? Str::upper(Str::substr($middle, 0, 1)) . '.' : null;

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$first, $initial, $last, $suffix]))));
    }

    /** Public URL of the profile photo, or null when there is none. */
    public function avatarUrl(): ?string
    {
        return self::avatarUrlFor($this->profile_picture);
    }

    public static function avatarUrlFor(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/' . ltrim($path, '/'));
    }

    public function isAdmin(): bool
    {
        return Str::lower((string) ($this->role->role_name ?? '')) === 'admin';
    }
}