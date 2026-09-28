<?php

namespace App\Livewire\Admin;

use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SemesterManagement extends Component
{
    // --- Create form ---
    public string $school_year = '';
    public string $semester_label = '';

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
        ];
    }

    protected function messages(): array
    {
        return [
            'school_year.regex' => 'School year must be in the format 2025-2026.',
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

        $semester = Semester::create([
            'school_year' => $this->school_year,
            'semester_label' => $this->semester_label,
            'is_current' => false,
        ]);

        $this->reset(['school_year', 'semester_label']);

        session()->flash('message', 'Semester created: ' . $semester->label());
        $this->dispatch('alert', type: 'success', message: 'Semester created: ' . $semester->label());
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
            'semesters' => Semester::orderByDesc('school_year')->orderBy('semester_label')->get(),
        ])->layout('layouts.app', ['title' => 'Semesters & School Years']);
    }
}