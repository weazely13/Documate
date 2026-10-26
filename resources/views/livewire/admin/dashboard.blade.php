@php
    $tiles = [
        [
            'label' => 'To review', 'count' => $work['to_review'], 'url' => route('admin.workflow.index'),
            'icon' => 'bx-task', 'iconWrap' => 'bg-blue-100 text-[#2A57B4]',
            'badge' => $work['overdue'] ? $work['overdue'] . ' overdue' : null, 'badgeClass' => 'bg-red-100 text-red-800',
        ],
        [
            'label' => 'Ready to finish', 'count' => $work['ready'], 'url' => route('admin.workflow.index'),
            'icon' => 'bx-check-double', 'iconWrap' => 'bg-emerald-100 text-emerald-700',
            'badge' => $work['ready'] ? 'Everyone signed' : null, 'badgeClass' => 'bg-emerald-100 text-emerald-800',
        ],
        [
            'label' => 'In signing', 'count' => $work['signing'], 'url' => route('admin.workflow.index', ['tab' => 'signing']),
            'icon' => 'bx-pen', 'iconWrap' => 'bg-violet-100 text-violet-700',
            'badge' => $work['signing_late'] ? $work['signing_late'] . ' late' : null, 'badgeClass' => 'bg-red-100 text-red-800',
        ],
        [
            'label' => 'Appointment requests', 'count' => $work['appointment_requests'], 'url' => route('admin.appointments.index'),
            'icon' => 'bx-calendar-exclamation', 'iconWrap' => 'bg-amber-100 text-amber-700',
            'badge' => null, 'badgeClass' => '',
        ],
    ];

    $bands = [
        'new' => ['Under 1 day', 'bg-slate-300'],
        'due' => ['1–2 days', 'bg-amber-300'],
        'follow_up' => ['3–4 days', 'bg-orange-500'],
        'urgent' => ['5–6 days', 'bg-red-600'],
        'overdue' => ['7+ days', 'bg-red-900'],
    ];
    $openTotal = array_sum($waiting);

    $clearanceBands = [
        'Cleared' => ['Cleared', 'bg-emerald-500'],
        'Pending' => ['Pending', 'bg-amber-400'],
        'Uncleared' => ['Uncleared', 'bg-rose-500'],
        'not_tagged' => ['Not tagged', 'bg-gray-200'],
    ];
    $pct = fn ($n, $total) => $total > 0 ? round($n / $total * 100) : 0;

    $facts = array_values(array_filter([
        $semesterLabel,
        $work['to_review'] ? $work['to_review'] . ' waiting for review' : 'Nothing waiting for review',
    ]));
@endphp

