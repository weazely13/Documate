<?php

namespace App\Livewire\Signatory;

use App\Models\CounselorAvailability;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** The Guidance Counselor sets the dates and times students can see them. */
class Availability extends Component
{
    public string $date = '';
    public string $start = '09:00';
    public string $end = '11:00';

    public function mount(): void
    {
        abort_unless(Auth::user()->holdsSignatoryRole('guidance_counselor'), 403);
    }

    public function add(): void
    {
        $this->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
        ]);

        $overlaps = CounselorAvailability::where('user_id', Auth::id())
            ->whereDate('available_date', $this->date)
            ->where('start_time', '<', $this->end . ':00')
            ->where('end_time', '>', $this->start . ':00')
            ->exists();

        if ($overlaps) {
            $this->addError('start', 'This overlaps a slot you already set for that day.');
            return;
        }

        CounselorAvailability::create([
            'user_id' => Auth::id(),
            'available_date' => $this->date,
            'start_time' => $this->start,
            'end_time' => $this->end,
        ]);

        $this->reset('date');
        session()->flash('avail_message', 'Availability added.');
    }

    public function remove(int $id): void
    {
        CounselorAvailability::where('user_id', Auth::id())->whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.signatory.availability', [
            'slots' => CounselorAvailability::where('user_id', Auth::id())
                ->whereDate('available_date', '>=', today())
                ->orderBy('available_date')->orderBy('start_time')->get(),
        ])->layout('layouts.app', ['title' => 'My Availability']);
    }
}