{{-- The processing period as a status pill: one word, no day count (WaitingTime::PILLS).
     <x-dm.wait-pill :w="$row->waiting" />  ($w = App\Support\WaitingTime::describe(...)) --}}
@props(['w'])
<span {{ $attributes->merge(['class' => \App\Support\StatusStyle::BADGE . ' ' . \App\Support\WaitingTime::pillClasses($w)]) }}>
    {{ \App\Support\WaitingTime::pillWord($w) }}
</span>
