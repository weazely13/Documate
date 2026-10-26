@php
    use App\Workflow\Documents\DocumentRegistry;

    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';

    // One plain-language status per row, tied to the tab it sits in.
    $statusFor = function ($row) use ($tab) {
        return match (true) {
            $tab === 'action_required' => ['Waiting on student', 'bg-amber-50 text-amber-800'],
            $tab === 'signing' => ['Signed ' . (int) $row->approved_count . ' of ' . (int) $row->approvals_count, 'bg-slate-100 text-slate-700'],
            $row->workflow_status?->value === 'approved' => ['Ready to finish', 'bg-emerald-600 text-white'],
            default => ['Needs review', 'bg-[#2A57B4]/10 text-[#1E4090]'],
        };
    };

    $oldest = $overdue->first();

    // Ready-to-finish rows are highlighted green: everyone has signed, only the final check is left.
    $rowTint = fn ($row) => $row->workflow_status?->value === 'approved' ? 'bg-emerald-50' : $row->waiting['row'];
@endphp

<div class="mx-auto max-w-5xl space-y-6">

    <x-admin.page-header
        title="Workflow Review"
        description="Transactions that need the VPSD office, longest waiting first. Finished ones are in Transaction Records."
        :crumbs="['Admin' => $homeUrl, 'Workflow Review' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.workflow.issue') }}" wire:navigate
               class="w-full rounded-lg bg-[#2A57B4] px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#24499A] sm:w-auto sm:py-2">
                Issue a document
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Overdue warning --}}
    @if ($overdue->isNotEmpty())
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 sm:px-5">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
            </svg>
            <p class="text-sm leading-relaxed text-red-900">
                <span class="font-semibold">
                    {{ $overdue->count() }} {{ \Illuminate\Support\Str::plural('transaction', $overdue->count()) }}
                    {{ $overdue->count() === 1 ? 'has' : 'have' }} waited a week or more.
                </span>
                @if ($oldest)
                    The oldest is {{ $oldest->field_values['student_name'] ?? $oldest->user?->first_name }}'s
                    {{ DocumentRegistry::find($oldest->document_type)?->name() ?? $oldest->document_type }}.
                @endif
                They are at the top of their tab.
            </p>
        </div>
    @endif

    @if ($readyCount > 0)
        <div class="flex flex-col gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <p>
                <span class="font-semibold">{{ $readyCount }} {{ \Illuminate\Support\Str::plural('transaction', $readyCount) }} {{ $readyCount === 1 ? 'is' : 'are' }} ready to finish.</span>
                Everyone has signed. Open {{ $readyCount === 1 ? 'it' : 'each one' }} and click Complete.
            </p>
            @if ($tab !== 'attention')
                <button type="button" wire:click="setTab('attention')" class="shrink-0 self-start font-semibold text-emerald-800 hover:underline sm:self-auto">Show them &rarr;</button>
            @endif
        </div>
    @endif

    {{-- Tabs + search --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <nav class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 sm:inline-flex" aria-label="Queue">
            @foreach ($tabs as $key => $label)
                @php $late = $lateCounts[$key] ?? 0; @endphp
                <button type="button" wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}"
                        @if ($tab === $key) aria-current="page" @endif
                        class="flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-2 text-center text-[13px] leading-tight transition sm:shrink-0 sm:flex-row sm:gap-2 sm:px-3.5 sm:text-sm
                            {{ $tab === $key ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'font-medium text-slate-600 hover:text-slate-900' }}">
                    {{ $label }}
                    <span class="text-xs tabular-nums {{ $late ? 'font-semibold text-red-700' : ($tab === $key ? 'text-[#2A57B4]' : 'text-slate-500') }}">
                        {{ $counts[$key] }}@if ($late) · {{ $late }} late @endif
                    </span>
                </button>
            @endforeach
        </nav>

        <div class="relative lg:w-72">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
            </svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, student number or code"
                   aria-label="Search" class="w-full pl-9 pr-3 {{ $field }}">
        </div>
    </div>

    {{-- Waiting-time legend (only where it is flagged) --}}
    @if (in_array($tab, \App\Livewire\Admin\Workflow\Index::FLAGGED_TABS, true))
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600">
            <span class="font-semibold">Waiting (school days)</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>Under 1</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>1–2</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>3–4 Follow up</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>5–6 Urgent</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-900"></span>7+ Overdue</span>
        </div>
    @endif

    {{-- Phones: sort menu --}}
    <div class="flex items-center gap-2 md:hidden">
        <label for="sort-menu" class="shrink-0 text-xs font-medium text-slate-500">Sort by</label>
        <select id="sort-menu" wire:model.live="sort" class="w-full {{ $field }}">
            @foreach ($sortOptions as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table (md and up) --}}
    <div class="hidden overflow-hidden rounded-2xl border border-slate-200/70 bg-white md:block">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-100 text-xs">
                <tr>
                    <x-admin.sort-th key="student" label="Student and document" :sort="$sortKey" :dir="$sortDir" class="px-6" />
                    <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" />
                    <x-admin.sort-th key="waiting" label="Waiting" :sort="$sortKey" :dir="$sortDir" />
                    <th class="w-10"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    @php
                        $doc = DocumentRegistry::find($row->document_type);
                        [$statusText, $statusClass] = $statusFor($row);
                    @endphp
                    <tr wire:key="row-{{ $row->workspace_id }}" class="cursor-pointer transition hover:brightness-[0.98] {{ $rowTint($row) ?: 'hover:bg-slate-50' }}"
                        onclick="Livewire.navigate('{{ route('admin.workflow.show', $row->workspace_id) }}')">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.workflow.show', $row->workspace_id) }}" wire:navigate onclick="event.stopPropagation()"
                               class="block max-w-[340px] truncate font-semibold text-slate-900 hover:text-[#2A57B4]">
                                {{ $row->field_values['student_name'] ?? trim(($row->user?->first_name ?? '') . ' ' . ($row->user?->last_name ?? '')) ?: '-' }}
                            </a>
                            <p class="mt-0.5 max-w-[340px] truncate text-[13px] text-slate-500" title="{{ $doc?->name() ?? $row->document_type }}">
                                {{ $doc?->name() ?? $row->document_type }}
                            </p>
                            @if ($row->issuer)
                                <p class="mt-0.5 max-w-[340px] truncate text-xs text-slate-500">Issued by <span class="text-slate-700">{{ $row->issuer }}</span></p>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4">
                            <span class="inline-flex items-center gap-2 {{ $row->waiting['tone'] }}">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                                {{ $row->waiting['text'] }}
                            </span>
                        </td>
                        <td class="pr-4 text-slate-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center text-sm text-slate-500">
                            {{ trim($search) !== '' ? 'No match. Try another name or clear the search.' : 'Nothing waiting here.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Cards (phones) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($rows as $row)
            @php
                $doc = DocumentRegistry::find($row->document_type);
                [$statusText, $statusClass] = $statusFor($row);
            @endphp
            <a href="{{ route('admin.workflow.show', $row->workspace_id) }}" wire:navigate wire:key="card-{{ $row->workspace_id }}"
               class="block rounded-2xl border p-4 transition active:brightness-95 {{ $row->workflow_status?->value === 'approved' ? 'border-emerald-200' : 'border-slate-200/70' }} {{ $rowTint($row) ?: 'bg-white' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                        <p class="mt-0.5 text-[13px] text-slate-500">{{ $doc?->name() ?? $row->document_type }}</p>
                        @if ($row->issuer)
                            <p class="mt-0.5 text-xs text-slate-500">Issued by <span class="text-slate-700">{{ $row->issuer }}</span></p>
                        @endif
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span>
                </div>
                <p class="mt-3 inline-flex items-center gap-2 text-sm {{ $row->waiting['tone'] }}">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                    {{ $row->waiting['text'] }}
                </p>
            </a>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">
                {{ trim($search) !== '' ? 'No match. Try another name or clear the search.' : 'Nothing waiting here.' }}
            </p>
        @endforelse
    </div>

    @if ($rows->hasPages())
        <div>{{ $rows->links() }}</div>
    @endif
</div>
