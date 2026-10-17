@php
    $datePresets = ['7d' => '7D', '30d' => '30D', '90d' => '3M', '180d' => '6M', '365d' => '1Y', 'all' => 'All'];
    $tabs = [
        'overview' => ['At a Glance', 'bx-home-alt'],
        'users' => ['Users', 'bx-group'],
        'transactions' => ['Transactions', 'bx-transfer'],
        'appointments' => ['Appointments', 'bx-calendar'],
        'clearance' => ['Clearance', 'bx-check-shield'],
        'verification' => ['Verification', 'bx-id-card'],
        'logbook' => ['Logbook', 'bx-book-open'],
    ];

    // Logbook is included in the date-range scope now: AdminReportService::logbookReport()
    // filters entries by date_from/date_to same as everything else (an entry with an
    // unparseable free-text date is kept regardless, since we genuinely don't know when
    // it happened — that's not the same as the tab being exempt from the filter).
    $activeFilterChips = [];
    $activeFilterCount = 0;
    if ($datePreset !== 'all') {
        $activeFilterChips[] = [
            'label' => \Carbon\Carbon::parse($dateFrom ?? $report['generated_at'])->format('M d, Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y'),
            'scope' => 'Users, Transactions, Appointments, Logbook',
        ];
        $activeFilterCount++;
    }
    if ($academicYear || $semester) {
        $activeFilterChips[] = [
            'label' => trim(($academicYear ?: 'All years') . ' · ' . ($semester ?: 'All semesters')),
            'scope' => 'Clearance, Verification',
        ];
        $activeFilterCount++;
    }
@endphp

