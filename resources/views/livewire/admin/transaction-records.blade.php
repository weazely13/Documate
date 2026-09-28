<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $mainTabs[$activeTab] }}</h1>
        <p class="mb-3 text-sm text-slate-500">
            @if($activeTab === 'transactions')
                Complete history of all document transactions.
            @else
                VPSD office logbooks uploaded from Excel exports.
            @endif
        </p>

        {{-- ============ NAVIGATION BAR: Transaction Records / Logbooks ============ --}}
        <div class="mb-5 inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
            @foreach($mainTabs as $key => $label)
                <button type="button" wire:click="setActiveTab('{{ $key }}')"
                        class="flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    <i class='bx {{ $key === 'transactions' ? 'bx-list-ul' : 'bx-book-content' }}'></i>
                    {{ $label }}
                </button>
            @endforeach
        </div>

    @if($activeTab === 'logbooks')
        {{-- ============ LOGBOOKS TAB ============ --}}
        <livewire:admin.logbook-manager />
    @else
    {{-- ============ TRANSACTION RECORDS TAB (unchanged) ============ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        {{-- Toolbar --}}
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full max-w-sm">
                <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search name, ID, document type, AI review notes…"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-[#2A57B4] focus:bg-white focus:ring-4 focus:ring-[#2A57B4]/10">
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <select wire:model.live="sortBy"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 outline-none transition focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- View toggle + status tabs on their own row for clarity --}}
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                @foreach($statusTabs as $tab)
                    <button type="button"
                            wire:click="setStatusFilter('{{ $tab }}')"
                            class="rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ $statusFilter === $tab ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' }}">
                        {{ $tab }}
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                <button type="button" wire:click="setViewMode('table')"
                        class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-semibold transition {{ $viewMode === 'table' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    <i class='bx bx-list-ul'></i> Table
                </button>
                <button type="button" wire:click="setViewMode('folder')"
                        class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-semibold transition {{ $viewMode === 'folder' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    <i class='bx bx-folder'></i> Folder
                </button>
            </div>
        </div>

        {{-- ============ TABLE VIEW ============ --}}
        @if($viewMode === 'table')
            <p class="mb-3 text-xs text-slate-400">
                {{ $pendingCount }} pending, {{ $forAppointmentCount }} for appointment, {{ $completedCount }} completed.
            </p>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-slate-50">
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Transaction ID</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Student ID</th>
                                <th class="px-4 py-3">Purpose</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Session</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($records as $record)
                                @php
                                    $statusClass = match ($record['status']) {
                                        'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        'For Appointment' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        'Waiting Upload' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                        'Processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                        'Missed' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                        default => 'bg-orange-50 text-orange-700 ring-orange-200',
                                    };
                                @endphp
                                <tr wire:key="transaction-record-{{ $record['workspace_id'] }}"
                                    @click="Livewire.navigate('{{ route('admin.transactions.show', $record['workspace_id']) }}')"
                                    class="group cursor-pointer text-slate-700 transition hover:bg-slate-50">
                                    <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-500">#{{ $record['transaction_id'] }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="max-w-[220px] truncate font-semibold text-slate-900 group-hover:text-[#2A57B4]" title="{{ $record['student_name'] }}">
                                            {{ $record['student_name'] }}
                                        </div>
                                        @if($record['search_snippet'])
                                            <p class="mt-1 max-w-[280px] text-xs italic leading-snug text-slate-400">
                                                <span class="font-semibold not-italic text-slate-500">{{ $record['search_snippet_label'] }}:</span>
                                                {!! $record['search_snippet'] !!}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-500">{{ $record['student_number'] }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="max-w-[200px] truncate" title="{{ $record['type'] }}">{{ $record['type'] }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-500">{{ $record['date_label'] }}</td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-500">{{ $record['appointment'] }}</td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                            {{ $record['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-14 text-center">
                                        <i class='bx bx-search-alt text-3xl text-slate-300'></i>
                                        <p class="mt-2 text-sm text-slate-500">No transaction records matched your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- ============ FOLDER VIEW ============ --}}
        @else
            @if($selectedFolder === null)
                {{-- Folder tiles grouped by document type --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($folders as $folder)
                        <button type="button" wire:click="openFolder('{{ $folder['type'] }}')"
                                class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#2A57B4]/40 hover:shadow-lg">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                                @if($folder['preview_url'])
                                    <img src="{{ $folder['preview_url'] }}" alt="{{ $folder['type'] }}" class="h-full w-full object-cover">
                                @else
                                    <i class='bx bxs-folder text-3xl text-slate-300'></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-bold text-slate-900 group-hover:text-[#2A57B4]">{{ $folder['type'] }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $folder['count'] }} transaction{{ $folder['count'] === 1 ? '' : 's' }}</p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-2 text-xs font-medium">
                                    <span class="inline-flex items-center gap-1 text-orange-600">
                                        <i class='bx bx-time-five'></i> {{ $folder['pending_count'] }} pending
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-amber-600">
                                        <i class='bx bx-calendar'></i> {{ $folder['for_appointment_count'] }} appointment
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-emerald-600">
                                        <i class='bx bx-check-circle'></i> {{ $folder['completed_count'] }} completed
                                    </span>
                                </p>
                                @if($folder['search_snippet'])
                                    <p class="mt-1 text-xs italic leading-snug text-slate-400">
                                        <span class="font-semibold not-italic text-slate-500">{{ $folder['search_snippet_label'] }}:</span>
                                        {!! $folder['search_snippet'] !!}
                                    </p>
                                @endif
                            </div>
                            <i class='bx bx-chevron-right text-xl text-slate-300 transition group-hover:text-[#2A57B4]'></i>
                        </button>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 px-6 py-14 text-center">
                            <i class='bx bx-folder-open text-3xl text-slate-300'></i>
                            <p class="mt-2 text-sm text-slate-500">No document types matched your filters.</p>
                        </div>
                    @endforelse
                </div>
            @else
                {{-- Inside a folder: show its transactions as rendered document cards --}}
                <div class="mb-4 flex items-center justify-between">
                    <button type="button" wire:click="closeFolder"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-[#2A57B4]">
                        <i class='bx bx-arrow-back'></i> Back to folders
                    </button>
                    <h3 class="text-sm font-bold text-slate-800">{{ $selectedFolder }} <span class="font-normal text-slate-400">({{ $totalCount }})</span></h3>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($records as $record)
                        @php
                            $statusBadgeClass = match ($record['status']) {
                                'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'For Appointment' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'Waiting Upload' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                'Processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                'Missed' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                default => 'bg-orange-50 text-orange-700 ring-orange-200',
                            };
                        @endphp
                        <a href="{{ route('admin.transactions.show', $record['workspace_id']) }}" wire:navigate
                           wire:key="folder-item-{{ $record['workspace_id'] }}"
                           class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                            <div class="border-b border-slate-100 bg-slate-50 p-3">
                                @include('livewire.partials.document-preview-card', ['workspace' => $record['workspace']])
                            </div>
                            <div class="space-y-2 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900 group-hover:text-[#2A57B4]">{{ $record['student_name'] }}</p>
                                        <p class="text-xs text-slate-400">#{{ $record['transaction_id'] }} &middot; {{ $record['student_number'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusBadgeClass }}">
                                        {{ $record['status'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400">{{ $record['date_label'] }} &middot; {{ $record['appointment'] }}</p>
                                @if($record['search_snippet'])
                                    <p class="text-xs italic leading-snug text-slate-400">
                                        <span class="font-semibold not-italic text-slate-500">{{ $record['search_snippet_label'] }}:</span>
                                        {!! $record['search_snippet'] !!}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 px-6 py-14 text-center text-sm text-slate-500">
                            No transactions in this folder.
                        </div>
                    @endforelse
                </div>
            @endif
        @endif

        {{-- Pagination --}}
        @if(($viewMode === 'table' && $totalCount > 0) || ($viewMode === 'folder' && $selectedFolder !== null && $totalCount > 0))
            <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-xs text-slate-400">
                    Showing {{ (($currentPage - 1) * 30) + 1 }}–{{ min($currentPage * 30, $totalCount) }} of {{ $totalCount }}
                </p>
                <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 text-sm">
                    <button type="button" wire:click="previousPage" @disabled($currentPage === 1)
                            class="px-4 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        <i class='bx bx-chevron-left'></i>
                    </button>
                    <div class="flex items-center border-x border-slate-200 bg-white px-4 text-slate-600">
                        {{ $currentPage }} / {{ $totalPages }}
                    </div>
                    <button type="button" wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                            class="px-4 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        <i class='bx bx-chevron-right'></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
    @endif
</div>