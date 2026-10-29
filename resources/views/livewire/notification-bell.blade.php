<div x-data="{ open: false }" class="relative" wire:poll.5s>
    <button @click="open = !open" @click.away="open = false" :aria-expanded="open" aria-label="Notifications"
            class="relative rounded-full p-2 transition hover:bg-slate-100" :class="open && 'bg-blue-50 text-[#2A57B4]'">
        <i class='bx bx-bell text-xl text-slate-600' :class="open && '!text-[#2A57B4]'"></i>
        @if($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute right-0 z-50 mt-2 flex w-[26rem] max-w-[calc(100vw-1.5rem)] origin-top-right flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 {{ $this->isExpanded ? 'max-h-[36rem]' : 'max-h-[30rem]' }}">

        {{-- Header + filter pills --}}
        <div class="shrink-0 border-b border-slate-100 bg-white px-4 pb-3 pt-4">
            <div class="flex items-center justify-between gap-3">
                <p class="text-base font-semibold text-slate-900">Notifications</p>
                @if($this->unreadCount > 0)
                    <button type="button" wire:click="markAllRead" @click.stop
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 text-xs font-semibold text-[#2A57B4] hover:bg-blue-50">
                        <i class='bx bx-check-double text-sm'></i>Mark all as read
                    </button>
                @endif
            </div>

            <div class="-mx-4 mt-3 flex gap-1.5 overflow-x-auto px-4 [mask-image:linear-gradient(to_right,black_88%,transparent)] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="Filter notifications">
                @foreach($this->tabs as $key => [$label, $count])
                    <button type="button" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                            wire:click="setFilter('{{ $key }}')" @click.stop wire:key="ntab-{{ $key }}"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition
                                {{ $filter === $key
                                    ? 'border-[#2A57B4] bg-[#2A57B4] text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-[#2A57B4]/40 hover:text-[#2A57B4]' }}">
                        {{ $label }}
                        @if($count)
                            <span class="rounded-full px-1.5 text-[10px] tabular-nums {{ $filter === $key ? 'bg-white/25 text-white' : 'bg-rose-50 text-rose-600' }}">{{ $count }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2" wire:loading.class="opacity-60" wire:target="setFilter">
            @if($this->todayNotifications->isEmpty() && $this->earlierNotifications->isEmpty())
                <div class="px-4 py-12 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-[#2A57B4]">
                        <i class='bx {{ $filter === 'unread' ? 'bx-check-double' : 'bx-bell-off' }} text-2xl'></i>
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-700">{{ $filter === 'unread' ? "You're all caught up" : 'No notifications here yet' }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ $filter === 'unread' ? 'Nothing new since you last checked.' : 'Updates about your documents and requests show up here.' }}</p>
                </div>
            @else
                {{-- Today --}}
                @if($this->todayNotifications->isNotEmpty())
                    <p class="px-3 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Today</p>
                    <div class="space-y-0.5">
                        @foreach($this->todayNotifications as $n)
                            @include('livewire.partials.notification-item', ['n' => $n])
                        @endforeach
                    </div>
                    @if($this->hasMoreToday)
                        <button wire:click="showMoreToday" @click.stop
                                class="mt-1 w-full rounded-lg px-4 py-2 text-center text-xs font-semibold text-[#2A57B4] hover:bg-blue-50">
                            See more from today
                        </button>
                    @endif
                @endif

                {{-- Earlier --}}
                @if($this->earlierNotifications->isNotEmpty())
                    <p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Earlier</p>
                    <div class="space-y-0.5">
                        @foreach($this->earlierNotifications as $n)
                            @include('livewire.partials.notification-item', ['n' => $n])
                        @endforeach
                    </div>
                    @if($this->hasMoreEarlier)
                        <button wire:click="showAllPrevious" @click.stop
                                class="mt-1 w-full rounded-lg px-4 py-2 text-center text-xs font-semibold text-[#2A57B4] hover:bg-blue-50">
                            See all earlier notifications
                        </button>
                    @endif
                @endif
            @endif
        </div>
    </div>
</div>
