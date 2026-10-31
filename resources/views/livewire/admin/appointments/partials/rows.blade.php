{{-- Appointment rows, after a table with an actions column: a table once the list is wide enough, cards below that.
     A row opens the full record. Needs: $rows, $mode ('review' | 'queue' | 'history'), plus the board's
     $statusFor, $aiFor, $initials, $avatar, $focus, $selectedIds, $search. --}}
@php
    $sessionShort = ['morning' => 'Morning', 'afternoon' => 'Afternoon'];
    $selectable = $mode === 'review';
    $pendingIds = $rows->where('status', 'pending')->pluck('appointment_id')->map(fn ($id) => (string) $id)->values();
    $empty = trim($search) !== '' ? 'No match. Try another name or clear the search.' : match ($mode) {
        'review' => 'No requests waiting for review on this day.',
        'queue' => 'Nobody is in the queue for this day yet.',
        default => 'No appointments here.',
    };
    $act = 'grid h-8 w-8 place-items-center rounded-lg text-base transition-colors [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;
    $cols = 6 + ($selectable ? 1 : 0) + ($mode !== 'queue' ? 1 : 0);
@endphp

<div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white cq-md:block"
     wire:loading.class="opacity-60" wire:target="setPhase, selectDate, step, jumpToday, search, filterDate, filterStatus, gotoPage, nextPage, previousPage">
    <table class="min-w-full text-sm">
        <thead class="border-b border-slate-200 text-xs text-slate-500">
            <tr>
                @if ($selectable)
                    <th scope="col" class="w-10 py-2.5 pl-4 pr-1">
                        @if ($pendingIds->isNotEmpty())
                            <input type="checkbox" aria-label="Select all requests on this list"
                                   @checked($pendingIds->diff($selectedIds)->isEmpty())
                                   x-on:change="$wire.set('selectedIds', $event.target.checked ? @js($pendingIds) : [])"
                                   class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        @endif
                    </th>
                @endif
                <th scope="col" class="w-14 px-3 py-2.5 text-left font-medium">No.</th>
                <th scope="col" class="px-3 py-2.5 text-left font-medium">Student</th>
                <th scope="col" class="px-3 py-2.5 text-left font-medium">Purpose and document</th>
                <th scope="col" class="px-3 py-2.5 text-left font-medium">{{ $mode === 'history' ? 'Date' : ($mode === 'review' ? 'Requested' : 'Session') }}</th>
                @if ($mode !== 'queue')
                    <th scope="col" class="px-3 py-2.5 text-left font-medium">AI check</th>
                @endif
                <th scope="col" class="px-3 py-2.5 text-left font-medium">Status</th>
                <th scope="col" class="px-3 py-2.5 text-right font-medium">Action</th>
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
                    <td class="px-3 py-3 font-medium tabular-nums text-slate-700">{{ $a->queue_number ? str_pad($a->queue_number, 2, '0', STR_PAD_LEFT) : '–' }}</td>
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                {{ $initials($a->user) }}
                                @if ($avatar($a->user))
                                    <img src="{{ $avatar($a->user) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                @endif
                            </span>
                            <div class="min-w-0">
                                <a href="{{ $url }}" wire:navigate onclick="event.stopPropagation()"
                                   class="block max-w-[200px] truncate rounded font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">
                                    {{ $a->user?->full_name ?? 'Unknown student' }}
                                </a>
                                <p class="truncate text-xs tabular-nums text-slate-500">{{ $a->user?->student_number }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <p class="max-w-[240px] truncate text-slate-800" title="{{ $a->purpose }}">{{ $a->purpose ?: 'General visit' }}</p>
                        <p class="max-w-[240px] truncate text-xs text-slate-500">{{ $a->workspace ? $a->workspace->documentName() : 'No document' }}</p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-slate-700">
                        @if ($mode === 'history')
                            <span class="block tabular-nums">{{ $a->appointment_date?->format('d M Y') }}</span>
                            <span class="text-xs text-slate-500">{{ $sessionShort[$a->session] ?? '' }}</span>
                        @elseif ($mode === 'review')
                            <span class="block tabular-nums">{{ $a->created_at?->format('d M, g:i A') }}</span>
                            <span class="text-xs text-slate-500">{{ $sessionShort[$a->session] ?? '' }}</span>
                        @else
                            {{ $sessionShort[$a->session] ?? '' }}
                        @endif
                    </td>
                    @if ($mode !== 'queue')
                        <td class="whitespace-nowrap px-3 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aPill }}"
                                  @if (! empty($a->ai_findings['summary'])) title="{{ $a->ai_findings['summary'] }}" @endif>
                                @if ($spin) <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif{{ $aText }}
                            </span>
                        </td>
                    @endif
                    <td class="whitespace-nowrap px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $sPill }}">{{ $sText }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-2" onclick="event.stopPropagation()">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ $url }}" wire:navigate class="{{ $act }} bg-[#f0f4fb] text-[#2A57B4] hover:bg-[#e2e9f7]"
                               aria-label="Open the record of {{ $a->user?->full_name }}" title="Open record">
                                <i class="bx bx-show" aria-hidden="true"></i>
                            </a>
                            @if ($a->status === 'pending')
                                <button type="button" wire:click="openDialog('approve', 'one', {{ $a->appointment_id }})"
                                        class="{{ $act }} bg-emerald-50 text-emerald-700 hover:bg-emerald-100" aria-label="Approve {{ $a->user?->full_name }}" title="Approve">
                                    <i class="bx bx-check" aria-hidden="true"></i>
                                </button>
                                <button type="button" wire:click="openDialog('reject', 'one', {{ $a->appointment_id }})"
                                        class="{{ $act }} bg-rose-50 text-rose-700 hover:bg-rose-100" aria-label="Reject {{ $a->user?->full_name }}" title="Reject">
                                    <i class="bx bx-x" aria-hidden="true"></i>
                                </button>
                            @elseif ($a->status === 'approved' && $mode === 'queue')
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
                <tr><td colspan="{{ $cols }}" class="px-6 py-14 text-center text-sm text-slate-500">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<ul class="grid gap-3 cq-sm:grid-cols-2 cq-md:hidden" aria-label="Appointments">
    @forelse ($rows as $a)
        @php
            [$sText, $sPill] = $statusFor($a);
            [$aText, $aPill, $spin] = $aiFor($a);
            $url = route('admin.appointments.show', $a->appointment_id);
        @endphp
        <li wire:key="ac-{{ $a->appointment_id }}" class="relative flex flex-col rounded-2xl border border-slate-200 bg-white">
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
                <span class="mt-3 block text-sm text-slate-800">{{ $a->purpose ?: 'General visit' }}</span>
                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $a->workspace ? $a->workspace->documentName() : 'No document' }}</span>
                <span class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $sPill }}">{{ $sText }}</span>
                    @if ($mode !== 'queue')
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aPill }}">
                            @if ($spin) <i class="bx bx-loader-alt bx-spin text-sm" aria-hidden="true"></i> @endif<span class="sr-only">AI check:</span> {{ $aText }}
                        </span>
                    @endif
                </span>
            </a>
            @if ($a->status === 'pending' || ($a->status === 'approved' && $mode === 'queue'))
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
