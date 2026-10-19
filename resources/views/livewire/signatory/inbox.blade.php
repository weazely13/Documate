@php use App\Workflow\Documents\DocumentRegistry; @endphp

<div class="mx-auto max-w-3xl space-y-8" wire:poll.15s="checkNew">
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

    @if (! auth()->user()->signature_path && auth()->user()->signature_enabled)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-900">You have not uploaded your e-signature yet, so you cannot sign.</p>
            <a href="{{ route('profile') }}" wire:navigate class="rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-700">Add signature</a>
        </div>
    @endif
    {{-- Roles I hold --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Your signing {{ $myRoles->count() === 1 ? 'role' : 'roles' }}</h3>
                <p class="text-xs text-slate-400">You can sign documents that need any of these roles.</p>
            </div>
            <span class="rounded-full bg-[#2A57B4]/10 px-2.5 py-1 text-xs font-semibold text-[#2A57B4]">{{ $myRoles->count() }}</span>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($myRoles as $a)
                @php $n = (int) ($waitingByRole[$a->signatory_role_id] ?? 0); @endphp
                <div wire:key="myrole-{{ $a->id }}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-xl text-[#2A57B4]"><i class='bx bx-id-card'></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900" title="{{ $a->role->label ?? '-' }}">{{ $a->role->label ?? '-' }}</p>
                        @if ($a->scope_name)
                            <p class="truncate text-xs text-slate-500" title="{{ $a->scope_name }}">For {{ $a->scope_name }}</p>
                        @else
                            <p class="text-xs text-slate-400">All students</p>
                        @endif
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ $a->active_until ? 'Until ' . $a->active_until->format('M j, Y') : 'No end date' }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $n > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $n > 0 ? $n . ' waiting' : 'Clear' }}
                    </span>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                    You do not hold a signing role right now.
                </p>
            @endforelse
        </div>
    </section>
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

    @php
        $input = 'rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10';
        $filtering = $search !== '' || $docType !== '' || $result !== 'all' || $sort !== 'oldest';
    @endphp

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['waiting' => ['Waiting for you', $pending->total()], 'history' => ['Recent decisions', $history->total()]] as $key => [$text, $count])
            <button type="button" wire:click="$set('tab', '{{ $key }}')" wire:key="inbox-tab-{{ $key }}"
                    class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ $tab === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $text }}
                <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $tab === $key ? 'bg-white/20' : 'bg-white text-slate-500' }}">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Search and filters --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-[220px] flex-1">
            <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code"
                class="{{ $input }} w-full pl-10">
        </div>

        <select wire:model.live="docType" class="{{ $input }} max-w-[220px]">
            <option value="">All documents</option>
            @foreach ($docTypes as $key => $name)
                <option value="{{ $key }}">{{ $name }}</option>
            @endforeach
        </select>

        @if ($tab === 'waiting')
            <select wire:model.live="sort" class="{{ $input }}">
                <option value="oldest">Oldest first</option>
                <option value="newest">Newest first</option>
            </select>
        @else
            <select wire:model.live="result" class="{{ $input }}">
                <option value="all">Signed and rejected</option>
                <option value="approved">Signed only</option>
                <option value="rejected">Rejected only</option>
            </select>
        @endif

        @if ($filtering)
            <button type="button" wire:click="clearFilters" class="px-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Clear</button>
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
                            {{ $a->label }} as {{ $a->role->label }}. Waiting {{ $a->opened_at?->diffForHumans(null, true) ?? 'since just now' }}
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
