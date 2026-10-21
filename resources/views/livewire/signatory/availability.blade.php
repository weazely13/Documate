@php $input = 'mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100 sm:py-2.5 sm:text-sm'; @endphp

<div class="mx-auto max-w-2xl space-y-5">
    <div>
        <a href="{{ route('signatory.inbox') }}" wire:navigate
           class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
            <i class='bx bx-chevron-left text-xl'></i> Signing Inbox
        </a>
        <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">My Availability</h2>
        <p class="mt-1 text-sm text-slate-500">Students who need counselling are given your earliest open slot. If you set none, they are told to come on the next Wednesday.</p>
    </div>

    @if (session('avail_message'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('avail_message') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="text-sm font-semibold text-slate-900">Add a time slot</h3>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="col-span-2 sm:col-span-1">
                <label for="avail-date" class="text-sm font-medium text-slate-700">Date</label>
                <input id="avail-date" type="date" wire:model="date" min="{{ now()->toDateString() }}" class="{{ $input }}">
                @error('date') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="avail-start" class="text-sm font-medium text-slate-700">From</label>
                <input id="avail-start" type="time" wire:model="start" class="{{ $input }}">
                @error('start') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="avail-end" class="text-sm font-medium text-slate-700">Until</label>
                <input id="avail-end" type="time" wire:model="end" class="{{ $input }}">
                @error('end') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <button type="button" wire:click="add" wire:loading.attr="disabled"
                class="mt-4 inline-flex min-h-[44px] w-full items-center justify-center rounded-xl bg-[#2A57B4] px-5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50 sm:w-auto">
            Add availability
        </button>
    </section>

    <section class="space-y-2">
        <h3 class="text-sm font-semibold text-slate-900">Upcoming slots <span class="font-normal text-slate-400">({{ $slots->count() }})</span></h3>
        @forelse ($slots as $s)
            <div wire:key="slot-{{ $s->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-xl text-[#2A57B4]"><i class='bx bx-calendar'></i></span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800">{{ $s->available_date->format('D, M j, Y') }}</p>
                        <p class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('g:i A') }} to {{ \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->format('g:i A') }}</p>
                    </div>
                </div>
                <button type="button" wire:click="remove({{ $s->id }})" wire:confirm="Remove this slot? Students already told to come keep their schedule."
                        class="inline-flex min-h-[44px] shrink-0 items-center rounded-lg px-3 text-sm font-semibold text-rose-600 hover:bg-rose-50">Remove</button>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">No slots set. Students will be told to come next Wednesday.</div>
        @endforelse
    </section>
</div>