<div x-data="{ open: false }" class="relative" wire:poll.5s>
    <button @click="open = !open" @click.away="open = false" class="relative rounded-full p-2 hover:bg-slate-100">
        <i class='bx bx-bell text-xl text-slate-600'></i>
        @if($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
        class="absolute right-0 z-50 mt-2 w-80 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl transition-all duration-200 {{ $this->isExpanded ? 'max-h-[34rem]' : 'max-h-96' }}">

        <div class="sticky top-0 z-10 border-b border-slate-100 bg-white px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Notifications</p>
        </div>

        @if($this->todayNotifications->isEmpty() && $this->earlierNotifications->isEmpty())
            <p class="px-4 py-6 text-center text-sm text-slate-400">No notifications yet.</p>
        @else
            <!-- Today -->
            @if($this->todayNotifications->isNotEmpty())
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Today</p>

                @foreach($this->todayNotifications as $n)
                    @include('livewire.partials.notification-item', ['n' => $n])
                @endforeach

                @if($this->hasMoreToday)
                    <button wire:click="showMoreToday" @click.stop
                        class="w-full px-4 py-2 text-center text-xs font-semibold text-[#2A57B4] hover:bg-slate-50">
                        See more today notifications
                    </button>
                @endif
            @endif

            <!-- Earlier -->
            @if($this->earlierNotifications->isNotEmpty())
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400 border-t border-slate-100">Earlier</p>

                @foreach($this->earlierNotifications as $n)
                    @include('livewire.partials.notification-item', ['n' => $n])
                @endforeach

                @if($this->hasMoreEarlier)
                    <button wire:click="showAllPrevious" @click.stop
                        class="w-full px-4 py-2 text-center text-xs font-semibold text-[#2A57B4] hover:bg-slate-50">
                        See all previous notifications
                    </button>
                @endif
            @endif
        @endif
    </div>
</div>