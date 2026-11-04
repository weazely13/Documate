@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $dotFor = fn (string $result) => match ($result) {
        'completed' => 'bg-emerald-600',
        'closed' => 'bg-slate-400',
        default => 'bg-[#2A57B4]',
    };
@endphp

<div class="mx-auto {{ $activeTab === 'logbooks' ? 'max-w-5xl' : 'max-w-7xl' }}">
    @if ($activeTab === 'logbooks')

        <x-admin.page-header
            title="Office Logbooks"
            description="VPSD office logbooks uploaded from Excel exports.">
            <x-slot:actions>
                <button type="button" wire:click="setActiveTab('transactions')"
                        class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">&larr; Transaction records</button>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.logbook-manager />

    @else

        {{-- ============ THE ARCHIVE ============
             Every transaction, filed twice over: a drawer per student (who transacted), and inside it a folder per
             document type. The folder strip above files the whole archive by type. --}}
        @php
            $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';
            $tints = [
                ['bg-[#dcecfa]', 'text-[#1E4090]'],
                ['bg-[#e6e0f5]', 'text-[#5b3fa0]'],
                ['bg-[#d9f3e1]', 'text-[#1f6b3a]'],
                ['bg-[#fef3c7]', 'text-[#8a5a00]'],
                ['bg-[#fde0ec]', 'text-[#9d2457]'],
                ['bg-[#fdece3]', 'text-[#9a4521]'],
            ];
            $tintFor = fn (string $type) => $tints[crc32($type) % count($tints)];
            // The dashboard's folder colours, one per tint: back panel and tab, front panel, ink.
            $themes = [
                ['back' => 'bg-[#bcd5f2]', 'front' => 'from-[#e6f0fc] to-[#dcecfa]', 'ink' => 'text-[#1E4090]'],
                ['back' => 'bg-[#d3c9ef]', 'front' => 'from-[#eee9f9] to-[#e6e0f5]', 'ink' => 'text-[#5b3fa0]'],
                ['back' => 'bg-[#b6e3c5]', 'front' => 'from-[#e6f7eb] to-[#d9f3e1]', 'ink' => 'text-[#1f6b3a]'],
                ['back' => 'bg-[#f3df93]', 'front' => 'from-[#fefae4] to-[#fef3c7]', 'ink' => 'text-[#8a5a00]'],
                ['back' => 'bg-[#f6c3d8]', 'front' => 'from-[#fdeef4] to-[#fde0ec]', 'ink' => 'text-[#9d2457]'],
                ['back' => 'bg-[#f6cdb5]', 'front' => 'from-[#fef4ee] to-[#fdece3]', 'ink' => 'text-[#9a4521]'],
            ];
            $folderTheme = fn (string $type) => $themes[crc32($type) % count($themes)];
            $tagFor = fn (array $r) => match (true) {
                $r['result'] === 'completed' => 'bg-emerald-600 text-white',
                $r['status'] === 'Rejected' => 'bg-rose-600 text-white',
                $r['result'] === 'closed' => 'bg-slate-500 text-white',
                $r['status'] === 'Waiting on Student' => 'bg-amber-500 text-white',
                default => 'bg-[#2A57B4] text-white',
            };
            $activeFilters = (int) ($result !== 'all') + (int) ($period !== 'all') + (int) ($mode !== 'all') + (int) ($folder !== '' && $by !== 'folder');
            $menuBtn = 'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition [@media(pointer:coarse)]:h-11 ' . $focus;
            $menu = 'absolute left-0 z-30 mt-1.5 max-h-80 w-64 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-lg';
            $menuItem = 'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-slate-700 hover:bg-slate-50';
        @endphp

        <div x-data class="pb-24">
            <h1 class="sr-only">Transaction records</h1>
            @php
                // Inside a folder, a student's account, or All documents: the filters and the view switch apply.
                // A student's account has its own Folders / Cards / Table switch and Sort, so the bar is just the search.
                $inside = in_array($level, ['folder', 'all'], true);
                $showType = $level === 'all';
            @endphp

            {{-- How the archive is filed, with Office logbooks on the same line --}}
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm sm:flex-none" role="tablist" aria-label="File the archive by">
                    @foreach (['folder' => ['bxs-folder', 'By folder', 'Folders'], 'student' => ['bxs-user-account', 'By student', 'Students'], 'all' => ['bxs-file', 'All documents', 'All']] as $b => [$bIcon, $bLabel, $bShort])
                        <button type="button" role="tab" wire:click="setBy('{{ $b }}')" aria-selected="{{ $by === $b ? 'true' : 'false' }}"
                                class="inline-flex h-9 min-w-0 flex-1 items-center justify-center gap-2 rounded-lg px-2 text-sm font-medium transition sm:flex-none sm:px-4 [@media(pointer:coarse)]:h-11 {{ $focus }}
                                    {{ $by === $b ? 'bg-[#2A57B4] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i class="bx {{ $bIcon }} text-base max-sm:!hidden" aria-hidden="true"></i><span class="sm:hidden">{{ $bShort }}</span><span class="hidden sm:inline">{{ $bLabel }}</span>
                        </button>
                    @endforeach
                </div>
                <button type="button" wire:click="setActiveTab('logbooks')" aria-label="Office logbooks"
                        class="inline-flex h-[2.875rem] shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 sm:px-4 [@media(pointer:coarse)]:h-[3.375rem] {{ $focus }}">
                    <i class="bx bx-book-bookmark text-lg text-[#2A57B4]" aria-hidden="true"></i><span class="hidden sm:inline">Office logbooks</span>
                </button>
            </div>

            {{-- Search; inside a folder, account or All documents also the filters and the view --}}
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3">
                <div class="flex items-center gap-2">
                    <div class="relative min-w-0 flex-1">
                        <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" aria-hidden="true"></i>
                        <input type="search" wire:model.live.debounce.300ms="search" aria-label="Search the archive"
                               placeholder="{{ match ($level) { 'folders' => 'Search folders, names, codes or scanned text', 'students' => 'Search students by name or number', default => 'Search name, student no., document, code or scanned text' } }}"
                               class="h-10 w-full rounded-lg border-slate-300 pl-10 pr-3 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
                    </div>
                    @if ($inside)
                        <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="View">
                            @foreach (['cards' => ['bx-grid-alt', 'Cards'], 'table' => ['bx-list-ul', 'Table']] as $v => [$vIcon, $vLabel])
                                <button type="button" wire:click="setView('{{ $v }}')" aria-pressed="{{ $view === $v ? 'true' : 'false' }}" title="{{ $vLabel }}"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition [@media(pointer:coarse)]:h-10 {{ $focus }}
                                            {{ $view === $v ? 'bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-800' }}">
                                    <i class="bx {{ $vIcon }} text-lg" aria-hidden="true"></i><span class="hidden sm:inline">{{ $vLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($inside)
                <div class="-mx-1 mt-2 flex items-center gap-2 overflow-x-auto px-1 pb-0.5 sm:flex-wrap sm:overflow-visible">
                    @if ($showType)
                        {{-- Document type: inside a student's account and on All documents --}}
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = !open" :aria-expanded="open"
                                    class="{{ $menuBtn }} {{ $folder !== '' ? 'border-[#2A57B4]/40 bg-[#eef3fc] text-[#1E4090]' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                <i class="bx bx-folder text-base" aria-hidden="true"></i>
                                <span class="max-w-[12rem] truncate">{{ $folder !== '' ? $folder : 'Document type' }}</span>
                                <i class="bx bx-chevron-down text-base text-slate-400" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity class="{{ $menu }} w-72" role="menu">
                                <button type="button" wire:click="$set('folder', '')" @click="open = false" class="{{ $menuItem }}" role="menuitem">
                                    <i class="bx bx-check text-base {{ $folder === '' ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i> Every type
                                </button>
                                @foreach (($level === 'student' && $openStudent ? $openStudent['folders']->map(fn ($f) => ['type' => $f['type'], 'count' => $f['forms']->count()]) : $folders) as $f)
                                    <button type="button" wire:click="$set('folder', @js($f['type']))" @click="open = false" class="{{ $menuItem }}" role="menuitem">
                                        <i class="bx bx-check text-base {{ $folder === $f['type'] ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i>
                                        <span class="min-w-0 flex-1 truncate">{{ $f['type'] }}</span>
                                        <span class="text-xs tabular-nums text-slate-400">{{ $f['count'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach ([['result', $results, $result, 'bx-check-circle', 'Result'], ['mode', $modes, $mode, 'bx-devices', 'Mode']] as [$prop, $options, $current, $icon, $name])
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="{{ $name }}: {{ $options[$current] }}"
                                    class="{{ $menuBtn }} {{ $current !== 'all' ? 'border-[#2A57B4]/40 bg-[#eef3fc] text-[#1E4090]' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                <i class="bx {{ $icon }} text-base" aria-hidden="true"></i>
                                <span class="whitespace-nowrap">{{ $current !== 'all' ? $options[$current] : $name }}</span>
                                <i class="bx bx-chevron-down text-base text-slate-400" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity class="{{ $menu }}" role="menu">
                                @foreach ($options as $value => $label)
                                    <button type="button" wire:click="$set('{{ $prop }}', '{{ $value }}')" @click="open = false" class="{{ $menuItem }}" role="menuitem">
                                        <i class="bx bx-check text-base {{ $current === $value ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i> {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Date: quick ranges, or pick the from and to dates --}}
                    <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Date: {{ $dateLabel }}"
                                class="{{ $menuBtn }} {{ $period !== 'all' ? 'border-[#2A57B4]/40 bg-[#eef3fc] text-[#1E4090]' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                            <i class="bx bx-calendar text-base" aria-hidden="true"></i>
                            <span class="whitespace-nowrap">{{ $period !== 'all' ? $dateLabel : 'Date' }}</span>
                            <i class="bx bx-chevron-down text-base text-slate-400" aria-hidden="true"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 z-30 mt-1.5 w-72 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-lg" role="dialog" aria-label="Date">
                            @foreach (array_diff_key($periods, ['custom' => 1]) as $value => $label)
                                <button type="button" wire:click="$set('period', '{{ $value }}')" @click="open = false" class="{{ $menuItem }}">
                                    <i class="bx bx-check text-base {{ $period === $value ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i> {{ $label }}
                                </button>
                            @endforeach
                            <div class="mt-1 border-t border-slate-100 px-3 pb-2 pt-2.5">
                                <p class="mb-1.5 flex items-center gap-2 text-xs font-medium text-slate-600">
                                    <i class="bx bx-check text-base {{ $period === 'custom' ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i> Date range
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="block min-w-0"><span class="mb-0.5 block text-[11px] text-slate-500">From</span>
                                        <input type="date" wire:model.live="dateFrom" max="{{ $dateTo ?: now()->toDateString() }}" class="h-9 w-full rounded-lg border-slate-300 px-2 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20"></label>
                                    <label class="block min-w-0"><span class="mb-0.5 block text-[11px] text-slate-500">To</span>
                                        <input type="date" wire:model.live="dateTo" min="{{ $dateFrom }}" max="{{ now()->toDateString() }}" class="h-9 w-full rounded-lg border-slate-300 px-2 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($activeFilters || trim($search) !== '')
                        <button type="button" wire:click="resetFilters"
                                class="inline-flex h-9 shrink-0 items-center gap-1 rounded-lg px-2.5 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-800 [@media(pointer:coarse)]:h-11 {{ $focus }}">
                            <i class="bx bx-x text-lg" aria-hidden="true"></i> Reset filters
                        </button>
                    @endif
                </div>
                @endif
            </div>

            @php
                $share = ['tagFor' => $tagFor, 'tintFor' => $tintFor, 'focus' => $focus];
                $cardGrid = 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5';
                $tableHead = 'mb-1.5 hidden grid-cols-[minmax(0,1fr)_10rem_6rem_7rem_10rem] gap-4 px-[0.9375rem] text-xs font-medium text-slate-500 md:grid';
                $backBtn = 'inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 ' . $focus;
            @endphp

            @if ($level === 'folders')
                {{-- ===== By folder: only the folders. Open one to see the documents inside. ===== --}}
                <h2 class="mb-3 text-[15px] font-semibold text-slate-900">Folders
                    <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $folders->count() }} · {{ number_format($allCount) }} {{ Str::plural('document', $allCount) }}</span>
                </h2>
                @if ($folders->isEmpty())
                    @include('livewire.admin.partials.archive-empty')
                @else
                    <ul class="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 sm:gap-x-4 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($folders as $f)
                            @include('livewire.admin.partials.archive-folder', ['f' => $f, 'click' => 'openFolder(' . \Illuminate\Support\Js::from($f['type']) . ')'])
                        @endforeach
                    </ul>
                @endif

            @elseif ($level === 'all')
                <h2 class="mb-3 text-[15px] font-semibold text-slate-900">{{ $folder !== '' ? $folder : 'All documents' }}
                    <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ number_format($totalCount) }} {{ Str::plural('document', $totalCount) }} · {{ $studentCount }} {{ Str::plural('student', $studentCount) }}</span>
                </h2>
            @endif

            @if (in_array($level, ['folder', 'all'], true))
                @if ($level === 'folder')
                {{-- ===== Inside one folder: its documents, each saying whose it is ===== --}}
                @php [$fBg, $fInk] = $tintFor($folder); @endphp
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="closeOpened" class="{{ $backBtn }}"><i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> All folders</button>
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $fBg }}"><i class="bx bxs-folder-open text-xl {{ $fInk }}" aria-hidden="true"></i></span>
                    <h2 class="min-w-0 text-lg font-semibold text-slate-900">{{ $folder }}
                        <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ number_format($totalCount) }} {{ Str::plural('document', $totalCount) }} · {{ $studentCount }} {{ Str::plural('student', $studentCount) }}</span>
                    </h2>
                </div>
                @endif
                @if ($pageItems->isEmpty())
                    @include('livewire.admin.partials.archive-empty')
                @elseif ($view === 'cards')
                    <ul class="{{ $cardGrid }}">
                        @foreach ($pageItems as $record)
                            @include('livewire.admin.partials.archive-card', $share + ['record' => $record, 'showStudent' => true, 'showDocType' => $level === 'all'])
                        @endforeach
                    </ul>
                @else
                    <div class="{{ $tableHead }}"><span class="pl-[2.375rem]">{{ $level === 'all' ? 'Student and document' : 'Student' }}</span><span>Code</span><span>Mode</span><span>Date</span><span>Status</span></div>
                    <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @foreach ($pageItems as $record)
                            @include('livewire.admin.partials.archive-row', $share + ['record' => $record, 'showStudent' => true, 'showDocType' => $level === 'all'])
                        @endforeach
                    </ul>
                @endif
            @endif

            @if ($level === 'students')
                {{-- ===== By student: only the students. Open one to see their account. ===== --}}
                <h2 class="mb-3 text-[15px] font-semibold text-slate-900">Students
                    <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ number_format($totalCount) }} · {{ number_format($recordCount) }} {{ Str::plural('transaction', $recordCount) }}</span>
                </h2>
                @if ($pageItems->isEmpty())
                    @include('livewire.admin.partials.archive-empty')
                @else
                    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($pageItems as $s)
                            <li wire:key="student-{{ $s['key'] }}">
                                {{-- A student's account, like an ID card: a tinted band, the photo over it, then the figures. --}}
                                @php
                                    $docTotal = $s['forms']->count();
                                    $donePct = $docTotal ? (int) round($s['completed_count'] / $docTotal * 100) : 0;
                                    $band = $folderTheme((string) $s['student_name']);
                                @endphp
                                <button type="button" wire:click="openStudent('{{ $s['key'] }}')"
                                        class="group flex h-full w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#2A57B4]/30 hover:shadow-[0_14px_30px_-16px_rgba(13,37,61,0.45)] motion-reduce:hover:translate-y-0 {{ $focus }}">
                                    <span class="relative block h-16 bg-gradient-to-br {{ $band['front'] }}" aria-hidden="true">
                                        <span class="absolute inset-0 opacity-50 [background-image:radial-gradient(rgba(13,37,61,0.12)_1px,transparent_1px)] [background-size:10px_10px]"></span>
                                        <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-md bg-white/80 px-1.5 py-0.5 text-[11px] font-medium {{ $band['ink'] }}">
                                            <i class="bx bxs-folder text-xs"></i>{{ $s['folders']->count() }} {{ Str::plural('folder', $s['folders']->count()) }}
                                        </span>
                                    </span>
                                    <span class="flex flex-1 flex-col px-4 pb-4">
                                        <x-dm.avatar :user="$s['user']" :name="$s['student_name']" class="-mt-10 h-20 w-20 text-xl ring-4 ring-white shadow-sm" />
                                        <span class="mt-2.5 block truncate text-base font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $s['student_name'] }}</span>
                                        <span class="block truncate text-xs tabular-nums text-slate-500">{{ $s['student_number'] }}</span>

                                        <span class="mt-4 grid grid-cols-3 divide-x divide-slate-200 rounded-lg border border-slate-200">
                                            <span class="px-2 py-2 text-center"><span class="block text-lg font-semibold tabular-nums text-slate-900">{{ $docTotal }}</span><span class="block text-[11px] text-slate-500">{{ Str::plural('document', $docTotal) }}</span></span>
                                            <span class="px-2 py-2 text-center"><span class="block text-lg font-semibold tabular-nums text-[#1f6b3a]">{{ $s['completed_count'] }}</span><span class="block text-[11px] text-slate-500">completed</span></span>
                                            <span class="px-2 py-2 text-center"><span class="block text-lg font-semibold tabular-nums text-[#1E4090]">{{ $s['open_count'] }}</span><span class="block text-[11px] text-slate-500">in progress</span></span>
                                        </span>

                                        <span class="mt-3 block" role="img" aria-label="{{ $donePct }}% completed">
                                            <span class="flex items-center justify-between text-[11px] text-slate-500"><span>Completed</span><span class="tabular-nums">{{ $donePct }}%</span></span>
                                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-emerald-500" style="width: {{ $donePct }}%"></span></span>
                                        </span>

                                        <span class="mt-auto flex items-center justify-between pt-4 text-xs text-slate-500">
                                            <span class="tabular-nums">Latest {{ $s['latest_label'] }}</span>
                                            <span class="inline-flex items-center gap-0.5 font-medium text-[#2A57B4]">Open account <i class="bx bx-chevron-right text-base transition group-hover:translate-x-0.5" aria-hidden="true"></i></span>
                                        </span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

            @elseif ($level === 'student')
                {{-- ===== Inside one student's account: their documents, filed by type ===== --}}
                @php $s = $openStudent; @endphp
                <div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <button type="button" wire:click="closeOpened" class="{{ $backBtn }} mb-4"><i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> All students</button>
                    <div class="flex flex-wrap items-center gap-4">
                        <x-dm.avatar :user="$s['user']" :name="$s['student_name']" class="h-14 w-14 text-base" />
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-xl font-semibold tracking-[-0.01em] text-slate-900">{{ $s['student_name'] }}</h2>
                            <p class="text-sm tabular-nums text-slate-500">{{ $s['student_number'] }} · latest {{ $s['latest_label'] }}</p>
                        </div>
                        <dl class="grid w-full grid-cols-3 gap-2 text-center sm:w-auto">
                            <div class="rounded-lg bg-[#f2f5fb] px-4 py-2"><dt class="text-[11px] text-slate-500">Documents</dt><dd class="text-lg font-semibold tabular-nums text-slate-900">{{ $s['forms']->count() }}</dd></div>
                            <div class="rounded-lg bg-[#d9f3e1] px-4 py-2"><dt class="text-[11px] text-slate-600">Completed</dt><dd class="text-lg font-semibold tabular-nums text-[#1f6b3a]">{{ $s['completed_count'] }}</dd></div>
                            <div class="rounded-lg bg-[#dcecfa] px-4 py-2"><dt class="text-[11px] text-slate-600">In progress</dt><dd class="text-lg font-semibold tabular-nums text-[#1E4090]">{{ $s['open_count'] }}</dd></div>
                        </dl>
                    </div>
                </div>

                {{-- The account's own controls: Folders / Cards / Table, and a separate Sort. Inside an open folder only
                     the Sort (the documents there, by date or code). --}}
                @php
                    $sortNow = array_key_exists($sort, $sortOptions) ? $sort : 'newest';
                    $inFolder = $folder !== '';
                    $accountFolders = match ($sortNow) {
                        'az' => $s['folders']->sortBy(fn ($f) => mb_strtolower($f['type']))->values(),
                        'za' => $s['folders']->sortByDesc(fn ($f) => mb_strtolower($f['type']))->values(),
                        default => $s['folders'],
                    };
                    if ($inFolder) {
                        $accountFolders = $accountFolders->map(fn ($f) => ['type' => $f['type'], 'forms' => match ($sortNow) {
                            'az' => $f['forms']->sortBy(fn ($r) => mb_strtolower($r['transaction_label']))->values(),
                            'za' => $f['forms']->sortByDesc(fn ($r) => mb_strtolower($r['transaction_label']))->values(),
                            default => $f['forms']->sortByDesc('date_sort')->values(),
                        }]);
                    }
                    $asTiles = $arrange === 'folders' && ! $inFolder;
                    $acctMode = $arrange === 'folders' ? 'folders' : $view;
                    $sortLabels = $inFolder ? ['newest' => 'Newest first', 'az' => 'Code A–Z', 'za' => 'Code Z–A'] : ['newest' => 'Newest first', 'az' => 'Name A–Z', 'za' => 'Name Z–A'];
                @endphp
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2">
                        @if ($inFolder)
                            <button type="button" wire:click="$set('folder', '')" class="{{ $backBtn }}"><i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> All folders</button>
                        @endif
                        <h3 class="truncate text-[15px] font-semibold text-slate-900">
                            {{ $inFolder ? $folder : ($asTiles ? 'Folders' : 'Documents') }}
                            <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $asTiles ? $accountFolders->count() : $s['forms']->count() }}</span>
                        </h3>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @unless ($inFolder)
                            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="Show as">
                                @foreach (['folders' => ['bxs-folder', 'Folders'], 'cards' => ['bx-grid-alt', 'Cards'], 'table' => ['bx-list-ul', 'Table']] as $key => [$aIcon, $aLabel])
                                    <button type="button" wire:click="setArrange('{{ $key }}')" aria-pressed="{{ $acctMode === $key ? 'true' : 'false' }}" title="{{ $aLabel }}"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition [@media(pointer:coarse)]:h-10 {{ $focus }}
                                                {{ $acctMode === $key ? 'bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-800' }}">
                                        <i class="bx {{ $aIcon }} text-lg" aria-hidden="true"></i><span class="hidden sm:inline">{{ $aLabel }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endunless
                        {{-- Sort: its own button --}}
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Sort: {{ $sortLabels[$sortNow] }}"
                                    class="{{ $menuBtn }} border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                <i class="bx {{ $sortOptions[$sortNow][0] }} text-lg" aria-hidden="true"></i>
                                <span class="whitespace-nowrap">{{ $sortLabels[$sortNow] }}</span>
                                <i class="bx bx-chevron-down text-base text-slate-400" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-30 mt-1.5 w-48 rounded-xl border border-slate-200 bg-white p-1 text-sm shadow-lg" role="menu">
                                @foreach ($sortLabels as $key => $label)
                                    <button type="button" wire:click="setSort('{{ $key }}')" @click="open = false" class="{{ $menuItem }}" role="menuitem">
                                        <i class="bx bx-check text-base {{ $sortNow === $key ? 'text-[#2A57B4]' : 'invisible' }}" aria-hidden="true"></i> {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @if ($asTiles)
                    <ul class="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 sm:gap-x-4 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($accountFolders as $sf)
                            @include('livewire.admin.partials.archive-folder', [
                                'f' => [
                                    'type' => $sf['type'],
                                    'count' => $sf['forms']->count(),
                                    'paperless' => (bool) $sf['forms']->first()['paperless'],
                                    'latest' => $sf['forms']->first()['date_label'],
                                    'thumbs' => $sf['forms']->filter(fn ($r) => $r['thumb'])->take(2)->map(fn ($r) => ['src' => $r['thumb'], 'image' => $r['thumb_is_image']])->values(),
                                ],
                                'click' => '$set(\'folder\', ' . \Illuminate\Support\Js::from($sf['type']) . ')',
                            ])
                        @endforeach
                    </ul>
                @else
                <div class="space-y-6">
                    @foreach ($accountFolders as $sf)
                        @php [$sfBg, $sfInk] = $tintFor($sf['type']); @endphp
                        <section wire:key="acct-folder-{{ md5($sf['type']) }}" aria-label="{{ $sf['type'] }}">
                            {{-- Inside an open folder its name is already in the bar above --}}
                            <p class="mb-2.5 flex items-center gap-2 text-sm {{ $inFolder ? 'hidden' : '' }}">
                                <span class="grid h-7 w-7 place-items-center rounded-md {{ $sfBg }}"><i class="bx bxs-folder text-base {{ $sfInk }}" aria-hidden="true"></i></span>
                                <span class="min-w-0 truncate font-semibold text-slate-800">{{ $sf['type'] }}</span>
                                <span class="tabular-nums text-slate-400">{{ $sf['forms']->count() }}</span>
                            </p>
                            @if ($view === 'cards')
                                <ul class="{{ $cardGrid }}">
                                    @foreach ($sf['forms'] as $record)
                                        @include('livewire.admin.partials.archive-card', $share + ['record' => $record, 'showStudent' => false])
                                    @endforeach
                                </ul>
                            @else
                                <ul class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                    @foreach ($sf['forms'] as $record)
                                        @include('livewire.admin.partials.archive-row', $share + ['record' => $record, 'showStudent' => false])
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @endforeach
                </div>
                @endif
            @endif

            {{-- Pagination --}}
            @if ($totalPages > 1 && in_array($level, ['folder', 'students', 'all'], true))
                <div class="mt-5 flex items-center justify-between gap-3">
                    <p class="text-xs tabular-nums text-slate-500">Page {{ $currentPage }} of {{ $totalPages }}</p>
                    <div class="flex gap-2">
                        <button type="button" wire:click="previousPage" @disabled($currentPage === 1)
                                class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300 [@media(pointer:coarse)]:h-11 {{ $focus }}"><i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> Previous</button>
                        <button type="button" wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                                class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300 [@media(pointer:coarse)]:h-11 {{ $focus }}">Next <i class="bx bx-chevron-right text-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
            @endif
        </div>

    @endif
</div>
