<div class="mx-auto max-w-3xl space-y-5 sm:space-y-6 sm:px-4 sm:py-2">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">My documents</h2>
            <p class="mt-0.5 text-sm text-slate-500">Everything you have started, and anything the office sent you.</p>
        </div>
        <a href="{{ route('student.new-transaction') }}" wire:navigate
           class="inline-flex items-center justify-center rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#24499A]">
            New transaction
        </a>
    </div>

    {{-- Tabs + search --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <nav class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 sm:inline-grid sm:self-start" aria-label="Documents">
            @foreach ($tabs as $key => $label)
                @php $count = $this->counts[$key] ?? 0; @endphp
                <button type="button" wire:click="$set('tab', '{{ $key }}')" wire:key="tab-{{ $key }}"
                        @if ($tab === $key) aria-current="page" @endif
                        class="flex flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-2 text-[13px] leading-tight transition sm:flex-row sm:gap-2 sm:px-4 sm:text-sm
                            {{ $tab === $key ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'font-medium text-slate-600 hover:text-slate-900' }}">
                    <span class="whitespace-nowrap">{{ $label }}</span>
                    <span class="text-xs tabular-nums {{ $tab === $key ? 'text-[#2A57B4]' : ($key === 'issued' && $count ? 'font-semibold text-amber-700' : 'text-slate-500') }}">{{ $count }}</span>
                </button>
            @endforeach
        </nav>

        <div class="relative sm:w-64">
            <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400' aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search your documents" aria-label="Search your documents"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-base text-slate-700 placeholder-slate-400 focus:border-[#2A57B4] focus:outline-none focus:ring-1 focus:ring-[#2A57B4] sm:text-sm" />
        </div>
    </div>

    {{-- Cards --}}
    <div class="space-y-3">
        @forelse ($workspaces as $workspace)
            <div wire:key="doc-{{ $workspace->workspace_id }}">
                @include('livewire.student.documents.partials.document-card', [
                    'workspace' => $workspace,
                    'issuer' => \App\Support\Issuers::label($issuers[$workspace->workspace_id] ?? null),
                ])
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                @if (trim($search) !== '')
                    <p class="text-sm font-medium text-slate-700">No documents match "{{ $search }}"</p>
                @elseif ($tab === 'active')
                    <p class="text-sm font-medium text-slate-700">Nothing in progress</p>
                    <p class="mt-1 text-sm text-slate-500">
                        <a href="{{ route('student.new-transaction') }}" wire:navigate class="font-semibold text-[#2A57B4] hover:underline">Start a new transaction</a>
                        and it will show up here.
                    </p>
                @elseif ($tab === 'issued')
                    <p class="text-sm font-medium text-slate-700">Nothing issued to you</p>
                    <p class="mt-1 text-sm text-slate-500">Slips, advisements and other documents the VPSD office or a signatory issues to you show up here.</p>
                @else
                    <p class="text-sm font-medium text-slate-700">No finished documents yet</p>
                    <p class="mt-1 text-sm text-slate-500">Completed, rejected and voided documents are kept here.</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Delete confirmation --}}
    <template x-teleport="body">
        <div x-show="$wire.deletingId" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px);">
            <div class="w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                <h3 class="text-center text-base font-semibold text-slate-900">Delete this document?</h3>
                <p class="mt-1 text-center text-sm text-slate-500">This can't be undone. Any booked appointment for it is cancelled.</p>
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="cancelDelete" class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" wire:click="deleteWorkspace" class="flex-1 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">Delete</button>
                </div>
            </div>
        </div>
    </template>
</div>
