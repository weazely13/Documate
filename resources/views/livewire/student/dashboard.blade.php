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

    {{-- Page header with the period picker --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Activity summary</h1>
            <p class="mt-1 text-sm text-gray-500">How your requests have moved in the last {{ $range }} days.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
        <button type="button" wire:click="$toggle('showAiSummary')" role="switch" aria-checked="{{ $showAiSummary ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-3.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 [@media(pointer:coarse)]:min-h-[44px]">
            <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors {{ $showAiSummary ? 'bg-[#2A57B4]' : 'bg-gray-300' }}" aria-hidden="true">
                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $showAiSummary ? 'translate-x-[18px]' : 'translate-x-0.5' }}"></span>
            </span>
            AI summary
        </button>
        <label class="relative">
            <span class="sr-only">Period</span>
            <i class='bx bx-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-gray-500' aria-hidden="true"></i>
            <select wire:model.live="range"
                    class="rounded-lg border-gray-200 bg-white py-2 pl-9 pr-9 text-sm font-medium text-gray-700 shadow-sm focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-[44px]">
                @foreach (\App\Support\DashboardInsights::RANGES as $days)
                    <option value="{{ $days }}" @selected($days == $range)>Last {{ $days }} days</option>
                @endforeach
            </select>
        </label>
        </div>
    </div>

    {{-- AI summary of everything below (loads after the page; not asked for at all while hidden) --}}
    @if ($showAiSummary)
        <livewire:student.activity-ai-summary :facts="$aiFacts" :key="'ai-summary-' . md5(json_encode($aiFacts))" />
    @endif

    {{-- Headline figures --}}
    <section class="grid grid-cols-2 gap-3 cq-sm:gap-4 cq-lg:grid-cols-4" aria-label="At a glance">
        @include('livewire.partials.dash.kpi', [
            'label' => 'In progress', 'icon' => 'bx-loader-circle', 'url' => route('student.documents.index'),
            'value' => $figures['active'], 'muted' => ! $figures['active'],
            'pill' => $urgent ? $urgent . ' urgent' : null, 'tone' => 'red',
            'caption' => 'Documents still moving',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Completed', 'icon' => 'bx-check-circle', 'url' => route('student.documents.index'),
            'value' => $figures['completed'], 'muted' => ! $figures['completed'],
            'caption' => 'In the last ' . $range . ' days',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Average turnaround', 'icon' => 'bx-timer', 'url' => route('student.documents.index'),
            'value' => $figures['turnaround'] !== null ? $figures['turnaround'] . ' ' . \Illuminate\Support\Str::plural('day', (int) ceil($figures['turnaround'])) : '—',
            'muted' => $figures['turnaround'] === null,
            'caption' => $figures['turnaround'] !== null ? 'From submitting to completed' : 'Nothing completed in this period',
        ])
        @include('livewire.partials.dash.kpi', [
            'label' => 'Approval rate', 'icon' => 'bx-badge-check', 'url' => route('student.documents.index'),
            'value' => $figures['approval'] !== null ? $figures['approval'] . '%' : '—',
            'muted' => $figures['approval'] === null,
            'caption' => $figures['decided'] ? 'Of ' . $figures['decided'] . ' decided, all time' : 'No decisions yet',
        ])
    </section>

    {{-- Trend, with what needs the student beside it --}}
    <div class="grid items-start gap-6 cq-lg:grid-cols-3">
        <div class="min-w-0 cq-lg:col-span-2">
            @include('livewire.partials.insights.trend-hero', ['title' => 'Your activity', 'metrics' => $insights['trend'], 'range' => $range])
        </div>
        <div class="min-w-0">
            <section class="min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="attention-title">
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

        </div>
    </div>

    {{-- Where documents stand, and the latest steps --}}
    <div class="grid items-start gap-6 cq-lg:grid-cols-2">
        <div class="min-w-0">
            @include('livewire.partials.insights.breakdown', [
                'title' => 'Where your documents stand',
                'note' => 'Every document you have started, by its current status.',
                'series' => [['key' => 'n', 'label' => 'Documents', 'color' => '#2A57B4']],
                'groups' => ['All' => $statusRows],
                'empty' => 'You have not started any documents yet.',
            ])
        </div>

        <section class="min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="recent-title">
            <h2 id="recent-title" class="text-lg font-bold tracking-tight text-gray-900">Recent activity</h2>
            <p class="mt-0.5 text-sm text-gray-500">The latest steps across your documents.</p>
            @if ($recent)
                <ol class="mt-4 space-y-0.5">
                    @foreach ($recent as $i => $item)
                        <li wire:key="recent-{{ $i }}" class="relative pl-6">
                            @unless ($loop->last)
                                <span class="absolute left-[5px] top-4 h-full w-px bg-gray-200" aria-hidden="true"></span>
                            @endunless
                            <span class="absolute left-0 top-[7px] h-[11px] w-[11px] rounded-full ring-2 ring-white {{ ['rose' => 'bg-rose-500', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-500', 'slate' => 'bg-gray-300'][$item['tone']] }}" aria-hidden="true"></span>
                            <a href="{{ $item['url'] }}" wire:navigate class="group -mx-2 block rounded-lg px-2 py-1.5 hover:bg-gray-50">
                                <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <span class="text-sm font-medium text-gray-900">{{ $item['text'] }}</span>
                                    <time class="shrink-0 text-xs tabular-nums text-gray-500" datetime="{{ $item['at']->toIso8601String() }}" title="{{ $item['at']->format('M j, Y g:i A') }}">{{ $item['at']->diffForHumans() }}</time>
                                </span>
                                <span class="block truncate text-xs text-gray-500 group-hover:text-[#2A57B4]">{{ $item['document'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">No activity yet.</p>
            @endif
        </section>
    </div>

            {{-- Documents in progress --}}
            <section class="min-w-0 rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="progress-title">
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
                                    <th scope="col" class="w-48 px-4 py-2.5 font-medium">Status</th>
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
                                        <td class="px-4 py-3.5 align-top">
                                            <span class="inline-block max-w-full break-words rounded-md px-1.5 py-0.5 text-xs font-semibold leading-snug {{ $tones[$doc['tone']] }}">{{ $doc['label'] }}</span>
                                        </td>
                                        <td class="py-3.5 pl-4 align-top">
                                            <div class="mt-1.5 grid grid-cols-4 gap-1" aria-hidden="true">
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


    {{-- Mix of documents (when the office is busiest now lives on Home) --}}
    @include('livewire.partials.insights.type-table', [
        'title' => 'Your documents by type',
        'note' => 'Everything you have started, rejected and voided ones aside.',
        'rows' => $insights['byType'],
        'empty' => 'You have not started any documents yet.',
    ])
</div>
