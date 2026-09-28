<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Appointments</h2>
        <a href="{{ route('admin.appointments.availability') }}" wire:navigate
            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18" />
            </svg>
            Manage Availability
        </a>
    </div>

    {{-- Live queue tracking — always visible, independent of active tab --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900">Today's Queue</h3>
                <p class="mt-0.5 text-xs text-slate-400">{{ now()->format('F j, Y') }} · Live tracking across both sessions</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-600">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                Live
            </span>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach(['morning' => 'Morning · 8:00 AM–12:00 PM', 'afternoon' => 'Afternoon · 1:00 PM–5:00 PM'] as $key => $label)
                @php $snap = $queueSnapshot[$key]; @endphp
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700">{{ $label }}</p>
                        <div class="flex items-center gap-2">
                            <div class="text-right">
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Now Serving</p>
                                <p class="text-lg font-bold leading-none text-[#2A57B4]">{{ $snap['now_serving'] ?? '—' }}</p>
                            </div>
                            <button wire:click="skipNowServing('{{ $key }}')"
                                class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">
                                Skip →
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
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
                                    title="{{ $q->user->first_name }} {{ $q->user->last_name }} — click to set as now serving"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold transition {{ $style }}">
                                    {{ $q->queue_number }}
                                </button>
                            @else
                                <div title="{{ $q->user->first_name }} {{ $q->user->last_name }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold {{ $style }}">
                                    {{ $q->queue_number }}
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-slate-400">No approved appointments yet for this session.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap gap-3 text-[11px] text-slate-500">
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#2A57B4]"></span> Now serving</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Attended</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Retracted</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full border border-slate-300 bg-white"></span> Waiting</span>
        </div>
    </section>

    {{-- Summary cards --}}
    <section>
        <p class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
            <span class="h-1.5 w-1.5 rounded-full {{ $tab === 'history' ? 'bg-slate-400' : 'bg-emerald-500' }}"></span>
            {{ $tab === 'history' ? "All-time appointment summary" : "Today's appointment summary" }}
        </p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach($summary as $card)
                @php
                    $valueClass = match($card['tone']) {
                        'green' => 'text-emerald-600',
                        'amber' => 'text-amber-600',
                        'red' => 'text-rose-600',
                        default => 'text-slate-900',
                    };
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $valueClass }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tabs + filters --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex flex-wrap rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['review' => 'For Review', 'daily' => 'Daily', 'completed' => 'Completed', 'missed' => 'Missed'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === $key ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <button wire:click="$set('tab', 'history')"
                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition {{ $tab === 'history' ? 'border-[#2A57B4] bg-blue-50 text-[#2A57B4]' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path stroke-linecap="round" d="M12 7v5l3 3" />
                </svg>
                History
            </button>
        </div>

        @if($tab === 'history')
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                <input type="date" wire:model.live="filterDate" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <select wire:model.live="filterStatus" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach(['pending','approved','rejected','attended','missed','retracted'] as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student..."
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </div>
        @endif

        <p class="text-xs text-slate-400">
            @switch($tab)
                @case('review') Appointments awaiting your review, across all dates. @break
                @case('daily') Approved appointments scheduled for today. @break
                @case('completed') Attended, rejected, or retracted appointments — today only. @break
                @case('missed') All missed appointments, regardless of date. @break
                @case('history') Full appointment history — every status, every date. @break
            @endswitch
        </p>
    </section>

    {{-- Appointment list --}}
    <section class="space-y-3">
        @forelse($appointments as $appt)
            @php
                $badge = match($appt->status) {
                    'approved' => 'bg-emerald-50 text-emerald-600',
                    'attended' => 'bg-emerald-50 text-emerald-600',
                    'missed' => 'bg-rose-50 text-rose-600',
                    'rejected' => 'bg-rose-50 text-rose-600',
                    'retracted' => 'bg-slate-100 text-slate-500',
                    default => 'bg-amber-50 text-amber-600',
                };
                $isNowServing = $appt->status === 'approved'
                    && $appt->queue_number
                    && $appt->queue_number === ($queueSnapshot[$appt->session]['now_serving'] ?? null);
            @endphp
            <a href="{{ route('admin.appointments.show', $appt->appointment_id) }}" wire:navigate
                class="flex items-center justify-between rounded-xl border bg-white p-4 shadow-sm transition {{ $isNowServing ? 'border-[#2A57B4] ring-1 ring-[#2A57B4]' : 'border-slate-200 hover:border-slate-300' }}">
                <div class="flex min-w-0 items-center gap-3">
                    @if($isNowServing)
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-sm font-bold text-white">
                            {{ $appt->queue_number }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">
                            {{ $appt->user->first_name }} {{ $appt->user->last_name }} —
                            {{ $appt->workspace?->template?->name ?? 'General visit (no document)' }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-slate-400">
                            {{ $appt->queue_number ? 'Queue #' . $appt->queue_number : 'Awaiting approval' }}
                            · {{ ucfirst($appt->session) }} · {{ $appt->appointment_date->format('M j, Y') }}
                            @if($isNowServing)
                                <span class="font-semibold text-[#2A57B4]"> · Now serving</span>
                            @endif
                            @if($appt->workspace?->missed_count > 0)
                                <span class="text-rose-500"> · Missed {{ $appt->workspace->missed_count }}x before</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if($appt->reschedule_count > 0 && $appt->status === 'pending')
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">Rescheduled</span>
                    @endif
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">{{ ucfirst($appt->status) }}</span>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                Nothing here.
            </div>
        @endforelse
    </section>
</div>