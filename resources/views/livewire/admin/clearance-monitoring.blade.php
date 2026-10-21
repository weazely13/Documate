@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $heading = $pageHeading ?? 'Clearance Monitoring';
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $badgeFor = fn (string $s) => match ($s) {
        'Cleared' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'Pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'Uncleared' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-gray-100 text-gray-500 ring-gray-200',
    };
    $sm = 'w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-1.5';

    $pageIds = $records->pluck('user_id')->all();
    $allOnPage = $records->isNotEmpty() && collect($pageIds)->every(fn ($id) => in_array($id, $selectedIds));
    $activeFilters = collect([$statusFilter, $organizationFilter, $yearFilter, $dateFrom, $dateTo])->filter(fn ($v) => $v !== '')->count();
@endphp

<div class="{{ count($selectedIds) > 0 ? 'pb-40 sm:pb-0' : '' }}">
    <x-admin.page-header
        :title="$heading"
        :description="$pageDescription ?? 'Track and tag student clearance status by semester.'"
        :crumbs="['Admin' => $homeUrl, $heading => null]" />

    @include('livewire.admin._admin-nav')

    @unless ($selectedSemesterId)
        <x-admin.alert message="Select a semester to view and tag clearance records." type="warning" />
    @endunless

    {{-- Toolbar --}}
    <div class="mb-2 space-y-2 rounded-xl border border-gray-200 bg-white p-2.5 shadow-sm sm:flex sm:flex-wrap sm:items-center sm:gap-2 sm:space-y-0 sm:p-3">
        <div class="sm:w-64">
            <select wire:model.live="selectedSemesterId" aria-label="Semester" class="{{ $sm }}">
                <option value="">Select a semester…</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}">{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->is_current ? ' (Current)' : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 sm:contents">
            <div class="relative min-w-0 flex-1 sm:min-w-[220px]">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, no., organization…"
                    class="{{ $sm }} pl-9 pr-3">
            </div>

            <button type="button" wire:click="toggleFilters" aria-expanded="{{ $showFilters ? 'true' : 'false' }}" aria-label="Filters"
                    class="relative inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm font-medium shadow-sm transition hover:bg-gray-50 sm:px-4 sm:py-1.5
                        {{ $showFilters ? 'border-[#2A57B4]/40 text-[#2A57B4]' : 'border-gray-200 text-gray-700' }}">
                <i class='bx bx-slider-alt text-base'></i>
                <span class="hidden sm:inline">Filters</span>
                @if ($activeFilters)
                    <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[10px] font-semibold text-white sm:static sm:h-auto sm:min-w-0 sm:px-1.5 sm:text-[11px]">{{ $activeFilters }}</span>
                @endif
            </button>
        </div>

        <p class="px-0.5 text-[11px] text-gray-500 sm:ml-auto sm:text-xs">{{ number_format($totalCount) }} {{ \Illuminate\Support\Str::plural('student', $totalCount) }}</p>
    </div>

    {{-- Filter panel --}}
    @if ($showFilters)
        <div class="mb-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5">
                <div class="col-span-2 sm:col-span-1">
                    <label class="mb-0.5 block text-[11px] font-medium text-gray-500 sm:text-xs">Status</label>
                    <select wire:model.live="statusFilter" class="{{ $sm }}">
                        <option value="">All</option>
                        @foreach ($filterStatuses as $status)
                            <option value="{{ $status }}">{{ \Illuminate\Support\Str::limit($status, 40) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="mb-0.5 block text-[11px] font-medium text-gray-500 sm:text-xs">Organization</label>
                    <select wire:model.live="organizationFilter" class="{{ $sm }}">
                        <option value="">All</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization }}">{{ \Illuminate\Support\Str::limit($organization, 40) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="mb-0.5 block text-[11px] font-medium text-gray-500 sm:text-xs">Year level</label>
                    <select wire:model.live="yearFilter" class="{{ $sm }}">
                        <option value="">All</option>
                        <option value="1">1st year</option>
                        <option value="2">2nd year</option>
                        <option value="3">3rd year</option>
                        <option value="4">4th year</option>
                    </select>
                </div>
                <div>
                    <label class="mb-0.5 block text-[11px] font-medium text-gray-500 sm:text-xs">Tagged from</label>
                    <input type="date" wire:model.live="dateFrom" class="{{ $sm }}">
                </div>
                <div>
                    <label class="mb-0.5 block text-[11px] font-medium text-gray-500 sm:text-xs">Tagged to</label>
                    <input type="date" wire:model.live="dateTo" class="{{ $sm }}">
                </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                <button type="button" wire:click="resetFilters"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:py-1.5">Reset</button>
                <button type="button" wire:click="applyFilters"
                        class="rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 sm:py-1.5">Apply</button>
            </div>
        </div>
    @endif

    {{-- Bulk tag bar: bottom sheet on mobile, floating bar on desktop --}}
    @if (count($selectedIds) > 0)
        <div class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl border-t border-[#2A57B4]/30 bg-[#2A57B4] px-4 pb-[calc(1rem+env(safe-area-inset-bottom,0px))] pt-4 text-white shadow-2xl
                    sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-4 sm:w-[92vw] sm:max-w-3xl sm:-translate-x-1/2 sm:rounded-xl sm:border sm:p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                <div class="flex items-center justify-between sm:justify-start">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold">
                        <i class='bx bx-check-square text-base'></i> {{ count($selectedIds) }} selected
                    </span>
                    <button type="button" wire:click="$set('selectedIds', [])" class="text-sm font-medium text-white/80 hover:text-white sm:hidden">Clear</button>
                </div>

                <select wire:model="bulkStatus" aria-label="Status to apply"
                        class="rounded-lg border-0 bg-white/15 px-3 py-2.5 text-base font-medium text-white focus:ring-2 focus:ring-white/40 sm:py-2 sm:text-sm">
                    <option value="" class="text-gray-800">Select status…</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" class="text-gray-800">{{ \Illuminate\Support\Str::limit($status, 40) }}</option>
                    @endforeach
                </select>

                <input type="text" wire:model="bulkRemarks" placeholder="Remarks (optional)"
                       class="min-w-0 rounded-lg border-0 bg-white/15 px-3 py-2.5 text-base text-white placeholder-white/70 focus:ring-2 focus:ring-white/40 sm:flex-1 sm:py-2 sm:text-sm">

                <button type="button" wire:click="bulkTag" wire:loading.attr="disabled" wire:target="bulkTag"
                        class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-[#2A57B4] transition hover:bg-white/90 disabled:opacity-60 sm:py-2">
                    Apply tag
                </button>
                <button type="button" wire:click="$set('selectedIds', [])" class="hidden text-sm font-medium text-white/80 hover:text-white sm:block">Clear</button>
            </div>
        </div>
    @endif

    {{-- ============ DESKTOP TABLE ============ --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                    <tr>
                        <th class="w-12 px-5 py-3 text-left">
                            <input type="checkbox" aria-label="Select all on page"
                                   wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})"
                                   @checked($allOnPage)
                                   class="rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </th>
                        <th class="w-1/4 px-5 py-3 text-left">Student</th>
                        <th class="w-[13%] px-5 py-3 text-left">Student no.</th>
                        <th class="w-1/5 px-5 py-3 text-left">Organization</th>
                        <th class="w-[9%] px-5 py-3 text-left">Year</th>
                        <th class="w-[13%] px-5 py-3 text-left">Status</th>
                        <th class="w-1/6 px-5 py-3 text-left">Tagged by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $record)
                        <tr wire:key="clearance-row-{{ $record['user_id'] }}"
                            wire:click="goToProfile({{ $record['user_id'] }})"
                            class="cursor-pointer align-middle transition-colors hover:bg-gray-50/70">
                            <td class="px-5 py-3.5" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}" aria-label="Select {{ $record['student_name'] }}"
                                       class="rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if (!empty($record['profile_picture']))
                                        <img src="{{ $record['profile_picture'] }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                                    @else
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-xs font-semibold text-[#2A57B4]">{{ strtoupper(mb_substr($record['student_name'], 0, 1)) }}</span>
                                    @endif
                                    <span class="truncate font-medium text-gray-900" title="{{ $record['student_name'] }}">{{ $record['student_name'] }}</span>
                                </div>
                            </td>
                            <td class="truncate px-5 py-3.5 text-gray-600" title="{{ $record['student_number'] }}">{{ $record['student_number'] }}</td>
                            <td class="px-5 py-3.5 text-gray-600"><div class="truncate" title="{{ $record['organization'] }}">{{ $record['organization'] }}</div></td>
                            <td class="truncate px-5 py-3.5 text-gray-600">{{ $record['year_level'] }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $badgeFor($record['status']) }}">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>{{ $record['status'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500"><div class="truncate" title="{{ $record['tagged_by'] }}">{{ $record['tagged_by'] }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">No students match your current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ MOBILE CARDS ============ --}}
    <div class="space-y-3 md:hidden">
        @if ($records->isNotEmpty())
            <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                <input type="checkbox" wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})" @checked($allOnPage)
                       class="h-5 w-5 rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                Select all on this page
            </label>
        @endif

        @forelse ($records as $record)
            <div wire:key="clearance-card-{{ $record['user_id'] }}"
                 class="flex items-start gap-3 rounded-xl border bg-white p-4 shadow-sm {{ in_array($record['user_id'], $selectedIds) ? 'border-[#2A57B4]/40 bg-[#2A57B4]/[0.03]' : 'border-gray-200' }}">
                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}" aria-label="Select {{ $record['student_name'] }}"
                       class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                <button type="button" wire:click="goToProfile({{ $record['user_id'] }})" class="min-w-0 flex-1 text-left">
                    <div class="flex items-center gap-3">
                        @if (!empty($record['profile_picture']))
                            <img src="{{ $record['profile_picture'] }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-sm font-semibold text-[#2A57B4]">{{ strtoupper(mb_substr($record['student_name'], 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900">{{ $record['student_name'] }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $record['student_number'] }} · {{ $record['year_level'] }}</p>
                        </div>
                    </div>
                    <p class="mt-2 truncate text-xs text-gray-500">{{ $record['organization'] }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $badgeFor($record['status']) }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $record['status'] }}
                        </span>
                        @if ($record['tagged_by'] !== '-')
                            <span class="text-xs text-gray-400">by {{ $record['tagged_by'] }}</span>
                        @endif
                    </div>
                </button>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No students match your current filters.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($totalPages > 1)
        <div class="mb-12 mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-xs text-gray-500">
                Showing {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $totalCount) }} of {{ number_format($totalCount) }}
            </p>
            <div class="flex w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm shadow-sm sm:inline-flex sm:w-auto">
                <button type="button" wire:click="previousPage" @disabled($currentPage === 1)
                        class="flex-1 px-4 py-2 transition sm:flex-none {{ $currentPage === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">Prev</button>
                <div class="flex items-center justify-center border-x border-gray-200 px-4 text-gray-600">{{ $currentPage }} / {{ $totalPages }}</div>
                <button type="button" wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                        class="flex-1 px-4 py-2 transition sm:flex-none {{ $currentPage === $totalPages ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">Next</button>
            </div>
        </div>
    @endif
</div>