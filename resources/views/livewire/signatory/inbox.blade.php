@php use App\Workflow\Documents\DocumentRegistry; @endphp

<div class="mx-auto max-w-3xl space-y-8" wire:poll.30s>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Signing Inbox</h2>
            <p class="mt-0.5 text-sm text-slate-500">Documents waiting for a signature from a role you hold. Oldest first.</p>
        </div>
        @if (count(auth()->user()->issuableDocuments()))
            <a href="{{ route('signatory.issue') }}" wire:navigate class="rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A]">Issue a report</a>
        @endif
    </div>

    @if (session('signing_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('signing_message') }}</div>
    @endif

    @if (! auth()->user()->signature_path)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-900">You have not uploaded your e-signature yet, so you cannot sign.</p>
            <a href="{{ route('profile') }}" wire:navigate class="rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-700">Add signature</a>
        </div>
    @endif

    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach ([
            ['Waiting for you', $stats['waiting'], $stats['oldest_waiting'] ? 'Oldest: ' . $stats['oldest_waiting']->diffForHumans() : 'All caught up'],
            ['Signed', $stats['signed'], $stats['this_month'] . ' this month'],
            ['Rejected', $stats['rejected'], null],
            ['Avg. response', $stats['avg_response'] ?? '-', 'From opened to signed'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-[#2A57B4]">{{ $value }}</p>
                @if ($hint)<p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>@endif
            </div>
        @endforeach

        @if ($byRole->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Signed by role</p>
                @foreach ($byRole as $r)
                    <p class="mt-1 flex justify-between text-sm"><span>{{ $r->label }}</span><strong>{{ $r->signed }}</strong></p>
                @endforeach
            </div>
        @endif
    </div>

    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-900">Waiting for you ({{ $pending->count() }})</h3>

        @forelse ($pending as $a)
            @php $doc = DocumentRegistry::find($a->workspace->document_type); @endphp
            <a href="{{ route('signatory.show', $a->id) }}" wire:navigate wire:key="pend-{{ $a->id }}"
               class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                <div class="min-w-0">
                    <p class="font-mono text-[11px] font-semibold text-slate-400">{{ $a->workspace->transaction_code }}</p>
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $doc?->name() ?? 'Document' }} for {{ $a->workspace->field_values['student_name'] ?? 'student' }}</p>
                    <p class="text-xs text-slate-500">{{ $a->label }} as {{ $a->role->label }}. Waiting {{ $a->opened_at?->diffForHumans(null, true) ?? 'since just now' }}</p>
                </div>
                <span class="rounded-lg bg-[#2A57B4] px-3.5 py-2 text-sm font-semibold text-white">Review and sign</span>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                Nothing to sign right now. You will get a notification when something arrives.
            </div>
        @endforelse
    </section>

    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-900">Your recent decisions</h3>

        @forelse ($history as $a)
            @php $ok = $a->status->value === 'approved'; @endphp
            <a href="{{ route('signatory.show', $a->id) }}" wire:navigate wire:key="hist-{{ $a->id }}"
               class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300">
                <div class="min-w-0">
                    <p class="font-mono text-[11px] font-semibold text-slate-400">{{ $a->workspace->transaction_code }}</p>
                    <p class="truncate text-sm font-medium text-slate-800">{{ $a->workspace->field_values['student_name'] ?? 'Student' }}</p>
                    <p class="text-xs text-slate-400">{{ $a->acted_at?->format('M j, Y g:i A') }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $ok ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">{{ $ok ? 'Signed' : 'Rejected' }}</span>
            </a>
        @empty
            <p class="text-sm text-slate-500">You have not signed or rejected anything yet.</p>
        @endforelse
    </section>
</div>
