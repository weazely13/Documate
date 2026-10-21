{{-- Shared activity timeline. Usage: @include('livewire.signatory._activity', ['audit' => $audit, 'prefix' => 'ia']) --}}
<aside x-data="{ open: window.matchMedia('(min-width: 1024px)').matches }"
       class="h-fit rounded-2xl border border-slate-200 bg-white shadow-sm">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex min-h-[56px] w-full items-center justify-between gap-3 p-4 text-left sm:p-5 lg:pointer-events-none">
        <span>
            <span class="block text-sm font-semibold text-slate-900">Activity</span>
            <span class="block text-xs text-slate-400">Newest first &middot; {{ count($audit) }} {{ count($audit) === 1 ? 'event' : 'events' }}</span>
        </span>
        <i class='bx bx-chevron-down text-2xl text-slate-400 transition lg:hidden' :class="open && 'rotate-180'"></i>
    </button>

    <div x-show="open" x-cloak class="border-t border-slate-100 p-4 sm:p-5">
        <div class="relative ml-1 space-y-5 border-l border-slate-200 pl-5">
            @foreach ($audit as $i => $item)
                <div class="relative" wire:key="{{ $prefix }}-{{ $i }}">
                    <span class="absolute -left-[25px] top-1.5 h-2 w-2 rounded-full bg-[#2A57B4]/60 ring-4 ring-white"></span>
                    <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                    <p class="text-xs text-slate-500">{{ $item['who'] }}</p>
                    <p class="text-xs text-slate-400">{{ $item['at']->format('M j, Y g:i A') }}</p>
                    @if ($item['note']) <p class="mt-1 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600">{{ $item['note'] }}</p> @endif
                </div>
            @endforeach
        </div>
    </div>
</aside>