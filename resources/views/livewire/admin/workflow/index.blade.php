@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $field = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';

    // DESIGN.md buttons: 8px, 36px tall (44px on touch), 14px/500 sentence case.
    $btnBase = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $btnDanger = $btnBase . ' border border-rose-200 bg-white text-rose-700 hover:bg-rose-50';

    // The status itself is the system-wide pill (<x-status-badge>, the words and colours on Reports);
    // this is only the short note beside it, or null.
    $statusFor = function ($row, $queue) {
        return match (true) {
            $queue === 'signing' => (int) $row->approved_count . '/' . (int) $row->approvals_count . ' signed',
            default => null,
        };
    };

    // Where the transaction is on its way (phone cards): [current step, labels, current tone: ring + text].
    $stageFor = function ($row) use ($tab) {
        return match (true) {
            $tab === 'action_required' => [0, ['Revision', 'Signing', 'Finish'], ['ring-orange-500', 'text-orange-800']],
            $tab === 'issuance' => [2, ['Review', 'Signing', 'Issue'], ['ring-green-600', 'text-green-700']],
            $tab === 'signing' => [1, ['Review', (int) $row->approved_count . '/' . (int) $row->approvals_count . ' signed', 'Finish'], ['ring-purple-600', 'text-purple-800']],
            $row->workflow_status?->value === 'approved' => [2, ['Review', 'Signing', 'Ready'], ['ring-green-600', 'text-green-700']],
            default => [0, ['Review', 'Signing', 'Finish'], ['ring-[#2A57B4]', 'text-[#1E4090]']],
        };
    };

    // The AI check as a compact pill: [text, classes, icon] or null when it does not apply.
    $aiFor = function ($row) {
        if (! ($row->ai_applies ?? true)) {
            return null;
        }
        $rec = $row->ai_findings['recommendation'] ?? null;

        return match (true) {
            $row->ai_flag === 'pending' => ['Checking', 'bg-sky-50 text-[#1E4090] ring-sky-200', 'bx-loader-alt bx-spin'],
            $row->ai_flag === 'review_failed' => ['Failed', 'bg-[#fdece3] text-[#b4471a] ring-orange-200', 'bx-error-circle'],
            $row->ai_flag === null => ['Unchecked', 'bg-slate-100 text-slate-600 ring-slate-200', null],
            $rec === 'reject' => ['Reject', 'bg-rose-50 text-rose-800 ring-rose-200', null],
            $rec === 'request_changes' => ['Revise', 'bg-amber-50 text-amber-800 ring-amber-200', null],
            $row->ai_flag === 'flagged' => ['Flagged', 'bg-amber-50 text-amber-800 ring-amber-200', null],
            default => ['Clear', 'bg-emerald-50 text-emerald-800 ring-emerald-200', null],
        };
    };

    $nameFor = fn ($row) => $row->field_values['student_name'] ?? (($row->user?->full_name ?? '') ?: '-');

    $initialsFor = fn ($row) => \App\Support\Honorifics::initials($nameFor($row));

    // The student's photo, laid over their initials; if it is missing or fails to load, the initials show.
    $avatarFor = fn ($row) => $row->user?->profile_picture ? asset('storage/' . $row->user->profile_picture) : null;

    // Under the student's name: when it was submitted (the code and student number are in the side panel)
    $metaFor = fn ($row) => $row->submitted_at ? 'Submitted ' . $row->submitted_at->format('M j, Y') : '';

    // Priority lives in the Waiting cell alone: one calm type step per level, a soft chip once overdue.
    $waitStyle = fn ($w) => match ($w['level']) {
        'overdue' => '-ml-2 rounded-md bg-red-50 px-2 py-0.5 font-medium text-red-800',
        'urgent' => 'font-medium text-red-700',
        'follow_up' => 'font-medium text-orange-700',
        'due' => 'text-slate-700',
        default => 'text-slate-500',
    };

    // Processing period, in school days: [dot, word, range, what it means]
    $legend = \App\Support\WaitingTime::legend();
    // What each status pill on this page means: the system-wide word and colour (StatusStyle), in the order work moves.
    $statusLegend = collect([
        'submitted' => 'Document is waiting to be reviewed.',
        'under_review' => 'Document is being reviewed.',
        'signing' => 'Document is waiting to be signed by the signatory.',
        'action_required' => 'Document requires revisions before proceeding.',
        'approved' => 'The form has been signed and is ready to be issued to the student.',
    ])->map(function ($meaning, $state) {
        $st = \App\Support\StatusStyle::transaction($state);

        return [\App\Support\StatusStyle::DOTS[$st['tone']] ?? 'bg-slate-400', $st['label'], '', $meaning];
    })->values()->all();

    // What each AI findings pill means: [dot, word, '', meaning], in the pills' colours.
    $aiLegend = [
        ['bg-emerald-500', 'Clear', '', 'Nothing looks wrong with the answers.'],
        ['bg-amber-500', 'Flagged', '', 'Something may be off. Open it and take a closer look.'],
        ['bg-amber-500', 'Revise', '', 'The AI would send it back to the student for revision.'],
        ['bg-rose-500', 'Reject', '', 'The AI would reject it, for example for made-up or unrelated answers.'],
        ['bg-slate-400', 'Unchecked', '', 'Not checked yet. Run it with the robot icon in the side panel.'],
        ['bg-sky-500', 'Checking', '', 'The check is running. It usually takes a few seconds.'],
        ['bg-orange-500', 'Failed', '', 'The check could not finish. Run it again from the side panel.'],
    ];

    $pageIds = $rows->getCollection()->map(fn ($r) => (string) $r->workspace_id)->values();
    $viewingFlagged = $tab === 'attention' && $ai === 'flagged';
