@php
    $admin = auth()->user();

    $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: 'Admin';

    $hour = now()->hour;

    if ($hour >= 5 && $hour < 12) {

        $timeOfDay = 'morning';
        $greeting = 'Good morning';
        $timeMessage = "Here's what's happening across DocuMate this morning.";

        $skyClass = 'bg-gradient-to-b from-[#7fc8f8] via-[#fbd58a] to-[#f6c66d]';
        $sunSize = 'h-16 w-16';
        $sunPosition = 'bottom-[22%] right-[25%]';
        $sunGlow = 'shadow-[0_0_45px_15px_rgba(255,220,120,0.45)]';
        $mountainBack = 'bg-[#7d91b5]';
        $mountainFront = 'bg-[#405879]';
        $groundClass = 'bg-[#263c50]';
        $showStars = false;

    } elseif ($hour >= 12 && $hour < 18) {

        $timeOfDay = 'afternoon';
        $greeting = 'Good afternoon';
        $timeMessage = "Here's what's happening across DocuMate this afternoon.";

        $skyClass = 'bg-gradient-to-b from-[#55b7ed] via-[#8bd2f2] to-[#d9f0f5]';
        $sunSize = 'h-20 w-20';
        $sunPosition = 'top-[18%] right-[24%]';
        $sunGlow = 'shadow-[0_0_55px_18px_rgba(255,235,150,0.55)]';
        $mountainBack = 'bg-[#7690ad]';
        $mountainFront = 'bg-[#3e5b72]';
        $groundClass = 'bg-[#294653]';
        $showStars = false;

    } else {

        $timeOfDay = 'evening';
        $greeting = 'Good evening';
        $timeMessage = "Here's your overview as the day comes to a close.";

        $skyClass = 'bg-gradient-to-b from-[#111d3d] via-[#293b68] to-[#735d78]';
        $sunSize = 'h-14 w-14';
        $sunPosition = 'top-[18%] right-[23%]';
        $sunGlow = '';
        $mountainBack = 'bg-[#334466]';
        $mountainFront = 'bg-[#18283d]';
        $groundClass = 'bg-[#101c2b]';
        $showStars = true;
    }

    $timeIcon = $timeOfDay === 'evening' ? 'bx-moon' : 'bx-sun';

    $statusStyles = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/15',
        'danger'  => 'bg-red-50 text-red-700 ring-red-600/15',
        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-500/15',
    ];

    $statusTone = fn (string $status) => match (strtolower($status)) {
        'completed', 'approved', 'confirmed', 'cleared', 'attended'
            => $statusStyles['success'],
        'pending', 'ongoing', 'submitted', 'under_review', 'action_required'
            => $statusStyles['warning'],
        'rejected', 'cancelled', 'declined', 'missed'
            => $statusStyles['danger'],
        default
            => $statusStyles['neutral'],
    };
@endphp

