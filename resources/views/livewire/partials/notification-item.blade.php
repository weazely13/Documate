@php
    use App\Livewire\NotificationBell;
    use Illuminate\Support\Str;

    $icon      = $n->data['icon'] ?? 'bx-bell';
    $color     = $n->data['color'] ?? 'text-slate-500 bg-slate-100';
    $avatar    = NotificationBell::avatarUrl($n->data['actor_avatar'] ?? null);
    $actorName = $n->data['actor_name'] ?? null;
    $title     = $n->data['title'] ?? null;
    $message   = $n->data['message'] ?? 'Notification';
    $namePrefix = (bool) ($n->data['name_prefix'] ?? false);

    // Older clearance notifications saved a whole organization record instead of its name; show just the name.
    $tidy = fn ($text) => is_string($text)
        ? preg_replace_callback('/\{"id":\d+,"name":"([^"]*)"[^{}]*\}/', fn ($m) => $m[1], $text)
        : $text;
    $title   = $tidy($title);
    $message = $tidy($message);
    $unread    = is_null($n->read_at);

    $category  = NotificationBell::categoryOf($n);
    [$catLabel, $catIcon] = NotificationBell::CATEGORIES[$category] ?? ['General', 'bx-bell'];

    // Like the reference: a short headline, and longer text (replies, reasons) in a quoted card underneath.
    if ($title) {
        $headline = $title;
        $quoteIt  = ! $namePrefix && Str::length($message) > 70;
    } else {
        $headline = $message;
        $quoteIt  = false;
        if (Str::length($message) > 90 && Str::contains($message, ': ')) {
            [$headline, $message, $quoteIt] = [Str::before($message, ': ') . '.', Str::after($message, ': '), true];
        }
    }
@endphp

<button wire:click="openNotification('{{ $n->id }}')" wire:key="n-{{ $n->id }}"
    class="group relative flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-slate-50 {{ $unread ? 'bg-blue-50/50' : '' }}">

    {{-- Avatar: photo (with a small status badge) over the status icon, so a missing photo falls back to the icon --}}
    <span class="relative mt-0.5 h-10 w-10 shrink-0">
        @if($unread)
            <span class="absolute -left-1 -top-1 z-20 h-2.5 w-2.5 rounded-full bg-[#2A57B4] ring-2 ring-white" aria-label="Unread"></span>
        @endif
        <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full {{ $color }}">
            <i class="bx {{ $icon }} text-xl"></i>
            @if($avatar)
                <img src="{{ $avatar }}" alt="" loading="lazy" onerror="this.parentElement.nextElementSibling?.remove(); this.remove()"
                     class="absolute inset-0 h-10 w-10 rounded-full object-cover ring-1 ring-slate-200">
            @endif
        </span>
        @if($avatar)
            <span class="absolute -bottom-1 -right-1 z-10 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white {{ $color }}">
                <i class="bx {{ $icon }} text-[11px]"></i>
            </span>
        @endif
    </span>

    <span class="min-w-0 flex-1">
        {{-- Headline --}}
        <span class="block text-[13px] leading-snug {{ $title ? 'font-semibold text-slate-900' : 'text-slate-600' }}">
            @if(! $title && $actorName)<span class="font-semibold text-slate-900">{{ $actorName }}</span>@endif
            {{ $headline }}
        </span>

        {{-- Supporting line for titled notifications, unless it reads better as a quote --}}
        @if($title && ! $quoteIt)
            <span class="mt-0.5 block line-clamp-2 text-[13px] leading-snug text-slate-600">
                @if($namePrefix && $actorName)<span class="font-semibold text-slate-900">{{ $actorName }}</span>@endif
                {{ $message }}
            </span>
        @endif

        {{-- Meta: time • category chip --}}
        <span class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-400">
            <span class="{{ $unread ? 'font-medium text-[#2A57B4]' : '' }}">{{ $n->created_at->diffForHumans() }}</span>
            <span aria-hidden="true">•</span>
            <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-medium text-slate-600">
                <i class="bx {{ $catIcon }} text-[#2A57B4]"></i>{{ $catLabel }}
            </span>
        </span>

        @if($quoteIt)
            <span class="mt-2 block rounded-lg border border-slate-200 bg-white px-3 py-2 text-[13px] leading-snug text-slate-700 shadow-sm group-hover:border-slate-300">
                <span class="line-clamp-2">{{ $message }}</span>
            </span>
        @endif
    </span>
</button>
