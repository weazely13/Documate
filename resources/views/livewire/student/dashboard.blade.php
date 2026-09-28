<div
    class="space-y-6 sm:space-y-8 pb-12"
    wire:poll.5s
>

    {{-- =========================================================
        ACCOUNT VERIFICATION
    ========================================================== --}}
    @include('livewire.partials.verify-account-card')


    {{-- =========================================================
        CLEARANCE THEME
    ========================================================== --}}
    @php

        $statusTheme = fn (string $status) => match ($status) {

            'Cleared' => [
                'badge' => 'bg-emerald-500/10 text-emerald-700 border-emerald-200/80',
                'border' => 'border-emerald-200/60',
                'bg' => 'bg-emerald-50/40',
                'icon' => 'bx-check-shield',
                'iconWrap' => 'bg-emerald-500 text-white shadow-emerald-500/25',
                'word' => 'text-emerald-600',
                'accent' => 'bg-emerald-500',
            ],

            'Pending' => [
                'badge' => 'bg-amber-500/10 text-amber-700 border-amber-200/80',
                'border' => 'border-amber-200/60',
                'bg' => 'bg-amber-50/40',
                'icon' => 'bx-time-five',
                'iconWrap' => 'bg-amber-500 text-white shadow-amber-500/25',
                'word' => 'text-amber-600',
                'accent' => 'bg-amber-500',
            ],

            'Uncleared' => [
                'badge' => 'bg-rose-500/10 text-rose-700 border-rose-200/60',
                'border' => 'border-rose-200/60',
                'bg' => 'bg-rose-50/40',
                'icon' => 'bx-x-circle',
                'iconWrap' => 'bg-rose-500 text-white shadow-rose-500/25',
                'word' => 'text-rose-600',
                'accent' => 'bg-rose-500',
            ],

            default => [
                'badge' => 'bg-slate-500/10 text-slate-700 border-slate-200',
                'border' => 'border-slate-200',
                'bg' => 'bg-slate-50/40',
                'icon' => 'bx-help-circle',
                'iconWrap' => 'bg-slate-500 text-white shadow-slate-500/25',
                'word' => 'text-slate-600',
                'accent' => 'bg-slate-400',
            ],
        };

        $currentTheme = $statusTheme(
            $currentClearanceStatus['status'] ?? 'Default'
        );

    @endphp


    {{-- =========================================================
        WELCOME + DATES TO REMEMBER
    ========================================================== --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">


        {{-- =====================================================
            TIME-BASED WELCOME CARD
        ====================================================== --}}
        <div
            x-data="{
                hour: new Date().getHours(),

                get period() {
                    if (this.hour >= 5 && this.hour < 12) {
                        return 'morning';
                    }

                    if (this.hour >= 12 && this.hour < 18) {
                        return 'afternoon';
                    }

                    return 'evening';
                },

                get greeting() {
                    if (this.period === 'morning') {
                        return 'Good morning';
                    }

                    if (this.period === 'afternoon') {
                        return 'Good afternoon';
                    }

                    return 'Good evening';
                }
            }"
            x-init="
                setInterval(() => {
                    hour = new Date().getHours()
                }, 60000)
            "
            class="
                lg:col-span-2
                relative
                overflow-hidden
                rounded-3xl
                border
                border-slate-200/80
                shadow-sm
                min-h-[310px]
                sm:min-h-[340px]
            "
        >

            {{-- MORNING --}}
            <div
                x-show="period === 'morning'"
                x-transition.opacity
                class="absolute inset-0 overflow-hidden"
            >

                <div class="absolute inset-0 bg-gradient-to-b from-sky-100 via-blue-50 to-amber-50"></div>

                <div class="
                    absolute
                    left-[20%]
                    bottom-[24%]
                    h-20
                    w-20
                    sm:h-24
                    sm:w-24
                    rounded-full
                    bg-amber-300
                    shadow-[0_0_60px_rgba(251,191,36,0.55)]
                "></div>

                <div
                    class="absolute bottom-0 left-0 w-full h-32 sm:h-40 bg-slate-400/70"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 65%,
                            15% 35%,
                            28% 70%,
                            43% 25%,
                            60% 65%,
                            76% 38%,
                            90% 68%,
                            100% 45%,
                            100% 100%
                        );
                    "
                ></div>

                <div
                    class="absolute bottom-0 left-0 w-full h-24 sm:h-32 bg-slate-600/80"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 60%,
                            20% 30%,
                            35% 68%,
                            50% 20%,
                            66% 70%,
                            82% 40%,
                            100% 62%,
                            100% 100%
                        );
                    "
                ></div>

            </div>


            {{-- AFTERNOON --}}
            <div
                x-show="period === 'afternoon'"
                x-transition.opacity
                class="absolute inset-0 overflow-hidden"
            >

                <div class="absolute inset-0 bg-gradient-to-b from-sky-300 via-sky-100 to-blue-50"></div>

                <div class="
                    absolute
                    top-[15%]
                    right-[18%]
                    h-20
                    w-20
                    sm:h-24
                    sm:w-24
                    rounded-full
                    bg-yellow-300
                    shadow-[0_0_70px_rgba(253,224,71,0.65)]
                "></div>

                <div class="absolute top-[20%] left-[12%] opacity-70">
                    <div class="h-5 w-20 rounded-full bg-white"></div>
                    <div class="h-7 w-12 rounded-full bg-white -mt-5 ml-5"></div>
                </div>

                <div class="absolute top-[34%] right-[36%] opacity-60">
                    <div class="h-4 w-16 rounded-full bg-white"></div>
                    <div class="h-6 w-10 rounded-full bg-white -mt-5 ml-4"></div>
                </div>

                <div
                    class="absolute bottom-0 left-0 w-full h-32 sm:h-40 bg-slate-500/75"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 62%,
                            15% 38%,
                            29% 68%,
                            44% 25%,
                            60% 65%,
                            76% 38%,
                            91% 67%,
                            100% 46%,
                            100% 100%
                        );
                    "
                ></div>

                <div
                    class="absolute bottom-0 left-0 w-full h-24 sm:h-32 bg-slate-700/80"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 64%,
                            20% 32%,
                            36% 70%,
                            51% 22%,
                            67% 70%,
                            82% 42%,
                            100% 64%,
                            100% 100%
                        );
                    "
                ></div>

            </div>


            {{-- EVENING --}}
            <div
                x-show="period === 'evening'"
                x-transition.opacity
                class="absolute inset-0 overflow-hidden"
            >

                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950 via-indigo-900 to-slate-900"></div>

                <div class="
                    absolute
                    top-[15%]
                    right-[18%]
                    h-20
                    w-20
                    sm:h-24
                    sm:w-24
                    rounded-full
                    bg-slate-100
                    shadow-[0_0_55px_rgba(255,255,255,0.35)]
                ">

                    <div class="absolute top-3 left-6 h-3 w-3 rounded-full bg-slate-300/70"></div>
                    <div class="absolute top-12 left-10 h-4 w-4 rounded-full bg-slate-300/60"></div>
                    <div class="absolute top-7 right-5 h-2 w-2 rounded-full bg-slate-300/70"></div>

                </div>

                <div class="absolute top-[18%] left-[15%] h-1.5 w-1.5 rounded-full bg-white"></div>
                <div class="absolute top-[28%] left-[28%] h-1 w-1 rounded-full bg-white"></div>
                <div class="absolute top-[14%] left-[48%] h-1.5 w-1.5 rounded-full bg-white"></div>
                <div class="absolute top-[32%] right-[28%] h-1 w-1 rounded-full bg-white"></div>
                <div class="absolute top-[12%] right-[40%] h-1 w-1 rounded-full bg-white"></div>

                <div
                    class="absolute bottom-0 left-0 w-full h-32 sm:h-40 bg-slate-800"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 62%,
                            15% 38%,
                            29% 68%,
                            44% 25%,
                            60% 65%,
                            76% 38%,
                            91% 67%,
                            100% 46%,
                            100% 100%
                        );
                    "
                ></div>

                <div
                    class="absolute bottom-0 left-0 w-full h-24 sm:h-32 bg-slate-950"
                    style="
                        clip-path: polygon(
                            0 100%,
                            0 64%,
                            20% 32%,
                            36% 70%,
                            51% 22%,
                            67% 70%,
                            82% 42%,
                            100% 64%,
                            100% 100%
                        );
                    "
                ></div>

            </div>


            {{-- WELCOME CONTENT --}}
            <div class="relative z-10 flex h-full min-h-[310px] sm:min-h-[340px] flex-col justify-between p-6 sm:p-8">

                <div>

                    <div
                        x-show="period === 'morning'"
                        class="inline-flex items-center gap-2 rounded-full bg-white/70 backdrop-blur-md border border-white/60 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-700"
                    >
                        <i class='bx bx-sun text-amber-500'></i>
                        Morning
                    </div>

                    <div
                        x-show="period === 'afternoon'"
                        class="inline-flex items-center gap-2 rounded-full bg-white/70 backdrop-blur-md border border-white/60 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-700"
                    >
                        <i class='bx bx-sun text-orange-500'></i>
                        Afternoon
                    </div>

                    <div
                        x-show="period === 'evening'"
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white"
                    >
                        <i class='bx bx-moon text-yellow-200'></i>
                        Evening
                    </div>


                    <h1
                        class="mt-4 text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight leading-tight"
                        :class="period === 'evening'
                            ? 'text-white'
                            : 'text-slate-900'"
                    >
                        <span x-text="greeting"></span>,
                        {{ $fullName ?: ($user->first_name ?? 'Student') }}!
                    </h1>


                    <p
                        class="mt-2 max-w-xl text-sm sm:text-base leading-relaxed"
                        :class="period === 'evening'
                            ? 'text-slate-200'
                            : 'text-slate-600'"
                    >
                        Welcome to your student portal. Manage your
                        documents, appointments, and clearance status
                        in one place.
                    </p>

                </div>

            </div>

        </div>


        {{-- =====================================================
            DATES TO REMEMBER
        ====================================================== --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div class="flex items-center gap-2">

                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <i class='bx bx-calendar-event text-lg'></i>
                    </div>

                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Dates to Remember
                        </h2>

                        <p class="text-xs text-slate-500">
                            Upcoming appointments
                        </p>
                    </div>

                </div>


                <a
                    href="{{ route('student.appointments.new') }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                >
                    Book
                </a>

            </div>


            <div class="mt-5 space-y-3">

                @forelse($stats['upcoming_appointments'] as $appointment)

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 p-3">

                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-white border border-slate-100 shadow-sm">

                            <span class="text-[9px] font-bold uppercase text-slate-400">
                                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M') }}
                            </span>

                            <span class="text-lg font-extrabold leading-none text-slate-900">
                                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d') }}
                            </span>

                        </div>


                        <div class="min-w-0">

                            <p class="truncate text-sm font-bold text-slate-900">
                                VPSD Appointment
                            </p>

                            <p class="mt-0.5 text-xs font-medium text-slate-500 capitalize">
                                {{ $appointment->session ?? 'Scheduled session' }}
                            </p>

                        </div>

                    </div>

                @empty

                    <div class="flex min-h-[170px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-5 text-center">

                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class='bx bx-calendar-x text-xl'></i>
                        </div>

                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            No upcoming appointments
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            You don't have any scheduled VPSD appointments.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>

    </section>


    {{-- =========================================================
        DOCUMENT OVERVIEW
    ========================================================== --}}
    <section>

        <div class="mb-4 flex items-end justify-between gap-4">

            <div>

                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Document Activity
                </span>

                <h2 class="mt-0.5 text-xl font-bold text-slate-900">
                    Your Documents
                </h2>

            </div>


            <a
                href="{{ route('student.documents.index') }}"
                class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700"
            >
                View all
                <i class='bx bx-chevron-right'></i>
            </a>

        </div>


        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">


            {{-- =================================================
                COMPLETED DOCUMENTS
            ================================================== --}}
            <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">

                <div class="flex items-center justify-between">

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-sm">
                        <i class='bx bx-check-double text-xl'></i>
                    </div>

                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">
                        Completed
                    </span>

                </div>


                <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">
                    {{ $stats['transactions']['completed'] }}
                </p>

                <p class="mt-1 text-sm font-medium text-slate-500">
                    Completed documents
                </p>

            </div>


            {{-- =================================================
                PENDING DOCUMENTS
            ================================================== --}}
            <div class="rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">

                <div class="flex items-center justify-between">

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-sm">
                        <i class='bx bx-time-five text-xl'></i>
                    </div>

                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600">
                        Pending
                    </span>

                </div>


                <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">
                    {{ $stats['transactions']['pending'] }}
                </p>

                <p class="mt-1 text-sm font-medium text-slate-500">
                    Documents in progress
                </p>

            </div>


            {{-- =================================================
                LAST DOCUMENT UPDATED / CREATED
            ================================================== --}}
            <div class="rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">

                <div class="flex items-start justify-between gap-3">

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                        <i class='bx bx-file-blank text-xl'></i>
                    </div>


                    @if($stats['latest_document'])

                        @php
                            $latestStatus = $stats['latest_document']->status ?? 'pending';

                            $latestStatusLabel = match ($latestStatus) {
                                'completed' => 'Completed',
                                'pending' => 'In Progress',
                                default => ucfirst(str_replace('_', ' ', $latestStatus)),
                            };

                            $latestStatusClass = match ($latestStatus) {
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                default => 'bg-slate-50 text-slate-600 border-slate-100',
                            };
                        @endphp

                        <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-1 text-[9px] font-bold uppercase tracking-wide {{ $latestStatusClass }}">
                            {{ $latestStatusLabel }}
                        </span>

                    @else

                        <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
                            Recent
                        </span>

                    @endif

                </div>


                @if($stats['latest_document'])

                    @php
                        $latestDocument = $stats['latest_document'];

                        $latestDocumentName =
                            $latestDocument->template?->name
                            ?? 'Document Transaction';
                    @endphp


                    {{-- DOCUMENT NAME --}}
                    <p class="mt-5 line-clamp-2 text-base font-extrabold leading-snug text-slate-900">
                        {{ $latestDocumentName }}
                    </p>


                    {{-- LAST UPDATED --}}
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-medium text-slate-500">

                        <i class='bx bx-time-five text-sm text-blue-500'></i>

                        <span>
                            Updated
                            {{ $latestDocument->updated_at
                                ? \Carbon\Carbon::parse($latestDocument->updated_at)->diffForHumans()
                                : 'recently'
                            }}
                        </span>

                    </div>


                    {{-- EXACT DATE --}}
                    @if($latestDocument->updated_at)

                        <p class="mt-1 text-[10px] text-slate-400">
                            {{ \Carbon\Carbon::parse($latestDocument->updated_at)->format('M d, Y • h:i A') }}
                        </p>

                    @endif


                    {{-- WORKSPACE ID --}}
                    @if($latestDocument->workspace_id)

                        <div class="mt-3 flex items-center gap-1.5">

                            <i class='bx bx-hash text-xs text-slate-400'></i>

                            <span class="truncate text-[10px] font-medium text-slate-400">
                                {{ $latestDocument->workspace_id }}
                            </span>

                        </div>

                    @endif

                @else

                    <p class="mt-5 text-base font-extrabold text-slate-400">
                        No documents yet
                    </p>

                    <p class="mt-1 text-xs font-medium text-slate-500">
                        Your latest document transaction will appear here.
                    </p>

                @endif

            </div>

        </div>

    </section>


    {{-- =========================================================
        APPOINTMENT SUMMARY + CLEARANCE
    ========================================================== --}}
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">


        {{-- =====================================================
            APPOINTMENT SUMMARY
        ====================================================== --}}
        <div class="xl:col-span-7 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between gap-4 pb-5 border-b border-slate-100">

                <div class="flex items-center gap-3">

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <i class='bx bx-calendar-check text-xl'></i>
                    </div>

                    <div>

                        <h2 class="text-base font-bold text-slate-900">
                            Appointment Summary
                        </h2>

                        <p class="text-xs text-slate-500">
                            Your VPSD appointment activity
                        </p>

                    </div>

                </div>


                <a
                    href="{{ route('student.appointments.index') }}"
                    class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700"
                >
                    Manage
                    <i class='bx bx-chevron-right'></i>
                </a>

            </div>


            {{-- SUMMARY NUMBERS --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">

                <div class="rounded-2xl bg-slate-50 p-4">

                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Total
                    </span>

                    <p class="mt-2 text-2xl font-extrabold text-slate-900">
                        {{ $stats['appointments']['total'] }}
                    </p>

                </div>


                <div class="rounded-2xl bg-blue-50/70 p-4">

                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-500">
                        Upcoming
                    </span>

                    <p class="mt-2 text-2xl font-extrabold text-blue-700">
                        {{ $stats['appointments']['upcoming'] }}
                    </p>

                </div>


                <div class="rounded-2xl bg-amber-50/70 p-4">

                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">
                        Pending
                    </span>

                    <p class="mt-2 text-2xl font-extrabold text-amber-700">
                        {{ $stats['appointments']['pending'] }}
                    </p>

                </div>


                <div class="rounded-2xl bg-emerald-50/70 p-4">

                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">
                        Attended
                    </span>

                    <p class="mt-2 text-2xl font-extrabold text-emerald-700">
                        {{ $stats['appointments']['attended'] }}
                    </p>

                </div>

            </div>


            {{-- NEXT APPOINTMENT --}}
            @if($stats['next_appointment'])

                <div class="mt-5 flex items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">

                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-white border border-blue-100 shadow-sm">

                        <span class="text-[9px] font-bold uppercase tracking-wider text-blue-500">
                            {{ \Carbon\Carbon::parse($stats['next_appointment']->appointment_date)->format('M') }}
                        </span>

                        <span class="text-xl font-extrabold leading-none text-slate-900">
                            {{ \Carbon\Carbon::parse($stats['next_appointment']->appointment_date)->format('d') }}
                        </span>

                    </div>


                    <div class="min-w-0">

                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">
                            Next Appointment
                        </span>

                        <p class="mt-0.5 text-sm font-bold text-slate-900">
                            {{ \Carbon\Carbon::parse($stats['next_appointment']->appointment_date)->format('F d, Y') }}
                        </p>

                        <p class="mt-0.5 text-xs font-medium text-slate-500 capitalize">
                            {{ $stats['next_appointment']->session ?? 'Scheduled session' }}
                        </p>

                    </div>

                </div>

            @else

                <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-4">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-slate-400 border border-slate-100">
                            <i class='bx bx-calendar-plus text-xl'></i>
                        </div>

                        <div>

                            <p class="text-sm font-bold text-slate-700">
                                No upcoming appointment
                            </p>

                            <p class="text-xs text-slate-500">
                                Schedule a VPSD appointment when needed.
                            </p>

                        </div>

                    </div>


                    <a
                        href="{{ route('student.appointments.new') }}"
                        class="shrink-0 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                    >
                        Book
                    </a>

                </div>

            @endif

        </div>


        {{-- =====================================================
            CLEARANCE STATUS
        ====================================================== --}}
        <div class="xl:col-span-5 space-y-4">

            <div
                class="
                    relative
                    overflow-hidden
                    rounded-3xl
                    border
                    {{ $currentTheme['border'] }}
                    {{ $currentTheme['bg'] }}
                    p-6
                    shadow-md
                    backdrop-blur-xs
                "
            >

                <div class="relative z-10 flex h-full flex-col justify-between space-y-6">


                    {{-- HEADER --}}
                    <div class="flex items-start justify-between gap-2">

                        <div class="flex items-center gap-3">

                            <span
                                class="
                                    flex
                                    h-12
                                    w-12
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-xl
                                    shadow-md
                                    {{ $currentTheme['iconWrap'] }}
                                "
                            >
                                <i class='bx {{ $currentTheme['icon'] }} text-2xl'></i>
                            </span>


                            <div>

                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Official Record
                                </span>

                                <p class="text-xs font-bold text-slate-700 sm:text-sm break-words">
                                    {{ $currentClearanceStatus['period_label'] ?? 'Current Term' }}
                                </p>

                            </div>

                        </div>


                        <span
                            class="
                                inline-flex
                                items-center
                                gap-1.5
                                rounded-full
                                border
                                px-3
                                py-1
                                text-[11px]
                                font-bold
                                uppercase
                                tracking-wider
                                shadow-2xs
                                {{ $currentTheme['badge'] }}
                            "
                        >

                            <span
                                class="
                                    h-1.5
                                    w-1.5
                                    rounded-full
                                    {{ $currentTheme['accent'] }}
                                    animate-pulse
                                "
                            ></span>

                            Current Clearance

                        </span>

                    </div>


                    {{-- STATUS --}}
                    <div>

                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
                            Clearance Status
                        </span>

                        <p
                            class="
                                text-3xl
                                font-black
                                tracking-tight
                                sm:text-4xl
                                {{ $currentTheme['word'] }}
                            "
                        >
                            {{ $currentClearanceStatus['status_word'] ?? 'Unknown' }}
                        </p>

                    </div>


                    {{-- FOOTER --}}
                    <div class="grid grid-cols-1 gap-3 border-t border-slate-200/60 pt-4 text-xs sm:grid-cols-3 sm:gap-2">

                        <div>

                            <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">
                                Student Holder
                            </span>

                            <p class="mt-0.5 font-bold tracking-tight text-slate-900 break-words">
                                {{ $fullName ?: ($user->first_name ?? 'Student') }}
                            </p>

                            <p class="text-[10px] font-medium text-slate-500 break-words">
                                {{ $user->student_number ?? 'No ID' }}
                                &bull;
                                {{ $formattedYearLevel }}
                            </p>

                        </div>


                        <div>

                            <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">
                                Tagged By
                            </span>

                            <p class="mt-0.5 font-semibold text-slate-800 break-words">
                                {{ $currentClearanceStatus['tagged_by'] ?: '-' }}
                            </p>

                        </div>


                        <div class="sm:text-right">

                            <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">
                                Remarks
                            </span>

                            <p class="mt-0.5 font-medium text-slate-700 break-words">
                                {{ $currentClearanceStatus['remarks'] ?: '-' }}
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            <a
                href="{{ route('student.clearance-status') }}"
                class="
                    inline-flex
                    w-full
                    items-center
                    justify-center
                    gap-2
                    rounded-2xl
                    bg-slate-900
                    px-5
                    py-3.5
                    text-sm
                    font-semibold
                    text-white
                    shadow-sm
                    hover:bg-slate-800
                    active:scale-[0.98]
                    transition-all
                "
            >
                <i class='bx bx-check-shield text-base'></i>
                <span>Open Full Clearance Status</span>
            </a>

        </div>

    </section>


    {{-- =========================================================
        MOBILE APPOINTMENT BUTTON
    ========================================================== --}}
    <div class="sm:hidden">

        <a
            href="{{ route('student.appointments.new') }}"
            class="
                flex
                w-full
                items-center
                justify-center
                gap-2
                rounded-2xl
                border
                border-slate-200
                bg-white
                px-5
                py-3.5
                text-sm
                font-semibold
                text-slate-700
                shadow-sm
                hover:bg-slate-50
            "
        >
            <i class='bx bx-calendar-plus text-lg text-blue-600'></i>
            Manage Appointments
        </a>

    </div>

</div>