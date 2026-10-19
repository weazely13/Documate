@php
    use App\Workflow\Documents\DocumentRegistry;
    $input = 'rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10';
    $filtering = $search !== '' || $folder !== '' || $status !== '' || $sort !== 'newest';
    $subjectOf = function ($row, $doc) {
        $v = $row->field_values ?? [];
        return $doc?->issuedToSelf() ? ($doc->summary($v) ?: '-') : ($v['student_name'] ?? '-');
    };
@endphp

<div class="mx-auto max-w-6xl space-y-5" wire:poll.30s>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Signing Inbox</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Issued Documents</h2>
            <p class="mt-0.5 text-sm text-slate-500">Every document you issued, with its progress. Download the official PDF once the VPSD office completes it.</p>
        </div>
        <a href="{{ route('signatory.issue') }}" wire:navigate class="rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A]">Issue a report</a>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([['Issued', $stats['total']], ['In progress', $stats['open']], ['Completed', $stats['completed']], ['Rejected / voided', $stats['closed']]] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-[#2A57B4]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 lg:grid-cols-[230px_minmax(0,1fr)]">

        {{-- Folders --}}
        <aside class="lg:sticky lg:top-2 lg:h-fit">
            <p class="mb-2 hidden text-xs font-bold uppercase tracking-wide text-slate-400 lg:block">Folders</p>
            <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0">
                <button type="button" wire:click="setFolder('')" wire:key="folder-all"
                        class="flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm font-medium transition {{ $folder === '' ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 lg:bg-transparent lg:ring-0' }}">
                    <i class='bx {{ $folder === '' ? 'bx-folder-open' : 'bx-folder' }} text-lg'></i>
                    <span class="flex-1 whitespace-nowrap">All documents</span>
                    <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $folder === '' ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $stats['total'] }}</span>
                </button>

                @foreach ($folders->sortKeys() as $key => $count)
                    @php $d = DocumentRegistry::find($key); @endphp
                    <button type="button" wire:click="setFolder('{{ $key }}')" wire:key="folder-{{ $key }}"
                            class="flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm font-medium transition {{ $folder === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 lg:bg-transparent lg:ring-0' }}">
                        <i class='bx {{ $folder === $key ? 'bx-folder-open' : 'bx-folder' }} text-lg'></i>
                        <span class="min-w-0 flex-1 truncate lg:max-w-[120px]" title="{{ $d?->name() ?? $key }}">{{ $d?->name() ?? $key }}</span>
                        <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $folder === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        {{-- Files --}}
        <div class="min-w-0 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code" class="{{ $input }} w-full pl-10">
                </div>
                <select wire:model.live="status" class="{{ $input }}">
                    <option value="">Any status</option>
                    @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                </select>
                <select wire:model.live="sort" class="{{ $input }}">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                </select>
                <div class="inline-flex rounded-xl border border-slate-200 bg-white p-0.5">
                    <button type="button" wire:click="setView('list')" aria-label="List view"
                            class="rounded-lg px-2.5 py-1.5 text-lg {{ $view === 'list' ? 'bg-[#2A57B4] text-white' : 'text-slate-500 hover:text-slate-800' }}"><i class='bx bx-list-ul'></i></button>
                    <button type="button" wire:click="setView('grid')" aria-label="Grid view"
                            class="rounded-lg px-2.5 py-1.5 text-lg {{ $view === 'grid' ? 'bg-[#2A57B4] text-white' : 'text-slate-500 hover:text-slate-800' }}"><i class='bx bx-grid-alt'></i></button>
                </div>
                @if ($filtering)
                    <button type="button" wire:click="clearFilters" class="px-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Clear</button>
                @endif
            </div>

            @if ($rows->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                    {{ $filtering ? 'Nothing matches your search or filters.' : 'You have not issued any document yet.' }}
                </div>

            @elseif ($view === 'grid')
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); @endphp
                        <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate wire:key="tile-{{ $row->workspace_id }}"
                           class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-2xl text-[#2A57B4]"><i class='bx bx-file'></i></span>
                                @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                            </div>
                            <p class="mt-3 truncate text-sm font-semibold text-slate-900" title="{{ $doc?->name() }}">{{ $doc?->name() ?? $row->document_type }}</p>
                            <p class="truncate text-xs text-slate-600" title="{{ $subjectOf($row, $doc) }}">{{ $subjectOf($row, $doc) }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $row->transaction_code }}</p>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                                <span>{{ $row->submitted_at?->format('M j, Y') }}</span>
                                <span>{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count . ' signed' : 'No signatures' }}</span>
                            </div>
                            @if ($row->workflow_status === \App\Enums\TransactionStatus::Completed)
                                <object><a href="{{ route('signatory.issued.download', $row->workspace_id) }}"
                                   class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-center text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Download PDF</a></object>
                            @endif
                        </a>
                    @endforeach
                </div>

            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Document</th>
                                    <th class="px-4 py-3">Issued to</th>
                                    <th class="px-4 py-3">Issued</th>
                                    <th class="px-4 py-3">Signatures</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">File</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rows as $row)
                                    @php $doc = DocumentRegistry::find($row->document_type); @endphp
                                    <tr wire:key="file-{{ $row->workspace_id }}" class="cursor-pointer transition hover:bg-slate-50"
                                        onclick="Livewire.navigate('{{ route('signatory.issued.show', $row->workspace_id) }}')">
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#2A57B4]/10 text-xl text-[#2A57B4]"><i class='bx bx-file'></i></span>
                                                <div class="min-w-0">
                                                    <p class="max-w-[240px] truncate font-semibold text-slate-900">{{ $doc?->name() ?? $row->document_type }}</p>
                                                    <p class="font-mono text-[11px] text-slate-400">{{ $row->transaction_code }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <p class="max-w-[200px] truncate text-slate-800">{{ $subjectOf($row, $doc) }}</p>
                                            @unless ($doc?->issuedToSelf())
                                                <p class="text-xs text-slate-400">{{ $row->user?->student_number }}</p>
                                            @endunless
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-slate-500">{{ $row->submitted_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                        <td class="px-4 py-3.5 text-slate-500">{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count : '-' }}</td>
                                        <td class="px-4 py-3.5">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                            @if ($row->workflow_status === \App\Enums\TransactionStatus::Completed)
                                                <a href="{{ route('signatory.issued.download', $row->workspace_id) }}" onclick="event.stopPropagation()"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                    <i class='bx bx-download'></i> PDF
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($rows->hasPages())
                <div>{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</div>