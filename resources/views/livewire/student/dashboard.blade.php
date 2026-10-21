@php
    $displayName = $fullName ?: ($user->first_name ?? 'Student');

    /* ---------- Clearance theme ---------- */
    $statusTheme = fn (string $status) => match ($status) {
        'Cleared' => [
            'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'icon' => 'bx-check-shield',
            'iconWrap' => 'bg-emerald-500 text-white',
            'word' => 'text-emerald-600',
            'bar' => 'bg-emerald-500',
        ],
        'Pending' => [
            'badge' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'icon' => 'bx-time-five',
            'iconWrap' => 'bg-amber-500 text-white',
            'word' => 'text-amber-600',
            'bar' => 'bg-amber-500',
        ],
        'Uncleared' => [
            'badge' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            'icon' => 'bx-x-circle',
            'iconWrap' => 'bg-rose-500 text-white',
            'word' => 'text-rose-600',
            'bar' => 'bg-rose-500',
        ],
        default => [
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'icon' => 'bx-help-circle',
            'iconWrap' => 'bg-slate-500 text-white',
            'word' => 'text-slate-600',
            'bar' => 'bg-slate-400',
        ],
    };

    $currentTheme = $statusTheme($currentClearanceStatus['status'] ?? 'Default');

    /* ---------- One combined "upcoming" list (notices + appointments), max 4 ---------- */
    $upcoming = collect($stats['office_notices'])->map(function ($n) {
            $nd = \App\Workflow\Documents\DocumentRegistry::get($n->document_type);
            $nv = $n->field_values ?? [];

            return [
                'key'   => 'notice-' . $n->workspace_id,
                'date'  => \Carbon\Carbon::parse($nd->noticeDate($nv) ?? $n->submitted_at ?? $n->created_at),
                'title' => $nd->noticeTitle($nv),
                'line'  => $nd->noticeLine($nv),
                'rose'  => $nd->noticeTone() === 'rose',
                'url'   => route('student.transactions.show', $n->workspace_id),
            ];
        })
        ->concat(
            collect($stats['upcoming_appointments'])->map(fn ($a, $i) => [
                'key'   => 'appt-' . $i,
                'date'  => \Carbon\Carbon::parse($a->appointment_date),
                'title' => 'VPSD Appointment',
                'line'  => ucfirst($a->session ?? 'Scheduled session'),
                'rose'  => false,
                'url'   => route('student.appointments.index'),
            ])
        )
        ->sortBy('date')
        ->take(4)
        ->values();

    /* ---------- Latest document ---------- */
    $latestDocument = $stats['latest_document'] ?? null;

    if ($latestDocument) {
        $latestStatus = $stats['latest_document_status'];

        $latestStatusLabel = match ($latestStatus) {
            'completed' => 'Completed',
            'pending'   => 'In progress',
            default     => ucfirst(str_replace('_', ' ', $latestStatus)),
        };

        $latestStatusClass = match ($latestStatus) {
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'pending', 'draft', 'submitted', 'under_review', 'action_required', 'approved'
                => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'rejected'  => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            default     => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };

        $latestDocumentName = $latestDocument->template?->name ?? 'Document Transaction';
    }
@endphp

