<div class="space-y-6">
    <div>
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Transaction Records</h1>
        <p class="mt-1 text-base text-slate-500">Database of all transactions history.</p>
    </div>

    <div class="rounded-[20px] border border-[#cfd7e4] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full max-w-[340px]">
                <i class='bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-400'></i>
                <input type="text"
                       wire:model.live="search"
                       placeholder="Search"
                       class="w-full rounded-[12px] border border-slate-300 bg-white py-2.5 pl-12 pr-4 text-sm text-slate-700 outline-none transition focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 p-1">
                    <button type="button"
                            wire:click="setViewMode('table')"
                            class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $viewMode === 'table' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Table View
                    </button>
                    <button type="button"
                            wire:click="setViewMode('folder')"
                            class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $viewMode === 'folder' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Folder View
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-5 flex flex-wrap items-center gap-2">
            @foreach($statusTabs as $tab)
                <button type="button"
                        wire:click="setStatusFilter('{{ $tab }}')"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $statusFilter === $tab ? 'bg-[#d9dde5] text-slate-900' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800' }}">
                    {{ $tab }}
                </button>
            @endforeach
        </div>

        @if($viewMode === 'table')
            <div class="overflow-hidden rounded-[16px] border border-[#aeb9c8] bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-900">
                            <tr class="border-b border-[#aeb9c8]">
                                <th class="px-4 py-3 text-left font-medium">Student</th>
                                <th class="px-4 py-3 text-left font-medium">Student ID</th>
                                <th class="px-4 py-3 text-left font-medium">Type</th>
                                <th class="px-4 py-3 text-left font-medium">Date</th>
                                <th class="px-4 py-3 text-left font-medium">Appointment</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-center font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c7d0dc] text-[15px] text-slate-800">
                            @forelse($records as $record)
                                @php
                                    $statusClass = match ($record['status']) {
                                        'Completed' => 'text-green-600',
                                        'For Appointment' => 'text-amber-500',
                                        'Waiting Upload' => 'text-blue-600',
                                        'Missed' => 'text-red-600',
                                        default => 'text-orange-500',
                                    };
                                @endphp
                                <tr class="hover:bg-[#f8fbff]" wire:key="transaction-record-{{ $record['workspace_id'] }}">
                                    <td class="px-4 py-3">
                                        <div class="max-w-[220px] truncate font-medium text-slate-900" title="{{ $record['student_name'] }}">
                                            {{ $record['student_name'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $record['student_number'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="max-w-[220px] truncate" title="{{ $record['type'] }}">
                                            {{ $record['type'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $record['date_label'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $record['appointment'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-semibold {{ $statusClass }}">{{ $record['status'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.transactions.show', $record['workspace_id']) }}"
                                           wire:navigate
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] border border-slate-300 bg-white text-slate-600 transition hover:border-[#2A57B4] hover:text-[#2A57B4]">
                                            <i class='bx bx-file-find text-lg'></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No transaction records matched your filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($records as $record)
                    @php
                        $statusBadgeClass = match ($record['status']) {
                            'Completed' => 'bg-green-50 text-green-600',
                            'For Appointment' => 'bg-amber-50 text-amber-600',
                            'Waiting Upload' => 'bg-blue-50 text-blue-600',
                            'Missed' => 'bg-red-50 text-red-600',
                            default => 'bg-orange-50 text-orange-500',
                        };
                    @endphp
                    <a href="{{ route('admin.transactions.show', $record['workspace_id']) }}"
                       wire:navigate
                       class="overflow-hidden rounded-[16px] border border-[#d4dceb] bg-white transition hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(15,23,42,0.10)]">
                        <div class="aspect-[1.35/1] border-b border-slate-200 bg-slate-50">
                            @if($record['preview_url'])
                                <img src="{{ $record['preview_url'] }}" alt="{{ $record['type'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-sm text-slate-400">Document Preview</div>
                            @endif
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="line-clamp-2 text-base font-bold text-slate-900">{{ $record['type'] }}</h3>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $record['student_name'] }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClass }}">{{ $record['status'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span>{{ $record['date_label'] }}</span>
                                <span>{{ $record['appointment'] }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-[16px] border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">
                        No transaction records matched your filters.
                    </div>
                @endforelse
            </div>
        @endif

        <div class="mt-6 flex items-center justify-center">
            <div class="inline-flex overflow-hidden rounded-[12px] border border-slate-300 text-sm">
                <button type="button"
                        wire:click="previousPage"
                        @disabled($currentPage === 1)
                        class="px-6 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                    &lt;Prev
                </button>

                <div class="border-x border-slate-300 bg-white px-8 py-2 text-slate-700">
                    {{ $currentPage }} out of {{ $totalPages }}
                </div>

                <button type="button"
                        wire:click="nextPage({{ $totalPages }})"
                        @disabled($currentPage === $totalPages)
                        class="px-6 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                    Next&gt;
                </button>
            </div>
        </div>
    </div>
</div>
