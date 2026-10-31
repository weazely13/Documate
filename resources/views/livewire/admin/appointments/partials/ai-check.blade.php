{{-- The AI check on an appointment: verdict, whether the purpose fits the document, and what to look at.
     Advice only. Needs: $appt. Optional: $run (Livewire call that re-runs it). --}}
@php
    $flag = $appt->ai_flag;
    $ai = $appt->ai_findings ?? [];
    $rec = $ai['recommendation'] ?? null;
    $risk = isset($ai['incorrect_probability']) ? (int) $ai['incorrect_probability'] : null;
    $relevance = $ai['relevance'] ?? null;
    $done = in_array($flag, ['clean', 'flagged'], true);

    [$verdict, $pill] = match (true) {
        $flag === 'pending' => ['Checking…', 'bg-sky-50 text-[#1E4090] ring-sky-200'],
        $flag === 'review_failed' => ['Check failed', 'bg-[#fdece3] text-[#b4471a] ring-orange-200'],
        $flag === null => ['Not checked', 'bg-slate-100 text-slate-600 ring-slate-200'],
        $rec === 'reject' => ['Suggests reject', 'bg-rose-50 text-rose-800 ring-rose-200'],
        $flag === 'flagged' => ['Worth a look', 'bg-amber-50 text-amber-800 ring-amber-200'],
        default => ['Looks fine', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    };
    $bar = match (true) {
        $risk === null => 'bg-slate-300',
        $risk >= 70 => 'bg-red-600',
        $risk >= 35 => 'bg-amber-500',
        default => 'bg-emerald-600',
    };
    $sev = fn ($s) => match ($s) {
        'high' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'medium' => 'bg-amber-50 text-amber-800 ring-amber-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<section aria-labelledby="appt-ai-{{ $appt->appointment_id }}">
    <div class="flex items-center justify-between gap-3">
        <h3 id="appt-ai-{{ $appt->appointment_id }}" class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
            <i class="bx bx-bot text-lg text-[#2A57B4]" aria-hidden="true"></i> AI check
        </h3>
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $pill }}" role="status">
            @if ($flag === 'pending') <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif
            {{ $verdict }}
        </span>
    </div>

    @if ($flag === 'pending')
        <p class="mt-2 text-sm text-slate-500">Reading the purpose and the attached document.</p>
    @elseif (! empty($ai['summary']))
        <p class="mt-2 text-sm text-slate-700">{{ $ai['summary'] }}</p>
    @endif

    @if ($done && $relevance)
        <div class="mt-3 flex items-start gap-2 rounded-lg px-3 py-2 text-sm {{ $relevance['relevant'] ? 'bg-emerald-50 text-emerald-900' : 'bg-rose-50 text-rose-900' }}">
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $relevance['relevant'] ? 'bg-emerald-600' : 'bg-red-600' }}" aria-hidden="true"></span>
            <span>
                <span class="font-medium">{{ $relevance['relevant'] ? 'Purpose fits' : 'Purpose does not fit' }}{{ $appt->workspace ? ' the document' : '' }}.</span>
                @if (! empty($relevance['note'])) {{ $relevance['note'] }} @endif
            </span>
        </div>
    @endif

    @if ($done && $risk !== null)
        <div class="mt-3">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Chance something is wrong</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ $risk }}%</span>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $risk }}" aria-label="Chance something is wrong">
                <div class="h-full rounded-full {{ $bar }}" style="width: {{ max(2, $risk) }}%"></div>
            </div>
        </div>
    @endif

    @if ($done && ! empty($ai['issues']))
        <h4 class="mt-3 text-xs font-medium text-slate-500">What to look at</h4>
        <ul class="mt-1.5 space-y-1.5">
            @foreach ($ai['issues'] as $issue)
                <li class="flex items-start gap-2 text-sm">
                    <span class="mt-0.5 shrink-0 rounded-full px-2 py-px text-[11px] font-medium capitalize ring-1 {{ $sev($issue['severity'] ?? 'low') }}">{{ $issue['severity'] ?? 'low' }}</span>
                    <span class="min-w-0 text-slate-700"><span class="font-medium text-slate-900">{{ $issue['field'] ?? 'General' }}:</span> {{ $issue['issue'] ?? '' }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-slate-500">
            @if ($appt->ai_reviewed_at && $flag !== 'pending') Checked {{ $appt->ai_reviewed_at->diffForHumans() }}. @endif
            AI can be wrong. You make the decision.
        </p>
        @if (! empty($run) && $flag !== 'pending')
            <button type="button" wire:click="{{ $run }}" wire:loading.attr="disabled"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 [@media(pointer:coarse)]:min-h-11">
                <i class="bx bx-refresh text-base text-slate-500" aria-hidden="true"></i> {{ $flag === null ? 'Run AI check' : 'Check again' }}
            </button>
        @endif
    </div>
</section>
