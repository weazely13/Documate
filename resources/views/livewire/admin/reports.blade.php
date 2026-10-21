@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');

    $datePresets = ['7d' => '7D', '30d' => '30D', '90d' => '3M', '180d' => '6M', '365d' => '1Y', 'all' => 'All'];

    $tabs = [
        'overview' => ['At a glance', 'bx-home-alt'],
        'users' => ['Users', 'bx-group'],
        'transactions' => ['Transactions', 'bx-transfer'],
        'workflow' => ['Paperless workflow', 'bx-task'],
        'appointments' => ['Appointments', 'bx-calendar'],
        'clearance' => ['Clearance', 'bx-check-shield'],
        'verification' => ['Verification', 'bx-id-card'],
        'logbook' => ['Logbook', 'bx-book-open'],
    ];

    // Human-readable range shown under the filter chips.
    $rangeLabel = $datePreset === 'all'
        ? 'All-time data'
        : \Carbon\Carbon::parse($dateFrom ?? $report['generated_at'])->format('M d, Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y');

    // Rows for the "all reports at a glance" table (table on desktop, cards on mobile).
    $glance = [
        ['Users', $report['users']['total'], $report['overview']['cards']['active_accounts'] . ' active', 'Registered in the selected range'],
        ['Transactions', $report['transactions']['total'], $report['transactions']['completed'] . ' completed', 'Avg. completion: ' . ($report['transactions']['avg_completion_hours'] ?? '—') . ' hrs'],
        ['Paperless workflow', $report['workflow']['total'], $report['workflow']['completion_rate'] . '% completed', 'Avg. time to issue: ' . $report['workflow']['avg_issue']],
        ['Appointments', $report['appointments']['total'], $report['appointments']['missed_rate'] . '% missed', $report['appointments']['reschedule_count'] . ' reschedules'],
        ['Clearance', $report['clearance']['total'], $report['clearance']['clearance_rate'] . '% cleared', 'All records, not date-filtered'],
        ['Verification', $report['verification']['total'], $report['verification']['verified_rate'] . '% verified', 'All records, not date-filtered'],
        ['Logbook', $report['logbook']['total_entries'], $report['logbook']['total_uploads'] . ' uploads', 'Filtered by the selected date range'],
    ];

    $kpi = 'rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm sm:p-4';
    $kpiLabel = 'text-xs font-medium text-gray-500';
    $kpiValue = 'mt-1 text-xl font-semibold text-gray-900 sm:text-2xl';
    $kpiNote = 'mt-1 text-[11px] leading-snug text-gray-400 sm:text-xs';
    $th = 'border-b border-gray-100 text-xs font-medium text-gray-500';
    $pngBtn = 'inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-[#2A57B4]';
    $noScrollbar = '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden';
@endphp

