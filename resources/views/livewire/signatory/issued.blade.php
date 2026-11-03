@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $control = 'h-9 rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 [@media(pointer:coarse)]:h-11';
    $pick = 'relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 sm:block sm:h-auto sm:w-auto sm:border-0 max-sm:[@media(pointer:coarse)]:h-11 max-sm:[@media(pointer:coarse)]:w-11';
    $pickField = $control . ' absolute inset-0 h-full w-full cursor-pointer pl-3 pr-8 opacity-0 sm:static sm:opacity-100';
    $filtering = $search !== '' || $folder !== '' || $status !== '' || $sort !== 'newest';
    $subjectOf = function ($row, $doc) {
        $v = $row->field_values ?? [];
        return $doc?->issuedToSelf() ? ($doc->summary($v) ?: '-') : ($v['student_name'] ?? '-');
    };
    $done = \App\Enums\TransactionStatus::Completed;
    $folderName = match ($folder) {
        '' => 'All documents',
        'drafts' => 'Drafts',
        default => DocumentRegistry::find($folder)?->name() ?? $folder,
    };
    $folderList = ['' => $stats['total'], 'drafts' => $drafts->count()] + $folders->sortKeys()->all();
    $folderLabel = fn ($key) => match ($key) { '' => 'All documents', 'drafts' => 'Drafts', default => DocumentRegistry::find($key)?->name() ?? $key };
@endphp