{{-- relative z-0 traps every card in its own stacking context so nothing can paint over the header/notification dropdown --}}
<div class="relative z-0 space-y-6 pb-12" wire:poll.30s>

    {{-- ACCOUNT VERIFICATION (only shows when action is needed) --}}
    @include('livewire.partials.verify-account-card')


    {{-- ============================================================
        WELCOME + CLEARANCE STATUS
    ============================================================= --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- TIME-OF-DAY WELCOME CARD --}}
        <div
            x-data="{
                hour: new Date().getHours(),
                get period() {
                    if (this.hour >= 5 && this.hour < 12) return 'morning';
                    if (this.hour >= 12 && this.hour < 18) return 'afternoon';
                    return 'evening';
                },
                get greeting() {
                    return { morning: 'Good morning', afternoon: 'Good afternoon', evening: 'Good evening' }[this.period];
                }
            }"
            x-init="setInterval(() => { hour = new Date().getHours() }, 60000)"
            class="relative isolate min-h-[220px] overflow-hidden rounded-xl border border-slate-200 shadow-sm sm:min-h-[240px] lg:col-span-2"
        >

            {{-- MORNING --}}
            <div x-show="period === 'morning'" x-transition.opacity class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-b from-sky-100 via-blue-50 to-amber-50"></div>
                <div class="absolute bottom-[24%] left-[20%] h-16 w-16 rounded-full bg-amber-300 shadow-[0_0_60px_rgba(251,191,36,0.55)] sm:h-20 sm:w-20"></div>
                <div class="absolute bottom-0 left-0 h-28 w-full bg-slate-400/70 sm:h-32"
                     style="clip-path: polygon(0 100%, 0 65%, 15% 35%, 28% 70%, 43% 25%, 60% 65%, 76% 38%, 90% 68%, 100% 45%, 100% 100%);"></div>
                <div class="absolute bottom-0 left-0 h-20 w-full bg-slate-600/80 sm:h-24"
                     style="clip-path: polygon(0 100%, 0 60%, 20% 30%, 35% 68%, 50% 20%, 66% 70%, 82% 40%, 100% 62%, 100% 100%);"></div>
            </div>

            {{-- AFTERNOON --}}
            <div x-show="period === 'afternoon'" x-transition.opacity class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-b from-sky-300 via-sky-100 to-blue-50"></div>
                <div class="absolute right-[18%] top-[15%] h-16 w-16 rounded-full bg-yellow-300 shadow-[0_0_70px_rgba(253,224,71,0.65)] sm:h-20 sm:w-20"></div>
                <div class="absolute left-[12%] top-[20%] opacity-70">
                    <div class="h-5 w-20 rounded-full bg-white"></div>
                    <div class="-mt-5 ml-5 h-7 w-12 rounded-full bg-white"></div>
                </div>
                <div class="absolute bottom-0 left-0 h-28 w-full bg-slate-500/75 sm:h-32"
                     style="clip-path: polygon(0 100%, 0 62%, 15% 38%, 29% 68%, 44% 25%, 60% 65%, 76% 38%, 91% 67%, 100% 46%, 100% 100%);"></div>
                <div class="absolute bottom-0 left-0 h-20 w-full bg-slate-700/80 sm:h-24"
                     style="clip-path: polygon(0 100%, 0 64%, 20% 32%, 36% 70%, 51% 22%, 67% 70%, 82% 42%, 100% 64%, 100% 100%);"></div>
            </div>

            {{-- EVENING --}}
            <div x-show="period === 'evening'" x-transition.opacity class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950 via-indigo-900 to-slate-900"></div>
                <div class="absolute right-[18%] top-[15%] h-14 w-14 rounded-full bg-slate-100 shadow-[0_0_55px_rgba(255,255,255,0.35)] sm:h-16 sm:w-16"></div>
                <div class="absolute left-[15%] top-[18%] h-1.5 w-1.5 rounded-full bg-white"></div>
                <div class="absolute left-[28%] top-[28%] h-1 w-1 rounded-full bg-white"></div>
                <div class="absolute left-[48%] top-[14%] h-1.5 w-1.5 rounded-full bg-white"></div>
                <div class="absolute right-[34%] top-[32%] h-1 w-1 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-0 h-28 w-full bg-slate-800 sm:h-32"
                     style="clip-path: polygon(0 100%, 0 62%, 15% 38%, 29% 68%, 44% 25%, 60% 65%, 76% 38%, 91% 67%, 100% 46%, 100% 100%);"></div>
                <div class="absolute bottom-0 left-0 h-20 w-full bg-slate-950 sm:h-24"
                     style="clip-path: polygon(0 100%, 0 64%, 20% 32%, 36% 70%, 51% 22%, 67% 70%, 82% 42%, 100% 64%, 100% 100%);"></div>
            </div>

            {{-- CONTENT --}}
            <div class="relative flex h-full min-h-[220px] flex-col justify-start p-6 sm:min-h-[240px] sm:p-8">
                <h1
                    class="text-2xl font-bold leading-tight tracking-tight sm:text-3xl"
                    :class="period === 'evening' ? 'text-white' : 'text-slate-900'"
                >
                    <span x-text="greeting"></span>, {{ $displayName }}!
                </h1>

                <p
                    class="mt-2 max-w-md text-sm leading-relaxed sm:text-base"
                    :class="period === 'evening' ? 'text-slate-200' : 'text-slate-600'"
                >
                    Your documents, appointments, and clearance in one place.
                </p>
            </div>
        </div>


        {{-- CLEARANCE STATUS (the one thing students check most) --}}
        <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900">Clearance Status</h2>

                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $currentTheme['badge'] }}">
                    {{ $currentClearanceStatus['period_label'] ?? 'Current term' }}
                </span>
            </div>

            <div class="mt-5 flex items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg {{ $currentTheme['iconWrap'] }}">
                    <i class='bx {{ $currentTheme['icon'] }} text-2xl'></i>
                </span>

                <p class="text-3xl font-bold tracking-tight {{ $currentTheme['word'] }}">
                    {{ $currentClearanceStatus['status_word'] ?? 'Unknown' }}
                </p>
            </div>

            @if(!empty($currentClearanceStatus['remarks']))
                <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    <span class="font-semibold text-slate-700">Remarks:</span>
                    {{ $currentClearanceStatus['remarks'] }}
                </p>
            @endif

            <a
                href="{{ route('student.clearance-status') }}"
                class="mt-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
            >
                View details
                <i class='bx bx-right-arrow-alt text-lg'></i>
            </a>

        </div>

    </section>


    {{-- ============================================================
        UPCOMING + DOCUMENTS
    ============================================================= --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- UPCOMING (notices + appointments, merged) --}}
        <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Upcoming</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Appointments and office notices.</p>
                </div>

                <a
                    href="{{ route('student.appointments.new') }}"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    <i class='bx bx-plus text-sm'></i>
                    Book
                </a>
            </div>

            <div class="mt-4 flex-1 space-y-2.5">

                @forelse($upcoming as $item)

                    <a
                        href="{{ $item['url'] }}"
                        wire:navigate
                        wire:key="{{ $item['key'] }}"
                        class="flex items-center gap-3 rounded-lg border p-3 transition-colors {{ $item['rose'] ? 'border-rose-200 bg-rose-50 hover:bg-rose-100/60' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100/70' }}"
                    >
                        <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg text-white {{ $item['rose'] ? 'bg-rose-600' : 'bg-[#2A57B4]' }}">
                            <span class="text-[9px] font-semibold uppercase opacity-80">{{ $item['date']->format('M') }}</span>
                            <span class="text-base font-bold leading-none">{{ $item['date']->format('d') }}</span>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['line'] }}</p>
                        </div>
                    </a>

                @empty

                    <div class="flex min-h-[170px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-5 text-center">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class='bx bx-calendar-x text-xl'></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Nothing coming up</p>
                        <p class="mt-1 text-xs text-slate-500">No appointments or notices from the VPSD office.</p>
                    </div>

                @endforelse

            </div>

            @if(($stats['appointments']['pending'] ?? 0) > 0)
                <p class="mt-4 flex items-center gap-1.5 text-xs font-medium text-amber-700">
                    <i class='bx bx-time-five text-sm'></i>
                    {{ $stats['appointments']['pending'] }} appointment {{ \Illuminate\Support\Str::plural('request', $stats['appointments']['pending']) }} awaiting approval
                </p>
            @endif

            <a
                href="{{ route('student.appointments.index') }}"
                class="mt-4 inline-flex items-center gap-1 self-start text-xs font-semibold text-blue-600 hover:text-blue-700"
            >
                Manage appointments
                <i class='bx bx-chevron-right'></i>
            </a>

        </div>


        {{-- DOCUMENTS --}}
        <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Your Documents</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Progress on your requests.</p>
                </div>

                <a
                    href="{{ route('student.documents.index') }}"
                    class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700"
                >
                    View all
                    <i class='bx bx-chevron-right'></i>
                </a>
            </div>

            {{-- NUMBERS --}}
            <div class="mt-4 grid grid-cols-2 gap-3">

                <div class="rounded-lg bg-emerald-50/70 p-4">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                        <i class='bx bx-check-double text-base'></i> Completed
                    </p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">
                        {{ $stats['transactions']['completed'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-amber-50/70 p-4">
                    <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700">
                        <i class='bx bx-time-five text-base'></i> In progress
                    </p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">
                        {{ $stats['transactions']['pending'] }}
                    </p>
                </div>

            </div>

            {{-- LATEST DOCUMENT --}}
            <div class="mt-4 flex-1">

                @if($latestDocument)

                    <p class="mb-2 text-xs font-medium text-slate-500">Most recent</p>

                    <a
                        href="{{ route('student.transactions.show', $latestDocument->workspace_id) }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 transition-colors hover:bg-slate-100/70"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class='bx bx-file-blank text-xl'></i>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $latestDocumentName }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Updated {{ $latestDocument->updated_at ? \Carbon\Carbon::parse($latestDocument->updated_at)->diffForHumans() : 'recently' }}
                            </p>
                        </div>

                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $latestStatusClass }}">
                            {{ $latestStatusLabel }}
                        </span>
                    </a>

                @else

                    <div class="flex min-h-[90px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-5 text-center">
                        <p class="text-sm font-semibold text-slate-600">No documents yet</p>
                        <p class="mt-1 text-xs text-slate-500">Your latest document will appear here.</p>
                    </div>

                @endif

            </div>

        </div>

    </section>

</div>