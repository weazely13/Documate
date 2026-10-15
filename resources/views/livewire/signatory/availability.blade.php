@php $input = 'mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100'; @endphp
<div class="mx-auto max-w-2xl space-y-5">
    <div>
        <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Signing Inbox</a>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">My Availability</h2>
        <p class="mt-0.5 text-sm text-slate-500">Students who need counselling are given your earliest open slot. If you set none, they are told to come on the next Wednesday.</p>
    </div>

    @if (session('avail_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('avail_message') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-slate-700">Date</label>
                <input type="date" wire:model="date" min="{{ now()->toDateString() }}" class="{{ $input }}">
                @error('date') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">From</label>
                <input type="time" wire:model="start" class="{{ $input }}">
                @error('start') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Until</label>
                <input type="time" wire:model="end" class="{{ $input }}">
                @error('end') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>
        <button type="button" wire:click="add" wire:loading.attr="disabled"
                class="mt-4 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50">Add availability</button>
    </section>

    <section class="space-y-2">
        <h3 class="text-sm font-semibold text-slate-900">Upcoming slots ({{ $slots->count() }})</h3>
        @forelse ($slots as $s)
            <div wire:key="slot-{{ $s->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ $s->available_date->format('l, F j, Y') }}</p>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('g:i A') }} to {{ \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->format('g:i A') }}</p>
                </div>
                <button type="button" wire:click="remove({{ $s->id }})" wire:confirm="Remove this slot? Students already told to come keep their schedule."
                        class="text-xs font-semibold text-rose-600 hover:underline">Remove</button>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No slots set. Students will be told to come next Wednesday.</div>
        @endforelse
    </section>
</div>