@use('Illuminate\Support\Str')
@php
    /*
     * Reports, laid out like a source report: a summary strip (headline, four figures, details),
     * a list to pick a form or organization beside its trend over time, then the records, which
     * can be ticked, trimmed to the columns needed and downloaded.
     */
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $pct = fn ($n, $d) => $d > 0 ? (int) round($n / $d * 100) : 0;
    $fmt = fn ($n) => number_format((int) $n);

    // [tab label, icon, icon colour when not selected] — module colours from the Audit Trail
    $reports = [
        'forms' => ['Transaction forms', 'bx-file', 'text-indigo-600'],
        'organizations' => ['Organizations', 'bx-buildings', 'text-cyan-600'],
        'clearance' => ['Clearance', 'bx-clipboard', 'text-teal-600'],
    ];
    // The period dropdown, grouped the way YouTube Studio groups it: rolling ranges, years,
    // recent months, then each dated semester ("sem-<id>"), then Custom.
    $periodGroups = [
        ['7d' => 'Last 7 days', '28d' => 'Last 28 days', '90d' => 'Last 90 days', '180d' => 'Last 180 days', '365d' => 'Last 365 days', 'all' => 'Lifetime'],
        ['year-' . now()->year => (string) now()->year, 'year-' . (now()->year - 1) => (string) (now()->year - 1)],
        collect(range(0, 2))->mapWithKeys(fn ($i) => [
            'month-' . now()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m') => now()->startOfMonth()->subMonthsNoOverflow($i)->format(now()->startOfMonth()->subMonthsNoOverflow($i)->year === now()->year ? 'F' : 'F Y'),
        ])->all(),
        $periodSemesters->take(4)->mapWithKeys(fn ($s) => ['sem-' . $s->id => $s->school_year . ' ' . $s->semester_label . ' Sem'])->all(),
    ];
    $bySemester = str_starts_with($datePreset, 'sem-');
    $rangeLabel = $from->format('M j, Y') . ' – ' . $to->format('M j, Y');
    $rangeShort = ($from->year === $to->year ? $from->format('M j') : $from->format('M j, Y')) . ' – ' . $to->format('M j, Y');
    $prevLabel = $prev ? $prev[0]->format('M j') . ' – ' . $prev[1]->format('M j, Y') : null;
    $semesterDates = $semesters->firstWhere('id', $semesterId)?->dateLabel();

    // A "vs before" change on a coloured tile: arrow, size, and in words whether it is good news.
    $delta = function ($now, $before, bool $lowerIsBetter = false) {
        if ($before === null) {
            return null;
        }
        if ((int) $before === 0) {
            return (int) $now === 0 ? ['text' => 'No change', 'icon' => 'bx-minus'] : ['text' => 'New', 'icon' => 'bx-up-arrow-alt'];
        }
        $p = (int) round(($now - $before) / $before * 100);
        if ($p === 0) {
            return ['text' => 'Same', 'icon' => 'bx-minus'];
        }

        return ['text' => abs($p) . '%', 'icon' => $p > 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt', 'good' => $lowerIsBetter ? $p < 0 : $p > 0];
    };

    $clear = [
        'Cleared' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'from-emerald-500 to-teal-400', 'bx-check'],
        'Pending' => ['bg-amber-50 text-amber-700 ring-amber-200', 'from-amber-400 to-orange-400', 'bx-time-five'],
        'Uncleared' => ['bg-rose-50 text-rose-700 ring-rose-200', 'from-rose-500 to-pink-500', 'bx-x'],
        'Not tagged' => ['bg-slate-100 text-slate-600 ring-slate-200', 'from-slate-400 to-slate-500', 'bx-minus'],
    ];
    $txTone = fn ($state) => match ($state) {
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'approved' => 'bg-blue-50 text-[#2A57B4] ring-blue-200',
        'submitted', 'under_review' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'action_required', 'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'rejected', 'voided' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };

    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm';
    $cardTitle = 'text-[15px] font-semibold text-slate-900';
    $cardNote = 'mt-0.5 text-xs text-slate-500';
    $pill = 'flex min-w-0 items-center gap-1 rounded-full bg-slate-100 py-1 pl-3 pr-1 text-sm transition focus-within:ring-2 focus-within:ring-[#2A57B4]';
    $pillOn = '!bg-[#2A57B4] [&>span]:!text-white [&>select]:!text-white [&_option]:text-slate-800';
    $pillSelect = 'max-w-[13rem] cursor-pointer truncate rounded-full border-0 bg-transparent py-1 pl-1 pr-7 text-sm font-semibold text-slate-800 focus:ring-0';
    $th = 'px-4 py-2.5 text-left text-xs font-semibold text-[#2A57B4]';
    $ruled = 'border-collapse [&_td]:border [&_th]:border [&_td]:border-slate-200 [&_th]:border-slate-200 [&_tr>*:first-child]:border-l-0 [&_tr>*:last-child]:border-r-0 [&_tr>*:first-child]:pl-4 [&_tr>*:last-child]:pr-4 md:[&_tr>*:first-child]:pl-6 md:[&_tr>*:last-child]:pr-6';
    // Sections that run edge to edge: out past the page gutter, their own text back in by it.
    $bleed = '-mx-4 md:-mx-6';
    $gutter = 'px-4 md:px-6';
    $badge = 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset';
    $menu = 'z-40 w-56 max-w-[calc(100vw-1.5rem)] max-h-[calc(100dvh-5rem)] overflow-y-auto rounded-xl bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200';
    $menuItem = 'flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40';
    $outlineBtn = 'inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]';
    $studentAvatar = 'h-7 w-7 text-[10px] bg-gradient-to-br from-sky-500 to-cyan-400';
    $first = $rowTotal ? ($page - 1) * \App\Livewire\Admin\Reports::PER_PAGE + 1 : 0;
    $last = min($rowTotal, $page * \App\Livewire\Admin\Reports::PER_PAGE);

    // What the report says (headline, figures, help, details, pick list, records title) comes from
    // the component's summary(), which the PDF download uses too; here it only gets its styling.
    $selectedForm = $summary['selectedForm'];
    $insight = $summary['insight'];
    $headline = [$summary['headline'][0], $summary['headline'][1], ['emerald' => 'text-emerald-600', 'blue' => 'text-[#2A57B4]', 'teal' => 'text-teal-600'][$summary['headline'][2]]];
    // figure key => [icon, gradient]
    $tileLook = [
        'requests' => ['bx-file', 'from-[#2A57B4] to-[#3b6fd8]'],
        'students' => ['bx-group', 'from-sky-500 to-cyan-400'],
        'completed' => ['bx-check-double', 'from-emerald-500 to-teal-400'],
        'returned' => ['bx-undo', 'from-rose-500 to-pink-500'],
        'orgs' => ['bx-buildings', 'from-[#2A57B4] to-[#3b6fd8]'],
        'members' => ['bx-group', 'from-sky-500 to-cyan-400'],
        'filed' => ['bx-file', 'from-amber-400 to-orange-400'],
        'cleared' => ['bx-check-shield', 'from-emerald-500 to-teal-400'],
    ] + collect($clear)->map(fn ($c) => [$c[2], $c[1]])->all();
    $tiles = array_map(fn ($t) => [
        $t['label'], $t['value'], $tileLook[$t['key']][0],
        $t['key'] === 'returned' && ! $t['value'] ? 'from-slate-400 to-slate-500' : $tileLook[$t['key']][1],
        $t['before'], $t['lower'], $t['click'],
    ], $summary['tiles']);
    $tileHelp = collect($summary['help'])->map(fn ($h) => $tab === 'clearance' ? [$h[0], $h[1] . ' Click to list only them.'] : $h)->all();
    $details = $summary['details'];
    $listAll = $summary['listAll'];
    $listItems = collect($summary['listItems']);
    $listAction = fn ($value) => match ($tab) {
        'forms' => 'pickForm(' . \Illuminate\Support\Js::from($value) . ')',
        'organizations' => 'pickOrg(' . \Illuminate\Support\Js::from($value) . ')',
        'clearance' => '$set(\'clearanceOrg\', ' . \Illuminate\Support\Js::from($value) . ')',
    };
    [$listTitle, $listHead] = $summary['list'];
    $listPeak = max(1, (int) $listItems->max(2));

    [$recordsTitle, $recordsNote, $unit] = $summary['records'];
    $filtered = $summary['filtered'];
    // The table's columns, picked in the Metrics panel: [key, header]
    $numeric = \App\Livewire\Admin\Reports::NUMERIC_METRICS;
    $columns = array_map(fn ($k) => [$k, $metricLabels[$k]], $columnOrder);
    $dash = '<span class="text-slate-300">—</span>';
    $reportName = $currentSaved?->name ?? $reports[$tab][0];
    $chartTitle = match ($tab) { 'forms' => 'Requests over time', 'organizations' => 'Member requests over time', 'clearance' => 'Clearance set over time' };
    $sparkle = '<svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="currentColor" aria-hidden="true"><path d="M12 2c.4 4.9 2.9 7.6 8 8-5.1.4-7.6 3.1-8 8-.4-4.9-2.9-7.6-8-8 5.1-.4 7.6-3.1 8-8Z"/><path d="M19 15c.2 2.2 1.1 3.1 3 3.3-1.9.2-2.8 1.1-3 3.2-.2-2.1-1.1-3-3-3.2 1.9-.2 2.8-1.1 3-3.3Z" opacity=".6"/></svg>';
    // Hover / focus help for menu items: sets the menu's `tip`, which its footer shows.
    $tip = fn (string $text) => '@mouseenter="tip = ' . e(\Illuminate\Support\Js::from($text)) . '" @focus="tip = ' . e(\Illuminate\Support\Js::from($text)) . '" @mouseleave="tip = \'\'" @blur="tip = \'\'"';
    $tipFooter = '<div class="border-t border-slate-100 bg-slate-50 px-3.5 py-2.5 text-xs leading-relaxed text-slate-600" aria-live="polite"><p class="flex gap-2"><i class="bx bx-info-circle mt-0.5 shrink-0 text-sm text-[#2A57B4]" aria-hidden="true"></i><span x-text="tip || \'Point at an option to see what it gives you.\'"></span></p></div>';
    $colCount = count($metrics) + 1;
    $downloadTips = [
        'sheet' => 'Opens this table in a new browser tab, laid out like a spreadsheet: the ' . $colCount . ' columns you picked in Metrics, a Total row, and every row (all pages). From there you can save it as Excel or CSV. Nothing downloads until you choose.',
        'csv' => 'Downloads all ' . number_format($rowTotal) . ' ' . Str::plural('row', $rowTotal) . ' of this table (every page, not just this one) as a .csv file that Excel or Google Sheets opens. It keeps your search, filters and the columns picked in Metrics, with the Total row first.',
        'ticked' => 'Downloads only the rows you ticked in the table, as a .csv with the same columns. Tick rows with the boxes on the left.',
        'clear' => 'Unticks every row in the table. Nothing is downloaded.',
        'pdf' => 'An A4 report on the LNU letterhead, written like an official report: a conclusion, each key area marked on track or needing attention with its priority, context, recommended actions and a sign-off, with the figures and chart as support. The AI drafts the wording first; you review and edit it before it downloads.',
        'excel' => 'The same full summary as the PDF, as an Excel workbook: one sheet per section with charts, and the request register and logbook as their own sheets, ready for your own analysis.',
    ];
    $rowKey = fn ($r) => (string) ($r['code'] ?? $r['user_id']);
@endphp

<div class="relative -mx-4 overflow-x-clip md:-mx-6 xl:-m-6" x-effect="document.body.classList.toggle('report-ai-open', !! ai)" x-data="{ ai: $wire.entangle('aiOpen'), pending: null,
        destroy() { document.body.classList.remove('report-ai-open') },
        ask(q) { if (! q || this.pending) return; this.ai = true; this.pending = q; this.$dispatch('ai-scroll'); this.$wire.askAi(q).finally(() => { this.pending = null; this.$dispatch('ai-scroll') }) } }">



    <div class="grid items-start gap-4 xl:grid-cols-[20rem_minmax(0,1fr)] xl:grid-rows-[auto_1fr] xl:gap-0 2xl:grid-cols-[22rem_minmax(0,1fr)]" wire:loading.class="opacity-60" wire:target="tab,setTab,compare,applyComparison,removeComparison,datePreset,dateFrom,dateTo,applyCustomRange,semesterId,clearanceOrg,search,pickForm,pickOrg,pickStatus,resetFilters,goToPage,clearClearanceFilters,loadSavedReport,setMetrics,resetMetrics,chartType,interval">


        {{-- ===================== CONTROLS AND AI QUESTIONS ===================== --}}
        <div class="min-w-0 space-y-3 px-4 md:px-6 xl:col-start-2 xl:row-start-1 xl:pt-6">
        {{-- ===================== CONTROLS ===================== --}}
    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            {{-- Report: the three built-in reports and the ones this admin saved --}}
            <div x-data="{ open: false }" class="relative min-w-0 grow basis-[calc(50%-0.25rem)] sm:grow-0 sm:basis-auto" @keydown.escape.window="open = false">
                <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu"
                    class="flex w-full min-w-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-left shadow-sm sm:w-auto sm:min-w-[11rem] sm:gap-3 sm:px-3.5 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] leading-4 text-slate-500">Report</span>
                        <span class="block truncate text-sm font-semibold text-slate-900 sm:max-w-[13rem]">{{ $reportName }}</span>
                    </span>
                    <i class="bx bx-chevron-down text-xl text-slate-500 transition" :class="open && 'rotate-180'" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" x-transition.origin.top.left role="menu" class="{{ $menu }} w-72">
                    <p class="px-3.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Reports</p>
                    @foreach ($reports as $key => [$label, $icon, $tone])
                        <button type="button" role="menuitem" wire:click="setTab('{{ $key }}')" @click="open = false" class="{{ $menuItem }} {{ $tab === $key && ! $currentSaved ? 'bg-blue-50 font-semibold !text-[#2A57B4]' : '' }}">
                            <i class="bx {{ $icon }} text-lg {{ $tone }}" aria-hidden="true"></i>{{ $label }}
                        </button>
                    @endforeach
                    <p class="mt-1 border-t border-slate-100 px-3.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Saved reports</p>
                    <div class="max-h-64 overflow-y-auto">
                        @forelse ($savedReports as $saved)
                            <div wire:key="saved-{{ $saved->id }}" class="group flex items-center {{ $savedId === $saved->id ? 'bg-blue-50' : 'hover:bg-slate-50' }}">
                                <button type="button" role="menuitem" wire:click="loadSavedReport({{ $saved->id }})" @click="open = false" class="flex min-w-0 flex-1 items-center gap-2.5 px-3.5 py-2 text-left">
                                    <i class="bx {{ $savedId === $saved->id ? 'bxs-bookmark text-[#2A57B4]' : 'bx-bookmark text-slate-400' }} text-lg" aria-hidden="true"></i>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm {{ $savedId === $saved->id ? 'font-semibold text-[#2A57B4]' : 'text-slate-700' }}">{{ $saved->name }}</span>
                                        <span class="block text-[11px] text-slate-400">{{ $reports[$saved->tab][0] ?? 'Report' }} · {{ $saved->updated_at->diffForHumans() }}</span>
                                    </span>
                                </button>
                                <button type="button" wire:click="deleteSavedReport({{ $saved->id }})" wire:confirm="Delete the saved report “{{ $saved->name }}”?"
                                    class="mr-2 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 sm:opacity-0 sm:focus:opacity-100 sm:group-hover:opacity-100" aria-label="Delete {{ $saved->name }}">
                                    <i class="bx bx-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        @empty
                            <p class="px-3.5 pb-3 pt-1 text-xs leading-relaxed text-slate-500">Nothing saved yet. Set up a report, then press <i class="bx bx-bookmark align-middle" aria-hidden="true"></i> to keep it here.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Period: grouped list like YouTube Studio's, with a custom range panel --}}
            <div wire:key="period-{{ $datePreset }}-{{ $dateFrom }}-{{ $dateTo }}" x-data="{ open: false, custom: false, from: @js($dateFrom), to: @js($dateTo),
                    get days() { if (! this.from || ! this.to) return 0; return Math.abs(Math.round((new Date(this.to) - new Date(this.from)) / 864e5)) + 1 } }"
                class="relative min-w-0 grow basis-[calc(50%-0.25rem)] sm:grow-0 sm:basis-auto" @keydown.escape.window="open = false; custom = false">
                <button type="button" x-ref="trigger" @click="open = ! open; custom = false" :aria-expanded="open" aria-haspopup="listbox"
                    class="flex w-full min-w-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-left shadow-sm sm:w-auto sm:min-w-[11rem] sm:gap-3 sm:px-3.5 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[11px] leading-4 text-slate-500" title="{{ $rangeLabel }}">{{ $rangeShort }}</span>
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $periodName }}</span>
                    </span>
                    <i class="bx bx-chevron-down text-xl text-slate-500 transition" :class="open && 'rotate-180'" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false; custom = false" x-transition.origin.top.right
                    class="z-40 w-64 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-xl bg-white text-sm shadow-lg ring-1 ring-slate-200">
                    <div x-show="! custom" role="listbox" aria-label="Period" class="thin-scroll max-h-[min(26rem,calc(100dvh-6rem))] overflow-y-auto py-1">
                        @foreach ($periodGroups as $group)
                            @continue(! $group)
                            <div class="{{ $loop->first ? '' : 'mt-1 border-t border-slate-100 pt-1' }}">
                                @foreach ($group as $key => $label)
                                    <button type="button" role="option" aria-selected="{{ $datePreset === $key ? 'true' : 'false' }}" wire:click="$set('datePreset', '{{ $key }}')" @click="open = false"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left transition {{ $datePreset === $key ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">
                                        {{ $label }}
                                        @if ($datePreset === $key)<i class="bx bx-check text-lg text-[#2A57B4]" aria-hidden="true"></i>@endif
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                        <div class="mt-1 border-t border-slate-100 pt-1">
                            <button type="button" @click="custom = true" class="flex w-full items-center justify-between px-4 py-2 text-left transition {{ $datePreset === 'custom' ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">
                                Custom <i class="bx bx-chevron-right text-lg text-slate-400" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div x-show="custom" x-cloak class="p-4">
                        <div class="flex items-end gap-2">
                            <label class="min-w-0 flex-1 text-[11px] text-slate-500">From
                                <input type="date" x-model="from" :max="to" class="mt-0.5 w-full rounded-none border-0 border-b-2 border-slate-300 px-0 py-1 text-sm text-slate-900 focus:border-[#2A57B4] focus:ring-0">
                            </label>
                            <span class="pb-1.5 text-slate-400">–</span>
                            <label class="min-w-0 flex-1 text-[11px] text-slate-500">To
                                <input type="date" x-model="to" :min="from" max="{{ today()->toDateString() }}" class="mt-0.5 w-full rounded-none border-0 border-b-2 border-slate-300 px-0 py-1 text-sm text-slate-900 focus:border-[#2A57B4] focus:ring-0">
                            </label>
                        </div>
                        <p class="mt-3 text-xs text-slate-600"><b class="tabular-nums" x-text="days"></b> <span x-text="days === 1 ? 'day selected' : 'days selected'"></span></p>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" @click="custom = false" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                            <button type="button" :disabled="! from || ! to" @click="$wire.applyCustomRange(from, to); open = false; custom = false" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-40">Apply</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comparison: YouTube Studio's "Select what to compare" --}}
            @php
                $seriesKeys = array_keys($seriesOptions);
                $itemKeys = array_keys($itemLabels);
                $isPair = in_array($compare, ['metric', 'item'], true);
                $active = $isPair || $comparison;
                $itemNoun = $tab === 'forms' ? 'form' : 'organization';
            @endphp
            <div x-data="{
                    open: false,
                    mode: @js($active ? $compare : 'metric'),
                    a: @js($compare === 'metric' ? $compareA : ($seriesKeys[0] ?? null)),
                    b: @js($compare === 'metric' ? $compareB : ($seriesKeys[1] ?? null)),
                    ia: @js($compare === 'item' ? $compareA : ($itemKeys[0] ?? null)),
                    ib: @js($compare === 'item' ? $compareB : ($itemKeys[1] ?? null)),
                    from: @js($compareFrom), to: @js($compareTo), sem: @js($compareSemesterId),
                    get ready() {
                        if (this.mode === 'metric') return this.a && this.b && this.a !== this.b;
                        if (this.mode === 'item') return this.ia && this.ib && this.ia !== this.ib;
                        if (this.mode === 'custom') return @js($tab === 'clearance') ? !! this.sem : !! (this.from && this.to);
                        return true;
                    },
                    add() {
                        const pair = this.mode === 'item' ? [this.ia, this.ib] : [this.a, this.b];
                        $wire.applyComparison({ mode: this.mode, a: pair[0], b: pair[1], from: this.from, to: this.to, semester: this.sem });
                        this.open = false;
                    },
                }" class="flex min-w-0 grow flex-wrap items-center gap-2 sm:grow-0" @keydown.escape.window="open = false">
                @if ($isPair)
                    @foreach ([$compareA, $compareB] as $i => $pick)
                        <button type="button" @click="open = true"
                            class="flex h-11 max-w-[13rem] items-center gap-2 overflow-hidden rounded-xl border border-slate-200 bg-white pr-3 text-left text-sm font-medium text-slate-900 shadow-sm transition hover:bg-slate-50"
                            title="Change what is compared">
                            <span class="h-full w-1.5 shrink-0" style="background: {{ $compareColours[$i] }}" aria-hidden="true"></span>
                            <span class="min-w-0 truncate pl-1.5">{{ $compare === 'metric' ? ($seriesOptions[$pick] ?? $pick) : ($itemLabels[$pick] ?? $pick) }}</span>
                            <i class="bx bx-chevron-down shrink-0 text-lg text-slate-500" aria-hidden="true"></i>
                        </button>
                    @endforeach
                @elseif ($comparison)
                    <button type="button" @click="open = true" class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pl-3.5 pr-2 text-left shadow-sm transition hover:bg-slate-50" title="Change what is compared">
                        <span class="min-w-0">
                            <span class="block text-[11px] leading-4 text-slate-500">{{ ['previous' => 'Period-over-period', 'lastyear' => 'Year-over-year', 'custom' => 'Compared with'][$compare] }}</span>
                            <span class="block max-w-[13rem] truncate text-sm font-semibold text-slate-900">{{ $comparison['label'] }}</span>
                        </span>
                        <i class="bx bx-chevron-down text-xl text-slate-500" aria-hidden="true"></i>
                    </button>
                @endif
                @if ($active)
                    <button type="button" wire:click="removeComparison" class="inline-flex h-11 items-center rounded-full bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-700">Remove comparison</button>
                @else
                    <button type="button" @click="open = true"
                        class="inline-flex h-11 grow items-center justify-center gap-2 rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] sm:grow-0">
                        <i class="bx bx-git-compare text-lg" aria-hidden="true"></i><span class="2xl:hidden">Compare</span><span class="hidden 2xl:inline">Add comparison</span>
                    </button>
                @endif

                <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/40 p-4" @click.self="open = false">
                    <div role="dialog" aria-modal="true" aria-labelledby="compare-title" x-trap.noscroll="open"
                        class="max-h-[calc(100dvh-2rem)] w-full max-w-sm overflow-y-auto rounded-3xl bg-white p-6 text-sm shadow-2xl">
                        <h2 id="compare-title" class="text-xl font-semibold text-slate-900">Select what to compare</h2>
                        @php $radio = 'h-5 w-5 border-slate-400 text-slate-900 focus:ring-slate-900'; $sel = 'mt-1 w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-slate-900'; @endphp
                        <div class="mt-5 space-y-4">
                            <div>
                                <label class="flex cursor-pointer items-center gap-3 text-[15px] text-slate-900"><input type="radio" value="metric" x-model="mode" class="{{ $radio }}">Metric</label>
                                <div x-show="mode === 'metric'" x-collapse class="ml-8 mt-2 space-y-2">
                                    <select x-model="a" aria-label="Metric" class="{{ $sel }}">
                                        @foreach ($seriesOptions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <p class="text-xs text-slate-500">Compare with</p>
                                    <select x-model="b" aria-label="Compare with" class="{{ $sel }} !mt-0.5">
                                        @foreach ($seriesOptions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <p x-show="a && a === b" class="text-xs text-rose-600">Pick two different metrics.</p>
                                </div>
                            </div>
                            <div>
                                <label class="flex items-center gap-3 text-[15px] {{ count($itemKeys) < 2 ? 'text-slate-400' : 'cursor-pointer text-slate-900' }}">
                                    <input type="radio" value="item" x-model="mode" @disabled(count($itemKeys) < 2) class="{{ $radio }}">{{ $tab === 'forms' ? 'Forms' : 'Organizations' }}
                                </label>
                                @if (count($itemKeys) < 2)<p class="ml-8 text-xs text-slate-400">Needs at least two {{ Str::plural($itemNoun) }} with data.</p>@endif
                                <div x-show="mode === 'item'" x-collapse class="ml-8 mt-2 space-y-2">
                                    <select x-model="ia" aria-label="First {{ $itemNoun }}" class="{{ $sel }}">
                                        @foreach ($itemLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <p class="text-xs text-slate-500">Compare with</p>
                                    <select x-model="ib" aria-label="Second {{ $itemNoun }}" class="{{ $sel }} !mt-0.5">
                                        @foreach ($itemLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <p x-show="ia && ia === ib" class="text-xs text-rose-600">Pick two different {{ Str::plural($itemNoun) }}.</p>
                                </div>
                            </div>
                            @foreach (['previous' => $tab === 'clearance' ? 'Semester-over-semester' : 'Period-over-period', 'lastyear' => 'Year-over-year'] as $mode => $name)
                                <div>
                                    <label class="flex items-center gap-3 text-[15px] {{ $compareOptions[$mode] ? 'cursor-pointer text-slate-900' : 'text-slate-400' }}">
                                        <input type="radio" value="{{ $mode }}" x-model="mode" @disabled(! $compareOptions[$mode]) class="{{ $radio }}">{{ $name }}
                                    </label>
                                    <p class="ml-8 text-xs text-slate-500">{{ $compareOptions[$mode][1] ?? 'Nothing to compare with' }}</p>
                                </div>
                            @endforeach
                            <div>
                                <label class="flex cursor-pointer items-center gap-3 text-[15px] text-slate-900"><input type="radio" value="custom" x-model="mode" class="{{ $radio }}">Custom</label>
                                <div x-show="mode === 'custom'" x-collapse class="ml-8 mt-2">
                                    @if ($tab === 'clearance')
                                        <select x-model.number="sem" aria-label="Semester to compare with" class="{{ $sel }}">
                                            <option value="">Choose a semester</option>
                                            @foreach ($semesters->where('id', '!=', $semesterId) as $sem)<option value="{{ $sem->id }}">{{ $sem->label() }}</option>@endforeach
                                        </select>
                                    @else
                                        <div class="flex items-end gap-2">
                                            <label class="min-w-0 flex-1 text-[11px] text-slate-500">From
                                                <input type="date" x-model="from" :max="to" class="mt-0.5 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-slate-900 focus:ring-slate-900">
                                            </label>
                                            <label class="min-w-0 flex-1 text-[11px] text-slate-500">To
                                                <input type="date" x-model="to" :min="from" max="{{ today()->toDateString() }}" class="mt-0.5 w-full rounded-lg border-slate-300 py-2 text-sm focus:border-slate-900 focus:ring-slate-900">
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="open = false" class="rounded-full bg-slate-100 px-5 py-2.5 font-semibold text-slate-800 hover:bg-slate-200">Cancel</button>
                            <button type="button" :disabled="! ready" @click="add()" class="rounded-full bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700 disabled:opacity-40">{{ $active ? 'Apply' : 'Add' }}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Clearance is read for one semester --}}
                @if ($tab !== 'forms')
                    <label class="{{ $pill }} self-stretch {{ $semesterId !== ($semesters->firstWhere('is_current', true)?->id) ? $pillOn : '' }}">
                        <span class="text-slate-500">Semester</span>
                        <select wire:model.live="semesterId" class="{{ $pillSelect }}">
                            @forelse ($semesters as $sem)
                                <option value="{{ $sem->id }}" @selected($sem->id === $semesterId)>{{ $sem->label() }}{{ $sem->pickerSuffix() }}</option>
                            @empty
                                <option value="">No semesters yet</option>
                            @endforelse
                        </select>
                    </label>
                @endif

            <div class="flex items-center gap-0.5">
            {{-- How to read this report --}}
            <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]" aria-label="How to read this report" title="How to read this report">
                    <i class="bx bx-help-circle text-2xl" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" x-transition.origin.top.right role="dialog" aria-label="How to read this report"
                    class="thin-scroll z-40 max-h-[calc(100dvh-5rem)] w-[22rem] max-w-[calc(100vw-1.5rem)] overflow-y-auto rounded-xl bg-white p-4 text-sm shadow-xl ring-1 ring-slate-200">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-semibold text-slate-900">How to read this report</p>
                        <button type="button" @click="open = false" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close"><i class="bx bx-x text-xl" aria-hidden="true"></i></button>
                    </div>
                    <div class="space-y-2 text-xs leading-relaxed text-slate-700">
                    @if ($tab === 'forms')
                        <p>Pick a form on the left to follow it in the chart and the list. <b>Students</b>: different students who filed it. <b>Requests</b>: times it was filed. <b>Completed</b>: finished and issued. <b>Returned</b>: rejected or voided, counted on the day it was filed.</p>
                        <p>The coloured figures compare with {{ $bySemester ? 'the semester before' : 'the same number of days just before the period' }}; “better” or “worse” says whether the change is good news.</p>
                    @elseif ($tab === 'organizations')
                        <p>Groups active students by the organization on their profile. Pick one on the left to follow it. The period counts the forms students filed; the semester picker sets the clearance figures.</p>
                    @else
                        <p>Every active student’s clearance for the chosen semester. Click a coloured figure or an organization to filter the list. <b>Not tagged</b> means no officer has checked the student yet; it is not the same as Uncleared.</p>
                        <p>The chart shows when the current statuses were set during the semester.</p>
                    @endif
                    <p>Use <b>Metrics</b> (or <i class="bx bx-plus-circle align-middle" aria-hidden="true"></i> in the table header) to add or remove columns. Tick rows to download only those, press <i class="bx bx-bookmark align-middle" aria-hidden="true"></i> to save this setup, and ask the AI chips above for a quick read.</p>
                        <p><b>Add comparison</b> lays the same figures for an earlier period (or semester) beside this one: dashed lines or paler bars in the chart, and the change beside each item on the left.</p>
                    </div>
                </div>
            </div>

            {{-- Save this report (bookmark) --}}
            <div x-data="{ open: false, name: '' }" class="relative" @keydown.escape.window="open = false" @report-saved.window="open = false; name = ''">
                <button type="button" x-ref="trigger" @click="open = ! open; $nextTick(() => $refs.name?.focus())" :aria-expanded="open"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                    aria-label="{{ $currentSaved ? 'Saved report options' : 'Save this report' }}" title="{{ $currentSaved ? 'Saved as “' . $currentSaved->name . '”' : 'Save this report' }}">
                    <i class="bx {{ $currentSaved ? 'bxs-bookmark text-[#2A57B4]' : 'bx-bookmark' }} text-2xl" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" x-transition.origin.top.right class="z-40 max-h-[calc(100dvh-5rem)] w-80 max-w-[calc(100vw-1.5rem)] overflow-y-auto rounded-xl bg-white p-4 text-sm shadow-lg ring-1 ring-slate-200">
                    @if ($currentSaved)
                        <p class="text-xs text-slate-500">Open now</p>
                        <p class="truncate font-semibold text-slate-900">{{ $currentSaved->name }}</p>
                        <button type="button" wire:click="updateSavedReport" @click="open = false" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="bx bx-refresh text-lg" aria-hidden="true"></i>Update with what is on screen
                        </button>
                        <p class="mt-4 border-t border-slate-100 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Or save as a new report</p>
                    @else
                        <p class="font-semibold text-slate-900">Save this report</p>
                        <p class="mt-0.5 text-xs text-slate-500">Keeps the report, period, filters, columns and chart so you can come back from the Report menu. Rolling periods like “Last 28 days” move with the calendar.</p>
                    @endif
                    <form @submit.prevent="$wire.saveReport(name)" class="mt-3">
                        <label class="sr-only" for="report-name">Report name</label>
                        <input id="report-name" x-ref="name" x-model="name" maxlength="80" placeholder="e.g. {{ $reports[$tab][0] }}, {{ $periodName }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                        @error('reportName')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" @click="open = false" class="rounded-full bg-slate-100 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                            <button type="submit" :disabled="! name.trim()" class="rounded-full bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-700 disabled:opacity-40">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Export: the table as a spreadsheet tab or .csv, or the full summary --}}
            <div x-data="{ open: false, tip: '' }" class="relative" @keydown.escape.window="open = false">
                <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]" aria-label="Export" title="Export">
                    <i class="bx bx-download text-2xl" wire:loading.remove wire:target="openPdfPreview,exportPdf,exportExcel,downloadList" aria-hidden="true"></i>
                    <i class="bx bx-loader-alt animate-spin text-2xl" wire:loading wire:target="openPdfPreview,exportPdf,exportExcel,downloadList" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" x-transition.origin.top.right role="menu" class="{{ $menu }} w-72">
                    <p class="px-3.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">This table · {{ $fmt($rowTotal) }} {{ Str::plural('row', $rowTotal) }}</p>
                    <a href="{{ $sheetUrl }}" target="_blank" rel="noopener" role="menuitem" @click="open = false" {!! $tip($downloadTips['sheet']) !!} class="{{ $menuItem }}">
                        <i class="bx bx-spreadsheet text-lg text-emerald-600" aria-hidden="true"></i><span class="flex-1">Spreadsheet</span><span class="inline-flex items-center gap-0.5 text-[11px] text-slate-400">opens new tab <i class="bx bx-link-external" aria-hidden="true"></i></span>
                    </a>
                    <button type="button" role="menuitem" wire:click="downloadList" @click="open = false" @disabled(! $rowTotal) {!! $tip($downloadTips['csv']) !!} class="{{ $menuItem }}"><i class="bx bx-file text-lg text-[#2A57B4]" aria-hidden="true"></i>Comma-separated values (.csv)</button>
                    <p class="mt-1 border-t border-slate-100 px-3.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Full summary report</p>
                    <button type="button" role="menuitem" wire:click="openPdfPreview" @click="open = false" {!! $tip($downloadTips['pdf']) !!} class="{{ $menuItem }}"><i class="bx bxs-file-pdf text-lg text-rose-500" aria-hidden="true"></i>PDF</button>
                    <button type="button" role="menuitem" wire:click="exportExcel" @click="open = false" {!! $tip($downloadTips['excel']) !!} class="{{ $menuItem }}"><i class="bx bx-spreadsheet text-lg text-emerald-600" aria-hidden="true"></i>Excel (.xlsx)</button>
                    {!! $tipFooter !!}
                </div>
            </div>
            <button type="button" wire:click="resetFilters" title="Back to the last 180 days, the current semester and no search" class="inline-flex h-11 items-center rounded-full px-2.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">Reset</button>
            </div>
    </div>

    {{-- ===================== AI QUESTIONS ===================== --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="Ask AI about this report">
        @foreach ($aiSuggestions as $q)
            <button type="button" @click="ask(@js($q))" :disabled="pending"
                class="inline-flex min-h-10 max-w-full items-center gap-2 rounded-full bg-slate-100 px-3.5 py-1.5 text-left text-[13px] font-medium text-slate-800 transition hover:bg-slate-200 disabled:opacity-60 sm:px-4 sm:text-sm">
                {!! $sparkle !!}{{ $q }}
            </button>
        @endforeach
        <button type="button" @click="ai = true" class="inline-flex min-h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-[13px] font-medium text-slate-700 sm:px-4 sm:text-sm transition hover:bg-slate-50">
            <i class="bx bx-message-square-dots text-base" aria-hidden="true"></i>Ask something else
        </button>
    </div>
        </div>

        {{-- ===================== SIDEBAR: SUMMARY AND PICK LIST ===================== --}}
        <aside x-data="{ more: false }" class="thin-scroll mx-4 flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white md:mx-6 xl:col-start-1 xl:row-span-2 xl:row-start-1 xl:mx-0 xl:sticky xl:-top-6 xl:h-[calc(100dvh-64px)] xl:overflow-y-auto xl:overscroll-contain xl:rounded-none xl:border-0 xl:border-r" aria-label="Report summary">
        <section class="cq shrink-0 border-slate-200 p-3 sm:p-4 xl:border-b" aria-label="Summary">
            <div class="grid gap-3 sm:gap-4">
                <div class="flex items-baseline gap-3">
                    <p class="text-2xl font-semibold tracking-tight tabular-nums sm:text-3xl {{ $headline[2] }}">{{ $headline[0] }}</p>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $headline[1] }}</p>
                </div>

                <div x-data="{ tile: null }" class="relative grid grid-cols-2 gap-2 sm:gap-3 cq-sm:grid-cols-4" @mouseleave="tile = null">
                    @foreach ($tiles as $ti => [$label, $value, $icon, $gradient, $before, $lowerIsBetter, $click])
                        @php $hover = '@mouseenter="tile = ' . $ti . '" @focus="tile = ' . $ti . '" @blur="tile = null" aria-describedby="tile-help-' . $ti . '"'; @endphp
                        @php $d = $delta($value, $before, $lowerIsBetter); $on = $click && $status === $click; @endphp
                        @if ($click)
                            <button type="button" wire:click="pickStatus('{{ $click }}')" aria-pressed="{{ $on ? 'true' : 'false' }}" {!! $hover !!}
                                class="relative overflow-hidden rounded-xl bg-gradient-to-br {{ $gradient }} p-3 text-left text-white shadow-sm transition sm:p-4 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2 {{ $on ? 'ring-2 ring-slate-900 ring-offset-2' : '' }}">
                        @else
                            <div tabindex="0" {!! $hover !!} @click="tile = tile === {{ $ti }} ? null : {{ $ti }}" class="relative cursor-help overflow-hidden rounded-xl bg-gradient-to-br {{ $gradient }} p-3 text-white shadow-sm sm:p-4 outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2">
                        @endif
                            <i class="bx {{ $icon }} pointer-events-none absolute -bottom-3 -right-2 text-5xl text-white/20 sm:text-6xl" aria-hidden="true"></i>
                            <p class="flex items-start justify-between gap-1 text-xs font-medium text-white/85">{{ $label }}<i class="bx bx-info-circle text-sm text-white/70" aria-hidden="true"></i></p>
                            <p class="mt-0.5 text-xl font-semibold tabular-nums sm:mt-1 sm:text-2xl">{{ $fmt($value) }}</p>
                            <p class="mt-1 flex min-h-[1.25rem] flex-wrap items-center gap-1.5 text-[11px] text-white/85">
                                @if ($d)
                                    <span class="inline-flex items-center gap-0.5 rounded-md bg-white/20 px-1.5 py-0.5 font-semibold text-white"><i class="bx {{ $d['icon'] }}" aria-hidden="true"></i>{{ $d['text'] }}</span>
                                    <span>{{ $fmt($before) }} before{{ isset($d['good']) ? ($d['good'] ? ' · better' : ' · worse') : '' }}</span>
                                @elseif ($tab === 'clearance')
                                    <span>{{ $pct($value, $studentTotal) }}% of students</span>
                                @endif
                            </p>
                        @if ($click)</button>@else</div>@endif
                    @endforeach

                    {{-- The help, laid over what follows and as wide as the figures, so it never runs off screen --}}
                    @foreach ($tiles as $ti => [$label])
                        @php [$what, $shows] = $tileHelp[$label] ?? ['', '']; @endphp
                        <div id="tile-help-{{ $ti }}" role="tooltip" x-show="tile === {{ $ti }}" x-cloak x-transition.opacity.duration.150ms
                            class="pointer-events-none absolute inset-x-0 top-full z-20 mt-2 rounded-xl bg-slate-900 p-3 text-xs leading-relaxed text-white shadow-xl">
                            <p class="font-semibold">{{ $label }}</p>
                            <p class="mt-1 text-white/85"><span class="font-semibold text-white">Counts:</span> {{ $what }}</p>
                            <p class="mt-1 text-white/85"><span class="font-semibold text-white">Shows:</span> {{ $shows }}</p>
                        </div>
                    @endforeach
                </div>

                <dl x-show="more" x-cloak id="report-details" class="grid gap-1.5 rounded-xl bg-slate-50 p-3 text-xs ring-1 ring-inset ring-slate-100 sm:grid-cols-[auto_minmax(0,1fr)] sm:gap-x-3 xl:!grid">
                    @foreach ($details as [$label, $value])
                        <dt class="font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="truncate font-semibold text-slate-800" title="{{ $value }}">{{ $value }}</dd>
                    @endforeach
                </dl>
            </div>

            <div x-show="more" x-cloak class="mt-4 flex items-start gap-2.5 border-t border-slate-100 pt-3 xl:!flex">
                <i class="bx bx-bulb mt-0.5 text-lg text-emerald-600" aria-hidden="true"></i>
                <p class="min-w-0 flex-1 text-sm text-slate-700">{{ $insight }}</p>
            </div>
            <button type="button" @click="more = ! more" :aria-expanded="more" aria-controls="report-details report-breakdown"
                class="mt-2 flex h-10 w-full items-center justify-center gap-1.5 rounded-lg text-sm font-semibold text-[#2A57B4] transition hover:bg-blue-50 xl:hidden">
                <span x-text="more ? 'Hide details' : @js('Details & breakdown by ' . ($tab === 'forms' ? 'form' : 'organization'))">Details</span>
                <i class="bx bx-chevron-down text-lg transition" :class="more && 'rotate-180'" aria-hidden="true"></i>
            </button>
        </section>

            <section x-show="more" x-cloak id="report-breakdown" class="flex flex-col border-t border-slate-200 xl:!flex xl:border-t-0" aria-labelledby="list-title">
                <h2 id="list-title" class="px-4 pb-2 pt-4 {{ $cardTitle }}">{{ $listTitle }}</h2>
                <div class="sticky top-0 z-10 flex items-center justify-between border-y border-blue-100 bg-blue-50 px-4 py-2 text-xs font-semibold text-[#2A57B4]">
                    <span>{{ $tab === 'forms' ? 'Form' : 'Organization' }}</span><span>{{ $listHead }}</span>
                </div>
                <ul class="thin-scroll max-h-[22rem] divide-y divide-slate-100 overflow-y-auto xl:max-h-none xl:overflow-visible">
                    @foreach (collect([$listAll])->concat($listItems) as $i => [$value, $label, $big, $small, $on])
                        <li wire:key="pick-{{ $tab }}-{{ md5((string) $value) }}">
                            @php $pairColour = $compare === 'item' && $i > 0 ? ($value === $compareA ? $compareColours[0] : ($value === $compareB ? $compareColours[1] : null)) : null; @endphp
                            <button type="button" wire:click="{{ $listAction($on && $i > 0 ? $listAll[0] : $value) }}" aria-pressed="{{ $on ? 'true' : 'false' }}" @if ($pairColour) style="box-shadow: inset 4px 0 0 {{ $pairColour }}" @endif
                                class="block w-full px-4 py-2.5 text-left transition {{ $on ? 'bg-[#2A57B4] text-white' : 'hover:bg-slate-50' }}">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-sm {{ $i === 0 ? 'font-semibold uppercase tracking-wide text-xs' : 'font-medium' }} {{ $on ? 'text-white' : ($label === 'No organization' ? 'text-slate-500' : 'text-slate-800') }}" title="{{ $label }}">{{ $label }}</span>
                                    <span class="shrink-0 tabular-nums"><b class="text-sm {{ $on ? 'text-white' : 'text-slate-900' }}">{{ $fmt($big) }}</b> <span class="text-xs {{ $on ? 'text-white/75' : 'text-slate-400' }}">/ {{ $fmt($small) }}</span></span>
                                </span>
                                @if ($comparison)
                                    @php $then = $comparison['list'][$i === 0 ? '__all' : $value] ?? 0; $d = $delta($big, $then); @endphp
                                    <span class="mt-0.5 flex items-center justify-end gap-1 text-[11px] tabular-nums {{ $on ? 'text-white/80' : 'text-slate-500' }}">
                                        vs {{ $fmt($then) }}
                                        <span class="inline-flex items-center rounded px-1 font-semibold {{ $on ? 'bg-white/20 text-white' : (isset($d['good']) ? ($d['good'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700') : 'bg-slate-100 text-slate-600') }}"><i class="bx {{ $d['icon'] }}" aria-hidden="true"></i>{{ $d['text'] }}</span>
                                    </span>
                                @endif
                                @if ($i > 0)
                                    <span class="mt-1.5 block h-1 overflow-hidden rounded-full {{ $on ? 'bg-white/25' : 'bg-slate-100' }}" aria-hidden="true">
                                        <span class="block h-full rounded-full {{ $on ? 'bg-white' : ($tab === 'clearance' ? 'bg-teal-500' : ($tab === 'organizations' ? 'bg-cyan-500' : 'bg-indigo-500')) }}" style="width: {{ $big / $listPeak * 100 }}%"></span>
                                    </span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                    @if ($listItems->isEmpty())
                        <li class="px-4 py-8 text-center text-sm text-slate-500">{{ $tab === 'forms' ? 'No forms were filed in this period.' : 'There are no active students yet.' }}</li>
                    @endif
                </ul>
            </section>
        </aside>

        <div class="min-w-0 space-y-4 px-4 pb-24 md:px-6 md:pb-8 xl:col-start-2 xl:row-start-2 xl:pt-5">

        {{-- ===================== CHART ===================== --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-900">{{ in_array($compare, ['metric', 'item'], true) && $compareTitle ? 'Compare ' . $compareTitle : $chartTitle }}@if (! in_array($compare, ['metric', 'item'], true)){{ $tab === 'forms' && $selectedForm ? ': ' . $selectedForm['label'] : ($tab === 'organizations' && $org ? ': ' . $org : ($tab === 'clearance' && $clearanceOrg !== '' ? ': ' . $clearanceOrg : '')) }}@endif</h2>
            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                @if ($showChart)
                    <label class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white pl-3 pr-1 text-sm shadow-sm">
                        <i class="bx {{ $chartType === 'bar' ? 'bx-bar-chart-alt-2' : 'bx-line-chart' }} text-lg text-slate-600" aria-hidden="true"></i>
                        <span class="sr-only">Chart type</span>
                        <select wire:model.live="chartType" class="h-full cursor-pointer border-0 bg-transparent py-0 pl-0 pr-8 text-sm font-medium text-slate-800 focus:ring-0">
                            <option value="line">Line chart</option>
                            <option value="bar">Bar chart</option>
                        </select>
                    </label>
                        <label class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white pl-3 pr-1 text-sm shadow-sm">
                            <span class="sr-only">Group by</span>
                            <select wire:model.live="interval" class="h-full cursor-pointer border-0 bg-transparent py-0 pl-0 pr-8 text-sm font-medium text-slate-800 focus:ring-0">
                                <option value="auto">Auto ({{ ['day' => 'daily', 'week' => 'weekly', 'month' => 'monthly', 'year' => 'yearly'][$trend['unit']] }})</option>
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </select>
                        </label>
                @endif
                <label class="ml-auto inline-flex h-10 cursor-pointer select-none items-center gap-2.5 pl-1 text-sm text-slate-600 sm:ml-0">
                    Show chart
                    <input type="checkbox" wire:model.live="showChart" class="peer sr-only">
                    <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 peer-focus-visible:ring-2 peer-focus-visible:ring-[#2A57B4] peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5" aria-hidden="true"></span>
                </label>
            </div>
        </div>
        <div>

            @if ($showChart)
            <section wire:key="chart-{{ $tab }}-{{ $chartType }}-{{ $trend['unit'] }}-{{ $compare }}" class="{{ $card }} p-4 sm:p-5" aria-labelledby="trend-title">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 id="trend-title" class="{{ $cardTitle }}">Trend</h3>
                        <p class="{{ $cardNote }}">
                            {{ $tab === 'clearance' ? 'When this semester’s statuses were set' : ($tab === 'organizations' ? 'Requests from members, and how many members filed' : 'Requests filed, completed and returned') }},
                            by {{ $trend['unit'] }}{{ $comparison ? ', against ' . $comparison['label'] : '' }}
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        <i class="bx bx-calendar" aria-hidden="true"></i>{{ $tab === 'clearance' ? ($semesterDates ?? ($semesterLabel ?? 'No semester')) : $periodName }}
                    </span>
                </div>
                @if ($chartType === 'bar')
                    @include('livewire.admin.partials.reports-bars', [
                        'empty' => $tab === 'clearance' ? 'No clearance status has been set this semester yet.' : 'No requests were filed in this period.',
                    ])
                @else
                    @include('livewire.admin.partials.reports-trend', [
                        'empty' => $tab === 'clearance' ? 'No clearance status has been set this semester yet.' : 'No requests were filed in this period.',
                    ])
                @endif
            </section>
            @endif
        </div>

        {{-- ===================== COMPARISON TABLE ===================== --}}
        @if ($breakdown)
            @php
                $share = fn ($v, $total) => $total > 0 ? number_format($v / $total * 100, 1) . '%' : '—';
                $tint = fn ($colour) => $colour ? 'background: ' . $colour . '14' : '';
            @endphp
            <section wire:key="breakdown-{{ $tab }}-{{ $compare }}-{{ $compareA }}-{{ $compareB }}" class="{{ $bleed }}" aria-labelledby="breakdown-title">
                <div class="{{ $gutter }} mb-2 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="breakdown-title" class="text-lg font-semibold text-slate-900">{{ $compareTitle ? 'Compare ' . $compareTitle : 'Comparison' }}</h2>
                    <p class="text-xs text-slate-500">
                        By {{ strtolower($breakdown['item']) }}{{ $breakdown['thenLabel'] ? ' · small figures are ' . $breakdown['thenLabel'] : '' }} · % is the share of the total
                    </p>
                </div>
                <div class="thin-scroll relative overflow-x-auto">
                    <table class="w-full text-sm {{ $ruled }}">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th scope="col" class="min-w-[14rem] px-4 py-3 text-left text-xs font-semibold text-slate-600">{{ $breakdown['item'] }}</th>
                                @foreach ($breakdown['columns'] as $c)
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold text-slate-700" style="{{ $tint($c['colour']) }}{{ $c['colour'] ? '; box-shadow: inset 0 3px 0 ' . $c['colour'] : '' }}">{{ $c['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr class="bg-slate-50/70 font-semibold text-slate-900">
                                <td class="px-4 py-3">Total</td>
                                @foreach ($breakdown['columns'] as $c)
                                    @php $now = $breakdown['totals'][$c['field']]; $was = $breakdown['thenTotals'][$c['field']] ?? null; $d = $was !== null ? $delta($now, $was) : null; @endphp
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums" style="{{ $tint($c['colour']) }}">
                                        {{ $fmt($now) }}
                                        @if ($d)
                                            <span class="block text-[11px] font-normal text-slate-500">vs {{ $fmt($was) }} <span class="{{ isset($d['good']) ? ($d['good'] ? 'text-emerald-700' : 'text-rose-700') : '' }}">{{ $d['text'] }}</span></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @forelse ($breakdown['rows'] as $r)
                                <tr wire:key="bd-{{ md5((string) $r['key']) }}" class="transition hover:bg-slate-50/70" @if ($r['colour']) style="box-shadow: inset 4px 0 0 {{ $r['colour'] }}; background: {{ $r['colour'] }}0d" @endif>
                                    <td class="max-w-[18rem] truncate px-4 py-3 {{ $r['colour'] ? 'font-semibold text-slate-900' : 'text-slate-800' }}" title="{{ $r['label'] }}">{{ $r['label'] }}</td>
                                    @foreach ($breakdown['columns'] as $c)
                                        @php $now = $r['values'][$c['field']]; $was = $r['then'][$c['field']] ?? null; $d = $was !== null ? $delta($now, $was) : null; @endphp
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums" style="{{ $tint($c['colour']) }}">
                                            @if ($now || $was)
                                                <span class="text-slate-900">{{ $fmt($now) }}</span>
                                                <span class="ml-2 inline-block w-12 text-xs text-slate-500">{{ $share($now, $breakdown['totals'][$c['field']]) }}</span>
                                                @if ($d)
                                                    <span class="block text-[11px] text-slate-500">vs {{ $fmt($was) }} <span class="{{ isset($d['good']) ? ($d['good'] ? 'text-emerald-700' : 'text-rose-700') : '' }}">{{ $d['text'] }}</span></span>
                                                @endif
                                            @else
                                                <span class="text-slate-300">—</span><span class="ml-2 inline-block w-12 text-xs text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($breakdown['columns']) + 1 }}" class="px-4 py-8 text-center text-sm text-slate-500">Nothing to compare for these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ===================== THE RECORDS ===================== --}}
        <section wire:key="records-{{ $tab }}" class="{{ $bleed }} min-w-0" aria-labelledby="records-title"
            x-data="{
                selected: [],
                pageKeys: @js($rows->map($rowKey)->values()),
                get allOnPage() { return this.pageKeys.length > 0 && this.pageKeys.every(k => this.selected.includes(k)) },
                togglePage() { this.selected = this.allOnPage ? this.selected.filter(k => ! this.pageKeys.includes(k)) : [...new Set([...this.selected, ...this.pageKeys])] },
                metricsOpen: false,
                metricQuery: '',
                draft: @js($metrics),
                closed: {},
                openMetrics() { this.draft = [...$wire.metrics]; this.metricQuery = ''; this.metricsOpen = true },
                matches(label) { return label.toLowerCase().includes(this.metricQuery.trim().toLowerCase()) },
            }">
            <div class="{{ $gutter }} flex flex-wrap items-start justify-between gap-3 pb-3">
                <div class="min-w-0">
                    <h2 id="records-title" class="text-lg font-semibold text-slate-900">{{ $recordsTitle }}</h2>
                    <p class="{{ $cardNote }}">
                        <b class="text-slate-700">{{ $fmt($rowTotal) }}</b> {{ Str::plural($unit, $rowTotal) }} · {{ $recordsNote }}
                        @if ($filtered)
                            <button type="button" wire:click="{{ $tab === 'forms' ? 'pickForm(null)' : ($tab === 'organizations' ? 'pickOrg(null)' : 'clearClearanceFilters') }}" class="ml-1 font-semibold text-[#2A57B4] hover:underline">Show all</button>
                        @endif
                    </p>
                </div>
                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <label class="relative w-full sm:w-60">
                        <span class="sr-only">Search this list</span>
                        <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, student no., program…"
                            class="w-full rounded-full border-slate-200 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                    </label>

                    {{-- Metrics: tick the columns to add to the table --}}
                    <div class="relative grow sm:grow-0" @keydown.escape.window="metricsOpen = false">
                        <button type="button" x-ref="metricsTrigger" @click="metricsOpen ? metricsOpen = false : openMetrics()" :aria-expanded="metricsOpen" class="{{ $outlineBtn }} h-10 w-full sm:w-auto">
                            <i class="bx bx-plus-circle text-lg" aria-hidden="true"></i>Metrics
                            <span class="rounded-full bg-slate-100 px-1.5 text-[11px] font-semibold tabular-nums text-slate-600">{{ count($metrics) }}</span>
                        </button>
                        <div x-show="metricsOpen" x-anchor.fixed.bottom-end.offset.6="$refs.metricsTrigger" x-cloak @click.outside="metricsOpen = false" x-transition.origin.top.right
                            class="z-40 flex max-h-[min(30rem,calc(100dvh-5rem))] w-80 max-w-[calc(100vw-1.5rem)] flex-col overflow-hidden rounded-2xl bg-white text-sm shadow-xl ring-1 ring-slate-200" role="dialog" aria-label="Metrics">
                            <div class="flex items-center justify-between px-5 pb-2 pt-4">
                                <p class="text-base font-semibold text-slate-900">Metrics</p>
                                <button type="button" @click="metricsOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close"><i class="bx bx-x text-2xl" aria-hidden="true"></i></button>
                            </div>
                            <div class="px-5 pb-2">
                                <label class="sr-only" for="metric-search">Search metrics</label>
                                <input id="metric-search" type="search" x-model="metricQuery" placeholder="Search metrics" class="w-full rounded-full border-slate-300 px-4 py-2 text-sm focus:border-slate-900 focus:ring-slate-900">
                            </div>
                            <div class="min-h-0 flex-1 overflow-y-auto px-3 pb-2">
                                <div class="flex items-center gap-3 rounded-lg px-2 py-2 text-slate-400">
                                    <input type="checkbox" checked disabled class="h-4 w-4 rounded border-slate-300 text-slate-400">Student <span class="text-[11px]">(always shown)</span>
                                </div>
                                @foreach ($metricGroups as $group => $items)
                                    <div x-show="Object.values(@js($items)).some(l => matches(l))">
                                        <button type="button" @click="closed[@js($group)] = ! closed[@js($group)]" class="flex w-full items-center justify-between rounded-lg px-2 py-2 text-left font-semibold text-slate-900 hover:bg-slate-50">
                                            {{ $group }} <i class="bx bx-chevron-up text-xl text-slate-500 transition" :class="closed[@js($group)] && 'rotate-180'" aria-hidden="true"></i>
                                        </button>
                                        <div x-show="! closed[@js($group)] || metricQuery">
                                            @foreach ($items as $key => $label)
                                                <label x-show="matches(@js($label))" class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-slate-700 hover:bg-slate-50">
                                                    <input type="checkbox" value="{{ $key }}" x-model="draft" class="h-4 w-4 rounded border-slate-400 text-slate-900 focus:ring-slate-900">{{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-2 border-t border-slate-100 px-5 py-3">
                                <button type="button" @click="draft = []" class="rounded-full bg-slate-100 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-200">Deselect all</button>
                                <button type="button" wire:click="resetMetrics" @click="metricsOpen = false" class="px-2 text-xs font-medium text-slate-500 hover:text-slate-800">Default</button>
                                <button type="button" @click="$wire.setMetrics(draft); metricsOpen = false" class="ml-auto rounded-full bg-slate-900 px-5 py-2 font-semibold text-white hover:bg-slate-700">Apply</button>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ open: false, tip: '' }" class="relative" @keydown.escape.window="open = false">
                        <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu" class="{{ $outlineBtn }} h-10" aria-label="Download this table">
                            <i class="bx bx-download text-lg" aria-hidden="true"></i>
                            <span x-show="selected.length" x-cloak class="rounded-full bg-[#2A57B4] px-1.5 text-[11px] font-semibold tabular-nums text-white" x-text="selected.length"></span>
                            <i class="bx bx-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" role="menu" class="{{ $menu }} w-72">
                            <a href="{{ $sheetUrl }}" target="_blank" rel="noopener" role="menuitem" @click="open = false" {!! $tip($downloadTips['sheet']) !!} class="{{ $menuItem }}">
                                <i class="bx bx-spreadsheet text-lg text-emerald-600" aria-hidden="true"></i><span class="flex-1">Spreadsheet</span><i class="bx bx-link-external text-slate-400" aria-hidden="true"></i>
                            </a>
                            <button type="button" role="menuitem" wire:click="downloadList" @click="open = false" @disabled(! $rowTotal) {!! $tip($downloadTips['csv']) !!} class="{{ $menuItem }}">
                                <i class="bx bx-file text-lg text-[#2A57B4]" aria-hidden="true"></i>Whole table (.csv)
                            </button>
                            <button type="button" role="menuitem" :disabled="! selected.length" @click="$wire.downloadList(selected); open = false" {!! $tip($downloadTips['ticked']) !!} class="{{ $menuItem }}">
                                <i class="bx bx-check-square text-lg text-[#2A57B4]" aria-hidden="true"></i><span x-text="selected.length ? 'Ticked rows (' + selected.length + ') .csv' : 'Ticked rows (.csv)'">Ticked rows (.csv)</span>
                            </button>
                            <button type="button" role="menuitem" :disabled="! selected.length" @click="selected = []; open = false" {!! $tip($downloadTips['clear']) !!} class="{{ $menuItem }}">
                                <i class="bx bx-x-circle text-lg text-slate-400" aria-hidden="true"></i>Clear ticks
                            </button>
                            {!! $tipFooter !!}
                        </div>
                    </div>
                </div>
            </div>

            @if ($rows->isEmpty())
                <p class="border-t border-slate-200 {{ $gutter }} py-10 text-center text-sm text-slate-500">{{ $search !== '' ? 'Nothing matches “' . $search . '”.' : 'Nothing to list for these filters.' }}</p>
            @else
                {{-- A table when wide, one card per row when narrow --}}
                <div class="thin-scroll relative hidden overflow-x-auto md:block">
                <table class="w-full text-sm {{ $ruled }}">
                    <thead class="bg-blue-50/60"><tr>
                        <th scope="col" class="w-10 !border-r-0 py-2"><span class="sr-only">Tick</span></th>
                        <th scope="col" class="{{ $th }} !border-l-0 py-2 pl-2">
                            <span class="flex items-center justify-between gap-2">Student
                                <button type="button" @click.stop="openMetrics(); $el.closest('section').scrollIntoView({ block: 'start', behavior: 'smooth' })" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-700 hover:bg-white" aria-label="Add metrics to the table" title="Add metrics">
                                    <i class="bx bx-plus-circle text-xl" aria-hidden="true"></i>
                                </button>
                            </span>
                        </th>
                        @foreach ($columns as [$key, $label])
                            <th scope="col" class="{{ $th }} whitespace-nowrap {{ in_array($key, $numeric, true) ? 'text-right' : '' }}">{{ $label }}</th>
                        @endforeach
                        <th scope="col" class="w-14 pr-4"><span class="sr-only">Actions</span></th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/80 font-semibold text-slate-900">
                            <td class="!border-r-0 py-2"><input type="checkbox" :checked="allOnPage" @change="togglePage()" aria-label="Tick every row on this page" title="Tick every row on this page" class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]"></td>
                            <td class="!border-l-0 py-2 pl-2 pr-4">Total <span class="font-normal text-slate-500">· {{ $fmt($rowTotal) }} {{ Str::plural($unit, $rowTotal) }}</span></td>
                            @foreach ($columns as [$key])
                                <td class="px-4 py-2 text-right tabular-nums">
                                    @if ($key === 'requests'){{ $fmt($columnTotals['requests']) }}@elseif ($key === 'days'){!! $columnTotals['days'] !== null ? $columnTotals['days'] . ' <span class="text-xs font-normal text-slate-500">avg</span>' : $dash !!}@endif
                                </td>
                            @endforeach
                            <td></td>
                        </tr>
                        @foreach ($rows as $r)
                            @php $k = $rowKey($r); @endphp
                            <tr wire:key="row-{{ $tab }}-{{ $k }}" class="transition hover:bg-slate-50/70" :class="selected.includes(@js($k)) && '!bg-blue-50/60'">
                                <td class="!border-r-0 py-1"><input type="checkbox" value="{{ $k }}" x-model="selected" aria-label="Tick {{ $r['student'] }}" class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]"></td>
                                <td class="!border-l-0 py-1 pl-2 pr-4">
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar :src="$r['avatar']" :name="$r['student']" class="{{ $studentAvatar }}" />
                                        <div class="min-w-0">
                                            <p class="max-w-[14rem] truncate font-medium text-slate-900">{{ $r['student'] }}</p>
                                            @unless (in_array('number', $metrics, true))<p class="truncate text-xs tabular-nums text-slate-500">{{ $r['number'] }}</p>@endunless
                                        </div>
                                    </div>
                                </td>
                                @foreach ($columns as [$key])
                                    @switch($key)
                                        @case('status')
                                            <td class="px-4 py-1"><span class="{{ $badge }} {{ $txTone($r['state']) }}">{{ $r['status'] }}</span></td>
                                            @break
                                        @case('clearance')
                                            <td class="px-4 py-1"><span class="{{ $badge }} {{ $clear[$r['status']][0] }}"><i class="bx {{ $clear[$r['status']][2] }}" aria-hidden="true"></i>{{ $r['status'] }}</span></td>
                                            @break
                                        @case('code')
                                            <td class="whitespace-nowrap px-4 py-1"><a href="{{ $r['url'] }}" wire:navigate class="font-semibold tabular-nums text-[#2A57B4] hover:underline">{{ $r['code'] }}</a></td>
                                            @break
                                        @case('organization')
                                            <td class="px-4 py-1"><span class="inline-flex max-w-[11rem] items-center gap-1 truncate rounded-md px-2 py-0.5 text-xs font-medium {{ in_array($r['organization'], ['No organization', '—'], true) ? 'bg-slate-100 text-slate-500' : 'bg-cyan-50 text-cyan-700' }}"><i class="bx bx-buildings" aria-hidden="true"></i>{{ $r['organization'] }}</span></td>
                                            @break
                                        @case('forms')
                                            <td class="max-w-[16rem] truncate px-4 py-1 text-slate-700" title="{{ implode(', ', $r['forms']) }}">{!! $r['forms'] ? e(implode(', ', $r['forms'])) : '<span class="text-xs text-slate-400">None in this period</span>' !!}</td>
                                            @break
                                        @case('requests')
                                            <td class="px-4 py-1 text-right tabular-nums {{ $r['requests'] ? 'text-slate-900' : 'text-slate-400' }}">{{ $fmt($r['requests']) }}</td>
                                            @break
                                        @case('days')
                                            <td class="whitespace-nowrap px-4 py-1 text-right tabular-nums text-slate-700">{!! $r['days'] !== null ? e($r['days']) . ' <span class="text-xs text-slate-400">d</span>' : $dash !!}</td>
                                            @break
                                        @case('submitted')
                                        @case('completed')
                                        @case('tagged')
                                        @case('last_filed')
                                            <td class="whitespace-nowrap px-4 py-1 text-slate-600">{!! $r[$key] ? e($r[$key]->format('M j, Y')) : $dash !!}</td>
                                            @break
                                        @default
                                            @php $v = $r[$key] ?? null; @endphp
                                            <td class="max-w-[15rem] truncate px-4 py-1 text-slate-700 {{ $key === 'number' ? 'tabular-nums' : '' }}" title="{{ $v }}">{!! filled($v) && $v !== '—' ? e($v) : $dash !!}</td>
                                    @endswitch
                                @endforeach
                                <td class="py-0.5 pr-4 text-right">
                                    <div x-data="{ open: false }" class="relative inline-block text-left" @keydown.escape.window="open = false">
                                        <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Actions for {{ $r['student'] }}">
                                            <i class="bx bx-dots-vertical-rounded text-xl" aria-hidden="true"></i>
                                        </button>
                                        <div x-show="open" x-anchor.fixed.bottom-end.offset.6="$refs.trigger" x-cloak @click.outside="open = false" role="menu" class="{{ $menu }}">
                                            @if ($tab === 'forms')
                                                <a href="{{ $r['url'] }}" wire:navigate role="menuitem" class="{{ $menuItem }}"><i class="bx bx-file text-lg text-indigo-600" aria-hidden="true"></i>Open {{ $r['code'] }}</a>
                                                <a href="{{ route('admin.clearance-monitoring.show', $r['user_id']) }}" wire:navigate role="menuitem" class="{{ $menuItem }}"><i class="bx bx-clipboard text-lg text-teal-600" aria-hidden="true"></i>Student’s clearance</a>
                                                <button type="button" role="menuitem" @click="navigator.clipboard?.writeText(@js($r['code'])); open = false" class="{{ $menuItem }}"><i class="bx bx-copy text-lg text-slate-400" aria-hidden="true"></i>Copy transaction code</button>
                                            @else
                                                <a href="{{ $r['url'] }}" wire:navigate role="menuitem" class="{{ $menuItem }}"><i class="bx bx-clipboard text-lg text-teal-600" aria-hidden="true"></i>Open clearance</a>
                                                <button type="button" role="menuitem" @click="navigator.clipboard?.writeText(@js($r['number'])); open = false" class="{{ $menuItem }}"><i class="bx bx-copy text-lg text-slate-400" aria-hidden="true"></i>Copy student number</button>
                                            @endif
                                            <button type="button" role="menuitem" @click="selected.includes(@js($k)) ? selected = selected.filter(x => x !== @js($k)) : selected.push(@js($k)); open = false" class="{{ $menuItem }}">
                                                <i class="bx bx-check-square text-lg text-[#2A57B4]" aria-hidden="true"></i><span x-text="selected.includes(@js($k)) ? 'Untick row' : 'Tick row'">Tick row</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <label class="flex h-11 cursor-pointer items-center gap-3 border-t border-slate-200 bg-slate-50/70 {{ $gutter }} text-sm font-semibold text-slate-900 md:hidden">
                    <input type="checkbox" :checked="allOnPage" @change="togglePage()" class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                    Tick all on this page <span class="font-normal text-slate-500">· {{ $fmt($rowTotal) }} {{ Str::plural($unit, $rowTotal) }}</span>
                </label>
                <ul class="divide-y divide-slate-200 border-y border-slate-200 md:hidden">
                    @foreach ($rows as $r)
                        @php $k = $rowKey($r); @endphp
                        <li wire:key="rowm-{{ $tab }}-{{ $k }}" class="flex items-start gap-2 {{ $gutter }} py-2.5">
                            <label class="-my-1 -ml-2.5 flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center">
                                <input type="checkbox" value="{{ $k }}" x-model="selected" aria-label="Tick {{ $r['student'] }}" class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </label>
                            <x-user-avatar :src="$r['avatar']" :name="$r['student']" class="{{ $studentAvatar }}" />
                            <a href="{{ $r['url'] }}" wire:navigate class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-slate-900">{{ $r['student'] }}</span>
                                    <span class="{{ $badge }} shrink-0 !px-2 !text-[11px] {{ $tab === 'forms' ? $txTone($r['state']) : $clear[$r['status']][0] }}">{{ $r['status'] }}</span>
                                </span>
                                <span class="block truncate text-xs text-slate-500">{{ $r['number'] }} · {{ $r['organization'] }}</span>
                                @if ($tab === 'forms')
                                    @unless ($form)<span class="block truncate text-xs text-slate-700">{{ $r['form'] }}</span>@endunless
                                    <span class="block text-xs text-slate-500"><span class="font-semibold text-[#2A57B4]">{{ $r['code'] }}</span> · {{ $r['submitted']?->format('M j, Y') }}</span>
                                @elseif ($tab === 'organizations')
                                    <span class="block truncate text-xs text-slate-700">{{ $r['requests'] ? $r['requests'] . ' ' . Str::plural('request', $r['requests']) . ': ' . implode(', ', $r['forms']) : 'No forms in this period' }}</span>
                                @elseif ($r['by'])
                                    <span class="block truncate text-xs text-slate-500">by {{ $r['by'] }} · {{ $r['tagged']?->format('M j, Y') }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                @include('livewire.admin.partials.reports-pager')
            @endif
        </section>
        </div>
    </div>

    {{-- ===================== PDF REPORT: REVIEW THE AI DRAFT, THEN DOWNLOAD ===================== --}}
    @if ($pdfPreviewOpen)
        @php
            $pdfAssessment = $this->assessment();
            $sections = [
                'conclusion' => ['Conclusion', 'The overall picture: what is going well and what needs work.'],
                'context' => ['Context and key considerations', 'The size of the period, what changed, and why some areas stand out.'],
                'actions' => ['Recommended actions', 'Practical next steps, high priority first.'],
            ];
            $field = 'w-full rounded-lg border-slate-300 text-sm leading-relaxed text-slate-800 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
        @endphp
        <div class="fixed inset-0 z-40 bg-slate-900/30" wire:click="$set('pdfPreviewOpen', false)" aria-hidden="true"></div>
        <aside class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-2xl ring-1 ring-slate-200 sm:inset-y-3 sm:right-3 sm:w-[34rem] sm:rounded-2xl"
            role="dialog" aria-modal="true" aria-labelledby="pdf-review-title" @keydown.escape.window="$wire.set('pdfPreviewOpen', false)"
            x-data="{ init() { document.body.classList.add('report-pdf-open') }, destroy() { document.body.classList.remove('report-pdf-open') } }">
            <header class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600"><i class="bx bxs-file-pdf text-xl" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <h2 id="pdf-review-title" class="text-base font-semibold text-slate-900">Review the PDF report</h2>
                    <p class="text-xs text-slate-500">{{ $reports[$tab][0] }} · {{ $periodName }}. Check the wording, add who it is for, then download.</p>
                    <p wire:loading.flex wire:target="openPdfPreview,regeneratePdfDraft" class="mt-1.5 items-center gap-1.5 text-xs font-semibold text-[#2A57B4]">
                        <i class="bx bx-loader-alt animate-spin" aria-hidden="true"></i> Drafting the wording with AI…
                    </p>
                </div>
                <button type="button" wire:click="$set('pdfPreviewOpen', false)" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close"><i class="bx bx-x text-2xl" aria-hidden="true"></i></button>
            </header>

            <div class="thin-scroll min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-4 text-sm">
                <p class="flex items-start gap-2 rounded-lg px-3 py-2.5 text-xs {{ ($pdfDraft['ok'] ?? false) ? 'bg-violet-50 text-violet-800' : 'bg-amber-50 text-amber-800' }}">
                    <i class="bx {{ ($pdfDraft['ok'] ?? false) ? 'bx-bot' : 'bx-info-circle' }} mt-0.5 text-base" aria-hidden="true"></i>
                    @if ($pdfDraft['ok'] ?? false)
                        Written by AI from this report’s totals only (no student names). AI can make mistakes: read it before you print.
                    @else
                        The AI wasn’t available, so plain sentences were written from the figures. Edit them freely, or press Regenerate to try the AI again.
                    @endif
                </p>

                {{-- what the rules decided --}}
                <section aria-labelledby="pdf-areas">
                    <h3 id="pdf-areas" class="text-sm font-semibold text-slate-900">Key areas and results</h3>
                    <p class="mb-2 text-xs text-slate-500">Set by fixed rules on the numbers, so the same data always gives the same result. You can edit each explanation.</p>
                    <div class="space-y-3">
                        @foreach ($pdfAssessment as $a)
                            <div wire:key="pdf-area-{{ $a['key'] }}" class="rounded-xl border border-slate-200 p-3">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-sm font-bold {{ $a['result'] === 'ok' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}" aria-label="{{ $a['result'] === 'ok' ? 'On track' : 'Needs attention' }}">{{ $a['result'] === 'ok' ? '✓' : '○' }}</span>
                                    <span class="font-semibold text-slate-900">{{ $a['area'] }}</span>
                                    <span class="tabular-nums text-slate-500">{{ $a['valueText'] }}</span>
                                    @if ($a['priority'])
                                        <span class="ml-auto rounded-full px-2 py-0.5 text-[11px] font-bold {{ $a['priority'] === 'High' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">{{ $a['priority'] }} priority</span>
                                    @endif
                                </div>
                                <label class="sr-only" for="pdf-detail-{{ $a['key'] }}">Explanation for {{ $a['area'] }}</label>
                                <textarea id="pdf-detail-{{ $a['key'] }}" rows="2" wire:model.blur="pdfDraft.details.{{ $a['key'] }}" class="{{ $field }}"></textarea>
                            </div>
                        @endforeach
                    </div>
                </section>

                @foreach ($sections as $key => [$title, $hint])
                    <section aria-labelledby="pdf-{{ $key }}">
                        <h3 id="pdf-{{ $key }}" class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mb-2 text-xs text-slate-500">{{ $hint }}</p>
                        <div class="space-y-2">
                            @foreach ($pdfDraft[$key] ?? [] as $i => $line)
                                <div wire:key="pdf-{{ $key }}-{{ $i }}" class="flex items-start gap-2">
                                    <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#C9A227]" aria-hidden="true"></span>
                                    <label class="sr-only" for="pdf-{{ $key }}-{{ $i }}">{{ $title }}, point {{ $i + 1 }}</label>
                                    <textarea id="pdf-{{ $key }}-{{ $i }}" rows="2" wire:model.blur="pdfDraft.{{ $key }}.{{ $i }}" class="{{ $field }}"></textarea>
                                    <button type="button" wire:click="removeDraftLine('{{ $key }}', {{ $i }})" class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Remove this point"><i class="bx bx-trash" aria-hidden="true"></i></button>
                                </div>
                            @endforeach
                            @if (count($pdfDraft[$key] ?? []) < 6)
                                <button type="button" wire:click="addDraftLine('{{ $key }}')" class="inline-flex h-9 items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-[#2A57B4] hover:bg-blue-50"><i class="bx bx-plus" aria-hidden="true"></i>Add a point</button>
                            @endif
                        </div>
                    </section>
                @endforeach

                {{-- sign-off --}}
                <section aria-labelledby="pdf-signoff" class="space-y-3">
                    <div>
                        <h3 id="pdf-signoff" class="text-sm font-semibold text-slate-900">Sign-off</h3>
                        <p class="text-xs text-slate-500">Printed at the end of the report, with signature lines. Prepared by is you ({{ auth()->user()?->full_name }}).</p>
                    </div>
                    <label class="block text-xs font-medium text-slate-600">Prepared for
                        <input type="text" wire:model.blur="pdfPreparedFor" maxlength="120" placeholder="e.g. Dr. Juan Dela Cruz, Vice President for Student Development" class="{{ $field }} mt-1">
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-xs font-medium text-slate-600">Noted by
                            <input type="text" wire:model.blur="pdfNotedBy" maxlength="120" placeholder="Name" class="{{ $field }} mt-1">
                        </label>
                        <label class="block text-xs font-medium text-slate-600">Position
                            <input type="text" wire:model.blur="pdfNotedByPosition" maxlength="120" placeholder="e.g. Director, OSDAS" class="{{ $field }} mt-1">
                        </label>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3">
                        <input type="checkbox" wire:model.live="pdfIncludeRecords" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        <span><span class="block font-medium text-slate-900">Include the records appendix</span>
                            <span class="block text-xs text-slate-500">Every {{ $unit }} with your Metrics columns, up to {{ \App\Livewire\Admin\Reports::PDF_ROW_CAP }} rows ({{ $fmt($rowTotal) }} now). Leave it off for a short report.</span></span>
                    </label>
                </section>
            </div>

            <footer class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-5 py-3">
                <button type="button" wire:click="regeneratePdfDraft" wire:loading.attr="disabled" class="{{ $outlineBtn }} h-11">
                    <i class="bx bx-refresh text-lg" wire:loading.class="animate-spin" wire:target="regeneratePdfDraft" aria-hidden="true"></i>Regenerate
                </button>
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" class="ml-auto inline-flex h-11 items-center gap-2 rounded-full bg-[#2A57B4] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1E4090] disabled:opacity-60">
                    <i class="bx bx-download text-lg" wire:loading.remove wire:target="downloadPdf" aria-hidden="true"></i>
                    <i class="bx bx-loader-alt animate-spin text-lg" wire:loading wire:target="downloadPdf" aria-hidden="true"></i>
                    Download PDF
                </button>
            </footer>
        </aside>
    @endif

    {{-- ===================== AI INSIGHTS SIDE PANEL ===================== --}}
    <div x-show="ai" x-cloak x-transition.opacity @click="ai = false" class="fixed inset-0 z-40 bg-slate-900/20 lg:hidden" aria-hidden="true"></div>
    <aside x-show="ai" x-cloak @keydown.escape.window="ai = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-2xl ring-1 ring-slate-200 sm:inset-y-3 sm:right-3 sm:w-[27rem] sm:rounded-2xl"
        role="dialog" aria-label="AI insights">
        <header class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#2A57B4] to-violet-500 text-white">{!! $sparkle !!}</span>
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-base font-semibold text-slate-900">{{ $aiTitle ?: 'AI insights' }}</h2>
                <p class="truncate text-[11px] text-slate-500">{{ $reports[$tab][0] }} · {{ $tab === 'clearance' ? ($semesterLabel ?? 'No semester') : $periodName }}</p>
            </div>
            <button type="button" wire:click="newAiChat" :disabled="pending" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 disabled:opacity-40" aria-label="New conversation" title="New conversation">
                <i class="bx bx-edit text-xl" aria-hidden="true"></i>
            </button>
            <button type="button" @click="ai = false" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100" aria-label="Close AI insights">
                <i class="bx bx-x text-2xl" aria-hidden="true"></i>
            </button>
        </header>

        <div x-ref="log" @ai-scroll.window="$nextTick(() => $refs.log.scrollTo({ top: $refs.log.scrollHeight, behavior: 'smooth' }))"
            class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4 text-sm" aria-live="polite">
            @if (! $aiMessages)
                <div x-show="! pending">
                    <p class="text-slate-700">Ask about the report on screen. The AI reads its totals, comparisons, per-form and per-organization figures and the trend, then explains what stands out.</p>
                    <div class="mt-4 space-y-2">
                        @foreach ($aiSuggestions as $q)
                            <button type="button" @click="ask(@js($q))" class="flex w-full items-center gap-2.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-left font-medium text-slate-800 transition hover:bg-slate-50">
                                <span class="text-[#2A57B4]">{!! $sparkle !!}</span>{{ $q }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($aiMessages as $i => $m)
                @if ($m['role'] === 'user')
                    <div wire:key="ai-{{ $i }}" class="flex justify-end">
                        <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-slate-100 px-3.5 py-2 text-slate-900">{{ $m['text'] }}</p>
                    </div>
                @else
                    <div wire:key="ai-{{ $i }}" class="{{ ! empty($m['error']) ? 'rounded-xl bg-rose-50 p-3 text-rose-700' : 'text-slate-700' }}
                        leading-relaxed [&_h3]:mb-1 [&_h3]:mt-3 [&_h3]:font-semibold [&_h3]:text-slate-900 [&_li]:mb-1 [&_ol]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_strong]:font-semibold [&_strong]:text-slate-900 [&_ul]:mb-2 [&_ul]:list-disc [&_ul]:pl-5
                        [&_table]:my-2 [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto [&_table]:rounded-lg [&_table]:bg-slate-50 [&_table]:text-xs [&_td]:border-t [&_td]:border-slate-200 [&_td]:px-2.5 [&_td]:py-2 [&_td]:align-top [&_th]:px-2.5 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold [&_th]:text-slate-900">
                        {!! Str::markdown($m['text'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                @endif
            @endforeach

            {{-- The question being answered, until the answer arrives --}}
            <template x-if="pending">
                <div class="space-y-4">
                    <div class="flex justify-end"><p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-slate-100 px-3.5 py-2 text-slate-900" x-text="pending"></p></div>
                    <div class="flex items-center gap-2 text-slate-500">
                        <span class="flex gap-1" aria-hidden="true">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-[#2A57B4] [animation-delay:-0.3s]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-[#2A57B4] [animation-delay:-0.15s]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-[#2A57B4]"></span>
                        </span>
                        <span class="text-xs">Reading the report…</span>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="ask($refs.q.value.trim()); $refs.q.value = ''" class="px-4 pb-3 pt-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm focus-within:border-slate-400">
                <label class="sr-only" for="ai-question">Ask something</label>
                <textarea id="ai-question" x-ref="q" rows="2" maxlength="500" placeholder="Ask something"
                    @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"
                    class="block w-full resize-none border-0 px-2 py-1 text-sm focus:ring-0"></textarea>
                <div class="flex items-center justify-between px-1">
                    <span class="text-[11px] text-slate-400">Enter to send · Shift+Enter for a new line</span>
                    <button type="submit" :disabled="pending" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-white hover:bg-slate-700 disabled:opacity-40" aria-label="Send">
                        <i class="bx bx-up-arrow-alt text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <p class="mt-2 text-center text-[11px] leading-snug text-slate-500">AI can make mistakes — check figures against the table. Only totals are shared with the AI, never student names.</p>
        </form>
    </aside>
</div>