@endphp

<div class="mx-auto max-w-[1440px] space-y-5" @if ($polling) wire:poll.5s @endif>

    <h1 class="sr-only">{{ $viewingFlagged ? 'AI Flagged' : $tabs[$tab] }}</h1>

    {{-- Queue cards, in the order a request finishes: Issuance (signed, or issued by a signatory: the office issues
         it), Signing, Revision, Review, then the AI's Flagged shortlist. [key, label, icon, hint] --}}
    @php
        $cards = [
            ['issuance', 'For Issuance', 'bx-badge-check', 'Signed, ready to issue'],
            ['signing', 'For Signing', 'bx-pen', 'With the signers'],
            ['action_required', 'For Revision', 'bx-hourglass', 'With the student to fix'],
            ['attention', 'For Review', 'bx-time-five', 'For the office to check'],
        ];
    @endphp
    <nav class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5" aria-label="Queues">
        @foreach ($cards as [$key, $label, $icon, $hint])
            @php
                $current = $tab === $key && ! $viewingFlagged;
                $late = $lateCounts[$key] ?? 0;
                $oldest = $faces[$key]->first();
            @endphp
            <button type="button" wire:click="openQueue('{{ $key }}')" wire:key="card-{{ $key }}"
                    @if ($current) aria-current="page" @endif
                    class="{{ \App\Support\SummaryCard::BOX }} {{ \App\Support\SummaryCard::tone($current) }} {{ $focus }}">
                <i class="bx {{ $icon }} {{ \App\Support\SummaryCard::ICON }}" aria-hidden="true"></i>
                <span class="flex items-center gap-2.5">
                    <span class="text-2xl font-semibold leading-none tracking-[-0.02em] tabular-nums text-slate-900">{{ $counts[$key] }}</span>
                    <span class="flex -space-x-1.5" aria-hidden="true">
                        @foreach ($faces[$key]->take(3) as $f)
                            <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-white text-[9px] font-semibold tracking-tight text-[#1E4090] ring-2 ring-white/90">
                                {{ $initialsFor($f) }}
                                @if ($avatarFor($f))
                                    <img src="{{ $avatarFor($f) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                @endif
                            </span>
                        @endforeach
                    </span>
                </span>
                <span class="mt-2 block text-sm font-semibold leading-tight text-slate-900 sm:text-[15px]">{{ $label }}</span>
                <span class="mt-0.5 block truncate text-xs text-slate-600">
                    @if ($late)
                        <span class="font-medium text-red-700">{{ $late }} Overdue</span> ·
                    @endif
                    {{ $oldest?->submitted_at ? 'Since ' . $oldest->submitted_at->format('M j') : $hint }}
                </span>
            </button>
        @endforeach

        {{-- The AI's shortlist: the review queue, narrowed to what it wants a person to look at --}}
        <button type="button" wire:click="showFlagged" wire:key="card-flagged"
                @if ($viewingFlagged) aria-current="page" @endif
                class="{{ \App\Support\SummaryCard::BOX }} {{ \App\Support\SummaryCard::tone($viewingFlagged) }} {{ $focus }}">
            <i class="bx bx-bot {{ \App\Support\SummaryCard::ICON }}" aria-hidden="true"></i>
            <span class="flex items-center gap-2.5">
                <span class="text-2xl font-semibold leading-none tracking-[-0.02em] tabular-nums text-slate-900">{{ $flaggedCount }}</span>
                <span class="flex -space-x-1.5" aria-hidden="true">
                    @foreach ($flaggedFaces->take(3) as $f)
                        <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-white text-[9px] font-semibold tracking-tight text-[#1E4090] ring-2 ring-white/90">
                            {{ $initialsFor($f) }}
                            @if ($avatarFor($f))
                                <img src="{{ $avatarFor($f) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                            @endif
                        </span>
                    @endforeach
                </span>
            </span>
            <span class="mt-2 block text-sm font-semibold leading-tight text-slate-900 sm:text-[15px]">AI Flagged</span>
            <span class="mt-0.5 block truncate text-xs text-slate-600">{{ $flaggedCount ? 'AI says check these' : 'Nothing flagged' }}</span>
        </button>
    </nav>

    {{-- List and, when a row is selected, the panel beside it (a drawer on smaller screens) --}}
    <div class="{{ $panel ? '2xl:grid 2xl:grid-cols-[minmax(0,1fr)_420px] 2xl:items-start 2xl:gap-5' : '' }}">

        <div class="cq min-w-0 space-y-3">
            {{-- One row: Filters (a pop-up like Clearance Monitoring's), the search, the one primary action.
                 The page still scrolls while the pop-up is open: its backdrop lets the wheel and touch through,
                 and a click outside closes it. Sorting is on the column headers. --}}
            <div class="flex items-center gap-2">
                @php $fsel = 'h-9 w-full py-0 text-sm [@media(pointer:coarse)]:h-11 ' . $field; @endphp
                <x-admin.filters-popup :count="$activeFilters" reset="resetFilters">
                        <label class="block min-w-0">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Processing period</span>
                            <select wire:model.live="elapsed" class="{{ $fsel }}">
                                <option value="">All</option>
                                @foreach ($filterOptions['elapsed'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block min-w-0">
                            <span class="mb-1 block text-xs font-medium text-slate-600">AI findings</span>
                            <select wire:model.live="ai" class="{{ $fsel }}">
                                @foreach ($aiFilters as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="col-span-2 block">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Document</span>
                            <select wire:model.live="docType" class="{{ $fsel }}">
                                <option value="">All</option>
                                @foreach ($filterOptions['docs'] as $key => $label)
                                    <option value="{{ $key }}">{{ \Illuminate\Support\Str::limit($label, 48) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="col-span-2 block">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Program</span>
                            <select wire:model.live="program" class="{{ $fsel }}">
                                <option value="">All</option>
                                @foreach ($filterOptions['programs'] as $id => $name)
                                    <option value="{{ $id }}">{{ \Illuminate\Support\Str::limit($name, 48) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="col-span-2 block">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Year level</span>
                            <select wire:model.live="year" class="{{ $fsel }}">
                                <option value="">All</option>
                                @foreach ($filterOptions['years'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block min-w-0">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Submitted from</span>
                            <input type="date" wire:model.live="dateFrom" max="{{ now()->toDateString() }}" class="{{ $fsel }}">
                        </label>
                        <label class="block min-w-0">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Submitted to</span>
                            <input type="date" wire:model.live="dateTo" max="{{ now()->toDateString() }}" class="{{ $fsel }}">
                        </label>
                </x-admin.filters-popup>

                <div class="relative min-w-0 flex-1">
                    <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, code or document"
                           aria-label="Search by name, student number, code or document type"
                           class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $field }}">
                </div>
                <a href="{{ route('admin.workflow.issue') }}" wire:navigate class="{{ $btnPrimary }} shrink-0" title="Issue a document">
                    <i class="bx bx-plus text-base" aria-hidden="true"></i>
                    <span class="sm:hidden">Issue</span>
                    <span class="hidden sm:inline">Issue a document</span>
                </a>
            </div>
            {{-- Table, once the list is wide enough --}}
            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity cq-md:block"
                 wire:loading.class="opacity-60" wire:loading.attr="aria-busy"
                 wire:target="search, sort, setTab, setAi, showFlagged, sortBy, gotoPage, nextPage, previousPage, elapsed, ai, docType, program, year, dateFrom, dateTo, resetFilters">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs">
                        <tr>
                            <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                            <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Document</th>
                            <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" class="!py-2.5">
                                <x-admin.legend-tip label="What the statuses mean" note="Indicates the current progress of the document." :items="$statusLegend" />
                            </x-admin.sort-th>
                            <x-admin.sort-th key="risk" label="AI findings" :sort="$sortKey" :dir="$sortDir" class="!py-2.5">
                                <x-admin.legend-tip label="What the AI findings mean" note="The AI reads the answers before you do. It only suggests: you make the decision." :items="$aiLegend" />
                            </x-admin.sort-th>
                            <x-admin.sort-th key="waiting" label="Processing period" :sort="$sortKey" :dir="$sortDir" class="!py-2.5">
                                <x-admin.legend-tip label="What the processing period means" note="Number of school days the document has been waiting. Weekends do not count." :items="$legend" />
                            </x-admin.sort-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $doc = DocumentRegistry::find($row->document_type);
                                $statusNote = $statusFor($row, $tab);
                                $aiPill = $aiFor($row);
                                $isOpen = $selected === $row->workspace_id;
                            @endphp
                            <tr wire:key="row-{{ $row->workspace_id }}" wire:click="select({{ $row->workspace_id }})"
                                class="cursor-pointer transition-colors {{ $isOpen ? 'bg-[#f0f4fb]' : 'hover:bg-slate-50' }}">
                                <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ ($rows->firstItem() ?? 1) + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                            {{ $initialsFor($row) }}
                                            @if ($avatarFor($row))
                                                <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            <button type="button" wire:click.stop="select({{ $row->workspace_id }})"
                                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="wf-panel"
                                                    class="block max-w-[220px] truncate rounded text-left font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">
                                                {{ $nameFor($row) }}
                                            </button>
                                            @if ($metaFor($row) !== '')
                                                <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-[240px] truncate text-slate-700" title="{{ $doc?->name() ?? $row->document_type }}">{{ $doc?->name() ?? $row->document_type }}</p>
                                    @if ($row->issuer)
                                        <p class="mt-0.5 max-w-[240px] truncate text-xs text-slate-500">Issued by {{ $row->issuer }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-status-badge :workspace="$row" />
                                    @if ($statusNote)<p class="mt-1 text-xs tabular-nums text-slate-500">{{ $statusNote }}</p>@endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($aiPill)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aiPill[1] }}"
                                              @if (! empty($row->ai_findings['summary'])) title="{{ $row->ai_findings['summary'] }}" @endif>
                                            @if ($aiPill[2]) <i class="bx {{ $aiPill[2] }} text-sm" aria-hidden="true"></i> @endif
                                            {{ $aiPill[0] }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">Filled out by staff</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-dm.time-elapsed :w="$row->waiting" class="{{ $waitStyle($row->waiting) }}" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">
                                    {{ trim($search) !== '' || $ai !== '' ? 'No matching records found.' : 'Nothing waiting here.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cards on narrow lists: tap to open the panel --}}
            <ul class="grid gap-3 transition-opacity cq-sm:grid-cols-2 cq-md:hidden" aria-label="{{ $tabs[$tab] }}"
                wire:loading.class="opacity-60" wire:loading.attr="aria-busy"
                wire:target="search, sort, setTab, setAi, showFlagged, sortBy, gotoPage, nextPage, previousPage, elapsed, ai, docType, program, year, dateFrom, dateTo, resetFilters">
                @forelse ($rows as $row)
                    @php
                        $doc = DocumentRegistry::find($row->document_type);
                        [$stage, $stageLabels, [$stageRing, $stageText]] = $stageFor($row);
                        $aiPill = $aiFor($row);
                        $isOpen = $selected === $row->workspace_id;
                    @endphp
                    <li wire:key="mcard-{{ $row->workspace_id }}">
                        <button type="button" wire:click="select({{ $row->workspace_id }})"
                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="wf-panel"
                                class="flex h-full w-full flex-col rounded-2xl border bg-white p-4 text-left transition-colors {{ $focus }}
                                    {{ $isOpen ? 'border-[#2A57B4] bg-[#f0f4fb]' : 'border-slate-200 active:bg-slate-50' }}">
                            <span class="flex w-full items-start gap-3">
                                <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                                    {{ $initialsFor($row) }}
                                    @if ($avatarFor($row))
                                        <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[15px] font-semibold leading-snug text-slate-900">{{ $nameFor($row) }}</span>
                                    @if ($metaFor($row) !== '')
                                        <span class="mt-0.5 block truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</span>
                                    @endif
                                </span>
                                @if ($aiPill)
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $aiPill[1] }}">
                                        @if ($aiPill[2]) <i class="bx {{ $aiPill[2] }} text-xs" aria-hidden="true"></i> @endif
                                        <span class="sr-only">AI check:</span> {{ $aiPill[0] }}
                                    </span>
                                @endif
                            </span>

                            <span class="mt-3 block text-sm leading-snug text-slate-700">{{ $doc?->name() ?? $row->document_type }}</span>

                            <ol class="mt-4 grid w-full grid-cols-3" aria-label="Progress">
                                @foreach ($stageLabels as $i => $label)
                                    @php $state = $i < $stage ? 'done' : ($i === $stage ? 'current' : 'todo'); @endphp
                                    <li class="relative flex flex-col items-center text-center" @if ($state === 'current') aria-current="step" @endif>
                                        @if ($i > 0)
                                            <span class="absolute right-1/2 top-[7px] h-0.5 w-full {{ $i <= $stage ? 'bg-[#2A57B4]' : 'bg-slate-200' }}" aria-hidden="true"></span>
                                        @endif
                                        <span class="relative z-10 h-4 w-4 rounded-full {{ $state === 'done' ? 'bg-[#2A57B4]' : ($state === 'current' ? 'bg-white ring-[3px] ' . $stageRing : 'bg-white ring-2 ring-slate-200') }}" aria-hidden="true"></span>
                                        <span class="mt-1.5 px-0.5 text-[11px] leading-tight {{ $state === 'current' ? 'font-semibold ' . $stageText : 'text-slate-500' }}">
                                            <span class="sr-only">{{ $state === 'done' ? 'Done: ' : ($state === 'current' ? 'Now: ' : 'Next: ') }}</span>{{ $label }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>

                            <span class="mt-4 flex w-full items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm">
                                <x-dm.time-elapsed :w="$row->waiting" class="{{ $waitStyle($row->waiting) }}" />
                                <span class="inline-flex items-center text-xs font-medium text-[#2A57B4]">Details <i class="bx bx-chevron-right text-base" aria-hidden="true"></i></span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 cq-sm:col-span-2">
                        {{ trim($search) !== '' || $ai !== '' ? 'No matching records found.' : 'Nothing waiting here.' }}
                    </li>
                @endforelse
            </ul>

            @if ($rows->hasPages())
                <div>{{ $rows->links() }}</div>
            @endif
        </div>

        {{-- Side panel --}}
        @if ($panel)
            @include('livewire.admin.workflow._panel', compact('panel', 'focus', 'btnPrimary', 'btnSecondary', 'btnDanger', 'statusFor', 'waitStyle', 'nameFor', 'initialsFor', 'avatarFor'))
        @endif
    </div>

    {{-- Request changes / Reject, from the panel --}}
    @if ($decision && $panel)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="closeDecision" wire:click.self="closeDecision"
             role="dialog" aria-modal="true" aria-labelledby="decision-title" aria-describedby="decision-lead">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                 x-data x-init="$nextTick(() => $refs.msg.focus())">
                <h2 id="decision-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">
                    {{ $decision === 'request' ? 'For Revision' : 'For Rejection' }}
                </h2>
                <p id="decision-lead" class="mt-1 text-sm text-slate-500">
                    {{ $nameFor($panel['ws']) }} · {{ $panel['document']?->name() }}.
                    {{ $decision === 'request'
                        ? 'The student sees this message and can edit and resubmit.'
                        : 'The student sees this reason. Rejection is final; they would need to start a new transaction.' }}
                </p>

                <label for="decision-message" class="mt-4 block text-sm font-medium text-slate-800">
                    {{ $decision === 'request' ? 'What needs to change?' : 'Why is it being rejected?' }} <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <textarea id="decision-message" x-ref="msg" wire:model="message" rows="5" maxlength="500" required
                          aria-describedby="decision-hint"
                          class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm"></textarea>
                <p id="decision-hint" class="mt-1 text-xs text-slate-500">
                    @if ($message !== '' && $message === ($panel['ws']->ai_findings['message'] ?? null))
                        Filled out from the AI suggestion. Read it and edit before sending.
                    @else
                        Up to 500 characters.
                    @endif
                </p>

                @error('action') <p class="mt-2 text-sm font-medium text-rose-700" role="alert">{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDecision" class="{{ $btnSecondary }}">Cancel</button>
                    <button type="button" wire:click="submitDecision" wire:loading.attr="disabled" wire:target="submitDecision"
                            class="{{ $decision === 'reject' ? $btnBase . ' bg-rose-600 text-white shadow-sm hover:bg-rose-700' : $btnPrimary }}">
                        {{ $decision === 'request' ? 'Send request' : 'Reject transaction' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
