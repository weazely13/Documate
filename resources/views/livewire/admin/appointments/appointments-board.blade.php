<div class="mx-auto max-w-7xl space-y-6" x-data="{ columnPage: 1 }">

    <!-- STICKY TOP OVERLAY ACTION BAR -->
    @if(count($selectedIds) > 0)
        <div class="sticky top-4 z-40 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#2A57B4]/30 bg-white/90 p-4 shadow-xl backdrop-blur-md transition-all">
            <div class="flex items-center gap-3">
                <span class="flex h-3 w-3 rounded-full bg-[#2A57B4] animate-ping"></span>
                <p class="text-sm font-bold text-[#2A57B4]">
                    {{ count($selectedIds) }} selected
                    @if($this->selectedPendingCount < count($selectedIds))
                        <span class="font-normal text-slate-500">({{ $this->selectedPendingCount }} pending)</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @unless($this->selectionHasApproved)
                    <button @click="$wire.showBulkApprove = true"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        Approve
                    </button>
                    <button @click="$wire.showBulkReject = true"
                        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-600 shadow-sm transition hover:bg-rose-100">
                        Reject
                    </button>
                @endunless
                <button @click="$wire.showBulkReschedule = true"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Reschedule
                </button>
                <button wire:click="bulkRerunReview" wire:loading.attr="disabled"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50">
                    Re-run AI review
                </button>
                <button wire:click="clearSelection" class="ml-2 text-xs font-semibold text-slate-400 hover:text-slate-600">
                    Cancel
                </button>
            </div>
        </div>
    @endif

    <!-- Top Bar: Header & View Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Appointments Board</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage daily schedule queue and review full appointment histories.</p>
        </div>

        <!-- Tab Navigation -->
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
            <button wire:click="setViewMode('board')"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition {{ $viewMode === 'board' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Board
            </button>
            <button wire:click="setViewMode('history')"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition {{ $viewMode === 'history' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                All History
            </button>
        </div>
    </div>

    @if($viewMode === 'board')
        {{--
            LIST-FIRST LAYOUT:
            Main column (appointments, order-1) is wide and comes first in the
            DOM so it's the first thing rendered/read on every breakpoint.
            The calendar + stats + queue board live in a slim sticky sidebar
            (order-2) — visible without scrolling on desktop, but never
            pushes the list down the page.
        --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px] items-start">

            <!-- ============ MAIN: APPOINTMENTS LIST (FIRST) ============ -->
            <div class="order-1 space-y-4 min-w-0">

                <!-- Date context bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Viewing</p>
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ \Carbon\Carbon::parse($selectedDate)->isToday() ? 'Today' : \Carbon\Carbon::parse($selectedDate)->format('l') }}
                            · {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button wire:click="selectDate('{{ \Carbon\Carbon::parse($selectedDate)->subDay()->toDateString() }}')"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-[#2A57B4] hover:text-[#2A57B4]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        @unless(\Carbon\Carbon::parse($selectedDate)->isToday())
                            <button wire:click="jumpToday" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">
                                Today
                            </button>
                        @endunless
                        <button wire:click="selectDate('{{ \Carbon\Carbon::parse($selectedDate)->addDay()->toDateString() }}')"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-[#2A57B4]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Column tabs -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <button x-show="columnPage === 1" wire:click="selectAllActionable"
                        class="text-xs font-semibold text-[#2A57B4] hover:underline">
                        Select all
                    </button>

                    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 ml-auto">
                        <button @click="columnPage = 1"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="columnPage === 1 ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Pending
                            <span class="rounded-full px-1.5 text-[10px] font-bold"
                                :class="columnPage === 1 ? 'bg-blue-50 text-[#2A57B4]' : 'bg-slate-200 text-slate-500'">
                                {{ $this->groupedAppointments['reject']->sum(fn($g) => $g['items']->count()) + ($this->groupedAppointments['approve'] ?? collect())->count() }}
                            </span>
                        </button>
                        <button @click="columnPage = 2"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="columnPage === 2 ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Processed
                            <span class="rounded-full px-1.5 text-[10px] font-bold"
                                :class="columnPage === 2 ? 'bg-blue-50 text-[#2A57B4]' : 'bg-slate-200 text-slate-500'">
                                {{ ($this->groupedAppointments['approved'] ?? collect())->count() + ($this->groupedAppointments['rejected'] ?? collect())->count() }}
                            </span>
                        </button>
                        <button @click="columnPage = 3"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="columnPage === 3 ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Missed
                            <span class="rounded-full px-1.5 text-[10px] font-bold"
                                :class="columnPage === 3 ? 'bg-blue-50 text-[#2A57B4]' : 'bg-slate-200 text-slate-500'">
                                {{ $this->missedAppointments->count() }}
                            </span>
                        </button>
                    </div>
                </div>

                {{-- CAPACITY / FCFS WARNING --}}
                @if($capacityWarning)
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
                        <div class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-amber-800">This doesn't fit today's remaining slots or queue order</p>
                                <ul class="mt-1.5 space-y-1 text-xs font-medium text-amber-700">
                                    @foreach ($capacityWarning as $session => $info)
                                        <li>
                                            {{ ucfirst($session) }}:
                                            @if(!$info['open'])
                                                session is closed for this day, but {{ $info['needed'] }} selected.
                                            @elseif(!empty($info['fcfs_violation']))
                                                there are earlier pending requests for this session not in your selection — approving out of order breaks first-come-first-served.
                                            @else
                                                {{ $info['needed'] }} selected, only {{ $info['remaining'] }} slot(s) remaining.
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="mt-3 flex gap-2">
                                    <button wire:click="bulkApprove(true)"
                                        class="rounded-lg bg-amber-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700">
                                        Approve anyway
                                    </button>
                                    <button wire:click="dismissCapacityWarning"
                                        class="rounded-lg border border-amber-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100/50">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- PAGE 1: FOR REJECTION & FOR APPROVAL -->
                <div x-show="columnPage === 1" class="grid gap-4 md:grid-cols-2">
                    <!-- FOR REJECTION -->
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-3">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">For Rejection</h4>
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-semibold text-slate-500">
                                    {{ $this->groupedAppointments['reject']->sum(fn($g) => $g['items']->count()) }}
                                </span>
                            </div>
                            <button wire:click="selectAllInColumn('reject')" class="text-[10px] font-semibold text-[#2A57B4] hover:underline">Select all</button>
                        </div>

                        <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-0.5">
                            @forelse($this->groupedAppointments['reject'] as $group)
                                <div>
                                    <p class="mb-1.5 px-1 text-[10px] font-bold uppercase tracking-wide text-rose-500/80">
                                        {{ $group['label'] }} · {{ $group['items']->count() }}
                                    </p>
                                    <div class="space-y-2">
                                        @foreach($group['items'] as $appt)
                                            @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt])
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="px-1 py-8 text-center text-xs text-slate-400">No appointments flagged for rejection.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- READY FOR APPROVAL -->
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-3">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Ready for Approval</h4>
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-semibold text-slate-500">
                                    {{ ($this->groupedAppointments['approve'] ?? collect())->count() }}
                                </span>
                            </div>
                            <button wire:click="selectAllInColumn('approve')" class="text-[10px] font-semibold text-[#2A57B4] hover:underline">Select all</button>
                        </div>

                        <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-0.5">
                            @forelse($this->groupedAppointments['approve'] ?? collect() as $appt)
                                @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt])
                            @empty
                                <p class="px-1 py-8 text-center text-xs text-slate-400">No appointments ready for approval.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- PAGE 2: APPROVED TODAY & REJECTED TODAY -->
                <div x-show="columnPage === 2" x-cloak class="grid gap-4 md:grid-cols-2">
                    <!-- APPROVED TODAY -->
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-3">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-[#2A57B4]"></span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Approved Today</h4>
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-semibold text-slate-500">
                                    {{ ($this->groupedAppointments['approved'] ?? collect())->count() }}
                                </span>
                            </div>
                            <button wire:click="emergencyRescheduleDay"
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-100">
                                <i class='bx bx-error-circle text-sm'></i>
                                Emergency: Reschedule Day
                            </button>
                        </div>

                        <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-0.5">
                            @forelse($this->groupedAppointments['approved'] ?? collect() as $appt)
                                @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt])
                            @empty
                                <p class="px-1 py-8 text-center text-xs text-slate-400">No approved appointments for this date.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- REJECTED TODAY -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-3">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Rejected Today</h4>
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-semibold text-slate-500">
                                    {{ ($this->groupedAppointments['rejected'] ?? collect())->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-0.5">
                            @forelse($this->groupedAppointments['rejected'] ?? collect() as $appt)
                                @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt])
                            @empty
                                <p class="px-1 py-8 text-center text-xs text-slate-400">No rejected appointments for this date.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- PAGE 3: MISSED APPOINTMENTS FOR SELECTED DATE -->
                <div x-show="columnPage === 3" x-cloak>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-3">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Missed Appointments</h4>
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-semibold text-slate-500">
                                    {{ $this->missedAppointments->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-[36rem] overflow-y-auto pr-0.5">
                            @forelse($this->missedAppointments as $appt)
                                @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt, 'selectable' => false])
                            @empty
                                <p class="px-1 py-8 text-center text-xs text-slate-400">No missed appointments for this date.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ SIDEBAR: CALENDAR + STATS + QUEUE (SECOND) ============ -->
            <div class="order-2 space-y-4 xl:sticky xl:top-6">

                {{-- COMPACT CALENDAR --}}
                <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <button @click="open = !open" class="flex w-full items-center justify-between">
                        <span class="text-sm font-semibold text-slate-900">{{ $monthLabel }}</span>
                        <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="mt-3">
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('admin.appointments.availability') }}" wire:navigate
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    <path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18" />
                                </svg>
                                Availability
                            </a>
                            <div class="flex items-center gap-1">
                                <button wire:click="jumpToday" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">Today</button>
                                <button wire:click="prevMonth" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-slate-300">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button wire:click="nextMonth" class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-slate-300">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[9px] font-semibold uppercase tracking-wide text-slate-400">
                            <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                        </div>

                        <div class="mt-1 grid grid-cols-7 gap-1">
                            @foreach($this->calendarDays as $day)
                                @php
                                    $remainingTone = match(true) {
                                        $day['closed'] => 'text-slate-300',
                                        $day['remaining'] === 0 => 'text-rose-500',
                                        $day['remaining'] <= (int) round($day['totalSlots'] * 0.2) => 'text-amber-500',
                                        default => 'text-emerald-600',
                                    };
                                    $cellClasses = match(true) {
                                        $day['isSelected'] => 'border-[#2A57B4] bg-blue-50 ring-1 ring-[#2A57B4]',
                                        !$day['inMonth'] => 'border-slate-100 bg-slate-50/50 opacity-40',
                                        $day['closed'] => 'border-slate-100 bg-slate-50',
                                        default => 'border-slate-200 bg-white hover:border-[#2A57B4] hover:bg-blue-50/40',
                                    };
                                @endphp
                                <button wire:click="selectDate('{{ $day['date'] }}')"
                                    title="{{ $day['closed'] ? 'Closed' : $day['remaining'] . ' slots left' }}{{ $day['pendingCount'] ? ' · ' . $day['pendingCount'] . ' pending' : '' }}{{ $day['flaggedCount'] ? ' · ' . $day['flaggedCount'] . ' flagged' : '' }}"
                                    class="relative flex h-10 flex-col items-center justify-center rounded-lg border text-[11px] transition {{ $cellClasses }}">
                                    @if($day['pendingCount'] > 0)
                                        <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-500 text-[8px] font-bold text-white">{{ $day['pendingCount'] }}</span>
                                    @endif
                                    @if($day['flaggedCount'] > 0)
                                        <span class="absolute -top-1 -left-1 h-2 w-2 rounded-full bg-rose-500"></span>
                                    @endif
                                    <span class="font-semibold {{ $day['inMonth'] ? 'text-slate-700' : 'text-slate-400' }}">{{ $day['day'] }}</span>
                                    @if($day['isToday'])
                                        <span class="absolute bottom-0.5 h-1 w-1 rounded-full bg-[#2A57B4]"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 border-t border-slate-100 pt-2 text-[9px] font-medium text-slate-500">
                            <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Pending</span>
                            <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Flagged</span>
                            <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-[#2A57B4]"></span>Today</span>
                        </div>
                    </div>

                    <template x-if="!open">
                        <p class="mt-1 text-[11px] text-slate-400">Click to browse dates</p>
                    </template>
                </div>

                {{-- STATS (click-to-switch, no auto-rotate) --}}
                <div x-data="{ tab: 'selected' }" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-900">Overview</h3>
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                            <button @click="tab = 'selected'" class="rounded px-2 py-0.5 text-[10px] font-semibold transition" :class="tab === 'selected' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500'">This day</button>
                            <button @click="tab = 'overall'" class="rounded px-2 py-0.5 text-[10px] font-semibold transition" :class="tab === 'overall' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500'">Overall</button>
                        </div>
                    </div>

                    <div x-show="tab === 'selected'" class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-slate-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-slate-400">Pending</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">{{ $this->pendingSummary['selected']['pending'] }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-[#2A57B4]">Approved</p>
                            <p class="mt-0.5 text-lg font-bold text-[#2A57B4]">{{ $this->pendingSummary['selected']['approved'] }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-emerald-500">AI: Approve</p>
                            <p class="mt-0.5 text-lg font-bold text-emerald-700">{{ $this->pendingSummary['selected']['ai_approve'] }}</p>
                        </div>
                        <div class="rounded-xl bg-rose-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-rose-500">AI: Reject</p>
                            <p class="mt-0.5 text-lg font-bold text-rose-700">{{ $this->pendingSummary['selected']['ai_reject'] }}</p>
                        </div>
                    </div>

                    <div x-show="tab === 'overall'" x-cloak class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-slate-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-slate-400">Pending</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">{{ $this->pendingSummary['overall']['pending'] }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-[#2A57B4]">Approved</p>
                            <p class="mt-0.5 text-lg font-bold text-[#2A57B4]">{{ $this->pendingSummary['overall']['approved'] }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-emerald-500">AI: Approve</p>
                            <p class="mt-0.5 text-lg font-bold text-emerald-700">{{ $this->pendingSummary['overall']['ai_approve'] }}</p>
                        </div>
                        <div class="rounded-xl bg-rose-50 p-2.5">
                            <p class="text-[9px] font-semibold uppercase text-rose-500">AI: Reject</p>
                            <p class="mt-0.5 text-lg font-bold text-rose-700">{{ $this->pendingSummary['overall']['ai_reject'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- QUEUE BOARD --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-900">Queue Board</h3>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-600">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            Live
                        </span>
                    </div>

                    <div class="mt-3 space-y-3">
                        @foreach(['morning' => 'Morning · 8–12', 'afternoon' => 'Afternoon · 1–5'] as $key => $label)
                            @php $snap = $this->queueBoard[$key]; @endphp
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-slate-700">{{ $label }}</p>
                                    <div class="flex items-center gap-1.5">
                                        <div class="text-right">
                                            <p class="text-[8px] uppercase tracking-wide text-slate-400">Serving</p>
                                            <p class="text-sm font-bold leading-none text-[#2A57B4]">{{ $snap['now_serving'] ?? '—' }}</p>
                                        </div>
                                        <button wire:click="skipNowServing('{{ $key }}')"
                                            class="rounded-lg border border-slate-300 bg-white px-1.5 py-1 text-[9px] font-medium text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">
                                            Skip →
                                        </button>
                                    </div>
                                </div>

                                <p class="mt-1 text-[10px] font-medium text-slate-500">
                                    @if(!$snap['open'])
                                        <span class="text-slate-400">Closed this day</span>
                                    @else
                                        {{ $snap['remaining'] }} of {{ $snap['slots'] }} remaining
                                    @endif
                                </p>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    @forelse($snap['list'] as $q)
                                        @php
                                            $style = match(true) {
                                                $q->status === 'attended' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                $q->status === 'retracted' => 'bg-slate-100 text-slate-400 border-slate-200 line-through',
                                                $q->queue_number === $snap['now_serving'] => 'bg-[#2A57B4] text-white border-[#2A57B4] shadow-sm',
                                                default => 'bg-white text-slate-600 border-slate-200 hover:border-[#2A57B4] cursor-pointer',
                                            };
                                        @endphp
                                        @if($q->status === 'approved')
                                            <button wire:click="setNowServing('{{ $key }}', {{ $q->queue_number }})"
                                                title="{{ $q->user->first_name }} {{ $q->user->last_name }}"
                                                class="flex h-6 w-6 items-center justify-center rounded-md border text-[10px] font-semibold transition {{ $style }}">
                                                {{ $q->queue_number }}
                                            </button>
                                        @else
                                            <div title="{{ $q->user->first_name }} {{ $q->user->last_name }}"
                                                class="flex h-6 w-6 items-center justify-center rounded-md border text-[10px] font-semibold {{ $style }}">
                                                {{ $q->queue_number }}
                                            </div>
                                        @endif
                                    @empty
                                        <p class="text-[10px] text-slate-400">No approved appointments yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- HISTORY / ALL LIST VIEW CONTENT (unchanged) -->
        <section class="space-y-4">
            <!-- Filter Bar -->
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search Student</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or ID..."
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="w-full sm:w-auto">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Date</label>
                    <input type="date" wire:model.live="filterDate"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="w-full sm:w-auto">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All Statuses</option>
                        @foreach(['pending','approved','rejected','attended','missed','retracted'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between px-1">
                <p class="text-xs font-semibold text-slate-500">
                    Showing {{ $this->historyAppointments->count() }} of {{ $this->historyAppointments->total() }} appointment(s)
                </p>
            </div>

            <div class="grid gap-3 grid-cols-1 md:grid-cols-2">
                @forelse($this->historyAppointments as $appt)
                    @include('livewire.admin.appointments.partials.appointment-card', ['appt' => $appt, 'selectable' => false, 'showDate' => true])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center text-xs text-slate-400">
                        No appointment records match your filters.
                    </div>
                @endforelse
            </div>

            <div class="mt-6 flex justify-center">
                <div class="w-full max-w-md flex justify-center">
                    {{ $this->historyAppointments->links() }}
                </div>
            </div>
        </section>
    @endif

    {{-- MODALS (unchanged) --}}
    <template x-teleport="body">
        <div x-show="$wire.showBulkApprove" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl transition-all">
                <h3 class="text-lg font-bold text-slate-900">Approve {{ $this->selectedPendingCount }} appointment(s)</h3>
                <p class="text-xs text-slate-500 mt-1">Queue numbers will be assigned for each session in order of submission. This cannot be undone.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="$wire.showBulkApprove = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button wire:click="bulkApprove" @click="$wire.showBulkApprove = false" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">Confirm Approval</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="$wire.showBulkReject" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl transition-all">
                <h3 class="text-lg font-bold text-slate-900">Reject {{ $this->selectedPendingCount }} appointment(s)</h3>
                <p class="text-xs text-slate-500 mt-1">This reason will be sent to every pending appointment in your selection. Non-pending selections are skipped.</p>
                <select wire:model.live="bulkRejectReason" class="mt-4 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                    <option value="">-- Select Rejection Reason --</option>
                    <option value="Incomplete or unreadable supporting documents submitted.">Incomplete or unreadable supporting documents submitted.</option>
                    <option value="Duplicate or conflicting appointment request detected.">Duplicate or conflicting appointment request detected.</option>
                    <option value="Ineligible for requested appointment type or service.">Ineligible for requested appointment type or service.</option>
                    <option value="Custom">Other / Custom Reason</option>
                </select>
                @error('bulkRejectReason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                @if($this->bulkRejectReason === 'Custom')
                    <textarea wire:model="customBulkRejectReason" rows="3" placeholder="Enter specific rejection reason..."
                        class="mt-2.5 w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"></textarea>
                    @error('customBulkRejectReason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                @endif
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="$wire.showBulkReject = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button wire:click="bulkReject" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700">Reject selected</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="$wire.showBulkReschedule" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl transition-all">
                <h3 class="text-lg font-bold text-slate-900">Reschedule {{ count($selectedIds) }} appointment(s)</h3>
                <p class="text-xs text-slate-500 mt-1">Applies to pending and approved selections; approved ones return to pending and lose their queue number.</p>
                <input type="date" wire:model="bulkRescheduleDate" class="mt-4 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('bulkRescheduleDate') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                <select wire:model="bulkRescheduleSession" class="mt-2.5 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Select session</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                </select>
                @error('bulkRescheduleSession') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                <select wire:model.live="bulkRescheduleReason" class="mt-2.5 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Select Reason --</option>
                    <option value="Official event conflict or university closure.">Official event conflict or university closure.</option>
                    <option value="Assigned staff unavailable on original date.">Assigned staff unavailable on original date.</option>
                    <option value="System adjustment / Queue capacity reallocation.">System adjustment / Queue capacity reallocation.</option>
                    <option value="Custom">Other / Custom Reason</option>
                </select>
                @if($bulkRescheduleReason === 'Custom')
                    <textarea wire:model="customBulkRescheduleReason" rows="3" placeholder="Reason for rescheduling (required)..."
                        class="mt-2.5 w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                @endif
                @error('bulkRescheduleReason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="$wire.showBulkReschedule = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button wire:click="bulkReschedule" class="rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Confirm</button>
                </div>
            </div>
        </div>
    </template>
</div>