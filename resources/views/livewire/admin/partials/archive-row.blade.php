{{-- One document in the transaction archive, as a table row (stacked on phones).
     Needs: $record, $showStudent (first column is the student, not the document), $tagFor, $tintFor, $focus. --}}
@php [$cBg, $cInk] = $tintFor($record['type']); $showDocType = $showDocType ?? false; @endphp
<li wire:key="row-{{ $record['workspace_id'] }}">
    <a href="{{ $record['url'] }}" wire:navigate
       class="block px-3.5 py-2.5 transition hover:bg-slate-50 md:grid md:grid-cols-[minmax(0,1fr)_10rem_6rem_7rem_10rem] md:items-center md:gap-4 {{ $focus }}">
        <span class="flex min-w-0 items-center gap-2.5">
            @if ($showStudent)
                <x-dm.avatar :user="$record['user']" :name="$record['student_name']" class="h-7 w-7 text-[10px]" />
            @else
                <i class="bx {{ $record['paperless'] ? 'bx-file' : 'bx-printer' }} text-lg {{ $cInk }}" aria-hidden="true"></i>
            @endif
            <span class="min-w-0">
                <span class="block truncate text-sm text-slate-900">{{ $showStudent ? $record['student_name'] : $record['type'] }}</span>
                @if ($showStudent || $record['issuer'] || $record['deleted_by_student'])
                    <span class="block truncate text-xs text-slate-500">
                        @if ($showStudent)<span class="tabular-nums">{{ $record['student_number'] }}</span>@endif
                        @if ($showDocType)<span class="{{ $cInk }}"> · {{ $record['type'] }}</span>@endif
                        @if ($record['issuer']){{ $showStudent ? '· ' : '' }}Issued by {{ $record['issuer'] }}@endif
                        @if ($record['deleted_by_student'])<span class="text-rose-600"> · Deleted by student</span>@endif
                    </span>
                @endif
                @if ($record['search_snippet'])
                    <span class="mt-0.5 block text-xs leading-snug text-slate-500"><span class="font-medium">{{ $record['search_snippet_label'] }}:</span> {!! $record['search_snippet'] !!}</span>
                @endif
            </span>
        </span>
        <span class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 pl-[2.375rem] text-xs text-slate-500 md:contents md:pl-0">
            <span class="truncate font-mono tabular-nums text-slate-700 md:text-[13px]">{{ $record['transaction_label'] }}</span>
            <span class="md:text-sm">{{ $record['paperless'] ? 'Online' : 'Paper' }}</span>
            <span class="tabular-nums md:text-sm">{{ $record['date_label'] }}</span>
            <span><span class="inline-block rounded-md px-1.5 py-0.5 text-[11px] font-medium {{ $tagFor($record) }}">{{ $record['status'] }}</span></span>
        </span>
    </a>
</li>
