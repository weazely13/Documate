@php
    use App\Workflow\Documents\DocumentRegistry;
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-base text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10 sm:py-2.5 sm:text-sm';
    $filtering = $search !== '' || $docType !== '' || $result !== 'all' || $sort !== 'oldest';
@endphp

<div class="mx-auto max-w-3xl space-y-5 sm:space-y-6" wire:poll.15s="checkNew">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Signing Inbox</h2>
            <p class="mt-1 text-sm text-slate-500">Documents waiting for a signature from a role you hold. Oldest first.</p>
        </div>
        @if (count(auth()->user()->issuableDocuments()))
            <a href="{{ route('signatory.issue') }}" wire:navigate
               class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl bg-[#2A57B4] px-5 text-sm font-semibold text-white hover:bg-[#24499A]">Issue a report</a>
        @endif
    </div>

    @if (session('signing_message'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('signing_message') }}</div>
    @endif

    @if (! auth()->user()->signature_path && auth()->user()->signature_enabled)
        <div class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-amber-900">You have not uploaded your e-signature yet, so you cannot sign.</p>
            <a href="{{ route('profile') }}" wire:navigate
               class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white hover:bg-amber-700">Add signature</a>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Waiting for you', $stats['waiting'], $stats['oldest_waiting'] ? 'Oldest: ' . $stats['oldest_waiting']->diffForHumans() : 'All caught up'],
            ['Signed', $stats['signed'], $stats['this_month'] . ' this month'],
            ['Rejected', $stats['rejected'], null],
            ['Avg. response', $stats['avg_response'] ?? '-', 'Opened to signed'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4">
                <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold text-[#2A57B4] sm:text-2xl">{{ $value }}</p>
                @if ($hint)<p class="mt-0.5 truncate text-xs text-slate-500">{{ $hint }}</p>@endif
            </div>
        @endforeach

        @if ($byRole->isNotEmpty())
            <div class="col-span-2 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4 lg:col-span-4">
                <p class="text-xs font-medium text-slate-500">Signed by role</p>
                <div class="mt-1 grid gap-x-6 sm:grid-cols-2">
                    @foreach ($byRole as $r)
                        <p class="flex justify-between gap-3 py-0.5 text-sm"><span class="truncate">{{ $r->label }}</span><strong>{{ $r->signed }}</strong></p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Roles I hold (collapsed by default so the work list stays near the top) --}}
    <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="flex min-h-[56px] cursor-pointer list-none items-center justify-between gap-3 p-4 sm:px-5 [&::-webkit-details-marker]:hidden">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Your signing {{ $myRoles->count() === 1 ? 'role' : 'roles' }}</span>
                <span class="block text-xs text-slate-400">You can sign documents that need any of these roles.</span>
            </span>
            <span class="flex items-center gap-2">
                <span class="rounded-full bg-[#2A57B4]/10 px-2.5 py-1 text-xs font-semibold text-[#2A57B4]">{{ $myRoles->count() }}</span>
                <i class='bx bx-chevron-down text-2xl text-slate-400 transition group-open:rotate-180'></i>
            </span>
        </summary>

        <div class="grid gap-3 border-t border-slate-100 p-4 sm:grid-cols-2 sm:p-5">
            @forelse ($myRoles as $a)
                @php $n = (int) ($waitingByRole[$a->signatory_role_id] ?? 0); @endphp
                <div wire:key="myrole-{{ $a->id }}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-xl text-[#2A57B4]"><i class='bx bx-id-card'></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900">{{ $a->role->label ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $a->scope_name ? 'For ' . $a->scope_name : 'All students' }}</p>
                        <p class="mt-1 text-[11px] text-slate-400">{{ $a->active_until ? 'Until ' . $a->active_until->format('M j, Y') : 'No end date' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $n > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $n > 0 ? $n . ' waiting' : 'Clear' }}
                    </span>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">You do not hold a signing role right now.</p>
            @endforelse
        </div>
    </details>

    {{-- Tabs (segmented control) --}}
    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="tablist">
        @foreach (['waiting' => ['Waiting', $pending->total()], 'history' => ['Decisions', $history->total()]] as $key => [$text, $count])
            <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    wire:click="$set('tab', '{{ $key }}')" wire:key="inbox-tab-{{ $key }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $tab === $key ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                {{ $text }}
                <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $tab === $key ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-white text-slate-500' }}">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Search and filters --}}
    <div class="space-y-2 sm:flex sm:flex-wrap sm:items-center sm:gap-2 sm:space-y-0">
        <div class="relative sm:min-w-[220px] sm:flex-1">
            <i class='bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code" class="{{ $input }} pl-10">
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-2">
            <select wire:model.live="docType" class="{{ $input }} sm:max-w-[220px]" aria-label="Document type">
                <option value="">All documents</option>
                @foreach ($docTypes as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>

            @if ($tab === 'waiting')
                <select wire:model.live="sort" class="{{ $input }}" aria-label="Sort order">
                    <option value="oldest">Oldest first</option>
                    <option value="newest">Newest first</option>
                </select>
            @else
                <select wire:model.live="result" class="{{ $input }}" aria-label="Result">
                    <option value="all">Signed and rejected</option>
                    <option value="approved">Signed only</option>
                    <option value="rejected">Rejected only</option>
                </select>
            @endif
        </div>
        @if ($filtering)
            <button type="button" wire:click="clearFilters" class="inline-flex min-h-[44px] items-center px-2 text-sm font-semibold text-slate-500 hover:text-slate-800">Clear filters</button>
        @endif
    </div>

    {{-- WAITING --}}
    @if ($tab === 'waiting')
        <section class="space-y-3">
            @forelse ($pending as $a)
                @php
                    $doc = DocumentRegistry::find($a->workspace->document_type);
                    $late = $a->opened_at && $a->opened_at->lt(now()->subDays(2));
                @endphp
                <a href="{{ route('signatory.show', $a->id) }}" wire:navigate wire:key="pend-{{ $a->id }}"
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-4 shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md {{ $late ? 'border-amber-200' : 'border-slate-200' }}">
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] font-semibold text-slate-400">{{ $a->workspace->transaction_code }}</p>
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $doc?->name() ?? 'Document' }} for {{ $a->workspace->field_values['student_name'] ?? 'student' }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $a->label }} as {{ $a->role->label ?? 'Account signer' }}. Waiting {{ $a->opened_at?->diffForHumans(null, true) ?? 'since just now' }}
                            @if ($late) <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700">Waiting long</span> @endif
                        </p>
                    </div>
                    <span class="rounded-lg bg-[#2A57B4] px-3.5 py-2 text-sm font-semibold text-white">Review and sign</span>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                    {{ $filtering ? 'Nothing matches your search or filters.' : 'Nothing to sign right now. You will get a notification when something arrives.' }}
                </div>
            @endforelse

            @if ($pending->hasPages())
                <div class="pt-1">{{ $pending->links() }}</div>
            @endif
        </section>

    {{-- HISTORY --}}
    @else
        <section class="space-y-3">
            @forelse ($history as $a)
                @php
                    $ok = $a->status->value === 'approved';
                    $doc = DocumentRegistry::find($a->workspace->document_type);
                @endphp
                <a href="{{ route('signatory.show', $a->id) }}" wire:navigate wire:key="hist-{{ $a->id }}"
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300">
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] font-semibold text-slate-400">{{ $a->workspace->transaction_code }}</p>
                        <p class="truncate text-sm font-medium text-slate-800">{{ $a->workspace->field_values['student_name'] ?? 'Student' }}</p>
                        <p class="text-xs text-slate-400">{{ $doc?->name() }} &middot; {{ $a->acted_at?->format('M j, Y g:i A') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $ok ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">{{ $ok ? 'Signed' : 'Rejected' }}</span>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                    {{ $filtering ? 'Nothing matches your search or filters.' : 'You have not signed or rejected anything yet.' }}
                </p>
            @endforelse

            @if ($history->hasPages())
                <div class="pt-1">{{ $history->links() }}</div>
            @endif
        </section>
    @endif
</div>