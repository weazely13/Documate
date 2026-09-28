@php
    $tone = match($appointment->ai_flag ?? 'pending') {
        'clean' => ['border-emerald-200/60', 'bg-emerald-50/40', 'text-emerald-600', 'bg-emerald-500', 'bx-check-shield', 'Looks clean'],
        'flagged' => ['border-rose-200/60', 'bg-rose-50/40', 'text-rose-600', 'bg-rose-500', 'bx-error', 'Needs a closer look'],
        'review_failed' => ['border-amber-200/60', 'bg-amber-50/40', 'text-amber-600', 'bg-amber-500', 'bx-error-circle', 'Review failed'],
        default => ['border-slate-200', 'bg-slate-50/40', 'text-slate-500', 'bg-slate-400', 'bx-loader-circle', 'Reviewing…'],
    };
    $probability = $appointment->ai_incorrect_probability;
    $recommendation = $appointment->ai_recommendation;
    $probTone = match(true) {
        $probability === null => 'bg-slate-100 text-slate-400',
        $probability >= 75 => 'bg-rose-500',
        $probability >= 50 => 'bg-amber-500',
        $probability >= 21 => 'bg-amber-300',
        default => 'bg-emerald-500',
    };
@endphp
<div class="rounded-2xl border {{ $tone[0] }} {{ $tone[1] }} p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">AI Document Review</p>
        <button wire:click="rerunAiReview" wire:loading.attr="disabled"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-[#2A57B4] hover:text-[#2A57B4] disabled:opacity-50">
            Re-run
        </button>
    </div>

    <div class="mt-2 flex items-center gap-2">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tone[3] }} text-white">
            <i class='bx {{ $tone[4] }} text-lg'></i>
        </span>
        <div class="min-w-0">
            <p class="text-sm font-bold {{ $tone[2] }}">{{ $tone[5] }}</p>
            @if(!empty($appointment->ai_findings['summary']))
                <p class="text-xs text-slate-500">{{ $appointment->ai_findings['summary'] }}</p>
            @endif
        </div>
    </div>

    {{-- Probability + Recommendation --}}
    @if($probability !== null)
        <div class="mt-3 rounded-xl border border-slate-100 bg-white/70 p-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-500">Likely incorrect</span>
                <span class="font-bold {{ $probability >= 50 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $probability }}%</span>
            </div>
            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full {{ $probTone }}" style="width: {{ $probability }}%"></div>
            </div>
            @if($recommendation)
                <p class="mt-2 text-xs font-semibold {{ $recommendation === 'approve' ? 'text-emerald-600' : 'text-rose-600' }}">
                    AI recommends: {{ ucfirst($recommendation) }}
                </p>
            @endif
        </div>
    @endif

    @if(!empty($appointment->ai_findings['issues']))
        <ul class="mt-3 space-y-1.5">
            @foreach($appointment->ai_findings['issues'] as $issue)
                @php
                    $sevTone = match($issue['severity'] ?? 'low') {
                        'high' => 'bg-rose-50 text-rose-600',
                        'medium' => 'bg-amber-50 text-amber-600',
                        default => 'bg-slate-100 text-slate-500',
                    };
                @endphp
                <li class="flex items-start gap-2 text-xs">
                    <span class="mt-0.5 shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase {{ $sevTone }}">{{ $issue['severity'] ?? 'low' }}</span>
                    <span class="text-slate-600"><span class="font-semibold text-slate-700">{{ $issue['field'] ?? 'General' }}:</span> {{ $issue['issue'] ?? '' }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Quick actions driven by the AI recommendation (pending only) --}}
    @if($appointment->status === 'pending' && $recommendation)
        <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">
            @if($recommendation === 'approve')
                <button wire:click="approve"
                    class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                    Approve (AI-recommended)
                </button>
            @else
                <button @click="$wire.rejectionReason = @js($appointment->ai_findings['summary'] ?? ''); $wire.showReject = true"
                    class="flex-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100">
                    Reject (AI-recommended)
                </button>
            @endif
        </div>
    @endif

    @if($appointment->ai_reviewed_at)
        <p class="mt-3 text-[10px] text-slate-400">Last reviewed {{ $appointment->ai_reviewed_at->format('M j, Y g:i A') }}</p>
    @endif
</div>