@php
    use App\Livewire\Admin\Appointments\AppointmentsBoard;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $field = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
    $btnBase = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $iconBtn = 'grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;
    $seg = fn ($on) => 'inline-flex min-h-8 items-center gap-1.5 rounded-md px-3 text-sm transition-colors [@media(pointer:coarse)]:min-h-10 ' . $focus . ' '
        . ($on ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900');

    // Status as a pill (DESIGN.md: tint + deep text + 200 ring): [text, classes].
    $statusFor = fn ($a) => match ($a->status) {
        'pending' => ['Pending', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'approved' => ['Approved' . ($a->queue_number ? ' · #' . $a->queue_number : ''), 'bg-[#e2e9f7] text-[#1E4090] ring-sky-200'],
        'attended' => ['Attended', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'missed' => ['Missed', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'rejected' => ['Rejected', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'retracted' => ['Withdrawn', 'bg-slate-100 text-slate-600 ring-slate-200'],
        default => [ucfirst($a->status), 'bg-slate-100 text-slate-600 ring-slate-200'],
    };

    // AI check as a compact pill: [text, classes, spinning?].
    $aiFor = function ($a) {
        $rec = $a->ai_findings['recommendation'] ?? null;
        $fits = $a->ai_findings['relevance']['relevant'] ?? true;

        return match (true) {
            $a->ai_flag === 'pending' => ['Checking', 'bg-sky-50 text-[#1E4090] ring-sky-200', true],
            $a->ai_flag === 'review_failed' => ['Check failed', 'bg-[#fdece3] text-[#b4471a] ring-orange-200', false],
            $a->ai_flag === null => ['Not checked', 'bg-slate-100 text-slate-600 ring-slate-200', false],
            $rec === 'reject' => ['Suggests reject', 'bg-rose-50 text-rose-800 ring-rose-200', false],
            ! $fits => ['Purpose mismatch', 'bg-amber-50 text-amber-800 ring-amber-200', false],
            $a->ai_flag === 'flagged' => ['Worth a look', 'bg-amber-50 text-amber-800 ring-amber-200', false],
            default => ['Looks fine', 'bg-emerald-50 text-emerald-800 ring-emerald-200', false],
        };
    };

    // Calendar event cards: tinted by where the visit stands.
    $eventTint = fn ($a) => match ($a->status) {
        'pending' => $a->ai_flag === 'flagged' ? 'bg-rose-50 ring-rose-200' : 'bg-amber-50 ring-amber-200',
        'approved' => 'bg-sky-50 ring-sky-200',
        'attended' => 'bg-emerald-50 ring-emerald-200',
        default => 'bg-slate-100 ring-slate-200',
    };

    $initials = function ($user) {
        $parts = preg_split('/\s+/', trim($user?->full_name ?? '')) ?: [];

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) ?: '?';
    };
    $avatar = fn ($user) => $user?->profile_picture ? asset('storage/' . $user->profile_picture) : null;

    $popped = $this->popped;
    $weekStart = $day->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
    $rangeLabel = match (true) {
        $viewMode === 'calendar' && $calendarMode === 'month' => $monthLabel,
        $viewMode === 'calendar' => $weekStart->format('M j') . ' – ' . $weekStart->copy()->addDays(6)->format($weekStart->month === $weekStart->copy()->addDays(6)->month ? 'j, Y' : 'M j, Y'),
        default => ($day->isToday() ? 'Today, ' : ($day->isTomorrow() ? 'Tomorrow, ' : $day->format('l') . ', ')) . $day->format('M j, Y'),
    };
    $sessions = ['morning' => ['Morning', '8:00 – 12:00'], 'afternoon' => ['Afternoon', '1:00 – 5:00']];
@endphp

<div class="mx-auto max-w-[1440px] space-y-5" x-data @if ($polling) wire:poll.5s @endif>

    {{-- Header --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Appointments</h1>
            <p class="mt-0.5 text-sm text-slate-500">See who is coming, set the office's hours, review requests and run the queue.</p>
        </div>
        <button type="button" wire:click="toggleEditingHours" aria-pressed="{{ $editingHours ? 'true' : 'false' }}"
                class="{{ $editingHours ? $btnPrimary : $btnSecondary }} shrink-0 self-start md:self-auto">
            <i class="bx bx-time text-base {{ $editingHours ? '' : 'text-slate-500' }}" aria-hidden="true"></i>
            {{ $editingHours ? 'Done setting hours' : 'Set office hours' }}
        </button>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg bg-slate-100 p-1" role="group" aria-label="View">
                @foreach (['calendar' => ['bx-calendar', 'Calendar'], 'list' => ['bx-list-ol', 'Day list'], 'history' => ['bx-history', 'All']] as $key => [$icon, $label])
                    <button type="button" wire:click="setViewMode('{{ $key }}')" aria-pressed="{{ $viewMode === $key ? 'true' : 'false' }}" class="{{ $seg($viewMode === $key) }}">
                        <i class="bx {{ $icon }} text-base" aria-hidden="true"></i> {{ $label }}
                    </button>
                @endforeach
            </div>
            @if ($viewMode === 'calendar')
                <div class="inline-flex rounded-lg bg-slate-100 p-1" role="group" aria-label="Calendar range">
                    @foreach (['week' => 'Week', 'month' => 'Month'] as $key => $label)
                        <button type="button" wire:click="setCalendarMode('{{ $key }}')" aria-pressed="{{ $calendarMode === $key ? 'true' : 'false' }}" class="{{ $seg($calendarMode === $key) }}">{{ $label }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex min-w-0 flex-wrap items-center gap-2">
            @if ($viewMode !== 'history')
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="step(-1)" class="{{ $iconBtn }}" aria-label="Previous {{ $viewMode === 'list' ? 'day' : $calendarMode }}"><i class="bx bx-chevron-left text-xl" aria-hidden="true"></i></button>
                    <p class="min-w-[9.5rem] text-center text-sm font-medium tabular-nums text-slate-900" aria-live="polite">{{ $rangeLabel }}</p>
                    <button type="button" wire:click="step(1)" class="{{ $iconBtn }}" aria-label="Next {{ $viewMode === 'list' ? 'day' : $calendarMode }}"><i class="bx bx-chevron-right text-xl" aria-hidden="true"></i></button>
                    <button type="button" wire:click="jumpToday" class="{{ $btnBase }} !px-3 text-[#2A57B4] hover:bg-[#f0f4fb]">Today</button>
                </div>
                @if ($viewMode === 'list')
                    <label>
                        <span class="sr-only">Go to a day</span>
                        <input type="date" wire:model.live="selectedDate" class="h-9 w-40 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                    </label>
                @endif
            @endif
            @if ($viewMode !== 'calendar')
                <div class="relative min-w-0 flex-1 lg:w-64 lg:flex-none">
                    <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, number or purpose"
                           aria-label="Search by name, student number or purpose" class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $field }}">
                </div>
            @endif
        </div>
    </div>

    {{-- ===================================== CALENDAR ===================================== --}}
    @if ($viewMode === 'calendar')
        @if ($editingHours)
            <p class="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-[#1E4090]" role="status">
                <i class="bx bx-info-circle mt-0.5 text-base" aria-hidden="true"></i>
                @if ($calendarMode === 'month')
                    Select the weekdays to change, then set their hours. Weekends are always closed.
                @else
                    Use the clock on a day to set its hours, or switch to Month to change several days at once.
                @endif
            </p>
        @endif

        @if ($calendarMode === 'week')
            {{-- Week: two sessions a day, the visits as cards --}}
            <section class="rounded-2xl border border-slate-200 bg-white max-md:overflow-x-auto" aria-label="Week of {{ $rangeLabel }}">
                <div class="grid min-w-[760px] grid-cols-[88px_repeat(7,minmax(0,1fr))]">
                    {{-- Day headers, with their hours --}}
                    <div class="border-b border-slate-200 p-2 text-xs text-slate-500"><span class="sr-only">Session</span></div>
                    @foreach ($this->weekDays as $d)
                        <div class="border-b border-l border-slate-200 p-2 text-center {{ $d['weekend'] ? 'bg-slate-50' : '' }}">
                            <p class="text-xs font-medium text-slate-500">{{ $d['carbon']->format('D') }}</p>
                            <button type="button" wire:click="openDay('{{ $d['date'] }}')"
                                    class="mx-auto mt-0.5 grid h-8 w-8 place-items-center rounded-full text-base font-semibold tabular-nums {{ $focus }}
                                        {{ $d['isToday'] ? 'bg-[#2A57B4] text-white' : 'text-slate-900 hover:bg-[#f0f4fb]' }}"
                                    aria-label="Open the list for {{ $d['carbon']->format('l, F j') }}">{{ $d['day'] }}</button>
                            @if ($d['weekend'])
                                <p class="mt-1 text-[11px] text-slate-500">Closed</p>
                            @else
                                <div class="mt-1 flex items-center justify-center gap-1">
                                    <p class="text-[11px] tabular-nums {{ $d['closed'] ? 'text-rose-700' : 'text-slate-500' }}">
                                        @if ($d['closed']) Closed
                                        @else {{ $d['morning']['open'] ? $d['morning']['left'] : '–' }} · {{ $d['afternoon']['open'] ? $d['afternoon']['left'] : '–' }} left
                                        @endif
                                    </p>
                                    <button type="button" wire:click="editDayHours('{{ $d['date'] }}')"
                                            class="grid h-6 w-6 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-[#2A57B4] [@media(pointer:coarse)]:h-9 [@media(pointer:coarse)]:w-9 {{ $focus }} {{ $editingHours || $d['custom'] ? 'text-[#2A57B4]' : '' }}"
                                            aria-label="Set office hours for {{ $d['carbon']->format('l, F j') }}" title="Set hours">
                                        <i class="bx bx-time-five text-sm" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Sessions --}}
                    @foreach ($sessions as $sKey => [$sName, $sTime])
                        <div class="border-b border-slate-200 p-2 last:border-b-0 {{ $loop->last ? 'border-b-0' : '' }}">
                            <p class="text-xs font-medium text-slate-700">{{ $sName }}</p>
                            <p class="text-[11px] tabular-nums text-slate-500">{{ $sTime }}</p>
                        </div>
                        @foreach ($this->weekDays as $i => $d)
                            @php
                                $cell = $d[$sKey];
                                $items = $cell['items'];
                                $side = $loop->index >= 4 ? 'left' : 'right';
                            @endphp
                            <div class="min-h-[132px] space-y-1.5 border-l border-slate-200 p-1.5 {{ $loop->parent->last ? '' : 'border-b' }}
                                {{ $d['weekend'] || ! $cell['open'] ? 'bg-[repeating-linear-gradient(135deg,#fafaf9_0_6px,#f5f4f2_6px_12px)]' : '' }}">
                                @if (! $d['weekend'] && ! $cell['open'])
                                    <p class="px-1 pt-1 text-[11px] text-slate-500">Session closed</p>
                                @endif
                                @foreach ($items->take(4) as $a)
                                    @php $isPop = $openId === $a->appointment_id; @endphp
                                    <div class="relative" wire:key="ev-{{ $a->appointment_id }}">
                                        <button type="button" wire:click="pop({{ $a->appointment_id }})" aria-expanded="{{ $isPop ? 'true' : 'false' }}"
                                                class="block w-full rounded-lg p-1.5 text-left ring-1 transition-shadow hover:shadow-md {{ $eventTint($a) }} {{ $isPop ? 'shadow-md ring-2 ring-[#2A57B4]' : '' }} {{ $focus }}">
                                            <span class="flex items-center gap-1.5">
                                                <span class="relative grid h-5 w-5 shrink-0 place-items-center overflow-hidden rounded-full bg-white text-[8px] font-semibold text-[#1E4090]" aria-hidden="true">
                                                    {{ $initials($a->user) }}
                                                    @if ($avatar($a->user))
                                                        <img src="{{ $avatar($a->user) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                                    @endif
                                                </span>
                                                <span class="truncate text-xs font-medium text-slate-900">{{ $a->user?->first_name }}</span>
                                                @if ($a->queue_number)
                                                    <span class="ml-auto shrink-0 text-[10px] tabular-nums text-slate-600">#{{ $a->queue_number }}</span>
                                                @endif
                                            </span>
                                            <span class="mt-0.5 block truncate text-[11px] text-slate-600">{{ $a->purpose ?: 'General visit' }}</span>
                                            <span class="sr-only">, {{ $statusFor($a)[0] }}</span>
                                        </button>
                                        @if ($isPop && $popped)
                                            @include('livewire.admin.appointments.partials.popover', ['p' => $popped, 'side' => $side])
                                        @endif
                                    </div>
                                @endforeach
                                @if ($items->count() > 4)
                                    <button type="button" wire:click="openDay('{{ $d['date'] }}')"
                                            class="block w-full rounded-md px-1.5 py-1 text-left text-[11px] font-medium text-[#2A57B4] hover:bg-[#f0f4fb] {{ $focus }}">
                                        +{{ $items->count() - 4 }} more
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </section>
        @else
            {{-- Month --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ $monthLabel }}">
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-medium text-slate-500" aria-hidden="true">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
                        <div class="py-2">{{ $wd }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($this->monthDays as $cell)
                        @php
                            $picked = in_array($cell['date'], $hoursDates, true);
                            $label = $cell['carbon']->format('l, F j') . ': '
                                . ($cell['closed'] ? 'office closed' : $cell['count'] . ' ' . str('appointment')->plural($cell['count']))
                                . ($cell['pending'] ? ', ' . $cell['pending'] . ' pending' : '')
                                . ($cell['flagged'] ? ', ' . $cell['flagged'] . ' flagged by AI' : '')
                                . ($editingHours ? ($picked ? ', selected' : ', select to set hours') : '');
                        @endphp
                        <button type="button" wire:key="m-{{ $cell['date'] }}"
                                wire:click="{{ $editingHours ? "toggleHoursDate('{$cell['date']}')" : "openDay('{$cell['date']}')" }}"
                                @disabled($editingHours && $cell['weekend'])
                                @if ($editingHours) aria-pressed="{{ $picked ? 'true' : 'false' }}" @endif
                                aria-label="{{ $label }}"
                                class="group relative flex min-h-[64px] flex-col border-b border-r border-slate-100 p-1.5 text-left transition-colors sm:min-h-[112px] sm:p-2 [&:nth-child(7n)]:border-r-0 {{ $focus }}
                                    {{ ! $cell['inMonth'] ? 'bg-slate-50/60' : ($cell['closed'] ? 'bg-[repeating-linear-gradient(135deg,#fafaf9_0_6px,#f5f4f2_6px_12px)]' : 'bg-white hover:bg-[#f0f4fb]') }}
                                    {{ $picked ? '!bg-[#e2e9f7] ring-2 ring-inset ring-[#2A57B4]' : '' }}
                                    {{ $editingHours && $cell['weekend'] ? 'cursor-not-allowed' : '' }}">
                            <span class="flex items-start justify-between gap-1">
                                @if ($editingHours && ! $cell['weekend'])
                                    <span class="grid h-4 w-4 place-items-center rounded border {{ $picked ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-300 bg-white' }}" aria-hidden="true">
                                        @if ($picked)<i class="bx bx-check text-xs"></i>@endif
                                    </span>
                                @elseif ($cell['count'] && $cell['inMonth'])
                                    <span class="hidden -space-x-1.5 sm:flex" aria-hidden="true">
                                        @foreach ($cell['people'] as $p)
                                            <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[9px] font-semibold text-[#1E4090] ring-2 ring-white">
                                                {{ $initials($p) }}
                                                @if ($avatar($p))
                                                    <img src="{{ $avatar($p) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                                @endif
                                            </span>
                                        @endforeach
                                        @if ($cell['count'] > 3)
                                            <span class="relative grid h-6 min-w-6 place-items-center rounded-full bg-slate-100 px-1 text-[9px] font-semibold text-slate-600 ring-2 ring-white">+{{ $cell['count'] - 3 }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span></span>
                                @endif
                                <span class="grid h-6 min-w-6 place-items-center rounded-full text-xs tabular-nums
                                    {{ $cell['isToday'] ? 'bg-[#2A57B4] font-semibold text-white' : ($cell['inMonth'] ? 'text-slate-700' : 'text-slate-400') }}">{{ $cell['day'] }}</span>
                            </span>

                            @if ($cell['inMonth'])
                                @if ($cell['closed'])
                                    <span class="mt-auto hidden text-[11px] text-slate-500 sm:block">Closed</span>
                                @else
                                    <span class="mt-auto hidden min-w-0 sm:block">
                                        @if ($cell['count'])
                                            <span class="block truncate text-xs font-medium text-slate-900">{{ $cell['names'] }}{{ $cell['count'] > 2 ? '…' : '' }}</span>
                                        @endif
                                        <span class="block text-[11px] tabular-nums text-slate-500">
                                            {{ $cell['morning']['open'] ? $cell['morning']['left'] : '–' }} · {{ $cell['afternoon']['open'] ? $cell['afternoon']['left'] : '–' }} slots left{{ $cell['custom'] ? ' · custom' : '' }}
                                        </span>
                                    </span>
                                    <span class="mt-1 flex flex-wrap gap-1" aria-hidden="true">
                                        @if ($cell['pending'])
                                            <span class="rounded-full bg-amber-50 px-1.5 text-[10px] font-medium tabular-nums text-amber-800 ring-1 ring-amber-200">{{ $cell['pending'] }}<span class="hidden sm:inline"> pending</span></span>
                                        @endif
                                        @if ($cell['flagged'])
                                            <span class="rounded-full bg-rose-50 px-1.5 text-[10px] font-medium tabular-nums text-rose-800 ring-1 ring-rose-200">{{ $cell['flagged'] }}<span class="hidden sm:inline"> flagged</span></span>
                                        @endif
                                        @if ($cell['count'] && ! $cell['pending'])
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#2A57B4] sm:hidden"></span>
                                        @endif
                                    </span>
                                @endif
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        <p class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-50 ring-1 ring-amber-200" aria-hidden="true"></span> Pending</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50 ring-1 ring-rose-200" aria-hidden="true"></span> Flagged by AI</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-50 ring-1 ring-sky-200" aria-hidden="true"></span> Approved</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-50 ring-1 ring-emerald-200" aria-hidden="true"></span> Attended</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[repeating-linear-gradient(135deg,#fafaf9_0_2px,#d4d1cb_2px_3px)]" aria-hidden="true"></span> Closed</span>
        </p>

        {{-- Several days picked for new hours --}}
        @if ($editingHours && $calendarMode === 'month' && count($hoursDates))
            <div class="pointer-events-none fixed inset-x-0 bottom-4 z-30 flex justify-center px-4">
                <div class="pointer-events-auto flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1.5 pl-3 shadow-lg" role="region" aria-label="Days selected for new hours">
                    <span class="mr-1 text-sm font-medium tabular-nums text-slate-800" role="status">{{ count($hoursDates) }} {{ str('day')->plural(count($hoursDates)) }} selected</span>
                    <button type="button" wire:click="openDialog('availability')" class="{{ $btnPrimary }} !min-h-9">Set hours</button>
                    <button type="button" wire:click="$set('hoursDates', [])" aria-label="Clear the selected days"
                            class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                        <i class="bx bx-x text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- ===================================== DAY LIST ===================================== --}}
    @if ($viewMode === 'list')
        {{-- The day's queue, front and centre --}}
        <section class="grid gap-4 md:grid-cols-2" aria-label="Queue for {{ $rangeLabel }}">
            @foreach ($sessions as $key => [$name, $time])
                @php $q = $this->queueBoard[$key]; @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-[15px] font-semibold text-slate-900">{{ $name }}</h2>
                            <p class="text-xs tabular-nums text-slate-500">{{ $time }} ·
                                {{ $q['open'] ? $q['remaining'] . ' of ' . $q['slots'] . ' slots left' : 'Closed this day' }}
                            </p>
                        </div>
                        <button type="button" wire:click="skipNowServing('{{ $key }}')" @disabled(! $q['next'])
                                class="{{ $btnPrimary }}">
                            Call next <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="mt-4 flex items-end gap-4">
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">Now serving</p>
                            <p class="text-4xl font-semibold leading-none tracking-[-0.02em] tabular-nums text-[#2A57B4]" aria-live="polite">
                                {{ $q['now_serving'] ? '#' . $q['now_serving'] : '–' }}
                            </p>
                            <p class="mt-1 truncate text-sm text-slate-700">{{ $q['serving']?->user?->full_name ?? 'Nobody called yet' }}</p>
                        </div>
                        <dl class="ml-auto grid grid-cols-2 gap-x-4 text-right text-xs">
                            <dt class="text-slate-500">Waiting</dt><dd class="font-semibold tabular-nums text-slate-900">{{ $q['waiting'] }}</dd>
                            <dt class="text-slate-500">Served</dt><dd class="font-semibold tabular-nums text-slate-900">{{ $q['done'] }}</dd>
                            <dt class="text-slate-500">Next</dt><dd class="font-semibold tabular-nums text-slate-900">{{ $q['next'] ? '#' . $q['next']->queue_number : '–' }}</dd>
                        </dl>
                    </div>
                    @if ($q['list']->isNotEmpty())
                        <ul class="mt-4 flex flex-wrap gap-1.5 border-t border-slate-100 pt-3" aria-label="{{ $name }} queue numbers">
                            @foreach ($q['list'] as $item)
                                @php
                                    $style = match (true) {
                                        $item->status === 'attended' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                        in_array($item->status, ['retracted', 'missed'], true) => 'border-slate-200 bg-slate-100 text-slate-500 line-through',
                                        $item->queue_number === $q['now_serving'] => 'border-[#2A57B4] bg-[#2A57B4] text-white',
                                        default => 'border-slate-200 bg-white text-slate-700 hover:border-[#2A57B4]',
                                    };
                                @endphp
                                <li>
                                    @if ($item->status === 'approved')
                                        <button type="button" wire:click="setNowServing('{{ $key }}', {{ $item->queue_number }})"
                                                class="grid h-8 min-w-8 place-items-center rounded-lg border px-1.5 text-xs font-medium tabular-nums [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:min-w-11 {{ $style }} {{ $focus }}"
                                                aria-label="Call number {{ $item->queue_number }}, {{ $item->user?->full_name }}" title="{{ $item->user?->full_name }}">
                                            {{ $item->queue_number }}
                                        </button>
                                    @else
                                        <span class="grid h-8 min-w-8 place-items-center rounded-lg border px-1.5 text-xs font-medium tabular-nums {{ $style }}" title="{{ $item->user?->full_name }} ({{ $item->status }})">
                                            {{ $item->queue_number }}<span class="sr-only">, {{ $item->status }}</span>
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Two phases: review the requests, then serve the approved ones --}}
        <nav aria-label="Phases">
            <ol class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                @foreach (AppointmentsBoard::PHASES as $key => $label)
                    @php $on = $phase === $key; $n = $this->phaseCounts[$key]; @endphp
                    <li>
                        <button type="button" wire:click="setPhase('{{ $key }}')" @if ($on) aria-current="step" @endif
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left transition-colors [@media(pointer:coarse)]:min-h-11 {{ $focus }}
                                    {{ $on ? 'bg-white shadow-sm' : 'hover:bg-white/60' }}">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-semibold {{ $on ? 'bg-[#2A57B4] text-white' : 'bg-white text-slate-600' }}">{{ $loop->iteration }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium {{ $on ? 'text-slate-900' : 'text-slate-700' }}">{{ $label }}</span>
                                <span class="block truncate text-xs text-slate-500">
                                    @if ($key === 'review')
                                        {{ $n }} waiting{{ $this->phaseCounts['flagged'] ? ' · ' . $this->phaseCounts['flagged'] . ' flagged by AI' : '' }}
                                    @else
                                        {{ $n }} in the queue · {{ $this->phaseCounts['attended'] }} attended
                                    @endif
                                </span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        @if ($capacityWarning)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4" role="alert">
                <p class="text-sm font-semibold text-amber-900">This doesn't fit the remaining slots or the queue order</p>
                <ul class="mt-1.5 space-y-1 text-sm text-amber-900">
                    @foreach ($capacityWarning as $info)
                        <li>
                            {{ ucfirst($info['label']) }}:
                            @if (! $info['open']) the session is closed, but {{ $info['needed'] }} selected.
                            @elseif ($info['fcfs_violation']) earlier requests for this session aren't in your selection, so approving now skips the line.
                            @else {{ $info['needed'] }} selected, only {{ $info['remaining'] }} {{ str('slot')->plural($info['remaining']) }} left.
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" wire:click="approve(true)" class="{{ $btnBase }} bg-amber-600 text-white hover:bg-amber-700">Approve anyway</button>
                    <button type="button" wire:click="dismissCapacityWarning" class="{{ $btnSecondary }}">Cancel</button>
                </div>
            </div>
        @endif

        <div class="cq space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-500">
                    {{ $phase === 'review' ? 'Requests with the AI\'s concerns first, then in the order they came in.' : 'In queue order. Call a number, then mark who attended.' }}
                </p>
                <div class="flex items-center gap-2">
                    @if ($phase === 'review' && $this->dayRows->isNotEmpty())
                        <button type="button" wire:click="selectAllPending" class="{{ $btnBase }} !px-3 text-[#2A57B4] hover:bg-[#f0f4fb]">Select all</button>
                    @endif
                    @if ($phase === 'queue' && $this->dayRows->where('status', 'approved')->isNotEmpty())
                        <button type="button" wire:click="emergencyRescheduleDay" class="{{ $btnBase }} !px-3 text-rose-700 hover:bg-rose-50">
                            <i class="bx bx-error-circle text-base" aria-hidden="true"></i> Move the whole day
                        </button>
                    @endif
                </div>
            </div>
            @include('livewire.admin.appointments.partials.rows', ['rows' => $this->dayRows, 'mode' => $phase])
        </div>
    @endif

    {{-- ===================================== HISTORY ===================================== --}}
    @if ($viewMode === 'history')
        <div class="flex flex-wrap items-end gap-3">
            <label class="block">
                <span class="block text-sm font-medium text-slate-700">Date</span>
                <input type="date" wire:model.live="filterDate" class="mt-1 h-9 w-44 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
            </label>
            <label class="block">
                <span class="block text-sm font-medium text-slate-700">Status</span>
                <select wire:model.live="filterStatus" class="mt-1 h-9 w-44 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'attended' => 'Attended', 'missed' => 'Missed', 'rejected' => 'Rejected', 'retracted' => 'Withdrawn'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </label>
            @if ($filterDate || $filterStatus || $search)
                <button type="button" wire:click="clearHistoryFilters" class="{{ $btnBase }} text-[#2A57B4] hover:bg-[#f0f4fb]">Clear filters</button>
            @endif
        </div>

        @php $hist = $this->historyRows; @endphp
        <div class="cq space-y-3">
            @include('livewire.admin.appointments.partials.rows', ['rows' => $hist->getCollection(), 'mode' => 'history'])
            @if ($hist->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-500">
                    <span>Showing {{ $hist->firstItem() }}–{{ $hist->lastItem() }} of {{ $hist->total() }} entries</span>
                    <div>{{ $hist->links() }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Bulk bar (review phase) --}}
    @if (count($selectedIds) && $viewMode === 'list')
        <div class="pointer-events-none fixed inset-x-0 bottom-4 z-30 flex justify-center px-4">
            <div class="pointer-events-auto flex max-w-full items-center gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1.5 pl-3 shadow-lg" role="region" aria-label="Selected appointments">
                <span class="mr-1 shrink-0 text-sm font-medium tabular-nums text-slate-800" role="status">{{ count($selectedIds) }} selected</span>
                @php $bar = 'inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-3 text-sm font-medium hover:bg-slate-100 [@media(pointer:coarse)]:min-h-11 ' . $focus; @endphp
                @if ($this->selectedPendingCount)
                    <button type="button" wire:click="openDialog('approve')" class="{{ $bar }} text-emerald-700">Approve</button>
                    <button type="button" wire:click="openDialog('reject')" class="{{ $bar }} text-rose-700">Reject</button>
                @endif
                <button type="button" wire:click="openDialog('reschedule')" class="{{ $bar }} text-slate-700">Reschedule</button>
                <button type="button" wire:click="bulkRerunReview" class="{{ $bar }} text-slate-700">
                    <i class="bx bx-bot text-base text-[#2A57B4]" aria-hidden="true"></i> AI check
                </button>
                <button type="button" wire:click="clearSelection" aria-label="Clear selection"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                    <i class="bx bx-x text-xl" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Dialogs --}}
    @if ($dialog)
        @php
            $oneAppt = $dialogScope === 'one' && $openId ? \App\Models\Appointment::with('user')->find($openId) : null;
            $n = $dialogScope === 'one' ? 1 : ($dialog === 'reschedule' ? count($selectedIds) : $this->selectedPendingCount);
            $who = $oneAppt ? ($oneAppt->user?->full_name ?? 'this student') : $n . ' ' . str('appointment')->plural($n);
            $reasons = $dialog === 'reschedule' ? AppointmentsBoard::RESCHEDULE_REASONS : AppointmentsBoard::REJECT_REASONS;
            $title = match ($dialog) {
                'availability' => 'Office hours',
                'approve' => 'Approve ' . ($oneAppt ? 'appointment' : $who),
                'reject' => 'Reject ' . ($oneAppt ? 'appointment' : $who),
                default => 'Reschedule ' . ($oneAppt ? 'appointment' : $who),
            };
        @endphp
        {{-- Moved to <body>, so the dimmed backdrop covers the whole window, top bar included. --}}
        <template x-teleport="body">
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="closeDialog" wire:click.self="closeDialog"
             role="dialog" aria-modal="true" aria-labelledby="appt-dialog-title" aria-describedby="appt-dialog-lead">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                 x-data x-init="$nextTick(() => $el.querySelector('select, input, textarea, button')?.focus())">
                <h2 id="appt-dialog-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">{{ $title }}</h2>
                <p id="appt-dialog-lead" class="mt-1 text-sm text-slate-500">
                    @if ($dialog === 'availability')
                        For {{ collect($hoursDates)->sort()->map(fn ($d) => \Carbon\Carbon::parse($d)->format('D, M j'))->implode(', ') }}.
                        Students can only book open sessions, up to the number of slots.
                    @elseif ($dialog === 'approve')
                        {{ $oneAppt ? $who . ' gets the next queue number for their session' : 'Queue numbers are given in order of submission' }}, and is notified. This cannot be undone.
                    @elseif ($dialog === 'reject')
                        The reason is sent to {{ $oneAppt ? $who : 'every pending appointment in your selection' }}.
                    @else
                        Approved visits go back to pending and lose their queue number. {{ $oneAppt ? ucfirst($who) . ' is' : 'Everyone is' }} notified.
                    @endif
                </p>

                @if ($dialog === 'availability')
                    <div class="mt-4 space-y-3">
                        @foreach (['morning' => 'Morning · 8:00 – 12:00', 'afternoon' => 'Afternoon · 1:00 – 5:00'] as $s => $label)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <label class="flex items-center justify-between gap-3 text-sm font-medium text-slate-800">
                                    {{ $label }}
                                    <span class="inline-flex items-center gap-2 text-sm font-normal text-slate-600">
                                        Open
                                        <input type="checkbox" wire:model.live="{{ $s }}Open" class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                                    </span>
                                </label>
                                @if (${$s . 'Open'})
                                    <label class="mt-2 flex items-center justify-between gap-3 text-sm text-slate-600">
                                        Slots
                                        <input type="number" wire:model="{{ $s }}Slots" min="0" max="500" class="h-9 w-24 py-0 text-right tabular-nums [@media(pointer:coarse)]:h-11 {{ $field }}">
                                    </label>
                                    @error($s . 'Slots') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('hoursDates') <p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @endif

                @if ($dialog === 'reschedule')
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="block text-sm font-medium text-slate-800">New day <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <input type="date" wire:model="rescheduleDate" min="{{ now()->toDateString() }}" required class="mt-1 h-9 w-full py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                        </label>
                        <label class="block">
                            <span class="block text-sm font-medium text-slate-800">Session <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <select wire:model="rescheduleSession" required class="mt-1 h-9 w-full py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                                <option value="">Choose…</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </label>
                    </div>
                    @error('rescheduleDate') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                    @error('rescheduleSession') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @endif

                @if (in_array($dialog, ['reject', 'reschedule'], true))
                    <label for="appt-reason" class="mt-4 block text-sm font-medium text-slate-800">Reason <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select id="appt-reason" wire:model.live="reasonOption" class="mt-1 h-9 w-full py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                        <option value="">Choose a reason…</option>
                        @foreach ($reasons as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                        <option value="Custom">Write my own</option>
                    </select>
                    @if ($reasonOption === 'Custom')
                        <textarea wire:model="reasonText" rows="3" maxlength="500" aria-label="Your reason"
                                  class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm"></textarea>
                        @if ($dialog === 'reject' && $oneAppt && $reasonText !== '' && $reasonText === ($oneAppt->ai_findings['summary'] ?? null))
                            <p class="mt-1 text-xs text-slate-500">Filled in from the AI summary. Edit it before sending.</p>
                        @endif
                    @endif
                    @error('reasonText') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @endif

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDialog" class="{{ $btnSecondary }}">Cancel</button>
                    @php $action = $dialog === 'availability' ? 'saveHours' : $dialog; @endphp
                    <button type="button" wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}"
                            class="{{ $dialog === 'reject' ? $btnBase . ' bg-rose-600 text-white shadow-sm hover:bg-rose-700' : $btnPrimary }}">
                        {{ match ($dialog) { 'availability' => 'Save hours', 'approve' => 'Approve', 'reject' => 'Reject', default => 'Reschedule' } }}{{ $dialogScope === 'one' || $dialog === 'availability' ? '' : ' ' . $n }}
                    </button>
                </div>
            </div>
        </div>
        </template>
    @endif
</div>
