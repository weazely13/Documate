<div class="mx-auto max-w-6xl space-y-4 sm:space-y-5">
    {{-- Header --}}
    <div>
        <a href="{{ route('signatory.oversight') }}" wire:navigate
           class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
            <i class='bx bx-chevron-left text-xl'></i> All Documents
        </a>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $document->name() }}</h2>
                <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $ws->transaction_code }} &middot; {{ $ws->field_values['student_name'] ?? '' }}</p>
            </div>
            <div class="shrink-0">@include('livewire.workflow._badge', ['status' => $ws->workflow_status])</div>
        </div>
        <a href="{{ route('signatory.oversight.report', $ws->workspace_id) }}"
           class="mt-3 inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
            <i class='bx bx-download text-lg'></i> Download report
        </a>
    </div>

    @if ($ws->workflow_note && in_array($ws->workflow_status->value, ['rejected', 'action_required'], true))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $ws->workflow_note }}</div>
    @endif
    @if ($ws->workflow_status->value === 'voided')
        <div class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-800">Voided: {{ $ws->void_reason }}</div>
    @endif

    <div class="grid gap-4 sm:gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-4 sm:space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('signatory.oversight.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

            {{-- Moved into the left column: it was a stray third grid child before --}}
            @if (! empty($ws->field_values['item_photo_path']))
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Item photo</h3>
                    <p class="text-xs text-slate-400">For identification only. Not part of the PDF.</p>
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo" loading="lazy"
                         class="mt-3 max-h-72 w-full rounded-xl border border-slate-200 bg-white object-contain p-1">
                </section>
            @endif

            @if ($approvals->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-900">Signatures</h3>
                    <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
                </section>
            @endif

            @if ($seal)
                <section class="rounded-2xl border p-4 shadow-sm sm:p-5 {{ $seal['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                    <h3 class="text-sm font-semibold {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">{{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}</h3>
                    <p class="mt-0.5 break-all text-xs {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}">Fingerprint {{ $seal['fingerprint'] }}</p>
                </section>
            @endif
        </div>

        @include('livewire.signatory._activity', ['audit' => $audit, 'prefix' => 'oa'])
    </div>
</div>