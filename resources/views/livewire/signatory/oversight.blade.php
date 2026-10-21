@php
    use App\Workflow\Documents\DocumentRegistry;
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-base text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10 sm:py-2.5 sm:text-sm';
    $filtering = $search !== '' || $type !== '' || $status !== '';
@endphp

<div class="mx-auto max-w-6xl space-y-4 sm:space-y-5" wire:poll.60s>
    <div>
        <a href="{{ route('signatory.inbox') }}" wire:navigate
           class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
            <i class='bx bx-chevron-left text-xl'></i> Signing Inbox
        </a>
        <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">All Documents</h2>
        <p class="mt-1 text-sm text-slate-500">Every transaction in DocuMate. This view is read-only.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([['All', $counts['all']], ['In progress', $counts['open']], ['Completed', $counts['completed']], ['Rejected / voided', $counts['closed']]] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4">
                <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold text-[#2A57B4] sm:text-2xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="space-y-2 md:flex md:flex-wrap md:items-center md:gap-2 md:space-y-0">
        <div class="relative md:min-w-[220px] md:flex-1">
            <i class='bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code" class="{{ $input }} pl-10">
        </div>
        <div class="grid grid-cols-2 gap-2 md:flex">
            <select wire:model.live="type" class="{{ $input }} md:max-w-[240px]" aria-label="Document type">
                <option value="">All documents</option>
                @foreach ($docTypes as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
            </select>
            <select wire:model.live="status" class="{{ $input }}" aria-label="Status">
                <option value="">Any status</option>
                @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
            </select>
        </div>
        @if ($filtering)
            <button type="button" wire:click="clearFilters" class="inline-flex min-h-[44px] items-center px-2 text-sm font-semibold text-slate-500 hover:text-slate-800">Clear filters</button>
        @endif
    </div>

    @if ($rows->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center text-sm text-slate-500">
            {{ $filtering ? 'Nothing matches your search or filters.' : 'No transactions yet.' }}
        </div>
    @else
        {{-- Mobile: cards --}}
        <ul class="space-y-3 md:hidden">
            @foreach ($rows as $row)
                @php $doc = DocumentRegistry::find($row->document_type); @endphp
                <li wire:key="ovm-{{ $row->workspace_id }}">
                    <a href="{{ route('signatory.oversight.show', $row->workspace_id) }}" wire:navigate
                       class="block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm active:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-[11px] font-semibold text-slate-400">{{ $row->transaction_code }}</p>
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                                <p class="truncate text-xs text-slate-600">{{ $doc?->name() ?? $row->document_type }}</p>
                            </div>
                            <div class="shrink-0">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</div>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <span>{{ $row->updated_at?->format('M j, g:i A') }}</span>
                            <span>{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count . ' signed' : 'No signatures' }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Desktop: table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Code</th>
                            <th scope="col" class="px-4 py-3">Student</th>
                            <th scope="col" class="px-4 py-3">Document</th>
                            <th scope="col" class="px-4 py-3">Updated</th>
                            <th scope="col" class="px-4 py-3">Signatures</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php $doc = DocumentRegistry::find($row->document_type); @endphp
                            <tr wire:key="ov-{{ $row->workspace_id }}" class="cursor-pointer transition hover:bg-slate-50"
                                onclick="Livewire.navigate('{{ route('signatory.oversight.show', $row->workspace_id) }}')">
                                <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-500">{{ $row->transaction_code }}</td>
                                <td class="px-4 py-3.5">
                                    <p class="max-w-[220px] truncate font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-400">{{ $row->user?->student_number }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-slate-700">{{ $doc?->name() ?? $row->document_type }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-500">{{ $row->updated_at?->format('M j, g:i A') }}</td>
                                <td class="px-4 py-3.5 text-slate-500">{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count : '-' }}</td>
                                <td class="px-4 py-3.5">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($rows->hasPages())
            <div>{{ $rows->links() }}</div>
        @endif
    @endif
</div>