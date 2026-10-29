@use('Illuminate\Support\Str')
@php
    /*
     * Layout and presentation follow the reference dashboard (headline cards with icon, pill, bar and
     * explanation; section titles above white cards; numbered tiles; a queue of mini cards; ranked
     * leaderboards). Colours and details follow the Audit Trail, the admin house style: its tile
     * gradients and faint corner icons, slate borders, ring badges, role-gradient avatars and brand blue.
     */
    $pct = fn ($n, $total) => $total > 0 ? (int) round($n / $total * 100) : 0;
    // Hours as words a person would say: "40 min", "5.5 hrs", "3 days".
    $duration = fn (?float $h) => match (true) {
        $h === null => '–',
        $h < 1 => 'under 1 hr',
        $h < 48 => round($h, 1) . ' hrs',
        default => round($h / 24, 1) . ' days',
    };
    $sig = $signatories;
    $cal = $calendar;
    $tx = $transactions;

    $clearedPct = $pct($clearance['Cleared'], $clearance['total']);
    $signedTotal = $sig['signed']['total'];
    $todayTotal = collect($cal['sessions'])->sum('total');
    $todaySeen = collect($cal['sessions'])->sum('attended');

    // Headline cards: [label, number, pill, icon, Audit Trail gradient, bar %, bar fill, explanation, link label, url]
    $heroes = [
        ['Requests to review', number_format($work['to_review']),
            $work['overdue'] ? $work['overdue'] . ' overdue' : 'none overdue', 'bx-file-find',
            'blue', $pct($work['overdue'], $work['to_review']),
            $work['overdue'] . ' of ' . $work['to_review'] . ' have waited 7+ school days · ' . $work['ready'] . ' more are fully signed and ready to finish',
            'Open Workflow Review', route('admin.workflow.index')],
        ['Waiting for signatures', number_format($work['signing']),
            $work['signing_late'] ? $work['signing_late'] . ' overdue' : 'on time', 'bx-edit',
            'indigo', $pct($signedTotal, $signedTotal + $sig['pending']),
            $signedTotal . ' signatures given in ' . $range . ' days · ' . $sig['pending'] . ' still to be given',
            'Requests in signing', route('admin.workflow.index', ['tab' => 'signing'])],
        ['Students cleared', $clearedPct . '%',
            $clearance['not_tagged'] . ' not tagged', 'bx-check-shield',
            'green', $clearedPct,
            number_format($clearance['Cleared']) . ' of ' . number_format($clearance['total']) . ' active students · ' . $clearance['Pending'] . ' pending, ' . $clearance['Uncleared'] . ' uncleared',
            'Clearance monitoring', route('admin.clearance-monitoring')],
        ['Appointment requests', number_format($work['appointment_requests']),
            $work['appointment_requests'] ? 'action needed' : 'all reviewed', 'bx-calendar-event',
            'manila', $pct($todaySeen, $todayTotal),
            'Visits waiting for approval · ' . $todaySeen . ' of ' . $todayTotal . ' seen today',
            'Appointments board', route('admin.appointments.index')],
    ];

    // Folder colours: back panel and tab, front panel, text on the front, its secondary text,
    // icon box, pill, progress track and fill. Manila (amber) takes dark text so it stays readable.
    $folders = [
        'blue' => ['back' => 'bg-[#1E4090]', 'front' => 'from-[#3b6fd8] to-[#2A57B4]', 'text' => 'text-white', 'soft' => 'text-white/90',
            'icon' => 'bg-white/20 ring-white/30', 'pill' => 'bg-black/15', 'dot' => 'bg-white', 'track' => 'bg-black/15', 'fill' => 'bg-amber-300', 'line' => 'decoration-white/50'],
        'indigo' => ['back' => 'bg-indigo-800', 'front' => 'from-indigo-500 to-indigo-600', 'text' => 'text-white', 'soft' => 'text-white/90',
            'icon' => 'bg-white/20 ring-white/30', 'pill' => 'bg-black/15', 'dot' => 'bg-white', 'track' => 'bg-black/15', 'fill' => 'bg-amber-300', 'line' => 'decoration-white/50'],
        'green' => ['back' => 'bg-emerald-800', 'front' => 'from-emerald-500 to-emerald-600', 'text' => 'text-white', 'soft' => 'text-white/90',
            'icon' => 'bg-white/20 ring-white/30', 'pill' => 'bg-black/15', 'dot' => 'bg-white', 'track' => 'bg-black/15', 'fill' => 'bg-white', 'line' => 'decoration-white/50'],
        'manila' => ['back' => 'bg-amber-500', 'front' => 'from-amber-200 to-amber-300', 'text' => 'text-amber-950', 'soft' => 'text-amber-900',
            'icon' => 'bg-white/50 ring-amber-500/30', 'pill' => 'bg-amber-950/10', 'dot' => 'bg-amber-700', 'track' => 'bg-amber-950/10', 'fill' => 'bg-[#2A57B4]', 'line' => 'decoration-amber-900/40'],
    ];

    $docs = $insights['byType'];
    $docPeak = max(1, ...array_map(fn ($r) => $r['total'], $docs ?: [['total' => 0]]));
    $docStep = $docPeak <= 4 ? 1 : (int) ceil($docPeak / 4);
    $docTop = $docStep * (int) ceil($docPeak / $docStep);

    $roles = collect($sig['byRole'])->sortByDesc('waiting')->values()->all();
    $orgs = $clearance['organizations'];
    $initials = fn (string $name) => Str::upper(collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('')) ?: '?';

    // Audit Trail ring badges.
    $badge = 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1';
    $tone = [
        'blue' => 'bg-blue-50 text-[#2A57B4] ring-blue-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'gray' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $apptTone = ['pending' => 'amber', 'approved' => 'blue', 'attended' => 'green', 'missed' => 'red'];
    $clearTone = ['Cleared' => 'green', 'Pending' => 'amber', 'Uncleared' => 'red'];

    // Cards come in pairs (1 × 2) at every width; on phones each half is "pocket" sized:
    // tighter padding and type, with secondary detail lines hidden below cq-sm.
    $sectionTitle = 'text-base font-bold tracking-tight text-[#1E4090] cq-sm:text-lg';
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm cq-sm:p-5';
    $cardTitle = 'flex min-w-0 items-center gap-1.5 text-[13px] font-semibold leading-tight text-[#2A57B4] cq-sm:gap-2 cq-sm:text-[15px]';
    $cardHead = 'flex flex-wrap items-center justify-between gap-x-2 gap-y-1';
    $link = 'inline-flex items-center gap-0.5 rounded text-xs font-semibold text-[#2A57B4] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] cq-sm:text-sm';
    $solidBtn = 'inline-flex items-center justify-center rounded-full bg-[#2A57B4] px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1E4090] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2';
    $adminAvatar = 'bg-gradient-to-br from-[#2A57B4] to-indigo-500';
    $pair = 'grid grid-cols-2 items-stretch gap-2.5 cq-sm:gap-4';
@endphp

<div class="cq relative z-0 mx-auto max-w-7xl space-y-9 pb-28 md:pb-12" wire:poll.60s>

    <div class="space-y-6">
        @include('livewire.partials.dash.header', [
            'name' => $firstName,
            'facts' => array_filter([$semesterLabel]),
            'picker' => false,
        ])

        {{-- ===================== HEADLINE CARDS: folders with documents inside ===================== --}}
        {{-- Phones: four small folders in a 2 × 2 grid (figures only). Wider: the full folders, 2 then 4 across. --}}
        <section aria-label="At a glance" class="grid grid-cols-2 gap-x-3 gap-y-4 cq-sm:gap-x-4 cq-sm:gap-y-5 cq-lg:grid-cols-4">
            @foreach ($heroes as [$label, $number, $pill, $icon, $theme, $bar, $explain, $linkLabel, $url])
                @php $c = $folders[$theme]; @endphp
                <a href="{{ $url }}" wire:navigate
                   class="group relative isolate flex min-w-0 flex-col pt-2 transition hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-4 rounded-xl cq-sm:pt-3">
                    {{-- Folder tab and back panel --}}
                    <span class="absolute left-0 top-0 h-5 w-2/5 rounded-t-lg {{ $c['back'] }} cq-sm:h-7 cq-sm:rounded-t-xl" aria-hidden="true"></span>
                    <span class="absolute inset-x-0 bottom-0 top-2 rounded-xl rounded-tl-none {{ $c['back'] }} shadow-md cq-sm:top-3" aria-hidden="true"></span>
                    {{-- Documents peeking out of the folder; they lift on hover --}}
                    <span class="absolute left-3 right-6 top-4 h-16 -rotate-2 rounded-md bg-white shadow-sm transition duration-300 group-hover:-translate-y-2 cq-sm:left-5 cq-sm:right-9 cq-sm:top-6 cq-sm:h-24" aria-hidden="true">
                        <span class="absolute left-2.5 right-6 top-2 h-1 rounded-full bg-slate-200 cq-sm:left-3 cq-sm:right-8 cq-sm:top-2.5"></span>
                        <span class="absolute left-2.5 right-10 top-4 h-1 rounded-full bg-slate-200 cq-sm:left-3 cq-sm:right-14 cq-sm:top-5"></span>
                    </span>
                    <span class="absolute left-6 right-3 top-5 h-16 rotate-2 rounded-md bg-slate-50 shadow-sm ring-1 ring-black/5 transition duration-300 group-hover:-translate-y-1 cq-sm:left-9 cq-sm:right-4 cq-sm:top-8 cq-sm:h-24" aria-hidden="true">
                        <span class="absolute left-2.5 right-5 top-2 h-1 rounded-full bg-slate-200 cq-sm:left-3 cq-sm:right-6 cq-sm:top-2.5"></span>
                        <span class="absolute left-2.5 right-9 top-4 h-1 rounded-full bg-slate-200 cq-sm:left-3 cq-sm:right-12 cq-sm:top-5"></span>
                    </span>

                    {{-- Front of the folder, holding the figures --}}
                    <span class="relative mt-8 flex flex-1 flex-col rounded-xl bg-gradient-to-b {{ $c['front'] }} p-3 {{ $c['text'] }} shadow-[0_-8px_18px_-12px_rgba(15,23,42,0.55)] ring-1 ring-inset ring-white/25 cq-sm:mt-11 cq-sm:p-5 cq-sm:pt-4">
                        <span class="flex items-start justify-between gap-2 cq-sm:gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $c['icon'] }} cq-sm:h-10 cq-sm:w-10"><i class='bx {{ $icon }} text-lg cq-sm:text-xl' aria-hidden="true"></i></span>
                            <span class="inline-flex min-w-0 items-center gap-1 truncate rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $c['pill'] }} cq-sm:gap-1.5 cq-sm:px-2.5 cq-sm:py-1 cq-sm:text-[11px]">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $c['dot'] }}" aria-hidden="true"></span><span class="truncate">{{ $pill }}</span>
                            </span>
                        </span>
                        <span class="mt-2.5 text-2xl font-semibold leading-none tracking-tight tabular-nums cq-sm:mt-3 cq-sm:text-[2rem]">{{ $number }}</span>
                        <span class="mt-1 text-xs font-semibold leading-snug cq-sm:mt-1.5 cq-sm:text-sm">{{ $label }}</span>
                        <span class="mt-2.5 block h-1.5 overflow-hidden rounded-full {{ $c['track'] }} cq-sm:mt-4 cq-sm:h-2" aria-hidden="true">
                            <span class="block h-full rounded-full {{ $c['fill'] }}" style="width: {{ $bar }}%"></span>
                        </span>
                        {{-- Explanation and link only where there is room; on phones the whole folder is the link --}}
                        <span class="mt-2.5 hidden text-xs leading-relaxed {{ $c['soft'] }} cq-sm:block">{{ $explain }}</span>
                        <span class="mt-auto hidden items-center gap-1 pt-3 text-xs font-semibold underline {{ $c['line'] }} underline-offset-4 cq-sm:inline-flex">
                            {{ $linkLabel }} <i class='bx bx-right-arrow-alt text-sm transition group-hover:translate-x-0.5' aria-hidden="true"></i>
                        </span>
                        <span class="sr-only cq-sm:hidden">{{ $explain }}. {{ $linkLabel }}.</span>
                    </span>
                </a>
            @endforeach
        </section>
    </div>

    {{-- ===================== TRANSACTIONS: documents | open requests ===================== --}}
    <section class="space-y-3 cq-sm:space-y-4" aria-labelledby="tx-title">
        <h2 id="tx-title" class="{{ $sectionTitle }}">Transactions in the last {{ $range }} days</h2>

        <div class="grid grid-cols-1 gap-2.5 cq-sm:grid-cols-2 cq-sm:gap-4">
            {{-- Most requested documents: the row's feature card, in brand navy, with bold columns --}}
            <div class="relative isolate flex min-w-0 flex-col overflow-hidden rounded-xl bg-gradient-to-br from-[#1E4090] via-[#2A57B4] to-[#3b6fd8] p-3 text-white shadow-lg ring-1 ring-white/10 cq-sm:p-5">
                <i class="bx bx-bar-chart-alt-2 pointer-events-none absolute -bottom-6 -right-4 -z-10 text-[7rem] leading-none text-white/10 cq-sm:text-[9rem]" aria-hidden="true"></i>
                <div class="{{ $cardHead }}">
                    <h3 class="flex min-w-0 items-center gap-1.5 text-[13px] font-semibold leading-tight cq-sm:gap-2 cq-sm:text-[15px]">
                        <span class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-inset ring-white/25 cq-sm:flex"><i class="bx bx-file text-lg" aria-hidden="true"></i></span>
                        Most requested documents
                    </h3>
                    <span class="hidden flex-col items-end gap-1 text-[11px] text-white/90 cq-sm:flex">
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-white/30 ring-1 ring-inset ring-white/40" aria-hidden="true"></span>Requested</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-amber-300" aria-hidden="true"></span>Completed</span>
                    </span>
                </div>
                @if ($docs)
                    <p class="mt-2 inline-flex w-fit max-w-full items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[11px] ring-1 ring-inset ring-white/20 cq-sm:gap-1.5 cq-sm:px-3 cq-sm:py-1 cq-sm:text-xs">
                        <i class="bx bxs-star text-amber-300" aria-hidden="true"></i>
                        <span class="truncate"><span class="hidden cq-sm:inline">Top request: </span><span class="font-semibold">{{ $docs[0]['label'] }}</span> · {{ $docs[0]['total'] }}</span>
                    </p>
                    <div class="mt-3 flex-1 rounded-lg bg-white/10 p-2 pl-6 ring-1 ring-inset ring-white/15 cq-sm:mt-4 cq-sm:p-3 cq-sm:pl-10">
                        <div class="relative mt-3 h-32 cq-sm:mt-0 cq-sm:h-52">
                            @foreach ([0, 0.5, 1] as $f)
                                <div class="pointer-events-none absolute inset-x-0 flex items-center" style="bottom: {{ $f * 100 }}%" aria-hidden="true">
                                    <span class="absolute -left-6 w-5 translate-y-1/2 text-right text-[10px] tabular-nums text-white/80 cq-sm:-left-8 cq-sm:w-7 cq-sm:text-[11px]">{{ (int) round($docTop * $f) }}</span>
                                    <span class="h-px w-full translate-y-1/2 {{ $f === 0 ? 'bg-white/40' : 'bg-white/15' }}"></span>
                                </div>
                            @endforeach
                            <div class="absolute inset-0 flex items-end justify-around gap-1 cq-sm:gap-2.5">
                                @foreach ($docs as $i => $doc)
                                    <div class="group relative flex h-full w-full max-w-14 flex-col justify-end" title="{{ $doc['label'] }}: {{ $doc['completed'] }} completed of {{ $doc['total'] }}">
                                        <div class="relative w-full rounded-t-md bg-white/25 ring-1 ring-inset ring-white/30 transition group-hover:bg-white/35 cq-sm:rounded-t-lg" style="height: {{ max(2, $doc['total'] / $docTop * 100) }}%">
                                            <span class="absolute inset-x-0 bottom-full mb-0.5 flex items-center justify-center gap-0.5 text-[10px] font-bold tabular-nums cq-sm:mb-1 cq-sm:text-xs">
                                                @if ($i === 0)<i class="bx bxs-star hidden text-[11px] text-amber-300 cq-sm:inline" aria-hidden="true"></i>@endif{{ $doc['total'] }}
                                            </span>
                                            <span class="absolute inset-x-0.5 bottom-0 rounded-t bg-gradient-to-t from-amber-400 to-amber-200 shadow-[0_0_12px_rgba(252,211,77,0.45)] cq-sm:inset-x-1.5 cq-sm:rounded-t-md" style="height: {{ $doc['total'] ? $doc['completed'] / $doc['total'] * 100 : 0 }}%"></span>
                                        </div>
                                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-6 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-white px-2.5 py-1.5 text-[11px] text-slate-800 shadow-lg group-hover:block">
                                            <p class="font-semibold text-[#1E4090]">{{ $doc['label'] }}</p>
                                            <p>{{ $doc['completed'] }} completed · {{ $doc['open'] }} still open</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-1.5 flex justify-around gap-1 cq-sm:mt-2 cq-sm:gap-2.5">
                            @foreach ($docs as $doc)
                                <span class="line-clamp-2 w-full max-w-16 text-center text-[9px] leading-tight text-white/90 cq-sm:text-[11px]">{{ $doc['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="sr-only"><table>
                        <thead><tr><th scope="col">Document</th><th scope="col">Requested</th><th scope="col">Completed</th></tr></thead>
                        <tbody>@foreach ($docs as $doc)<tr><td>{{ $doc['label'] }}</td><td>{{ $doc['total'] }}</td><td>{{ $doc['completed'] }}</td></tr>@endforeach</tbody>
                    </table></div>
                @else
                    <div class="flex-1 py-8 text-center cq-sm:py-12">
                        <i class="bx bx-file text-3xl text-white/40 cq-sm:text-4xl" aria-hidden="true"></i>
                        <p class="mt-2 text-xs font-medium cq-sm:text-sm">No documents requested yet</p>
                    </div>
                @endif
            </div>

            {{-- Who has the open requests --}}
            <div class="{{ $card }} flex flex-col">
                <div class="{{ $cardHead }}">
                    <h3 class="{{ $cardTitle }}"><i class="bx bx-transfer hidden cq-sm:inline" aria-hidden="true"></i>Who has the open requests</h3>
                    <a href="{{ route('admin.transactions.index') }}" wire:navigate class="{{ $link }}">All records <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>
                <p class="mt-1 text-xs text-slate-600 cq-sm:text-sm"><span class="font-semibold text-slate-900">{{ number_format($tx['open']) }}</span> still open<span class="hidden cq-sm:inline">. This is where each one is waiting.</span></p>
                <ul class="mt-2.5 grid grid-cols-2 gap-1.5 cq-sm:mt-4 cq-sm:grid-cols-1 cq-sm:gap-3 cq-lg:grid-cols-2">
                    @foreach ($tx['stages'] as $i => [$label, $count, $color, $meaning])
                        <li class="rounded-lg border border-slate-200 p-2 cq-sm:p-3.5">
                            <div class="flex items-center gap-1.5 cq-sm:gap-2">
                                <span class="hidden rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-slate-600 cq-sm:inline">{{ sprintf('%02d', $i + 1) }}</span>
                                <span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-900 cq-sm:text-sm">{{ $label }}</span>
                                <span class="text-[11px] font-bold tabular-nums text-slate-700 cq-sm:text-xs">{{ number_format($count) }}</span>
                            </div>
                            <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-slate-100 cq-sm:mt-2.5 cq-sm:h-2" aria-hidden="true">
                                <span class="block h-full rounded-full" style="width: {{ $pct($count, $tx['open']) }}%; background: {{ $color }}"></span>
                            </span>
                        </li>
                    @endforeach
                    <li class="rounded-lg bg-blue-50/60 p-2 ring-1 ring-inset ring-blue-100 cq-sm:p-3.5">
                        <p class="text-[11px] font-medium text-[#2A57B4] cq-sm:text-xs">Completed in {{ $range }} days</p>
                        <p class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900 cq-sm:mt-1 cq-sm:text-2xl">{{ number_format($tx['completed']['total']) }}</p>
                        <p class="mt-0.5 hidden text-xs text-slate-600 cq-sm:block">{{ $tx['rate'] === null ? 'No finished requests yet' : $tx['rate'] . '% of finished requests were completed' }} · typically {{ $duration($tx['turnaround']) }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ===================== CLEARANCE: status | by organization ===================== --}}
    @php
        $ringR = 46;
        $ringC = 2 * M_PI * $ringR;
        // [status, count, meaning, icon, icon colours, bar colour, slice colour]
        $statusRows = [
            ['Cleared', $clearance['Cleared'], 'Met every requirement', 'bx-check', 'bg-emerald-50 text-emerald-600', 'bg-emerald-500', '#10b981'],
            ['Pending', $clearance['Pending'], 'Still being checked', 'bx-time-five', 'bg-amber-50 text-amber-600', 'bg-amber-400', '#fbbf24'],
            ['Uncleared', $clearance['Uncleared'], 'Something left to settle', 'bx-x', 'bg-rose-50 text-rose-600', 'bg-rose-500', '#f43f5e'],
            ['Not tagged', $clearance['not_tagged'], 'Not checked yet', 'bx-minus', 'bg-slate-100 text-slate-500', 'bg-slate-300', '#cbd5e1'],
        ];
        // Donut slices, clockwise from twelve o'clock, with a hairline of white between neighbours.
        $slices = [];
        $live = count(array_filter($statusRows, fn ($s) => $s[1] > 0));
        $gap = $live > 1 ? 1.5 : 0;
        $offset = 0;
        foreach ($statusRows as $s) {
            if (! $s[1] || ! $clearance['total']) {
                continue;
            }
            $len = $s[1] / $clearance['total'] * $ringC;
            $slices[] = ['color' => $s[6], 'dash' => max(0, $len - $gap), 'offset' => -$offset, 'label' => $s[0], 'n' => $s[1]];
            $offset += $len;
        }
    @endphp
    <section class="space-y-3 cq-sm:space-y-4" aria-labelledby="clearance-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="clearance-title" class="{{ $sectionTitle }}">Clearance</h2>
            @if ($semesterLabel)
                <span class="{{ $badge }} !text-[11px] {{ $tone['green'] }}"><i class="bx bx-calendar-check" aria-hidden="true"></i>{{ $semesterLabel }}</span>
            @endif
        </div>

        {{-- Pair 1: clearance status | by organization and latest tags --}}
        <div class="{{ $pair }}">
            <div class="{{ $card }} flex flex-col">
                <div class="{{ $cardHead }}">
                    <h3 class="{{ $cardTitle }}"><i class="bx bx-clipboard hidden cq-sm:inline" aria-hidden="true"></i>Clearance this semester</h3>
                    <a href="{{ route('admin.clearance-monitoring') }}" wire:navigate class="{{ $link }}">Manage <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>

                <div class="mt-3 flex flex-col gap-3 cq-sm:mt-4 cq-sm:gap-4 cq-lg:flex-row cq-lg:items-center">
                    {{-- Pie of all four statuses; cleared share in the middle. Not tagged is never counted as uncleared. --}}
                    <div class="flex flex-col items-center gap-1.5 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 p-2.5 text-center ring-1 ring-inset ring-emerald-100 cq-sm:gap-2 cq-sm:p-4">
                        <div class="relative h-24 w-24 shrink-0 cq-sm:h-32 cq-sm:w-32">
                            <svg viewBox="0 0 108 108" class="h-full w-full -rotate-90" role="img"
                                 aria-label="{{ collect($statusRows)->map(fn ($s) => $s[0] . ' ' . $s[1])->implode(', ') }}">
                                <circle cx="54" cy="54" r="{{ $ringR }}" fill="none" stroke="#e2e8f0" stroke-width="13" />
                                @foreach ($slices as $sl)
                                    <circle cx="54" cy="54" r="{{ $ringR }}" fill="none" stroke="{{ $sl['color'] }}" stroke-width="13"
                                            stroke-dasharray="{{ round($sl['dash'], 2) }} {{ round($ringC, 2) }}" stroke-dashoffset="{{ round($sl['offset'], 2) }}">
                                        <title>{{ $sl['label'] }}: {{ $sl['n'] }}</title>
                                    </circle>
                                @endforeach
                            </svg>
                            <span class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-lg font-semibold leading-none tabular-nums text-slate-900 cq-sm:text-2xl">{{ $clearedPct }}%</span>
                                <span class="mt-0.5 text-[9px] font-semibold uppercase tracking-wide text-emerald-700 cq-sm:text-[10px]">cleared</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-700 cq-sm:text-sm"><span class="font-semibold text-slate-900">{{ number_format($clearance['Cleared']) }}</span> of {{ number_format($clearance['total']) }}<span class="hidden text-xs text-slate-500 cq-sm:block">active students</span></p>
                    </div>

                    <ul class="min-w-0 flex-1 space-y-1.5 cq-sm:space-y-2.5">
                        @foreach ($statusRows as [$label, $n, $meaning, $icon, $iconClass, $bar])
                            <li class="flex items-center gap-2">
                                <span class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $iconClass }} cq-sm:flex"><i class="bx {{ $icon }} text-base" aria-hidden="true"></i></span>
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $bar }} cq-sm:hidden" aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-1.5 text-xs cq-sm:text-sm">
                                        <span class="truncate font-semibold text-slate-900">{{ $label }}</span>
                                        <span class="shrink-0 tabular-nums"><span class="font-semibold text-slate-900">{{ number_format($n) }}</span><span class="hidden text-xs text-slate-500 cq-sm:inline"> · {{ $pct($n, $clearance['total']) }}%</span></span>
                                    </div>
                                    <span class="mt-1 hidden h-1.5 overflow-hidden rounded-full bg-slate-100 cq-sm:block" aria-hidden="true"><span class="block h-full rounded-full {{ $bar }}" style="width: {{ $pct($n, $clearance['total']) }}%"></span></span>
                                    <span class="mt-0.5 hidden truncate text-[11px] text-slate-500 cq-sm:block">{{ $meaning }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="{{ $card }} flex flex-col">
                <div class="{{ $cardHead }}">
                    <h3 class="{{ $cardTitle }}"><i class="bx bx-buildings hidden cq-sm:inline" aria-hidden="true"></i>By organization</h3>
                    <span class="hidden gap-2 text-[10px] text-slate-500 cq-sm:flex">
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Cleared</span>
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-400" aria-hidden="true"></span>Pending</span>
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-rose-500" aria-hidden="true"></span>Uncleared</span>
                    </span>
                </div>
                @if ($orgs)
                    <ul class="mt-2.5 space-y-2 cq-sm:mt-3 cq-sm:space-y-3">
                        @foreach (array_slice($orgs, 0, 4) as $o)
                            <li>
                                <div class="flex items-center justify-between gap-1.5">
                                    <span class="inline-flex min-w-0 items-center gap-1 rounded-md bg-teal-50 px-1.5 py-0.5 text-[11px] font-medium text-teal-700 cq-sm:px-2 cq-sm:text-xs"><i class="bx bx-buildings hidden cq-sm:inline" aria-hidden="true"></i><span class="truncate">{{ $o['label'] }}</span></span>
                                    <span class="shrink-0 text-[11px] tabular-nums text-slate-500 cq-sm:text-xs"><span class="font-semibold text-slate-900">{{ $o['Cleared'] }}</span>/{{ $o['total'] }}<span class="hidden cq-sm:inline"> cleared</span></span>
                                </div>
                                <span class="mt-1 flex h-1.5 gap-px overflow-hidden rounded-full bg-slate-100 cq-sm:mt-1.5 cq-sm:h-2" aria-hidden="true">
                                    @if ($o['Cleared'])<span class="h-full bg-emerald-500" style="width: {{ $pct($o['Cleared'], $o['total']) }}%"></span>@endif
                                    @if ($o['Pending'])<span class="h-full bg-amber-400" style="width: {{ $pct($o['Pending'], $o['total']) }}%"></span>@endif
                                    @if ($o['Uncleared'])<span class="h-full bg-rose-500" style="width: {{ $pct($o['Uncleared'], $o['total']) }}%"></span>@endif
                                </span>
                                <span class="sr-only">{{ $o['Cleared'] }} cleared, {{ $o['Pending'] }} pending, {{ $o['Uncleared'] }} uncleared of {{ $o['total'] }}.</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2.5 text-xs text-slate-500">No students tagged this semester yet.</p>
                @endif

                @if ($clearance['recent'])
                    <h4 class="mt-3 flex items-center gap-1 border-t border-slate-100 pt-3 text-[11px] font-semibold text-slate-500 cq-sm:mt-5 cq-sm:pt-4 cq-sm:text-xs"><i class="bx bx-history" aria-hidden="true"></i>Latest tags</h4>
                    <ul class="mt-1 divide-y divide-slate-100">
                        @foreach (array_slice($clearance['recent'], 0, 3) as $t)
                            <li>
                                <a @if ($t['url']) href="{{ $t['url'] }}" wire:navigate @endif class="-mx-1 flex items-center gap-2 rounded-md px-1 py-1.5 transition hover:bg-slate-50 cq-sm:gap-2.5 cq-sm:py-2">
                                    <x-user-avatar :src="$t['avatar']" :name="$t['name']" class="h-7 w-7 text-[10px] bg-gradient-to-br from-sky-500 to-cyan-400 cq-sm:h-8 cq-sm:w-8 cq-sm:text-[11px]" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-medium text-slate-900 cq-sm:text-sm">{{ $t['name'] }}</span>
                                        <span class="hidden truncate text-xs text-slate-500 cq-sm:block">{{ $t['organization'] ?: 'No organization' }}@if ($t['by']) · by {{ $t['by'] }}@endif · {{ $t['when'] }}</span>
                                        <span class="block truncate text-[10px] font-semibold cq-sm:hidden {{ ['Cleared' => 'text-emerald-700', 'Pending' => 'text-amber-700', 'Uncleared' => 'text-rose-700'][$t['status']] ?? 'text-slate-500' }}">{{ $t['status'] }}</span>
                                    </span>
                                    <span class="hidden {{ $badge }} {{ $tone[$clearTone[$t['status']] ?? 'gray'] }} cq-sm:inline-flex">{{ $t['status'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== SIGNATORIES: roles | waiting the longest ===================== --}}
    <section class="space-y-3 cq-sm:space-y-4" aria-labelledby="sig-title">
        <h2 id="sig-title" class="{{ $sectionTitle }}">Signatories</h2>

        <div class="{{ $pair }}">
            <div class="{{ $card }} flex flex-col">
                <div class="{{ $cardHead }}">
                    <h3 class="{{ $cardTitle }}"><i class="bx bx-pen hidden cq-sm:inline" aria-hidden="true"></i>By role</h3>
                    <a href="{{ route('admin.workflow.index', ['tab' => 'signing']) }}" wire:navigate class="{{ $link }}">In signing <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    <span class="{{ $badge }} !px-2 !text-[10px] cq-sm:!px-2.5 cq-sm:!text-xs {{ $tone['blue'] }}">{{ $sig['pending'] }} waiting</span>
                    @if ($sig['unassigned'])
                        <span class="{{ $badge }} !px-2 !text-[10px] cq-sm:!px-2.5 cq-sm:!text-xs {{ $tone['amber'] }}"><i class="bx bx-user-x" aria-hidden="true"></i>{{ $sig['unassigned'] }} unassigned</span>
                    @endif
                </div>

                @if ($roles)
                    <ul class="mt-2 flex-1 divide-y divide-slate-100 cq-sm:mt-3">
                        @foreach (array_slice($roles, 0, 6) as $r)
                            @php
                                $done = $pct($r['signed'], $r['signed'] + $r['waiting']);
                                $waitTone = $r['waiting'] >= 5 ? 'red' : ($r['waiting'] > 0 ? 'amber' : 'green');
                            @endphp
                            <li class="flex items-center gap-2 py-2 cq-sm:gap-3 cq-sm:py-3">
                                @if ($r['holders'])
                                    <span class="flex shrink-0 -space-x-2.5">
                                        @foreach (array_slice($r['holders'], 0, 2) as $hi => $h)
                                            <x-user-avatar :src="$h['avatar']" :name="$h['name']" class="h-8 w-8 text-[10px] ring-2 ring-white {{ $adminAvatar }} {{ $hi ? 'hidden cq-sm:inline-flex' : '' }} cq-sm:h-10 cq-sm:w-10 cq-sm:text-xs" />
                                        @endforeach
                                    </span>
                                @else
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-200 cq-sm:h-10 cq-sm:w-10"><i class="bx bx-user-x" aria-hidden="true"></i></span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1.5">
                                        <p class="truncate text-xs font-semibold text-slate-900 cq-sm:text-sm">{{ $r['label'] }}</p>
                                        <span class="shrink-0 {{ $badge }} !px-1.5 !text-[10px] cq-sm:!px-2.5 cq-sm:!text-xs {{ $tone[$waitTone] }}">{{ $r['waiting'] }}<span class="hidden cq-sm:inline">&nbsp;waiting</span></span>
                                    </div>
                                    <p class="hidden truncate text-xs cq-sm:block {{ $r['holders'] ? 'text-slate-500' : 'font-medium text-amber-700' }}">
                                        {{ $r['holders'] ? collect($r['holders'])->pluck('name')->take(2)->implode(', ') . (count($r['holders']) > 2 ? ' +' . (count($r['holders']) - 2) : '') : 'Nobody assigned' }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-2 cq-sm:mt-1.5">
                                        <span class="flex h-1.5 flex-1 overflow-hidden rounded-full bg-amber-100" aria-hidden="true"><span class="block h-full rounded-full bg-[#2A57B4]" style="width: {{ $done }}%"></span></span>
                                        <span class="hidden shrink-0 text-[11px] tabular-nums text-slate-500 cq-sm:inline">{{ $r['signed'] }} signed</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 flex-1 text-xs text-slate-500">No signing activity yet.</p>
                @endif
            </div>

            {{-- Signatures waiting the longest: the signer's photo, what they hold up, and how long --}}
            <div class="{{ $card }} flex flex-col">
                <div class="{{ $cardHead }}">
                    <h3 class="{{ $cardTitle }}"><i class="bx bx-hourglass hidden cq-sm:inline" aria-hidden="true"></i>Waiting the longest</h3>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="{{ $badge }} shrink-0 whitespace-nowrap !px-2 !text-[10px] cq-sm:!px-2.5 cq-sm:!text-xs {{ $tone['amber'] }}">{{ $sig['pending'] }} pending</span>
                        <a href="{{ route('admin.workflow.index', ['tab' => 'signing']) }}" wire:navigate class="inline-flex items-center gap-0.5 whitespace-nowrap text-xs font-semibold text-[#2A57B4] hover:underline" title="See every waiting signature in Workflow Review">Workflow Review<i class="bx bx-chevron-right text-base" aria-hidden="true"></i></a>
                    </div>
                </div>
                <p class="mt-1 hidden text-xs text-slate-500 cq-sm:block">Who to remind first, counted in school days.</p>
                @if ($sig['longest'])
                    <ul class="mt-2 space-y-2 cq-sm:mt-3 cq-sm:space-y-2.5">
                        @foreach (array_slice($sig['longest'], 0, 4) as $l)
                            @php
                                $days = $l['days'] ?? 0;
                                $urgency = $days >= 7 ? 'red' : ($days >= 5 ? 'amber' : 'gray');
                                $ring = ['red' => 'ring-rose-400', 'amber' => 'ring-amber-400', 'gray' => 'ring-slate-200'][$urgency];
                                $dayText = ['red' => 'text-rose-600', 'amber' => 'text-amber-600', 'gray' => 'text-slate-700'][$urgency];
                                $people = $l['people'];
                            @endphp
                            <li class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 p-2 transition hover:border-[#2A57B4]/30 hover:bg-slate-50/60 cq-sm:flex-nowrap cq-sm:gap-3 cq-sm:p-3">
                                <span class="relative flex shrink-0">
                                    @if ($people)
                                        <span class="flex -space-x-3">
                                            @foreach ($people as $pi => $p)
                                                <x-user-avatar :src="$p['avatar']" :name="$p['name']"
                                                    class="h-8 w-8 text-[10px] ring-2 ring-offset-1 ring-offset-white {{ $ring }} {{ $adminAvatar }} {{ $pi ? 'hidden cq-sm:inline-flex' : '' }} cq-sm:h-11 cq-sm:w-11 cq-sm:text-sm cq-sm:ring-offset-2" />
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-2 ring-offset-1 ring-offset-white {{ $ring }} cq-sm:h-11 cq-sm:w-11 cq-sm:ring-offset-2">
                                            <i class="bx {{ $l['external'] ? 'bx-envelope' : 'bx-user-x' }} cq-sm:text-lg" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-900 cq-sm:text-sm">{{ $l['signer'] }}</p>
                                    <span class="mt-0.5 hidden max-w-full items-center gap-1 rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-700 cq-sm:inline-flex">
                                        <i class="bx {{ $l['external'] ? 'bx-envelope' : 'bx-pen' }}" aria-hidden="true"></i><span class="truncate">{{ $l['role'] }}@if ($l['external']) · by email @endif</span>
                                    </span>
                                    <p class="truncate text-[10px] text-slate-500 cq-sm:hidden">{{ $l['role'] }}</p>
                                    <p class="mt-1 hidden truncate text-xs text-slate-600 cq-sm:block">{{ $l['document'] }} <span class="text-slate-400">for</span> {{ $l['student'] }}</p>
                                    @if ($l['opened'])<p class="hidden text-[11px] text-slate-500 cq-sm:block">Waiting since {{ $l['opened'] }}</p>@endif
                                </div>

                                {{-- Phones: days and Open on their own line; wider: a column on the right --}}
                                <div class="flex w-full items-center justify-between gap-2 cq-sm:w-auto cq-sm:shrink-0 cq-sm:flex-col cq-sm:items-end cq-sm:gap-1.5">
                                    <p class="leading-none cq-sm:text-right">
                                        <span class="text-base font-bold tabular-nums {{ $dayText }} cq-sm:block cq-sm:text-2xl">{{ $days }}</span>
                                        <span class="text-[10px] font-medium text-slate-500"><span class="cq-sm:hidden">days</span><span class="hidden cq-sm:inline">{{ $days === 1 ? 'school day' : 'school days' }}</span></span>
                                    </p>
                                    <a href="{{ $l['url'] }}" wire:navigate class="{{ $solidBtn }} !px-2.5 !py-0.5 cq-sm:!px-3 cq-sm:!py-1">Open<span class="sr-only"> {{ $l['document'] }} for {{ $l['student'] }}</span></a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 flex-1 text-xs text-emerald-800">No signatures are pending.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== APPOINTMENTS: today at the office ===================== --}}
    <section class="space-y-3 cq-sm:space-y-4" aria-labelledby="today-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="today-title" class="{{ $sectionTitle }}">Today at the office</h2>
            <a href="{{ route('admin.appointments.index') }}" wire:navigate class="{{ $link }}">Appointments board <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
        </div>

        <div class="{{ $card }}">
            {{-- Morning | afternoon --}}
            <div class="grid grid-cols-2 gap-2.5 cq-sm:gap-3">
                @foreach ($cal['sessions'] as $session => $s)
                    @php $started = $s['now_serving'] !== null; @endphp
                    <div class="relative isolate overflow-hidden rounded-xl bg-gradient-to-br {{ $session === 'morning' ? 'from-[#2A57B4] to-[#3b6fd8]' : 'from-[#1E4090] to-indigo-500' }} p-3 text-white shadow-sm cq-sm:p-4">
                        <i class="bx {{ $session === 'morning' ? 'bx-sun' : 'bx-cloud' }} pointer-events-none absolute -bottom-3 -right-2 -z-10 text-5xl text-white/20 cq-sm:text-6xl" aria-hidden="true"></i>
                        <div class="flex flex-wrap items-center justify-between gap-1">
                            <p class="text-xs font-semibold cq-sm:text-sm">{{ ucfirst($session) }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold cq-sm:px-2.5 cq-sm:text-[11px] {{ $started ? 'bg-emerald-100 text-emerald-800' : 'bg-white/20 text-white' }}">{{ $started ? 'Serving ' . $s['now_serving'] : 'Not started' }}</span>
                        </div>
                        <p class="mt-2 text-xl font-semibold tabular-nums cq-sm:mt-3 cq-sm:text-2xl">{{ $s['attended'] }} <span class="text-xs font-medium text-white/90 cq-sm:text-sm">of {{ $s['total'] }} seen</span></p>
                        <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-black/15" aria-hidden="true"><span class="block h-full rounded-full bg-amber-300" style="width: {{ $pct($s['attended'], $s['total']) }}%"></span></span>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <span class="mr-1 text-xs font-semibold text-slate-700">{{ $cal['picked']->isToday() ? 'Today' : $cal['picked']->format('D, M j') }}:</span>
                @forelse ($cal['list'] as $a)
                    <a href="{{ $a['url'] }}" wire:navigate class="{{ $badge }} {{ $tone[$apptTone[$a['status']] ?? 'gray'] }} hover:brightness-95">
                        <span class="tabular-nums">{{ $a['queue'] ?? '–' }}</span> {{ $a['name'] }} · {{ Str::headline($a['status']) }}
                    </a>
                @empty
                    <span class="text-xs text-slate-500">No bookings.</span>
                @endforelse
                @if ($cal['listMore'])<span class="text-xs text-slate-500">+ {{ $cal['listMore'] }} more</span>@endif
            </div>

                {{-- A wall calendar: binder rings, a month header, and a ruled grid of day boxes --}}
                @php [$calMonthName, $calYear] = array_pad(explode(' ', $cal['month'], 2), 2, ''); @endphp
                <div class="relative mt-5 pt-3">
                    {{-- Binder rings --}}
                    @foreach (['left-[22%]', 'right-[22%]'] as $ringPos)
                        <span class="absolute top-0 z-10 h-7 w-3 rounded-full bg-gradient-to-b from-slate-200 via-slate-400 to-slate-500 shadow ring-2 ring-white {{ $ringPos }}" aria-hidden="true"></span>
                    @endforeach

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md">
                        {{-- Month header --}}
                        <div class="flex items-center justify-between gap-3 bg-gradient-to-r from-[#1E4090] to-[#2A57B4] px-4 pb-3 pt-5 text-white">
                            <div class="min-w-0">
                                <p class="text-xl font-bold uppercase leading-none tracking-wide">{{ $calMonthName }}</p>
                                <p class="mt-1 text-xs text-white/85">{{ $calYear }} · {{ $cal['monthTotal'] }} {{ Str::plural('booking', $cal['monthTotal']) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                @unless ($cal['isCurrent'])
                                    <button type="button" wire:click="backToToday" class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Today</button>
                                @endunless
                                <button type="button" wire:click="shiftMonth(-1)" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Previous month"><i class="bx bx-chevron-left text-xl" aria-hidden="true"></i></button>
                                <button type="button" wire:click="shiftMonth(1)" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Next month"><i class="bx bx-chevron-right text-xl" aria-hidden="true"></i></button>
                            </div>
                        </div>

                        {{-- Weekday row --}}
                        <div class="grid grid-cols-7 border-b border-blue-100 bg-blue-50 text-center text-[11px] font-semibold uppercase tracking-wide">
                            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $wd)
                                <span class="py-1.5 {{ $i === 0 ? 'text-rose-600' : 'text-[#2A57B4]' }}">{{ $wd }}</span>
                            @endforeach
                        </div>

                        {{-- Day boxes. No @continue in here: skipping out of an @if leaves Livewire's block markers
                             unbalanced and the next month morphs into blank cells. Each box is keyed by its date. --}}
                        <div class="grid grid-cols-7 [&>*:nth-child(7n)]:border-r-0" wire:loading.class="opacity-60">
                            @foreach ($cal['days'] as $d)
                                @if (! $d['in'])
                                    {{-- Neighbouring month, printed faintly as on a real calendar --}}
                                    <span wire:key="day-{{ $d['date'] }}" class="h-16 border-b border-r border-slate-100 bg-slate-50/70 p-1.5 text-xs text-slate-300" aria-hidden="true">{{ $d['day'] }}</span>
                                @else
                                    @php
                                        $box = match (true) {
                                            $d['picked'] => 'bg-blue-50 ring-2 ring-inset ring-[#2A57B4]',
                                            $d['closed'] => 'bg-rose-50/40',
                                            $d['total'] > 5 => 'bg-blue-100/70 hover:bg-blue-100',
                                            $d['total'] > 2 => 'bg-blue-50 hover:bg-blue-100/60',
                                            default => 'hover:bg-slate-50',
                                        };
                                    @endphp
                                    <button type="button" wire:key="day-{{ $d['date'] }}" wire:click="pickDay('{{ $d['date'] }}')"
                                            class="relative flex h-16 flex-col justify-between border-b border-r border-slate-100 p-1.5 text-left transition focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#2A57B4] {{ $box }}"
                                            aria-label="{{ \Illuminate\Support\Carbon::parse($d['date'])->format('l, F j') }}: {{ $d['total'] }} {{ Str::plural('appointment', $d['total']) }}{{ $d['pending'] ? ', ' . $d['pending'] . ' to approve' : '' }}{{ $d['closed'] ? ', office closed' : '' }}"
                                            @if ($d['picked']) aria-current="date" @endif>
                                        <span class="flex items-start justify-between">
                                            <span @class([
                                                'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                                'bg-[#2A57B4] text-white shadow-sm' => $d['today'],
                                                'text-rose-500' => ! $d['today'] && $d['closed'],
                                                'text-slate-800' => ! $d['today'] && ! $d['closed'],
                                            ])>{{ $d['day'] }}</span>
                                            @if ($d['pending'])
                                                <span class="mt-1 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white" aria-hidden="true"></span>
                                            @endif
                                        </span>
                                        @if ($d['total'])
                                            <span class="truncate rounded bg-[#2A57B4]/10 px-1 text-[10px] font-semibold text-[#1E4090]">{{ $d['total'] }}<span class="hidden cq-sm:inline"> booked</span></span>
                                        @elseif ($d['closed'])
                                            <span class="text-[10px] font-medium text-rose-400">Closed</span>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
        </div>
    </section>
</div>
