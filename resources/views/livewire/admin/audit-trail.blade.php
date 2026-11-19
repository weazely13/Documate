@php
    use Illuminate\Support\Str;

    // The Workflow review page's controls, so the two pages read the same.
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $field = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
    $fsel = 'h-9 w-full py-0 text-sm [@media(pointer:coarse)]:h-11 ' . $field;
    $btnPrimary = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[#24499A] disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;

    $actionBadge = fn ($action) => match ($action) {
        'login' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'login_failed', 'deleted' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'created', 'restored' => 'bg-blue-50 text-[#2A57B4] ring-blue-200',
        'updated' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'workflow' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $actionIcon = [
        'login' => 'bx-log-in', 'logout' => 'bx-log-out', 'login_failed' => 'bx-shield-x', 'created' => 'bx-plus',
        'updated' => 'bx-edit-alt', 'deleted' => 'bx-trash', 'restored' => 'bx-revision', 'exported' => 'bx-download', 'workflow' => 'bx-git-branch',
    ];
    // Module chip: [icon, classes]
    $moduleStyle = fn ($m) => match ($m) {
        'Authentication' => ['bx-lock-alt', 'bg-emerald-50 text-emerald-700'],
        'Transactions' => ['bx-transfer', 'bg-indigo-50 text-indigo-700'],
        'Users' => ['bx-group', 'bg-sky-50 text-sky-700'],
        'Feedback' => ['bx-message-square-dots', 'bg-pink-50 text-pink-700'],
        'Appointments' => ['bx-calendar', 'bg-violet-50 text-violet-700'],
        'Clearance' => ['bx-clipboard', 'bg-teal-50 text-teal-700'],
        'Verification' => ['bx-check-shield', 'bg-lime-50 text-lime-700'],
        'Templates' => ['bx-file', 'bg-orange-50 text-orange-700'],
        'Signatories' => ['bx-pen', 'bg-fuchsia-50 text-fuchsia-700'],
        'Organizations' => ['bx-buildings', 'bg-cyan-50 text-cyan-700'],
        'Semesters & Verification' => ['bx-calendar-event', 'bg-amber-50 text-amber-700'],
        'Audit Trail' => ['bx-history', 'bg-slate-100 text-slate-700'],
        default => ['bx-cube', 'bg-slate-100 text-slate-600'],
    };
    $show = fn ($v) => $v === null || $v === '' ? '(empty)' : (is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v));

    $loadTargets = 'search, action, module, role, period, dateFrom, dateTo, resetFilters, clearFilters, sortBy, nextPage, previousPage, gotoPage';
    $emptyTitle = $hasFilters ? 'No entries match your filters.' : 'No activity recorded yet.';
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    <h1 class="sr-only">Audit Trail</h1>

    {{-- Today --}}
    @php
        $todayTiles = [
            // [label, count today, icon, the action filter it sets (null = today's events)]
            ['Events', $today['events'], 'bx-pulse', null],
            ['Sign-ins', $today['signins'], 'bx-log-in', 'login'],
            ['Changes', $today['changes'], 'bx-edit-alt', 'updated'],
            ['Failed', $today['failed'], 'bx-shield-x', 'login_failed'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($todayTiles as [$label, $value, $icon, $filter])
            @php $picked = $filter ? $action === $filter : ($period === 'today' && $action === ''); @endphp
            <button type="button" @if ($filter) wire:click="$set('action', '{{ $filter }}')" @else wire:click="$set('period', 'today')" @endif
                    aria-pressed="{{ $picked ? 'true' : 'false' }}"
                    class="{{ \App\Support\SummaryCard::BOX }} {{ \App\Support\SummaryCard::tone($picked) }} {{ $focus }}">
                <i class="bx {{ $icon }} {{ \App\Support\SummaryCard::ICON }}" aria-hidden="true"></i>
                <p class="text-2xl font-semibold leading-none tabular-nums">{{ number_format($value) }}</p>
                <p class="mt-2 text-sm font-semibold leading-tight sm:text-[15px]">{{ $label }}</p>
                <p class="mt-0.5 text-xs text-slate-600">Today</p>
            </button>
        @endforeach
    </div>

    <div class="space-y-3">
        {{-- One row, as on Workflow review: Filters (role, action, module and period in one pop-up), the search, Download CSV --}}
        <div class="flex items-center gap-2">
            <x-admin.filters-popup :count="$activeFilters" reset="resetFilters">
                <label class="block min-w-0">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Role</span>
                    <select wire:model.live="role" class="{{ $fsel }}">
                        <option value="">All</option>
                        @foreach ($roles as $r)<option value="{{ $r }}">{{ Str::headline($r) }}</option>@endforeach
                    </select>
                </label>
                <label class="block min-w-0">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Action</span>
                    <select wire:model.live="action" class="{{ $fsel }}">
                        <option value="">All</option>
                        @foreach ($actions as $key => $text)<option value="{{ $key }}">{{ $text }}</option>@endforeach
                    </select>
                </label>
                <label class="col-span-2 block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Module</span>
                    <select wire:model.live="module" class="{{ $fsel }}">
                        <option value="">All</option>
                        @foreach ($modules as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                    </select>
                </label>
                <label class="col-span-2 block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Period</span>
                    <select wire:model.live="period" class="{{ $fsel }}">
                        @foreach ($periods as $key => $text)<option value="{{ $key }}">{{ $text }}</option>@endforeach
                    </select>
                </label>
                @if ($period === 'custom')
                    <label class="block min-w-0">
                        <span class="mb-1 block text-xs font-medium text-slate-600">From</span>
                        <input type="date" wire:model.live="dateFrom" max="{{ now()->toDateString() }}" class="{{ $fsel }}">
                    </label>
                    <label class="block min-w-0">
                        <span class="mb-1 block text-xs font-medium text-slate-600">To</span>
                        <input type="date" wire:model.live="dateTo" max="{{ now()->toDateString() }}" class="{{ $fsel }}">
                    </label>
                @endif
            </x-admin.filters-popup>

            <div class="relative min-w-0 flex-1">
                <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search person, IP or description"
                       aria-label="Search audit trail"
                       class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $field }}">
            </div>

            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                    class="{{ $btnPrimary }} shrink-0" title="Download these {{ number_format($page->total()) }} {{ Str::plural('entry', $page->total()) }} as a CSV file">
                <i class="bx bx-download text-base" wire:loading.remove wire:target="export" aria-hidden="true"></i>
                <i class="bx bx-loader-alt animate-spin text-base" wire:loading wire:target="export" aria-hidden="true"></i>
                <span class="sm:hidden">CSV</span>
                <span class="hidden sm:inline">Download CSV</span>
            </button>
        </div>

        {{-- Table: the Workflow review table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity lg:block"
             wire:loading.class="opacity-60" wire:loading.attr="aria-busy" wire:target="{{ $loadTargets }}">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs">
                    <tr>
                        <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                        <x-admin.sort-th key="user" label="User" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <x-admin.sort-th key="action" label="Action" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5">
                            <x-admin.legend-tip label="What the actions mean" note="What happened in each entry." :items="\App\Support\Legends::auditActions()" />
                        </x-admin.sort-th>
                        <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Description</th>
                        <x-admin.sort-th key="module" label="Module" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5">
                            <x-admin.legend-tip label="What the modules mean" note="The part of DocuMate each entry is about." :items="\App\Support\Legends::auditModules()" />
                        </x-admin.sort-th>
                        <x-admin.sort-th key="date" label="Date" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <th scope="col" class="w-10 py-2.5 pl-1 pr-4"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        @php
                            $open = $openRow === $r['key'];
                            $hasDetail = $r['changes'] || $r['ip'] || $r['agent'] || $r['link'];
                            [$mIcon, $mClass] = $moduleStyle($r['module']);
                        @endphp
                        <tr wire:key="row-{{ $r['key'] }}" @if ($hasDetail) wire:click="toggleRow('{{ $r['key'] }}')" @endif
                            class="transition-colors {{ $open ? 'bg-[#f0f4fb]' : ($hasDetail ? 'cursor-pointer hover:bg-slate-50' : '') }}">
                            <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ number_format(($page->firstItem() ?? 1) + $loop->index) }}</td>
                            <td class="px-4 py-3">
                                @include('livewire.admin.partials.audit-user', ['r' => $r, 'size' => 'h-8 w-8 text-xs'])
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $actionBadge($r['action']) }}"><i class="bx {{ $actionIcon[$r['action']] ?? 'bx-circle' }}" aria-hidden="true"></i>{{ $r['action_label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="line-clamp-2 max-w-[420px] break-words text-slate-700">{{ $r['description'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex max-w-[180px] items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $mClass }}"><i class="bx {{ $mIcon }}" aria-hidden="true"></i><span class="truncate">{{ $r['module'] }}</span></span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3" title="{{ $r['at']->format('M j, Y g:i:s A') }}">
                                <p class="tabular-nums text-slate-700">{{ $r['at']->format('M j, Y') }}</p>
                                <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ $r['at']->format('g:i A') }}</p>
                            </td>
                            <td class="py-3 pl-1 pr-4 text-slate-400">
                                @if ($hasDetail)
                                    <button type="button" wire:click.stop="toggleRow('{{ $r['key'] }}')" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-label="{{ $open ? 'Hide' : 'Show' }} details"
                                            class="grid h-7 w-7 place-items-center rounded-md hover:bg-slate-100 hover:text-slate-700 {{ $focus }}">
                                        <i class="bx bx-chevron-down text-lg transition-transform {{ $open ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($open)
                            <tr wire:key="detail-{{ $r['key'] }}" class="bg-[#f0f4fb]">
                                <td></td>
                                <td colspan="6" class="px-4 pb-4 pt-0">
                                    @include('livewire.admin.partials.audit-detail', ['r' => $r, 'show' => $show])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-sm text-slate-500">{{ $emptyTitle }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cards on narrower screens, as on Workflow review: tap one for its details --}}
        <ul class="grid gap-3 transition-opacity md:grid-cols-2 lg:hidden" aria-label="Audit trail"
            wire:loading.class="opacity-60" wire:loading.attr="aria-busy" wire:target="{{ $loadTargets }}">
            @forelse ($rows as $r)
                @php
                    $open = $openRow === $r['key'];
                    $hasDetail = $r['changes'] || $r['ip'] || $r['agent'] || $r['link'];
                    [$mIcon, $mClass] = $moduleStyle($r['module']);
                @endphp
                <li wire:key="mcard-{{ $r['key'] }}"
                    class="rounded-2xl border bg-white {{ $open ? 'border-[#2A57B4] bg-[#f0f4fb] md:col-span-2' : 'border-slate-200' }}">
                    <button type="button" @if ($hasDetail) wire:click="toggleRow('{{ $r['key'] }}')" aria-expanded="{{ $open ? 'true' : 'false' }}" @endif
                            class="flex w-full flex-col rounded-2xl p-4 text-left {{ $focus }} {{ $hasDetail && ! $open ? 'active:bg-slate-50' : '' }} {{ $hasDetail ? '' : 'cursor-default' }}">
                        <span class="flex w-full items-start gap-3">
                            <span class="min-w-0 flex-1">
                                @include('livewire.admin.partials.audit-user', ['r' => $r, 'size' => 'h-10 w-10 text-[13px]'])
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $actionBadge($r['action']) }}"><i class="bx {{ $actionIcon[$r['action']] ?? 'bx-circle' }} text-xs" aria-hidden="true"></i>{{ $r['action_label'] }}</span>
                        </span>

                        <span class="mt-3 block break-words text-sm leading-snug text-slate-700">{{ $r['description'] }}</span>

                        <span class="mt-3 flex w-full items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="inline-flex min-w-0 items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $mClass }}"><i class="bx {{ $mIcon }}" aria-hidden="true"></i><span class="truncate">{{ $r['module'] }}</span></span>
                            <span class="shrink-0 text-xs tabular-nums text-slate-500">#{{ number_format(($page->firstItem() ?? 1) + $loop->index) }} · {{ $r['at']->format('M j, Y g:i A') }}</span>
                        </span>
                    </button>
                    @if ($open)
                        <div class="px-4 pb-4">
                            @include('livewire.admin.partials.audit-detail', ['r' => $r, 'show' => $show])
                        </div>
                    @endif
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 md:col-span-2">{{ $emptyTitle }}</li>
            @endforelse
        </ul>

        @if ($page->hasPages())
            <div>{{ $page->links() }}</div>
        @endif
    </div>
</div>
