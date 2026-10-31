{{-- The AI pre-check on a transaction. Advice only: the office still decides.
     Needs: $ws. Optional: $run (Livewire call that re-runs it, e.g. "runAiCheck(12)"), $canRun (default true). --}}
@php
    use App\Services\Workflow\TransactionReviewAgent;

    $flag = $ws->ai_flag;
    $ai = $ws->ai_findings ?? [];
    $rec = $ai['recommendation'] ?? null;
    $risk = isset($ai['risk']) ? (int) $ai['risk'] : null;
    $stale = in_array($flag, ['clean', 'flagged'], true)
        && ! empty($ai['answers_hash'])
        && $ai['answers_hash'] !== TransactionReviewAgent::answersHash($ws);
    $canRun = ($canRun ?? true) && ! empty($run);

    // [label, pill classes]: pastel tint + deep text of the same hue, per DESIGN.md status pills.
    [$verdict, $pill] = match (true) {
        $flag === 'pending' => ['Checking…', 'bg-sky-50 text-[#1E4090] ring-sky-200'],
        $flag === 'review_failed' => ['Check failed', 'bg-[#fdece3] text-[#b4471a] ring-orange-200'],
        $flag === null => ['Not checked', 'bg-slate-100 text-slate-600 ring-slate-200'],
        $rec === 'reject' => ['Suggests reject', 'bg-rose-50 text-rose-800 ring-rose-200'],
        $rec === 'request_changes' => ['Suggests changes', 'bg-amber-50 text-amber-800 ring-amber-200'],
        $flag === 'flagged' => ['Worth a look', 'bg-amber-50 text-amber-800 ring-amber-200'],
        default => ['Looks fine', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    };

    $riskBar = match (true) {
        $risk === null => 'bg-slate-300',
        $risk >= 70 => 'bg-red-600',
        $risk >= 35 => 'bg-amber-500',
        default => 'bg-emerald-600',
    };

    // Checklist result: [dot, word read out and shown].
    $result = fn ($r) => match ($r) {
        'fail' => ['bg-red-600', 'Problem', 'text-red-700'],
        'warn' => ['bg-amber-500', 'Check', 'text-amber-700'],
        default => ['bg-emerald-600', 'OK', 'text-emerald-700'],
    };
    $sev = fn ($s) => match ($s) {
        'high' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'medium' => 'bg-amber-50 text-amber-800 ring-amber-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<section aria-labelledby="ai-check-{{ $ws->workspace_id }}" @if ($flag === 'pending' && ($poll ?? true)) wire:poll.4s @endif>
    <div class="flex items-center justify-between gap-3">
        <h3 id="ai-check-{{ $ws->workspace_id }}" class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
            <i class="bx bx-bot text-lg text-[#2A57B4]" aria-hidden="true"></i> AI check
        </h3>
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $pill }}" role="status">
            @if ($flag === 'pending') <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif
            {{ $verdict }}
        </span>
    </div>

    @if ($flag === null)
        <p class="mt-2 text-sm text-slate-500">This one was sent before the AI check was turned on.</p>
    @elseif ($flag === 'pending')
        <p class="mt-2 text-sm text-slate-500">Reading the answers. This usually takes a few seconds.</p>
    @elseif (! empty($ai['summary']))
        <p class="mt-2 text-sm text-slate-700">{{ $ai['summary'] }}</p>
    @endif

    @if ($stale)
        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">The answers changed after this check. Run it again for an up-to-date result.</p>
    @endif

    @if ($risk !== null && in_array($flag, ['clean', 'flagged'], true))
        <div class="mt-3">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Chance the office would not accept it</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ $risk }}%</span>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $risk }}" aria-label="Chance the office would not accept it">
                <div class="h-full rounded-full {{ $riskBar }}" style="width: {{ max(2, $risk) }}%"></div>
            </div>
        </div>
    @endif

    @if (! empty($ai['checks']) && in_array($flag, ['clean', 'flagged'], true))
        <ul class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200/80" aria-label="Checklist">
            @foreach ($ai['checks'] as $c)
                @php [$dot, $word, $wordClass] = $result($c['result'] ?? 'pass'); @endphp
                <li class="px-3 py-2">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="flex min-w-0 items-center gap-2 text-slate-700">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                            {{ $c['label'] }}
                        </span>
                        <span class="shrink-0 text-xs font-medium {{ $wordClass }}">{{ $word }}</span>
                    </div>
                    @if (! empty($c['note']) && ($c['result'] ?? 'pass') !== 'pass')
                        <p class="mt-0.5 pl-4 text-xs text-slate-500">{{ $c['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if (! empty($ai['issues']) && in_array($flag, ['clean', 'flagged'], true))
        <h4 class="mt-3 text-xs font-medium text-slate-500">What to look at</h4>
        <ul class="mt-1.5 space-y-1.5">
            @foreach ($ai['issues'] as $issue)
                <li class="flex items-start gap-2 text-sm">
                    <span class="mt-0.5 shrink-0 rounded-full px-2 py-px text-[11px] font-medium capitalize ring-1 {{ $sev($issue['severity'] ?? 'low') }}">{{ $issue['severity'] ?? 'low' }}</span>
                    <span class="min-w-0 text-slate-700"><span class="font-medium text-slate-900">{{ $issue['field'] }}:</span> {{ $issue['issue'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if (! empty($ai['message']) && $flag === 'flagged')
        <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2.5">
            <p class="text-xs font-medium text-slate-500">Suggested note to the student</p>
            <p class="mt-0.5 text-sm text-slate-700">{{ $ai['message'] }}</p>
            <p class="mt-1 text-xs text-slate-500">It is filled in for you when you choose {{ $rec === 'reject' ? 'Reject' : 'Request changes' }}. You can edit it first.</p>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-slate-500">
            @if ($ws->ai_reviewed_at && $flag !== 'pending')
                Checked {{ $ws->ai_reviewed_at->diffForHumans() }}.
            @endif
            AI can be wrong. You make the decision.
        </p>
        @if ($canRun && $flag !== 'pending')
            <button type="button" wire:click="{{ $run }}" wire:loading.attr="disabled" wire:target="{{ Str::before($run, '(') }}"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 [@media(pointer:coarse)]:min-h-11">
                <i class="bx bx-refresh text-base text-slate-500" aria-hidden="true"></i>
                {{ $flag === null ? 'Run AI check' : 'Check again' }}
            </button>
        @endif
    </div>
</section>
