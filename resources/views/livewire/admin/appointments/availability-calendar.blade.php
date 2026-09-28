<div x-data class="mx-auto max-w-7xl space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Office Availability</h2>
        <a href="{{ route('admin.appointments.index') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back to Appointments</a>
    </div>

    {{-- Side-by-side Layout Wrapper --}}
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        
        {{-- Calendar Column (Spans 7 or 8 cols on desktop) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ count($selectedDates) > 0 ? 'lg:col-span-7 xl:col-span-8' : 'lg:col-span-12' }}">
            {{-- Month / Year navigation --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button wire:click="prevMonth" class="rounded-lg border border-slate-200 p-2 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <h3 class="w-40 text-center text-lg font-semibold text-slate-900">{{ $monthLabel }}</h3>
                    <button wire:click="nextMonth" class="rounded-lg border border-slate-200 p-2 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                    </button>
                </div>
                <button wire:click="jumpToday" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Today</button>
            </div>

            {{-- Weekday header --}}
            <div class="mt-5 grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd)
                    <div>{{ $wd }}</div>
                @endforeach
            </div>

            {{-- Day grid --}}
            <div class="mt-2 grid grid-cols-7 gap-2">
                @foreach($days as $day)
                    @php
                        $isSelected = in_array($day['date'], $selectedDates, true);
                    @endphp
                    <button wire:click="toggleDate('{{ $day['date'] }}')"
                        @class([
                            'relative h-16 rounded-xl border p-2 text-left text-sm font-medium transition',
                            'opacity-30' => !$day['inMonth'],
                            'ring-2 ring-[#2A57B4] border-[#2A57B4] bg-blue-50' => $isSelected,
                            'border-rose-200 bg-rose-50 text-rose-600' => $day['closed'] && !$isSelected,
                            'border-amber-200 bg-amber-50 text-amber-600' => $day['partial'] && !$day['closed'] && !$isSelected,
                            'border-slate-200 bg-slate-50/50 text-slate-700 hover:border-slate-300' => $day['isWeekend'] && !$day['closed'] && !$day['partial'] && !$isSelected,
                            'border-slate-200 bg-white text-slate-700 hover:border-[#2A57B4]' => !$day['isWeekend'] && !$day['closed'] && !$day['partial'] && !$isSelected,
                        ])>
                        <span class="flex items-center gap-1">
                            {{ $day['day'] }}
                            @if($day['isToday'])
                                <span class="h-1.5 w-1.5 rounded-full bg-[#2A57B4]"></span>
                            @endif
                        </span>
                        @if($day['hasOverride'])
                            <span class="absolute bottom-1.5 right-1.5 h-1.5 w-1.5 rounded-full {{ $day['closed'] ? 'bg-rose-400' : ($day['partial'] ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span> Fully closed</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Partially open</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Custom hours set</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full border border-[#2A57B4] bg-blue-50"></span> Selected</span>
            </div>
        </div>

        {{-- Side Multi-select Editor Panel --}}
        @if(count($selectedDates) > 0)
            <div class="sticky top-6 rounded-2xl border-2 border-[#2A57B4] bg-blue-50 p-5 lg:col-span-5 xl:col-span-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-[#2A57B4]">
                        Editing {{ count($selectedDates) }} date{{ count($selectedDates) === 1 ? '' : 's' }}
                    </p>
                    <button wire:click="clearSelection" class="text-xs font-medium text-slate-500 hover:text-slate-700">Clear selection</button>
                </div>

                <div class="mt-3 flex max-h-32 flex-wrap gap-1.5 overflow-y-auto">
                    @foreach($selectedDates as $d)
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600 shadow-sm">
                            {{ \Carbon\Carbon::parse($d)->format('M j') }}
                        </span>
                    @endforeach
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl bg-white p-4 shadow-sm">
                        <label class="flex items-center justify-between text-sm font-medium text-slate-700">
                            Morning session open
                            <input type="checkbox" wire:model="morningOpen" class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </label>
                        <input type="number" wire:model="morningSlots" min="0" placeholder="Slots"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4]">
                        @error('morningSlots') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl bg-white p-4 shadow-sm">
                        <label class="flex items-center justify-between text-sm font-medium text-slate-700">
                            Afternoon session open
                            <input type="checkbox" wire:model="afternoonOpen" class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </label>
                        <input type="number" wire:model="afternoonSlots" min="0" placeholder="Slots"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4]">
                        @error('afternoonSlots') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button wire:click="saveSelection"
                    class="mt-4 w-full rounded-xl bg-[#2A57B4] px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#24499A] transition">
                    Apply to {{ count($selectedDates) }} date{{ count($selectedDates) === 1 ? '' : 's' }}
                </button>
            </div>
        @endif

    </div>
</div>