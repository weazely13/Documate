{{-- Admin › Templates & Uploads › Uploads: signed paper forms brought to the office, filed like Transaction Records. --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';
    $themes = [
        ['back' => 'bg-[#bcd5f2]', 'front' => 'from-[#e6f0fc] to-[#dcecfa]', 'ink' => 'text-[#1E4090]'],
        ['back' => 'bg-[#d3c9ef]', 'front' => 'from-[#eee9f9] to-[#e6e0f5]', 'ink' => 'text-[#5b3fa0]'],
        ['back' => 'bg-[#b6e3c5]', 'front' => 'from-[#e6f7eb] to-[#d9f3e1]', 'ink' => 'text-[#1f6b3a]'],
        ['back' => 'bg-[#f3df93]', 'front' => 'from-[#fefae4] to-[#fef3c7]', 'ink' => 'text-[#8a5a00]'],
        ['back' => 'bg-[#f6c3d8]', 'front' => 'from-[#fdeef4] to-[#fde0ec]', 'ink' => 'text-[#9d2457]'],
        ['back' => 'bg-[#f6cdb5]', 'front' => 'from-[#fef4ee] to-[#fdece3]', 'ink' => 'text-[#9a4521]'],
    ];
    $folderTheme = fn (string $t) => $themes[crc32($t) % count($themes)];
    $tag = fn (array $r) => match ($r['status']) {
        'Verified' => ['Verified', 'bg-emerald-600 text-white'],
        'Reviewing' => ['Being checked', 'bg-[#2A57B4] text-white'],
        'Needs Re-upload' => ['Sent back', 'bg-rose-600 text-white'],
        default => ['Needs upload', 'bg-amber-500 text-white'],
    };
    $inside = in_array($level, ['folder', 'student', 'all'], true);
    $backBtn = 'inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 ' . $focus;
    $dateLabel = $dateFrom || $dateTo
        ? trim(($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M j') : 'Any') . ' – ' . ($dateTo ? \Carbon\Carbon::parse($dateTo)->format('M j, Y') : 'today'))
        : null;
@endphp

<div class="mx-auto w-full max-w-7xl pb-16">
    <x-admin.area-header name="documents" active="uploads" />

    {{-- Filing and status --}}
    <div class="mb-4 flex items-center justify-between gap-2 sm:flex-wrap">
        <div class="flex min-w-0 shrink-0 rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="tablist" aria-label="File uploads by">
            @foreach (['folder' => ['bxs-folder', 'By form', 'Forms'], 'student' => ['bxs-user-account', 'By student', 'Students'], 'all' => ['bxs-file', 'All uploads', 'All']] as $b => [$icon, $label, $short])
                <button type="button" role="tab" wire:click="setBy('{{ $b }}')" aria-selected="{{ $by === $b ? 'true' : 'false' }}"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg px-3 text-sm font-medium transition sm:px-4 [@media(pointer:coarse)]:h-11 {{ $focus }} {{ $by === $b ? 'bg-[#2A57B4] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bx {{ $icon }} text-base max-sm:!hidden" aria-hidden="true"></i><span class="sm:hidden">{{ $short }}</span><span class="hidden sm:inline">{{ $label }}</span>
                </button>
            @endforeach
        </div>
        {{-- Phones: the status is a dropdown beside the tabs --}}
        <label class="relative min-w-0 flex-1 sm:hidden">
            <span class="sr-only">Status</span>
            <select wire:model.live="filter"
                    class="h-[3.25rem] w-full truncate rounded-xl border-slate-200 bg-white py-0 pl-3 pr-8 text-base font-medium text-slate-800 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }} ({{ $counts[$key] }})</option>
                @endforeach
            </select>
        </label>
        <div class="-mx-1 hidden gap-1.5 overflow-x-auto px-1 sm:flex" role="group" aria-label="Status">
            @foreach ($statuses as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
                        class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-[13px] font-medium transition [@media(pointer:coarse)]:h-11 {{ $focus }}
                            {{ $filter === $key ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $label }} <span class="tabular-nums {{ $filter === $key ? 'text-white/70' : 'text-slate-400' }}">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Search, dates and view --}}
    <div class="mb-6 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3">
        <div class="relative min-w-0 flex-1 basis-56">
            <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search student, student no. or form" aria-label="Search uploads"
                   class="h-10 w-full rounded-lg border-slate-300 py-0 pl-10 pr-3 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
        </div>
        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                    class="inline-flex h-10 items-center gap-1.5 rounded-lg border px-3 text-sm font-medium [@media(pointer:coarse)]:h-11 {{ $focus }} {{ $dateLabel ? 'border-[#2A57B4]/40 bg-[#eef3fc] text-[#1E4090]' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                <i class="bx bx-calendar text-base" aria-hidden="true"></i><span class="whitespace-nowrap">{{ $dateLabel ?? 'Visit date' }}</span><i class="bx bx-chevron-down text-base text-slate-400" aria-hidden="true"></i>
            </button>
            {{-- Phones: a sheet from the bottom. Wider: under the button, kept inside the screen. --}}
            <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/30 sm:hidden" @click="open = false" aria-hidden="true"></div>
            <div x-show="open" x-cloak x-transition.opacity
                 x-init="$watch('open', v => v && $nextTick(() => { if (window.innerWidth < 640) return; const r = $el.getBoundingClientRect(); $el.style.transform = r.right > window.innerWidth - 8 ? `translateX(${window.innerWidth - 8 - r.right}px)` : (r.left < 8 ? `translateX(${8 - r.left}px)` : ''); }))"
                 class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl border-t border-slate-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:z-30 sm:mt-1.5 sm:w-72 sm:rounded-xl sm:border sm:p-3 sm:shadow-lg"
                 role="dialog" aria-label="Visit date">
                <p class="mb-2 text-sm font-semibold text-slate-900 sm:hidden">Visit date</p>
                <div class="grid grid-cols-2 gap-2">
                    <label class="block"><span class="mb-0.5 block text-[11px] text-slate-500">From</span><input type="date" wire:model.live="dateFrom" class="h-9 w-full rounded-lg border-slate-300 px-2 py-0 text-sm"></label>
                    <label class="block"><span class="mb-0.5 block text-[11px] text-slate-500">To</span><input type="date" wire:model.live="dateTo" class="h-9 w-full rounded-lg border-slate-300 px-2 py-0 text-sm"></label>
                </div>
                @if ($dateLabel)
                    <button type="button" wire:click="clearDates" @click="open = false" class="mt-2 text-sm font-medium text-[#2A57B4] hover:underline">Clear dates</button>
                @endif
            </div>
        </div>
        @if ($inside)
            <div class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="View">
                @foreach (['cards' => ['bx-grid-alt', 'Cards'], 'table' => ['bx-list-ul', 'Table']] as $v => [$vIcon, $vLabel])
                    <button type="button" wire:click="setView('{{ $v }}')" aria-pressed="{{ $view === $v ? 'true' : 'false' }}" title="{{ $vLabel }}"
                            class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition {{ $focus }} {{ $view === $v ? 'bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-800' }}">
                        <i class="bx {{ $vIcon }} text-lg" aria-hidden="true"></i><span class="hidden sm:inline">{{ $vLabel }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($recordCount === 0)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-16 text-center">
            <i class="bx bx-images text-4xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-medium text-slate-700">{{ $filter === 'needs_action' && ! $search && ! $dateLabel ? 'Nothing to upload. Every signed form brought in is checked.' : 'No upload matches.' }}</p>
        </div>

    @elseif ($level === 'folders')
        <h2 class="mb-3 text-[15px] font-semibold text-slate-900">Forms <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $folders->count() }} · {{ $recordCount }} {{ Str::plural('upload', $recordCount) }}</span></h2>
        <ul class="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 sm:gap-x-4 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($folders as $f)
                @include('livewire.admin.partials.archive-folder', ['f' => $f, 'click' => 'openFolder(' . \Illuminate\Support\Js::from($f['type']) . ')'])
            @endforeach
        </ul>

    @elseif ($level === 'students')
        <h2 class="mb-3 text-[15px] font-semibold text-slate-900">Students <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $itemCount }}</span></h2>
        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($pageItems as $s)
                @php $band = $folderTheme($s['student_name']); @endphp
                <li wire:key="up-st-{{ $s['key'] }}">
                    <button type="button" wire:click="openStudent('{{ $s['key'] }}')"
                            class="group flex h-full w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#2A57B4]/30 hover:shadow-md motion-reduce:hover:translate-y-0 {{ $focus }}">
                        <span class="block h-14 bg-gradient-to-br {{ $band['front'] }}" aria-hidden="true"></span>
                        <span class="flex flex-1 flex-col px-4 pb-4">
                            <x-dm.avatar :user="$s['user']" :name="$s['student_name']" class="-mt-9 h-16 w-16 text-lg ring-4 ring-white shadow-sm" />
                            <span class="mt-2 block truncate text-[15px] font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $s['student_name'] }}</span>
                            <span class="block truncate text-xs tabular-nums text-slate-500">{{ $s['student_number'] }}</span>
                            <span class="mt-3 grid grid-cols-3 divide-x divide-slate-200 rounded-lg border border-slate-200 text-center">
                                <span class="py-2"><span class="block text-lg font-semibold tabular-nums text-slate-900">{{ $s['forms']->count() }}</span><span class="block text-[11px] text-slate-500">{{ Str::plural('form', $s['forms']->count()) }}</span></span>
                                <span class="py-2"><span class="block text-lg font-semibold tabular-nums text-[#8a5a00]">{{ $s['needs'] }}</span><span class="block text-[11px] text-slate-500">to upload</span></span>
                                <span class="py-2"><span class="block text-lg font-semibold tabular-nums text-[#1f6b3a]">{{ $s['verified'] }}</span><span class="block text-[11px] text-slate-500">verified</span></span>
                            </span>
                            <span class="mt-auto flex items-center justify-between pt-3 text-xs text-slate-500">
                                <span>Visited {{ $s['latest'] }}</span>
                                <span class="inline-flex items-center gap-0.5 font-medium text-[#2A57B4]">Open <i class="bx bx-chevron-right text-base"></i></span>
                            </span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

    @else
        {{-- One folder, one student's uploads, or every upload --}}
        @php
            $list = match ($level) {
                'student' => $openStudent['forms'],
                default => $pageItems,
            };
        @endphp
        <div class="mb-4 flex flex-wrap items-center gap-3">
            @if ($level !== 'all')
                <button type="button" wire:click="closeOpened" class="{{ $backBtn }}"><i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> {{ $level === 'folder' ? 'All forms' : 'All students' }}</button>
            @endif
            @if ($level === 'student')
                <x-dm.avatar :user="$openStudent['user']" :name="$openStudent['student_name']" class="h-10 w-10 text-sm" />
            @endif
            <h2 class="min-w-0 text-lg font-semibold text-slate-900">
                {{ match ($level) { 'folder' => $folder, 'student' => $openStudent['student_name'], default => 'All uploads' } }}
                <span class="ml-1 text-sm font-normal tabular-nums text-slate-500">{{ $list->count() }} {{ Str::plural('form', $list->count()) }}</span>
            </h2>
        </div>

        @if ($view === 'cards')
            {{-- Same card as My documents and Issued documents: the whole page, the status on its corner, then who and what --}}
            <ul class="grid grid-cols-2 content-start gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($list as $r)
                    @php [$tText, $tCls] = $tag($r); @endphp
                    <li wire:key="up-{{ $r['id'] }}" class="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] motion-reduce:hover:translate-y-0">
                        <div class="relative aspect-[842/1171] overflow-hidden border-b border-slate-200 bg-white" aria-hidden="true">
                            @if ($r['photo'])
                                <img src="{{ $r['photo'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain">
                            @elseif ($r['form_image'])
                                {{-- No photo yet: the blank form, faded, with a camera --}}
                                <img src="{{ $r['form_image'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain opacity-40 grayscale">
                                <span class="absolute inset-0 grid place-items-center"><span class="grid h-12 w-12 place-items-center rounded-full bg-white/95 text-slate-500 shadow ring-1 ring-slate-900/5"><i class="bx bx-camera text-2xl"></i></span></span>
                            @else
                                <div class="flex h-full items-center justify-center bg-[#f0f4fb] text-[#1E4090]"><i class="bx bx-file text-4xl"></i></div>
                            @endif
                            <span class="absolute left-2 top-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium shadow-sm {{ $tCls }}">{{ $tText }}</span>
                        </div>
                        <div class="flex flex-1 flex-col p-3 sm:p-3.5">
                            <a href="{{ $r['url'] }}" wire:navigate class="line-clamp-2 rounded text-sm font-semibold leading-snug text-slate-900 after:absolute after:inset-0 after:content-[''] group-hover:text-[#2A57B4] {{ $focus }}">
                                {{ $level === 'student' ? $r['type'] : $r['student_name'] }}
                            </a>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $level === 'student' ? $r['student_number'] : ($level === 'all' ? $r['type'] : $r['student_number']) }}</p>
                            <p class="mt-auto flex items-center gap-1.5 pt-1.5 text-xs text-slate-600"><i class="bx bx-calendar-check text-sm text-slate-400" aria-hidden="true"></i>Visited {{ $r['visited_label'] }}</p>
                        </div>
                        <div class="relative z-10 grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 text-slate-500">
                            <a href="{{ $r['url'] }}" wire:navigate class="flex h-9 items-center justify-center gap-1 text-xs font-medium hover:bg-slate-50 hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 {{ $focus }}">
                                <i class="bx {{ $r['state'] === 'needs_action' ? 'bx-upload' : 'bx-file' }} text-base" aria-hidden="true"></i>{{ $r['state'] === 'needs_action' ? 'Upload' : 'Open' }}
                            </a>
                            @if ($r['photo'])
                                <a href="{{ $r['photo'] }}" target="_blank" rel="noopener" class="flex h-9 items-center justify-center gap-1 text-xs font-medium hover:bg-slate-50 hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 {{ $focus }}"><i class="bx bx-show text-base" aria-hidden="true"></i>Photo</a>
                            @else
                                <span class="flex h-9 items-center justify-center gap-1 text-xs text-slate-300 [@media(pointer:coarse)]:h-11"><i class="bx bx-image text-base" aria-hidden="true"></i>No photo</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="hidden grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_8rem_9rem_6rem] gap-4 border-b border-slate-200 px-4 py-2.5 text-xs font-medium text-slate-500 md:grid">
                    <span>Student</span><span>Form</span><span>Visited</span><span>Status</span><span></span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($list as $r)
                        @php [$tText, $tCls] = $tag($r); @endphp
                        <li wire:key="upr-{{ $r['id'] }}">
                            <a href="{{ $r['url'] }}" wire:navigate class="block px-4 py-3 hover:bg-slate-50 md:grid md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_8rem_9rem_6rem] md:items-center md:gap-4 {{ $focus }}">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <x-dm.avatar :user="$r['user']" :name="$r['student_name']" class="h-7 w-7 text-[10px]" />
                                    <span class="min-w-0"><span class="block truncate text-sm text-slate-900">{{ $r['student_name'] }}</span><span class="block truncate text-xs tabular-nums text-slate-500">{{ $r['student_number'] }}</span></span>
                                </span>
                                <span class="mt-1 block truncate text-sm text-slate-700 md:mt-0">{{ $r['type'] }}</span>
                                <span class="mt-1 block text-xs tabular-nums text-slate-500 md:mt-0 md:text-sm">{{ $r['visited_label'] }}</span>
                                <span class="mt-1 block md:mt-0"><span class="inline-block rounded-md px-1.5 py-0.5 text-[11px] font-medium {{ $tCls }}">{{ $tText }}</span></span>
                                <span class="hidden text-right text-sm font-medium text-[#2A57B4] md:block">{{ $r['state'] === 'needs_action' ? 'Upload' : 'Open' }} →</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if ($lastPage > 1 && in_array($level, ['folder', 'students', 'all'], true))
        <div class="mt-5 flex items-center justify-between gap-3">
            <p class="text-xs tabular-nums text-slate-500">Page {{ $currentPage }} of {{ $lastPage }}</p>
            <div class="flex gap-2">
                <button type="button" wire:click="previousPage" @disabled($currentPage === 1) class="{{ $backBtn }} disabled:cursor-not-allowed disabled:text-slate-300"><i class="bx bx-chevron-left text-lg"></i> Previous</button>
                <button type="button" wire:click="nextPage({{ $lastPage }})" @disabled($currentPage === $lastPage) class="{{ $backBtn }} disabled:cursor-not-allowed disabled:text-slate-300">Next <i class="bx bx-chevron-right text-lg"></i></button>
            </div>
        </div>
    @endif
</div>
