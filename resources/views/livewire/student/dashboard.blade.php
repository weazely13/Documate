@php
    $tones = \App\Support\DocumentProgress::TONES;

    $attentionStyle = [
        'amber' => ['icon' => 'bx-edit bg-amber-100 text-amber-700', 'title' => 'text-amber-950'],
        'rose' => ['icon' => 'bx-error-circle bg-rose-100 text-rose-700', 'title' => 'text-rose-950'],
        'blue' => ['icon' => 'bx-bell bg-blue-100 text-[#2A57B4]', 'title' => 'text-gray-900'],
        'slate' => ['icon' => 'bx-user bg-gray-100 text-gray-600', 'title' => 'text-gray-900'],
    ];
    $shown = array_slice($attention, 0, 4);
    $more = count($attention) - count($shown);
    $urgent = collect($attention)->where('tone', 'rose')->count();

    $clearanceKpi = match ($clearance['status'] ?? '') {
        'Cleared' => ['class' => 'text-emerald-600', 'icon' => 'bx-check-shield'],
        'Pending' => ['class' => 'text-amber-600', 'icon' => 'bx-time-five'],
        'Uncleared' => ['class' => 'text-rose-600', 'icon' => 'bx-x-circle'],
        default => ['class' => 'text-gray-900', 'icon' => 'bx-help-circle'],
    };

    // The next appointment as one figure: the queue number once approved, else its date.
    $visit = null;
    if ($appointment) {
        $day = $appointment['is_today'] ? 'Today' : ($appointment['date']->isTomorrow() ? 'Tomorrow' : $appointment['date']->format('D, M j'));
        $visit = [
            'value' => $appointment['status'] === 'approved' && $appointment['queue_number'] ? '#' . $appointment['queue_number'] : $appointment['date']->format('M j'),
            'pill' => $appointment['status'] === 'pending' ? 'Pending' : ($appointment['is_today'] ? 'Today' : null),
            'tone' => $appointment['status'] === 'pending' ? 'amber' : 'blue',
            'caption' => $day . ', ' . strtolower($appointment['session'])
                . ($appointment['now_serving'] ? ' · now serving ' . $appointment['now_serving'] : ''),
        ];
    }
@endphp