<div class="relative z-0 mx-auto max-w-7xl space-y-6 pb-12" wire:poll.60s>

    {{-- Greeting + today --}}
    <section class="grid gap-6 lg:grid-cols-3">
        @include('livewire.partials.greeting-card', [
            'name' => $firstName,
            'subtitle' => 'Here is what the VPSD office has on today.',
            'facts' => $facts,
            'class' => 'lg:col-span-2',
        ])

        <a href="{{ route('admin.appointments.index') }}" wire:navigate
           class="flex flex-col rounded-2xl bg-[#2A57B4] p-6 text-white shadow-sm transition hover:brightness-110">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-blue-100">Today's appointments</p>
                <i class='bx bx-calendar-check text-3xl text-blue-200' aria-hidden="true"></i>
            </div>
            <p class="mt-3 text-4xl font-bold tabular-nums">{{ $today['total'] }}</p>
            <div class="mt-auto grid grid-cols-2 gap-3 pt-5">
                @foreach ($today['sessions'] as $session => $s)
                    <div class="rounded-xl bg-white/10 px-3 py-2.5">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-100">{{ $session }}</p>
                        <p class="mt-0.5 text-sm">Serving <span class="text-lg font-bold">{{ $s['now_serving'] ?? '–' }}</span></p>
                        <p class="text-xs text-blue-100">{{ $s['attended'] }}/{{ $s['total'] }} done</p>
                    </div>
                @endforeach
            </div>
        </a>
    </section>

    {{-- What needs action --}}
    <section aria-label="Needs action">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($tiles as $t)
                <a href="{{ $t['url'] }}" wire:navigate
                   class="flex flex-col rounded-2xl border bg-white p-5 transition hover:shadow-sm {{ $t['label'] === 'Ready to finish' && $t['count'] ? 'border-emerald-200' : 'border-gray-200/70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $t['iconWrap'] }}">
                            <i class='bx {{ $t['icon'] }} text-xl' aria-hidden="true"></i>
                        </span>
                        @if ($t['badge'])
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $t['badgeClass'] }}">{{ $t['badge'] }}</span>
                        @endif
                    </div>
                    <p class="mt-4 text-3xl font-bold tabular-nums {{ $t['count'] ? 'text-gray-900' : 'text-gray-300' }}">{{ $t['count'] }}</p>
                    <p class="mt-0.5 text-sm font-medium text-gray-600">{{ $t['label'] }}</p>
                </a>
            @endforeach
        </div>

        @if ($work['uploads'] || $unverified)
            <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                @if ($work['uploads'])
                    <a href="{{ route('admin.document-uploads.index') }}" wire:navigate class="hover:text-[#2A57B4]">
                        <span class="font-semibold text-gray-900">{{ $work['uploads'] }}</span> {{ \Illuminate\Support\Str::plural('upload', $work['uploads']) }} to check &rarr;
                    </a>
                @endif
                @if ($unverified)
                    <a href="{{ route('admin.verification') }}" wire:navigate class="hover:text-[#2A57B4]">
                        <span class="font-semibold text-gray-900">{{ $unverified }}</span> {{ \Illuminate\Support\Str::plural('student', $unverified) }} not verified &rarr;
                    </a>
                @endif
            </p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Open queue by waiting time --}}
        <section class="rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="queue-title">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="queue-title" class="text-base font-semibold text-gray-900">How long the queue has waited</h2>
                <span class="text-sm text-gray-500">{{ $openTotal }} open</span>
            </div>

            @if ($openTotal)
                <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-gray-100" role="img"
                     aria-label="{{ collect($bands)->map(fn ($b, $k) => $waiting[$k] . ' ' . $b[0])->implode(', ') }}">
                    @foreach ($bands as $key => [$label, $color])
                        @if ($waiting[$key])
                            <span class="{{ $color }}" style="width: {{ $pct($waiting[$key], $openTotal) }}%"></span>
                        @endif
                    @endforeach
                </div>
                <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                    @foreach ($bands as $key => [$label, $color])
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $color }}" aria-hidden="true"></span>
                            <dt class="text-gray-600">{{ $label }}</dt>
                            <dd class="font-semibold tabular-nums text-gray-900">{{ $waiting[$key] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Nothing is waiting on the office or signatories.</p>
            @endif
        </section>

        {{-- This semester --}}
        <section class="rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="semester-title">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="semester-title" class="text-base font-semibold text-gray-900">Clearance this semester</h2>
                <span class="text-sm text-gray-500">{{ $pct($clearance['Cleared'], $clearance['total']) }}% cleared</span>
            </div>

            <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-gray-100">
                @foreach ($clearanceBands as $key => [$label, $color])
                    @if ($clearance[$key])
                        <span class="{{ $color }}" style="width: {{ $pct($clearance[$key], $clearance['total']) }}%"></span>
                    @endif
                @endforeach
            </div>
            <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                @foreach ($clearanceBands as $key => [$label, $color])
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $color }}" aria-hidden="true"></span>
                        <dt class="text-gray-600">{{ $label }}</dt>
                        <dd class="font-semibold tabular-nums text-gray-900">{{ $clearance[$key] }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($verification)
                <p class="mt-4 border-t border-gray-100 pt-3 text-sm text-gray-600">
                    <span class="font-semibold text-gray-900">{{ $verification['verified'] }} of {{ $verification['total'] }}</span> students verified for this period.
                </p>
            @endif
        </section>
    </div>
</div>
