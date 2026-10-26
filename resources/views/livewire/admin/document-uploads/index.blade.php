@php
    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';

    $statusBadge = fn ($status) => match ($status) {
        'Verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'Needs Re-upload' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'Reviewing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        default => 'bg-amber-50 text-amber-700 ring-amber-200',
    };
@endphp

<div class="mx-auto max-w-6xl">

    <x-admin.area-header name="documents" active="uploads" />

    {{-- Tabs --}}
    <div class="mb-4 border-b border-slate-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto whitespace-nowrap" aria-label="Upload status">
            @foreach ($filters as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')" wire:key="filter-{{ $key }}"
                        @if ($filter === $key) aria-current="page" @endif
                        class="inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition
                            {{ $filter === $key
                                ? 'border-[#2A57B4] text-[#2A57B4]'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[11px] font-medium tabular-nums {{ $filter === $key ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 grid grid-cols-2 gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:flex lg:flex-wrap lg:items-end">
        <div class="relative col-span-2 lg:min-w-[260px] lg:max-w-sm lg:flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
            </svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search student, ID, or document"
                   class="w-full pl-9 pr-3 {{ $field }}">
        </div>

        <div>
            <label for="date-from" class="mb-1 block text-xs font-medium text-slate-500">Attended from</label>
            <input id="date-from" type="date" wire:model.live="dateFrom" class="w-full {{ $field }}">
        </div>
        <div>
            <label for="date-to" class="mb-1 block text-xs font-medium text-slate-500">To</label>
            <input id="date-to" type="date" wire:model.live="dateTo" class="w-full {{ $field }}">
        </div>

        @if ($dateFrom !== '' || $dateTo !== '')
            <button type="button" wire:click="clearDateFilter"
                    class="col-span-2 py-2 text-center text-xs font-medium text-slate-500 hover:text-slate-800 lg:col-span-1 lg:py-2.5">Clear dates</button>
        @endif

        {{-- Sort menu (phones and tablets; large screens use the column headers) --}}
        <div class="col-span-2 flex items-center gap-2 md:hidden">
            <label for="sort-menu" class="shrink-0 text-xs font-medium text-slate-500">Sort by</label>
            <select id="sort-menu" wire:model.live="sort" class="w-full {{ $field }}">
                @foreach ($sortOptions as $value => $text)
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            </select>
        </div>

        @if ($sortKey)
            <button type="button" wire:click="clearSort" class="hidden py-2.5 text-xs font-medium text-slate-500 hover:text-slate-800 md:inline">Reset sorting</button>
        @endif

        <span class="col-span-2 text-xs text-slate-500 lg:ml-auto lg:py-2.5">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('student', $total) }}</span>
    </div>

    {{-- Table (md and up) --}}
    <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs">
                <tr>
                    <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" />
                    <x-admin.sort-th key="forms" label="Forms" :sort="$sortKey" :dir="$sortDir" />
                    <x-admin.sort-th key="latest" label="Latest attended" :sort="$sortKey" :dir="$sortDir" />
                    <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" />
                </tr>
            </thead>

            @forelse ($students as $s)
                {{-- One tbody per student so the expand state stays with that student --}}
                <tbody wire:key="student-{{ $s['user_id'] ?? 'unknown' }}" x-data="{ open: false }" class="border-t border-slate-100 first:border-t-0">

                    <tr @click="open = !open" class="cursor-pointer transition hover:bg-slate-50">
                        <td class="px-4 py-3.5">
                            <button type="button" @click.stop="open = !open" :aria-expanded="open" class="flex items-center gap-2.5 text-left">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                                <span>
                                    <span class="block font-semibold text-slate-900">{{ $s['student_name'] }}</span>
                                    <span class="block text-xs text-slate-400">{{ $s['student_number'] }}</span>
                                </span>
                            </button>
                        </td>
                        <td class="px-4 py-3.5 text-slate-600">{{ $s['total'] }} {{ \Illuminate\Support\Str::plural('form', $s['total']) }}</td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $s['latest_attended']?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3.5">
                            @if ($s['needs_action'] > 0)
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">{{ $s['needs_action'] }} need action</span>
                            @elseif ($s['all_verified'])
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">All verified</span>
                            @else
                                <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">Reviewing</span>
                            @endif
                        </td>
                    </tr>

                    <tr x-show="open" x-cloak>
                        <td colspan="4" class="bg-slate-50/70 px-4 py-2">
                            <ul class="divide-y divide-slate-200/70">
                                @foreach ($s['forms'] as $r)
                                    <li wire:key="doc-upload-{{ $r['workspace_id'] }}">
                                        <a href="{{ route('admin.document-uploads.show', $r['workspace_id']) }}" wire:navigate
                                           class="flex items-center justify-between gap-3 rounded-lg py-2.5 pl-8 pr-2 hover:bg-white">
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-slate-800">{{ $r['type'] }}</p>
                                                <p class="text-xs text-slate-400">Attended {{ $r['attended_at']?->format('M j, Y g:i A') ?? '—' }}</p>
                                            </div>
                                            <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusBadge($r['doc_status']) }}">{{ $r['doc_status'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr><td colspan="4" class="px-4 py-14 text-center text-sm text-slate-500">No records match your filters.</td></tr>
                </tbody>
            @endforelse
        </table>
    </div>

    {{-- Cards (phones) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($students as $s)
            <div wire:key="student-card-{{ $s['user_id'] ?? 'unknown' }}" x-data="{ open: false }"
                 class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-start gap-3 p-4 text-left">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-900">{{ $s['student_name'] }}</p>
                        <p class="text-xs text-slate-400">{{ $s['student_number'] }}</p>
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            @if ($s['needs_action'] > 0)
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">{{ $s['needs_action'] }} need action</span>
                            @elseif ($s['all_verified'])
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">All verified</span>
                            @else
                                <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">Reviewing</span>
                            @endif
                            <span class="text-xs text-slate-500">{{ $s['total'] }} {{ \Illuminate\Support\Str::plural('form', $s['total']) }} · {{ $s['latest_attended']?->format('M j, Y') ?? '—' }}</span>
                        </div>
                    </div>
                    <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                </button>

                <ul x-show="open" x-cloak class="divide-y divide-slate-100 border-t border-slate-100 bg-slate-50/70">
                    @foreach ($s['forms'] as $r)
                        <li wire:key="doc-upload-card-{{ $r['workspace_id'] }}">
                            <a href="{{ route('admin.document-uploads.show', $r['workspace_id']) }}" wire:navigate
                               class="flex items-center justify-between gap-3 px-4 py-3 active:bg-white">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $r['type'] }}</p>
                                    <p class="text-xs text-slate-400">Attended {{ $r['attended_at']?->format('M j, Y g:i A') ?? '—' }}</p>
                                </div>
                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusBadge($r['doc_status']) }}">{{ $r['doc_status'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">No records match your filters.</p>
        @endforelse
    </div>

    {{-- Pager --}}
    @if ($lastPage > 1)
        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" wire:click="previousPage" @disabled($page <= 1)
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Previous</button>
            <span class="text-sm text-slate-500">Page {{ $page }} of {{ $lastPage }}</span>
            <button type="button" wire:click="nextPage" @disabled($page >= $lastPage)
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Next</button>
        </div>
    @endif
</div>