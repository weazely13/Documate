{{-- The sender's avatar, as Workflow review shows a student: their photo laid over their initials (if the photo is
     missing or fails to load, the initials show). $u the user (or null), $label their name, $size the size classes. --}}
@php $src = $u?->avatarUrl(); @endphp
<span class="relative grid {{ $size }} shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] font-semibold text-[#1E4090]" aria-hidden="true">
    {{ \App\Support\Honorifics::initials($label) }}
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif
</span>
