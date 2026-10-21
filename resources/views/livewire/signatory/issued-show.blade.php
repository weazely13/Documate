@php $toSelf = $document->issuedToSelf(); @endphp

<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('signatory.issued', ['folder' => $ws->document_type]) }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Issued Documents</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $document->name() }}</h2>
            <p class="font-mono text-xs text-slate-400">
                {{ $ws->transaction_code }}@unless ($toSelf) &middot; {{ $ws->field_values['student_name'] ?? '' }}@endunless
            </p>
        </div>
        @include('livewire.workflow._badge', ['status' => $ws->workflow_status])
    </div>

    {{-- Where it stands + file actions --}}
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900">Status</p>
            <p class="mt-0.5 text-sm text-slate-600">{{ $nextStep }}</p>
            <p class="mt-1 text-xs text-slate-400">Issued {{ $ws->submitted_at?->format('M j, Y g:i A') }}
                @if ($ws->completed_at) &middot; Completed {{ $ws->completed_at->format('M j, Y g:i A') }} @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($mine)
                <a href="{{ route('signatory.show', $mine->id) }}" wire:navigate
                   class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open signing page</a>
            @endif
            @if ($ws->workflow_status === \App\Enums\TransactionStatus::Completed)
                <a href="{{ route('signatory.issued.download', $ws->workspace_id) }}"
                   class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Download PDF</a>
            @endif
        </div>
    </section>
    @if ($ws->workflow_status === \App\Enums\TransactionStatus::Completed)
        <p class="-mt-3 text-xs text-slate-400">Print the PDF only if a physical copy is required.</p>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <p class="text-xs text-slate-400">A preview of the document layout. The downloaded PDF is the official copy, so spacing may differ slightly.</p>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('signatory.issued.preview', $ws->workspace_id),
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
                    @if ($ws->verification_token)
                        <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Open the public verification page</a>
                    @endif
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Activity</h3>
            <p class="text-xs text-slate-400">Newest first</p>
            <div class="mt-4 space-y-4">
                @foreach ($audit as $i => $item)
                    <div class="relative pl-5" wire:key="ia-{{ $i }}">
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