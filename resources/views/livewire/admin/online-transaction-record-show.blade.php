{{-- Transaction Records › one online transaction. Read-only, laid out like a document in an archive:
     the details in tabs on the left, the page itself on the right. --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';
    $tag = ['emerald' => 'bg-emerald-600 text-white', 'slate' => 'bg-slate-500 text-white', 'blue' => 'bg-[#2A57B4] text-white'][$tone];
    $btn = 'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 ' . $focus;
    $row = 'grid gap-1 py-3 sm:grid-cols-[11rem_minmax(0,1fr)] sm:gap-4';
    $dt = 'text-sm text-slate-500';
    $dd = 'min-w-0 break-words text-sm text-slate-900';
    $previewSrc = route('admin.workflow.preview', $ws->workspace_id);
    $tabs = ['details' => 'Details', 'answers' => 'Answers'] + ($approvals->isNotEmpty() ? ['signatures' => 'Signatures'] : []) + ['history' => 'History'];
@endphp

<div class="mx-auto max-w-7xl pb-16" x-data="{ tab: 'details' }">
    {{-- Title bar: back, the document and its code, then the actions --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ session('transactions.back', route('admin.transactions.index')) }}" wire:navigate aria-label="Back to transaction records" title="Back to transaction records"
           class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
            <i class="bx bx-arrow-back text-lg" aria-hidden="true"></i>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xl font-semibold tracking-[-0.015em] text-slate-900 sm:text-2xl">
                <span class="min-w-0">{{ $documentName }}</span>
                <span class="rounded-md bg-[#1E4090] px-1.5 py-0.5 font-mono text-xs font-medium text-white">{{ $ws->transaction_code }}</span>
                <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $tag }}">{{ \Illuminate\Support\Str::before($result, ':') }}</span>
            </h1>
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $studentName }}@if ($studentNumber) · {{ $studentNumber }}@endif</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($ws->workflow_status?->value === 'completed' && $ws->verification_token)
                <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener" class="{{ $btn }}">
                    <i class="bx bx-check-shield text-base text-[#2A57B4]" aria-hidden="true"></i> Verification page
                </a>
            @endif
            <a href="{{ $previewSrc }}" target="_blank" rel="noopener" class="{{ $btn }}">
                <i class="bx bx-link-external text-base" aria-hidden="true"></i> Open full size
            </a>
            @if ($ws->workflow_status?->value === 'completed')
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] disabled:opacity-50 [@media(pointer:coarse)]:h-11 {{ $focus }}">
                    <i class="bx bx-download text-base" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                    <span wire:loading wire:target="downloadPdf">Preparing…</span>
                </button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        {{-- Left: the record --}}
        <div class="min-w-0">
            <div class="mb-1 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Record">
                @foreach ($tabs as $key => $label)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                            class="-mb-px shrink-0 border-b-2 px-3 py-2.5 text-sm font-medium transition {{ $focus }}"
                            :class="tab === '{{ $key }}' ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        {{ $label }}
                        @if ($key === 'history')<span class="ml-0.5 text-xs text-slate-400">{{ $audit->count() }}</span>@endif
                    </button>
                @endforeach
            </div>

            {{-- Details --}}
            <div x-show="tab === 'details'" role="tabpanel">
                @if ($codeGone)
                    <p class="mt-3 rounded-lg bg-[#fef3c7] px-4 py-3 text-sm text-amber-900">This form was removed from the system. Its record is shown as it was saved{{ ! empty($ws->snapshot['captured_at']) ? ' on ' . \Carbon\Carbon::parse($ws->snapshot['captured_at'])->format('M j, Y') : '' }}.</p>
                @endif
                @if ($ws->trashed())
                    <p class="mt-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800">The student deleted this transaction from their list. The record is kept for the audit trail.</p>
                @endif
                @if ($ws->workflow_note && in_array($ws->workflow_status?->value, ['rejected', 'action_required'], true))
                    <p class="mt-3 rounded-lg bg-[#fef3c7] px-4 py-3 text-sm text-amber-900"><span class="font-semibold">{{ $ws->workflow_status?->value === 'rejected' ? 'Reason for rejection:' : 'Changes asked:' }}</span> {{ $ws->workflow_note }}</p>
                @elseif ($ws->workflow_status?->value === 'voided')
                    <p class="mt-3 rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-700"><span class="font-semibold">Voided{{ $ws->voided_at ? ' ' . $ws->voided_at->format('M j, Y') : '' }}:</span> {{ $ws->void_reason }}</p>
                @endif

                <dl class="divide-y divide-slate-100">
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Student</dt>
                        <dd class="{{ $dd }} flex items-center gap-2.5">
                            <x-dm.avatar :user="$ws->user" :name="$studentName" class="h-8 w-8 text-[11px]" />
                            <span class="min-w-0"><span class="block truncate font-medium">{{ $studentName }}</span><span class="block truncate text-xs text-slate-500">{{ $studentNumber ?: '—' }}@if ($program) · {{ $program }}@endif</span></span>
                        </dd>
                    </div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Document type</dt><dd class="{{ $dd }}">{{ $documentName }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Transaction code</dt><dd class="{{ $dd }} font-mono">{{ $ws->transaction_code }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Result</dt>
                        <dd class="{{ $dd }}"><span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $tag }}">{{ \Illuminate\Support\Str::before($result, ':') }}</span>
                            @if (str_contains($result, ':'))<span class="ml-1.5 text-slate-500">{{ trim(\Illuminate\Support\Str::after($result, ':')) }}</span>@endif</dd>
                    </div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Mode</dt><dd class="{{ $dd }}">Online</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Submitted</dt><dd class="{{ $dd }} tabular-nums">{{ $ws->submitted_at?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">{{ $ws->completed_at ? 'Completed' : 'Last update' }}</dt><dd class="{{ $dd }} tabular-nums">{{ ($ws->completed_at ?? $ws->updated_at)?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
                    @if ($issuer)
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Issued by</dt><dd class="{{ $dd }}">{{ $issuer['name'] }}@if ($issuer['role'] !== '')<span class="text-slate-500"> · {{ $issuer['role'] }}</span>@endif</dd></div>
                    @endif
                    @if ($seal)
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Seal</dt>
                            <dd class="{{ $dd }}">
                                <span class="inline-flex items-center gap-1 font-medium {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}"><i class="bx {{ $seal['valid'] ? 'bx-check-shield' : 'bx-error' }} text-base" aria-hidden="true"></i>{{ $seal['valid'] ? 'Intact' : 'Check failed' }}</span>
                                <span class="mt-0.5 block break-all font-mono text-[11px] text-slate-500">{{ $seal['fingerprint'] }}</span>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Answers --}}
            <div x-show="tab === 'answers'" x-cloak role="tabpanel">
                @if ($answers->isEmpty())
                    <p class="py-6 text-sm text-slate-500">No answers recorded.</p>
                @else
                    <dl class="divide-y divide-slate-100">
                        @foreach ($answers as $a)
                            <div class="{{ $row }}"><dt class="{{ $dt }}">{{ $a['label'] }}@if ($a['retired'])<span class="mt-0.5 block text-[11px] text-amber-700">No longer on the form</span>@endif</dt><dd class="{{ $dd }} whitespace-pre-line">{{ $a['value'] }}</dd></div>
                        @endforeach
                    </dl>
                @endif
                @if (! empty($ws->field_values['item_photo_path']))
                    <div class="{{ $row }} border-t border-slate-100"><dt class="{{ $dt }}">Item photo</dt>
                        <dd><img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo" class="max-h-72 max-w-full rounded-lg border border-slate-200 bg-white object-contain p-1"></dd>
                    </div>
                @endif
            </div>

            {{-- Signatures --}}
            @if ($approvals->isNotEmpty())
                <div x-show="tab === 'signatures'" x-cloak role="tabpanel" class="pt-4">
                    @include('livewire.workflow._chain', ['approvals' => $approvals])
                </div>
            @endif

            {{-- History --}}
            <div x-show="tab === 'history'" x-cloak role="tabpanel" class="pt-4">
                <ol class="space-y-4">
                    @forelse ($audit as $i => $item)
                        <li class="relative pl-6" wire:key="audit-{{ $i }}">
                            @if (($item['kind'] ?? '') === 'appointment')
                                <span class="absolute -left-[3px] top-0.5 grid h-4 w-4 place-items-center rounded-full bg-[#fdece3] text-[#9a4521]" aria-hidden="true"><i class="bx bx-calendar text-[10px]"></i></span>
                            @else
                                <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full {{ $loop->first ? 'bg-[#2A57B4]' : 'bg-slate-300' }}" aria-hidden="true"></span>
                            @endif
                            @unless ($loop->last)<span class="absolute left-[4px] top-5 h-[calc(100%-4px)] w-px bg-slate-200" aria-hidden="true"></span>@endunless
                            <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['who'] }} · <span class="tabular-nums">{{ $item['at']->format('M j, Y g:i A') }}</span></p>
                            @if ($item['note'])<p class="mt-0.5 break-words text-xs text-slate-500">{{ $item['note'] }}</p>@endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No activity yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Right: the document, on a grey desk --}}
        <div class="min-w-0 lg:sticky lg:top-4 lg:self-start">
            <div class="rounded-xl bg-slate-200/80 p-2 sm:p-3">
                @include('livewire.workflow._preview', [
                    'src' => $previewSrc,
                    'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                ])
            </div>
        </div>
    </div>
</div>
