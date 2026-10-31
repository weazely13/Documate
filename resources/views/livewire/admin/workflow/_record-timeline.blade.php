{{-- Signatures and activity as checkpoints (Workflow Review panel and the full record).
     Needs: $approvals (with role, actor; may be empty), $audit (AuditLabels::history, newest first).
     Optional: $idPrefix, $reportUrl (audit trail PDF download) and $reportEvent (window event that opens its preview). --}}
@php
    $idPrefix ??= 'rec';
    $signed = $approvals->filter(fn ($a) => $a->status->value === 'approved')->count();
@endphp

@if ($approvals->isNotEmpty())
    <section aria-labelledby="{{ $idPrefix }}-signatures">
        <h3 id="{{ $idPrefix }}-signatures" class="text-sm font-semibold text-slate-900">
            Signatures <span class="font-normal tabular-nums text-slate-500">· {{ $signed }} of {{ $approvals->count() }}</span>
        </h3>
        <ol class="mt-3">
            @foreach ($approvals as $a)
                @php
                    $v = $a->status->value;
                    $external = ($a->signer_type ?? 'internal') === 'external';
                    $who = $a->actor?->full_name ?: ($external ? ($a->external_name ?: $a->external_email) : ($a->role->label ?? 'Signatory'));
                    $ring = match ($v) {
                        'approved' => 'bg-emerald-600 ring-emerald-600',
                        'pending' => 'bg-white ring-[#2A57B4]',
                        'rejected' => 'bg-red-600 ring-red-600',
                        default => 'bg-white ring-slate-300',
                    };
                    $state = ['approved' => 'Signed', 'pending' => 'Waiting', 'rejected' => 'Declined', 'skipped' => 'Cancelled'][$v] ?? 'Not started';
                @endphp
                <li class="relative flex gap-3 pb-4 last:pb-0" wire:key="{{ $idPrefix }}-sig-{{ $a->id }}">
                    @unless ($loop->last)
                        <span class="absolute left-[5px] top-4 h-[calc(100%-0.75rem)] w-px bg-slate-200" aria-hidden="true"></span>
                    @endunless
                    <span class="relative mt-1 h-[11px] w-[11px] shrink-0 rounded-full ring-2 {{ $ring }}" aria-hidden="true"></span>
                    <div class="flex min-w-0 flex-1 flex-wrap items-start justify-between gap-x-3 gap-y-0.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $a->label }}</p>
                            <p class="break-words text-xs text-slate-500">{{ $state }} · {{ $who }}</p>
                            @if ($external && $a->external_email && $who !== $a->external_email)
                                <p class="break-all text-xs text-slate-500">{{ $a->external_email }}</p>
                            @endif
                            @if ($v === 'rejected' && $a->reason)
                                <p class="mt-0.5 break-words text-xs text-rose-700">{{ $a->reason }}</p>
                            @endif
                        </div>
                        @if ($a->acted_at)
                            <p class="shrink-0 text-xs tabular-nums text-slate-500">{{ $a->acted_at->format('M j, g:i A') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif

@if ($audit->isNotEmpty())
    <section aria-labelledby="{{ $idPrefix }}-activity">
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
            <h3 id="{{ $idPrefix }}-activity" class="text-sm font-semibold text-slate-900">Checkpoints</h3>
            @if (! empty($reportUrl))
                <div class="-mr-1 flex items-center gap-0.5">
                    @if (! empty($reportEvent))
                        <button type="button" @click="$dispatch('{{ $reportEvent }}')"
                                class="inline-flex min-h-8 items-center gap-1 rounded-lg px-2 text-sm font-medium text-[#2A57B4] hover:bg-[#f0f4fb] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-11">
                            <i class="bx bx-show text-base" aria-hidden="true"></i> Preview<span class="sr-only"> the audit trail</span>
                        </button>
                    @endif
                    <a href="{{ $reportUrl }}"
                       class="inline-flex min-h-8 items-center gap-1 rounded-lg px-2 text-sm font-medium text-[#2A57B4] hover:bg-[#f0f4fb] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-11">
                        <i class="bx bx-download text-base" aria-hidden="true"></i> Download<span class="sr-only"> the audit trail as PDF</span>
                    </a>
                </div>
            @endif
        </div>
        @if (! empty($reportUrl))
            <p class="mt-0.5 text-xs text-slate-500">The audit trail: answers, signatures, seal and every step, as one report.</p>
        @endif
        <ol class="mt-3">
            @foreach ($audit as $i => $item)
                <li class="relative flex gap-3 pb-4 last:pb-0" wire:key="{{ $idPrefix }}-cp-{{ $i }}">
                    @unless ($loop->last)
                        <span class="absolute left-[5px] top-4 h-[calc(100%-0.75rem)] w-px bg-slate-200" aria-hidden="true"></span>
                    @endunless
                    <span class="relative mt-1 h-[11px] w-[11px] shrink-0 rounded-full bg-white ring-2 {{ $loop->first ? 'ring-[#c9531f]' : 'ring-slate-300' }}" aria-hidden="true"></span>
                    <div class="flex min-w-0 flex-1 flex-wrap items-start justify-between gap-x-3 gap-y-0.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">
                                @if ($loop->first)<span class="sr-only">Latest: </span>@endif{{ $item['text'] }}
                            </p>
                            <p class="break-words text-xs text-slate-500">{{ $item['who'] }}{{ $item['role'] ? ' (' . $item['role'] . ')' : '' }}</p>
                            @if ($item['note'])
                                <p class="mt-0.5 break-words text-xs text-slate-600">{{ $item['note'] }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 text-xs tabular-nums text-slate-500">{{ $item['at']->format('M j, g:i A') }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif
