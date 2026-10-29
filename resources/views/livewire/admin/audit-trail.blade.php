@php
    use Illuminate\Support\Str;

    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';
    $pill = 'flex items-center gap-1 rounded-full bg-slate-100 py-1 pl-3 pr-1 text-sm transition focus-within:ring-2 focus-within:ring-[#2A57B4]';
    $pillSelect = 'max-w-[7rem] cursor-pointer truncate rounded-full border-0 bg-transparent py-1 pl-1 pr-7 text-sm font-semibold text-slate-800 focus:ring-0';

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
        'Feedback & Issues' => ['bx-message-square-dots', 'bg-pink-50 text-pink-700'],
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
    $roleAvatar = fn ($role, $user) => match (true) {
        $user === 'System' => 'bg-slate-400',
        strtolower((string) $role) === 'admin' => 'bg-gradient-to-br from-[#2A57B4] to-indigo-500',
        strtolower((string) $role) === 'student' => 'bg-gradient-to-br from-sky-500 to-cyan-400',
        strtolower((string) $role) === 'officer' => 'bg-gradient-to-br from-amber-500 to-orange-400',
        default => 'bg-gradient-to-br from-emerald-500 to-teal-400',
    };
    $show = fn ($v) => $v === null || $v === '' ? '(empty)' : (is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v));
@endphp

