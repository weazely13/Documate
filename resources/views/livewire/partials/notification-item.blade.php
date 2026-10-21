@php
    $icon      = $n->data['icon'] ?? 'bx-bell';
    $color     = $n->data['color'] ?? 'text-slate-500 bg-slate-100';
    $avatar    = $n->data['actor_avatar'] ?? null;
    $actorName = $n->data['actor_name'] ?? null;
    $title     = $n->data['title'] ?? null;
    $message   = $n->data['message'] ?? 'Notification';
    $unread    = is_null($n->read_at);
@endphp

<button wire:click="openNotification('{{ $n->id }}')"
    class="group flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 {{ $unread ? 'bg-blue-50/60' : 'bg-white' }}">

    {{-- Avatar / status icon --}}
    <div class="relative h-10 w-10 shrink-0">
        @if($avatar)
            <img src="{{ $avatar }}" alt="{{ $actorName }}"
                class="h-10 w-10 rounded-full object-cover ring-2 ring-white">
            <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white {{ $color }}">
                <i class="bx {{ $icon }} text-[11px]"></i>
            </span>
        @else
            <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $color }}">
                <i class="bx {{ $icon }} text-xl"></i>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        @if($title)
            <p class="truncate text-sm font-bold text-slate-900">{{ $title }}</p>
            <p class="mt-0.5 line-clamp-2 text-[13px] leading-snug text-slate-600">
                @if($actorName && ($n->data['name_prefix'] ?? false))
                    <span class="font-bold text-slate-900">{{ $actorName }}</span>
                @endif
                {{ $message }}
            </p>
        @else
            <p class="line-clamp-3 text-[13px] leading-snug text-slate-600">
                @if($actorName)<span class="font-bold text-slate-900">{{ $actorName }}</span>@endif
                {{ $message }}
            </p>
        @endif

        <p class="mt-1 flex items-center gap-1 text-xs {{ $unread ? 'font-semibold text-[#2A57B4]' : 'text-slate-400' }}">
            <i class="bx bx-time-five text-[13px]"></i>
            {{ $n->created_at->diffForHumans() }}
        </p>
    </div>

    {{-- Unread dot --}}
    @if($unread)
        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-[#2A57B4]"></span>
    @endif
</button>