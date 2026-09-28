<?php

namespace App\Livewire\Admin\Appointments;

use App\Models\OfficeAvailability;
use Carbon\Carbon;
use Livewire\Component;

class AvailabilityCalendar extends Component
{
    public string $month;
    public array $selectedDates = [];

    public bool $morningOpen = true;
    public bool $afternoonOpen = true;
    public int $morningSlots = 25;
    public int $afternoonSlots = 25;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->addMonth()->format('Y-m');
    }

    public function jumpToday(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function toggleDate(string $date): void
    {
        if (in_array($date, $this->selectedDates, true)) {
            $this->selectedDates = array_values(array_diff($this->selectedDates, [$date]));
        } else {
            $this->selectedDates[] = $date;
        }

        if (count($this->selectedDates) === 1) {
            $existing = OfficeAvailability::where('date', $this->selectedDates[0])->first();
            $this->morningOpen = $existing?->morning_open ?? true;
            $this->afternoonOpen = $existing?->afternoon_open ?? true;
            $this->morningSlots = $existing?->morning_slots ?? 25;
            $this->afternoonSlots = $existing?->afternoon_slots ?? 25;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedDates = [];
    }

    public function saveSelection(): void
    {
        $this->validate([
            'morningSlots' => 'required|integer|min:0',
            'afternoonSlots' => 'required|integer|min:0',
        ]);

        foreach ($this->selectedDates as $date) {
            OfficeAvailability::updateOrCreate(
                ['date' => $date],
                [
                    'morning_open' => $this->morningOpen,
                    'afternoon_open' => $this->afternoonOpen,
                    'morning_slots' => $this->morningSlots,
                    'afternoon_slots' => $this->afternoonSlots,
                ]
            );
        }

        $this->selectedDates = [];
    }

    public function render()
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $availabilities = OfficeAvailability::whereBetween('date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->get()->keyBy(fn ($a) => $a->date->toDateString());

        $days = [];
        for ($d = $calendarStart->copy(); $d->lte($calendarEnd); $d->addDay()) {
            $iso = $d->toDateString();
            $availability = $availabilities->get($iso);

            $days[] = [
                'date' => $iso,
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isToday' => $d->isToday(),
                // Saturday is treated as a normal working day; only Sunday is marked as weekend off
                'isWeekend' => $d->isSunday(), 
                'closed' => $availability && !$availability->morning_open && !$availability->afternoon_open,
                'partial' => $availability && ($availability->morning_open xor $availability->afternoon_open),
                'hasOverride' => (bool) $availability,
            ];
        }

        return view('livewire.admin.appointments.availability-calendar', [
            'days' => $days,
            'monthLabel' => $start->format('F Y'),
        ])->layout('layouts.app', ['title' => 'Office Availability']);
    }
}