<div class="mx-auto max-w-7xl">

    <x-admin.page-header title="Audit Trail"
        description="Every sign-in, record change, export and transaction step, with who did it and when. Entries can't be edited or deleted.">
        <x-slot:actions>
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full border-2 border-[#2A57B4] px-4 py-2 text-sm font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4] hover:text-white disabled:opacity-60 sm:w-auto">
                <i class="bx bx-download" wire:loading.remove wire:target="export"></i>
                <i class="bx bx-loader-alt animate-spin" wire:loading wire:target="export"></i>
                Download CSV
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Today --}}
    @php
        $todayTiles = [
            ['Events today', $today['events'], 'bx-pulse', 'from-[#2A57B4] to-[#3b6fd8]', null],
            ['Sign-ins', $today['signins'], 'bx-log-in', 'from-emerald-500 to-teal-400', 'login'],
            ['Record changes', $today['changes'], 'bx-edit-alt', 'from-amber-400 to-orange-400', 'updated'],
            ['Failed sign-ins', $today['failed'], 'bx-shield-x', $today['failed'] ? 'from-rose-500 to-pink-500' : 'from-slate-400 to-slate-500', 'login_failed'],
        ];
    @endphp
    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($todayTiles as [$label, $value, $icon, $gradient, $filter])
            <button type="button" @if ($filter) wire:click="$set('action', '{{ $filter }}')" @else wire:click="$set('period', 'today')" @endif
                    class="relative overflow-hidden rounded-xl bg-gradient-to-br {{ $gradient }} p-4 text-left text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <i class="bx {{ $icon }} pointer-events-none absolute -bottom-3 -right-2 text-6xl text-white/20" aria-hidden="true"></i>
                <p class="text-xs font-medium text-white/85">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($value) }}</p>
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
        {{-- Search keeps the right-hand slot from lg up; only the filter pills wrap --}}
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <label class="{{ $pill }} {{ $role !== '' ? '!bg-[#2A57B4] [&>span]:!text-white [&>select]:!text-white [&_option]:text-slate-800' : '' }}">
                <span class="text-slate-500">Role</span>
                <select wire:model.live="role" class="{{ $pillSelect }}">
                    <option value="">All</option>
                    @foreach ($roles as $r)<option value="{{ $r }}">{{ Str::headline($r) }}</option>@endforeach
                </select>
            </label>
            <label class="{{ $pill }} {{ $action !== '' ? '!bg-[#2A57B4] [&>span]:!text-white [&>select]:!text-white [&_option]:text-slate-800' : '' }}">
                <span class="text-slate-500">Action</span>
                <select wire:model.live="action" class="{{ $pillSelect }}">
                    <option value="">All</option>
                    @foreach ($actions as $key => $text)<option value="{{ $key }}">{{ $text }}</option>@endforeach
                </select>
            </label>
            <label class="{{ $pill }} {{ $module !== '' ? '!bg-[#2A57B4] [&>span]:!text-white [&>select]:!text-white [&_option]:text-slate-800' : '' }}">
                <span class="text-slate-500">Module</span>
                <select wire:model.live="module" class="{{ $pillSelect }}">
                    <option value="">All</option>
                    @foreach ($modules as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                </select>
            </label>
            <label class="{{ $pill }} {{ $period !== '' ? '!bg-[#2A57B4] [&>span]:!text-white [&>select]:!text-white [&_option]:text-slate-800' : '' }}">
                <span class="text-slate-500">Period</span>
                <select wire:model.live="period" class="{{ $pillSelect }}">
                    @foreach ($periods as $key => $text)<option value="{{ $key }}">{{ $text }}</option>@endforeach
                </select>
            </label>

            @if ($period === 'custom')
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="dateFrom" aria-label="From date" class="{{ $field }}">
                    <span class="text-sm text-slate-400">to</span>
                    <input type="date" wire:model.live="dateTo" aria-label="To date" class="{{ $field }}">
                </div>
            @endif

            </div>

            <div class="relative order-first w-full shrink-0 lg:order-none lg:w-48 2xl:w-64">
                <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search person, IP, or description"
                       aria-label="Search audit trail" class="w-full pl-9 pr-3 {{ $field }}">
            </div>
        </div>

        <div class="mt-2 flex items-center justify-between gap-3 px-1 text-xs text-slate-500">
            <span>{{ number_format($page->total()) }} {{ Str::plural('entry', $page->total()) }}</span>
            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="font-medium hover:text-slate-800">Clear filters</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" wire:loading.class="opacity-60" wire:target="search,action,module,role,period,dateFrom,dateTo,clearFilters,nextPage,previousPage">
        <div class="hidden grid-cols-[8.5rem_minmax(0,1.1fr)_6.5rem_minmax(0,2.6fr)_minmax(0,1fr)_1.25rem] gap-4 border-b border-blue-100 bg-blue-50/60 px-4 py-2.5 text-xs font-semibold text-[#2A57B4] lg:grid">
            <span>Date</span><span>User</span><span>Action</span><span>Description</span><span>Module</span><span></span>
        </div>

        @forelse ($rows as $r)
            @php $open = $openRow === $r['key']; $hasDetail = $r['changes'] || $r['ip'] || $r['agent'] || $r['link']; @endphp
            <div wire:key="row-{{ $r['key'] }}" class="border-b border-slate-100 last:border-b-0">
                <button type="button" @if ($hasDetail) wire:click="toggleRow('{{ $r['key'] }}')" aria-expanded="{{ $open ? 'true' : 'false' }}" @endif
                        class="grid w-full grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-1 px-4 py-3 text-left text-sm transition lg:grid-cols-[8.5rem_minmax(0,1.1fr)_6.5rem_minmax(0,2.6fr)_minmax(0,1fr)_1.25rem] lg:items-center lg:gap-4
                            {{ $open ? 'bg-slate-50' : ($hasDetail ? 'hover:bg-slate-50' : 'cursor-default') }}">

                    <span class="order-3 text-xs text-slate-500 lg:order-none lg:text-sm lg:text-slate-600" title="{{ $r['at']->format('M j, Y g:i:s A') }}">
                        <span class="lg:block">{{ $r['at']->format('M j, Y') }}</span>
                        <span class="lg:block lg:text-xs lg:text-slate-400">{{ $r['at']->format('g:i A') }}</span>
                    </span>

                    <span class="order-1 flex min-w-0 items-center gap-2.5 lg:order-none">
                        @if ($r['user'] === 'System')
                            <x-user-avatar name="System" class="h-8 w-8 {{ $roleAvatar($r['role'], 'System') }}"><i class="bx bx-cog text-base"></i></x-user-avatar>
                        @else
                            <x-user-avatar :src="$r['avatar']" :name="$r['user']" class="h-8 w-8 text-[11px] {{ $roleAvatar($r['role'], $r['user']) }}" />
                        @endif
                        <span class="min-w-0">
                            <span class="block truncate font-medium {{ $r['user'] === 'System' ? 'text-slate-500' : 'text-slate-900' }}">{{ $r['user'] }}</span>
                            @if ($r['role'])<span class="hidden truncate text-xs text-slate-400 lg:block">{{ Str::headline($r['role']) }}</span>@endif
                        </span>
                    </span>

                    <span class="order-2 justify-self-end lg:order-none lg:justify-self-start">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $actionBadge($r['action']) }}"><i class="bx {{ $actionIcon[$r['action']] ?? 'bx-circle' }}"></i>{{ $r['action_label'] }}</span>
                    </span>

                    <span class="order-4 col-span-2 min-w-0 text-slate-700 lg:order-none lg:col-span-1">
                        <span class="line-clamp-2 break-words">{{ $r['description'] }}</span>
                    </span>

                    @php [$mIcon, $mClass] = $moduleStyle($r['module']); @endphp
                    <span class="order-3 min-w-0 justify-self-end lg:order-none lg:justify-self-start">
                        <span class="inline-flex max-w-full items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $mClass }}"><i class="bx {{ $mIcon }}"></i><span class="truncate">{{ $r['module'] }}</span></span>
                    </span>

                    <span class="hidden text-slate-400 lg:block">
                        @if ($hasDetail)<i class="bx bx-chevron-down text-lg transition-transform {{ $open ? 'rotate-180' : '' }}"></i>@endif
                    </span>
                </button>

                @if ($open)
                    <div class="space-y-3 bg-slate-50 px-4 pb-4 text-sm lg:pl-[calc(8.5rem+2rem)]">
                        @if ($r['changes'])
                            @if ($r['is_diff'])
                                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-slate-50 text-left text-slate-500">
                                            <tr><th class="px-3 py-2 font-medium">Field</th><th class="px-3 py-2 font-medium">Before</th><th class="px-3 py-2 font-medium">After</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($r['changes'] as $fieldName => $pair)
                                                <tr>
                                                    <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-700">{{ Str::headline($fieldName) }}</td>
                                                    <td class="max-w-xs break-words px-3 py-2 text-rose-700"><span class="bg-rose-50">{{ $show($pair[0] ?? null) }}</span></td>
                                                    <td class="max-w-xs break-words px-3 py-2 text-emerald-700"><span class="bg-emerald-50">{{ $show($pair[1] ?? null) }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 rounded-lg border border-slate-200 bg-white p-3 text-xs">
                                    @foreach ($r['changes'] as $k => $v)
                                        <dt class="font-medium text-slate-500">{{ Str::headline((string) $k) }}</dt>
                                        <dd class="break-words text-slate-700">{{ $show($v) }}</dd>
                                    @endforeach
                                </dl>
                            @endif
                        @endif

                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                            @if ($r['email'])<span><i class="bx bx-envelope"></i> {{ $r['email'] }}</span>@endif
                            @if ($r['ip'])<span><i class="bx bx-globe"></i> {{ $r['ip'] }}</span>@endif
                            @if ($r['agent'])<span class="min-w-0 max-w-full truncate" title="{{ $r['agent'] }}"><i class="bx bx-desktop"></i> {{ Str::limit($r['agent'], 90) }}</span>@endif
                            @if ($r['link'])<a href="{{ $r['link'] }}" wire:navigate class="font-medium text-[#2A57B4] hover:underline">Open transaction →</a>@endif
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="px-4 py-14 text-center">
                <i class="bx bx-history text-4xl text-slate-300"></i>
                <p class="mt-2 text-sm font-medium text-slate-700">{{ $hasFilters ? 'No entries match your filters' : 'No activity recorded yet' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $hasFilters ? 'Widen the period or clear the filters.' : 'Sign-ins and changes will appear here as people use DocuMate.' }}</p>
            </div>
        @endforelse
    </div>

    @if ($page->hasPages())
        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" wire:click="previousPage" @disabled($page->onFirstPage())
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Previous</button>
            <span class="text-sm text-slate-500">Page {{ $page->currentPage() }} of {{ $page->lastPage() }}</span>
            <button type="button" wire:click="nextPage" @disabled(! $page->hasMorePages())
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Next</button>
        </div>
    @endif
</div>
