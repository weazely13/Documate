@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $card = 'min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm cq-sm:p-5';
    $max = max(1, collect($buckets)->max(fn ($b) => $b['signed'] + $b['rejected']));
    $roleMax = max(1, (int) $byRole->max());
    $docMax = max(1, (int) $byDocument->max());
    $decisions = $figures['signed'] + $figures['rejected'];

    $kpis = [
        ['Waiting for you', $figures['waiting'], 'bx-pen', route('signatory.inbox'),
            $figures['oldest'] ? 'Oldest since ' . $figures['oldest']->diffForHumans() : 'All caught up',
            $figures['waiting'] ? ['Needs you', 'bg-amber-50 text-amber-800 ring-amber-200'] : null],
        ['Signed', $figures['signed'], 'bx-check-double', route('signatory.inbox', ['tab' => 'history', 'result' => 'approved']),
            number_format($figures['allTime']) . ' signed in all', null],
        ['Rejected', $figures['rejected'], 'bx-x-circle', route('signatory.inbox', ['tab' => 'history', 'result' => 'rejected']),
            $decisions ? round($figures['rejected'] / $decisions * 100) . '% of your decisions' : 'No decisions yet', null],
        ['Avg. response', $figures['avg'] ?? '–', 'bx-time-five', route('signatory.inbox', ['tab' => 'history']),
            'From reaching you to your decision', null],
    ];
@endphp

