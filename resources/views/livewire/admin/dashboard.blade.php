@php
    $bands = [
        'new' => ['Under 1 day', 'bg-slate-300'],
        'due' => ['1–2 days', 'bg-amber-300'],
        'follow_up' => ['3–4 days', 'bg-orange-500'],
        'urgent' => ['5–6 days', 'bg-red-600'],
        'overdue' => ['7+ days', 'bg-red-900'],
    ];
    $openTotal = array_sum($waiting);
    $pct = fn ($n, $total) => $total > 0 ? round($n / $total * 100) : 0;
@endphp

<div class="cq relative z-0 mx-auto max-w-7xl space-y-6 pb-28 md:pb-12" wire:poll.60s>

    @include('livewire.partials.dash.header', [
        'name' => $firstName,
        'facts' => array_filter([$semesterLabel]),
    ])

    {{-- Headline counts: each opens the page where it gets done --}}
    <section class="grid grid-cols-2 gap-3 cq-sm:gap-4 cq-lg:grid-cols-4" aria-label="Needs action">
        @include('livewire.partials.dash.kpi', [
            'label' => 'To review', 'icon' => 'bx-task', 'url' => route('admin.workflow.index'),
            'value' => $work['to_review'], 'muted' => ! $work['to_review'],
            'pill' => $work['overdue'] ? $work['overdue'] . ' overdue' : null, 'tone' => 'red',
            'caption' => 'Waiting on the office',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'In signing', 'icon' => 'bx-pen', 'url' => route('admin.workflow.index', ['tab' => 'signing']),
            'value' => $work['signing'], 'muted' => ! $work['signing'],
            'pill' => $work['signing_late'] ? $work['signing_late'] . ' late' : null, 'tone' => 'red',
            'caption' => 'Waiting on signatories',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Ready to finish', 'icon' => 'bx-check-double', 'url' => route('admin.workflow.index'),
            'value' => $work['ready'], 'muted' => ! $work['ready'],
            'pill' => $work['ready'] ? 'Everyone signed' : null, 'tone' => 'green',
            'caption' => 'Signed and ready to complete',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Appointment requests', 'icon' => 'bx-calendar-exclamation', 'url' => route('admin.appointments.index'),
            'value' => $work['appointment_requests'], 'muted' => ! $work['appointment_requests'],
            'caption' => 'Waiting for approval',
        ])
    </section>

    {{-- Narrow pages: one column ordered by what to act on first (order-*); wide: main + side columns --}}
    <div class="grid items-start gap-6 cq-lg:grid-cols-3">

        {{-- Main column --}}
        <div class="contents cq-lg:block cq-lg:min-w-0 cq-lg:col-span-2 cq-lg:space-y-6">
            <div class="order-3 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.trend-hero', ['title' => 'Office activity', 'metrics' => $insights['trend'], 'range' => $range])
            </div>

            {{-- How long the open queue (review + signing) has waited --}}
            <section class="order-2 min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5 cq-lg:order-none" aria-labelledby="queue-title">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="queue-title" class="text-base font-semibold text-gray-900">How long the queue has waited</h2>
                    <a href="{{ route('admin.workflow.index') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">Workflow review &rarr;</a>
                </div>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-4xl font-bold leading-none tracking-tight tabular-nums {{ $openTotal ? 'text-gray-900' : 'text-gray-300' }}">{{ $openTotal }}</span>
                    <span class="text-sm text-gray-500">open in review and signing</span>
                </p>

                @if ($openTotal)
                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4 rounded-xl border border-gray-200/70 p-4 cq-md:grid-cols-5" role="list">
                        @foreach ($bands as $key => [$label, $color])
                            <div role="listitem" class="min-w-0">
                                <p class="text-lg font-bold tabular-nums {{ $waiting[$key] ? 'text-gray-900' : 'text-gray-300' }}">{{ $waiting[$key] }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $label }}</p>
                                <span class="mt-2 block h-1 rounded-full {{ $waiting[$key] ? $color : 'bg-gray-100' }}" aria-hidden="true"></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Nothing is waiting on the office or signatories.</p>
                @endif

                @if ($work['uploads'] || $unverified)
                    <p class="mt-4 flex flex-wrap gap-x-6 gap-y-1 border-t border-gray-100 pt-3 text-sm text-gray-600">
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

            <div class="order-4 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.type-table', [
                    'title' => 'Documents by type',
                    'note' => 'Submitted in the last ' . $range . ' days.',
                    'rows' => $insights['byType'],
                    'empty' => 'No documents were submitted in this period.',
                ])
            </div>
        </div>

        {{-- Side column --}}
        <div class="contents cq-lg:block cq-lg:min-w-0 cq-lg:space-y-6">
            <a href="{{ route('admin.appointments.index') }}" wire:navigate
               class="order-1 block min-w-0 rounded-2xl bg-gradient-to-br from-[#2A57B4] to-[#1E4090] p-5 cq-lg:order-none text-white shadow-[0_10px_28px_-14px_rgba(42,87,180,0.7)] transition hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Today's appointments</h2>
                    <i class='bx bx-calendar-check text-xl text-blue-200' aria-hidden="true"></i>
                </div>
                <p class="mt-3 text-5xl font-bold leading-none tracking-tight tabular-nums">{{ $today['total'] }}</p>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    @foreach ($today['sessions'] as $session => $s)
                        <div class="rounded-xl bg-white/10 px-3 py-2.5">
                            <p class="text-xs font-medium uppercase tracking-wide text-blue-100">{{ $session }}</p>
                            <p class="mt-0.5 text-sm">Serving <span class="text-lg font-bold tabular-nums">{{ $s['now_serving'] ?? '–' }}</span></p>
                            <p class="text-xs tabular-nums text-blue-100">{{ $s['attended'] }}/{{ $s['total'] }} done</p>
                        </div>
                    @endforeach
                </div>
            </a>

            <div class="order-5 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.weekdays', [
                    'title' => 'Busiest days',
                    'note' => 'When documents reach the office.',
                    'times' => $insights['times'],
                ])
            </div>

            <div class="order-6 min-w-0 cq-lg:order-none">
            @include('livewire.partials.insights.gauge', [
                'title' => 'Clearance this semester',
                'pct' => $pct($clearance['Cleared'], $clearance['total']),
                'caption' => number_format($clearance['Cleared']) . ' of ' . number_format($clearance['total']) . ' students cleared',
                'legend' => [
                    ['Pending', $clearance['Pending'], 'bg-amber-400'],
                    ['Uncleared', $clearance['Uncleared'], 'bg-rose-500'],
                    ['Not tagged', $clearance['not_tagged'], 'bg-gray-300'],
                ],
                'url' => route('admin.clearance-monitoring'),
                'footnote' => $verification ? $verification['verified'] . ' of ' . $verification['total'] . ' students verified for this period' : null,
            ])
            </div>

            <div class="order-7 min-w-0 cq-lg:order-none">
            @include('livewire.partials.insights.breakdown', [
                'title' => 'Where requests come from',
                'note' => 'Share of submissions in the last ' . $range . ' days.',
                'series' => [['key' => 'count', 'label' => 'Submissions', 'color' => '#2A57B4']],
                'groups' => ['Colleges' => $insights['origins']['colleges'], 'Programs' => $insights['origins']['programs']],
                'percent' => true,
                'empty' => 'No documents were submitted in this period.',
            ])
            </div>
        </div>
    </div>
</div>
