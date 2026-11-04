@php
    use App\Livewire\Student\Appointments\AppointmentsIndex;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btnBase = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $iconBtn = 'grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;

    $statusFor = fn ($a) => match ($a->status) {
        'pending' => ['Waiting for approval', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'approved' => ['Approved' . ($a->queue_number ? ' · #' . $a->queue_number : ''), 'bg-[#e2e9f7] text-[#1E4090] ring-sky-200'],
        'attended' => ['Attended', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'missed' => ['Missed', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'rejected' => ['Not approved', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'retracted' => ['Withdrawn', 'bg-slate-100 text-slate-600 ring-slate-200'],
        default => [ucfirst($a->status), 'bg-slate-100 text-slate-600 ring-slate-200'],
    };

    // The live queue line on approved visits: [text, classes, pulsing?].
    $queueFor = function ($a) {
        if ($a->status !== 'approved') {
            return null;
        }

        return match (true) {
            ! $a->appointment_date->isToday() => ['Queue tracking opens ' . $a->appointment_date->format('M j'), 'text-slate-600', false],
            ! $a->isWithinServiceWindow() => ['Outside service hours (' . $a->sessionLabel() . ')', 'text-amber-800', false],
            $a->now_serving === $a->queue_number => ['It is your turn. Please go to the counter.', 'font-medium text-emerald-800', true],
            $a->now_serving !== null && $a->queue_number < $a->now_serving => ['Your number has passed. Please check in with the staff.', 'text-rose-800', false],
            $a->now_serving !== null => ['Now serving #' . $a->now_serving . ' · ' . ($a->queue_number - $a->now_serving) . ' ahead of you', 'text-[#1E4090]', true],
            default => ['Live queue · waiting for the first call', 'text-[#1E4090]', true],
        };
    };

    $counts = $this->counts;
    $sessionShort = ['morning' => 'Morning · 8–12', 'afternoon' => 'Afternoon · 1–5'];
@endphp

<div class="mx-auto max-w-6xl space-y-5 pb-28" @if ($live) wire:poll.5s @endif>

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Appointments</h1>
            <p class="mt-0.5 text-sm text-slate-500">Your visits to the VPSD office, with your queue number once they are approved.</p>
        </div>
        <a href="{{ route('student.appointments.new') }}" wire:navigate class="{{ $btnPrimary }} shrink-0">
            <i class="bx bx-plus text-base" aria-hidden="true"></i> Book an appointment
        </a>
    </div>

    @if ($booked)
        <p class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
            <i class="bx bx-check-circle mt-0.5 text-base text-emerald-600" aria-hidden="true"></i> {{ $booked }}
        </p>
    @endif

    {{-- Toolbar: tabs, search and view on one line at every width --}}
    @php
        $segS = fn ($on) => 'inline-flex min-h-8 items-center gap-1.5 rounded-md px-2 text-sm transition-colors sm:px-3 [@media(pointer:coarse)]:min-h-10 ' . $focus . ' '
            . ($on ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900');
    @endphp
    <div class="relative flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white p-2 sm:gap-2 sm:p-3">
        <nav class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1" aria-label="Appointments">
            @foreach (AppointmentsIndex::FILTERS as $key => $label)
                @php $on = $filter === $key; @endphp
                <button type="button" wire:click="setFilter('{{ $key }}')" @if ($on) aria-current="page" @endif class="{{ $segS($on && $viewMode === 'list') }}">
                    @if ($key === 'past')<span class="sm:hidden">Past</span><span class="hidden sm:inline">{{ $label }}</span>@else{{ $label }}@endif
                    <span class="rounded-full px-1.5 text-[11px] tabular-nums {{ $on && $viewMode === 'list' ? 'bg-[#e2e9f7] text-[#1E4090]' : 'bg-white/80 text-slate-600' }}">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </nav>
        @if ($viewMode === 'list')
            <x-dm.toolbar-search class="ml-auto sm:max-w-xs" placeholder="Search purpose or document" label="Search by purpose or document" />
        @endif
        <div class="{{ $viewMode === 'list' ? '' : 'ml-auto' }} inline-flex shrink-0 rounded-lg bg-slate-100 p-1" role="group" aria-label="View">
            @foreach (['list' => ['bx-list-ul', 'List'], 'calendar' => ['bx-calendar', 'Calendar']] as $key => [$icon, $label])
                <button type="button" wire:click="setViewMode('{{ $key }}')" aria-pressed="{{ $viewMode === $key ? 'true' : 'false' }}" class="{{ $segS($viewMode === $key) }}" title="{{ $label }}">
                    <i class="bx {{ $icon }} text-base" aria-hidden="true"></i><span class="max-md:sr-only">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ============ List: one row per visit, date first ============ --}}
    @if ($viewMode === 'list')
        <ul class="min-h-[22rem] divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ AppointmentsIndex::FILTERS[$filter] }}">
            @forelse ($this->rows as $a)
                @php [$sText, $sPill] = $statusFor($a); $queue = $queueFor($a); @endphp
                <li wire:key="sr-{{ $a->appointment_id }}" class="relative flex items-start gap-3 p-3 transition-colors hover:bg-slate-50 sm:items-center sm:gap-4 sm:p-4">
                    {{-- Date tile --}}
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl {{ $a->appointment_date->isToday() ? 'bg-[#2A57B4] text-white' : 'bg-[#f0f4fb] text-[#1E4090]' }}" aria-hidden="true">
                        <span class="text-center leading-none">
                            <span class="block text-[11px] font-medium">{{ $a->appointment_date->format('M') }}</span>
                            <span class="mt-0.5 block text-xl font-semibold tabular-nums">{{ $a->appointment_date->format('j') }}</span>
                        </span>
                    </span>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('student.appointments.show', $a->appointment_id) }}" wire:navigate
                           class="block rounded font-medium text-slate-900 after:absolute after:inset-0 after:content-[''] hover:text-[#2A57B4] {{ $focus }}">
                            {{ $a->purpose ?: 'General visit' }}
                        </a>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <span class="sr-only">{{ $a->appointment_date->format('l, F j, Y') }}, </span>
                            {{ $a->appointment_date->isToday() ? 'Today' : $a->appointment_date->format('l') }} · {{ $sessionShort[$a->session] ?? ucfirst($a->session) }}
                            @if ($a->workspace) · {{ $a->workspace->documentName() }} @endif
                        </p>
                        @if ($queue)
                            <p class="mt-1 inline-flex items-center gap-1.5 text-xs {{ $queue[1] }}" aria-live="polite">
                                @if ($queue[2]) <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current motion-reduce:animate-none" aria-hidden="true"></span> @endif
                                {{ $queue[0] }}
                            </p>
                        @endif
                        @if ($a->status === 'rejected' && $a->rejection_reason)
                            <p class="mt-1 text-xs text-rose-800">Reason: {{ $a->rejection_reason }}</p>
                        @endif
                        <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 sm:hidden {{ $sPill }}">{{ $sText }}</span>
                    </div>

                    <div class="relative z-10 hidden shrink-0 items-center gap-2 sm:flex">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $sPill }}">{{ $sText }}</span>
                    </div>
                    @if ($a->canBeDeleted())
                        <button type="button" wire:click="confirmRetract({{ $a->appointment_id }})"
                                class="relative z-10 {{ $btnBase }} shrink-0 !px-2.5 text-rose-700 hover:bg-rose-50">Withdraw<span class="sr-only"> {{ $a->purpose }}</span></button>
                    @elseif ($a->canReapply())
                        <a href="{{ route('student.appointments.reapply', $a->appointment_id) }}" wire:navigate
                           class="relative z-10 {{ $btnBase }} shrink-0 !px-2.5 text-[#2A57B4] hover:bg-[#f0f4fb]">Book again</a>
                    @else
                        <i class="bx bx-chevron-right shrink-0 self-center text-xl text-slate-400" aria-hidden="true"></i>
                    @endif
                </li>
            @empty
                <li class="px-6 py-14 text-center text-sm text-slate-500">
                    {{ trim($search) !== '' ? 'No record match your search.' : ($filter === 'past' ? 'No past visits yet.' : 'No upcoming visits.') }}
                    @if ($filter !== 'past' && trim($search) === '')
                        <a href="{{ route('student.appointments.new') }}" wire:navigate class="ml-1 font-medium text-[#2A57B4] hover:underline">Book an appointment</a>
                    @endif
                </li>
            @endforelse
        </ul>
    @endif

    {{-- ============ Calendar: the dashboard's wall calendar ============ --}}
    @if ($viewMode === 'calendar')
        <div class="cq">
        <x-dm.wall-calendar :title="$monthName" :subtitle="$monthYear . ' · ' . $monthCount . ' ' . str('visit')->plural($monthCount)"
                            prev="prevMonth" next="nextMonth" :today="$isThisMonth ? null : 'thisMonth'">
            <div class="grid grid-cols-7 [&>*:nth-child(7n)]:border-r-0" wire:loading.class="opacity-60" wire:target="prevMonth, nextMonth, thisMonth">
                @foreach ($this->calendarDays as $cell)
                    @if (! $cell['inMonth'])
                        <span wire:key="scal-{{ $cell['date'] }}" class="h-16 border-b border-r border-slate-100 bg-slate-50/70 p-1.5 text-xs text-slate-300 sm:h-24" aria-hidden="true">{{ $cell['day'] }}</span>
                    @else
                        @php $n = $cell['items']->count(); @endphp
                        <div wire:key="scal-{{ $cell['date'] }}"
                             class="relative flex h-16 min-w-0 flex-col justify-between border-b border-r border-slate-100 p-1.5 sm:h-24
                                {{ $cell['weekend'] ? 'bg-rose-50/40' : ($n ? 'bg-blue-50' : '') }}">
                            <span class="flex items-start justify-between">
                                <span @class([
                                    'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                    'bg-[#2A57B4] text-white shadow-sm' => $cell['isToday'],
                                    'text-rose-500' => ! $cell['isToday'] && $cell['weekend'],
                                    'text-slate-800' => ! $cell['isToday'] && ! $cell['weekend'],
                                ])>{{ $cell['day'] }}</span>
                                @if ($cell['items']->contains('status', 'pending'))
                                    <span class="mt-1 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white" aria-hidden="true"></span>
                                @endif
                            </span>
                            @if ($n)
                                {{-- Phones: one chip that opens the first visit; wider: a chip per visit --}}
                                @php $first = $cell['items']->first(); @endphp
                                <a href="{{ route('student.appointments.show', $first->appointment_id) }}" wire:navigate
                                   class="truncate rounded bg-[#2A57B4]/10 px-1 text-[10px] font-semibold tabular-nums text-[#1E4090] sm:hidden {{ $focus }}">
                                    {{ $n }}<span class="sr-only"> {{ str('visit')->plural($n) }} on {{ \Carbon\Carbon::parse($cell['date'])->format('F j') }}</span>
                                </a>
                                <span class="hidden min-w-0 flex-col gap-0.5 sm:flex">
                                    @foreach ($cell['items']->take(2) as $a)
                                        @php [$sText, $sPill] = $statusFor($a); @endphp
                                        <a href="{{ route('student.appointments.show', $a->appointment_id) }}" wire:navigate
                                           class="block truncate rounded px-1 text-[10px] font-semibold ring-1 {{ $sPill }} {{ $focus }}"
                                           title="{{ $a->purpose }} · {{ $sText }}">
                                            {{ $a->session === 'morning' ? '8–12' : '1–5' }} · {{ $a->purpose ?: 'Visit' }}
                                            <span class="sr-only">, {{ $a->appointment_date->format('F j') }}, {{ $sText }}</span>
                                        </a>
                                    @endforeach
                                    @if ($n > 2)<span class="text-[10px] text-slate-500">+{{ $n - 2 }} more</span>@endif
                                </span>
                            @elseif ($cell['weekend'])
                                <span class="text-[10px] font-medium text-rose-400">Closed</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </x-dm.wall-calendar>
        </div>
        <p class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span> Waiting for approval</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50 ring-1 ring-rose-200" aria-hidden="true"></span> Office closed</span>
            <span>Select a visit to see its details.</span>
        </p>
    @endif

    {{-- Withdraw --}}
    @if ($retractingId)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="cancelRetract" wire:click.self="cancelRetract"
             role="alertdialog" aria-modal="true" aria-labelledby="retract-title" aria-describedby="retract-lead">
            <div class="w-full max-w-sm rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6" x-data x-init="$nextTick(() => $refs.keep.focus())">
                <h2 id="retract-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">Withdraw this appointment?</h2>
                <p id="retract-lead" class="mt-1 text-sm text-slate-500">Your request is cancelled and any queue number is released. You can book again later.</p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-ref="keep" wire:click="cancelRetract" class="{{ $btnSecondary }}">Keep it</button>
                    <button type="button" wire:click="retract" wire:loading.attr="disabled" class="{{ $btnBase }} bg-rose-600 text-white shadow-sm hover:bg-rose-700">Withdraw</button>
                </div>
            </div>
        </div>
    @endif
</div>