{{--
    `relative z-0` creates a stacking context for the whole page body.
    Everything inside (cards, hovers, transforms) is trapped at z-0 and can
    never paint above the header / notification dropdown, which lives outside
    this component.
--}}
<div class="relative z-0 space-y-8 pb-12" wire:poll.30s>

    {{-- ============================================================
        TIME-OF-DAY WELCOME HEADER
    ============================================================= --}}
    <header class="relative isolate h-[240px] overflow-hidden rounded-xl border border-slate-200 shadow-sm sm:h-[260px]">

        {{-- SKY --}}
        <div class="absolute inset-0 {{ $skyClass }}"></div>

        {{-- SUN / MOON --}}
        @if($timeOfDay === 'morning')
            <div class="absolute {{ $sunPosition }} h-32 w-32 rounded-full bg-[#ffe7a1]/30 blur-2xl"></div>
            <div class="absolute {{ $sunPosition }} {{ $sunSize }} {{ $sunGlow }} rounded-full bg-[#ffd34f]"></div>
        @elseif($timeOfDay === 'afternoon')
            <div class="absolute {{ $sunPosition }} h-40 w-40 rounded-full bg-white/30 blur-3xl"></div>
            <div class="absolute {{ $sunPosition }} {{ $sunSize }} {{ $sunGlow }} rounded-full bg-[#fff4ad]"></div>
        @else
            <div class="absolute {{ $sunPosition }} h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute {{ $sunPosition }} {{ $sunSize }} rounded-full bg-[#f4f1d4] shadow-[0_0_35px_8px_rgba(255,255,255,0.15)]">
                <div class="absolute -right-1 top-0 h-12 w-12 rounded-full bg-[#293b68]"></div>
            </div>
        @endif

        {{-- STARS --}}
        @if($showStars)
            <div class="absolute inset-0">
                <span class="absolute left-[12%] top-[22%] h-1 w-1 rounded-full bg-white/80"></span>
                <span class="absolute left-[23%] top-[12%] h-1.5 w-1.5 rounded-full bg-white/70"></span>
                <span class="absolute left-[37%] top-[27%] h-1 w-1 rounded-full bg-white/70"></span>
                <span class="absolute left-[48%] top-[13%] h-1 w-1 rounded-full bg-white/80"></span>
                <span class="absolute left-[59%] top-[29%] h-1.5 w-1.5 rounded-full bg-white/70"></span>
                <span class="absolute left-[72%] top-[11%] h-1 w-1 rounded-full bg-white/80"></span>
                <span class="absolute left-[82%] top-[31%] h-1 w-1 rounded-full bg-white/70"></span>
                <span class="absolute left-[91%] top-[18%] h-1.5 w-1.5 rounded-full bg-white/80"></span>
            </div>
        @endif

        {{-- CLOUDS --}}
        @if($timeOfDay !== 'evening')
            <div class="absolute left-[12%] top-[23%] opacity-70">
                <div class="relative h-8 w-28 rounded-full bg-white/45">
                    <div class="absolute -top-5 left-5 h-10 w-10 rounded-full bg-white/45"></div>
                    <div class="absolute -top-7 left-12 h-14 w-14 rounded-full bg-white/50"></div>
                    <div class="absolute -top-4 right-4 h-8 w-8 rounded-full bg-white/40"></div>
                </div>
            </div>
            <div class="absolute right-[38%] top-[16%] scale-75 opacity-50">
                <div class="relative h-8 w-28 rounded-full bg-white/45">
                    <div class="absolute -top-5 left-5 h-10 w-10 rounded-full bg-white/45"></div>
                    <div class="absolute -top-7 left-12 h-14 w-14 rounded-full bg-white/50"></div>
                    <div class="absolute -top-4 right-4 h-8 w-8 rounded-full bg-white/40"></div>
                </div>
            </div>
        @endif

        {{-- DISTANT MOUNTAINS --}}
        <div class="absolute bottom-[19%] left-[-5%] h-[45%] w-[65%] {{ $mountainBack }} opacity-80"
             style="clip-path: polygon(0% 100%, 15% 58%, 25% 72%, 40% 35%, 50% 62%, 63% 20%, 76% 56%, 88% 38%, 100% 68%, 100% 100%);"></div>
        <div class="absolute bottom-[19%] right-[-5%] h-[48%] w-[70%] {{ $mountainBack }} opacity-75"
             style="clip-path: polygon(0% 100%, 12% 65%, 26% 45%, 39% 67%, 53% 25%, 65% 55%, 78% 40%, 90% 63%, 100% 50%, 100% 100%);"></div>

        {{-- FRONT MOUNTAINS --}}
        <div class="absolute bottom-[12%] left-[-8%] h-[47%] w-[75%] {{ $mountainFront }}"
             style="clip-path: polygon(0% 100%, 10% 68%, 23% 42%, 35% 67%, 49% 27%, 62% 58%, 74% 40%, 87% 68%, 100% 52%, 100% 100%);"></div>
        <div class="absolute bottom-[12%] right-[-8%] h-[50%] w-[70%] {{ $mountainFront }}"
             style="clip-path: polygon(0% 100%, 14% 62%, 29% 75%, 43% 36%, 57% 65%, 71% 30%, 84% 57%, 100% 43%, 100% 100%);"></div>

        {{-- GROUND + WATER --}}
        <div class="absolute bottom-0 left-0 right-0 h-[21%] {{ $groundClass }}"></div>
        <div class="absolute bottom-[12%] left-0 right-0 h-[9%] bg-[#7da7b2]/30"></div>

        {{-- TEXT READABILITY OVERLAY --}}
        <div class="absolute inset-0 bg-gradient-to-r from-black/35 via-black/10 to-transparent"></div>

        {{-- WELCOME CONTENT --}}
        <div class="relative flex h-full items-center px-6 py-6 sm:px-8">
            <div class="min-w-0 max-w-xl">

                <div class="flex items-center gap-2 text-white/90">
                    <i class='bx {{ $timeIcon }} text-xl {{ $timeOfDay === 'evening' ? 'text-[#f5f0ce]' : 'text-[#ffe27a]' }}'></i>
                    <p class="text-xs font-semibold uppercase tracking-widest">{{ $greeting }}</p>
                </div>

                <h1 class="mt-1 truncate text-2xl font-bold tracking-tight text-white drop-shadow-md sm:text-3xl">
                    {{ $adminName }}
                </h1>

                <p class="mt-1.5 max-w-md text-sm text-white/90 drop-shadow-sm">
                    {{ $timeMessage }}
                </p>

                <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-black/20 px-3 py-1.5 text-xs font-medium text-white/90 ring-1 ring-inset ring-white/15 backdrop-blur-sm">
                    <i class='bx bx-calendar text-base'></i>
                    {{ now()->format('l, F j, Y') }}
                </div>

            </div>
        </div>

    </header>


    {{-- ============================================================
        QUICK ACCESS
    ============================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Quick Access</h2>
            <p class="mt-0.5 text-sm text-slate-500">Frequently used tools.</p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">

            @foreach([
                ['route' => 'admin.transactions.index',  'label' => 'Transactions', 'icon' => 'bx-transfer',   'bg' => 'bg-blue-50',    'color' => 'text-[#2A57B4]'],
                ['route' => 'admin.workflow.index',      'label' => 'Workflow',     'icon' => 'bx-task',       'bg' => 'bg-blue-50',    'color' => 'text-[#2A57B4]'],
                ['route' => 'admin.appointments.index',  'label' => 'Appointments', 'icon' => 'bx-calendar',   'bg' => 'bg-indigo-50',  'color' => 'text-indigo-600'],
                ['route' => 'admin.clearance-monitoring','label' => 'Clearance',    'icon' => 'bx-clipboard',  'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
                ['route' => 'admin.verification',        'label' => 'Verification', 'icon' => 'bx-id-card',    'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
                ['route' => 'admin.reports',             'label' => 'Reports',      'icon' => 'bx-bar-chart',  'bg' => 'bg-purple-50',  'color' => 'text-purple-600'],
            ] as $app)

                <a
                    href="{{ route($app['route']) }}"
                    wire:navigate
                    class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white px-3 py-4 text-center shadow-sm transition-colors hover:border-[#2A57B4]/40 hover:bg-slate-50"
                >
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $app['bg'] }} {{ $app['color'] }}">
                        <i class='bx {{ $app['icon'] }} text-2xl'></i>
                    </div>

                    <span class="mt-2.5 text-xs font-semibold text-slate-700 group-hover:text-[#2A57B4]">
                        {{ $app['label'] }}
                    </span>
                </a>

            @endforeach

        </div>

    </section>


    {{-- ============================================================
        ACTION CENTER
    ============================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">Action Center</h2>
            <p class="mt-0.5 text-sm text-slate-500">Items that may require your attention.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            @foreach($actionItems as $item)

                @php
                    $tone = match($item['tone']) {
                        'amber' => ['box' => 'bg-amber-50 text-amber-600', 'number' => 'text-amber-600', 'hover' => 'hover:border-amber-300'],
                        'red'   => ['box' => 'bg-red-50 text-red-600',     'number' => 'text-red-600',   'hover' => 'hover:border-red-300'],
                        default => ['box' => 'bg-blue-50 text-[#2A57B4]',  'number' => 'text-[#2A57B4]', 'hover' => 'hover:border-blue-300'],
                    };
                @endphp

                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    class="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-colors {{ $tone['hover'] }}"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $tone['box'] }}">
                            <i class='bx {{ $item['icon'] }} text-xl'></i>
                        </div>

                        <span class="text-3xl font-bold tabular-nums {{ $tone['number'] }}">
                            {{ number_format($item['count']) }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</h3>
                        <i class='bx bx-chevron-right text-xl text-slate-300 group-hover:text-slate-500'></i>
                    </div>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        {{ $item['description'] }}
                    </p>
                </a>

            @endforeach

        </div>

    </section>


    {{-- ============================================================
        SYSTEM OVERVIEW
    ============================================================= --}}
    <section>

        <div class="mb-4">
            <h2 class="text-base font-semibold text-slate-900">System Overview</h2>
            <p class="mt-0.5 text-sm text-slate-500">Accounts, transactions, and clearance progress at a glance.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- TOP METRICS --}}
            <div class="grid divide-y divide-slate-200 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">

                {{-- Students & Officers --}}
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]">
                            <i class='bx bx-group text-2xl'></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500">Students &amp; Officers</p>
                            <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">
                                {{ number_format($kpis['total_students']) }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Registered users in the system</p>
                </div>

                {{-- Active Accounts --}}
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i class='bx bx-user-check text-2xl'></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500">Active Accounts</p>
                            <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">
                                {{ number_format($kpis['active_accounts']) }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1.5 text-xs text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Currently active
                    </div>
                </div>

                {{-- Pending Transactions --}}
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i class='bx bx-transfer text-2xl'></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500">Pending Transactions</p>
                            <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">
                                {{ number_format($kpis['pending_transactions']) }}
                            </p>
                        </div>
                    </div>

                    @if($kpis['pending_transactions'] > 0)
                        <div class="mt-4 flex items-center gap-1.5 text-xs font-medium text-amber-600">
                            <i class='bx bx-error-circle text-sm'></i> Needs attention
                        </div>
                    @else
                        <div class="mt-4 flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                            <i class='bx bx-check-circle text-sm'></i> All caught up
                        </div>
                    @endif
                </div>

                {{-- Clearance Rate --}}
                <div class="bg-slate-50/60 p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Clearance Rate</p>
                            <p class="mt-0.5 text-2xl font-bold tabular-nums text-[#2A57B4]">
                                {{ $kpis['clearance_rate'] }}<span class="ml-0.5 text-base font-semibold text-slate-400">%</span>
                            </p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]">
                            <i class='bx bx-check-shield text-2xl'></i>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div
                                class="h-full rounded-full bg-[#2A57B4] transition-all duration-700"
                                style="width: {{ min(max($kpis['clearance_rate'], 0), 100) }}%;"
                            ></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Overall clearance progress</p>
                    </div>
                </div>

            </div>

            {{-- OPERATIONS SUMMARY --}}
            <div class="grid divide-y divide-slate-200 border-t border-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0">

                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="group flex items-center justify-between gap-4 p-5 transition-colors hover:bg-slate-50 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]">
                            <i class='bx bx-calendar text-xl'></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Today's Appointments</p>
                            <p class="mt-0.5 text-xs text-slate-500">Scheduled for today</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tabular-nums text-slate-900">{{ $kpis['todays_appointments'] }}</span>
                        <i class='bx bx-chevron-right text-xl text-slate-300 group-hover:text-[#2A57B4]'></i>
                    </div>
                </a>

                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="group flex items-center justify-between gap-4 p-5 transition-colors hover:bg-slate-50 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class='bx bx-calendar-exclamation text-xl'></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Appointment Requests</p>
                            <p class="mt-0.5 text-xs text-slate-500">Awaiting admin review</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tabular-nums {{ $kpis['pending_appointment_review'] > 0 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ $kpis['pending_appointment_review'] }}
                        </span>
                        <i class='bx bx-chevron-right text-xl text-slate-300 group-hover:text-red-500'></i>
                    </div>
                </a>

            </div>

        </div>

    </section>


    {{-- ============================================================
        RECENT ACTIVITY + TODAY'S OPERATIONS
    ============================================================= --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- RECENT ACTIVITY --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Recent Activity</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Latest activity across the system.</p>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                    <i class='bx bx-history text-lg'></i>
                </div>
            </div>

            <div class="mt-4 divide-y divide-slate-100">

                @forelse($recentActivity as $activity)

                    @php $isAppointment = $activity->type === 'appointment'; @endphp

                    <div class="flex items-center justify-between gap-3 py-3.5">

                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $isAppointment ? 'bg-blue-50 text-[#2A57B4]' : 'bg-emerald-50 text-emerald-600' }}">
                                <i class='bx {{ $isAppointment ? 'bx-calendar' : 'bx-file' }} text-lg'></i>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ $activity->user_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ ucfirst($activity->type) }} · {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium capitalize ring-1 ring-inset {{ $statusTone((string) $activity->status) }}">
                            {{ str_replace('_', ' ', $activity->status) }}
                        </span>

                    </div>

                @empty

                    <div class="py-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-300">
                            <i class='bx bx-history text-2xl'></i>
                        </div>
                        <p class="mt-3 text-sm font-medium text-slate-600">No recent activity yet</p>
                        <p class="mt-1 text-xs text-slate-500">New system activity will appear here.</p>
                    </div>

                @endforelse

            </div>

        </section>


        {{-- TODAY'S OPERATIONS --}}
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Today's Operations</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Appointments scheduled for today.</p>
                </div>

                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#2A57B4] hover:underline"
                >
                    View all <i class='bx bx-right-arrow-alt text-base'></i>
                </a>
            </div>

            @if($todaysAppointments->count())

                <div class="mt-4 space-y-2.5">

                    @foreach($todaysAppointments as $appointment)

                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3.5">

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200">
                                <i class='bx bx-calendar text-lg'></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $appointment->user_name }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                                    <i class='bx bx-time-five'></i>
                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('g:i A') }}
                                    · {{ ucfirst($appointment->session) }} session
                                </p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium capitalize ring-1 ring-inset {{ $statusTone((string) $appointment->status) }}">
                                {{ str_replace('_', ' ', $appointment->status) }}
                            </span>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="mt-4 flex min-h-[230px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-5 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-300 shadow-sm ring-1 ring-slate-200">
                        <i class='bx bx-calendar-check text-2xl'></i>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">No appointments today</p>
                    <p class="mt-1 max-w-xs text-xs leading-5 text-slate-500">
                        Your schedule is clear. New appointments will appear here.
                    </p>
                </div>

            @endif

        </section>

    </div>


    {{-- ============================================================
        MOBILE QUICK ACTIONS
    ============================================================= --}}
    <div class="grid gap-3 sm:hidden">

        <a
            href="{{ route('admin.transactions.index') }}"
            wire:navigate
            class="flex items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-4 py-3 text-sm font-semibold text-white shadow-sm"
        >
            <i class='bx bx-transfer text-lg'></i> Review Transactions
        </a>

        <a
            href="{{ route('admin.appointments.index') }}"
            wire:navigate
            class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm"
        >
            <i class='bx bx-calendar text-lg'></i> Manage Appointments
        </a>

    </div>

</div>