{{-- Issued documents: a file cabinet. Folders (one per document type) on the left as a quiet text menu,
     the open folder's files on the right in one table card. --}}
<div class="mx-auto max-w-6xl space-y-4 pb-24" wire:poll.30s>
    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Issued documents</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                <span class="tabular-nums">{{ $stats['total'] }}</span> issued ·
                <span class="tabular-nums">{{ $stats['open'] }}</span> in progress ·
                <span class="tabular-nums">{{ $stats['completed'] }}</span> completed<span class="hidden sm:inline"> · <span class="tabular-nums">{{ $stats['closed'] }}</span> rejected or voided</span>
            </p>
        </div>
        <a href="{{ route('signatory.issue') }}" wire:navigate
           class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] {{ $focus }} [@media(pointer:coarse)]:min-h-11">
            <i class="bx bx-file text-base" aria-hidden="true"></i> Issue a Document
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6">

        {{-- Folders: a dropdown below lg, a text menu from lg --}}
        <label class="relative block lg:hidden">
            <span class="sr-only">Folder</span>
            <i class="bx bx-folder-open pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-[#2A57B4]" aria-hidden="true"></i>
            <select wire:change="setFolder($event.target.value)" class="{{ $control }} w-full pl-9 pr-9 font-medium text-slate-900">
                @foreach ($folderList as $key => $count)
                    <option value="{{ $key }}" @selected($folder === $key)>{{ $folderLabel($key) }} ({{ $count }})</option>
                @endforeach
            </select>
        </label>
        <nav class="hidden min-w-0 lg:sticky lg:top-2 lg:block lg:h-fit" aria-label="Folders">
            <p class="px-2.5 pb-1 text-xs font-medium text-slate-500">Folders</p>
            <div class="flex flex-col gap-px">
                @foreach ($folderList as $key => $count)
                    @php $on = $folder === $key; $name = $folderLabel($key); @endphp
                    @if ($loop->index === 2)<span class="my-1 border-t border-slate-200" aria-hidden="true"></span>@endif
                    <button type="button" wire:click="setFolder('{{ $key }}')" wire:key="folder-{{ $key ?: 'all' }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                            class="flex items-center gap-2 rounded-md px-2.5 py-[7px] text-left text-sm transition-colors {{ $focus }}
                                {{ $on ? 'bg-[#e2e9f7] font-medium text-[#24499A]' : 'text-slate-700 hover:bg-[#ecebe7]' }}">
                        <i class="bx {{ $key === 'drafts' ? 'bx-edit' : ($on ? 'bx-folder-open' : 'bx-folder') }} {{ $on ? 'text-[#2A57B4]' : 'text-slate-400' }} text-base" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1 truncate" title="{{ $name }}">{{ $name }}</span>
                        <span class="text-xs tabular-nums {{ $on ? 'text-[#24499A]' : 'text-slate-500' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </nav>

        <div class="min-w-0 space-y-4">
        {{-- Files --}}
        <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @if ($folder === 'drafts')
                {{-- Drafts: reports started on the Issue page and saved for later. Only their issuer sees them. --}}
                <div class="flex items-baseline justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <h2 class="text-[15px] font-semibold text-slate-900">Drafts <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $drafts->count() }}</span></h2>
                    <p class="text-xs text-slate-500">Saved, not issued yet. Only you can see them.</p>
                </div>
                <ul class="min-h-[24rem] divide-y divide-slate-100">
                    @forelse ($drafts as $d)
                        <li wire:key="draft-{{ $d->id }}" class="flex items-center gap-3 px-3 py-2.5 sm:px-4">
                            <x-dm.avatar :user="$d->student" :name="$d->student?->full_name ?? '?'" class="h-9 w-9 text-xs" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $d->document()?->name() ?? $d->document_type }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $d->student?->full_name ? 'For ' . $d->student->full_name : 'No student picked yet' }} · saved {{ $d->updated_at->diffForHumans() }}</p>
                            </div>
                            <a href="{{ route('signatory.issue', ['draft' => $d->id]) }}" wire:navigate
                               class="inline-flex min-h-8 shrink-0 items-center rounded-lg bg-[#2A57B4] px-3 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] {{ $focus }} [@media(pointer:coarse)]:min-h-10">Continue</a>
                            <button type="button" wire:click="deleteDraft({{ $d->id }})" wire:confirm="Delete this draft? This cannot be undone."
                                    class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-700 {{ $focus }} [@media(pointer:coarse)]:h-10 [@media(pointer:coarse)]:w-10" aria-label="Delete draft">
                                <i class="bx bx-trash text-lg" aria-hidden="true"></i>
                            </button>
                        </li>
                    @empty
                        <li class="px-6 py-20 text-center">
                            <i class="bx bx-edit mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                            <p class="text-sm font-medium text-slate-700">No drafts</p>
                            <p class="mt-0.5 text-xs text-slate-500">Use Save draft on the Issue page to finish a report later.</p>
                        </li>
                    @endforelse
                </ul>
            @else
            <div class="relative flex items-center gap-1.5 border-b border-slate-200 p-2 sm:gap-2 sm:p-3">
                <x-dm.toolbar-search class="sm:max-w-sm" placeholder="Student, number or code" label="Search by student name, student number or transaction code" />
                {{-- Phones: status and order as icons on this line --}}
                <div class="ml-auto flex items-center gap-1.5 sm:hidden">
                    <label class="{{ $pick }}" title="Status">
                        <i class="bx bx-filter-alt text-lg" aria-hidden="true"></i>
                        @if ($status)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white" aria-hidden="true"></span>@endif
                        <span class="sr-only">Status</span>
                        <select wire:model.live="status" class="{{ $control }} absolute inset-0 h-full w-full cursor-pointer opacity-0">
                            <option value="">Any status</option>
                            @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                        </select>
                    </label>
                    <label class="{{ $pick }}" title="Order">
                        <i class="bx bx-sort text-lg" aria-hidden="true"></i>
                        <span class="sr-only">Order</span>
                        <select wire:model.live="sort" class="{{ $control }} absolute inset-0 h-full w-full cursor-pointer opacity-0">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                        </select>
                    </label>
                </div>
                <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1 sm:ml-auto" role="group" aria-label="Layout">
                    @foreach (['list' => 'bx-list-ul', 'grid' => 'bx-grid-alt'] as $v => $icon)
                        <button type="button" wire:click="setView('{{ $v }}')" aria-label="{{ ucfirst($v) }} view" aria-pressed="{{ $view === $v ? 'true' : 'false' }}"
                                class="grid h-7 w-7 place-items-center rounded-md text-base {{ $focus }} [@media(pointer:coarse)]:h-9 [@media(pointer:coarse)]:w-9 {{ $view === $v ? 'bg-white text-[#24499A] shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            <i class="bx {{ $icon }}" aria-hidden="true"></i>
                        </button>
                    @endforeach
                </div>
                @if ($filtering)
                    <button type="button" wire:click="clearFilters" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-[#2A57B4] hover:bg-[#f0f4fb] {{ $focus }}" aria-label="Clear filters" title="Clear filters">
                        <i class="bx bx-reset text-lg" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
            {{-- From sm up: status and order under the header line --}}
            <div class="hidden items-center gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 sm:flex">
                <label class="flex items-center gap-2 text-xs text-slate-500">Status
                    <select wire:model.live="status" class="{{ $control }} w-40 pl-3 pr-8">
                        <option value="">Any status</option>
                        @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 text-xs text-slate-500">Order
                    <select wire:model.live="sort" class="{{ $control }} w-36 pl-3 pr-8">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                    </select>
                </label>
                <span class="ml-auto text-xs tabular-nums text-slate-500">{{ $folderName }} · {{ $rows->total() }} {{ str('file')->plural($rows->total()) }}</span>
            </div>

            <div class="min-h-[24rem]" wire:loading.class="opacity-60">
                @if ($rows->isEmpty())
                    <div class="px-6 py-20 text-center">
                        <i class="bx {{ $filtering ? 'bx-search-alt' : 'bx-folder' }} mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                        <p class="text-sm font-medium text-slate-700">{{ $filtering ? 'Nothing matches your search or filters.' : 'You have not issued anything yet.' }}</p>
                        @unless ($filtering)
                            <a href="{{ route('signatory.issue') }}" wire:navigate class="mt-1 inline-block text-sm font-medium text-[#2A57B4] hover:underline">Issue your first report</a>
                        @endunless
                    </div>

                @elseif ($view === 'grid')
                    <div class="grid gap-3 p-3 sm:grid-cols-2 sm:p-4 xl:grid-cols-3">
                        @foreach ($rows as $row)
                            @php $doc = DocumentRegistry::find($row->document_type); @endphp
                            <div wire:key="tile-{{ $row->workspace_id }}" class="relative flex flex-col rounded-lg border border-slate-200 bg-white p-3.5 transition hover:border-slate-300 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#e6e0f5] text-violet-800"><i class='bx bx-file text-lg' aria-hidden="true"></i></span>
                                    @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                                </div>
                                <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate
                                   class="mt-3 truncate text-sm font-semibold text-slate-900 after:absolute after:inset-0 after:content-[''] {{ $focus }}" title="{{ $doc?->name() }}">{{ $doc?->name() ?? $row->document_type }}</a>
                                <p class="truncate text-xs text-slate-600">{{ $subjectOf($row, $doc) }}</p>
                                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs text-slate-500">
                                    <span class="font-mono tabular-nums">{{ $row->transaction_code }}</span>
                                    <span class="tabular-nums">{{ $row->approvals_count ? $row->approved_count . '/' . $row->approvals_count . ' signed' : 'No signatures' }}</span>
                                </div>
                                @if ($row->workflow_status === $done)
                                    <a href="{{ route('signatory.issued.download', $row->workspace_id) }}"
                                       class="relative z-10 mt-2.5 inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 hover:bg-slate-50 {{ $focus }}"><i class='bx bx-download text-base text-[#2A57B4]' aria-hidden="true"></i> Download PDF</a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                @else
                    {{-- Phones: rows --}}
                    <ul class="divide-y divide-slate-100 md:hidden">
                        @foreach ($rows as $row)
                            @php $doc = DocumentRegistry::find($row->document_type); @endphp
                            <li wire:key="row-{{ $row->workspace_id }}" class="relative flex items-center gap-3 px-3 py-3">
                                <x-dm.avatar :user="$doc?->issuedToSelf() ? null : $row->user" :name="$subjectOf($row, $doc)" class="h-9 w-9 text-xs" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate
                                       class="block truncate text-sm font-medium text-slate-900 after:absolute after:inset-0 after:content-[''] {{ $focus }}">{{ $subjectOf($row, $doc) }}</a>
                                    <p class="truncate text-xs text-slate-500">{{ $doc?->name() ?? $row->document_type }} · {{ $row->submitted_at?->format('M j') ?? '-' }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                                    @if ($row->workflow_status === $done)
                                        <a href="{{ route('signatory.issued.download', $row->workspace_id) }}" class="relative z-10 inline-flex min-h-8 items-center gap-1 rounded-md px-1.5 text-xs font-medium text-[#2A57B4] {{ $focus }}"><i class='bx bx-download' aria-hidden="true"></i> PDF</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Wider: table --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs text-slate-500">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 font-medium">Issued to</th>
                                    <th scope="col" class="px-4 py-2.5 font-medium">Document</th>
                                    <th scope="col" class="px-4 py-2.5 font-medium">Issued</th>
                                    <th scope="col" class="hidden px-4 py-2.5 font-medium 2xl:table-cell">Signatures</th>
                                    <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                    <th scope="col" class="px-4 py-2.5 text-right font-medium"><span class="sr-only">File</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rows as $row)
                                    @php $doc = DocumentRegistry::find($row->document_type); @endphp
                                    <tr wire:key="file-{{ $row->workspace_id }}" class="cursor-pointer transition-colors hover:bg-slate-50"
                                        onclick="Livewire.navigate('{{ route('signatory.issued.show', $row->workspace_id) }}')">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <x-dm.avatar :user="$doc?->issuedToSelf() ? null : $row->user" :name="$subjectOf($row, $doc)" class="h-8 w-8 text-[11px]" />
                                                <div class="min-w-0">
                                                    <a href="{{ route('signatory.issued.show', $row->workspace_id) }}" wire:navigate onclick="event.stopPropagation()"
                                                       class="block max-w-[200px] truncate rounded font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">{{ $subjectOf($row, $doc) }}</a>
                                                    @unless ($doc?->issuedToSelf())
                                                        <p class="text-xs tabular-nums text-slate-500">{{ $row->user?->student_number }}</p>
                                                    @endunless
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="max-w-[220px] truncate text-slate-800" title="{{ $doc?->name() }}">{{ $doc?->name() ?? $row->document_type }}</p>
                                            <p class="font-mono text-xs tabular-nums text-slate-500">{{ $row->transaction_code }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ $row->submitted_at?->format('M j, Y') ?? '-' }}</td>
                                        <td class="hidden px-4 py-3 2xl:table-cell">
                                            @if ($row->approvals_count)
                                                <span class="flex items-center gap-2">
                                                    <span class="block h-1.5 w-14 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><span class="block h-full rounded-full bg-[#2A57B4]" style="width: {{ $row->approved_count / $row->approvals_count * 100 }}%"></span></span>
                                                    <span class="text-xs tabular-nums text-slate-600">{{ $row->approved_count }}/{{ $row->approvals_count }}</span>
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">–</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right">
                                            @if ($row->workflow_status === $done)
                                                <a href="{{ route('signatory.issued.download', $row->workspace_id) }}" onclick="event.stopPropagation()"
                                                   class="inline-flex min-h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-800 hover:bg-slate-50 {{ $focus }}">
                                                    <i class='bx bx-download text-[#2A57B4]' aria-hidden="true"></i> PDF
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($rows->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
            @endif
            @endif {{-- drafts / files --}}
        </div>
        </div>
    </div>
</div>
