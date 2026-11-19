{{-- The system's one status pill, in the words and colours Reports uses (App\Support\StatusStyle).
     <x-status-badge :workspace="$ws" />                  a request (shows Signing once the signers are asked); best when the workspace is at hand
     <x-status-badge :status="$ws->workflow_status" />   a request: the enum, its value ('signing' too), or a paper form's 'pending' / 'completed'
     <x-status-badge :clearance="$row->status ?? ''" />  a clearance status, with its icon ('' = Untagged)
     <x-status-badge :paper="$ws->isCompleted()" />       a paper form: Pending or Completed
     <x-status-badge :appointment="$a->status" />         an appointment
     Pass :icon="false" to drop the clearance icon; extra classes merge onto the pill. --}}
@props(['status' => null, 'workspace' => null, 'clearance' => false, 'paper' => null, 'appointment' => null, 'icon' => true])
@php
    // @props swaps a null prop for its default, so pass a status that may be null as `?? ''`: '' reads as Untagged.
    $s = match (true) {
        $clearance !== false => \App\Support\StatusStyle::clearance($clearance),
        $paper !== null => \App\Support\StatusStyle::paper((bool) $paper),
        $appointment !== null => \App\Support\StatusStyle::appointment($appointment),
        $workspace !== null => \App\Support\StatusStyle::transaction(\App\Support\StatusStyle::state($workspace)),
        default => \App\Support\StatusStyle::transaction($status),
    };
@endphp
<span {{ $attributes->merge(['class' => \App\Support\StatusStyle::classes($s['tone'])]) }}>@if ($icon && ! empty($s['icon']))<i class="bx {{ $s['icon'] }}" aria-hidden="true"></i>@endif{{ $s['label'] }}</span>
