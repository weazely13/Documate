<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use Auditable;

    protected string $auditModule = 'Semesters & Verification';

    use HasFactory;

    protected const MONITORED_ROLES = ['Student', 'Officer'];

    protected $fillable = [
        'school_year',
        'semester_label',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'finalized_at' => 'datetime',
    ];

    /** Order of the terms within a school year. */
    public const TERM_ORDER = ['First' => 1, 'Second' => 2, 'Summer' => 3];

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

    // ------------------------------------------------------------------
    // Dates: a semester runs from starts_on to ends_on, both days included.
    // Semesters made before dates existed have none; they behave as before.
    // ------------------------------------------------------------------

    public function isDated(): bool
    {
        return $this->starts_on !== null && $this->ends_on !== null;
    }

    public function contains(CarbonInterface $day): bool
    {
        return $this->isDated() && $day->copy()->startOfDay()->betweenIncluded($this->starts_on, $this->ends_on);
    }

    public function hasStarted(): bool
    {
        return ! $this->isDated() || today()->gte($this->starts_on);
    }

    public function hasEnded(): bool
    {
        return $this->isDated() && today()->gt($this->ends_on);
    }

    /** Open = today falls inside its dates. A semester without dates counts as open, as it always did. */
    public function isOpen(): bool
    {
        return $this->hasStarted() && ! $this->hasEnded();
    }

    /** "Upcoming", "Open", "Ended" or "Dates needed". */
    public function phase(): string
    {
        return match (true) {
            ! $this->isDated() => 'Dates needed',
            ! $this->hasStarted() => 'Upcoming',
            $this->hasEnded() => 'Ended',
            default => 'Open',
        };
    }

    /** The dated semester whose range contains the day, if any. */
    public static function forDate(CarbonInterface $day): ?self
    {
        $day = $day->toDateString();

        return static::query()
            ->whereNotNull('starts_on')->whereNotNull('ends_on')
            ->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day)
            ->orderByDesc('starts_on')
            ->first();
    }

    /** "Aug 11 – Dec 19, 2026", or null without dates. */
    public function dateLabel(): ?string
    {
        if (! $this->isDated()) {
            return null;
        }

        return $this->starts_on->year === $this->ends_on->year
            ? $this->starts_on->format('M j') . ' – ' . $this->ends_on->format('M j, Y')
            : $this->starts_on->format('M j, Y') . ' – ' . $this->ends_on->format('M j, Y');
    }

    /** What a semester picker adds after the name: " (Current)", " (closed)" or " (opens Jan 11)". */
    public function pickerSuffix(): string
    {
        return match (true) {
            $this->is_current && $this->isOpen() => ' (Current)',
            $this->hasEnded() => ($this->is_current ? ' (Current, ' : ' (') . 'closed)',
            ! $this->hasStarted() => ' (opens ' . $this->starts_on->format('M j') . ')',
            $this->is_current => ' (Current)',
            default => '',
        };
    }

    /** Sorts semesters by school year, then First, Second, Summer. */
    public function sortKey(): string
    {
        return $this->school_year . '-' . (self::TERM_ORDER[$this->semester_label] ?? 9);
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
    public function makeCurrent(bool $onlyCloseEndedSemester = false): ?self
    {
        return DB::transaction(function () use ($onlyCloseEndedSemester) {
            $previous = static::query()
                ->where('is_current', true)
                ->where('id', '!=', $this->id)
                ->first();

            static::query()->where('id', '!=', $this->id)->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();

            // The automatic switch only closes out a semester whose end date has passed; one that
            // is still running is closed later by semesters:sync-current, once it ends.
            if ($previous && ! $previous->finalized_at && (! $onlyCloseEndedSemester || ! $previous->isDated() || $previous->hasEnded())) {
                $previous->finalizeClearance();
            }

            return $previous;
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
            $organization = $user->organization?->name ?: 'Unassigned';

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

        $this->forceFill(['finalized_at' => now()])->save();
    }

    public function auditName(): string
    {
        return trim("{$this->school_year} {$this->semester_label}") ?: 'Semester #' . $this->getKey();
    }
}
