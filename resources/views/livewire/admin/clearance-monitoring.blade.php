@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $heading = $pageHeading ?? 'Clearance Monitoring';
    // The officers' Clearance Tagging uses this same page for their own organization: no organization filter
    // or column, and no admin breadcrumb.
    $isOfficer = $isOfficer ?? false;

    // Clearance pills: the system-wide words and colours (<x-status-badge :clearance>), as on Reports.
    $sm = 'w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-1.5';

    $pageIds = $records->pluck('user_id')->all();
    $allOnPage = $records->isNotEmpty() && collect($pageIds)->every(fn ($id) => in_array($id, $selectedIds));
    $activeFilters = collect([$statusFilter, $organizationFilter, $yearFilter, $dateFrom . $dateTo])->filter(fn ($v) => $v !== '')->count();
    $semesterLabel = $semesters->firstWhere('id', $selectedSemesterId)?->label();
@endphp

<div class="{{ count($selectedIds) > 0 ? 'pb-40' : 'pb-16' }}">
    <h1 class="sr-only">{{ $heading }}</h1>


    @unless ($selectedSemesterId)
        <x-admin.alert message="Choose a semester under Filters to view and tag clearance records." type="warning" />
    @endunless

    {{-- Summary cards: Cleared, Pending, Uncleared, Untagged, each in its pill's colours (StatusStyle::CLEARANCE), the count
         with a few of its students beside it. Selecting one filters the list; the selected card gets a darker outline. --}}
    @php
        // [status key, label, icon, card (idle), card (selected), label text]
        $summaryCards = [
            ['Cleared', 'Cleared', 'bx-check-shield',
                'bg-emerald-50 border-emerald-200 hover:border-emerald-300', 'bg-emerald-50 border-emerald-500 ring-1 ring-emerald-500', 'text-emerald-700'],
            ['Pending', 'Pending', 'bx-time-five',
                'bg-amber-50 border-amber-200 hover:border-amber-300', 'bg-amber-50 border-amber-500 ring-1 ring-amber-500', 'text-amber-700'],
            ['Uncleared', 'Uncleared', 'bx-x-circle',
                'bg-rose-50 border-rose-200 hover:border-rose-300', 'bg-rose-50 border-rose-500 ring-1 ring-rose-500', 'text-rose-700'],
            [\App\Livewire\Admin\ClearanceMonitoring::STATUS_NOT_TAGGED, 'Untagged', 'bx-circle',
                'bg-slate-100 border-slate-200 hover:border-slate-300', 'bg-slate-100 border-slate-500 ring-1 ring-slate-500', 'text-slate-600'],
        ];
    @endphp
    <nav class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Clearance status">
        @foreach ($summaryCards as [$key, $label, $icon, $idle, $active, $text])
            @php $on = $statusFilter === $key; $n = (int) ($statusCounts[$key] ?? 0); @endphp
            <button type="button" wire:click="$set('statusFilter', '{{ $on ? '' : $key }}')" aria-pressed="{{ $on ? 'true' : 'false' }}" wire:key="ccard-{{ $loop->index }}"
                    class="{{ \App\Support\SummaryCard::BOX }} {{ $on ? $active : $idle }} focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30">
                <i class="bx {{ $icon }} pointer-events-none absolute -bottom-3 -right-2 -z-10 text-6xl opacity-[0.12] {{ $text }}" aria-hidden="true"></i>
                <span class="flex items-center gap-2.5">
                    <span class="text-2xl font-semibold leading-none tracking-[-0.02em] tabular-nums text-slate-900">{{ $n }}</span>
                    <span class="flex -space-x-1.5" aria-hidden="true">
                        @foreach (($statusFaces[$key] ?? collect()) as $f)
                            <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-white text-[9px] font-semibold tracking-tight text-[#1E4090] ring-2 ring-white/90">
                                {{ mb_strtoupper(mb_substr($f['student_name'], 0, 1)) }}
                                @if (! empty($f['profile_picture']))
                                    <img src="{{ $f['profile_picture'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                @endif
                            </span>
                        @endforeach
                    </span>
                </span>
                <span class="mt-2 block text-sm font-semibold leading-tight sm:text-[15px] {{ $text }}">{{ $label }}</span>
            </button>
        @endforeach
    </nav>

    {{-- Toolbar: Filters (the shared pop-up from Workflow review, with the semester at the top), then the search. --}}
    <div class="mb-3 flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3">
        <x-admin.filters-popup :count="$activeFilters" reset="resetFilters">
            <label class="col-span-2 block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Semester</span>
                <select wire:model.live="selectedSemesterId" class="{{ $sm }}">
                    <option value="">Select a semester…</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->pickerSuffix() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="col-span-2 block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Status</span>
                <select wire:model.live="statusFilter" class="{{ $sm }}">
                    <option value="">All</option>
                    @foreach ($filterStatuses as $status)
                        <option value="{{ $status }}">{{ \Illuminate\Support\Str::limit($status, 40) }}</option>
                    @endforeach
                </select>
            </label>
            @unless ($isOfficer)
                <label class="col-span-2 block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Organization</span>
                    <select wire:model.live="organizationFilter" class="{{ $sm }}">
                        <option value="">All</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization }}">{{ \Illuminate\Support\Str::limit($organization, 40) }}</option>
                        @endforeach
                    </select>
                </label>
            @endunless
            <label class="col-span-2 block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Year level</span>
                <select wire:model.live="yearFilter" class="{{ $sm }}">
                    <option value="">All</option>
                    <option value="1">1st year</option>
                    <option value="2">2nd year</option>
                    <option value="3">3rd year</option>
                    <option value="4">4th year</option>
                </select>
            </label>
            <label class="block min-w-0">
                <span class="mb-1 block text-xs font-medium text-slate-600">Tagged from</span>
                <input type="date" wire:model.live="dateFrom" class="{{ $sm }}">
            </label>
            <label class="block min-w-0">
                <span class="mb-1 block text-xs font-medium text-slate-600">Tagged to</span>
                <input type="date" wire:model.live="dateTo" class="{{ $sm }}">
            </label>
        </x-admin.filters-popup>

        <div class="relative min-w-0 flex-1">
            <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ $isOfficer ? 'Search name or student no.…' : 'Search name, no., organization…' }}" aria-label="Search students"
                   class="{{ $sm }} pl-9 pr-3">
        </div>

        {{-- The semester being shown, now that its picker lives in Filters --}}
        <p class="hidden max-w-[16rem] shrink-0 truncate text-xs tabular-nums text-slate-500 sm:block" title="{{ $semesterLabel }}">
            {{ number_format($totalCount) }} {{ \Illuminate\Support\Str::plural('student', $totalCount) }}@if ($semesterLabel) · {{ $semesterLabel }}@endif
        </p>
    </div>

    {{-- Bulk bar: the same floating bar as Workflow review, once students are ticked. The status is three buttons. --}}
    @if (count($selectedIds) > 0)
        @php
            $bulkTone = [
                'Cleared' => ['bx-check-shield', 'border-emerald-300 bg-emerald-50 text-emerald-800', 'text-emerald-600'],
                'Pending' => ['bx-time-five', 'border-amber-300 bg-amber-50 text-amber-800', 'text-amber-600'],
                'Uncleared' => ['bx-x-circle', 'border-rose-300 bg-rose-50 text-rose-800', 'text-rose-600'],
            ];
        @endphp
        <div class="pointer-events-none fixed inset-x-0 bottom-3 z-[85] flex justify-center px-3 sm:bottom-4 sm:px-4">
            <div class="pointer-events-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-2.5 shadow-lg sm:flex sm:items-center sm:gap-2 sm:p-1.5 sm:pl-3" role="region" aria-label="Tag the selected students">
                <div class="mb-2 flex items-center justify-between sm:mb-0 sm:shrink-0">
                    <span class="text-sm font-medium tabular-nums text-slate-800" role="status">{{ count($selectedIds) }} selected</span>
                    <button type="button" wire:click="$set('selectedIds', [])" aria-label="Clear selection"
                            class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 sm:hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11">
                        <i class="bx bx-x text-xl" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-1.5 sm:flex sm:shrink-0" role="group" aria-label="Status to apply">
                    @foreach ($statuses as $status)
                        @php [$bIcon, $bOn, $bIconColor] = $bulkTone[$status] ?? ['bx-circle', 'border-slate-300 bg-slate-50 text-slate-800', 'text-slate-500']; $on = $bulkStatus === $status; @endphp
                        <button type="button" wire:click="$set('bulkStatus', '{{ $status }}')" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border px-2.5 text-sm transition-colors [@media(pointer:coarse)]:min-h-11
                                    {{ $on ? $bOn . ' font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                            <i class="bx {{ $bIcon }} text-base {{ $on ? '' : $bIconColor }}" aria-hidden="true"></i>{{ $status }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-2 flex items-center gap-1.5 sm:mt-0 sm:min-w-0 sm:flex-1">
                    <input type="text" wire:model="bulkRemarks" placeholder="Remarks (optional)" aria-label="Remarks"
                           class="h-9 min-w-0 flex-1 rounded-lg border-slate-300 px-3 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
                    <button type="button" wire:click="bulkTag" wire:loading.attr="disabled" wire:target="bulkTag" @disabled($bulkStatus === '')
                            class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] disabled:opacity-50 [@media(pointer:coarse)]:min-h-11">
                        <i class="bx bx-purchase-tag text-base" aria-hidden="true"></i> Apply tag
                    </button>
                    <button type="button" wire:click="$set('selectedIds', [])" aria-label="Clear selection"
                            class="hidden h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 sm:grid">
                        <i class="bx bx-x text-xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ DESKTOP TABLE: the Workflow review table's style ============ --}}
    @php $loadTargets = 'search, sortBy, selectedSemesterId, statusFilter, organizationFilter, yearFilter, dateFrom, dateTo, resetFilters, previousPage, nextPage'; @endphp
    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity md:block"
         wire:loading.class="opacity-60" wire:loading.attr="aria-busy" wire:target="{{ $loadTargets }}">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs">
                    <tr>
                        <th scope="col" class="w-10 py-2.5 pl-4 pr-1 text-left">
                            <input type="checkbox" aria-label="Select all on page"
                                   wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})"
                                   @checked($allOnPage)
                                   class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </th>
                        <th scope="col" class="w-12 py-2.5 pl-2 pr-1 text-left font-medium text-slate-500">#</th>
                        <x-admin.sort-th key="student" label="Student" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        @unless ($isOfficer)<x-admin.sort-th key="organization" label="Organization" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />@endunless
                        <x-admin.sort-th key="year" label="Year" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <x-admin.sort-th key="status" label="Status" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5">
                            <x-admin.legend-tip label="What the clearance statuses mean" note="The student's latest tag for the semester." :items="\App\Support\Legends::clearance()" />
                        </x-admin.sort-th>
                        <x-admin.sort-th key="tagged" label="Tagged by" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php $picked = in_array($record['user_id'], $selectedIds); @endphp
                        <tr wire:key="clearance-row-{{ $record['user_id'] }}"
                            wire:click="goToProfile({{ $record['user_id'] }})"
                            class="cursor-pointer transition-colors {{ $picked ? 'bg-[#f0f4fb]' : 'hover:bg-slate-50' }}">
                            <td class="py-3 pl-4 pr-1" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}" aria-label="Select {{ $record['student_name'] }}"
                                       class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </td>
                            <td class="py-3 pl-2 pr-1 text-xs tabular-nums text-slate-500">{{ ($currentPage - 1) * $perPage + $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                        {{ \App\Support\Honorifics::initials($record['student_name']) }}
                                        @if (! empty($record['profile_picture']))
                                            <img src="{{ $record['profile_picture'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-[240px] truncate font-medium text-slate-900" title="{{ $record['student_name'] }}">{{ $record['student_name'] }}</p>
                                        <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $record['student_number'] }}</p>
                                    </div>
                                </div>
                            </td>
                            @unless ($isOfficer)
                                <td class="px-4 py-3"><p class="max-w-[240px] truncate text-slate-700" title="{{ $record['organization'] }}">{{ $record['organization'] }}</p></td>
                            @endunless
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $record['year_level'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-status-badge :clearance="$record['status'] ?? ''" />
                            </td>
                            <td class="px-4 py-3">
                                <p class="max-w-[200px] truncate text-slate-700" title="{{ $record['tagged_by'] }}">{{ $record['tagged_by'] }}</p>
                                @if ($record['tagged_date'] !== '')
                                    <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ \Illuminate\Support\Carbon::parse($record['tagged_date'])->format('M j, Y') }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isOfficer ? 6 : 7 }}" class="px-6 py-16 text-center text-sm text-slate-500">No students match your current filters.</td></tr>
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
                 class="flex items-start gap-3 rounded-xl border bg-white p-3 shadow-sm {{ in_array($record['user_id'], $selectedIds) ? 'border-[#2A57B4]/40 bg-[#2A57B4]/[0.03]' : 'border-gray-200' }}">
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
                    @unless ($isOfficer)<p class="mt-2 truncate text-xs text-gray-500">{{ $record['organization'] }}</p>@endunless
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <x-status-badge :clearance="$record['status'] ?? ''" />
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