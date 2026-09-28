@php
    $icon = $n->data['icon'] ?? 'bx-bell';
    $color = $n->data['color'] ?? 'text-slate-500 bg-slate-100';
    $avatar = $n->data['actor_avatar'] ?? null;
    $actorName = $n->data['actor_name'] ?? null;
    $message = $n->data['message'] ?? 'Notification';
@endphp

<button wire:click="openNotification('{{ $n->id }}')"
    class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 {{ is_null($n->read_at) ? 'bg-slate-50/70' : 'bg-white' }}">

    <!-- Unread Indicator Dot -->
    <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full {{ is_null($n->read_at) ? 'bg-[#2A57B4]' : 'bg-transparent' }}"></span>

    <!-- Avatar / Status Icon -->
    <div class="relative h-9 w-9 shrink-0">
        @if($avatar)
            {{-- Profile picture is available: show it as the main circle,
                 with the status icon as a small colored badge in the corner. --}}
            <img src="{{ $avatar }}" alt="{{ $actorName }}"
                class="h-9 w-9 rounded-full object-cover ring-2 ring-white">
            <span class="absolute -bottom-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full ring-2 ring-white {{ $color }}">
                <i class="bx {{ $icon }} text-[10px]"></i>
            </span>
        @else
            {{-- No human actor (system notification): fall back to a plain status icon circle. --}}
            <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $color }}">
                <i class="bx {{ $icon }} text-lg"></i>
            </div>
        @endif
    </div>

    <!-- Content -->
    <div class="min-w-0 flex-1">
        <p class="text-sm text-slate-700 leading-snug">
            @if($actorName)
                <span class="font-bold text-slate-900">{{ $actorName }}</span>
            @endif
            {{ $message }}
        </p>
        <p class="mt-1 text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</p>
    </div>
</button>