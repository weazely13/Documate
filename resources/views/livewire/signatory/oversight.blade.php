@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $control = 'h-9 rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 [@media(pointer:coarse)]:h-11';
    $pick = 'relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 sm:block sm:h-auto sm:w-auto sm:border-0 max-sm:[@media(pointer:coarse)]:h-11 max-sm:[@media(pointer:coarse)]:w-11';
    $pickField = $control . ' absolute inset-0 h-full w-full cursor-pointer pl-3 pr-8 opacity-0 sm:static sm:opacity-100';
    $filtering = $search !== '' || $type !== '' || $status !== '';
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') ?: '?';

    // The four figures, as a summary strip in the folder tints (DESIGN.md: sky = to review, lavender = signing,
    // mint = done, rose = stopped). Each one filters the list.
    $strip = [
        ['All transactions', $counts['all'], '', 'bg-white', 'bx-collection'],
        ['In progress', $counts['open'], 'open', 'bg-[#dcecfa]', 'bx-loader-circle'],
        ['Completed', $counts['completed'], 'completed', 'bg-[#d9f3e1]', 'bx-check-shield'],
        ['Rejected or voided', $counts['closed'], 'closed', 'bg-[#fde0ec]', 'bx-block'],
    ];
@endphp

{{-- All documents: the VPSD's read-only view of every transaction in DocuMate. --}}
<div class="mx-auto max-w-6xl space-y-4 pb-24" wire:poll.60s>
    <h1 class="sr-only">All documents (read-only)</h1>

    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 shadow-sm lg:grid-cols-4">
        @foreach ($strip as [$label, $value, $filter, $tint, $icon])
            @php $on = $filter !== '' && $status === $filter; @endphp
            <button type="button" wire:click="$set('status', '{{ $on ? '' : $filter }}')" aria-pressed="{{ $on ? 'true' : 'false' }}"
                    class="relative isolate flex min-w-0 flex-col items-start overflow-hidden p-3.5 text-left transition-colors sm:p-4 {{ $tint }} {{ $focus }} {{ $on ? 'ring-2 ring-inset ring-[#2A57B4]' : 'hover:brightness-[0.98]' }}">
                <i class="bx {{ $icon }} pointer-events-none absolute -bottom-2 -right-1 -z-10 text-5xl text-slate-900/[0.06]" aria-hidden="true"></i>
                <span class="text-xs font-medium text-slate-700">{{ $label }}</span>
                <span class="mt-1 text-2xl font-semibold tabular-nums tracking-[-0.02em] text-slate-900">{{ number_format($value) }}</span>
            </button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="relative flex items-center gap-1.5 border-b border-slate-200 p-2 sm:gap-2 sm:p-3">
            <x-dm.toolbar-search class="sm:max-w-sm" placeholder="Student, number or code" label="Search by student name, student number or transaction code" />
            <label class="{{ $pick }} sm:ml-auto" title="Document type">
                <i class="bx bx-file text-lg sm:!hidden" aria-hidden="true"></i>
                @if ($type)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white sm:hidden" aria-hidden="true"></span>@endif
                <span class="sr-only">Document type</span>
                <select wire:model.live="type" class="{{ $pickField }} sm:w-56">
                    <option value="">All documents</option>
                    @foreach ($docTypes as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                </select>
            </label>
            <label class="{{ $pick }}" title="Status">
                <i class="bx bx-filter-alt text-lg sm:!hidden" aria-hidden="true"></i>
                @if ($status)<span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white sm:hidden" aria-hidden="true"></span>@endif
                <span class="sr-only">Status</span>
                <select wire:model.live="status" class="{{ $pickField }} sm:w-40">
                    <option value="">Any status</option>
                    <option value="open">In progress (any)</option>
                    <option value="closed">Rejected or voided</option>
                    @foreach ($statuses as $key => $name) <option value="{{ $key }}">{{ $name }}</option> @endforeach
                </select>
            </label>
            @if ($filtering)
                <button type="button" wire:click="clearFilters" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-[#2A57B4] hover:bg-[#f0f4fb] {{ $focus }}" aria-label="Clear filters" title="Clear filters">
                    <i class="bx bx-reset text-lg" aria-hidden="true"></i>
                </button>
            @endif
            <span class="ml-auto hidden shrink-0 text-xs tabular-nums text-slate-500 lg:inline">{{ number_format($rows->total()) }} shown</span>
        </div>

        <div class="min-h-[24rem]" wire:loading.class="opacity-60">
            @if ($rows->isEmpty())
                <div class="px-6 py-20 text-center">
                    <i class="bx {{ $filtering ? 'bx-search-alt' : 'bx-collection' }} mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                    <p class="text-sm font-medium text-slate-700">{{ $filtering ? 'Nothing matches your search or filters.' : 'No transactions yet.' }}</p>
                </div>
            @else
                {{-- Phones: rows --}}
                <ul class="divide-y divide-slate-100 md:hidden">
                    @foreach ($rows as $row)
                        @php $doc = DocumentRegistry::find($row->document_type); $who = $row->field_values['student_name'] ?? '-'; @endphp
                        <li wire:key="ovm-{{ $row->workspace_id }}" class="relative flex items-center gap-3 px-3 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">{{ $initials($who) }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('signatory.oversight.show', $row->workspace_id) }}" wire:navigate
                                   class="block truncate text-sm font-medium text-slate-900 after:absolute after:inset-0 after:content-[''] {{ $focus }}">{{ $who }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $doc?->name() ?? $row->document_type }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                @include('livewire.workflow._badge', ['status' => $row->workflow_status])
                                <span class="text-[11px] tabular-nums text-slate-500">{{ $row->updated_at?->format('M j') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Wider: table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 font-medium">Student</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Document</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Signatures</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                @php $doc = DocumentRegistry::find($row->document_type); $who = $row->field_values['student_name'] ?? '-'; @endphp
                                <tr wire:key="ov-{{ $row->workspace_id }}" class="cursor-pointer transition-colors hover:bg-slate-50"
                                    onclick="Livewire.navigate('{{ route('signatory.oversight.show', $row->workspace_id) }}')">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#e2e9f7] text-[11px] font-semibold text-[#1E4090]" aria-hidden="true">{{ $initials($who) }}</span>
                                            <div class="min-w-0">
                                                <a href="{{ route('signatory.oversight.show', $row->workspace_id) }}" wire:navigate onclick="event.stopPropagation()"
                                                   class="block max-w-[220px] truncate rounded font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">{{ $who }}</a>
                                                <p class="text-xs tabular-nums text-slate-500">{{ $row->user?->student_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="max-w-[260px] truncate text-slate-800">{{ $doc?->name() ?? $row->document_type }}</p>
                                        <p class="font-mono text-xs tabular-nums text-slate-500">{{ $row->transaction_code }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($row->approvals_count)
                                            <span class="flex items-center gap-2">
                                                <span class="block h-1.5 w-14 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><span class="block h-full rounded-full bg-violet-600" style="width: {{ $row->approved_count / $row->approvals_count * 100 }}%"></span></span>
                                                <span class="text-xs tabular-nums text-slate-600">{{ $row->approved_count }}/{{ $row->approvals_count }}</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">@include('livewire.workflow._badge', ['status' => $row->workflow_status])</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-xs tabular-nums text-slate-500">{{ $row->updated_at?->format('M j, g:i A') }}</td>
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
    </div>
</div>
