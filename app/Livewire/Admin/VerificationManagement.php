<?php

namespace App\Livewire\Admin;

use App\Models\Semester;
use App\Models\Setting;
use App\Models\StudentVerification;
use App\Models\User;
use App\Notifications\AccountStatusChangedByAdmin;
use App\Notifications\VerificationPeriodOpened;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Livewire\Component;

class VerificationManagement extends Component
{
    protected const MONITORED_ROLES = ['Student', 'Officer'];

    // --- Raise verification form ---
    public string $verification_start_date = '';
    public string $verification_end_date = '';
    public bool $showRaiseModal = false;

    // --- Student list ---
    public string $search = '';
    public string $verificationStatusFilter = '';
    public string $accountStatusFilter = '';
    public int $currentPage = 1;
    public int $perPage = 10;

    // --- History modal ---
    public bool $showHistoryModal = false;
    public ?int $historyUserId = null;

    protected function rules(): array
    {
        $rules = [
            'verification_start_date' => ['required', 'date'],
            'verification_end_date' => ['required', 'date', 'after_or_equal:verification_start_date'],
        ];

        // A verification period belongs to the current semester, so it has to fit inside its dates.
        $semester = Semester::current();
        if ($semester?->isDated()) {
            $rules['verification_start_date'][] = 'after_or_equal:' . $semester->starts_on->toDateString();
            $rules['verification_end_date'][] = 'before_or_equal:' . $semester->ends_on->toDateString();
        }

        return $rules;
    }

