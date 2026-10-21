@php $toSelf = $document->issuedToSelf(); @endphp

<div class="mx-auto max-w-6xl space-y-4 sm:space-y-5">
    {{-- Header --}}
    <div>
        <a href="{{ route('signatory.issued', ['folder' => $ws->document_type]) }}" wire:navigate
           class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
            <i class='bx bx-chevron-left text-xl'></i> Issued Documents
        </a>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $document->name() }}</h2>
                <p class="mt-0.5 font-mono text-xs text-slate-400">
                    {{ $ws->transaction_code }}@unless ($toSelf) &middot; {{ $ws->field_values['student_name'] ?? '' }}@endunless
                </p>
            </div>
            <div class="shrink-0">@include('livewire.workflow._badge', ['status' => $ws->workflow_status])</div>
        </div>
    </div>

    {{-- Status and file actions --}}
    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex sm:items-center sm:justify-between sm:space-y-0 sm:gap-4 sm:p-5">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900">Status</p>
            <p class="mt-0.5 text-sm text-slate-600">{{ $nextStep }}</p>
            <p class="mt-1 text-xs text-slate-400">Issued {{ $ws->submitted_at?->format('M j, Y g:i A') }}
                @if ($ws->completed_at)<br class="sm:hidden"><span class="hidden sm:inline"> &middot; </span>Completed {{ $ws->completed_at->format('M j, Y g:i A') }}@endif
            </p>
        </div>
        @if ($mine || $ws->workflow_status === \App\Enums\TransactionStatus::Completed)
            <div class="flex flex-col gap-2 sm:flex-row sm:shrink-0">
                @if ($mine)
                    <a href="{{ route('signatory.show', $mine->id) }}" wire:navigate
                       class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open signing page</a>
                @endif
                @if ($ws->workflow_status === \App\Enums\TransactionStatus::Completed)
                    <a href="{{ route('signatory.issued.download', $ws->workspace_id) }}"
                       class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"><i class='bx bx-download text-lg'></i> Download PDF</a>
                @endif
            </div>
        @endif
    </section>
    @if ($ws->workflow_status === \App\Enums\TransactionStatus::Completed)
        <p class="-mt-2 text-xs text-slate-400">Print the PDF only if a physical copy is required.</p>
    @endif

    <div class="grid gap-4 sm:gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-4 sm:space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <p class="text-xs text-slate-400">A preview of the layout. The downloaded PDF is the official copy, so spacing may differ slightly.</p>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('signatory.issued.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

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
                    @if ($ws->verification_token)
                        <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener"
                           class="mt-2 inline-flex min-h-[44px] items-center text-sm font-semibold text-[#2A57B4] hover:underline">Open the public verification page</a>
                    @endif
                </section>
            @endif
        </div>

        @include('livewire.signatory._activity', ['audit' => $audit, 'prefix' => 'ia'])
    </div>
</div>