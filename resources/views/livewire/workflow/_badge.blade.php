{{-- A transaction's status pill: the system-wide words and colours (see x-status-badge). --}}
@if (! empty($ws))
    <x-status-badge :workspace="$ws" class="shrink-0" />
@elseif ($status ?? null)
    <x-status-badge :status="$status" class="shrink-0" />
@else
    <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">-</span>
@endif
