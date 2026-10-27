@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');

    $datePresets = ['7d' => '7D', '30d' => '30D', '90d' => '3M', '180d' => '6M', '365d' => '1Y', 'all' => 'All', 'custom' => 'Custom'];

    $tabs = [
        'overview' => ['Overview', 'bx-grid-alt'],
        'documents' => ['Documents', 'bx-file'],
        'appointments' => ['Appointments', 'bx-calendar'],
        'students' => ['Students', 'bx-group'],
        'logbook' => ['Logbook', 'bx-book-open'],
    ];

    $wf = $report['workflow'];
    $ap = $report['appointments'];
    $cl = $report['clearance'];
    $ve = $report['verification'];
    $us = $report['users'];
    $lb = $report['logbook'];
    $cards = $report['overview']['cards'];

    $rangeLabel = $insights['from']->format('M j, Y') . ' – ' . $insights['to']->format('M j, Y');
    $termLabel = trim(($semester ?: 'All semesters') . ' · ' . ($academicYear ? 'AY ' . $academicYear : 'All years'));

    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100) : 0;
    $attended = $ap['by_status']['attended'] ?? 0;
    $missed = $ap['by_status']['missed'] ?? 0;

    // label => count maps become breakdown rows
    $rows = fn (array $map, ?callable $label = null) => collect($map)
        ->map(fn ($n, $k) => ['label' => $label ? $label($k) : ucwords(str_replace('_', ' ', (string) $k)), 'count' => (int) $n])
        ->sortByDesc('count')->values()->all();
    $one = [['key' => 'count', 'label' => 'Count', 'color' => '#2A57B4']];
    $split = [
        ['key' => 'completed', 'label' => 'Completed', 'color' => '#2A57B4'],
        ['key' => 'open', 'label' => 'In progress', 'color' => '#eb6834'],
    ];

    // Clearance by organization, status colours from the dataviz status palette (always shown with labels).
    $orgClearance = collect($cl['by_organization'])
        ->map(fn ($s, $org) => ['label' => $org, 'cleared' => (int) ($s['Cleared'] ?? 0), 'pending' => (int) ($s['Pending'] ?? 0), 'uncleared' => (int) ($s['Uncleared'] ?? 0)])
        ->sortByDesc(fn ($r) => $r['cleared'] + $r['pending'] + $r['uncleared'])
        ->take(10)->values()->all();

    $stat = 'rounded-2xl border border-gray-200/70 bg-white p-4';
    $statLabel = 'text-sm text-gray-500';
    $statValue = 'mt-1 text-2xl font-bold tabular-nums tracking-tight text-gray-900';
    $statNote = 'mt-0.5 text-xs text-gray-500';
    $card = 'rounded-2xl border border-gray-200/70 bg-white';
    $scope = 'inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600';
    $noScrollbar = '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden';
    $select = 'w-full rounded-full border-gray-200 bg-gray-50 py-2 pl-3.5 pr-9 text-sm font-medium text-gray-700 focus:border-[#2A57B4] focus:ring-[#2A57B4]';
@endphp

