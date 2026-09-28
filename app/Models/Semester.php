<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory;

    protected const MONITORED_ROLES = ['Student', 'Officer'];

    protected $fillable = [
        'school_year',
        'semester_label',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function clearanceStatuses(): HasMany
    {
        return $this->hasMany(ClearanceStatus::class);
    }

    /**
     * The single semester currently open for tagging, or null if the
     * admin hasn't set one yet (e.g. brand-new install).
     */
    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->first();
    }

    public function label(): string
    {
        return $this->school_year . ' | ' . $this->semester_label . ' Semester';
    }

    /**
     * Flip this semester to "current" and every other semester to not-current,
     * atomically. This is the ONLY place is_current should ever be written.
     *
     * Whichever semester WAS current before this call gets its clearance
     * finalized as part of the same transaction — see finalizeClearance().
     * The confirm dialog on the "Set as Current" button should tell the
     * admin this happens, since it's not reversible in a meaningful way
     * (the auto-created Uncleared records can be manually re-tagged
     * afterward, but the semester is being closed out either way).
     */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            $previous = static::query()
                ->where('is_current', true)
                ->where('id', '!=', $this->id)
                ->first();

            static::query()->where('id', '!=', $this->id)->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();

            if ($previous) {
                $previous->finalizeClearance();
            }
        });
    }

    /**
     * End-of-semester clearance finalization.
     *
     * Every monitored student/officer who has NO clearance_statuses row
     * at all for this semester (i.e. nobody ever tagged them, in either
     * direction) gets one auto-created now, status Uncleared, so that
     * every student ends up with a definitive clearance record for
     * every semester that has actually ended — not just the ones an
     * officer happened to get around to tagging.
     *
     * Students who already have ANY record for this semester (Cleared,
     * Pending, or Uncleared) are left alone — that's their real,
     * intentionally-tagged history and this method doesn't touch it.
     */
    public function finalizeClearance(): void
    {
        $taggedUserIds = ClearanceStatus::query()
            ->where('semester_id', $this->id)
            ->pluck('user_id')
            ->unique();

        $untaggedUsers = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', self::MONITORED_ROLES))
            ->whereNotIn('id', $taggedUserIds)
            ->get();

        foreach ($untaggedUsers as $user) {
            $organization = $user->organization ?: 'Unassigned';

            $record = $user->clearanceStatuses()->create([
                'tagged_by' => null,
                'organization' => $organization,
                'status' => ClearanceStatus::STATUS_UNCLEARED,
                'semester_id' => $this->id,
                'academic_year' => $this->school_year,
                'semester' => $this->semester_label,
                'remarks' => 'Automatically marked Uncleared — no clearance activity was recorded before the semester ended.',
                'tagged_at' => now(),
            ]);

            $user->notify(new \App\Notifications\ClearanceStatusUpdated($record->status, $organization, $this));
        }
    }
}