<div x-data="{ tab: 'overview' }">

    <x-admin.page-header
        title="Reports"
        description="Statistical overview of transactions, appointments, clearance, and verification."
        :crumbs="['Admin' => $homeUrl, 'Reports' => null]">
        <x-slot:actions>
            <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60 sm:flex-none sm:py-2">
                <i class='bx bxs-file-pdf text-base' wire:loading.remove wire:target="exportPdf"></i>
                <i class='bx bx-loader-alt animate-spin text-base' wire:loading wire:target="exportPdf"></i>
                <span wire:loading.remove wire:target="exportPdf">PDF</span>
                <span wire:loading wire:target="exportPdf">Generating…</span>
            </button>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100 disabled:opacity-60 sm:flex-none sm:py-2">
                <i class='bx bxs-file-export text-base' wire:loading.remove wire:target="exportExcel"></i>
                <i class='bx bx-loader-alt animate-spin text-base' wire:loading wire:target="exportExcel"></i>
                <span wire:loading.remove wire:target="exportExcel">Excel</span>
                <span wire:loading wire:target="exportExcel">Generating…</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Date range: one-tap chips (no popover), scrolls sideways on small screens --}}
    <div class="-mt-3 mb-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-semibold text-gray-700">Date range</p>
            <p class="truncate text-xs text-gray-500">{{ $rangeLabel }}</p>
        </div>

        <div class="mt-2 flex gap-2 overflow-x-auto pb-1 {{ $noScrollbar }}" wire:loading.class="opacity-60" wire:target="setDatePreset">
            @foreach ($datePresets as $key => $label)
                <button type="button" wire:click="setDatePreset('{{ $key }}')"
                    @if ($datePreset === $key) aria-pressed="true" @else aria-pressed="false" @endif
                    class="min-w-[3rem] shrink-0 rounded-full border px-3.5 py-2 text-xs font-semibold transition sm:py-1.5
                    {{ $datePreset === $key ? 'border-[#2A57B4] bg-[#2A57B4] text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <p class="mt-1.5 text-[11px] leading-relaxed text-gray-400">
            Applies to Users, Transactions, Workflow, Appointments &amp; Logbook. Clearance and Verification show all records.
        </p>
    </div>

    {{-- Tabs: swipeable, active tab scrolls into view --}}
    <nav aria-label="Report sections"
        x-effect="tab; $nextTick(() => $el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }))"
        class="mb-5 flex gap-5 overflow-x-auto whitespace-nowrap border-b border-gray-200 sm:mb-6 sm:gap-6 {{ $noScrollbar }}">
        @foreach ($tabs as $key => [$label, $icon])
            <button type="button" @click="tab = '{{ $key }}'; $dispatch('tab-switched')"
                :aria-current="tab === '{{ $key }}' ? 'page' : null"
                class="-mb-px inline-flex shrink-0 items-center gap-2 border-b-2 px-1 pb-3 pt-1 text-sm font-medium transition"
                :class="tab === '{{ $key }}' ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                <i class='bx {{ $icon }}'></i>
                <span>{{ $label }}</span>
            </button>
        @endforeach
    </nav>

    {{-- ================= OVERVIEW ================= --}}
    <div x-show="tab === 'overview'" x-cloak class="space-y-5">

        <p class="hidden text-sm text-gray-500 sm:block">
            A quick read on how the office is doing: registrations, how requests and appointments are moving, and how
            clean each queue looks. Spot problem areas here, then open a tab for the full breakdown.
        </p>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Students / officers</p>
                <p class="{{ $kpiValue }}">{{ $report['overview']['cards']['total_students'] }}</p>
                <p class="{{ $kpiNote }}">{{ $report['overview']['cards']['active_accounts'] }} active accounts</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Transactions completed</p>
                <p class="{{ $kpiValue }}">{{ $report['overview']['cards']['completed_transactions'] }}<span class="text-sm font-normal text-gray-400"> / {{ $report['overview']['cards']['total_transactions'] }}</span></p>
                <p class="{{ $kpiNote }}">Finished vs. filed in range</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Paperless completed</p>
                <p class="{{ $kpiValue }}">{{ $report['workflow']['completed'] }}<span class="text-sm font-normal text-gray-400"> / {{ $report['workflow']['total'] }}</span></p>
                <p class="{{ $kpiNote }}">{{ $report['workflow']['in_progress'] }} in progress · avg. {{ $report['workflow']['avg_issue'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Appointments today</p>
                <p class="{{ $kpiValue }}">{{ $report['overview']['cards']['todays_appointments'] }}</p>
                <p class="{{ $kpiNote }}">{{ $report['overview']['cards']['pending_verifications'] }} verifications pending</p>
            </div>
            <div class="{{ $kpi }} col-span-2 lg:col-span-1">
                <p class="{{ $kpiLabel }}">Clearance rate</p>
                <p class="{{ $kpiValue }} text-emerald-600">{{ $report['overview']['cards']['clearance_rate'] }}%</p>
                <p class="{{ $kpiNote }}">Tagged students marked Cleared</p>
            </div>
        </div>

        {{-- Mini charts: 2-up on phones, helper text only on larger screens --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            <x-admin.section title="Users">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['users']['total'] }}</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniUsers"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Accounts by status. Watch for a rising inactive or suspended share.</p>
            </x-admin.section>
            <x-admin.section title="Transactions">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['transactions']['total'] }}</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniTransactions"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Requests by status. A large pending slice means a backlog.</p>
            </x-admin.section>
            <x-admin.section title="Workflow">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['workflow']['total'] }}</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniWorkflow"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Digital transactions by status. A big under-review slice means signatures are waiting.</p>
            </x-admin.section>
            <x-admin.section title="Appointments">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['appointments']['missed_rate'] }}% missed</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniAppointments"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Sessions by outcome. A high missed rate may mean reminders need attention.</p>
            </x-admin.section>
            <x-admin.section title="Clearance">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['clearance']['clearance_rate'] }}%</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniClearance"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Tagged students by status.</p>
            </x-admin.section>
            <x-admin.section title="Verification">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['verification']['verified_rate'] }}%</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniVerification"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">e-Slip submissions by status.</p>
            </x-admin.section>
            <x-admin.section title="Logbook" class="col-span-2 lg:col-span-1">
                <x-slot:actions><span class="text-xs text-gray-400">{{ $report['logbook']['total_entries'] }} entries</span></x-slot:actions>
                <div class="relative h-24 sm:h-28"><canvas x-ref="overviewMiniLogbook"></canvas></div>
                <p class="mt-2 hidden text-[11px] text-gray-400 sm:block">Top 5 request purposes from the paper logbook.</p>
            </x-admin.section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="Registrations trend" description="New accounts created per month in the selected range.">
                <div class="relative h-52 sm:h-64"><canvas x-ref="registrationTrend"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Users by role" description="Students, officers, and staff.">
                <div class="relative h-52 sm:h-64"><canvas x-ref="usersByRoleOverview"></canvas></div>
            </x-admin.section>
        </div>

        <x-admin.section title="All reports at a glance" description="Headline number and key rate for each system area." :padded="false">

            {{-- Mobile: stacked cards --}}
            <div class="divide-y divide-gray-100 sm:hidden">
                @foreach ($glance as [$area, $total, $rate, $note])
                    <div class="px-4 py-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $area }}</p>
                            <p class="text-lg font-semibold tabular-nums text-gray-900">{{ $total }}</p>
                        </div>
                        <p class="mt-0.5 text-xs font-medium text-gray-600">{{ $rate }}</p>
                        <p class="mt-0.5 text-[11px] text-gray-400">{{ $note }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Tablet / desktop: table --}}
            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 {{ $th }}">
                        <tr>
                            <th class="px-5 py-3 font-medium">Area</th>
                            <th class="px-5 py-3 font-medium">Total</th>
                            <th class="px-5 py-3 font-medium">Key rate</th>
                            <th class="px-5 py-3 font-medium">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($glance as [$area, $total, $rate, $note])
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $area }}</td>
                                <td class="px-5 py-3">{{ $total }}</td>
                                <td class="px-5 py-3">{{ $rate }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.section>
    </div>

    {{-- ================= TRANSACTIONS summary ================= --}}
    <div x-show="tab === 'transactions'" x-cloak class="mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Total</p>
                <p class="{{ $kpiValue }}">{{ $report['transactions']['total'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Completed</p>
                <p class="{{ $kpiValue }} text-emerald-600">{{ $report['transactions']['completed'] }}</p>
            </div>
            <div class="{{ $kpi }} col-span-2 sm:col-span-1">
                <p class="{{ $kpiLabel }}">Avg. completion time</p>
                <p class="{{ $kpiValue }}">{{ $report['transactions']['avg_completion_hours'] ?? '—' }} <span class="text-sm font-normal text-gray-400">hrs</span></p>
            </div>
        </div>
        <p class="text-xs text-gray-500">Document requests filed through DocuMate in the selected date range.</p>
    </div>

    {{-- ================= APPOINTMENTS summary ================= --}}
    <div x-show="tab === 'appointments'" x-cloak class="mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Total</p>
                <p class="{{ $kpiValue }}">{{ $report['appointments']['total'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Missed rate</p>
                <p class="{{ $kpiValue }} text-amber-600">{{ $report['appointments']['missed_rate'] }}%</p>
            </div>
            <div class="{{ $kpi }} col-span-2 sm:col-span-1">
                <p class="{{ $kpiLabel }}">Reschedules</p>
                <p class="{{ $kpiValue }}">{{ $report['appointments']['reschedule_count'] }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500">Missed rate is calculated against resolved appointments (attended + missed), excluding no-shows still pending.</p>
    </div>

    {{-- ================= CLEARANCE summary ================= --}}
    <div x-show="tab === 'clearance'" x-cloak class="mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Total tagged</p>
                <p class="{{ $kpiValue }}">{{ $report['clearance']['total'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Clearance rate</p>
                <p class="{{ $kpiValue }} text-emerald-600">{{ $report['clearance']['clearance_rate'] }}%</p>
            </div>
        </div>
        <p class="text-xs text-gray-500">Covers all tagged students. The date range does not apply here.</p>
    </div>

    {{-- ================= VERIFICATION summary ================= --}}
    <div x-show="tab === 'verification'" x-cloak class="mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Total submitted</p>
                <p class="{{ $kpiValue }}">{{ $report['verification']['total'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Verified rate</p>
                <p class="{{ $kpiValue }} text-emerald-600">{{ $report['verification']['verified_rate'] }}%</p>
            </div>
        </div>
        <p class="text-xs text-gray-500">Covers all e-Slip submissions. The date range does not apply here.</p>
    </div>

    {{-- ================= LOGBOOK summary ================= --}}
    <div x-show="tab === 'logbook'" x-cloak class="mb-4">
        @if ($report['logbook']['total_uploads'] > 0)
            <div class="grid grid-cols-2 gap-3">
                <div class="{{ $kpi }}">
                    <p class="{{ $kpiLabel }}">Uploads</p>
                    <p class="{{ $kpiValue }}">{{ $report['logbook']['total_uploads'] }}</p>
                </div>
                <div class="{{ $kpi }}">
                    <p class="{{ $kpiLabel }}">Total entries</p>
                    <p class="{{ $kpiValue }}">{{ $report['logbook']['total_entries'] }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">Logbook entries are filtered by the selected date range. Entries with an unreadable date are always included.</p>
        @else
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No logbook uploads found for the selected range.</p>
        @endif
    </div>

    {{-- ================= WORKFLOW summary ================= --}}
    <div x-show="tab === 'workflow'" x-cloak class="mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Submitted</p>
                <p class="{{ $kpiValue }}">{{ $report['workflow']['total'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Completed</p>
                <p class="{{ $kpiValue }} text-emerald-600">{{ $report['workflow']['completed'] }}<span class="text-sm font-normal text-gray-400"> ({{ $report['workflow']['completion_rate'] }}%)</span></p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">In progress</p>
                <p class="{{ $kpiValue }} text-amber-600">{{ $report['workflow']['in_progress'] }}</p>
            </div>
            <div class="{{ $kpi }}">
                <p class="{{ $kpiLabel }}">Avg. time to issue</p>
                <p class="{{ $kpiValue }}">{{ $report['workflow']['avg_issue'] }}</p>
            </div>
        </div>
        <p class="text-xs leading-relaxed text-gray-500">
            Digital (paperless) transactions filed in the selected date range.
            {{ $report['workflow']['rejected'] }} rejected
            ({{ $report['workflow']['rejections']['review'] }} by VPSD, {{ $report['workflow']['rejections']['signer'] }} by a signatory)
            · {{ $report['workflow']['voided'] }} voided.
        </p>
    </div>

    {{-- ================= CHARTS (Chart.js, wire:ignore, refreshed via event) ================= --}}
    <div wire:ignore x-data="reportsCharts(@js($report))" x-init="init()" x-on:tab-switched.window="handleTabSwitch()">

        {{-- USERS --}}
        <div x-show="tab === 'users'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By account status" description="Active, inactive, and suspended. A growing inactive share can mean stale records to clean up.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="usersByStatus"></canvas></div>
            </x-admin.section>
            <x-admin.section title="By role" description="How registered users split between students, officers, and staff.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="usersByRole"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Top programs" description="The 10 programs with the most registered students in the selected range." class="lg:col-span-2">
                <div class="relative h-64 sm:h-80"><canvas x-ref="usersByProgram"></canvas></div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="{{ $th }}">
                            <tr>
                                <th class="py-2 pr-4 font-medium">Program</th>
                                <th class="py-2 text-right font-medium">Students</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['users']['by_program'] as $program => $count)
                                <tr>
                                    <td class="py-2.5 pr-4 text-gray-700">{{ $program }}</td>
                                    <td class="py-2.5 text-right tabular-nums text-gray-500">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-4 text-gray-400">No program data for this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.section>
        </div>

        {{-- TRANSACTIONS --}}
        <div x-show="tab === 'transactions'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By status" description="Where document requests currently stand.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="transactionsByStatus"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Most requested documents" description="The 10 templates requested most often in the selected range.">
                <div class="relative h-64 sm:h-72"><canvas x-ref="transactionsByTemplate"></canvas></div>
            </x-admin.section>
        </div>

        {{-- APPOINTMENTS --}}
        <div x-show="tab === 'appointments'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By status" description="Attended, missed, and upcoming appointments.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="appointmentsByStatus"></canvas></div>
            </x-admin.section>
            <x-admin.section title="By session" description="Morning vs. afternoon bookings.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="appointmentsBySession"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Appointments per day" description="Daily booking volume. Useful for spotting days that need more slots or staff." class="lg:col-span-2">
                <div class="relative h-56 sm:h-72"><canvas x-ref="appointmentsPerDay"></canvas></div>
            </x-admin.section>
        </div>

        {{-- CLEARANCE --}}
        <div x-show="tab === 'clearance'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By status" description="Cleared vs. pending vs. flagged students.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="clearanceByStatus"></canvas></div>
            </x-admin.section>
        </div>

        {{-- VERIFICATION --}}
        <div x-show="tab === 'verification'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By status" description="e-Slip submissions awaiting review vs. already verified.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="verificationByStatus"></canvas></div>
            </x-admin.section>
        </div>

        {{-- LOGBOOK --}}
        <div x-show="tab === 'logbook'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="Requests by purpose" description="The 10 most common reasons students visited.">
                <x-slot:actions>
                    <button type="button" @click="download('logbookByPurpose', 'logbook-by-purpose')" class="{{ $pngBtn }}"><i class='bx bx-download'></i> PNG</button>
                </x-slot:actions>
                <div class="relative h-64 sm:h-72"><canvas x-ref="logbookByPurpose"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Requests by course / program" description="The 10 programs with the most logbook entries.">
                <x-slot:actions>
                    <button type="button" @click="download('logbookByProgram', 'logbook-by-program')" class="{{ $pngBtn }}"><i class='bx bx-download'></i> PNG</button>
                </x-slot:actions>
                <div class="relative h-64 sm:h-72"><canvas x-ref="logbookByProgram"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Monthly trend" description="Logbook entries per month." class="lg:col-span-2">
                <x-slot:actions>
                    <button type="button" @click="download('logbookMonthly', 'logbook-monthly-trend')" class="{{ $pngBtn }}"><i class='bx bx-download'></i> PNG</button>
                </x-slot:actions>
                <div class="relative h-56 sm:h-72"><canvas x-ref="logbookMonthly"></canvas></div>
            </x-admin.section>
        </div>

        {{-- WORKFLOW --}}
        <div x-show="tab === 'workflow'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-admin.section title="By status" description="Where digital transactions currently stand.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="workflowByStatus"></canvas></div>
            </x-admin.section>
            <x-admin.section title="By document" description="Which digital forms are requested most.">
                <div class="relative h-56 sm:h-72"><canvas x-ref="workflowByType"></canvas></div>
            </x-admin.section>
            <x-admin.section title="Submitted per month" description="Volume of digital transactions over the selected range." class="lg:col-span-2">
                <div class="relative h-56 sm:h-72"><canvas x-ref="workflowMonthly"></canvas></div>
            </x-admin.section>
        </div>
    </div>

    {{-- Workflow tables (scroll sideways on phones) --}}
    <div x-show="tab === 'workflow'" x-cloak class="mt-4 grid gap-4 xl:grid-cols-2">
        <x-admin.section title="Signer performance" description="Response time runs from when a step opened to when it was signed or rejected. Swipe to see all columns." :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[30rem] text-left text-sm">
                    <thead class="bg-gray-50 {{ $th }}">
                        <tr>
                            <th class="px-4 py-3 font-medium sm:px-5">Role</th>
                            <th class="px-3 py-3 font-medium">Signed</th>
                            <th class="px-3 py-3 font-medium">Rej.</th>
                            <th class="px-3 py-3 font-medium">Pending</th>
                            <th class="px-3 py-3 font-medium">Avg. response</th>
                            <th class="px-4 py-3 font-medium sm:px-5">Oldest pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($report['workflow']['signers'] as $s)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 sm:px-5">{{ $s['label'] }}</td>
                                <td class="px-3 py-3">{{ $s['signed'] }}</td>
                                <td class="px-3 py-3">{{ $s['rejected'] }}</td>
                                <td class="px-3 py-3">{{ $s['pending'] }}</td>
                                <td class="px-3 py-3">{{ $s['avg_response'] }}</td>
                                <td class="px-4 py-3 text-gray-500 sm:px-5">{{ $s['oldest_pending'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No signing activity in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.section>

        <x-admin.section title="Oldest unfinished transactions" description="Submitted, under review, waiting on the student, or approved but not yet completed." :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[26rem] text-left text-sm">
                    <thead class="bg-gray-50 {{ $th }}">
                        <tr>
                            <th class="px-4 py-3 font-medium sm:px-5">Reference</th>
                            <th class="px-3 py-3 font-medium">Document</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium sm:px-5">Waiting</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($report['workflow']['stuck'] as $r)
                            <tr>
                                <td class="px-4 py-3 sm:px-5"><a href="{{ route('admin.workflow.show', $r['id']) }}" class="font-mono font-semibold text-[#2A57B4] hover:underline">{{ $r['code'] }}</a></td>
                                <td class="px-3 py-3">{{ $r['name'] }}</td>
                                <td class="px-3 py-3">{{ $r['status'] }}</td>
                                <td class="px-4 py-3 text-gray-500 sm:px-5">{{ $r['since'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Nothing is stuck.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.section>
    </div>
</div>