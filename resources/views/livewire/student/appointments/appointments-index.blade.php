<div x-data="{
    // Active Navigation Tab
    activeTab: 'upcoming',

    // View Controls (Upcoming)
    groupByDate: false,

    // Upcoming UI Management State
    upcomingSearch: '',
    upcomingFilter: 'all',
    upcomingSort: 'desc', // 'desc' | 'asc'

    // History UI Management State
    historySearch: '',
    historyFilter: 'all',
    historyDateFilter: '',
    historySort: 'desc', // 'desc' | 'asc'

    // Helper function for upcoming filtering
    matchUpcoming(purpose, doc, status) {
        const query = this.upcomingSearch.toLowerCase().trim();
        const matchesQuery = !query || purpose.toLowerCase().includes(query) || doc.toLowerCase().includes(query);
        const matchesStatus = this.upcomingFilter === 'all' || status.toLowerCase() === this.upcomingFilter.toLowerCase();
        return matchesQuery && matchesStatus;
    },

    // Helper function for history filtering
    matchHistory(purpose, doc, status, date) {
        const query = this.historySearch.toLowerCase().trim();
        const matchesQuery = !query || purpose.toLowerCase().includes(query) || doc.toLowerCase().includes(query);
        const matchesStatus = this.historyFilter === 'all' || status.toLowerCase() === this.historyFilter.toLowerCase();
        const matchesDate = !this.historyDateFilter || date.startsWith(this.historyDateFilter);
        return matchesQuery && matchesStatus && matchesDate;
    }
}" wire:poll.5s x-data="{ ... }" class="mx-auto max-w-4xl space-y-6 px-3 py-2 sm:px-4">

    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Appointments</h2>
            <p class="mt-0.5 text-xs text-slate-500">Manage your scheduled office visits and track live queue status</p>
        </div>
        <a href="{{ route('student.appointments.new') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-xs sm:text-sm font-semibold text-white transition-all hover:bg-[#204491] active:scale-[0.98] w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Book Appointment
        </a>
    </div>

    {{-- Main Tabs Navigation --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 sm:space-x-8" aria-label="Tabs">
            <button type="button" @click="activeTab = 'upcoming'"
                :class="activeTab === 'upcoming' 
                    ? 'border-[#2A57B4] text-[#2A57B4] font-bold' 
                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm transition-all">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Upcoming Visits</span>
                <span :class="activeTab === 'upcoming' ? 'bg-blue-100 text-[#2A57B4]' : 'bg-slate-100 text-slate-600'"
                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold">
                    {{ count($pending) }}
                </span>
            </button>

            <button type="button" @click="activeTab = 'history'"
                :class="activeTab === 'history' 
                    ? 'border-[#2A57B4] text-[#2A57B4] font-bold' 
                    : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm transition-all">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Past History</span>
                <span :class="activeTab === 'history' ? 'bg-blue-100 text-[#2A57B4]' : 'bg-slate-100 text-slate-600'"
                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold">
                    {{ count($history) }}
                </span>
            </button>
        </nav>
    </div>

    {{-- TAB 1: UPCOMING VISITS --}}
    <div x-show="activeTab === 'upcoming'" class="space-y-4">
        {{-- Standalone Search Bar (Upcoming) --}}
        <div class="relative w-full">
            <input type="text" x-model="upcomingSearch" placeholder="Search upcoming visits by purpose or template..."
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-xs sm:text-sm text-slate-700 placeholder-slate-400 shadow-sm focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4]" />
            <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        {{-- Toolbar: Upcoming Filters --}}
        <div class="flex flex-col gap-3 rounded-xl bg-slate-50/80 p-3 sm:flex-row sm:items-center sm:justify-between border border-slate-100">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">Layout Style:</span>
                <div class="flex items-center rounded-lg bg-slate-200/60 p-0.5 text-[11px] font-medium text-slate-600">
                    <button type="button" @click="groupByDate = false" 
                        :class="!groupByDate ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-md px-2.5 py-1 transition-all">
                        List View
                    </button>
                    <button type="button" @click="groupByDate = true" 
                        :class="groupByDate ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-md px-2.5 py-1 transition-all">
                        By Date
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <select x-model="upcomingFilter" 
                    class="appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-600 focus:border-[#2A57B4] focus:outline-none">
                    <option value="all">All Statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                </select>

                <select x-model="upcomingSort" 
                    class="appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-600 focus:border-[#2A57B4] focus:outline-none">
                    <option value="desc">Newest Date First</option>
                    <option value="asc">Oldest Date First</option>
                </select>
            </div>
        </div>

        {{-- Standard View List --}}
        <div x-show="!groupByDate">
            <div class="space-y-3" :class="upcomingSort === 'asc' ? 'flex flex-col-reverse space-y-reverse' : ''">
                @forelse($pending as $appt)
                    @include('livewire.student.appointments.partials.appointment-card', ['appt' => $appt])
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                        <p class="text-sm font-medium text-slate-700">No upcoming appointments</p>
                        <p class="mt-0.5 text-xs text-slate-400">Book a visit to request document services or assistance.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Grouped By Date View --}}
        <div x-show="groupByDate">
            <div class="space-y-6 pt-1" :class="upcomingSort === 'asc' ? 'flex flex-col-reverse space-y-reverse' : ''">
                @php
                    $unapprovedPending = $pending->filter(fn($item) => strtolower($item->status) === 'pending' || !$item->appointment_date);
                    $scheduledPending = $pending->reject(fn($item) => strtolower($item->status) === 'pending' || !$item->appointment_date);
                    $groupedPending = $scheduledPending->groupBy(fn($item) => $item->appointment_date->format('Y-m-d'));
                @endphp

                @if($unapprovedPending->count() > 0)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1.5 border-b-2 border-amber-200/80">
                            <div class="flex items-center gap-2">
                                <span class="text-base sm:text-lg font-extrabold text-amber-700">Pending Approval</span>
                                <span class="text-slate-300">•</span>
                                <span class="text-xs font-semibold text-amber-600/90">Awaiting Schedule Assignment</span>
                            </div>
                            <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                {{ $unapprovedPending->count() }} {{ Str::plural('request', $unapprovedPending->count()) }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($unapprovedPending as $appt)
                                @include('livewire.student.appointments.partials.appointment-card', ['appt' => $appt])
                            @endforeach
                        </div>
                    </div>
                @endif

                @forelse($groupedPending as $dateKey => $dayAppointments)
                    @php
                        $firstAppt = $dayAppointments->first();
                        $isToday = $firstAppt->appointment_date->isToday();
                        $isTomorrow = $firstAppt->appointment_date->isTomorrow();
                        $dayLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $firstAppt->appointment_date->format('l'));
                        $fullDate = $firstAppt->appointment_date->format('F j, Y');
                    @endphp
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base sm:text-lg font-extrabold text-slate-900">{{ $dayLabel }}</span>
                                <span class="text-slate-300">•</span>
                                <span class="text-xs sm:text-sm font-semibold text-slate-600">{{ $fullDate }}</span>
                            </div>
                            <span class="text-xs font-bold text-slate-500">
                                {{ count($dayAppointments) }} {{ Str::plural('visit', count($dayAppointments)) }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($dayAppointments as $appt)
                                @include('livewire.student.appointments.partials.appointment-card', ['appt' => $appt])
                            @endforeach
                        </div>
                    </div>
                @empty
                    @if($unapprovedPending->count() === 0)
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                            <p class="text-sm font-medium text-slate-700">No upcoming appointments</p>
                            <p class="mt-0.5 text-xs text-slate-400">Book a visit to request document services or assistance.</p>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB 2: PAST HISTORY --}}
    <div x-show="activeTab === 'history'" x-cloak class="space-y-4">
        {{-- History Search & Toolbar --}}
        <div class="relative w-full">
            <input type="text" x-model="historySearch" placeholder="Search history by purpose or template..."
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-xs sm:text-sm text-slate-700 placeholder-slate-400 shadow-sm focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4]" />
            <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-slate-50/80 p-3 border border-slate-100">
            <span class="text-xs font-semibold text-slate-500">Filter History:</span>

            <div class="grid grid-cols-3 gap-2 sm:flex sm:items-center">
                <div class="relative col-span-1">
                    <input type="date" x-model="historyDateFilter"
                        class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-700 focus:border-[#2A57B4] focus:outline-none" />
                    <button type="button" x-show="historyDateFilter" @click="historyDateFilter = ''" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-slate-600 bg-white rounded px-0.5">
                        ✕
                    </button>
                </div>

                <select x-model="historyFilter" 
                    class="col-span-1 appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-600 focus:border-[#2A57B4] focus:outline-none">
                    <option value="all">All Statuses</option>
                    <option value="attended">Attended</option>
                    <option value="missed">Missed</option>
                    <option value="retracted">Retracted</option>
                    <option value="rejected">Rejected</option>
                </select>

                <select x-model="historySort" 
                    class="col-span-1 appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-600 focus:border-[#2A57B4] focus:outline-none">
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
        </div>

        <div class="space-y-2" :class="historySort === 'asc' ? 'flex flex-col-reverse space-y-reverse' : ''">
            @forelse($history as $appt)
                @php
                    $badgeStyle = match($appt->status) {
                        'attended' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                        'missed' => 'bg-rose-50 text-rose-700 border-rose-200/60',
                        'retracted' => 'bg-slate-100 text-slate-600 border-slate-200',
                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                    };
                    $purpose = $appt->purpose ?: 'General Visit';
                    $docName = $appt->workspace?->template?->name ?? 'General Visit';
                    $formattedDate = $appt->appointment_date?->format('Y-m-d') ?? '';
                @endphp
                <a x-show="matchHistory(@js($purpose), @js($docName), @js($appt->status), @js($formattedDate))"
                    href="{{ route('student.appointments.show', $appt->appointment_id) }}" wire:navigate
                    class="flex flex-col sm:flex-row sm:items-center justify-between rounded-xl border border-slate-200/80 bg-white p-3.5 transition-colors hover:border-[#2A57B4]/40 gap-2 sm:gap-4">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800 truncate">
                                {{ $appt->purpose ?: 'General visit' }}
                            </p>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                            <div class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>{{ $appt->workspace?->template?->name ?? 'General visit' }}</span>
                            </div>

                            <span>•</span>

                            <div class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $appt->appointment_date?->format('M j, Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1 sm:pt-0 border-t sm:border-0 border-slate-100">
                        <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $badgeStyle }}">
                            {{ $appt->status }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                    <p class="text-sm font-medium text-slate-700">No past history found</p>
                    <p class="mt-0.5 text-xs text-slate-400">Past completed or cancelled appointments will show up here.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Retraction Confirmation Modal --}}
    <template x-teleport="body">   
        <div x-show="$wire.retractingId" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px);">
            <div class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-center text-base font-semibold text-slate-900">Retract this appointment?</h3>
                <p class="mt-1 text-center text-xs text-slate-500">Your queue position will be canceled.</p>
                
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="$set('retractingId', null)" class="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="retract" class="flex-1 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                        Confirm Retract
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>