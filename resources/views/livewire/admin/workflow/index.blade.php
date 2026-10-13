@php use App\Workflow\Documents\DocumentRegistry; @endphp

<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Workflow Review</h2>
            <p class="mt-0.5 text-sm text-slate-500">Digital transactions waiting for the VPSD office. Oldest first under Needs action.</p>
        </div>
        <a href="{{ route('admin.workflow.issue') }}" wire:navigate
        class="rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A]">Issue a document</a>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}"
                    class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ $tab === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $tab === $key ? 'bg-white/20' : 'bg-white text-slate-500' }}">{{ $counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <div class="relative max-w-sm">
        <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search code, name or student number"
               class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Document</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Signatures</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); @endphp
                        <tr wire:key="row-{{ $row->workspace_id }}" class="cursor-pointer transition hover:bg-slate-50"
                            onclick="Livewire.navigate('{{ route('admin.workflow.show', $row->workspace_id) }}')">
                            <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-500">{{ $row->transaction_code }}</td>
                            <td class="px-4 py-3.5">
                                <p class="max-w-[220px] truncate font-semibold text-slate-900">{{ $row->field_values['student_name'] ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ $row->user?->student_number }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-slate-700">
                                {{ $doc?->name() ?? $row->document_type }}
                                @if ($doc?->issuedByOffice())
                                    <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">Issued</span>
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
        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