<div class="mx-auto max-w-7xl pb-12" x-data="{ tab: 'overview' }">

    <x-admin.page-header
        title="Reports"
        description="How documents, appointments and students are moving, for the period you pick."
        :crumbs="['Admin' => $homeUrl, 'Reports' => null]">
        <x-slot:actions>
            <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-60 sm:flex-none">
                <i class='bx bxs-file-pdf text-base text-rose-600' wire:loading.remove wire:target="exportPdf" aria-hidden="true"></i>
                <i class='bx bx-loader-alt animate-spin text-base' wire:loading wire:target="exportPdf" aria-hidden="true"></i>
                PDF
            </button>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-60 sm:flex-none">
                <i class='bx bxs-spreadsheet text-base text-emerald-600' wire:loading.remove wire:target="exportExcel" aria-hidden="true"></i>
                <i class='bx bx-loader-alt animate-spin text-base' wire:loading wire:target="exportExcel" aria-hidden="true"></i>
                Excel
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- ============ FILTERS ============ --}}
    {{-- Phones: presets scroll on one row, the rest stack two-up. Wide: everything on one line. --}}
    <section class="{{ $card }} p-3" aria-label="Filters">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <div class="flex min-w-0 gap-1 overflow-x-auto rounded-full bg-gray-100 p-1 {{ $noScrollbar }}" role="group" aria-label="Period">
                @foreach ($datePresets as $key => $label)
                    <button type="button" wire:click="setDatePreset('{{ $key }}')" aria-pressed="{{ $datePreset === $key ? 'true' : 'false' }}"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-semibold transition {{ $datePreset === $key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($datePreset === 'custom')
                <div class="grid grid-cols-2 gap-2 xl:flex xl:items-center">
                    <label class="min-w-0">
                        <span class="sr-only">From</span>
                        <input type="date" wire:model.live="dateFrom" class="{{ $select }} pr-3" max="{{ $dateTo }}">
                    </label>
                    <label class="min-w-0">
                        <span class="sr-only">To</span>
                        <input type="date" wire:model.live="dateTo" class="{{ $select }} pr-3" min="{{ $dateFrom }}">
                    </label>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2 xl:ml-auto xl:flex xl:items-center">
                <label class="min-w-0">
                    <span class="sr-only">Academic year</span>
                    <select wire:model.live="academicYear" class="{{ $select }}">
                        <option value="">All years</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year }}">AY {{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="min-w-0">
                    <span class="sr-only">Semester</span>
                    <select wire:model.live="semester" class="{{ $select }}">
                        <option value="">All semesters</option>
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem }}">{{ $sem }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 px-1 text-xs text-gray-500">
            <span class="{{ $scope }}"><i class='bx bx-calendar' aria-hidden="true"></i>{{ $rangeLabel }}</span>
            <span class="{{ $scope }}"><i class='bx bx-book-bookmark' aria-hidden="true"></i>{{ $termLabel }}</span>
            <button type="button" wire:click="resetFilters" class="ml-auto font-semibold text-[#2A57B4] hover:underline">Reset</button>
            <span wire:loading class="text-gray-400">Updating…</span>
        </div>
    </section>

    {{-- ============ TABS ============ --}}
    <nav aria-label="Report sections"
         x-effect="tab; $nextTick(() => $el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }))"
         class="mt-5 flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200 {{ $noScrollbar }}">
        @foreach ($tabs as $key => [$label, $icon])
            <button type="button" @click="tab = '{{ $key }}'"
                :aria-current="tab === '{{ $key }}' ? 'page' : null"
                class="-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-0.5 pb-3 pt-1 text-sm font-semibold transition"
                :class="tab === '{{ $key }}' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-800'">
                <i class='bx {{ $icon }} text-base' aria-hidden="true"></i>{{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="mt-5" wire:loading.class="opacity-60" wire:target="setDatePreset,dateFrom,dateTo,academicYear,semester,resetFilters">

        {{-- ================= OVERVIEW ================= --}}
        <div x-show="tab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <div class="{{ $stat }}">
                    <p class="{{ $statLabel }}">Completion rate</p>
                    <p class="{{ $statValue }}">{{ $wf['completion_rate'] }}%</p>
                    <p class="{{ $statNote }}">{{ $wf['completed'] }} of {{ $wf['total'] }} digital documents</p>
                </div>
                <div class="{{ $stat }}">
                    <p class="{{ $statLabel }}">Time to issue</p>
                    <p class="{{ $statValue }}">{{ $wf['avg_issue'] }}</p>
                    <p class="{{ $statNote }}">Average, submitted to completed</p>
                </div>
                <div class="{{ $stat }}">
                    <p class="{{ $statLabel }}">Attendance</p>
                    <p class="{{ $statValue }}">{{ $pct($attended, $attended + $missed) }}%</p>
                    <p class="{{ $statNote }}">{{ $attended }} attended · {{ $missed }} missed</p>
                </div>
                <div class="{{ $stat }}">
                    <p class="{{ $statLabel }}">Cleared</p>
                    <p class="{{ $statValue }}">{{ $cl['clearance_rate'] }}%</p>
                    <p class="{{ $statNote }}">{{ $cl['by_status']['Cleared'] ?? 0 }} of {{ $cl['total'] }} tagged students</p>
                </div>
                <div class="{{ $stat }} col-span-2 md:col-span-1">
                    <p class="{{ $statLabel }}">Verified</p>
                    <p class="{{ $statValue }}">{{ $ve['verified_rate'] }}%</p>
                    <p class="{{ $statNote }}">{{ $ve['by_status']['verified'] ?? 0 }} of {{ $ve['total'] }} e-slips</p>
                </div>
            </div>

            @include('livewire.partials.insights.trend', [
                'title' => 'Activity',
                'note' => 'Compared with the same number of days just before.',
                'metrics' => $insights['trend'],
                'picker' => false,
            ])

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Most requested documents',
                    'note' => 'Submitted in this period.',
                    'series' => $split,
                    'groups' => ['All' => array_slice($insights['byType'], 0, 6)],
                    'empty' => 'No documents were submitted in this period.',
                ])

                {{-- Needs attention --}}
                <section class="{{ $card }} p-5">
                    <h2 class="text-lg font-bold tracking-tight text-gray-900">Needs attention</h2>
                    <p class="mt-0.5 text-sm text-gray-500">The longest-waiting documents and where they are stuck.</p>
                    <ul class="mt-4 divide-y divide-gray-100">
                        @forelse (array_slice($wf['stuck'], 0, 6) as $r)
                            <li class="flex items-center gap-3 py-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700"><i class='bx bx-time-five text-lg' aria-hidden="true"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $r['name'] }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $r['status'] }} · submitted {{ $r['since'] }}</p>
                                </div>
                                <a href="{{ route('admin.workflow.show', $r['id']) }}" wire:navigate class="shrink-0 font-mono text-xs font-semibold text-[#2A57B4] hover:underline">{{ $r['code'] }}</a>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-gray-500">Nothing is waiting. Every document in this period is finished.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>

        {{-- ================= DOCUMENTS ================= --}}
        <div x-show="tab === 'documents'" x-cloak class="space-y-6">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Submitted</p><p class="{{ $statValue }}">{{ number_format($wf['total']) }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Completed</p><p class="{{ $statValue }}">{{ number_format($wf['completed']) }}</p><p class="{{ $statNote }}">{{ $wf['completion_rate'] }}%</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">In progress</p><p class="{{ $statValue }}">{{ number_format($wf['in_progress']) }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Rejected</p><p class="{{ $statValue }}">{{ number_format($wf['rejected']) }}</p><p class="{{ $statNote }}">{{ $wf['rejections']['review'] }} by VPSD · {{ $wf['rejections']['signer'] }} by signers</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Voided</p><p class="{{ $statValue }}">{{ number_format($wf['voided']) }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Time to issue</p><p class="{{ $statValue }}">{{ $wf['avg_issue'] }}</p><p class="{{ $statNote }}">Average</p></div>
            </div>
            <p class="-mt-3 text-xs text-gray-500">Digital documents submitted in this period. Paper forms are counted in "Most requested documents".</p>

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Where documents stand',
                    'note' => 'Current status of the documents submitted in this period.',
                    'series' => $one,
                    'groups' => ['All' => $rows($wf['by_status'], fn ($k) => $k)],
                    'empty' => 'No digital documents in this period.',
                ])
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Most requested documents',
                    'note' => 'Digital and paper forms submitted in this period.',
                    'series' => $split,
                    'groups' => ['All' => $insights['byType']],
                    'empty' => 'No documents were submitted in this period.',
                ])
            </div>

            {{-- Signer performance: table when wide, cards when narrow --}}
            <section class="{{ $card }} overflow-hidden">
                <div class="p-5 pb-3">
                    <h2 class="text-lg font-bold tracking-tight text-gray-900">Signer performance</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Response time runs from when a step opened to when it was signed or rejected.</p>
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-sm">
                        <thead class="border-y border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">Role</th>
                                <th class="px-4 py-3 text-right font-medium">Signed</th>
                                <th class="px-4 py-3 text-right font-medium">Rejected</th>
                                <th class="px-4 py-3 text-right font-medium">Waiting</th>
                                <th class="px-4 py-3 text-left font-medium">Avg. response</th>
                                <th class="px-5 py-3 text-left font-medium">Oldest waiting</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($wf['signers'] as $s)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $s['label'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $s['signed'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $s['rejected'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums {{ $s['pending'] ? 'font-semibold text-amber-700' : '' }}">{{ $s['pending'] }}</td>
                                    <td class="px-4 py-3">{{ $s['avg_response'] }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $s['oldest_pending'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">No signing activity in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <ul class="divide-y divide-gray-100 border-t border-gray-100 md:hidden">
                    @forelse ($wf['signers'] as $s)
                        <li class="px-4 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 font-semibold text-gray-900">{{ $s['label'] }}</p>
                                @if ($s['pending'])
                                    <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $s['pending'] }} waiting</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $s['signed'] }} signed · {{ $s['rejected'] }} rejected · avg. {{ $s['avg_response'] }}</p>
                            @if ($s['pending'])
                                <p class="text-xs text-gray-400">Oldest waiting {{ $s['oldest_pending'] }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-500">No signing activity in this period.</li>
                    @endforelse
                </ul>
            </section>

            @include('livewire.partials.insights.active-times', [
                'title' => 'When documents arrive',
                'note' => 'Submissions in this period. Based on',
                'times' => $insights['times'],
                'summary' => 'Busiest windows',
            ])
        </div>

        {{-- ================= APPOINTMENTS ================= --}}
        <div x-show="tab === 'appointments'" x-cloak class="space-y-6">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Booked</p><p class="{{ $statValue }}">{{ number_format($ap['total']) }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Attended</p><p class="{{ $statValue }}">{{ number_format($attended) }}</p><p class="{{ $statNote }}">{{ $pct($attended, $attended + $missed) }}% of finished visits</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Missed</p><p class="{{ $statValue }}">{{ $ap['missed_rate'] }}%</p><p class="{{ $statNote }}">{{ $missed }} no-shows</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Rescheduled</p><p class="{{ $statValue }}">{{ number_format($ap['reschedule_count']) }}</p></div>
            </div>

            @include('livewire.partials.insights.trend', [
                'title' => 'Office visits',
                'note' => 'Appointments by the day of the visit.',
                'metrics' => $insights['visits'],
                'picker' => false,
            ])

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Outcome',
                    'note' => 'Every appointment booked in this period.',
                    'series' => $one,
                    'groups' => ['All' => $rows($ap['by_status'])],
                    'empty' => 'No appointments in this period.',
                ])
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Morning or afternoon',
                    'note' => 'Which session students book.',
                    'series' => $one,
                    'groups' => ['All' => $rows($ap['by_session'])],
                    'empty' => 'No appointments in this period.',
                ])
            </div>
        </div>

        {{-- ================= STUDENTS ================= --}}
        <div x-show="tab === 'students'" x-cloak class="space-y-6">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Students and officers</p><p class="{{ $statValue }}">{{ number_format($cards['total_students']) }}</p><p class="{{ $statNote }}">{{ number_format($cards['active_accounts']) }} active</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">New accounts</p><p class="{{ $statValue }}">{{ number_format($us['total']) }}</p><p class="{{ $statNote }}">Created in this period</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Cleared</p><p class="{{ $statValue }}">{{ $cl['clearance_rate'] }}%</p><p class="{{ $statNote }}">{{ $termLabel }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Verification waiting</p><p class="{{ $statValue }}">{{ number_format($cards['pending_verifications']) }}</p><p class="{{ $statNote }}">{{ $ve['verified_rate'] }}% verified</p></div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Clearance by organization',
                    'note' => 'Share cleared, for ' . $termLabel . '.',
                    'series' => [
                        ['key' => 'cleared', 'label' => 'Cleared', 'color' => '#0ca30c'],
                        ['key' => 'pending', 'label' => 'Pending', 'color' => '#fab219'],
                        ['key' => 'uncleared', 'label' => 'Uncleared', 'color' => '#d03b3b'],
                    ],
                    'groups' => ['All' => $orgClearance],
                    'format' => fn ($r, $total) => ($total ? round($r['cleared'] / $total * 100) : 0) . '%',
                    'empty' => 'No students have been tagged for this term.',
                ])
                <div class="space-y-6">
                    @include('livewire.partials.insights.breakdown', [
                        'title' => 'Clearance status',
                        'series' => $one,
                        'groups' => ['All' => $rows($cl['by_status'], fn ($k) => $k)],
                        'empty' => 'No students have been tagged for this term.',
                    ])
                    @include('livewire.partials.insights.breakdown', [
                        'title' => 'Account verification',
                        'note' => 'E-slips submitted for ' . $termLabel . '.',
                        'series' => $one,
                        'groups' => ['All' => $rows($ve['by_status'])],
                        'empty' => 'No e-slips submitted for this term.',
                    ])
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'New accounts by program',
                    'note' => 'Top programs among accounts created in this period.',
                    'series' => $one,
                    'groups' => ['All' => $rows($us['by_program'], fn ($k) => $k)],
                    'empty' => 'No new accounts in this period.',
                ])
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'New accounts by year level',
                    'series' => $one,
                    'groups' => ['Year level' => collect($us['by_year_level'])->map(fn ($n, $y) => ['label' => 'Year ' . $y, 'count' => (int) $n])->values()->all(),
                                 'Role' => $rows($us['by_role'], fn ($k) => ucfirst($k))],
                    'empty' => 'No new accounts in this period.',
                ])
            </div>
        </div>

        {{-- ================= LOGBOOK ================= --}}
        <div x-show="tab === 'logbook'" x-cloak class="space-y-6">
            <div class="grid grid-cols-2 gap-3">
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Logbook uploads</p><p class="{{ $statValue }}">{{ number_format($lb['total_uploads']) }}</p></div>
                <div class="{{ $stat }}"><p class="{{ $statLabel }}">Entries</p><p class="{{ $statValue }}">{{ number_format($lb['total_entries']) }}</p></div>
            </div>
            <p class="-mt-3 text-xs text-gray-500">Entries from the uploaded paper logbooks dated in the period above. Academic year and semester do not apply: logbook rows only carry a date.</p>

            <div class="grid gap-6 lg:grid-cols-2">
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Why students visit',
                    'series' => $one,
                    'groups' => ['All' => $rows($lb['by_purpose'], fn ($k) => $k)],
                    'empty' => 'No logbook entries are dated in this period.',
                ])
                @include('livewire.partials.insights.breakdown', [
                    'title' => 'Visits by program',
                    'series' => $one,
                    'groups' => ['All' => $rows($lb['by_program'], fn ($k) => $k)],
                    'empty' => 'No logbook entries are dated in this period.',
                ])
            </div>

            @include('livewire.partials.insights.columns', [
                'title' => 'Entries per month',
                'columns' => array_map('intval', $lb['monthly']),
                'unit' => ['entry', 'entries'],
                'empty' => 'No logbook entries are dated in this period.',
            ])
        </div>
    </div>
</div>
