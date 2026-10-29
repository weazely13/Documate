<?php

namespace App\Livewire\Admin;

use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SemesterManagement extends Component
{
    // --- Create form ---
    public string $school_year = '';
    public string $semester_label = '';
    public string $starts_on = '';
    public string $ends_on = '';

    // --- Editing a semester's dates ---
    public ?int $editingId = null;
    public string $editStartsOn = '';
    public string $editEndsOn = '';

    // --- Delete confirmation ---
    public ?int $pendingDeleteId = null;
    public int $pendingDeleteClearanceCount = 0;
    public int $pendingDeleteSettingCount = 0;
    public bool $confirmDeleteChecked = false;

    protected function rules(): array
    {
        return [
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester_label' => 'required|in:First,Second,Summer',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
        ];
    }

    protected function messages(): array
    {
        return [
            'school_year.regex' => 'School year must be in the format 2025-2026.',
            'ends_on.after' => 'The end date must be after the start date.',
            'editEndsOn.after' => 'The end date must be after the start date.',
        ];
    }

    public function createSemester(): void
    {
        $this->validate();

        $exists = Semester::where('school_year', $this->school_year)
            ->where('semester_label', $this->semester_label)
            ->exists();

        if ($exists) {
            $this->addError('semester_label', 'This semester and school year combination already exists.');

            return;
        }

        if ($problem = $this->datesProblem($this->school_year, $this->starts_on, $this->ends_on)) {
            $this->addError($problem[0] === 'start' ? 'starts_on' : 'ends_on', $problem[1]);

            return;
        }

        $semester = Semester::create([
            'school_year' => $this->school_year,
            'semester_label' => $this->semester_label,
            'starts_on' => $this->starts_on,
            'ends_on' => $this->ends_on,
            'is_current' => false,
        ]);

        $this->reset(['school_year', 'semester_label', 'starts_on', 'ends_on']);
        $this->syncCurrent();

        session()->flash('message', 'Semester created: ' . $semester->label());
        $this->dispatch('alert', type: 'success', message: 'Semester created: ' . $semester->label());
    }

    public function editDates(int $semesterId): void
    {
        $semester = Semester::find($semesterId);
        if (! $semester) {
            return;
        }

        $this->resetErrorBag();
        $this->editingId = $semester->id;
        $this->editStartsOn = $semester->starts_on?->toDateString() ?? '';
        $this->editEndsOn = $semester->ends_on?->toDateString() ?? '';
    }

    public function cancelEditDates(): void
    {
        $this->reset(['editingId', 'editStartsOn', 'editEndsOn']);
        $this->resetErrorBag();
    }

    public function saveDates(): void
    {
        $semester = Semester::find($this->editingId);
        if (! $semester) {
            $this->cancelEditDates();

            return;
        }

        $this->validate([
            'editStartsOn' => 'required|date',
            'editEndsOn' => 'required|date|after:editStartsOn',
        ], [], ['editStartsOn' => 'start date', 'editEndsOn' => 'end date']);

        if ($problem = $this->datesProblem($semester->school_year, $this->editStartsOn, $this->editEndsOn, $semester->id)) {
            $this->addError($problem[0] === 'start' ? 'editStartsOn' : 'editEndsOn', $problem[1]);

            return;
        }

        $semester->update(['starts_on' => $this->editStartsOn, 'ends_on' => $this->editEndsOn]);
        $label = $semester->label();
        $this->cancelEditDates();
        $this->syncCurrent();

        $this->dispatch('alert', type: 'success', message: "Dates saved for {$label}.");
    }

    /**
     * Why a date range can't be used, as [field, message], or null when it's fine:
     * it must sit within the school year's two calendar years and not overlap another semester.
     */
    protected function datesProblem(string $schoolYear, string $start, string $end, ?int $ignoreId = null): ?array
    {
        [$from, $to] = [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->startOfDay()];
        $years = array_map('intval', explode('-', $schoolYear));

        if (count($years) === 2 && ($from->year < $years[0] || $to->year > $years[1])) {
            return ['start', "The dates must fall within school year {$schoolYear} ({$years[0]} to {$years[1]})."];
        }

        $clash = Semester::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->whereNotNull('starts_on')->whereNotNull('ends_on')
            ->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from)
            ->first();

        return $clash
            ? ['start', 'These dates overlap ' . $clash->label() . ' (' . $clash->dateLabel() . '). Semesters cannot share days.']
            : null;
    }

    /** Apply the date rule right away instead of waiting for tonight's scheduled run. */
    protected function syncCurrent(): void
    {
        Artisan::call('semesters:sync-current');
    }

    public function setCurrent(int $semesterId): void
    {
        $semester = Semester::find($semesterId);

        if (! $semester) {
            return;
        }

        $semester->makeCurrent();

        session()->flash('message', 'Current semester updated.');
        $this->dispatch('alert', type: 'success', message: "Current semester set to {$semester->label()}.");
    }

    /**
     * Step 1: load impact counts and show the confirmation panel.
     * The actual delete only happens via deleteSemester() below, after
     * the admin explicitly checks the "I understand" box — deleting a
     * semester is destructive to reporting even though the FKs are
     * nullOnDelete (history rows survive, but lose their semester link).
     */
    public function confirmDelete(int $semesterId): void
    {
        $this->pendingDeleteId = $semesterId;
        $this->confirmDeleteChecked = false;

        $this->pendingDeleteClearanceCount = ClearanceStatus::where('semester_id', $semesterId)->count();
        $this->pendingDeleteSettingCount = Setting::where('semester_id', $semesterId)->count();
    }

    public function cancelDelete(): void
    {
        $this->reset(['pendingDeleteId', 'pendingDeleteClearanceCount', 'pendingDeleteSettingCount', 'confirmDeleteChecked']);
    }

    public function deleteSemester(): void
    {
        if (! $this->pendingDeleteId || ! $this->confirmDeleteChecked) {
            return;
        }

        $semester = Semester::find($this->pendingDeleteId);

        if (! $semester) {
            $this->cancelDelete();

            return;
        }

        $wasCurrent = $semester->is_current;
        $label = $semester->label();

        DB::transaction(function () use ($semester) {
            // FKs on clearance_statuses.semester_id and settings.semester_id
            // are nullOnDelete, so history rows survive this and simply
            // lose their semester link — they still carry the human-readable
            // academic_year/semester string columns for context.
            $semester->delete();
        });

        $this->cancelDelete();

        session()->flash('message', "Deleted semester: {$label}.");
        $this->dispatch('alert', type: 'success', message: "Deleted {$label}." . ($wasCurrent ? ' Note: this was the current semester — set a new one.' : ''));
    }

    public function render()
    {
        return view('livewire.admin.semester-management', [
            'semesters' => Semester::all()->sortByDesc(fn (Semester $s) => $s->sortKey())->values(),
            'today' => Semester::forDate(today()),
        ])->layout('layouts.app', ['title' => 'Semesters & School Years']);
    }
}