@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $control = 'h-9 rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 [@media(pointer:coarse)]:h-11';
    $filtering = $search !== '' || $docType !== '' || $result !== 'all' || $sort !== 'oldest';
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') ?: '?';
@endphp

{{-- Signing inbox: only the work. What is waiting for a signature, and the decisions already made.
     Figures live on the Activity summary; roles and reports live on Home. --}}
<div class="mx-auto max-w-5xl space-y-4 pb-24" wire:poll.15s="checkNew">

    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Signing inbox</h1>
            <p class="mt-0.5 text-sm text-slate-500">Documents waiting for a signature from a role you hold, and what you decided.</p>
        </div>
    </div>

    @if (session('signing_message'))
        <p role="status" class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <i class="bx bx-check-circle mt-0.5 text-base text-emerald-600" aria-hidden="true"></i>{{ session('signing_message') }}
        </p>
    @endif

    @if (! auth()->user()->signature_path && auth()->user()->signature_enabled)
        <div class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-amber-900">You have not uploaded your e-signature yet, so you cannot sign.</p>
            <a href="{{ route('profile') }}" wire:navigate class="inline-flex min-h-9 shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-medium text-white hover:bg-amber-700 {{ $focus }}">Add signature</a>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        {{-- Tabs and filters, one line --}}
        <div class="relative flex items-center gap-1.5 border-b border-slate-200 p-2 sm:gap-2 sm:p-3">
            <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Inbox">
                @foreach (['waiting' => ['Waiting', $waitingTotal, 'bx-time-five'], 'history' => ['Decisions', $history->total(), 'bx-history']] as $key => [$text, $count, $icon])
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')" wire:key="inbox-tab-{{ $key }}"
                            class="inline-flex min-h-8 items-center gap-1 rounded-md px-2 text-sm transition-colors sm:gap-1.5 sm:px-3 [@media(pointer:coarse)]:min-h-10 {{ $focus }} {{ $tab === $key ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $text }}
                        <span class="{{ $key === 'history' ? 'max-sm:hidden' : '' }} rounded-full px-1.5 text-[11px] font-semibold tabular-nums {{ $tab === $key ? ($key === 'waiting' && $count ? 'bg-amber-100 text-amber-800' : 'bg-[#e2e9f7] text-[#1E4090]') : 'bg-white/80 text-slate-600' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>

            <x-dm.toolbar-search class="ml-auto sm:max-w-xs" placeholder="Student, number or code" label="Search by student name, student number or transaction code" />

            {{-- Phones: the two filters as icons on this line --}}
            <label class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 sm:hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11" title="Document type">
                <i class="bx bx-file text-lg" aria-hidden="true"></i>
                @if ($docType)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white" aria-hidden="true"></span>@endif
                <span class="sr-only">Document type</span>
                <select wire:model.live="docType" class="{{ $control }} absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    <option value="">All documents</option>
                    @foreach ($docTypes as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 sm:hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11" title="{{ $tab === 'waiting' ? 'Order' : 'Result' }}">
                <i class="bx {{ $tab === 'waiting' ? 'bx-sort' : 'bx-filter-alt' }} text-lg" aria-hidden="true"></i>
                @if (($tab === 'waiting' && $sort !== 'oldest') || ($tab === 'history' && $result !== 'all'))<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white" aria-hidden="true"></span>@endif
                <span class="sr-only">{{ $tab === 'waiting' ? 'Order' : 'Result' }}</span>
                @if ($tab === 'waiting')
                    <select wire:model.live="sort" class="{{ $control }} absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        <option value="oldest">Oldest first</option>
                        <option value="newest">Newest first</option>
                    </select>
                @else
                    <select wire:model.live="result" class="{{ $control }} absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        <option value="all">Signed and rejected</option>
                        <option value="approved">Signed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                @endif
            </label>
            @if ($filtering)
                <button type="button" wire:click="clearFilters" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-[#2A57B4] hover:bg-[#f0f4fb] {{ $focus }} [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11" aria-label="Clear filters" title="Clear filters">
                    <i class="bx bx-reset text-lg" aria-hidden="true"></i>
                </button>
            @endif
        </div>

        <div class="hidden items-center gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 sm:flex">
            <label class="flex items-center gap-2 text-xs text-slate-500">Document
                <select wire:model.live="docType" class="{{ $control }} w-56 pl-3 pr-8">
                    <option value="">All documents</option>
                    @foreach ($docTypes as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            @if ($tab === 'waiting')
                <label class="flex items-center gap-2 text-xs text-slate-500">Order
                    <select wire:model.live="sort" class="{{ $control }} w-36 pl-3 pr-8">
                        <option value="oldest">Oldest first</option>
                        <option value="newest">Newest first</option>
                    </select>
                </label>
            @else
                <label class="flex items-center gap-2 text-xs text-slate-500">Result
                    <select wire:model.live="result" class="{{ $control }} w-44 pl-3 pr-8">
                        <option value="all">Signed and rejected</option>
                        <option value="approved">Signed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </label>
            @endif
        </div>

        {{-- WAITING --}}
        @if ($tab === 'waiting')
            <ul class="min-h-[22rem] divide-y divide-slate-100" wire:loading.class="opacity-60" aria-label="Waiting for your signature">
                @forelse ($pending as $a)
                    @php
                        $doc = DocumentRegistry::find($a->workspace->document_type);
                        $late = $a->opened_at && $a->opened_at->lt(now()->subDays(2));
                        $who = $a->workspace->field_values['student_name'] ?? 'Student';
                    @endphp
                    <li wire:key="pend-{{ $a->id }}" class="relative flex items-center gap-3 px-3 py-3 transition-colors hover:bg-slate-50 sm:px-4">
                        <x-dm.avatar :user="$a->workspace->user" :name="$who" class="h-9 w-9 text-xs" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('signatory.show', $a->id) }}" wire:navigate
                               class="block truncate text-sm font-medium text-slate-900 after:absolute after:inset-0 after:content-[''] hover:text-[#2A57B4] {{ $focus }}">
                                {{ $doc?->name() ?? 'Document' }} <span class="font-normal text-slate-600 max-sm:block max-sm:truncate"><span class="text-slate-500">for</span> {{ $who }}</span>
                            </a>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 truncate text-xs text-slate-500">
                                <span class="font-mono tabular-nums">{{ $a->workspace->transaction_code }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $a->label }} as {{ $a->role->label ?? 'Account signer' }}</span>
                            </p>
                        </div>
                        <span class="hidden shrink-0 text-right sm:block">
                            <span class="inline-flex items-center gap-1 text-xs tabular-nums {{ $late ? 'font-medium text-amber-800' : 'text-slate-500' }}">@if ($late)<i class="bx bx-time-five text-sm" aria-hidden="true"></i>@endif{{ $a->opened_at ? 'Waiting ' . $a->opened_at->diffForHumans(null, true) : 'Just arrived' }}</span>
                        </span>
                        <span class="relative z-10 inline-flex min-h-8 shrink-0 items-center gap-1 rounded-lg bg-[#2A57B4] px-3 text-sm font-medium text-white shadow-sm [@media(pointer:coarse)]:min-h-10" aria-hidden="true">
                            <span class="hidden sm:inline">Review</span><i class="bx bx-right-arrow-alt text-base"></i>
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-20 text-center">
                        <i class="bx {{ $filtering ? 'bx-search-alt' : 'bx-check-double' }} mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                        <p class="text-sm font-medium text-slate-700">{{ $filtering ? 'Nothing matches your search or filters.' : 'Nothing to sign right now.' }}</p>
                        @unless ($filtering)<p class="mt-0.5 text-xs text-slate-500">You get a notification when something arrives.</p>@endunless
                    </li>
                @endforelse
            </ul>
            @if ($pending->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $pending->links() }}</div>
            @endif

        {{-- DECISIONS --}}
        @else
            <ul class="min-h-[22rem] divide-y divide-slate-100" wire:loading.class="opacity-60" aria-label="Your decisions">
                @forelse ($history as $a)
                    @php
                        $ok = $a->status->value === 'approved';
                        $doc = DocumentRegistry::find($a->workspace->document_type);
                        $who = $a->workspace->field_values['student_name'] ?? 'Student';
                    @endphp
                    <li wire:key="hist-{{ $a->id }}" class="relative flex items-center gap-3 px-3 py-3 transition-colors hover:bg-slate-50 sm:px-4">
                        <span class="relative shrink-0">
                            <x-dm.avatar :user="$a->workspace->user" :name="$who" class="h-9 w-9 text-xs" />
                            <span class="absolute -bottom-0.5 -right-0.5 grid h-4 w-4 place-items-center rounded-full ring-2 ring-white {{ $ok ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}" aria-hidden="true">
                                <i class="bx {{ $ok ? 'bx-check' : 'bx-x' }} text-[11px]"></i>
                            </span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('signatory.show', $a->id) }}" wire:navigate
                               class="block truncate text-sm font-medium text-slate-900 after:absolute after:inset-0 after:content-[''] hover:text-[#2A57B4] {{ $focus }}">{{ $who }}</a>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                <span class="font-mono tabular-nums">{{ $a->workspace->transaction_code }}</span> · {{ $doc?->name() }}
                            </p>
                        </div>
                        <span class="shrink-0 text-right">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $ok ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-rose-50 text-rose-800 ring-rose-200' }}">{{ $ok ? 'Signed' : 'Rejected' }}</span>
                            <span class="mt-0.5 block text-[11px] tabular-nums text-slate-500">{{ $a->acted_at?->format('M j, Y g:i A') }}</span>
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-20 text-center">
                        <i class="bx bx-history mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                        <p class="text-sm font-medium text-slate-700">{{ $filtering ? 'Nothing matches your search or filters.' : 'You have not signed or rejected anything yet.' }}</p>
                    </li>
                @endforelse
            </ul>
            @if ($history->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $history->links() }}</div>
            @endif
        @endif
    </div>
</div>