<div class="space-y-6" x-data="{ tab: 'overview', filtersOpen: false }">

    {{-- HEADER --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#2A57B4]">Admin</p>
            <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Reports</h1>
            <p class="mt-1 text-sm text-slate-500">Statistical overview of transactions, appointments, clearance, and verification.</p>
        </div>

        {{-- ACTIONS: Filters dropdown sits directly before the export buttons --}}
        <div class="flex items-center gap-2">

            {{-- FILTERS DROPDOWN OVERLAY --}}
            <div class="relative" x-on:click.outside="filtersOpen = false">
                <button @click="filtersOpen = !filtersOpen"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold shadow-sm transition
                    {{ $activeFilterCount > 0 ? 'border-[#2A57B4]/30 bg-[#2A57B4]/5 text-[#2A57B4]' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <i class='bx bx-filter-alt text-lg'></i>
                    <span>Filters</span>
                    @if($activeFilterCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[11px] font-bold text-white">{{ $activeFilterCount }}</span>
                    @endif
                    <i class='bx bx-chevron-down text-lg transition' :class="filtersOpen && 'rotate-180'"></i>
                </button>

                <div x-show="filtersOpen" x-cloak x-transition.origin.top.right
                    class="absolute right-0 z-20 mt-2 w-80 rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-xl">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date range</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">Applies to Users, Transactions, Appointments &amp; Logbook</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach($datePresets as $key => $label)
                            <button wire:click="setDatePreset('{{ $key }}')"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition
                                {{ $datePreset === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                        <button wire:click="useCustomRange"
                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold transition
                            {{ $datePreset === 'custom' ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            <i class='bx bx-calendar-alt'></i> Custom
                        </button>
                    </div>

                    @if($datePreset === 'custom')
                        <div class="mt-2 flex items-center gap-2">
                            <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-slate-200 py-1.5 text-xs">
                            <span class="text-xs text-slate-400">to</span>
                            <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-slate-200 py-1.5 text-xs">
                        </div>
                    @endif

                    <div class="my-3 h-px bg-slate-100"></div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Academic period</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">Applies to Clearance &amp; Verification</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <select wire:model.live="academicYear" class="rounded-lg border-slate-200 py-1.5 text-xs">
                            <option value="">All years</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay }}">{{ $ay }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="semester" class="rounded-lg border-slate-200 py-1.5 text-xs">
                            <option value="">All semesters</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                        <button wire:click="resetFilters" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 transition hover:text-[#2A57B4]">
                            <i class='bx bx-reset'></i> Reset all
                        </button>
                        <button @click="filtersOpen = false" class="rounded-lg bg-[#2A57B4] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#214795]">
                            Done
                        </button>
                    </div>
                </div>
            </div>

            <button wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                class="inline-flex items-center gap-2 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#214795] disabled:opacity-60">
                <i class='bx bxs-file-pdf text-lg' wire:loading.remove wire:target="exportPdf"></i>
                <i class='bx bx-loader-alt animate-spin text-lg' wire:loading wire:target="exportPdf"></i>
                <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
                <span wire:loading wire:target="exportPdf">Generating…</span>
            </button>
            <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100 disabled:opacity-60">
                <i class='bx bxs-file-export text-lg' wire:loading.remove wire:target="exportExcel"></i>
                <i class='bx bx-loader-alt animate-spin text-lg' wire:loading wire:target="exportExcel"></i>
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Generating…</span>
            </button>
        </div>
    </div>

    {{-- Active-filter summary line, replaces the old always-visible filter bar --}}
    <p class="-mt-2 text-xs text-slate-400">
        @if(!empty($activeFilterChips))
            Showing:
            @foreach($activeFilterChips as $chip)
                <span class="font-semibold text-slate-600">{{ $chip['label'] }}</span> <span class="text-slate-400">({{ $chip['scope'] }})</span>@if(!$loop->last)<span class="mx-1">·</span>@endif
            @endforeach
        @else
            Showing all-time data for every chart. Open <span class="font-semibold text-slate-600">Filters</span> to narrow the range.
        @endif
    </p>

    {{-- TABS --}}
    <div class="flex flex-wrap gap-1 rounded-2xl border border-[#d7e0ee] bg-white p-1.5 shadow-sm">
        @foreach($tabs as $key => [$label, $icon])
            <button @click="tab = '{{ $key }}'; $dispatch('tab-switched')"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="tab === '{{ $key }}' ? 'bg-[#2A57B4] text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'">
                <i class='bx {{ $icon }}'></i>
                <span>{{ $label }}</span>
            </button>
        @endforeach
    </div>

    {{-- ================= TAB CONTENT (KPIs / summaries — updates live with filters) ================= --}}

    {{-- OVERVIEW / AT A GLANCE — everything the admin needs to know in one screen. --}}
    <div x-show="tab === 'overview'" x-cloak class="space-y-4">

        <p class="text-sm text-slate-500">
            A quick read on how the office is doing right now: how many people are registered, how requests and
            appointments are moving, and how clean each queue looks. Use the cards below to spot problem areas, then
            switch to a tab above for the full breakdown.
        </p>

        {{-- Top-line KPI cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Students / Officers</p>
                <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $report['overview']['cards']['total_students'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $report['overview']['cards']['active_accounts'] }} active accounts</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Transactions Completed</p>
                <p class="mt-1 text-3xl font-extrabold text-slate-900">
                    {{ $report['overview']['cards']['completed_transactions'] }}
                    <span class="text-base font-medium text-slate-400">/ {{ $report['overview']['cards']['total_transactions'] }}</span>
                </p>
                <p class="mt-1 text-xs text-slate-400">Document requests finished vs. filed, in the selected range</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Appointments Today</p>
                <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $report['overview']['cards']['todays_appointments'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $report['overview']['cards']['pending_verifications'] }} verifications still pending</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Clearance Rate</p>
                <p class="mt-1 text-3xl font-extrabold text-emerald-600">{{ $report['overview']['cards']['clearance_rate'] }}%</p>
                <p class="mt-1 text-xs text-slate-400">Share of tagged students marked Cleared this period</p>
            </div>
        </div>

        {{-- One glance card per system area — shape of the data, not the full detail --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Users</h3>
                    <span class="text-xs text-slate-400">{{ $report['users']['total'] }} total</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniUsers"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">Split of registered accounts by status — watch for a rising inactive/suspended share.</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Transactions</h3>
                    <span class="text-xs text-slate-400">{{ $report['transactions']['total'] }} total</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniTransactions"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">Document requests by status — a large pending slice means a backlog to clear.</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Appointments</h3>
                    <span class="text-xs text-slate-400">{{ $report['appointments']['missed_rate'] }}% missed</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniAppointments"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">Booked sessions by outcome — high missed rate may mean reminders need attention.</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Clearance</h3>
                    <span class="text-xs text-slate-400">{{ $report['clearance']['clearance_rate'] }}% cleared</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniClearance"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">Tagged students by clearance status for the selected academic period.</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Verification</h3>
                    <span class="text-xs text-slate-400">{{ $report['verification']['verified_rate'] }}% verified</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniVerification"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">e-Slip submissions by status for the selected academic period.</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Logbook</h3>
                    <span class="text-xs text-slate-400">{{ $report['logbook']['total_entries'] }} entries</span>
                </div>
                <div class="relative mt-2 h-32"><canvas x-ref="overviewMiniLogbook"></canvas></div>
                <p class="mt-2 text-[11px] text-slate-400">Top 5 request purposes from the paper logbook — lifetime, not affected by the date filter.</p>
            </div>
        </div>

        {{-- Trend + role split --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">Registrations Trend</h3>
                <p class="mt-1 text-xs text-slate-400">New accounts created per month, for the selected date range.</p>
                <div class="relative mt-3 h-64"><canvas x-ref="registrationTrend"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">Users by Role</h3>
                <p class="mt-1 text-xs text-slate-400">How the user base breaks down between students, officers, and staff.</p>
                <div class="relative mt-3 h-64"><canvas x-ref="usersByRoleOverview"></canvas></div>
            </div>
        </div>

        {{-- One summary table tying every area together --}}
        <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-700">All Reports at a Glance</h3>
            <p class="mt-1 text-xs text-slate-400">The headline number and key rate for each system area, side by side.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                            <th class="py-2 pr-4 font-semibold">Area</th>
                            <th class="py-2 pr-4 font-semibold">Total</th>
                            <th class="py-2 pr-4 font-semibold">Key Rate</th>
                            <th class="py-2 font-semibold">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Users</td>
                            <td class="py-2 pr-4">{{ $report['users']['total'] }}</td>
                            <td class="py-2 pr-4">{{ $report['overview']['cards']['active_accounts'] }} active</td>
                            <td class="py-2 text-slate-400">Registered in the selected date range</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Transactions</td>
                            <td class="py-2 pr-4">{{ $report['transactions']['total'] }}</td>
                            <td class="py-2 pr-4">{{ $report['transactions']['completed'] }} completed</td>
                            <td class="py-2 text-slate-400">Avg. completion: {{ $report['transactions']['avg_completion_hours'] ?? '—' }} hrs</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Appointments</td>
                            <td class="py-2 pr-4">{{ $report['appointments']['total'] }}</td>
                            <td class="py-2 pr-4">{{ $report['appointments']['missed_rate'] }}% missed</td>
                            <td class="py-2 text-slate-400">{{ $report['appointments']['reschedule_count'] }} reschedules</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Clearance</td>
                            <td class="py-2 pr-4">{{ $report['clearance']['total'] }}</td>
                            <td class="py-2 pr-4">{{ $report['clearance']['clearance_rate'] }}% cleared</td>
                            <td class="py-2 text-slate-400">Scoped by academic year / semester</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Verification</td>
                            <td class="py-2 pr-4">{{ $report['verification']['total'] }}</td>
                            <td class="py-2 pr-4">{{ $report['verification']['verified_rate'] }}% verified</td>
                            <td class="py-2 text-slate-400">Scoped by academic year / semester</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-semibold text-slate-700">Logbook</td>
                            <td class="py-2 pr-4">{{ $report['logbook']['total_entries'] }}</td>
                            <td class="py-2 pr-4">{{ $report['logbook']['total_uploads'] }} uploads</td>
                            <td class="py-2 text-slate-400">Lifetime — not affected by filters</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TRANSACTIONS summary --}}
    <div x-show="tab === 'transactions'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['transactions']['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Completed</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $report['transactions']['completed'] }}</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Avg. Completion Time</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['transactions']['avg_completion_hours'] ?? '—' }} <span class="text-sm font-medium text-slate-400">hrs</span></p>
            </div>
        </div>
        <p class="text-xs text-slate-400">Document requests filed through DocuMate, filtered to the selected date range.</p>
    </div>

    {{-- APPOINTMENTS summary --}}
    <div x-show="tab === 'appointments'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['appointments']['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Missed Rate</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ $report['appointments']['missed_rate'] }}%</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Reschedules</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['appointments']['reschedule_count'] }}</p>
            </div>
        </div>
        <p class="text-xs text-slate-400">Missed rate is calculated against resolved appointments (attended + missed), excluding no-shows still pending.</p>
    </div>

    {{-- CLEARANCE summary --}}
    <div x-show="tab === 'clearance'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Total Tagged</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['clearance']['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Clearance Rate</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $report['clearance']['clearance_rate'] }}%</p>
            </div>
        </div>
        <p class="text-xs text-slate-400">Scoped to the selected academic year and semester, not the date-range filter.</p>
    </div>

    {{-- VERIFICATION summary --}}
    <div x-show="tab === 'verification'" x-cloak class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Total Submitted</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['verification']['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-400">Verified Rate</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $report['verification']['verified_rate'] }}%</p>
            </div>
        </div>
        <p class="text-xs text-slate-400">Scoped to the selected academic year and semester, not the date-range filter.</p>
    </div>

    {{-- LOGBOOK summary --}}
    <div x-show="tab === 'logbook'" x-cloak>
        @if($report['logbook']['total_uploads'] > 0)
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-400">Uploads</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['logbook']['total_uploads'] }}</p>
                </div>
                <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-400">Total Entries</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $report['logbook']['total_entries'] }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Logbook entries carry their own free-text dates and are always shown lifetime, independent of the date filter above.</p>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500">
                No logbook uploads found for the selected range.
            </div>
        @endif
    </div>

    {{-- ================= CHARTS (Chart.js, wire:ignore — refreshed via event, never re-rendered by Livewire) ================= --}}
    <div wire:ignore x-data="reportsCharts(@js($report))" x-init="init()" x-on:tab-switched.window="handleTabSwitch()">

        {{-- USERS charts --}}
        <div x-show="tab === 'users'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Account Status</h3>
                <p class="mt-1 text-xs text-slate-400">Active, inactive, and suspended accounts — a growing inactive share can mean stale records to clean up.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="usersByStatus"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Role</h3>
                <p class="mt-1 text-xs text-slate-400">How the registered user base splits between students, officers, and staff roles.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="usersByRole"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="text-sm font-bold text-slate-700">Top Programs</h3>
                <p class="mt-1 text-xs text-slate-400">The 10 academic programs with the most registered students, for the selected date range.</p>
                <div class="relative mt-3 h-80"><canvas x-ref="usersByProgram"></canvas></div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-2 pr-4 font-semibold">Program</th>
                                <th class="py-2 font-semibold">Students</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($report['users']['by_program'] as $program => $count)
                                <tr>
                                    <td class="py-2 pr-4 text-slate-700">{{ $program }}</td>
                                    <td class="py-2 text-slate-500">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-3 text-slate-400">No program data for this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TRANSACTIONS charts --}}
        <div x-show="tab === 'transactions'" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Status</h3>
                <p class="mt-1 text-xs text-slate-400">Where document requests currently stand in the workflow.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="transactionsByStatus"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">Most Requested Documents</h3>
                <p class="mt-1 text-xs text-slate-400">The 10 document templates requested most often in the selected range.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="transactionsByTemplate"></canvas></div>
            </div>
        </div>

        {{-- APPOINTMENTS charts --}}
        <div x-show="tab === 'appointments'" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Status</h3>
                <p class="mt-1 text-xs text-slate-400">Attended, missed, and upcoming appointments for the selected range.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="appointmentsByStatus"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Session</h3>
                <p class="mt-1 text-xs text-slate-400">Split between morning and afternoon session bookings.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="appointmentsBySession"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="text-sm font-bold text-slate-700">Appointments per Day</h3>
                <p class="mt-1 text-xs text-slate-400">Daily booking volume — useful for spotting which days need more slots or staff.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="appointmentsPerDay"></canvas></div>
            </div>
        </div>

        {{-- CLEARANCE chart --}}
        <div x-show="tab === 'clearance'" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Status</h3>
                <p class="mt-1 text-xs text-slate-400">Cleared vs. pending vs. flagged students for the selected academic period.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="clearanceByStatus"></canvas></div>
            </div>
        </div>

        {{-- VERIFICATION chart --}}
        <div x-show="tab === 'verification'" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">By Status</h3>
                <p class="mt-1 text-xs text-slate-400">e-Slip submissions awaiting review vs. already verified.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="verificationByStatus"></canvas></div>
            </div>
        </div>

        {{-- LOGBOOK charts --}}
        <div x-show="tab === 'logbook'" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Requests by Purpose</h3>
                    <button @click="download('logbookByPurpose', 'logbook-by-purpose')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]">
                        <i class='bx bx-download'></i> PNG
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-400">The 10 most common reasons students visited, from paper logbook records.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="logbookByPurpose"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Requests by Course / Program</h3>
                    <button @click="download('logbookByProgram', 'logbook-by-program')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]">
                        <i class='bx bx-download'></i> PNG
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-400">The 10 programs with the most logbook entries.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="logbookByProgram"></canvas></div>
            </div>
            <div class="rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Monthly Trend</h3>
                    <button @click="download('logbookMonthly', 'logbook-monthly-trend')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]">
                        <i class='bx bx-download'></i> PNG
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-400">Logbook entries per month across the entire uploaded history.</p>
                <div class="relative mt-3 h-72"><canvas x-ref="logbookMonthly"></canvas></div>
            </div>
        </div>
    </div>
</div>