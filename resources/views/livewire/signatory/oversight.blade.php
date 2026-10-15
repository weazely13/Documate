@php
    use App\Workflow\Documents\DocumentRegistry;
    $input = 'rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10';
    $filtering = $search !== '' || $type !== '' || $status !== '';
@endphp

<div class="mx-auto max-w-6xl space-y-5" wire:poll.60s>
    <div>
        <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Signing Inbox</a>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">All Documents</h2>
        <p class="mt-0.5 text-sm text-slate-500">Every transaction in DocuMate. This view is read-only.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([['All', $counts['all']], ['In progress', $counts['open']], ['Completed', $counts['completed']], ['Rejected / voided', $counts['closed']]] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-[#2A57B4]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-[220px] flex-1">
            <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code" class="{{ $input }} w-full pl-10">
        </div>
        <select wire:model.live="type" class="{{ $input }} max-w-[240px]">
            <option value="">All documents</option>
            @foreach ($docTypes as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
        </select>
        <select wire:model.live="status" class="{{ $input }}">
            <option value="">Any status</option>
            @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
        </select>
        @if ($filtering)
            <button type="button" wire:click="clearFilters" class="px-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Clear</button>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Document</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3">Signatures</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
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
                    @empty
                        <tr><td colspan="6" class="px-4 py-14 text-center text-sm text-slate-500">{{ $filtering ? 'Nothing matches your search or filters.' : 'No transactions yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </div>
</div>