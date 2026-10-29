<div class="space-y-4 sm:space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ $pageHeading ?? 'Clearance Tagging' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $pageDescription ?? 'Tag and review clearance statuses for students in your organization' }}
        </p>
    </div>

    {{-- SEMESTER SELECTOR CARD --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-r from-white via-slate-50/50 to-white p-4 shadow-sm shadow-slate-200/50 transition-all duration-200 sm:p-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div class="flex items-center gap-3">
                <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-[#2A57B4] shadow-inner ring-1 ring-[#2A57B4]/20">
                    <i class='bx bx-calendar-check text-xl'></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Academic Context</h3>
                    <p class="text-sm font-bold text-slate-800">Viewing &amp; Tagging Semester</p>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:flex-none">
                <div class="relative min-w-0 flex-1 sm:w-80 sm:flex-none">
                    {{-- text-base on mobile prevents iOS auto-zoom on focus --}}
                    <select wire:model.live="selectedSemesterId"
                        class="w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white/80 py-3 pl-4 pr-10 text-base font-semibold text-slate-700 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-slate-300 hover:bg-white focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#2A57B4]/15 sm:py-2.5 sm:text-sm">
                        <option value="">Select a semester...</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">
                                {{ $semester->label() }}{{ $semester->pickerSuffix() }}
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
            <div class="mt-3.5 flex items-start gap-2 rounded-xl border border-amber-200/60 bg-amber-50/70 px-3.5 py-2 text-xs font-semibold text-amber-800 backdrop-blur-sm">
                <span class="relative mt-1.5 flex h-2 w-2 shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                </span>
                <i class='bx bx-info-circle mt-0.5 shrink-0 text-base text-amber-600'></i>
                <span>Select an active semester above to view and tag clearance records.</span>
            </div>
        @endif
    </div>

    {{-- SEARCH + FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-5">
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="relative min-w-0 flex-1">
                <i class='bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or student no..."
                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10 sm:py-2.5 sm:text-sm">
            </div>

            <div class="sm:relative">
                <button type="button" wire:click="toggleFilters" aria-label="Filter"
                    class="inline-flex h-[46px] shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:h-auto sm:px-4 sm:py-2.5">
                    <i class='bx bx-slider-alt text-lg sm:text-base'></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>

                @if($showFilters)
                    {{-- Mobile backdrop --}}
                    <div wire:click="toggleFilters" class="fixed inset-0 z-30 bg-slate-900/40 sm:hidden"></div>

                    {{-- Mobile: bottom sheet. sm+: dropdown anchored to the button. --}}
                    <div class="fixed inset-x-3 bottom-3 z-40 rounded-2xl border border-slate-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-xl
                                sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:top-full sm:z-20 sm:mt-2 sm:w-[300px] sm:pb-4">
                        <div class="mb-3 flex items-center justify-between sm:hidden">
                            <p class="text-sm font-bold text-slate-800">Filters</p>
                            <button type="button" wire:click="toggleFilters" class="p-1 text-slate-400 hover:text-slate-600" aria-label="Close filters">
                                <i class='bx bx-x text-2xl'></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <select wire:model.live="statusFilter" class="col-span-2 rounded-xl border border-slate-300 px-3 py-3 text-base sm:py-2 sm:text-sm">
                                <option value="">Status</option>
                                @foreach($filterStatuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="yearFilter" class="col-span-2 rounded-xl border border-slate-300 px-3 py-3 text-base sm:py-2 sm:text-sm">
                                <option value="">Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>

                            <div class="min-w-0">
                                <label class="text-xs text-slate-400">From</label>
                                <input type="date" wire:model.live="dateFrom" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                            </div>
                            <div class="min-w-0">
                                <label class="text-xs text-slate-400">To</label>
                                <input type="date" wire:model.live="dateTo" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <button wire:click="applyFilters" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-3 text-sm font-semibold text-white hover:bg-[#214795] sm:py-2">Apply</button>
                            <button wire:click="resetFilters" class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 sm:py-2">Reset</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- BULK TAG BAR --}}
    {{-- Mobile: bottom sheet (thumb reachable). sm+: floating top bar. --}}
    @if(count($selectedIds) > 0)
        <div class="fixed inset-x-3 bottom-3 z-50 rounded-2xl border border-[#2A57B4]/30 bg-[#2A57B4] p-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-white shadow-lg shadow-[#2A57B4]/20
                    sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-4 sm:w-[92vw] sm:max-w-2xl sm:-translate-x-1/2 sm:px-6 sm:pb-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <div class="flex items-center justify-between sm:justify-start sm:gap-3">
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-semibold">
                        <i class='bx bx-check-square text-base'></i> {{ count($selectedIds) }} selected
                    </span>
                    <button wire:click="$set('selectedIds', [])" class="px-1 py-1 text-sm font-medium text-white/80 hover:text-white sm:order-last">
                        Clear
                    </button>
                </div>

                <select wire:model="bulkStatus" class="rounded-xl border-0 bg-white/15 px-3 py-3 text-base font-semibold text-white outline-none sm:py-2 sm:text-sm">
                    <option value="" class="text-slate-800">Select status...</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" class="text-slate-800">{{ $status }}</option>
                    @endforeach
                </select>

                <input type="text" wire:model="bulkRemarks" placeholder="Remarks (optional)"
                    class="min-w-0 rounded-xl border-0 bg-white/15 px-3 py-3 text-base text-white placeholder-white/70 outline-none sm:min-w-[180px] sm:flex-1 sm:py-2 sm:text-sm">

                <button wire:click="bulkTag" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-[#2A57B4] transition hover:bg-white/90 sm:py-2">
                    Apply Tag
                </button>
            </div>
        </div>

        {{-- Spacer so the bottom sheet never covers the last rows on mobile --}}
        <div class="h-56 sm:hidden" aria-hidden="true"></div>
    @endif

    {{-- RESULTS --}}
    @php
        $pageIds = $records->pluck('user_id')->all();
        $allOnPageSelected = $records->isNotEmpty() && $records->pluck('user_id')->every(fn($id) => in_array($id, $selectedIds));
        $badgeFor = fn (string $status) => match ($status) {
            'Cleared' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Uncleared' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-500 border-slate-200',
        };
    @endphp

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- MOBILE: CARD LIST (below md) --}}
        <div class="md:hidden">
            @if($records->isNotEmpty())
                <label class="flex cursor-pointer items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <input type="checkbox" wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})"
                        @checked($allOnPageSelected)
                        class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                    Select all on page
                </label>
            @endif

            <div class="divide-y divide-slate-100">
                @forelse($records as $record)
                    @php $initials = strtoupper(mb_substr($record['student_name'], 0, 1)); @endphp
                    <div class="flex items-start gap-3 px-4 py-3.5 transition active:bg-[#2A57B4]/[0.05]"
                        wire:key="clearance-card-{{ $record['user_id'] }}"
                        wire:click="goToProfile({{ $record['user_id'] }})">

                        {{-- Larger tap target for the checkbox --}}
                        <label class="-m-2 flex shrink-0 cursor-pointer items-center p-2" wire:click.stop>
                            <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}"
                                class="h-5 w-5 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </label>

                        @if(!empty($record['profile_picture']))
                            <img src="{{ $record['profile_picture'] }}" alt="{{ $record['student_name'] }}"
                                class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-sm font-bold text-[#2A57B4]">
                                {{ $initials }}
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="min-w-0 truncate text-sm font-semibold text-slate-800">{{ $record['student_name'] }}</p>
                                <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badgeFor($record['status']) }}">
                                    {{ $record['status'] }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $record['student_number'] }} &middot; {{ $record['year_level'] }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-400">Tagged by {{ $record['tagged_by'] ?: '-' }}</p>
                        </div>

                        <i class='bx bx-chevron-right mt-2 shrink-0 text-lg text-slate-300'></i>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-400">
                        No students match your current filters.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- DESKTOP/TABLET: TABLE (md and up) --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full table-fixed text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})"
                                @checked($allOnPageSelected)
                                class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </th>
                        <th class="w-1/3 px-4 py-3 text-left font-semibold">Student</th>
                        <th class="w-[15%] px-4 py-3 text-left font-semibold">Student No.</th>
                        <th class="w-[10%] px-4 py-3 text-left font-semibold">Year</th>
                        <th class="w-[15%] px-4 py-3 text-left font-semibold">Status</th>
                        <th class="w-1/4 px-4 py-3 text-left font-semibold">Tagged By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        @php $initials = strtoupper(mb_substr($record['student_name'], 0, 1)); @endphp
                        <tr class="cursor-pointer transition hover:bg-[#2A57B4]/[0.03]"
                            wire:key="clearance-row-{{ $record['user_id'] }}"
                            wire:click="goToProfile({{ $record['user_id'] }})">
                            <td class="px-4 py-3" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}"
                                    class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </td>
                            <td class="max-w-0 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
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
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide {{ $badgeFor($record['status']) }}">
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
            <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 sm:justify-center sm:py-4">
                <button wire:click="previousPage" @disabled($currentPage === 1)
                    class="min-w-[84px] rounded-lg border px-4 py-2 text-sm sm:py-1.5 {{ $currentPage === 1 ? 'border-slate-100 text-slate-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">Prev</button>
                <span class="px-2 text-sm text-slate-500">{{ $currentPage }} / {{ $totalPages }}</span>
                <button wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                    class="min-w-[84px] rounded-lg border px-4 py-2 text-sm sm:py-1.5 {{ $currentPage === $totalPages ? 'border-slate-100 text-slate-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">Next</button>
            </div>
        @endif
    </div>
</div>