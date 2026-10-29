{{-- Profile photo with initials underneath; if the photo is missing or fails to load, the initials show.
     <x-user-avatar :src="$user->avatarUrl()" :name="$fullName" class="h-9 w-9 text-xs bg-[#2A57B4]" /> --}}
@props(['src' => null, 'name' => ''])

@php
    $initials = \Illuminate\Support\Str::upper(
        collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('')
    ) ?: '?';
@endphp

<span {{ $attributes->class('relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold text-white') }}>
    {{ $slot->isEmpty() ? $initials : $slot }}
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" loading="lazy" onerror="this.remove()"
             class="absolute inset-0 h-full w-full object-cover">
    @endif
</span>
