{{-- Nothing in the transaction archive matches the search and filters. --}}
<div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center">
    <i class="bx bx-archive text-4xl text-slate-300" aria-hidden="true"></i>
    <p class="mt-2 text-sm font-medium text-slate-700">Nothing in the archive matches.</p>
    @if ($activeFilters || trim($search) !== '')
        <button type="button" wire:click="resetFilters" class="mt-2 text-sm font-medium text-[#2A57B4] hover:text-[#1E4090]">Reset filters</button>
    @endif
</div>
