@php
    $admin = auth()->user();

    $adminName = trim(
        ($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')
    ) ?: 'Admin';

    $hour = now()->hour;

    $greeting = $hour < 12
        ? 'Good morning'
        : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp


<div
    class="space-y-6 pb-12"
    wire:poll.10s
>


    {{-- ============================================================
        TIME-OF-DAY SCENIC WELCOME CARD
    ============================================================= --}}

    @php

        if ($hour >= 6 && $hour < 12) {

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

            $sunGlow = 'shadow-[0_0_35px_8px_rgba(230,235,255,0.18)]';

            $mountainBack = 'bg-[#334466]';
            $mountainFront = 'bg-[#18283d]';

            $groundClass = 'bg-[#101c2b]';

            $showStars = true;
        }

    @endphp


    <div
        class="relative h-[270px] overflow-hidden rounded-[28px] border border-[#d7e0ee] shadow-[0_18px_45px_rgba(15,23,42,0.08)]"
    >

        {{-- SKY --}}
        <div
            class="absolute inset-0 {{ $skyClass }} transition-all duration-1000"
        ></div>


        {{-- SUN / MOON --}}

        @if($timeOfDay === 'morning')

            <div
                class="absolute {{ $sunPosition }} h-32 w-32 rounded-full bg-[#ffe7a1]/30 blur-2xl"
            ></div>

            <div
                class="absolute {{ $sunPosition }} {{ $sunSize }} {{ $sunGlow }} z-10 rounded-full bg-[#ffd34f]"
            ></div>

        @elseif($timeOfDay === 'afternoon')

            <div
                class="absolute {{ $sunPosition }} h-40 w-40 rounded-full bg-white/30 blur-3xl"
            ></div>

            <div
                class="absolute {{ $sunPosition }} {{ $sunSize }} {{ $sunGlow }} z-10 rounded-full bg-[#fff4ad]"
            ></div>

        @else

            <div
                class="absolute {{ $sunPosition }} h-28 w-28 rounded-full bg-white/10 blur-2xl"
            ></div>

            <div
                class="absolute {{ $sunPosition }} {{ $sunSize }} z-10 rounded-full bg-[#f4f1d4] shadow-[0_0_35px_8px_rgba(255,255,255,0.15)]"
            >
                <div
                    class="absolute -right-1 top-0 h-12 w-12 rounded-full bg-[#293b68]"
                ></div>
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

        <div
            class="absolute bottom-[19%] left-[-5%] h-[45%] w-[65%] {{ $mountainBack }} opacity-80"
            style="clip-path: polygon(
                0% 100%,
                15% 58%,
                25% 72%,
                40% 35%,
                50% 62%,
                63% 20%,
                76% 56%,
                88% 38%,
                100% 68%,
                100% 100%
            );"
        ></div>


        <div
            class="absolute bottom-[19%] right-[-5%] h-[48%] w-[70%] {{ $mountainBack }} opacity-75"
            style="clip-path: polygon(
                0% 100%,
                12% 65%,
                26% 45%,
                39% 67%,
                53% 25%,
                65% 55%,
                78% 40%,
                90% 63%,
                100% 50%,
                100% 100%
            );"
        ></div>


        {{-- FRONT MOUNTAINS --}}

        <div
            class="absolute bottom-[12%] left-[-8%] h-[47%] w-[75%] {{ $mountainFront }}"
            style="clip-path: polygon(
                0% 100%,
                10% 68%,
                23% 42%,
                35% 67%,
                49% 27%,
                62% 58%,
                74% 40%,
                87% 68%,
                100% 52%,
                100% 100%
            );"
        ></div>


        <div
            class="absolute bottom-[12%] right-[-8%] h-[50%] w-[70%] {{ $mountainFront }}"
            style="clip-path: polygon(
                0% 100%,
                14% 62%,
                29% 75%,
                43% 36%,
                57% 65%,
                71% 30%,
                84% 57%,
                100% 43%,
                100% 100%
            );"
        ></div>


        {{-- MOUNTAIN LIGHT --}}

        @if($timeOfDay === 'morning')

            <div
                class="absolute bottom-[31%] left-[31%] h-[8%] w-[10%] rotate-[25deg] bg-[#e9b98b]/60"
                style="clip-path: polygon(50% 0%, 100% 100%, 0% 100%);"
            ></div>

        @elseif($timeOfDay === 'afternoon')

            <div
                class="absolute bottom-[37%] left-[31%] h-[8%] w-[10%] rotate-[25deg] bg-[#b9cbd2]/50"
                style="clip-path: polygon(50% 0%, 100% 100%, 0% 100%);"
            ></div>

        @endif


        {{-- GROUND --}}

        <div
            class="absolute bottom-0 left-0 right-0 h-[21%] {{ $groundClass }}"
        ></div>


        {{-- WATER / HORIZON --}}

        <div
            class="absolute bottom-[12%] left-0 right-0 h-[9%] bg-[#7da7b2]/30"
        ></div>


        {{-- CONTENT OVERLAY --}}

        <div
            class="absolute inset-0 z-30 bg-gradient-to-r from-black/30 via-black/5 to-transparent"
        ></div>


        {{-- WELCOME CONTENT --}}

        <div
            class="relative z-40 flex h-full items-center px-7 py-6 sm:px-9"
        >

            <div class="max-w-xl">

                <div class="flex items-center gap-2">

                    @if($timeOfDay === 'morning')

                        <i class='bx bx-sun text-2xl text-[#ffe27a]'></i>

                    @elseif($timeOfDay === 'afternoon')

                        <i class='bx bx-sun text-2xl text-[#fff2a8]'></i>

                    @else

                        <i class='bx bx-moon text-2xl text-[#f5f0ce]'></i>

                    @endif

                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-white/90">
                        {{ $greeting }}
                    </p>

                </div>


                <h1
                    class="mt-1 text-3xl font-extrabold tracking-tight text-white drop-shadow-md sm:text-4xl"
                >
                    {{ $adminName }}
                </h1>


                <p
                    class="mt-2 max-w-md text-sm leading-6 text-white/90 drop-shadow-sm sm:text-base"
                >
                    {{ $timeMessage }}
                </p>


                <div
                    class="mt-3 flex items-center gap-2 text-xs font-medium text-white/75"
                >
                    <i class='bx bx-calendar text-base'></i>

                    <span>
                        {{ now()->format('l, F j, Y') }}
                    </span>
                </div>

            </div>

        </div>

    </div>




    {{-- ============================================================
    QUICK ACCESS — APP ICONS
    ============================================================= --}}

    <section>

        <div class="mb-3">
            <h2 class="text-sm font-bold text-slate-800">
                Quick Access
            </h2>

            <p class="mt-0.5 text-xs text-slate-400">
                Frequently used tools
            </p>
        </div>

        {{-- APP ICONS --}}
        <div class="grid grid-cols-3 gap-4 sm:grid-cols-5 sm:gap-6">

            @foreach([
                [
                    'route' => 'admin.transactions.index',
                    'label' => 'Transactions',
                    'icon' => 'bx-transfer',
                    'iconBg' => 'bg-blue-50',
                    'iconColor' => 'text-[#2A57B4]',
                ],
                [
                    'route' => 'admin.appointments.index',
                    'label' => 'Appointments',
                    'icon' => 'bx-calendar',
                    'iconBg' => 'bg-indigo-50',
                    'iconColor' => 'text-indigo-600',
                ],
                [
                    'route' => 'admin.clearance-monitoring',
                    'label' => 'Clearance',
                    'icon' => 'bx-clipboard',
                    'iconBg' => 'bg-emerald-50',
                    'iconColor' => 'text-emerald-600',
                ],
                [
                    'route' => 'admin.verification',
                    'label' => 'Verification',
                    'icon' => 'bx-id-card',
                    'iconBg' => 'bg-amber-50',
                    'iconColor' => 'text-amber-600',
                ],
                [
                    'route' => 'admin.reports',
                    'label' => 'Reports',
                    'icon' => 'bx-bar-chart',
                    'iconBg' => 'bg-purple-50',
                    'iconColor' => 'text-purple-600',
                ],
            ] as $app)

                <a
                    href="{{ route($app['route']) }}"
                    wire:navigate
                    class="group flex flex-col items-center justify-center rounded-2xl px-3 py-3 text-center transition duration-200 hover:bg-white/70"
                >

                    {{-- APP ICON --}}
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[18px]
                        {{ $app['iconBg'] }}
                        {{ $app['iconColor'] }}
                        shadow-sm ring-1 ring-black/[0.02]
                        transition duration-200
                        group-hover:-translate-y-1
                        group-hover:scale-105
                        group-hover:shadow-md"
                    >
                        <i class='bx {{ $app['icon'] }} text-[27px]'></i>
                    </div>

                    {{-- APP LABEL --}}
                    <span
                        class="mt-2.5 text-xs font-semibold text-slate-600 transition group-hover:text-[#2A57B4]"
                    >
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

        <div class="mb-4 flex items-end justify-between gap-3">

            <div>
                <h2 class="text-lg font-bold tracking-tight text-slate-900">
                    Action Center
                </h2>

                <p class="mt-0.5 text-sm text-slate-500">
                    Items that may require your attention.
                </p>
            </div>

        </div>


        <div class="grid gap-4 md:grid-cols-3">

            @foreach($actionItems as $item)

                @php
                    $toneClasses = match($item['tone']) {

                        'amber' => [
                            'box' => 'bg-amber-50 text-amber-600',
                            'number' => 'text-amber-600',
                            'hover' => 'hover:border-amber-200 hover:bg-amber-50/40',
                        ],

                        'red' => [
                            'box' => 'bg-red-50 text-red-600',
                            'number' => 'text-red-600',
                            'hover' => 'hover:border-red-200 hover:bg-red-50/40',
                        ],

                        default => [
                            'box' => 'bg-blue-50 text-[#2A57B4]',
                            'number' => 'text-[#2A57B4]',
                            'hover' => 'hover:border-blue-200 hover:bg-blue-50/40',
                        ],

                    };
                @endphp


                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    class="group rounded-2xl border border-[#d7e0ee] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] transition {{ $toneClasses['hover'] }}"
                >

                    <div class="flex items-start justify-between gap-4">

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl {{ $toneClasses['box'] }}"
                        >
                            <i class='bx {{ $item['icon'] }} text-xl'></i>
                        </div>

                        <span
                            class="text-3xl font-extrabold {{ $toneClasses['number'] }}"
                        >
                            {{ $item['count'] }}
                        </span>

                    </div>


                    <div class="mt-4">

                        <div class="flex items-center justify-between gap-2">

                            <h3 class="font-bold text-slate-900">
                                {{ $item['label'] }}
                            </h3>

                            <i
                                class='bx bx-right-arrow-alt text-lg text-slate-300 transition group-hover:translate-x-1 group-hover:text-slate-500'
                            ></i>

                        </div>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            {{ $item['description'] }}
                        </p>

                    </div>

                </a>

            @endforeach

        </div>

    </section>



    {{-- ============================================================
        SYSTEM OVERVIEW
    ============================================================= --}}

    <section>

        <div class="mb-4">

            <h2 class="text-lg font-bold tracking-tight text-slate-900">
                System Overview
            </h2>

            <p class="mt-0.5 text-sm text-slate-500">
                A simple look at accounts, transactions, and clearance progress.
            </p>

        </div>


        {{-- MAIN OVERVIEW CARD --}}

        <div
            class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.05)]"
        >

            {{-- TOP METRICS --}}

            <div class="grid sm:grid-cols-2 lg:grid-cols-4">


                {{-- STUDENTS / OFFICERS --}}

                <div
                    class="group relative p-6 transition hover:bg-blue-50/30"
                >

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[#2A57B4]"
                        >
                            <i class='bx bx-group text-2xl'></i>
                        </div>

                        <div class="min-w-0">

                            <p class="text-xs font-semibold text-slate-400">
                                Students & Officers
                            </p>

                            <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($kpis['total_students']) }}
                            </p>

                        </div>

                    </div>

                    <p class="mt-4 text-xs text-slate-400">
                        Registered users in the system
                    </p>

                </div>



                {{-- ACTIVE ACCOUNTS --}}

                <div
                    class="group border-t border-slate-100 p-6 transition hover:bg-emerald-50/30 sm:border-l"
                >

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600"
                        >
                            <i class='bx bx-user-check text-2xl'></i>
                        </div>

                        <div class="min-w-0">

                            <p class="text-xs font-semibold text-slate-400">
                                Active Accounts
                            </p>

                            <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($kpis['active_accounts']) }}
                            </p>

                        </div>

                    </div>

                    <div class="mt-4 flex items-center gap-1.5">

                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        <span class="text-xs text-slate-400">
                            Currently active
                        </span>

                    </div>

                </div>



                {{-- PENDING TRANSACTIONS --}}

                <div
                    class="group border-t border-slate-100 p-6 transition hover:bg-amber-50/30 lg:border-l lg:border-t-0"
                >

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600"
                        >
                            <i class='bx bx-transfer text-2xl'></i>
                        </div>

                        <div class="min-w-0">

                            <p class="text-xs font-semibold text-slate-400">
                                Pending Transactions
                            </p>

                            <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                                {{ number_format($kpis['pending_transactions']) }}
                            </p>

                        </div>

                    </div>


                    @if($kpis['pending_transactions'] > 0)

                        <div class="mt-4 flex items-center gap-1.5 text-xs font-semibold text-amber-600">

                            <i class='bx bx-error-circle'></i>

                            <span>
                                Needs attention
                            </span>

                        </div>

                    @else

                        <div class="mt-4 flex items-center gap-1.5 text-xs font-semibold text-emerald-600">

                            <i class='bx bx-check-circle'></i>

                            <span>
                                All caught up
                            </span>

                        </div>

                    @endif

                </div>



                {{-- CLEARANCE --}}

                <div
                    class="border-t border-slate-100 bg-slate-50/50 p-6 lg:border-l lg:border-t-0"
                >

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs font-semibold text-slate-400">
                                Clearance Rate
                            </p>

                            <div class="mt-1 flex items-baseline gap-1">

                                <span class="text-3xl font-extrabold tracking-tight text-[#2A57B4]">
                                    {{ $kpis['clearance_rate'] }}
                                </span>

                                <span class="font-bold text-slate-400">
                                    %
                                </span>

                            </div>

                        </div>


                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#2A57B4]"
                        >
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

                        <p class="mt-2 text-[11px] text-slate-400">
                            Overall clearance progress
                        </p>

                    </div>

                </div>

            </div>



            {{-- OPERATIONS SUMMARY --}}

            <div class="grid border-t border-slate-100 sm:grid-cols-2">


                {{-- TODAY'S APPOINTMENTS --}}

                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="group flex items-center justify-between gap-4 p-5 transition hover:bg-blue-50/30 sm:px-6"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#2A57B4]"
                        >
                            <i class='bx bx-calendar text-xl'></i>
                        </div>

                        <div>

                            <p class="text-sm font-bold text-slate-800">
                                Today's Appointments
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Scheduled for today
                            </p>

                        </div>

                    </div>


                    <div class="flex items-center gap-2">

                        <span class="text-xl font-extrabold text-slate-900">
                            {{ $kpis['todays_appointments'] }}
                        </span>

                        <i
                            class='bx bx-chevron-right text-lg text-slate-300 transition group-hover:translate-x-1 group-hover:text-[#2A57B4]'
                        ></i>

                    </div>

                </a>



                {{-- APPOINTMENT REQUESTS --}}

                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="group flex items-center justify-between gap-4 border-t border-slate-100 p-5 transition hover:bg-red-50/30 sm:border-l sm:border-t-0 sm:px-6"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600"
                        >
                            <i class='bx bx-calendar-exclamation text-xl'></i>
                        </div>

                        <div>

                            <p class="text-sm font-bold text-slate-800">
                                Appointment Requests
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Awaiting admin review
                            </p>

                        </div>

                    </div>


                    <div class="flex items-center gap-2">

                        <span
                            class="text-xl font-extrabold {{
                                $kpis['pending_appointment_review'] > 0
                                    ? 'text-red-600'
                                    : 'text-slate-900'
                            }}"
                        >
                            {{ $kpis['pending_appointment_review'] }}
                        </span>

                        <i
                            class='bx bx-chevron-right text-lg text-slate-300 transition group-hover:translate-x-1 group-hover:text-red-500'
                        ></i>

                    </div>

                </a>

            </div>

        </div>

    </section>



    {{-- ============================================================
        RECENT ACTIVITY + TODAY'S OPERATIONS
    ============================================================= --}}

    <div class="grid gap-6 lg:grid-cols-[1fr_1.15fr]">


        {{-- ========================================================
            RECENT ACTIVITY
        ========================================================= --}}

        <section
            class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]"
        >

            <div class="flex items-center justify-between gap-3">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        Recent Activity
                    </h2>

                    <p class="mt-0.5 text-xs text-slate-400">
                        Latest activity across the system.
                    </p>

                </div>


                <div
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400"
                >
                    <i class='bx bx-history text-lg'></i>
                </div>

            </div>


            <div class="mt-5 divide-y divide-slate-100">

                @forelse($recentActivity as $activity)

                    @php

                        $isAppointment = $activity->type === 'appointment';

                        $status = strtolower((string) $activity->status);

                        $statusClass = match($status) {

                            'completed',
                            'approved',
                            'cleared',
                            'attended'
                                => 'bg-emerald-50 text-emerald-700',

                            'pending',
                            'ongoing'
                                => 'bg-amber-50 text-amber-700',

                            'rejected',
                            'cancelled',
                            'declined',
                            'missed'
                                => 'bg-red-50 text-red-700',

                            default
                                => 'bg-slate-100 text-slate-600',

                        };

                    @endphp


                    <div class="flex items-center justify-between gap-3 py-4">

                        <div class="flex min-w-0 items-center gap-3">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{
                                    $isAppointment
                                        ? 'bg-blue-50 text-[#2A57B4]'
                                        : 'bg-emerald-50 text-emerald-600'
                                }}"
                            >

                                <i
                                    class='bx {{
                                        $isAppointment
                                            ? 'bx-calendar'
                                            : 'bx-file'
                                    }} text-lg'
                                ></i>

                            </div>


                            <div class="min-w-0">

                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $activity->user_name }}
                                </p>

                                <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-400">

                                    <span>
                                        {{ ucfirst($activity->type) }}
                                    </span>

                                    <span>·</span>

                                    <span>
                                        {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                    </span>

                                </div>

                            </div>

                        </div>


                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $statusClass }}"
                        >
                            {{ $activity->status }}
                        </span>

                    </div>


                @empty

                    <div class="py-10 text-center">

                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-300"
                        >
                            <i class='bx bx-history text-2xl'></i>
                        </div>

                        <p class="mt-3 text-sm font-medium text-slate-500">
                            No recent activity yet.
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            New system activity will appear here.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>



        {{-- ========================================================
            TODAY'S OPERATIONS
        ========================================================= --}}

        <section
            class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]"
        >

            <div class="flex flex-wrap items-start justify-between gap-3">

                <div>

                    <h2 class="text-lg font-bold text-slate-900">
                        Today's Operations
                    </h2>

                    <p class="mt-0.5 text-xs text-slate-400">
                        Appointments scheduled for today.
                    </p>

                </div>


                <a
                    href="{{ route('admin.appointments.index') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#2A57B4] hover:underline"
                >
                    View all
                    <i class='bx bx-right-arrow-alt text-base'></i>
                </a>

            </div>


            @if($todaysAppointments->count())

                <div class="mt-5 space-y-2.5">

                    @foreach($todaysAppointments as $appointment)

                        @php

                            $appointmentStatus = strtolower(
                                (string) $appointment->status
                            );

                            $appointmentStatusClass = match($appointmentStatus) {

                                'approved',
                                'confirmed',
                                'attended',
                                'completed'
                                    => 'bg-emerald-50 text-emerald-700',

                                'pending'
                                    => 'bg-amber-50 text-amber-700',

                                'cancelled',
                                'rejected',
                                'missed'
                                    => 'bg-red-50 text-red-700',

                                default
                                    => 'bg-slate-100 text-slate-600',

                            };

                        @endphp


                        <div
                            class="group flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 p-3.5 transition hover:border-slate-200 hover:bg-white"
                        >

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#2A57B4] shadow-sm"
                            >
                                <i class='bx bx-calendar text-lg'></i>
                            </div>


                            <div class="min-w-0 flex-1">

                                <p class="truncate text-sm font-bold text-slate-800">
                                    {{ $appointment->user_name }}
                                </p>

                                <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-400">

                                    <i class='bx bx-time-five'></i>

                                    <span>
                                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('g:i A') }}
                                    </span>

                                </div>

                            </div>


                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold capitalize {{ $appointmentStatusClass }}"
                            >
                                {{ $appointment->status }}
                            </span>

                        </div>

                    @endforeach

                </div>


            @else

                <div
                    class="mt-5 flex min-h-[230px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-5 text-center"
                >

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-300 shadow-sm"
                    >
                        <i class='bx bx-calendar-check text-2xl'></i>
                    </div>

                    <p class="mt-3 text-sm font-semibold text-slate-600">
                        No appointments today
                    </p>

                    <p class="mt-1 max-w-xs text-xs leading-5 text-slate-400">
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
            class="flex items-center justify-center gap-2 rounded-xl bg-[#2A57B4] px-4 py-3 text-sm font-semibold text-white shadow-sm"
        >
            <i class='bx bx-transfer text-lg'></i>
            Review Transactions
        </a>


        <a
            href="{{ route('admin.appointments.index') }}"
            wire:navigate
            class="flex items-center justify-center gap-2 rounded-xl border border-[#d7e0ee] bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm"
        >
            <i class='bx bx-calendar text-lg'></i>
            Manage Appointments
        </a>

    </div>

</div>

