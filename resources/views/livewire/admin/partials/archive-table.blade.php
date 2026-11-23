{{-- Documents in the transaction archive as a table, styled like the Workflow review table: a # column, the student
     with their photo or initials and their number under the name, then the document, code, mode, date and status.
     Clicking a row opens the record. On phones the rows become cards, as Workflow review's do.
     Needs: $records, $showStudent (a Student column), $showDocType (a Document column beside the student),
     $offset (the number before the first row), $tintFor, $focus. --}}
@php
    $showDocType = $showDocType ?? false;
    $offset = $offset ?? 0;
    $docColumn = ! $showStudent || $showDocType;
    $columns = 5 + (int) $showStudent + (int) $docColumn;
    $avatarFor = fn ($r) => $r['user']?->avatarUrl();
@endphp

<div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white md:block">
    <table class="min-w-full text-sm">
        <thead class="border-b border-slate-200 text-xs">
            <tr>
                <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                @if ($showStudent)<x-admin.sort-th key="student" label="Student" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5" />@endif
                @if ($docColumn)<x-admin.sort-th key="document" label="Document" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5" />@endif
                <x-admin.sort-th key="code" label="Code" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5" />
                <x-admin.sort-th key="mode" label="Mode" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5">
                    <x-admin.legend-tip label="What the modes mean" note="How the transaction was made." :items="\App\Support\Legends::modes()" />
                </x-admin.sort-th>
                <x-admin.sort-th key="date" label="Date" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5" />
                <x-admin.sort-th key="status" label="Status" :sort="$tableSort ?: null" :dir="$tableDir" class="!py-2.5">
                    <x-admin.legend-tip label="What the statuses mean" note="Where each transaction is, online or paper." :items="\App\Support\Legends::transactionStatuses()" />
                </x-admin.sort-th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($records as $record)
                @php [$cBg, $cInk] = $tintFor($record['type']); @endphp
                <tr wire:key="row-{{ $record['workspace_id'] }}"
                    x-data @click="if (! $event.target.closest('a')) Livewire.navigate(@js($record['url']))"
                    class="cursor-pointer transition-colors hover:bg-slate-50">
                    <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ $offset + $loop->iteration }}</td>

                    @if ($showStudent)
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                    {{ \App\Support\Honorifics::initials($record['student_name']) }}
                                    @if ($avatarFor($record))
                                        <img src="{{ $avatarFor($record) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ $record['url'] }}" wire:navigate
                                       class="block max-w-[220px] truncate rounded font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">{{ $record['student_name'] }}</a>
                                    <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $record['student_number'] }}</p>
                                </div>
                            </div>
                        </td>
                    @endif

                    @if ($docColumn)
                        <td class="px-4 py-3">
                            @if ($showStudent)
                                <p class="max-w-[260px] truncate text-slate-700" title="{{ $record['type'] }}">{{ $record['type'] }}</p>
                            @else
                                <a href="{{ $record['url'] }}" wire:navigate
                                   class="block max-w-[260px] truncate rounded font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}" title="{{ $record['type'] }}">{{ $record['type'] }}</a>
                            @endif
                            @if ($record['issuer'] || $record['deleted_by_student'])
                                <p class="mt-0.5 max-w-[260px] truncate text-xs text-slate-500">
                                    @if ($record['issuer'])Issued by {{ $record['issuer'] }}@endif
                                    @if ($record['deleted_by_student'])<span class="text-rose-600">{{ $record['issuer'] ? '· ' : '' }}Deleted by student</span>@endif
                                </p>
                            @endif
                            @if ($record['search_snippet'])
                                <p class="mt-0.5 max-w-[320px] text-xs leading-snug text-slate-500"><span class="font-medium">{{ $record['search_snippet_label'] }}:</span> {!! $record['search_snippet'] !!}</p>
                            @endif
                        </td>
                    @endif

                    <td class="whitespace-nowrap px-4 py-3 font-mono text-[13px] tabular-nums text-slate-700">{{ $record['transaction_label'] }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                        <span class="inline-flex items-center gap-1.5"><i class="bx {{ $record['paperless'] ? 'bx-globe' : 'bx-printer' }} text-base text-slate-400" aria-hidden="true"></i>{{ $record['paperless'] ? 'Online' : 'Paper' }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-700">{{ $record['date_label'] }}</td>
                    <td class="whitespace-nowrap px-4 py-3"><x-status-badge :status="$record['state']" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Phones: cards, as on Workflow review --}}
<ul class="grid gap-3 md:hidden">
    @foreach ($records as $record)
        <li wire:key="mrow-{{ $record['workspace_id'] }}">
            <a href="{{ $record['url'] }}" wire:navigate
               class="flex h-full w-full flex-col rounded-2xl border border-slate-200 bg-white p-4 text-left transition-colors active:bg-slate-50 {{ $focus }}">
                <span class="flex w-full items-start gap-3">
                    @if ($showStudent)
                        <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                            {{ \App\Support\Honorifics::initials($record['student_name']) }}
                            @if ($avatarFor($record))
                                <img src="{{ $avatarFor($record) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                            @endif
                        </span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[15px] font-semibold leading-snug text-slate-900">{{ $showStudent ? $record['student_name'] : $record['type'] }}</span>
                        <span class="mt-0.5 block truncate text-xs tabular-nums text-slate-500">{{ $showStudent ? $record['student_number'] : $record['transaction_label'] }}</span>
                    </span>
                    <x-status-badge :status="$record['state']" class="shrink-0" />
                </span>

                @if ($showStudent && $showDocType)
                    <span class="mt-3 block text-sm leading-snug text-slate-700">{{ $record['type'] }}</span>
                @endif
                @if ($record['issuer'] || $record['deleted_by_student'])
                    <span class="mt-1 block truncate text-xs text-slate-500">
                        @if ($record['issuer'])Issued by {{ $record['issuer'] }}@endif
                        @if ($record['deleted_by_student'])<span class="text-rose-600">{{ $record['issuer'] ? '· ' : '' }}Deleted by student</span>@endif
                    </span>
                @endif
                @if ($record['search_snippet'])
                    <span class="mt-1 block text-xs leading-snug text-slate-500"><span class="font-medium">{{ $record['search_snippet_label'] }}:</span> {!! $record['search_snippet'] !!}</span>
                @endif

                <span class="mt-3 flex w-full items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span class="min-w-0 truncate tabular-nums">#{{ $offset + $loop->iteration }} · {{ $showStudent ? $record['transaction_label'] . ' · ' : '' }}{{ $record['paperless'] ? 'Online' : 'Paper' }}</span>
                    <span class="shrink-0 tabular-nums">{{ $record['date_label'] }}</span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
