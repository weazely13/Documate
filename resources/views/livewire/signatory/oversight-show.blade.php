<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('signatory.oversight') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; All Documents</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $document->name() }}</h2>
            <p class="font-mono text-xs text-slate-400">{{ $ws->transaction_code }} &middot; {{ $ws->field_values['student_name'] ?? '' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('signatory.oversight.report', $ws->workspace_id) }}"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download report</a>
            @include('livewire.workflow._badge', ['status' => $ws->workflow_status])
        </div>
    </div>

    @if ($ws->workflow_note && in_array($ws->workflow_status->value, ['rejected', 'action_required'], true))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $ws->workflow_note }}</div>
    @endif
    @if ($ws->workflow_status->value === 'voided')
        <div class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-800">Voided: {{ $ws->void_reason }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        @if (! empty($ws->field_values['item_photo_path']))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Item photo</h3>
                <p class="text-xs text-slate-400">For identification only. Not part of the PDF.</p>
                <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo"
                    class="mt-3 max-h-72 rounded-xl border border-slate-200 bg-white object-contain p-1">
            </section>
        @endif
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('signatory.oversight.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

            @if ($approvals->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Signatures</h3>
                    <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
                </section>
            @endif

            @if ($seal)
                <section class="rounded-2xl border p-5 shadow-sm {{ $seal['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                    <h3 class="text-sm font-semibold {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">{{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}</h3>
                    <p class="mt-0.5 text-xs {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}">Fingerprint {{ $seal['fingerprint'] }}</p>
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Activity</h3>
            <p class="text-xs text-slate-400">Newest first</p>
            <div class="mt-4 space-y-4">
                @foreach ($audit as $i => $item)
                    <div class="relative pl-5" wire:key="oa-{{ $i }}">
                        <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-slate-300"></span>
                        <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                        <p class="text-xs text-slate-500">{{ $item['who'] }}</p>
                        <p class="text-xs text-slate-400">{{ $item['at']->format('M j, Y g:i A') }}</p>
                        @if ($item['note']) <p class="mt-0.5 text-xs text-slate-500">{{ $item['note'] }}</p> @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>