<div class="cq relative z-0 mx-auto max-w-7xl space-y-6 pb-28 md:pb-12" wire:poll.60s>

    @include('livewire.partials.verify-account-card')

    @include('livewire.partials.dash.header', [
        'name' => $firstName,
        'facts' => $facts,
        'action' => ['label' => 'New transaction', 'url' => route('student.new-transaction')],
    ])

    {{-- Headline figures --}}
    <section class="grid grid-cols-2 gap-3 cq-sm:gap-4 cq-lg:grid-cols-4" aria-label="At a glance">
        @include('livewire.partials.dash.kpi', [
            'label' => 'Things to do', 'icon' => 'bx-list-check', 'url' => route('student.documents.index'),
            'value' => count($attention), 'muted' => ! $attention,
            'pill' => $urgent ? $urgent . ' urgent' : null, 'tone' => 'red',
            'caption' => $attention ? 'Waiting on you' : 'You are all caught up',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'In progress', 'icon' => 'bx-file', 'url' => route('student.documents.index'),
            'value' => $activeCount, 'muted' => ! $activeCount,
            'caption' => 'Documents still moving',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Clearance', 'icon' => $clearanceKpi['icon'], 'url' => route('student.clearance-status'),
            'value' => $clearance['status_word'] ?? 'Not tagged', 'valueClass' => $clearanceKpi['class'],
            'caption' => $clearance['period_label'] ?? 'This semester',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => $visit && str_starts_with($visit['value'], '#') ? 'Your number' : 'Next appointment',
            'icon' => 'bx-calendar', 'url' => route('student.appointments.index'),
            'value' => $visit['value'] ?? '—', 'muted' => ! $visit,
            'pill' => $visit['pill'] ?? null, 'tone' => $visit['tone'] ?? 'blue',
            'caption' => $visit['caption'] ?? 'No upcoming appointment',
        ])
    </section>

    {{-- Narrow pages: one column ordered by what to act on first (order-*); wide: main + side columns --}}
    <div class="grid items-start gap-6 cq-lg:grid-cols-3">

        {{-- Main column --}}
        <div class="contents cq-lg:block cq-lg:min-w-0 cq-lg:col-span-2 cq-lg:space-y-6">
            <div class="order-3 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.trend-hero', ['title' => 'Your activity', 'metrics' => $insights['trend'], 'range' => $range])
            </div>

            {{-- Documents in progress --}}
            <section class="order-2 min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5 cq-lg:order-none" aria-labelledby="progress-title">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="progress-title" class="text-base font-semibold text-gray-900">In progress</h2>
                    <a href="{{ route('student.documents.index') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">All documents &rarr;</a>
                </div>

                @if ($active->isNotEmpty())
                    <ul class="mt-2 divide-y divide-gray-100 cq-sm:hidden">
                        @foreach ($active as $doc)
                            <li wire:key="active-m-{{ $doc['id'] }}">
                                <a href="{{ $doc['href'] }}" wire:navigate class="-mx-2 block rounded-lg px-2 py-3.5 active:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                                    <span class="block font-medium leading-snug text-gray-900">{{ $doc['title'] }}</span>
                                    @if ($doc['next'])
                                        <span class="mt-0.5 block text-xs {{ $doc['tone'] === 'you' ? 'text-amber-800' : 'text-gray-500' }}">{{ $doc['next'] }}</span>
                                    @endif
                                    <span class="mt-2.5 flex items-center justify-between gap-3">
                                        <span class="min-w-0 truncate rounded-md px-1.5 py-0.5 text-xs font-semibold {{ $tones[$doc['tone']] }}">{{ $doc['label'] }}</span>
                                        <span class="shrink-0 text-xs tabular-nums text-gray-500">Step {{ min(4, $doc['step'] + 1) }} of 4</span>
                                    </span>
                                    <span class="mt-2 grid grid-cols-4 gap-1" aria-hidden="true">
                                        @for ($i = 1; $i <= 4; $i++)
                                            <span class="h-1.5 rounded-full {{ $i <= $doc['step'] ? 'bg-emerald-500' : ($i === $doc['step'] + 1 ? ($doc['tone'] === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-gray-100') }}"></span>
                                        @endfor
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="-mx-5 mt-3 hidden overflow-x-auto px-5 cq-sm:block">
                        <table class="w-full min-w-[32rem] table-fixed text-sm">
                            <thead>
                                <tr class="whitespace-nowrap border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th scope="col" class="py-2.5 pr-4 font-medium">Document</th>
                                    <th scope="col" class="w-44 px-4 py-2.5 font-medium">Status</th>
                                    <th scope="col" class="w-36 py-2.5 pl-4 font-medium">Progress</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($active as $doc)
                                    <tr wire:key="active-{{ $doc['id'] }}" class="group">
                                        <td class="py-3.5 pr-4">
                                            <a href="{{ $doc['href'] }}" wire:navigate class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500"><i class='bx bx-file text-base' aria-hidden="true"></i></span>
                                                <span class="min-w-0">
                                                    <span class="block truncate font-medium text-gray-900 group-hover:text-[#2A57B4]">{{ $doc['title'] }}</span>
                                                    @if ($doc['next'])
                                                        <span class="block truncate text-xs {{ $doc['tone'] === 'you' ? 'text-amber-800' : 'text-gray-500' }}">{{ $doc['next'] }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <span class="rounded-md px-1.5 py-0.5 text-xs font-semibold {{ $tones[$doc['tone']] }}">{{ $doc['label'] }}</span>
                                        </td>
                                        <td class="py-3.5 pl-4">
                                            <div class="grid grid-cols-4 gap-1" aria-hidden="true">
                                                @for ($i = 1; $i <= 4; $i++)
                                                    <span class="h-1.5 rounded-full {{ $i <= $doc['step'] ? 'bg-emerald-500' : ($i === $doc['step'] + 1 ? ($doc['tone'] === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-gray-100') }}"></span>
                                                @endfor
                                            </div>
                                            <p class="mt-1.5 text-xs tabular-nums text-gray-500">Step {{ min(4, $doc['step'] + 1) }} of 4</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($activeCount > $active->count())
                        <a href="{{ route('student.documents.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">
                            {{ $activeCount - $active->count() }} more in progress &rarr;
                        </a>
                    @endif
                @else
                    <a href="{{ route('student.new-transaction') }}" wire:navigate
                       class="mt-4 flex items-center gap-4 rounded-xl border border-dashed border-gray-300 p-4 transition hover:border-[#2A57B4]/40">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#2A57B4]"><i class='bx bx-plus text-xl' aria-hidden="true"></i></span>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Nothing in progress</span>
                            <span class="block text-sm text-gray-500">Start a new transaction when you need a form.</span>
                        </span>
                    </a>
                @endif
            </section>

            <div class="order-4 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.type-table', [
                    'title' => 'Your documents by type',
                    'note' => 'Everything you have started, rejected and voided ones aside.',
                    'rows' => $insights['byType'],
                    'empty' => 'You have not started any documents yet.',
                ])
            </div>
        </div>

        {{-- Side column --}}
        <div class="contents cq-lg:block cq-lg:min-w-0 cq-lg:space-y-6">
            <section class="order-1 min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5 cq-lg:order-none" aria-labelledby="attention-title">
                <h2 id="attention-title" class="text-base font-semibold text-gray-900">Needs your attention</h2>

                @if ($shown)
                    <ul class="mt-3 divide-y divide-gray-100">
                        @foreach ($shown as $i => $item)
                            <li wire:key="attn-{{ $i }}">
                                <a href="{{ $item['url'] }}" wire:navigate
                                   class="group -mx-2 flex items-start gap-3 rounded-lg px-2 py-3 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                                    @php [$glyph, $chip] = explode(' ', $attentionStyle[$item['tone']]['icon'], 2); @endphp
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip }}" aria-hidden="true"><i class='bx {{ $glyph }} text-base'></i></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold {{ $attentionStyle[$item['tone']]['title'] }}">{{ $item['title'] }}</span>
                                        @if ($item['text'])
                                            <span class="mt-0.5 line-clamp-2 text-xs text-gray-500">{{ $item['text'] }}</span>
                                        @endif
                                    </span>
                                    <span class="shrink-0 pt-0.5 text-xs font-semibold text-[#2A57B4] group-hover:text-[#1E4090]">{{ $item['action'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($more > 0)
                        <a href="{{ route('student.documents.index') }}" wire:navigate class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">
                            {{ $more }} more {{ \Illuminate\Support\Str::plural('thing', $more) }} to check &rarr;
                        </a>
                    @endif
                @else
                    <div class="mt-4 flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><i class='bx bx-party text-lg' aria-hidden="true"></i></span>
                        <p class="text-sm text-emerald-900"><span class="font-semibold">You're all caught up.</span> Nothing needs you right now.</p>
                    </div>
                @endif
            </section>

            <div class="order-5 min-w-0 cq-lg:order-none">
                @include('livewire.partials.insights.weekdays', [
                    'title' => 'When the office is busiest',
                    'note' => 'Submissions from all students, so plan around the peak.',
                    'times' => $insights['times'],
                ])
            </div>
        </div>
    </div>
</div>