    protected function messages(): array
    {
        $semester = Semester::current();
        $inside = $semester?->isDated()
            ? 'The verification period must fall inside ' . $semester->label() . ' (' . $semester->dateLabel() . ').'
            : '';

        return [
            'verification_start_date.after_or_equal' => $inside,
            'verification_end_date.before_or_equal' => $inside,
            'verification_end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function openRaise(): void
    {
        $this->reset(['verification_start_date', 'verification_end_date']);
        $this->resetErrorBag();

        // Start today (or the semester's first day) and give students two weeks, within the semester.
        $semester = Semester::current();
        $start = today()->max($semester?->starts_on ?? today());
        $end = $start->copy()->addDays(13);
        if ($semester?->isDated()) {
            $end = $end->min($semester->ends_on);
        }
        $this->verification_start_date = $start->toDateString();
        $this->verification_end_date = $end->toDateString();

        $this->showRaiseModal = true;
    }

    public function closeRaise(): void
    {
        $this->reset(['verification_start_date', 'verification_end_date', 'showRaiseModal']);
        $this->resetErrorBag();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'verificationStatusFilter', 'accountStatusFilter'], true)) {
            $this->currentPage = 1;
        }
    }

    /**
     * Opens a new verification round. Does NOT touch any account_status —
     * accounts only get disabled by the scheduled deadline-enforcement
     * command once this period's end date has actually passed and a
     * given user still hasn't been verified for it.
     */
    public function raiseVerification(): void
    {
        $this->validate();

        $currentSemester = Semester::current();
        $latest = Setting::query()->latest('id')->first();
        $newVersion = ($latest?->verification_version ?? 0) + 1;

        $period = Setting::create([
            'semester_id' => $currentSemester?->id,
            'current_semester' => $currentSemester?->semester_label,
            'academic_year' => $currentSemester?->school_year,
            'verification_start_date' => $this->verification_start_date,
            'verification_end_date' => $this->verification_end_date,
            'verification_version' => $newVersion,
        ]);

        $recipients = User::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('role_name', self::MONITORED_ROLES))
            ->get();

        NotificationFacade::send($recipients, new VerificationPeriodOpened($period));

        $this->closeRaise();

        session()->flash('message', 'Verification period opened and students notified.');
        $this->dispatch('alert', type: 'success', message: 'Verification period opened. Students have been notified.');
    }

    /**
     * Switch which semester is "current" system-wide. Shared with the
     * clearance monitoring page — this is the same semesters table.
     * The confirm prompt lives client-side (wire:click.prevent + a JS
     * confirm, or a modal) since this affects what officers can tag.
     */
    public function setCurrentSemester(int $semesterId): void
    {
        $semester = Semester::find($semesterId);

        if (! $semester) {
            return;
        }

        $semester->makeCurrent();

        session()->flash('message', 'Current semester updated.');
        $this->dispatch('alert', type: 'success', message: "Current semester set to {$semester->label()}.");
    }

    public function toggleAccountStatus(int $userId): void
    {
        $user = User::with('role')
            ->whereKey($userId)
            ->whereHas('role', fn (Builder $q) => $q->whereIn('role_name', self::MONITORED_ROLES))
            ->first();

        if (! $user) {
            return;
        }

        $user->account_status = $user->account_status === 'active' ? 'inactive' : 'active';
        $user->save();

        $user->notify(new AccountStatusChangedByAdmin($user->account_status));

        session()->flash('message', 'Account status updated.');
        $this->dispatch('alert', type: 'success', message: 'Account status updated for ' . $this->fullName($user) . '.');
    }

    public function viewHistory(int $userId): void
    {
        $this->historyUserId = $userId;
        $this->showHistoryModal = true;
    }

    public function closeHistory(): void
    {
        $this->historyUserId = null;
        $this->showHistoryModal = false;
    }

    protected function fullName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
        ]))));
    }

    /**
     * A user's verification status for the currently open/most recent
     * period: verified / pending / rejected / not_submitted.
     */
    protected function verificationStatusFor(User $user, ?Setting $period): string
    {
        if (! $period) {
            return 'not_submitted';
        }

        $records = $user->verifications->filter(fn ($v) => $this->belongsToPeriod($v, $period));

        // Verified wins over pending, pending over rejected, regardless of order.
        foreach (['verified', 'pending', 'rejected'] as $status) {
            if ($records->contains('status', $status)) {
                return $status;
            }
        }

        return $records->first()?->status ?? 'not_submitted';
    }

    protected function belongsToPeriod($verification, Setting $period): bool
    {
        if ($verification->setting_id) {
            return (int) $verification->setting_id === (int) $period->id;
        }

        // Records saved without a period (e.g. registered before any period existed):
        // match on the semester and academic year instead.
        return $verification->semester === $period->current_semester
            && $verification->academic_year === $period->academic_year;
    }

    protected function buildRecords(): Collection
    {
        $currentPeriod = Setting::query()->latest('id')->first();

        $users = User::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('role_name', self::MONITORED_ROLES))
            ->with(['role', 'verifications' => fn ($q) => $q->latest('id')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $users->map(function (User $user) use ($currentPeriod) {
            return [
                'user_id' => $user->id,
                'name' => $this->fullName($user),
                'student_number' => $user->student_number ?: 'N/A',
                'email' => $user->email,
                'role' => $user->role->role_name ?? 'Student',
                'verification_status' => $this->verificationStatusFor($user, $currentPeriod),
                'account_status' => $user->account_status ?: 'inactive',
                'history_count' => $user->verifications->count(),
            ];
        })->values();
    }

    protected function filteredRecords(): Collection
    {
        return $this->buildRecords()
            ->when($this->search !== '', function (Collection $records) {
                $needle = Str::lower($this->search);

                return $records->filter(fn (array $r) => Str::contains(
                    Str::lower($r['name'] . ' ' . $r['student_number'] . ' ' . $r['email']),
                    $needle
                ));
            })
            ->when($this->verificationStatusFilter !== '', fn (Collection $r) => $r->where('verification_status', $this->verificationStatusFilter))
            ->when($this->accountStatusFilter !== '', fn (Collection $r) => $r->where('account_status', $this->accountStatusFilter))
            ->values();
    }

    public function render()
    {
        $filtered = $this->filteredRecords();
        $totalPages = max(1, (int) ceil($filtered->count() / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filtered->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();

        $historyRecords = $this->historyUserId
            ? StudentVerification::where('user_id', $this->historyUserId)->with('setting')->latest('id')->get()
            : collect();

        return view('livewire.admin.verification-management', [
            'currentPeriod' => Setting::query()->latest('id')->first(),
            'records' => $records,
            'totalPages' => $totalPages,
            'historyRecords' => $historyRecords,
            'historyUser' => $this->historyUserId ? User::find($this->historyUserId) : null,
            'statuses' => StudentVerification::STATUSES,
        ])->layout('layouts.app', ['title' => 'Student Verification']);
    }
}