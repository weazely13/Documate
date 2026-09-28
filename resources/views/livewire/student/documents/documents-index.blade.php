<div class="mx-auto max-w-4xl space-y-6 px-3 py-2 sm:px-4">

    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Documents</h2>
            <p class="mt-0.5 text-xs text-slate-500">Track your submitted document transactions</p>
        </div>
    </div>

    {{-- Main Tabs Navigation (mirrors Appointments) --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 sm:space-x-8" aria-label="Tabs">
            <button type="button" wire:click="$set('tab', 'pending')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm transition-all {{ $tab === 'pending' ? 'border-[#2A57B4] text-[#2A57B4] font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium' }}">
                <i class='bx bx-file text-lg'></i>
                <span>In Progress</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tab === 'pending' ? 'bg-blue-100 text-[#2A57B4]' : 'bg-slate-100 text-slate-600' }}">
                    {{ $this->pendingCount }}
                </span>
            </button>

            <button type="button" wire:click="$set('tab', 'completed')"
                class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm transition-all {{ $tab === 'completed' ? 'border-[#2A57B4] text-[#2A57B4] font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium' }}">
                <i class='bx bx-check-circle text-lg'></i>
                <span>Completed</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tab === 'completed' ? 'bg-blue-100 text-[#2A57B4]' : 'bg-slate-100 text-slate-600' }}">
                    {{ $this->completedCount }}
                </span>
            </button>
        </nav>
    </div>

    {{-- Search --}}
    <div class="relative w-full">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search documents by name..."
            class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-xs sm:text-sm text-slate-700 placeholder-slate-400 shadow-sm focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4]" />
        <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400'></i>
    </div>

    {{-- Toolbar: bulk select + sort (mirrors Appointments toolbar) --}}
    <div class="flex flex-col gap-3 rounded-xl bg-slate-50/80 p-3 sm:flex-row sm:items-center sm:justify-between border border-slate-100">
        <div class="flex items-center gap-2.5">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" @checked($selectAll) wire:click="toggleSelectAll"
                    class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                <span class="text-xs font-semibold text-slate-500">
                    {{ count($selected) > 0 ? count($selected) . ' selected' : 'Select all' }}
                </span>
            </label>

            @if(count($selected) > 0)
                <button type="button" wire:click="confirmBulkDelete"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-100">
                    <i class='bx bx-trash text-sm'></i>
                    <span>Delete</span>
                </button>
            @endif
        </div>

        <select wire:model.live="sort"
            class="appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-8 text-xs text-slate-600 focus:border-[#2A57B4] focus:outline-none">
            <option value="desc">Newest First</option>
            <option value="asc">Oldest First</option>
        </select>
    </div>

    {{-- Cards --}}
    <div class="space-y-3">
        @forelse($workspaces as $workspace)
            @include('livewire.student.documents.partials.document-card', ['workspace' => $workspace])
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                <p class="text-sm font-medium text-slate-700">No {{ $tab }} documents</p>
                <p class="mt-0.5 text-xs text-slate-400">Documents you submit will show up here.</p>
            </div>
        @endforelse
    </div>

    {{-- Single delete modal --}}
    <template x-teleport="body">
        <div x-show="$wire.deletingId" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px);">
            <div class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <i class='bx bx-trash text-xl'></i>
                </div>
                <h3 class="text-center text-base font-semibold text-slate-900">Delete this document?</h3>
                <p class="mt-1 text-center text-xs text-slate-500">This operation cannot be reversed.</p>
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="cancelDelete" class="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" wire:click="deleteWorkspace" class="flex-1 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">Delete</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Bulk delete modal --}}
    <template x-teleport="body">
        <div x-show="$wire.confirmingBulkDelete" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px);">
            <div class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <i class='bx bx-trash text-xl'></i>
                </div>
                <h3 class="text-center text-base font-semibold text-slate-900">Delete {{ count($selected) }} documents?</h3>
                <p class="mt-1 text-center text-xs text-slate-500">This operation cannot be reversed.</p>
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="cancelBulkDelete" class="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" wire:click="bulkDelete" class="flex-1 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">Delete All</button>
                </div>
            </div>
        </div>
    </template>
</div>