{{-- A person's round avatar: their profile picture, or their initials on brand tint when there is none
     (or the picture fails to load). <x-dm.avatar :user="$u" name="Fallback Name" class="h-9 w-9 text-xs" /> --}}
@props(['user' => null, 'name' => null])
@php
    $label = trim((string) ($user?->full_name ?: $name));
    $initials = \App\Support\Honorifics::initials($label);   // skips a leading honorific (Dr. Maria Santos → MS)
    $src = $user?->profile_picture ? asset('storage/' . $user->profile_picture) : null;
@endphp
<span {{ $attributes->merge(['class' => 'relative grid shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] font-semibold uppercase text-[#1E4090]']) }} aria-hidden="true">
    {{ mb_strtoupper($initials) }}
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif
</span>
