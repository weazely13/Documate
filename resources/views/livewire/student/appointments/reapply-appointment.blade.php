<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Reapply for Appointment</h2>
        <p class="mt-1 text-sm text-slate-500">Your purpose and document stay the same — just pick a new date and session.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
        <p><span class="text-slate-500">Purpose:</span> {{ $appointment->purpose }}</p>
        <p class="mt-1"><span class="text-slate-500">Document:</span> {{ $appointment->workspace?->template?->name ?? 'No document attached' }}</p>
        <p class="mt-1"><span class="text-slate-500">Previously missed:</span> {{ $appointment->appointment_date->format('F j, Y') }} · {{ $appointment->sessionLabel() }}</p>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        {{-- Calendar View --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-7">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button wire:click="prevMonth" class="rounded-lg border border-slate-200 p-2 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <h3 class="w-36 text-center text-base font-semibold text-slate-900">{{ $monthLabel }}</h3>
                    <button wire:click="nextMonth" class="rounded-lg border border-slate-200 p-2 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd)
                    <div>{{ $wd }}</div>
                @endforeach
            </div>

            <div class="mt-2 grid grid-cols-7 gap-1">
                @foreach($this->calendarDays as $day)
                    @php
                        $isSelected = $selectedDate === $day['date'];
                        $isDisabled = !$day['inMonth'] || $day['isPast'] || $day['isSunday'] || !$day['has_available'];
                    @endphp
                    <button 
                        @if(!$isDisabled) wire:click="selectDate('{{ $day['date'] }}')" @endif
                        @disabled($isDisabled)
                        @class([
                            'relative flex h-11 flex-col items-center justify-center rounded-lg text-sm font-medium transition',
                            'opacity-30 cursor-not-allowed' => !$day['inMonth'],
                            'bg-slate-100 text-slate-300 cursor-not-allowed' => ($day['isPast'] || $day['isSunday'] || !$day['has_available']) && $day['inMonth'],
                            'bg-blue-50 border border-[#2A57B4] text-[#2A57B4] font-bold' => $isSelected && !$isDisabled,
                            'bg-white text-slate-700 hover:border-[#2A57B4] hover:bg-blue-50/50 border border-slate-200' => !$isSelected && !$isDisabled,
                        ])>
                        <span>{{ $day['day'] }}</span>
                        @if(!$isDisabled)
                            <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-4 flex justify-between text-xs text-slate-500">
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Available</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Unavailable</span>
            </div>
        </div>

        {{-- Session Selection Panel --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-5">
            <h4 class="font-semibold text-slate-900">
                {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('D, M j, Y') : 'Select a date' }}
            </h4>
            <p class="mt-1 text-xs text-slate-500">Choose your new appointment time slot.</p>

            @if($selectedDate)
                @php
                    $dayData = collect($this->calendarDays)->firstWhere('date', $selectedDate);
                @endphp

                @if($dayData)
                    <div class="mt-4 space-y-3">
                        <button wire:click="selectSlot('{{ $selectedDate }}', 'morning')"
                            @disabled(!$dayData['morning_open'])
                            class="w-full rounded-xl border p-3.5 text-left transition {{ $selectedSession === 'morning' ? 'border-[#2A57B4] bg-blue-50 text-[#2A57B4]' : ($dayData['morning_open'] ? 'border-slate-200 hover:border-[#2A57B4] hover:bg-blue-50' : 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed') }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">Morning Session</span>
                                <span class="text-xs text-slate-500">8:00 AM – 12:00 PM</span>
                            </div>
                            <span class="mt-1 inline-block text-xs font-medium {{ $dayData['morning_open'] ? 'text-emerald-600' : 'text-rose-500' }}">
                                {{ $dayData['morning_open'] ? $dayData['morning_remaining'] . ' slots available' : 'Unavailable' }}
                            </span>
                        </button>

                        <button wire:click="selectSlot('{{ $selectedDate }}', 'afternoon')"
                            @disabled(!$dayData['afternoon_open'])
                            class="w-full rounded-xl border p-3.5 text-left transition {{ $selectedSession === 'afternoon' ? 'border-[#2A57B4] bg-blue-50 text-[#2A57B4]' : ($dayData['afternoon_open'] ? 'border-slate-200 hover:border-[#2A57B4] hover:bg-blue-50' : 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed') }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">Afternoon Session</span>
                                <span class="text-xs text-slate-500">1:00 PM – 5:00 PM</span>
                            </div>
                            <span class="mt-1 inline-block text-xs font-medium {{ $dayData['afternoon_open'] ? 'text-emerald-600' : 'text-rose-500' }}">
                                {{ $dayData['afternoon_open'] ? $dayData['afternoon_remaining'] . ' slots available' : 'Unavailable' }}
                            </span>
                        </button>
                    </div>
                @endif
            @else
                <div class="mt-8 text-center text-sm text-slate-400">
                    Please click an available date on the calendar.
                </div>
            @endif

            @error('selectedDate') <p class="mt-3 text-sm text-rose-500">{{ $message }}</p> @enderror
            @error('selectedSession') <p class="mt-1 text-sm text-rose-500">{{ $message }}</p> @enderror

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.appointments.show', $appointment->appointment_id) }}" wire:navigate
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                <button wire:click="confirmReapply"
                    wire:loading.attr="disabled"
                    wire:target="confirmReapply"
                    :disabled="!$wire.selectedDate || !$wire.selectedSession"
                    class="rounded-xl bg-[#2A57B4] px-5 py-2.5 text-sm font-medium text-white disabled:opacity-40 hover:bg-[#24499A] transition">
                    <span wire:loading.remove wire:target="confirmReapply">Confirm New Date</span>
                    <span wire:loading wire:target="confirmReapply">Submitting...</span>
                </button>
            </div>
        </div>
    </div>
</div>