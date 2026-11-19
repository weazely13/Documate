{{-- Appointment rows in the Workflow Review table style: a table once the list is wide enough, cards below that.
     A row opens the full record (there is no separate open button). The table always fits its box: the purpose
     column takes the width that is left and truncates. Needs: $rows, $mode ('day' = the Day list, one table for the day with a checkbox
     on every row; 'history' = All), plus the board's $statusFor, $aiFor, $initials, $avatar, $focus, $selectedIds, $search. --}}
@php
    use App\Support\StatusStyle;

    $sessionShort = ['morning' => 'Morning', 'afternoon' => 'Afternoon'];
    $selectable = $mode === 'day';
    $rowIds = $rows->pluck('appointment_id')->map(fn ($id) => (string) $id)->values();
    $empty = trim($search) !== '' ? 'No match. Try another name or clear the search.' : ($mode === 'day' ? 'No appointments on this day.' : 'No appointments here.');
    $act = 'grid h-8 w-8 place-items-center rounded-lg text-base transition-colors [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;
    $th = 'px-4 py-2.5 text-left font-medium text-slate-500';
    // The All tab numbers its rows (#) before the queue number, counting on across pages ($offset = rows on earlier pages)
    $counted = $mode === 'history';
    $offset ??= 0;
    $cols = 7 + ($selectable ? 1 : 0) + ($counted ? 1 : 0);
    // The All tab sorts by its column headers, as Workflow Review does
    $sortable = $mode === 'history';
    [$sortKey, $sortDir] = $sortable ? $this->parsedSort() : [null, null];

    // What the header icons explain: the status words (StatusStyle) and the AI pills, in their colours.
    $statusLegend = collect([
        'pending' => 'Requested. Waiting for the office to approve or reject it.',
        'approved' => 'Booked, with a queue number for the session.',
        'attended' => 'The student came to the office.',
        'missed' => 'The session ended and the student did not come.',
        'rejected' => 'The office turned the request down.',
        'retracted' => 'The student cancelled it.',
    ])->map(function ($meaning, $key) {
        $s = StatusStyle::appointment($key);

        return [StatusStyle::DOTS[$s['tone']] ?? 'bg-slate-400', $s['label'], '', $meaning];
    })->values()->all();
    $aiLegend = [
        ['bg-emerald-500', 'Clear', '', 'The purpose and the details look fine.'],
        ['bg-amber-500', 'Flagged', '', 'Something may be off. Open it and take a closer look.'],
        ['bg-amber-500', 'Mismatch', '', 'The purpose does not fit the office or the document picked.'],
        ['bg-rose-500', 'Reject', '', 'The AI would reject it, for example for made-up or unrelated details.'],
        ['bg-sky-500', 'Checking', '', 'The check is running or waiting its turn. It usually takes a few seconds.'],
        ['bg-orange-500', 'Failed', '', 'The check could not finish. Run it again from the selection bar.'],
    ];
@endphp

{{-- min-h keeps a full working area under the rows, so a short list never sits cramped against the bulk bar or the page end. --}}
<div class="hidden min-h-[28rem] overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity cq-md:block"
     wire:loading.class="opacity-60" wire:target="selectDate, step, jumpToday, search, filterFrom, filterTo, filterStatus, filterAi, filterYear, filterProgram, resetFilters, sortBy, gotoPage, nextPage, previousPage">
    <table class="w-full text-sm">
        <thead class="border-b border-slate-200 text-xs">
            <tr>
                @if ($selectable)
                    <th scope="col" class="w-10 py-2.5 pl-4 pr-1">
                        @if ($rowIds->isNotEmpty())
                            <input type="checkbox" aria-label="Select every appointment on this list"
                                   @checked($rowIds->diff($selectedIds)->isEmpty())
                                   x-on:change="$wire.set('selectedIds', $event.target.checked ? @js($rowIds) : [])"
                                   class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        @endif
                    </th>
                @endif
                @if ($counted)
                    <th scope="col" class="w-12 {{ $th }}">#</th>
                @endif
                <th scope="col" class="w-16 {{ $th }}">Queue No.</th>
                @if ($sortable)
                    <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" class="w-[42%] !py-2.5" />
                @else
                    <th scope="col" class="w-[42%] {{ $th }}">Student</th>
                @endif
                <th scope="col" class="w-[58%] {{ $th }}">Purpose and document</th>
                @if ($sortable)
                    <x-admin.sort-th key="date" label="Date" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                @else
                    <th scope="col" class="{{ $th }}">Session</th>
                @endif
                {{-- Under 60rem of table the AI suggestion sits under the status pill, so Student and Purpose keep room to read --}}
                <th scope="col" class="hidden [@container(min-width:60rem)]:table-cell {{ $th }}">
                    <span class="inline-flex items-center gap-1 whitespace-nowrap">AI suggestion
                        <x-admin.legend-tip label="What the AI suggestions mean" note="The AI reads each request before you do. It only suggests: you make the decision." :items="$aiLegend" />
                    </span>
                </th>
                @if ($sortable)
                    <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" class="!py-2.5">
                        <x-admin.legend-tip label="What the statuses mean" note="Where each appointment stands." :items="$statusLegend" />
                    </x-admin.sort-th>
                @else
                    <th scope="col" class="{{ $th }}">
                        <span class="inline-flex items-center gap-1">Status
                            <x-admin.legend-tip label="What the statuses mean" note="Where each appointment stands." :items="$statusLegend" />
                        </span>
                    </th>
                @endif
                <th scope="col" class="{{ $th }} !text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $a)
                @php
                    [$sText, $sPill] = $statusFor($a);
                    [$aText, $aPill, $spin] = $aiFor($a);
                    $url = route('admin.appointments.show', $a->appointment_id);
                @endphp
                <tr wire:key="ar-{{ $a->appointment_id }}" class="cursor-pointer transition-colors hover:bg-slate-50" onclick="Livewire.navigate('{{ $url }}')">
                    @if ($selectable)
                        <td class="py-3 pl-4 pr-1" onclick="event.stopPropagation()">
                            <input type="checkbox" wire:model.live="selectedIds" value="{{ $a->appointment_id }}"
                                   aria-label="Select {{ $a->user?->full_name }}"
                                   class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        </td>
                    @endif
                    @if ($counted)
                        <td class="px-4 py-3 text-xs tabular-nums text-slate-500">{{ $offset + $loop->iteration }}</td>
                    @endif
                    <td class="px-4 py-3 text-xs tabular-nums text-slate-500">{{ $a->queue_number ? str_pad($a->queue_number, 2, '0', STR_PAD_LEFT) : '–' }}</td>
                    <td class="max-w-0 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                {{ $initials($a->user) }}
                                @if ($avatar($a->user))
                                    <img src="{{ $avatar($a->user) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                @endif
                            </span>
                            <div class="min-w-0">
                                <a href="{{ $url }}" wire:navigate onclick="event.stopPropagation()"
                                   class="block max-w-[11rem] truncate rounded text-left font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}" title="{{ $a->user?->full_name }}">
                                    {{ $a->user?->full_name ?? 'Unknown student' }}
                                </a>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $a->user?->student_number }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="max-w-0 px-4 py-3">
                        <p class="truncate text-slate-700" title="{{ $a->purpose ?: 'General visit' }}">{{ $a->purpose ?: 'General visit' }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500" title="{{ $a->workspace ? $a->workspace->documentName() : 'No document' }}">{{ $a->workspace ? $a->workspace->documentName() : 'No document' }}</p>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                        @if ($mode === 'history')
                            <span class="block tabular-nums">{{ $a->appointment_date?->format('M j, Y') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $sessionShort[$a->session] ?? '' }}</span>
                        @else
                            <span class="block">{{ $sessionShort[$a->session] ?? '' }}</span>
                            @if ($a->status === 'pending')
                                <span class="mt-0.5 block text-xs tabular-nums text-slate-500">Requested {{ $a->created_at?->format('M j, g:i A') }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="hidden whitespace-nowrap px-4 py-3 [@container(min-width:60rem)]:table-cell">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aPill }}"
                              @if (! empty($a->ai_findings['summary'])) title="{{ $a->ai_findings['summary'] }}" @endif>
                            @if ($spin) <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif{{ $aText }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="{{ StatusStyle::BADGE }} {{ $sPill }}">{{ $sText }}</span>
                        <span class="mt-1.5 flex [@container(min-width:60rem)]:hidden">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aPill }}"
                                  @if (! empty($a->ai_findings['summary'])) title="{{ $a->ai_findings['summary'] }}" @endif>
                                @if ($spin) <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif<span class="sr-only">AI suggestion:</span> {{ $aText }}
                            </span>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-end gap-1">
                            @if ($a->status === 'pending')
                                <button type="button" wire:click="openDialog('approve', 'one', {{ $a->appointment_id }})"
                                        class="{{ $act }} bg-emerald-50 text-emerald-700 hover:bg-emerald-100" aria-label="Approve {{ $a->user?->full_name }}" title="Approve">
                                    <i class="bx bx-check" aria-hidden="true"></i>
                                </button>
                                <button type="button" wire:click="openDialog('reject', 'one', {{ $a->appointment_id }})"
                                        class="{{ $act }} bg-rose-50 text-rose-700 hover:bg-rose-100" aria-label="Reject {{ $a->user?->full_name }}" title="Reject">
                                    <i class="bx bx-x" aria-hidden="true"></i>
                                </button>
                            @elseif ($a->status === 'approved' && $mode === 'day')
                                <button type="button" wire:click="setNowServing('{{ $a->session }}', {{ $a->queue_number }})"
                                        class="{{ $act }} bg-sky-50 text-[#1E4090] hover:bg-sky-100" aria-label="Call number {{ $a->queue_number }}, {{ $a->user?->full_name }}" title="Call to the counter">
                                    <i class="bx bx-bell" aria-hidden="true"></i>
                                </button>
                                <button type="button" wire:click="markAttended({{ $a->appointment_id }})" wire:confirm="Mark {{ $a->user?->full_name }} as attended?"
                                        class="{{ $act }} bg-emerald-50 text-emerald-700 hover:bg-emerald-100" aria-label="Mark {{ $a->user?->full_name }} as attended" title="Attended">
                                    <i class="bx bx-check-double" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $cols }}" class="px-6 py-24 text-center text-sm text-slate-500">
                    <i class="bx bx-calendar-x mx-auto mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>{{ $empty }}
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<ul class="grid min-h-[20rem] content-start gap-3 cq-sm:grid-cols-2 cq-md:hidden" aria-label="Appointments">
    @forelse ($rows as $a)
        @php
            [$sText, $sPill] = $statusFor($a);
            [$aText, $aPill, $spin] = $aiFor($a);
            $url = route('admin.appointments.show', $a->appointment_id);
        @endphp
        <li wire:key="ac-{{ $a->appointment_id }}" class="relative flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white">
            <a href="{{ $url }}" wire:navigate class="block flex-1 rounded-2xl p-4 {{ $focus }}">
                <span class="flex items-start gap-3 {{ $selectable ? 'pr-8' : '' }}">
                    <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                        {{ $initials($a->user) }}
                        @if ($avatar($a->user))
                            <img src="{{ $avatar($a->user) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[15px] font-semibold text-slate-900">{{ $a->user?->full_name ?? 'Unknown student' }}</span>
                        <span class="block truncate text-xs tabular-nums text-slate-500">
                            {{ $a->user?->student_number }}{{ $a->queue_number ? ' · #' . $a->queue_number : '' }}
                            · {{ $mode === 'history' ? $a->appointment_date?->format('M j') . ', ' : '' }}{{ $sessionShort[$a->session] ?? '' }}
                        </span>
                    </span>
                </span>
                <span class="mt-3 block truncate text-sm text-slate-800" title="{{ $a->purpose ?: 'General visit' }}">{{ $a->purpose ?: 'General visit' }}</span>
                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $a->workspace ? $a->workspace->documentName() : 'No document' }}</span>
                <span class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="{{ StatusStyle::BADGE }} {{ $sPill }}">{{ $sText }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aPill }}">
                        @if ($spin) <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif<span class="sr-only">AI suggestion:</span> {{ $aText }}
                    </span>
                </span>
            </a>
            @if ($a->status === 'pending' || ($a->status === 'approved' && $mode === 'day'))
                <div class="flex justify-end gap-1 border-t border-slate-100 px-2 py-1.5">
                    @if ($a->status === 'pending')
                        <button type="button" wire:click="openDialog('reject', 'one', {{ $a->appointment_id }})" class="{{ $act }} !w-auto px-3 text-sm font-medium text-rose-700 hover:bg-rose-50">Reject</button>
                        <button type="button" wire:click="openDialog('approve', 'one', {{ $a->appointment_id }})" class="{{ $act }} !w-auto bg-emerald-50 px-3 text-sm font-medium text-emerald-800 hover:bg-emerald-100">Approve</button>
                    @else
                        <button type="button" wire:click="setNowServing('{{ $a->session }}', {{ $a->queue_number }})" class="{{ $act }} !w-auto px-3 text-sm font-medium text-[#1E4090] hover:bg-sky-50">Call</button>
                        <button type="button" wire:click="markAttended({{ $a->appointment_id }})" wire:confirm="Mark {{ $a->user?->full_name }} as attended?"
                                class="{{ $act }} !w-auto bg-emerald-50 px-3 text-sm font-medium text-emerald-800 hover:bg-emerald-100">Attended</button>
                    @endif
                </div>
            @endif
            @if ($selectable)
                <label class="absolute right-2 top-2 grid h-11 w-11 place-items-center">
                    <span class="sr-only">Select {{ $a->user?->full_name }}</span>
                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $a->appointment_id }}"
                           class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                </label>
            @endif
        </li>
    @empty
        <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 cq-sm:col-span-2">{{ $empty }}</li>
    @endforelse
</ul>
