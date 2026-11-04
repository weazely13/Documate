@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $heading = $pageHeading ?? 'Clearance Monitoring';
    // The officers' Clearance Tagging uses this same page for their own organization: no organization filter
    // or column, and no admin breadcrumb.
    $isOfficer = $isOfficer ?? false;
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

<div class="{{ count($selectedIds) > 0 ? 'pb-40' : 'pb-16' }}">
    <h1 class="sr-only">{{ $heading }}</h1>


    @unless ($selectedSemesterId)
        <x-admin.alert message="Select a semester to view and tag clearance records." type="warning" />
    @endunless

    {{-- Summary cards: each status in its work tint, the count with a few of its students beside it. Selecting one filters the list. --}}
    @php
        $summaryCards = [
            [\App\Livewire\Admin\ClearanceMonitoring::STATUS_NOT_TAGGED, 'Not tagged', 'bg-[#f5f4f2]', 'bx-circle', 'No status assigned'],
            ['Pending', 'Pending', 'bg-[#fef3c7]', 'bx-time-five', 'Awaiting clearance verification'],
            ['Cleared', 'Cleared', 'bg-[#d9f3e1]', 'bx-check-shield', 'Requirements are fulfilled'],
            ['Uncleared', 'Uncleared', 'bg-[#fde0ec]', 'bx-x-circle', 'Requirements are incomplete'],
        ];
    @endphp
    <nav class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Clearance status">
        @foreach ($summaryCards as [$key, $label, $tint, $icon, $hint])
            @php $on = $statusFilter === $key; $n = (int) ($statusCounts[$key] ?? 0); @endphp
            <button type="button" wire:click="$set('statusFilter', '{{ $on ? '' : $key }}')" aria-pressed="{{ $on ? 'true' : 'false' }}" wire:key="ccard-{{ $loop->index }}"
                    class="relative isolate flex flex-col overflow-hidden rounded-2xl border p-3.5 text-left transition sm:p-4 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 {{ $tint }}
                        {{ $on ? 'border-[#2A57B4] shadow-[0_0_0_1px_#2A57B4]' : 'border-transparent hover:brightness-[0.98]' }}">
                <i class="bx {{ $icon }} pointer-events-none absolute -bottom-3 -right-2 -z-10 text-6xl text-slate-900/[0.07]" aria-hidden="true"></i>
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
                <span class="mt-2 block text-sm font-semibold leading-tight text-slate-900 sm:text-[15px]">{{ $label }}</span>
                <span class="mt-0.5 block truncate text-xs text-slate-600">{{ $allCount ? round($n / $allCount * 100) . '% · ' : '' }}{{ $hint }}</span>
            </button>
        @endforeach
    </nav>

    {{-- Toolbar: the semester and the filters sit together on the left so the search gets the rest of the line.
         On phones the semester is an icon with the native picker laid over it. --}}
    <div class="mb-3 flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3">
        <label class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-[#2A57B4] sm:block sm:h-auto sm:w-56 sm:border-0 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 sm:[@media(pointer:coarse)]:h-auto sm:[@media(pointer:coarse)]:w-56" title="Semester">
            <i class="bx bx-calendar-check text-lg sm:!hidden" aria-hidden="true"></i>
            <span class="sr-only">Semester</span>
            <select wire:model.live="selectedSemesterId" class="{{ $sm }} absolute inset-0 h-full cursor-pointer opacity-0 sm:static sm:opacity-100">
                <option value="">Select a semester…</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}">{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->pickerSuffix() }}</option>
                @endforeach
            </select>
        </label>

        {{-- Filters: a pop-up over the page (a bottom sheet on phones), not a panel that pushes the list down --}}
        <div class="relative shrink-0">
            <button type="button" wire:click="toggleFilters" aria-expanded="{{ $showFilters ? 'true' : 'false' }}" aria-haspopup="dialog" aria-label="Filters"
                    class="relative inline-flex h-9 items-center justify-center gap-2 rounded-lg border bg-white px-2.5 text-sm font-medium shadow-sm transition hover:bg-slate-50 sm:px-3.5 [@media(pointer:coarse)]:h-11
                        {{ $showFilters ? 'border-[#2A57B4]/40 text-[#2A57B4]' : 'border-slate-200 text-slate-700' }}">
                <i class='bx bx-slider-alt text-base' aria-hidden="true"></i>
                <span class="hidden sm:inline">Filters</span>
                @if ($activeFilters)
                    <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[10px] font-semibold text-white sm:static sm:h-auto sm:min-w-0 sm:px-1.5 sm:text-[11px]">{{ $activeFilters }}</span>
                @endif
            </button>

            @if ($showFilters)
                <div wire:click="toggleFilters" class="fixed inset-0 z-[90] bg-slate-900/40 sm:z-40 sm:bg-transparent" aria-hidden="true"></div>
                <div class="fixed inset-x-0 bottom-0 z-[95] rounded-t-2xl bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl
                            sm:absolute sm:z-50 sm:inset-x-auto sm:bottom-auto sm:left-0 sm:top-full sm:mt-2 sm:w-[22rem] sm:rounded-xl sm:border sm:border-slate-200 sm:p-4 sm:shadow-lg"
                     role="dialog" aria-label="Filters" wire:keydown.escape.window="toggleFilters">
                    <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300 sm:hidden" aria-hidden="true"></div>
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-[15px] font-semibold text-slate-900">Filters</p>
                        <button type="button" wire:click="toggleFilters" aria-label="Close filters" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bx bx-x text-xl" aria-hidden="true"></i></button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
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
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" wire:click="resetFilters"
                                class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 [@media(pointer:coarse)]:min-h-11">Reset</button>
                        <button type="button" wire:click="applyFilters"
                                class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] [@media(pointer:coarse)]:min-h-11">Done</button>
                    </div>
                </div>
            @endif
        </div>

        <div class="relative min-w-0 flex-1">
            <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ $isOfficer ? 'Search name or student no.…' : 'Search name, no., organization…' }}" aria-label="Search students"
                   class="{{ $sm }} pl-9 pr-3">
        </div>

        <p class="hidden shrink-0 text-xs tabular-nums text-slate-500 lg:block">{{ number_format($totalCount) }} {{ \Illuminate\Support\Str::plural('student', $totalCount) }}</p>
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

    {{-- ============ DESKTOP TABLE ============ --}}
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                    <tr>
                        <th class="w-12 px-5 py-2.5 text-left">
                            <input type="checkbox" aria-label="Select all on page"
                                   wire:click="toggleSelectAllOnPage({{ json_encode($pageIds) }})"
                                   @checked($allOnPage)
                                   class="rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        </th>
                        <th class="{{ $isOfficer ? 'w-[45%]' : 'w-1/4' }} px-5 py-3 text-left">Student</th>
                        <th class="w-[13%] px-5 py-3 text-left">Student no.</th>
                        @unless ($isOfficer)<th class="w-1/5 px-5 py-3 text-left">Organization</th>@endunless
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
                            <td class="px-5 py-2" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $record['user_id'] }}" aria-label="Select {{ $record['student_name'] }}"
                                       class="rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            </td>
                            <td class="px-5 py-2">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if (!empty($record['profile_picture']))
                                        <img src="{{ $record['profile_picture'] }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                                    @else
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-xs font-semibold text-[#2A57B4]">{{ strtoupper(mb_substr($record['student_name'], 0, 1)) }}</span>
                                    @endif
                                    <span class="truncate font-medium text-gray-900" title="{{ $record['student_name'] }}">{{ $record['student_name'] }}</span>
                                </div>
                            </td>
                            <td class="truncate px-5 py-2 text-gray-600" title="{{ $record['student_number'] }}">{{ $record['student_number'] }}</td>
                            @unless ($isOfficer)<td class="px-5 py-2 text-gray-600"><div class="truncate" title="{{ $record['organization'] }}">{{ $record['organization'] }}</div></td>@endunless
                            <td class="truncate px-5 py-2 text-gray-600">{{ $record['year_level'] }}</td>
                            <td class="px-5 py-2">
                                <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $badgeFor($record['status']) }}">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>{{ $record['status'] }}
                                </span>
                            </td>
                            <td class="px-5 py-2 text-gray-500"><div class="truncate" title="{{ $record['tagged_by'] }}">{{ $record['tagged_by'] }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isOfficer ? 6 : 7 }}" class="px-5 py-12 text-center text-sm text-gray-400">No students match your current filters.</td></tr>
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