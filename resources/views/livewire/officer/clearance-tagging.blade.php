<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            {{ $pageHeading ?? 'Clearance Tagging' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $pageDescription ?? 'Tag and review clearance statuses for students in your organization' }}
        </p>
    </div>

    {{-- SEMESTER SELECTOR CARD --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-r from-white via-slate-50/50 to-white p-4 sm:p-5 shadow-sm shadow-slate-200/50 transition-all duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-[#2A57B4] ring-1 ring-[#2A57B4]/20 shadow-inner">
                    <i class='bx bx-calendar-check text-xl'></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Academic Context</h3>
                    <p class="text-sm font-bold text-slate-800">Viewing &amp; Tagging Semester</p>
                </div>
            </div>

            <div class="flex flex-1 sm:flex-none items-center gap-3">
                <div class="relative w-full sm:w-80">
                    <select wire:model.live="selectedSemesterId"
                        class="w-full appearance-none rounded-xl border border-slate-200 bg-white/80 pl-4 pr-10 py-2.5 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-all duration-200 cursor-pointer hover:border-slate-300 hover:bg-white focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#2A57B4]/15">
                        <option value="">Select a semester...</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">
                                {{ $semester->label() }}{{ $semester->is_current ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div wire:loading wire:target="selectedSemesterId" class="shrink-0">
                    <i class='bx bx-loader-alt animate-spin text-lg text-[#2A57B4]'></i>
                </div>
            </div>
        </div>

        @if(!$selectedSemesterId)
            <div class="mt-3.5 flex items-center gap-2 rounded-xl border border-amber-200/60 bg-amber-50/70 px-3.5 py-2 text-xs font-semibold text-amber-800 backdrop-blur-sm">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                </span>
                <i class='bx bx-info-circle text-base text-amber-600'></i>
                <span>Select an active semester above to view and tag clearance records.</span>
            </div>
        @endif
    </div>

    {{-- SEARCH + FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <i class='bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or student number..."
                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-11 pr-4 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
            </div>

            <div class="relative">
                <button type="button" wire:click="toggleFilters"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class='bx bx-slider-alt'></i> Filter
                </button>

                @if($showFilters)
                    <div class="absolute right-0 top-full z-20 mt-2 w-[300px] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                        <div class="grid grid-cols-2 gap-3">
                            <select wire:model.live="statusFilter" class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Status</option>
                                @foreach($filterStatuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="yearFilter" class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>

                            <div>
                                <label class="text-xs text-slate-400">From</label>
                                <input type="date" wire:model.live="dateFrom" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-slate-400">To</label>
                                <input type="date" wire:model.live="dateTo" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <button wire:click="applyFilters" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-[#214795]">Apply</button>
                            <button wire:click="resetFilters" class="flex-1 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- BULK TAG BAR --}}
    @if(count($selectedIds) > 0)
        <div class="fixed left-1/2 top-4 z-50 w-[92vw] max-w-2xl -translate-x-1/2 rounded-2xl border border-[#2A57B4]/30 bg-[#2A57B4] px-6 py-4 text-white shadow-lg shadow-[#2A57B4]/20">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 text-sm font-semibold whitespace-nowrap">
                    <i class='bx bx-check-square text-base'></i> {{ count($selectedIds) }} selected
                </span>

                <select wire:model="bulkStatus" class="rounded-xl border-0 bg-white/15 px-3 py-2 text-sm font-semibold text-white outline-none">
                    <option value="" class="text-slate-800">Select status...</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" class="text-slate-800">{{ $status }}</option>
                    @endforeach
                </select>

                <input type="text" wire:model="bulkRemarks" placeholder="Remarks (optional)"
                    class="flex-1 min-w-[180px] rounded-xl border-0 bg-white/15 px-3 py-2 text-sm text-white placeholder-white/70 outline-none">

                <button wire:click="bulkTag" class="rounded-xl bg-white px-5 py-2 text-sm font-semibold text-[#2A57B4] transition hover:bg-white/90">
                    Apply Tag
                </button>
                <button wire:click="$set('selectedIds', [])" class="text-sm font-medium text-white/80 hover:text-white">
                    Clear
                </button>
            </div>
        </div>
    @endif

    {{-- TABLE --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table-fixed">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:click="toggleSelectAllOnPage({{ json_encode($records->pluck('user_id')->all()) }})"
                                @checked($records->isNotEmpty() && $records->pluck('user_id')->every(fn($id) => in_array($id, $selectedIds)))
                                class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </th>
                        <th class="px-4 py-3 text-left font-semibold w-1/3">Student</th>
                        <th class="px-4 py-3 text-left font-semibold w-[15%]">Student No.</th>
                        <th class="px-4 py-3 text-left font-semibold w-[10%]">Year</th>
                        <th class="px-4 py-3 text-left font-semibold w-[15%]">Status</th>
                        <th class="px-4 py-3 text-left font-semibold w-1/4">Tagged By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        @php
                            $badge = match ($record['status']) {
                                'Cleared' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'Uncleared' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-slate-100 text-slate-500 border-slate-200',
                            };
                            $initials = strtoupper(mb_substr($record['student_name'], 0, 1));
                        @endphp
                        <tr class="hover:bg-[#2A57B4]/[0.03] cursor-pointer transition"
                            wire:key="clearance-row-{{ $record['user_id'] }}"
                            wire:click="goToProfile({{ $record['user_id'] }})">
                            <td class="px-4 py-3" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}"
                                    class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </td>
                            <td class="max-w-0 px-4 py-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    @if(!empty($record['profile_picture']))
                                        <img src="{{ $record['profile_picture'] }}"
                                            alt="{{ $record['student_name'] }}"
                                            class="h-8 w-8 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
                                    @else
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-xs font-bold text-[#2A57B4]">
                                            {{ $initials }}
                                        </span>
                                    @endif
                                    <span class="truncate font-medium text-slate-800" title="{{ $record['student_name'] }}">{{ $record['student_name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $record['student_number'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $record['year_level'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide {{ $badge }}">
                                    {{ $record['status'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $record['tagged_by'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                                No students match your current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($totalPages > 1)
            <div class="flex items-center justify-center gap-2 border-t border-slate-100 px-4 py-4">
                <button wire:click="previousPage" @disabled($currentPage === 1)
                    class="rounded-lg px-4 py-1.5 text-sm border {{ $currentPage === 1 ? 'text-slate-300 border-slate-100' : 'text-slate-700 border-slate-300 hover:bg-slate-50' }}">Prev</button>
                <span class="text-sm text-slate-500 px-2">{{ $currentPage }} / {{ $totalPages }}</span>
                <button wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                    class="rounded-lg px-4 py-1.5 text-sm border {{ $currentPage === $totalPages ? 'text-slate-300 border-slate-100' : 'text-slate-700 border-slate-300 hover:bg-slate-50' }}">Next</button>
            </div>
        @endif
    </div>
</div>