<div class="cq mx-auto max-w-7xl space-y-6 pb-28 md:pb-12" wire:poll.60s>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 cq-sm:text-2xl">Activity summary</h1>
            <p class="mt-0.5 text-sm text-slate-500">What you signed, rejected and issued in the last {{ $days }} days.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="$toggle('showAiSummary')" role="switch" aria-checked="{{ $showAiSummary ? 'true' : 'false' }}"
                class="inline-flex h-9 items-center gap-2.5 rounded-lg border border-slate-300 bg-white pl-3 pr-3.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 {{ $focus }} [@media(pointer:coarse)]:h-11">
            <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors {{ $showAiSummary ? 'bg-[#2A57B4]' : 'bg-slate-300' }}" aria-hidden="true">
                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $showAiSummary ? 'translate-x-[18px]' : 'translate-x-0.5' }}"></span>
            </span>
            AI summary
        </button>
        <label class="relative">
            <span class="sr-only">Period</span>
            <i class='bx bx-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-500' aria-hidden="true"></i>
            <select wire:model.live="range"
                    class="h-9 rounded-lg border-slate-300 bg-white py-0 pl-9 pr-9 text-sm font-medium text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 [@media(pointer:coarse)]:h-11">
                @foreach (\App\Support\DashboardInsights::RANGES as $d)
                    <option value="{{ $d }}" @selected($d == $days)>Last {{ $d }} days</option>
                @endforeach
            </select>
        </label>
        </div>
    </div>

    @if ($showAiSummary)
        <livewire:student.activity-ai-summary :facts="$aiFacts" :key="'sig-ai-summary-' . md5(json_encode($aiFacts))" />
    @endif

    {{-- Headline figures: two across on phones, four from cq-lg --}}
    <div class="grid grid-cols-2 gap-3 cq-lg:grid-cols-4" wire:loading.class="opacity-60" wire:target="range">
        @foreach ($kpis as [$label, $value, $icon, $url, $caption, $pill])
            <a href="{{ $url }}" wire:navigate class="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#2A57B4]/30 hover:shadow-md cq-sm:p-5 {{ $focus }}">
                <span class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm font-medium text-slate-700">{{ $label }}</span>
                    <i class='bx {{ $icon }} shrink-0 text-lg text-[#2A57B4]' aria-hidden="true"></i>
                </span>
                <span class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-[1.75rem] font-semibold leading-none tracking-[-0.02em] tabular-nums text-slate-900 cq-sm:text-3xl">{{ $value }}</span>
                    @if ($pill)<span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $pill[1] }}">{{ $pill[0] }}</span>@endif
                </span>
                <span class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $caption }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 cq-lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        {{-- Decisions over time --}}
        <section class="{{ $card }}" aria-labelledby="trend-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="trend-title" class="text-[15px] font-semibold text-slate-900">Your decisions</h2>
                <p class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#2A57B4]" aria-hidden="true"></span>Signed</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-400" aria-hidden="true"></span>Rejected</span>
                </p>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">{{ count($buckets) > 7 || $days > 7 ? 'Per week' : 'Per day' }} · {{ $decisions }} in all</p>
            @if ($decisions)
                <ol class="mt-4 flex h-44 items-end gap-1.5 border-b border-slate-200 cq-sm:gap-2" aria-label="Decisions {{ $days > 7 ? 'per week' : 'per day' }}">
                    @foreach ($buckets as $b)
                        @php $t = $b['signed'] + $b['rejected']; @endphp
                        <li class="group relative flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $b['name'] }}: {{ $b['signed'] }} signed, {{ $b['rejected'] }} rejected">
                            <span class="sr-only">{{ $b['name'] }}: {{ $b['signed'] }} signed, {{ $b['rejected'] }} rejected</span>
                            @if ($t)
                                <span class="mb-1 hidden text-center text-[10px] font-medium tabular-nums text-slate-500 cq-sm:block" aria-hidden="true">{{ $t }}</span>
                            @endif
                            <span class="flex w-full flex-col overflow-hidden rounded-t-md" style="height: {{ $t / $max * 100 }}%" aria-hidden="true">
                                <span class="block bg-rose-400" style="flex: {{ $b['rejected'] }} 0 0"></span>
                                <span class="block bg-[#2A57B4] group-hover:bg-[#24499A]" style="flex: {{ $b['signed'] }} 0 0"></span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-1.5 flex gap-1.5 cq-sm:gap-2" aria-hidden="true">
                    @foreach ($buckets as $i => $b)
                        <span class="min-w-0 flex-1 truncate text-center text-[10px] text-slate-500 {{ count($buckets) > 7 && $i % 2 ? 'invisible cq-md:visible' : '' }}">{{ $b['label'] }}</span>
                    @endforeach
                </div>
            @else
                <p class="mt-4 rounded-lg border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">No decisions in this period.</p>
            @endif
        </section>

        {{-- Signed by role --}}
        <section class="{{ $card }}" aria-labelledby="role-title">
            <h2 id="role-title" class="text-[15px] font-semibold text-slate-900">Signed by role</h2>
            <p class="mt-0.5 text-xs text-slate-500">The role each signature was given under.</p>
            @if ($byRole->isNotEmpty())
                <ul class="mt-4 space-y-3">
                    @foreach ($byRole as $label => $n)
                        <li>
                            <p class="flex items-baseline justify-between gap-3 text-sm"><span class="truncate text-slate-800">{{ $label }}</span><span class="font-semibold tabular-nums text-slate-900">{{ $n }}</span></p>
                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><span class="block h-full rounded-full bg-[#2A57B4]" style="width: {{ $n / $roleMax * 100 }}%"></span></span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-500">Nothing signed in this period.</p>
            @endif
        </section>
    </div>

    <div class="grid gap-4 cq-lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
        {{-- What the work was --}}
        <section class="{{ $card }}" aria-labelledby="doc-title">
            <h2 id="doc-title" class="text-[15px] font-semibold text-slate-900">By document</h2>
            <p class="mt-0.5 text-xs text-slate-500">Signed and rejected, most common first.</p>
            @if ($byDocument->isNotEmpty())
                <ul class="mt-4 space-y-3">
                    @foreach ($byDocument as $label => $n)
                        <li>
                            <p class="flex items-baseline justify-between gap-3 text-sm"><span class="truncate text-slate-800">{{ $label }}</span><span class="font-semibold tabular-nums text-slate-900">{{ $n }}</span></p>
                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><span class="block h-full rounded-full bg-violet-600" style="width: {{ $n / $docMax * 100 }}%"></span></span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-500">Nothing in this period.</p>
            @endif
            <div class="mt-5 flex items-center justify-between gap-3 rounded-lg bg-[#e6e0f5] px-3.5 py-3 text-sm text-slate-900">
                <span><span class="font-semibold tabular-nums">{{ $figures['issued'] }}</span> {{ str('report')->plural($figures['issued']) }} issued · <span class="tabular-nums">{{ $figures['issuedOpen'] }}</span> still moving</span>
                <a href="{{ route('signatory.issued') }}" wire:navigate class="shrink-0 text-xs font-semibold text-[#1E4090] hover:underline {{ $focus }}">Issued documents</a>
            </div>
        </section>

        {{-- Recent decisions --}}
        <section class="{{ $card }} !p-0" aria-labelledby="recent-title">
            <div class="flex items-baseline justify-between gap-3 px-4 pt-4 cq-sm:px-5 cq-sm:pt-5">
                <h2 id="recent-title" class="text-[15px] font-semibold text-slate-900">Latest decisions</h2>
                <a href="{{ route('signatory.inbox', ['tab' => 'history']) }}" wire:navigate class="text-xs font-semibold text-[#2A57B4] hover:underline cq-sm:text-sm {{ $focus }}">All decisions &rarr;</a>
            </div>
            <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
                @forelse ($recent as $a)
                    @php $ok = $a->status->value === 'approved'; @endphp
                    <li wire:key="rd-{{ $a->id }}">
                        <a href="{{ route('signatory.show', $a->id) }}" wire:navigate class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 cq-sm:px-5 {{ $focus }}">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $ok ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}"><i class="bx {{ $ok ? 'bx-check' : 'bx-x' }} text-lg" aria-hidden="true"></i></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $a->workspace?->field_values['student_name'] ?? 'Student' }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ DocumentRegistry::find($a->workspace?->document_type)?->name() ?? 'Document' }} · as {{ $a->role->label ?? 'signer' }}</span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block text-xs font-medium {{ $ok ? 'text-emerald-700' : 'text-rose-700' }}">{{ $ok ? 'Signed' : 'Rejected' }}</span>
                                <span class="block text-[11px] tabular-nums text-slate-500">{{ $a->acted_at?->format('M j') }}</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">You have not signed or rejected anything yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
