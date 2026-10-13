<div x-data="{ step: @entangle('step') }" class="mx-auto max-w-4xl space-y-6">
    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Book an Appointment</h2>
        <p class="mt-1 text-sm text-slate-500">Step <span x-text="step"></span> of 4</p>
    </div>

    <div class="flex items-center gap-1.5">
        @for($i = 1; $i <= 4; $i++)
            <span class="h-1.5 flex-1 rounded-full {{ $step >= $i ? 'bg-[#2A57B4]' : 'bg-slate-200' }}"></span>
        @endfor
    </div>

    {{-- Step 1: Purpose --}}
    @if($step === 1)
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-semibold text-slate-900">What's the purpose of your visit?</h3>

            @if($this->selectedWorkspace)
                <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-[#2A57B4]">
                    Linked to: {{ $this->selectedWorkspace->template?->name }}
                </div>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($purposeOptions as $option)
                    <button type="button" wire:click="selectPurposeOption('{{ $option }}')"
                        class="rounded-xl border p-3.5 text-left text-sm font-medium transition {{ $selectedPurposeOption === $option ? 'border-[#2A57B4] bg-blue-50 text-[#2A57B4]' : 'border-slate-200 text-slate-700 hover:border-[#2A57B4] hover:bg-blue-50/40' }}">
                        {{ $option }}
                    </button>
                @endforeach

                <button type="button" wire:click="selectPurposeOption('Others')"
                    class="rounded-xl border p-3.5 text-left text-sm font-medium transition {{ $selectedPurposeOption === 'Others' ? 'border-[#2A57B4] bg-blue-50 text-[#2A57B4]' : 'border-slate-200 text-slate-700 hover:border-[#2A57B4] hover:bg-blue-50/40' }}">
                    Others
                </button>
            </div>
            @error('selectedPurposeOption') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror

            @if($selectedPurposeOption === 'Others')
                <textarea wire:model="customPurpose" rows="3" placeholder="Please specify the purpose of your visit"
                    class="mt-3 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100"></textarea>
                @error('customPurpose') <p class="mt-1 text-sm text-rose-500">{{ $message }}</p> @enderror
            @endif

            <button wire:click="continueFromPurpose" :disabled="!$wire.selectedPurposeOption || ($wire.selectedPurposeOption === 'Others' && !$wire.customPurpose)"
                class="mt-4 rounded-xl bg-[#2A57B4] px-5 py-2.5 text-sm font-medium text-white disabled:opacity-40 hover:bg-[#24499A] transition">
                Continue
            </button>
        </div>
    @endif

    {{-- Step 2: Select pending document --}}
    @if($step === 2)
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-semibold text-slate-900">Select a document (optional)</h3>
            <p class="mt-1 text-sm text-slate-500">You can proceed without attaching a document if this visit isn't about a specific transaction.</p>
            <div class="mt-4 space-y-2">
                @forelse($this->pendingDocuments as $doc)
                    <button wire:click="selectDocument({{ $doc->workspace_id }})"
                        class="flex w-full items-center justify-between rounded-xl border border-slate-200 p-4 text-left transition hover:border-[#2A57B4] hover:bg-blue-50/40">
                        <div>
                            <p class="font-medium text-slate-900">{{ $doc->template?->name }}</p>
                            <p class="text-xs text-slate-400">Saved {{ $doc->updated_at->diffForHumans() }}</p>
                        </div>
                    </button>
                @empty
                    <p class="text-sm text-slate-400">No pending documents available.</p>
                @endforelse
            </div>
            <div class="mt-4 flex justify-between items-center">
                <button wire:click="goToStep(1)" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back</button>
                <button wire:click="skipDocument" class="text-sm font-medium text-[#2A57B4] hover:underline">Continue without a document →</button>
            </div>
        </div>
    @endif

    {{-- Step 3: Pick date/session --}}
    @if($step === 3)
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- Month Calendar View --}}
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

            {{-- Available Time Sessions Panel --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-5">
                <h4 class="font-semibold text-slate-900">
                    {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('D, M j, Y') : 'Select a date' }}
                </h4>
                <p class="mt-1 text-xs text-slate-500">Select an available session slot to proceed.</p>

                @if($selectedDate)
                    @php
                        $dayData = collect($this->calendarDays)->firstWhere('date', $selectedDate);
                    @endphp

                    @if($dayData)
                        <div class="mt-4 space-y-3">
                            {{-- Morning --}}
                            <button wire:click="selectSlot('{{ $selectedDate }}', 'morning')"
                                @disabled(!$dayData['morning_open'])
                                class="w-full rounded-xl border p-3.5 text-left transition {{ $dayData['morning_open'] ? 'border-slate-200 hover:border-[#2A57B4] hover:bg-blue-50' : 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-800">Morning Session</span>
                                    <span class="text-xs text-slate-500">8:00 AM – 12:00 PM</span>
                                </div>
                                <span class="mt-1 inline-block text-xs font-medium {{ $dayData['morning_open'] ? 'text-emerald-600' : 'text-rose-500' }}">
                                    {{ $dayData['morning_open'] ? $dayData['morning_remaining'] . ' slots available' : 'Unavailable' }}
                                </span>
                            </button>

                            {{-- Afternoon --}}
                            <button wire:click="selectSlot('{{ $selectedDate }}', 'afternoon')"
                                @disabled(!$dayData['afternoon_open'])
                                class="w-full rounded-xl border p-3.5 text-left transition {{ $dayData['afternoon_open'] ? 'border-slate-200 hover:border-[#2A57B4] hover:bg-blue-50' : 'border-slate-100 bg-slate-50 opacity-60 cursor-not-allowed' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-800">Afternoon Session</span>
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
                        Please click a green date on the calendar.
                    </div>
                @endif

                <div class="mt-6 pt-4 border-t border-slate-100">
                    <button wire:click="goToStep({{ $workspaceId ? 1 : 2 }})" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Step 4: Confirm --}}
    @if($step === 4)
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-semibold text-slate-900">Confirm your appointment</h3>
            <div class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                @if($this->selectedWorkspace)
                    <p><span class="text-slate-500">Document:</span> {{ $this->selectedWorkspace->template?->name }}</p>
                @endif
                <p><span class="text-slate-500">Purpose:</span> {{ $purpose }}</p>
                <p><span class="text-slate-500">Date:</span> {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</p>
                <p><span class="text-slate-500">Session:</span> {{ ucfirst($selectedSession) }} ({{ $selectedSession === 'morning' ? '8:00 AM – 12:00 PM' : '1:00 PM – 5:00 PM' }})</p>
            </div>
            @error('selectedSession') <p class="mt-2 text-sm text-rose-500">{{ $message }}</p> @enderror

            <div class="mt-5 flex gap-3">
                <button wire:click="goToStep(3)" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</button>
                <button wire:click="confirmAppointment"
                    wire:loading.attr="disabled"
                    wire:target="confirmAppointment"
                    class="rounded-xl bg-[#2A57B4] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#24499A] transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="confirmAppointment">Confirm Appointment</span>
                    <span wire:loading wire:target="confirmAppointment">Submitting...</span>
                </button>
            </div>
        </div>
    @endif
</div>