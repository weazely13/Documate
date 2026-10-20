@php
    use App\Workflow\Documents\DocumentRegistry;

    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';
@endphp

<div class="mx-auto max-w-6xl">

    <x-admin.page-header
        title="Workflow Review"
        description="Digital transactions waiting for the VPSD office. Oldest first under Needs action."
        :crumbs="['Admin' => $homeUrl, 'Workflow Review' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.workflow.issue') }}" wire:navigate
               class="w-full rounded-lg bg-[#2A57B4] px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#24499A] sm:w-auto sm:py-2">
                Issue a document
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Queue tabs --}}
    <div class="mb-4 border-b border-slate-200">
        <nav class="-mb-px flex gap-5 overflow-x-auto whitespace-nowrap" aria-label="Queue">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}"
                        @if ($tab === $key) aria-current="page" @endif
                        class="inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition
                            {{ $tab === $key
                                ? 'border-[#2A57B4] text-[#2A57B4]'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[11px] font-medium tabular-nums {{ $tab === $key ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex sm:flex-wrap sm:items-center">
        <div class="relative sm:min-w-[240px] sm:max-w-sm sm:flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
            </svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search code, name or student number"
                   class="w-full pl-9 pr-3 {{ $field }}">
        </div>

        {{-- Sort menu (phones; on larger screens use the column headers) --}}
        <div class="flex items-center gap-2 md:hidden">
            <label for="sort-menu" class="shrink-0 text-xs font-medium text-slate-500">Sort by</label>
            <select id="sort-menu" wire:model.live="sort" class="w-full {{ $field }}">
                @foreach ($sortOptions as $value => $text)
                    <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
            </select>
        </div>

        @if ($sortKey)
            <button type="button" wire:click="clearSort" class="hidden text-xs font-medium text-slate-500 hover:text-slate-800 md:inline">Reset sorting</button>
        @endif

        <span class="text-xs text-slate-500 sm:ml-auto">{{ number_format($rows->total()) }} {{ \Illuminate\Support\Str::plural('transaction', $rows->total()) }}</span>
    </div>

    {{-- Table (md and up) --}}
    <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs">
                    <tr>
                        <x-admin.sort-th key="code" label="Code" :sort="$sortKey" :dir="$sortDir" />
                        <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" />
                        <x-admin.sort-th key="document" label="Document" :sort="$sortKey" :dir="$sortDir" />
                        <x-admin.sort-th key="submitted" label="Submitted" :sort="$sortKey" :dir="$sortDir" />
                        <x-admin.sort-th key="signatures" label="Signatures" :sort="$sortKey" :dir="$sortDir" />
                        <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); @endphp
                        <tr wire:key="row-{{ $row->workspace_id }}" class="cursor-pointer transition hover:bg-slate-50"
                            onclick="Livewire.navigate('{{ route('admin.workflow.show', $row->workspace_id) }}')">
                            <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-500">
                                <a href="{{ route('admin.workflow.show', $row->workspace_id) }}" wire:navigate
                                   onclick="event.stopPropagation()" class="hover:text-[#2A57B4] hover:underline">{{ $row->transaction_code }}</a>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="max-w-[220px] truncate font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ $row->user?->student_number }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-slate-700">
                                {{ $doc?->name() ?? $row->document_type }}
                                @if ($doc?->issuedByOffice())
                                    <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Issued</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-slate-500">{{ $row->submitted_at?->format('M j, g:i A') ?? '-' }}</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count : '-' }}</td>
                            <td class="px-4 py-3.5">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center text-sm text-slate-500">
                                Nothing here. Try another tab or clear the search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cards (phones) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($rows as $row)
            @php $doc = DocumentRegistry::find($row->document_type); @endphp
            <a href="{{ route('admin.workflow.show', $row->workspace_id) }}" wire:navigate wire:key="card-{{ $row->workspace_id }}"
               class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition active:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <span class="font-mono text-xs font-semibold text-slate-500">{{ $row->transaction_code }}</span>
                    @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                </div>

                <p class="mt-2 truncate font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                <p class="text-xs text-slate-400">{{ $row->user?->student_number }}</p>

                <p class="mt-2 text-sm text-slate-700">
                    {{ $doc?->name() ?? $row->document_type }}
                    @if ($doc?->issuedByOffice())
                        <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Issued</span>
                    @endif
                </p>

                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span>{{ $row->submitted_at?->format('M j, g:i A') ?? 'Not submitted' }}</span>
                    <span>{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count . ' signed' : 'No signatures yet' }}</span>
                </div>
            </a>
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">
                Nothing here. Try another tab or clear the search.
            </p>
        @endforelse
    </div>

    @if ($rows->hasPages())
        <div class="mt-4">{{ $rows->links() }}</div>
    @endif
</div>