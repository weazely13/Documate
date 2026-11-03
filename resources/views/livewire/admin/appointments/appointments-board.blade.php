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

<div class="mx-auto max-w-[1440px] space-y-5 pb-32" x-data @if ($polling) wire:poll.5s @endif>

    {{-- Header --}}
    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Appointments</h1>
            <p class="mt-0.5 hidden text-sm text-slate-500 sm:block">See who is coming, set the office's hours, review requests and run the queue.</p>
        </div>
        <button type="button" wire:click="toggleEditingHours" aria-pressed="{{ $editingHours ? 'true' : 'false' }}"
                class="{{ $editingHours ? $btnPrimary : $btnSecondary }} shrink-0 max-sm:!px-3">
            <i class="bx bx-time text-base {{ $editingHours ? '' : 'text-slate-500' }}" aria-hidden="true"></i>
            <span class="sm:hidden">{{ $editingHours ? 'Done' : 'Hours' }}</span><span class="hidden sm:inline">{{ $editingHours ? 'Done setting hours' : 'Set office hours' }}</span>
        </button>
    </div>

    {{-- Toolbar: one line at every width. On phones the labels drop to icons, search opens over the bar,
         and the date and status fields become icons with the native picker laid over them. --}}
    @php
        $segM = fn ($on) => 'inline-flex min-h-8 items-center gap-1.5 rounded-md px-2 text-sm transition-colors sm:px-3 [@media(pointer:coarse)]:min-h-10 ' . $focus . ' '
            . ($on ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900');
        $pickWrap = 'relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 focus-within:ring-[3px] focus-within:ring-[#2A57B4]/30 sm:block sm:h-auto sm:w-auto sm:border-0 sm:bg-transparent sm:focus-within:ring-0 max-sm:[@media(pointer:coarse)]:h-11 max-sm:[@media(pointer:coarse)]:w-11';
        $pickField = 'absolute inset-0 h-full w-full cursor-pointer opacity-0 sm:static sm:h-9 sm:py-0 sm:opacity-100 sm:[@media(pointer:coarse)]:h-11 ' . $field;
    @endphp
    <div class="relative flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white p-2 sm:gap-2 sm:p-3">
        <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1" role="group" aria-label="View">
            @foreach (['calendar' => ['bx-calendar', 'Calendar'], 'list' => ['bx-list-ol', 'Day list'], 'history' => ['bx-history', 'All']] as $key => [$icon, $label])
                <button type="button" wire:click="setViewMode('{{ $key }}')" aria-pressed="{{ $viewMode === $key ? 'true' : 'false' }}" class="{{ $segM($viewMode === $key) }}" title="{{ $label }}">
                    <i class="bx {{ $icon }} text-base" aria-hidden="true"></i><span class="max-md:sr-only">{{ $label }}</span>
                </button>
            @endforeach
        </div>

        @if ($viewMode === 'calendar')
            <div class="ml-auto inline-flex shrink-0 rounded-lg bg-slate-100 p-1" role="group" aria-label="Calendar range">
                @foreach (['week' => 'Week', 'month' => 'Month'] as $key => $label)
                    <button type="button" wire:click="setCalendarMode('{{ $key }}')" aria-pressed="{{ $calendarMode === $key ? 'true' : 'false' }}" class="{{ $segM($calendarMode === $key) }}">{{ $label }}</button>
                @endforeach
            </div>
        @endif

        @if ($viewMode === 'list')
            <div class="flex shrink-0 items-center gap-1">
                <button type="button" wire:click="step(-1)" class="{{ $iconBtn }}" aria-label="Previous day"><i class="bx bx-chevron-left text-xl" aria-hidden="true"></i></button>
                <label class="{{ str_replace(['grid h-9 w-9', 'max-sm:[@media(pointer:coarse)]:w-11'], ['inline-flex h-9 items-center gap-1 px-2 max-sm:min-w-9', ''], $pickWrap) }}" title="Go to a day">
                    <i class="bx bx-calendar text-lg sm:!hidden" aria-hidden="true"></i>
                    <span class="text-sm font-medium tabular-nums text-slate-800 sm:hidden" aria-hidden="true">{{ $day->isToday() ? 'Today' : $day->format('M j') }}</span>
                    <span class="sr-only">Go to a day</span>
                    <input type="date" wire:model.live="selectedDate" class="{{ $pickField }} sm:w-40" @click="try { $el.showPicker() } catch (e) {}">
                </label>
                <button type="button" wire:click="step(1)" class="{{ $iconBtn }}" aria-label="Next day"><i class="bx bx-chevron-right text-xl" aria-hidden="true"></i></button>
                @unless ($day->isToday())
                    <button type="button" wire:click="jumpToday" class="{{ $btnBase }} !px-2.5 text-[#2A57B4] hover:bg-[#f0f4fb] max-sm:hidden">Today</button>
                @endunless
            </div>
            <p class="hidden min-w-0 truncate text-sm font-medium tabular-nums text-slate-900 xl:block" aria-live="polite">{{ $rangeLabel }}</p>
        @endif

        @if ($viewMode !== 'calendar')
            <x-dm.toolbar-search class="ml-auto sm:max-w-xs" placeholder="Search name, number or purpose" label="Search by name, student number or purpose" />
        @endif

        @if ($viewMode === 'history')
            <label class="{{ $pickWrap }}" title="Filter by date">
                <i class="bx bx-calendar text-lg sm:!hidden" aria-hidden="true"></i>
                @if ($filterDate)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white sm:hidden" aria-hidden="true"></span>@endif
                <span class="sr-only">Filter by date</span>
                <input type="date" wire:model.live="filterDate" class="{{ $pickField }} sm:w-40" @click="try { $el.showPicker() } catch (e) {}">
            </label>
            <label class="{{ $pickWrap }}" title="Filter by status">
                <i class="bx bx-filter-alt text-lg sm:!hidden" aria-hidden="true"></i>
                @if ($filterStatus)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white sm:hidden" aria-hidden="true"></span>@endif
                <span class="sr-only">Filter by status</span>
                <select wire:model.live="filterStatus" class="{{ $pickField }} sm:w-40">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'attended' => 'Attended', 'missed' => 'Missed', 'rejected' => 'Rejected', 'retracted' => 'Withdrawn'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </label>
            @if ($filterDate || $filterStatus || $search)
                <button type="button" wire:click="clearHistoryFilters" class="{{ $iconBtn }} shrink-0 text-[#2A57B4]" aria-label="Clear filters" title="Clear filters">
                    <i class="bx bx-reset text-lg" aria-hidden="true"></i>
                </button>
            @endif
        @endif
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

        @php
            $inMonth = collect($this->monthDays)->where('inMonth', true);
            $weekCount = collect($this->weekDays)->sum('count');
            $monthStart = \Carbon\Carbon::parse($month . '-01');
            $isCurrent = $calendarMode === 'month'
                ? $month === now()->format('Y-m')
                : $weekStart->isSameDay(now()->startOfWeek(\Carbon\Carbon::SUNDAY));
            [$calTitle, $calSub] = $calendarMode === 'month'
                ? [$monthStart->format('F'), $monthStart->format('Y') . ' · ' . $inMonth->sum('count') . ' ' . str('booking')->plural($inMonth->sum('count')) . ($inMonth->sum('pending') ? ' · ' . $inMonth->sum('pending') . ' to approve' : '')]
                : [$rangeLabel, $weekCount . ' ' . str('booking')->plural($weekCount) . ' this week'];
        @endphp
        {{-- Not clipped: an event's popover may spill past the calendar's edges --}}
        <x-dm.wall-calendar :title="$calTitle" :subtitle="$calSub" prev="step(-1)" next="step(1)" :clip="false"
                            :today="$isCurrent ? null : 'jumpToday'" :unit="$calendarMode" :weekdays="$calendarMode === 'month'">
        @if ($calendarMode === 'week')
            {{-- Week: two sessions a day, the visits as cards --}}
            <div class="max-md:overflow-x-auto" role="group" aria-label="Week of {{ $rangeLabel }}">
                <div class="grid min-w-[760px] grid-cols-[88px_repeat(7,minmax(0,1fr))]" wire:loading.class="opacity-60" wire:target="step, jumpToday">
                    {{-- Day headers, in the calendar's weekday band, with their hours --}}
                    <div class="border-b border-blue-100 bg-blue-50 p-2 text-xs text-slate-500"><span class="sr-only">Session</span></div>
                    @foreach ($this->weekDays as $d)
                        <div class="border-b border-l border-blue-100 bg-blue-50 p-2 text-center">
                            <p class="text-[11px] font-semibold uppercase tracking-wide {{ $d['carbon']->dayOfWeek === 0 ? 'text-rose-600' : 'text-[#2A57B4]' }}">{{ $d['carbon']->format('D') }}</p>
                            <button type="button" wire:click="openDay('{{ $d['date'] }}')"
                                    class="mx-auto mt-0.5 grid h-8 w-8 place-items-center rounded-full text-base font-semibold tabular-nums {{ $focus }}
                                        {{ $d['isToday'] ? 'bg-[#2A57B4] text-white shadow-sm' : ($d['closed'] ? 'text-rose-500 hover:bg-white' : 'text-slate-900 hover:bg-white') }}"
                                    aria-label="Open the list for {{ $d['carbon']->format('l, F j') }}">{{ $d['day'] }}</button>
                            @if ($d['weekend'])
                                <p class="mt-1 text-[11px] font-medium text-rose-400">Closed</p>
                            @else
                                <div class="mt-1 flex items-center justify-center gap-1">
                                    <p class="text-[11px] tabular-nums {{ $d['closed'] ? 'font-medium text-rose-400' : 'text-slate-500' }}">
                                        @if ($d['closed']) Closed
                                        @else {{ $d['morning']['open'] ? $d['morning']['left'] : '–' }} · {{ $d['afternoon']['open'] ? $d['afternoon']['left'] : '–' }} left
                                        @endif
                                    </p>
                                    <button type="button" wire:click="editDayHours('{{ $d['date'] }}')"
                                            class="grid h-6 w-6 place-items-center rounded-md text-slate-400 hover:bg-white hover:text-[#2A57B4] [@media(pointer:coarse)]:h-9 [@media(pointer:coarse)]:w-9 {{ $focus }} {{ $editingHours || $d['custom'] ? 'text-[#2A57B4]' : '' }}"
                                            aria-label="Set office hours for {{ $d['carbon']->format('l, F j') }}" title="Set hours">
                                        <i class="bx bx-time-five text-sm" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Sessions, labelled in their dashboard tints --}}
                    @foreach ($sessions as $sKey => [$sName, $sTime])
                        <div class="p-2 {{ $loop->last ? '' : 'border-b border-slate-100' }} {{ $sKey === 'morning' ? 'bg-[#dcecfa]/50' : 'bg-[#e6e0f5]/50' }}">
                            <p class="flex items-center gap-1 text-xs font-semibold text-slate-800"><i class="bx {{ $sKey === 'morning' ? 'bx-sun' : 'bx-cloud' }} text-sm text-slate-500" aria-hidden="true"></i>{{ $sName }}</p>
                            <p class="text-[11px] tabular-nums text-slate-500">{{ $sTime }}</p>
                        </div>
                        @foreach ($this->weekDays as $i => $d)
                            @php
                                $cell = $d[$sKey];
                                $items = $cell['items'];
                                $side = $loop->index >= 4 ? 'left' : 'right';
                            @endphp
                            <div class="min-h-[132px] space-y-1.5 border-l border-slate-100 p-1.5 {{ $loop->parent->last ? '' : 'border-b' }}
                                {{ $d['weekend'] || ! $cell['open'] ? 'bg-rose-50/40' : ($d['isToday'] ? 'bg-blue-50/40' : '') }}">
                                @if (! $d['weekend'] && ! $cell['open'])
                                    <p class="px-1 pt-1 text-[11px] font-medium text-rose-400">Session closed</p>
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
            </div>
        @else
            {{-- Month: the dashboard's ruled day boxes. No @continue in here (see the dashboard); each box is keyed by date. --}}
            <div class="grid grid-cols-7 [&>*:nth-child(7n)]:border-r-0" wire:loading.class="opacity-60" wire:target="step, jumpToday">
                @foreach ($this->monthDays as $cell)
                    @if (! $cell['inMonth'])
                        <span wire:key="m-{{ $cell['date'] }}" class="h-[4.5rem] border-b border-r border-slate-100 bg-slate-50/70 p-1.5 text-xs text-slate-300 sm:h-24" aria-hidden="true">{{ $cell['day'] }}</span>
                    @else
                        @php
                            $picked = in_array($cell['date'], $hoursDates, true);
                            $label = $cell['carbon']->format('l, F j') . ': '
                                . ($cell['closed'] ? 'office closed' : $cell['count'] . ' ' . str('appointment')->plural($cell['count']))
                                . ($cell['pending'] ? ', ' . $cell['pending'] . ' pending' : '')
                                . ($cell['flagged'] ? ', ' . $cell['flagged'] . ' flagged by AI' : '')
                                . ($editingHours ? ($picked ? ', selected' : ', select to set hours') : '');
                            $box = match (true) {
                                $picked => 'bg-blue-50 ring-2 ring-inset ring-[#2A57B4]',
                                $cell['closed'] => 'bg-rose-50/40',
                                $cell['count'] > 5 => 'bg-blue-100/70 hover:bg-blue-100',
                                $cell['count'] > 2 => 'bg-blue-50 hover:bg-blue-100/60',
                                default => 'hover:bg-slate-50',
                            };
                        @endphp
                        <button type="button" wire:key="m-{{ $cell['date'] }}"
                                wire:click="{{ $editingHours ? "toggleHoursDate('{$cell['date']}')" : "openDay('{$cell['date']}')" }}"
                                @disabled($editingHours && $cell['weekend'])
                                @if ($editingHours) aria-pressed="{{ $picked ? 'true' : 'false' }}" @endif
                                @if ($cell['isToday']) aria-current="date" @endif
                                aria-label="{{ $label }}"
                                class="relative flex h-[4.5rem] min-w-0 flex-col justify-between border-b border-r border-slate-100 p-1.5 text-left transition focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#2A57B4] sm:h-24 {{ $box }} {{ $editingHours && $cell['weekend'] ? 'cursor-not-allowed' : '' }}">
                            <span class="flex items-start justify-between gap-1">
                                <span @class([
                                    'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                    'bg-[#2A57B4] text-white shadow-sm' => $cell['isToday'],
                                    'text-rose-500' => ! $cell['isToday'] && $cell['closed'],
                                    'text-slate-800' => ! $cell['isToday'] && ! $cell['closed'],
                                ])>{{ $cell['day'] }}</span>
                                @if ($editingHours && ! $cell['weekend'])
                                    <span class="mt-0.5 grid h-4 w-4 place-items-center rounded border {{ $picked ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-300 bg-white' }}" aria-hidden="true">
                                        @if ($picked)<i class="bx bx-check text-xs"></i>@endif
                                    </span>
                                @else
                                    <span class="mt-1 flex gap-0.5" aria-hidden="true">
                                        @if ($cell['flagged'])<span class="h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>@endif
                                        @if ($cell['pending'])<span class="h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white"></span>@endif
                                    </span>
                                @endif
                            </span>
                            @if ($cell['closed'])
                                <span class="text-[10px] font-medium text-rose-400">Closed</span>
                            @else
                                <span class="min-w-0">
                                    @if ($cell['count'])
                                        <span class="mb-1 flex -space-x-1.5" aria-hidden="true" title="{{ $cell['names'] }}{{ $cell['count'] > 2 ? '…' : '' }}">
                                            @foreach ($cell['people'] as $pi => $person)
                                                <x-dm.avatar :user="$person" class="h-5 w-5 text-[8px] ring-2 ring-white sm:h-6 sm:w-6 sm:text-[9px] {{ $pi >= 2 ? 'max-sm:hidden' : '' }}" />
                                            @endforeach
                                            @if ($cell['count'] > 3)
                                                <span class="relative hidden h-6 min-w-6 place-items-center rounded-full bg-slate-100 px-1 text-[9px] font-semibold text-slate-600 ring-2 ring-white sm:grid">+{{ $cell['count'] - 3 }}</span>
                                            @endif
                                        </span>
                                    @endif
                                    <span class="flex min-w-0 items-center gap-1">
                                        @if ($cell['count'])
                                            <span class="truncate rounded bg-[#2A57B4]/10 px-1 text-[10px] font-semibold tabular-nums text-[#1E4090]">{{ $cell['count'] }}<span class="hidden md:inline"> booked</span></span>
                                        @endif
                                        @if ($cell['count'] || $cell['custom'])
                                            <span class="hidden truncate text-[10px] tabular-nums text-slate-500 xl:inline">{{ $cell['morning']['open'] ? $cell['morning']['left'] : '–' }} · {{ $cell['afternoon']['open'] ? $cell['afternoon']['left'] : '–' }} left{{ $cell['custom'] ? ' · custom' : '' }}</span>
                                        @endif
                                    </span>
                                </span>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>
        @endif
        </x-dm.wall-calendar>

        <p class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
            @if ($calendarMode === 'month')
                <span class="inline-flex items-center gap-1.5"><span class="rounded bg-[#2A57B4]/10 px-1 text-[10px] font-semibold text-[#1E4090]" aria-hidden="true">3</span> Booked</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span> Pending</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500" aria-hidden="true"></span> Flagged by AI</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50 ring-1 ring-rose-200" aria-hidden="true"></span> Closed</span>
                <span class="hidden sm:inline">A deeper blue day is a busier day.</span>
            @else
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-50 ring-1 ring-amber-200" aria-hidden="true"></span> Pending</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50 ring-1 ring-rose-200" aria-hidden="true"></span> Flagged by AI</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sky-50 ring-1 ring-sky-200" aria-hidden="true"></span> Approved</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-50 ring-1 ring-emerald-200" aria-hidden="true"></span> Attended</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50/60 ring-1 ring-rose-100" aria-hidden="true"></span> Closed</span>
            @endif
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
        <div class="cq">
        <section class="grid grid-cols-2 gap-2.5 cq-sm:gap-3" aria-label="Queue for {{ $rangeLabel }}">
            @foreach ($sessions as $key => [$name, $time])
                @php
                    $q = $this->queueBoard[$key];
                    $qTotal = $q['list']->whereIn('status', ['approved', 'attended', 'missed'])->count();
                    $qStatus = ! $q['open'] ? 'Closed' : ($q['now_serving'] ? 'Serving #' . $q['now_serving'] : 'Not started');
                @endphp
                <x-dm.session-card :session="$key" :label="$name" :time="$time" :value="$q['done']" :total="$qTotal"
                                   :status="$qStatus" :live="(bool) $q['now_serving']">
                    <div class="mt-3 min-w-0" aria-live="polite">
                        <p class="text-[11px] text-slate-600">Now serving</p>
                        <p class="truncate text-sm font-semibold cq-sm:text-[15px]">{{ $q['serving']?->user?->full_name ?? 'Nobody called yet' }}</p>
                        <p class="mt-0.5 truncate text-[11px] tabular-nums text-slate-600">
                            Next {{ $q['next'] ? '#' . $q['next']->queue_number : '–' }} · {{ $q['waiting'] }} waiting<span class="hidden cq-sm:inline"> · {{ $q['open'] ? $q['remaining'] . ' of ' . $q['slots'] . ' slots left' : 'closed this day' }}</span>
                        </p>
                    </div>
                    <button type="button" wire:click="skipNowServing('{{ $key }}')" @disabled(! $q['next'])
                            class="{{ $btnPrimary }} mt-3 w-full !px-3 cq-sm:w-auto cq-sm:self-start">
                        <span class="cq-sm:hidden">Next</span><span class="hidden cq-sm:inline">Call next</span>
                        <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                    </button>
                    @if ($q['list']->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-1 border-t border-slate-900/10 pt-2.5 cq-sm:gap-1.5" aria-label="{{ $name }} queue numbers">
                            @foreach ($q['list'] as $item)
                                @php
                                    $style = match (true) {
                                        $item->status === 'attended' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                        in_array($item->status, ['retracted', 'missed'], true) => 'border-transparent bg-slate-900/5 text-slate-500 line-through',
                                        $item->queue_number === $q['now_serving'] => 'border-[#2A57B4] bg-[#2A57B4] text-white',
                                        default => 'border-transparent bg-white/75 text-slate-800 hover:border-[#2A57B4] hover:bg-white',
                                    };
                                    $chip = 'grid h-7 min-w-7 place-items-center rounded-md border px-1 text-[11px] font-medium tabular-nums cq-sm:h-8 cq-sm:min-w-8 cq-sm:rounded-lg cq-sm:text-xs [@media(pointer:coarse)]:h-10 [@media(pointer:coarse)]:min-w-10';
                                @endphp
                                <li>
                                    @if ($item->status === 'approved')
                                        <button type="button" wire:click="setNowServing('{{ $key }}', {{ $item->queue_number }})"
                                                class="{{ $chip }} {{ $style }} {{ $focus }}"
                                                aria-label="Call number {{ $item->queue_number }}, {{ $item->user?->full_name }}" title="{{ $item->user?->full_name }}">
                                            {{ $item->queue_number }}
                                        </button>
                                    @else
                                        <span class="{{ $chip }} {{ $style }}" title="{{ $item->user?->full_name }} ({{ $item->status }})">
                                            {{ $item->queue_number }}<span class="sr-only">, {{ $item->status }}</span>
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dm.session-card>
            @endforeach
        </section>
        </div>

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
