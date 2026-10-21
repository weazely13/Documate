@php
    use App\Workflow\Documents\DocumentRegistry;
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-base text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10 sm:py-2.5 sm:text-sm';
    $filtering = $search !== '' || $folder !== '' || $status !== '' || $sort !== 'newest';
    $subjectOf = function ($row, $doc) {
        $v = $row->field_values ?? [];
        return $doc?->issuedToSelf() ? ($doc->summary($v) ?: '-') : ($v['student_name'] ?? '-');
    };
    $done = \App\Enums\TransactionStatus::Completed;
@endphp

<div class="mx-auto max-w-6xl space-y-4 sm:space-y-5" wire:poll.30s>
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('signatory.inbox') }}" wire:navigate
               class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
                <i class='bx bx-chevron-left text-xl'></i> Signing Inbox
            </a>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Issued Documents</h2>
            <p class="mt-1 text-sm text-slate-500">Every document you issued, with its progress. Download the official PDF once the VPSD office completes it.</p>
        </div>
        <a href="{{ route('signatory.issue') }}" wire:navigate
           class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl bg-[#2A57B4] px-5 text-sm font-semibold text-white hover:bg-[#24499A] sm:mt-2">Issue a report</a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([['Issued', $stats['total']], ['In progress', $stats['open']], ['Completed', $stats['completed']], ['Rejected / voided', $stats['closed']]] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4">
                <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold text-[#2A57B4] sm:text-2xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-5">

        {{-- Folders: scrollable chips on mobile, sidebar on desktop --}}
        <aside class="min-w-0 lg:sticky lg:top-2 lg:h-fit">
            <p class="mb-2 hidden text-xs font-semibold text-slate-400 lg:block">Folders</p>
            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0 [&::-webkit-scrollbar]:hidden">
                <button type="button" wire:click="setFolder('')" wire:key="folder-all" aria-pressed="{{ $folder === '' ? 'true' : 'false' }}"
                        class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3 text-left text-sm font-medium transition {{ $folder === '' ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 lg:bg-transparent lg:ring-0' }}">
                    <i class='bx {{ $folder === '' ? 'bx-folder-open' : 'bx-folder' }} text-lg'></i>
                    <span class="flex-1 whitespace-nowrap">All documents</span>
                    <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $folder === '' ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $stats['total'] }}</span>
                </button>

                @foreach ($folders->sortKeys() as $key => $count)
                    @php $d = DocumentRegistry::find($key); @endphp
                    <button type="button" wire:click="setFolder('{{ $key }}')" wire:key="folder-{{ $key }}" aria-pressed="{{ $folder === $key ? 'true' : 'false' }}"
                            class="flex min-h-[44px] shrink-0 items-center gap-2.5 rounded-xl px-3 text-left text-sm font-medium transition {{ $folder === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 lg:bg-transparent lg:ring-0' }}">
                        <i class='bx {{ $folder === $key ? 'bx-folder-open' : 'bx-folder' }} text-lg'></i>
                        <span class="whitespace-nowrap lg:min-w-0 lg:flex-1 lg:truncate" title="{{ $d?->name() ?? $key }}">{{ $d?->name() ?? $key }}</span>
                        <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $folder === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        {{-- Files --}}
        <div class="min-w-0 space-y-3">
            <div class="space-y-2 sm:flex sm:flex-wrap sm:items-center sm:gap-2 sm:space-y-0">
                <div class="relative sm:min-w-[200px] sm:flex-1">
                    <i class='bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search student name, number or code" class="{{ $input }} pl-10">
                </div>
                <div class="flex gap-2">
                    <select wire:model.live="status" class="{{ $input }} min-w-0 flex-1" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                    </select>
                    <select wire:model.live="sort" class="{{ $input }} min-w-0 flex-1" aria-label="Sort order">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                    </select>
                    <div class="inline-flex shrink-0 rounded-xl border border-slate-200 bg-white p-0.5">
                        <button type="button" wire:click="setView('list')" aria-label="List view" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
                                class="flex h-11 w-11 items-center justify-center rounded-lg text-xl sm:h-10 sm:w-10 {{ $view === 'list' ? 'bg-[#2A57B4] text-white' : 'text-slate-500 hover:text-slate-800' }}"><i class='bx bx-list-ul'></i></button>
                        <button type="button" wire:click="setView('grid')" aria-label="Grid view" aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"
                                class="flex h-11 w-11 items-center justify-center rounded-lg text-xl sm:h-10 sm:w-10 {{ $view === 'grid' ? 'bg-[#2A57B4] text-white' : 'text-slate-500 hover:text-slate-800' }}"><i class='bx bx-grid-alt'></i></button>
                    </div>
                </div>
                @if ($filtering)
                    <button type="button" wire:click="clearFilters" class="inline-flex min-h-[44px] items-center px-2 text-sm font-semibold text-slate-500 hover:text-slate-800">Clear filters</button>
                @endif
            </div>

            @if ($rows->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                    {{ $filtering ? 'Nothing matches your search or filters.' : 'You have not issued any document yet.' }}
                </div>

            @elseif ($view === 'grid')
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); @endphp
                        {{-- Stretched link: the card is one tap target, the PDF button sits above it (no nested anchors) --}}
                        <div wire:key="tile-{{ $row->workspace_id }}"
                             class="relative flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition active:bg-slate-50 sm:hover:border-[#2A57B4]/40 sm:hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-2xl text-[#2A57B4]"><i class='bx bx-file'></i></span>
                                @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                            </div>
                            <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate
                               class="mt-3 truncate text-sm font-semibold text-slate-900 after:absolute after:inset-0 after:content-['']" title="{{ $doc?->name() }}">{{ $doc?->name() ?? $row->document_type }}</a>
                            <p class="truncate text-xs text-slate-600" title="{{ $subjectOf($row, $doc) }}">{{ $subjectOf($row, $doc) }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $row->transaction_code }}</p>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                                <span>{{ $row->submitted_at?->format('M j, Y') }}</span>
                                <span>{{ $row->approvals_count ? $row->approved_count . ' of ' . $row->approvals_count . ' signed' : 'No signatures' }}</span>
                            </div>
                            @if ($row->workflow_status === $done)
                                <a href="{{ route('signatory.issued.download', $row->workspace_id) }}"
                                   class="relative z-10 mt-3 inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"><i class='bx bx-download text-lg'></i> Download PDF</a>
                            @endif
                        </div>
                    @endforeach
                </div>

            @else
                {{-- Mobile: compact rows --}}
                <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:hidden">
                    @foreach ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); @endphp
                        <li wire:key="row-{{ $row->workspace_id }}" class="relative flex items-center gap-3 p-4 active:bg-slate-50">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-xl text-[#2A57B4]"><i class='bx bx-file'></i></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate
                                   class="block truncate text-sm font-semibold text-slate-900 after:absolute after:inset-0 after:content-['']">{{ $doc?->name() ?? $row->document_type }}</a>
                                <p class="truncate text-xs text-slate-600">{{ $subjectOf($row, $doc) }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-400">
                                    <span class="font-mono">{{ $row->transaction_code }}</span> &middot; {{ $row->submitted_at?->format('M j') ?? '-' }}
                                    @if ($row->approvals_count) &middot; {{ $row->approved_count }} of {{ $row->approvals_count }} signed @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                                @if ($row->workflow_status === $done)
                                    <a href="{{ route('signatory.issued.download', $row->workspace_id) }}" aria-label="Download PDF"
                                       class="relative z-10 inline-flex h-9 items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-700"><i class='bx bx-download'></i> PDF</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: table --}}
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Document</th>
                                    <th scope="col" class="px-4 py-3">Issued to</th>
                                    <th scope="col" class="px-4 py-3">Issued</th>
                                    <th scope="col" class="px-4 py-3">Signatures</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    <th scope="col" class="px-4 py-3 text-right">File</th>
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
                                            @if ($row->workflow_status === $done)
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