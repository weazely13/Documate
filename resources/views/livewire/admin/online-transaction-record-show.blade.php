@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $card = 'rounded-2xl border border-gray-200/70 bg-white p-4 sm:p-5';
    $dot = ['emerald' => 'bg-emerald-600', 'slate' => 'bg-slate-400', 'blue' => 'bg-[#2A57B4]'][$tone];
@endphp

<div class="mx-auto max-w-5xl space-y-5">

    <x-admin.page-header
        :title="$studentName"
        :description="$document->name() . ' · ' . $ws->transaction_code"
        :crumbs="['Admin' => $homeUrl, 'Transaction Records' => route('admin.transactions.index'), $ws->transaction_code => null]">
        <x-slot:actions>
            <a href="{{ route('admin.transactions.index') }}" wire:navigate
               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:w-auto sm:py-2">
                <i class='bx bx-arrow-back' aria-hidden="true"></i> Back to transaction records
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Summary --}}
    <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="{{ $card }}">
            <dt class="text-xs text-gray-500">Result</dt>
            <dd class="mt-1.5 flex items-start gap-2 text-sm font-semibold text-gray-900">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>{{ $result }}
            </dd>
        </div>
        <div class="{{ $card }}">
            <dt class="text-xs text-gray-500">Submitted</dt>
            <dd class="mt-1.5 text-sm font-semibold text-gray-900">{{ $ws->submitted_at?->format('M j, Y') ?? '—' }}</dd>
            <dd class="text-xs text-gray-500">{{ $ws->submitted_at?->format('g:i A') }}</dd>
        </div>
        <div class="{{ $card }}">
            <dt class="text-xs text-gray-500">{{ $ws->completed_at ? 'Completed' : 'Last update' }}</dt>
            <dd class="mt-1.5 text-sm font-semibold text-gray-900">{{ ($ws->completed_at ?? $ws->updated_at)?->format('M j, Y') ?? '—' }}</dd>
            <dd class="text-xs text-gray-500">{{ ($ws->completed_at ?? $ws->updated_at)?->format('g:i A') }}</dd>
        </div>
        <div class="{{ $card }}">
            <dt class="text-xs text-gray-500">Student</dt>
            <dd class="mt-1.5 truncate text-sm font-semibold text-gray-900">{{ $studentNumber ?: '—' }}</dd>
            <dd class="truncate text-xs text-gray-500">{{ $program ?: 'Online' }}</dd>
        </div>
    </dl>

    @if ($issuer)
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 rounded-xl bg-gray-50 px-4 py-3 text-sm">
            <span class="text-gray-500">Issued by</span>
            <span class="font-semibold text-gray-900">{{ $issuer['name'] }}</span>
            @if ($issuer['role'] !== '') <span class="text-gray-600">{{ $issuer['role'] }}</span> @endif
        </div>
    @endif

    @if ($ws->trashed())
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">The student deleted this transaction from their list. The record is kept for the audit trail.</p>
    @endif

    {{-- Outcome note and download --}}
    @if ($ws->workflow_note && in_array($ws->workflow_status?->value, ['rejected', 'action_required'], true))
        <div class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">{{ $ws->workflow_status?->value === 'rejected' ? 'Reason for rejection:' : 'Changes asked:' }}</span> {{ $ws->workflow_note }}
        </div>
    @elseif ($ws->workflow_status?->value === 'voided')
        <div class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Voided{{ $ws->voided_at ? ' ' . $ws->voided_at->format('M j, Y') : '' }}:</span> {{ $ws->void_reason }}
        </div>
    @elseif ($ws->workflow_status?->value === 'completed')
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50 sm:w-auto">
                <span wire:loading.remove wire:target="downloadPdf">Download official PDF</span>
                <span wire:loading wire:target="downloadPdf">Preparing…</span>
            </button>
            @if ($ws->verification_token)
                <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener"
                   class="text-center text-sm font-semibold text-[#2A57B4] hover:underline sm:px-3">Public verification page</a>
            @endif
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-5">

            {{-- Answers --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-semibold text-gray-900">Answers</h3>
                @if ($answers->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">No answers recorded.</p>
                @else
                    <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        @foreach ($answers as $a)
                            <div class="min-w-0">
                                <dt class="text-xs text-gray-500">{{ $a['label'] }}</dt>
                                <dd class="whitespace-pre-line break-words text-sm text-gray-900">{{ $a['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            @if (! empty($ws->field_values['item_photo_path']))
                <section class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-gray-900">Item photo</h3>
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo"
                         class="mt-3 max-h-72 max-w-full rounded-lg border border-gray-200 bg-white object-contain p-1">
                </section>
            @endif

            {{-- Document preview --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-semibold text-gray-900">Document</h3>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('admin.workflow.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

            @if ($approvals->isNotEmpty())
                <section class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-gray-900">Signatures</h3>
                    <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
                </section>
            @endif

            @if ($seal)
                <section class="rounded-2xl p-4 sm:p-5 {{ $seal['valid'] ? 'bg-emerald-50' : 'bg-rose-50' }}">
                    <h3 class="text-sm font-semibold {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}
                    </h3>
                    <p class="mt-0.5 break-all text-xs {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}">Fingerprint {{ $seal['fingerprint'] }}</p>
                </section>
            @endif
        </div>

        {{-- Activity: folded on phones, a side column on large screens --}}
        <aside class="{{ $card }} h-fit lg:sticky lg:top-6" aria-label="Activity" x-data="{ open: false }">
            <button type="button" @click="open = !open" :aria-expanded="open"
                    class="flex min-h-[44px] w-full items-center justify-between text-left lg:pointer-events-none lg:min-h-0">
                <span class="text-sm font-semibold text-gray-900">Activity <span class="font-normal text-gray-400">({{ $audit->count() }})</span></span>
                <i class='bx bx-chevron-down text-xl text-gray-400 transition lg:hidden' :class="open && 'rotate-180'" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak class="mt-4 space-y-4 lg:!block">
                @foreach ($audit as $i => $item)
                    <div class="relative pl-5" wire:key="audit-{{ $i }}">
                        <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-gray-300"></span>
                        <p class="text-sm font-medium text-gray-800">{{ $item['text'] }}</p>
                        <p class="text-xs text-gray-500">{{ $item['who'] }} · {{ $item['at']->format('M j, Y g:i A') }}</p>
                        @if ($item['note']) <p class="mt-0.5 break-words text-xs text-gray-500">{{ $item['note'] }}</p> @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>
