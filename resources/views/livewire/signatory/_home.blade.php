{{-- Signatory Home, inside the page-wide sky (partials.dash.header, page mode), mirroring the student's Home:
       Overview  the search drops down every report they may issue; below it, one small card per signing role,
                 with what waits on it and the student documents it signs
       Explore   the same search filters report cards with a preview of each, laid out like the student's Explore
     Needs: $hero, $reports, $categories, $docsByRole, $assignments, $waitingByRole, $waiting, $oldest, $hasSignature. --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $onSky = 'text-slate-900 group-data-[ink=light]/greet:text-white';
    $linkOnSky = 'text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:hover:text-blue-100';
    $canIssue = count($reports) > 0;
@endphp
<div x-data="{
        tab: location.hash === '#explore' ? 'explore' : 'overview',
        items: @js($reports),
        q: '', cat: 'all', open: false, active: 0,
        menu: null, preview: null,
        touch() { return window.matchMedia('(hover: none)').matches; },
        matches(i) {
            const words = this.q.toLowerCase().split(/\s+/).filter(Boolean);
            return (this.cat === 'all' || i.category === this.cat)
                && words.every(w => (i.name + ' ' + i.description + ' ' + i.categoryLabel + ' ' + i.code).toLowerCase().includes(w));
        },
        get results() { return this.items.filter(i => this.matches(i)); },
        countIn(cat) { return this.items.filter(i => i.category === cat && this.matches(i)).length; },
        setTab(t) {
            this.tab = t; this.open = false; this.menu = null;
            history.replaceState(null, '', t === 'explore' ? '#explore' : location.pathname + location.search);
        },
        show() { if (this.tab === 'overview') { this.open = true; this.active = 0; } },
        pick(cat) { this.cat = this.cat === cat ? 'all' : cat; this.show(); this.$refs.q.focus(); },
        move(d) {
            if (this.tab !== 'overview') return;
            if (! this.open) return this.show();
            const n = this.results.length;
            if (n) { this.active = (this.active + d + n) % n; this.$nextTick(() => document.getElementById('rep-opt-' + this.active)?.scrollIntoView({ block: 'nearest' })); }
        },
        go(i) { const r = this.results[i]; if (this.tab === 'overview' && r) Livewire.navigate(r.url); },
        openCard(k) { if (this.touch()) { this.menu = k; } else { Livewire.navigate(this.items[k].url); } },
        openPreview(k) { this.menu = null; this.preview = k; },
     }"
     @keydown.escape.window="menu = null; preview = null"
     {{-- The Home tour switches tabs and opens a sample preview as it explains them --}}
     @tour-page.window="const d = $event.detail; if (d.tab) setTab(d.tab);
        if (d.preview === null) preview = null;
        else if (d.preview !== undefined) {
            {{-- the sample: the first online form on the page (a print form has no online steps to show) --}}
            const ks = [...document.querySelectorAll('[data-tour=explore-card]')].map(e => +e.dataset.k);
            openPreview(ks.find(k => ! items[k].paper) ?? ks[0] ?? 0);
        }"
     @tour-ended.window="preview = null; menu = null">

    {{-- ===== First screen: greeting, then the view switch and the search ===== --}}
    <div class="flex min-h-[calc(50dvh-95px)] flex-col justify-end text-center">
        {!! $hero !!}

        <div class="mt-3 flex justify-center gap-2 pb-3" role="tablist" aria-label="Home view" data-tour="tabs">
            @foreach (['overview' => ['Overview', 'bx-home-smile'], 'explore' => ['Explore', 'bx-compass']] as $key => [$label, $icon])
                <button type="button" role="tab" data-tour="tab-{{ $key }}" @click="setTab('{{ $key }}')" :aria-selected="(tab === '{{ $key }}').toString()"
                        class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold backdrop-blur-sm transition-colors {{ $focus }} [@media(pointer:coarse)]:min-h-[36px] [@media(pointer:coarse)]:px-3.5"
                        :class="tab === '{{ $key }}'
                            ? 'border-[#2A57B4]/40 bg-[#2A57B4]/10 text-[#1E4090] group-data-[ink=light]/greet:border-white/60 group-data-[ink=light]/greet:bg-white/20 group-data-[ink=light]/greet:text-white'
                            : 'border-slate-900/15 bg-transparent text-slate-700 hover:border-slate-900/30 hover:text-slate-900 group-data-[ink=light]/greet:border-white/25 group-data-[ink=light]/greet:text-white/80 group-data-[ink=light]/greet:hover:border-white/50 group-data-[ink=light]/greet:hover:text-white'">
                    <i class='bx {{ $icon }} text-sm' aria-hidden="true"></i>{{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ===== Search the reports this person may issue ===== --}}
    @if ($canIssue)
        <div class="relative z-20 mx-auto max-w-2xl" @click.outside="open = false" @keydown.escape="open = false" data-tour="search">
            <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_12px_32px_-12px_rgba(13,37,61,0.28)]">
                <label for="report-search" class="sr-only">Search the reports you can issue</label>
                <div class="relative">
                    <i class='bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xl text-slate-400' aria-hidden="true"></i>
                    <input id="report-search" x-ref="q" type="text" autocomplete="off"
                           x-model="q" @focus="show()" @input="show()"
                           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go(active)"
                           role="combobox" aria-autocomplete="list" aria-controls="report-results"
                           :aria-expanded="open.toString()" :aria-activedescendant="open && results.length ? 'rep-opt-' + active : null"
                           :placeholder="tab === 'overview' ? 'Which report do you need to issue?' : 'Filter reports'"
                           placeholder="Which report do you need to issue?"
                           class="w-full rounded-xl border-0 bg-transparent py-3.5 pl-11 pr-32 text-base text-slate-900 placeholder-slate-500 focus:ring-0">
                    <button type="button" x-show="tab === 'overview'" @click="open ? open = false : (show(), $refs.q.focus())"
                            class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 {{ $focus }}"
                            :aria-label="open ? 'Close the list of reports' : 'Show all reports'">
                        <span x-text="open ? 'Close' : 'All reports'">All reports</span>
                        <i class='bx text-lg' :class="open ? 'bx-chevron-up' : 'bx-chevron-down'" aria-hidden="true"></i>
                    </button>
                    <button type="button" x-show="tab === 'explore' && (q || cat !== 'all')" x-cloak @click="q = ''; cat = 'all'"
                            class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 {{ $focus }}">
                        Clear <i class='bx bx-x text-lg' aria-hidden="true"></i>
                    </button>
                </div>
                @if (count($categories) > 1)
                    <div class="flex gap-1.5 overflow-x-auto px-1.5 pb-1.5 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ($categories as $c)
                            <button type="button" wire:key="rcat-{{ $c['key'] }}" @click="pick(@js($c['key']))"
                                    :class="cat === @js($c['key']) ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
                                    class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium transition-colors {{ $focus }}">
                                {{ $c['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Results (Overview only) --}}
            <div x-show="open && tab === 'overview'" x-cloak x-transition.opacity.duration.150ms
                 class="absolute inset-x-0 top-full mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-[0_16px_40px_-12px_rgba(13,37,61,0.3)]">
                <ul id="report-results" role="listbox" aria-label="Reports you can issue" class="max-h-[min(50vh,26rem)] overflow-y-auto py-1.5">
                    <template x-for="(r, i) in results" :key="r.key">
                        <li>
                            <p x-show="i === 0 || results[i - 1].category !== r.category"
                               class="px-4 pb-1 pt-3 text-xs font-medium text-slate-500" x-text="r.categoryLabel"></p>
                            <a :href="r.url" wire:navigate :id="'rep-opt-' + i" role="option" :aria-selected="(active === i).toString()"
                               @mouseenter="active = i" :class="active === i ? 'bg-[#2A57B4]/[0.06]' : ''"
                               class="mx-1.5 flex items-center gap-3 rounded-lg px-2.5 py-2.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#e6e0f5] text-violet-800"><i class='bx bx-file text-lg' aria-hidden="true"></i></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900" x-text="r.name"></span>
                                    <span class="block truncate text-xs text-slate-500" x-text="r.description"></span>
                                </span>
                                <span class="shrink-0 text-[11px] font-medium tabular-nums text-slate-500" x-text="r.code"></span>
                            </a>
                        </li>
                    </template>
                    <li x-show="! results.length" class="px-4 py-8 text-center text-sm text-slate-500">
                        No report matches your search.
                        <button type="button" @click="q = ''; cat = 'all'; $refs.q.focus()" class="ml-1 font-semibold text-[#2A57B4] hover:underline">Show all reports</button>
                    </li>
                </ul>
                <p class="border-t border-slate-100 px-4 py-2 text-[11px] text-slate-500">
                    Reports you issue are signed with your profile signature, sealed, and sent to the student and the VPSD office.
                </p>
            </div>
        </div>
    @endif

    @unless ($hasSignature)
        <div class="mx-auto mt-6 flex max-w-5xl flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-left sm:flex-row sm:items-center sm:justify-between" role="status">
            <p class="text-sm text-amber-900"><i class="bx bx-error-circle mr-1 align-[-2px]" aria-hidden="true"></i> Add your e-signature before you sign or issue anything.</p>
            <a href="{{ route('profile') }}" wire:navigate class="inline-flex min-h-9 shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-medium text-white hover:bg-amber-700 {{ $focus }}">Add signature</a>
        </div>
    @endunless

    {{-- ===== Overview: one small card per signing role ===== --}}
    <div x-show="tab === 'overview'" class="mt-[38dvh] space-y-8 pb-16 sm:mt-[22dvh]">
        {{-- Pick up where you left off: reports saved as drafts on the Issue page --}}
        @if ($drafts->isNotEmpty())
            <section class="mx-auto max-w-5xl text-left" aria-labelledby="resume-title">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="resume-title" class="text-base font-semibold {{ $onSky }}">Pick up where you left off</h2>
                    <a href="{{ route('signatory.issued') }}" wire:navigate class="{{ $linkOnSky }}">Issued documents &rarr;</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2.5 sm:gap-3">
                    @foreach ($drafts as $d)
                        <a href="{{ route('signatory.issue', ['draft' => $d->id]) }}" wire:navigate wire:key="resume-{{ $d->id }}"
                           class="group flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 max-sm:flex-col max-sm:items-stretch max-sm:gap-2 max-sm:p-3 transition hover:border-slate-300 hover:shadow-sm {{ $focus }}">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700"><i class='bx bx-edit text-lg' aria-hidden="true"></i></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $d->document()?->name() ?? 'Report' }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $d->student?->full_name ? 'For ' . $d->student->full_name : 'No student picked yet' }}</span>
                                <span class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                    <span class="rounded-md bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-800">Draft</span>
                                    <span class="text-slate-500">Saved {{ $d->updated_at->diffForHumans() }}</span>
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div data-tour="waiting">@include('livewire.signatory._waiting-cards', ['signingTotal' => $waiting, 'width' => 'max-w-5xl'])</div>

        <div data-tour="roles">@include('livewire.signatory._role-cards', ['width' => 'max-w-5xl'])</div>
    </div>

    {{-- ===== Explore: the reports they may issue, with a preview of each (the student Explore's layout) ===== --}}
    <section x-show="tab === 'explore'" x-cloak class="mt-10 pb-40 text-left sm:pb-52" aria-labelledby="explore-title" data-tour="explore">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 id="explore-title" class="text-xl font-semibold tracking-tight {{ $onSky }}">Reports you can issue</h2>
            <p class="text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50" aria-live="polite">
                <span x-text="results.length"></span> of {{ count($reports) }} reports
            </p>
        </div>

        @if ($canIssue)
            <div class="mt-5 space-y-8">
                @foreach ($categories as $c)
                    <div wire:key="explore-cat-{{ $c['key'] }}" x-show="countIn(@js($c['key']))">
                        <h3 class="flex items-baseline gap-2 text-sm font-semibold text-slate-800 group-data-[ink=light]/greet:text-white">
                            {{ $c['label'] }}
                            <span class="font-medium text-slate-500 group-data-[ink=light]/greet:text-blue-100" x-text="countIn(@js($c['key']))"></span>
                        </h3>

                        {{-- Phones: one row per category, two cards across, swiped sideways. Wider: four across. --}}
                        <div class="-mx-4 mt-3 grid auto-cols-[calc(50%-1.25rem)] grid-flow-col gap-2.5 overflow-x-auto overscroll-x-contain scroll-px-4 px-4 pb-2 [scrollbar-width:none] snap-x snap-mandatory [&::-webkit-scrollbar]:hidden
                                    sm:mx-0 sm:grid-flow-row sm:grid-cols-4 sm:gap-4 sm:overflow-visible sm:px-0 sm:pb-0">
                            @foreach ($reports as $k => $item)
                                @continue($item['category'] !== $c['key'])
                                <div data-tour="explore-card" data-k="{{ $k }}" wire:key="explore-{{ $item['key'] }}" x-show="matches(items[{{ $k }}])" class="group relative min-w-0 snap-start">
                                    <a href="{{ $item['url'] }}" @click.prevent="openCard({{ $k }})"
                                       class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] motion-reduce:hover:translate-y-0">
                                        {{-- Preview: the whole page as it prints, blank (same card as My documents). Loads once the card is on screen. --}}
                                        <div class="relative aspect-[842/1171] overflow-hidden border-b border-slate-200 bg-white" aria-hidden="true"
                                             x-data="{ s: 0.2, src: '' }" x-init="const fit = () => s = $el.clientWidth / 842; fit(); new ResizeObserver(fit).observe($el)"
                                             x-intersect.once.margin.200px="src = @js($item['preview'])">
                                            <iframe :src="src || false" tabindex="-1" title=""
                                                    class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                                                    :style="`width: 842px; height: 1171px; transform: scale(${s})`" style="width: 842px; height: 1171px; transform: scale(0.2)"></iframe>
                                            <span class="absolute left-2 top-2 inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-800 shadow-sm ring-1 ring-violet-200">Issue</span>
                                        </div>
                                        <div class="flex flex-1 flex-col p-3 sm:p-3.5">
                                            <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-[#2A57B4]">{{ $item['name'] }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ ($item['code'] ?? '') ?: $item['categoryLabel'] }}</p>
                                        </div>
                                    </a>

                                    {{-- ⋯ menu: appears on hover (desktop) --}}
                                    <button type="button" @click="menu = menu === {{ $k }} ? null : {{ $k }}"
                                            :aria-expanded="(menu === {{ $k }}).toString()" aria-label="More about {{ $item['name'] }}"
                                            class="absolute right-2 top-2 hidden h-8 w-8 items-center justify-center rounded-full bg-white/95 text-slate-700 opacity-0 shadow ring-1 ring-slate-900/10 transition hover:text-slate-900 focus:opacity-100 group-hover:opacity-100 sm:flex [@media(hover:none)]:!hidden"
                                            :class="menu === {{ $k }} ? '!opacity-100' : ''">
                                        <i class='bx bx-dots-horizontal-rounded text-xl' aria-hidden="true"></i>
                                    </button>
                                    <div x-show="menu === {{ $k }} && ! touch()" x-cloak x-transition.opacity.duration.120ms @click.outside="if (menu === {{ $k }}) menu = null"
                                         class="absolute right-2 top-11 z-30 w-72 max-w-[calc(100vw-2rem)] rounded-xl bg-white p-4 text-left shadow-[0_16px_40px_-12px_rgba(13,37,61,0.35)] ring-1 ring-slate-900/10">
                                        <p class="text-xs text-slate-500">{{ $item['categoryLabel'] }} · <span class="tabular-nums">{{ $item['code'] }}</span></p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $item['description'] ?: 'No description yet.' }}</p>
                                        <div class="mt-3 grid gap-2">
                                            <a href="{{ $item['url'] }}" wire:navigate class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-sm font-semibold text-white hover:bg-[#24499A]">
                                                <i class='bx bx-file text-base' aria-hidden="true"></i> Issue this report
                                            </a>
                                            <button type="button" @click="openPreview({{ $k }})" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                <i class='bx bx-show text-base' aria-hidden="true"></i> Preview report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p x-show="! results.length" x-cloak class="mt-6 rounded-2xl bg-white/80 px-4 py-10 text-center text-sm text-slate-600 backdrop-blur">
                No report matches your search.
                <button type="button" @click="q = ''; cat = 'all'" class="ml-1 font-semibold text-[#2A57B4] hover:underline">Show all</button>
            </p>
        @else
            <p class="mt-5 rounded-2xl bg-white/80 px-4 py-10 text-center text-sm text-slate-600 backdrop-blur">None of your signing roles issues reports.</p>
        @endif
    </section>

    {{-- Touch screens: tapping a card opens its menu as a bottom sheet --}}
    <template x-teleport="body">
        <div x-show="menu !== null && touch()" x-cloak class="fixed inset-0 z-[90] flex items-end bg-slate-900/50" @click.self="menu = null"
             role="dialog" aria-modal="true" aria-label="Report options">
            <div x-show="menu !== null && touch()" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full"
                 class="w-full rounded-t-2xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl">
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300"></div>
                <template x-if="menu !== null">
                    <div>
                        <p class="text-xs text-slate-500" x-text="items[menu].categoryLabel + ' · ' + items[menu].code"></p>
                        <p class="mt-1 text-base font-semibold text-slate-900" x-text="items[menu].name"></p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600" x-text="items[menu].description || 'No description yet.'"></p>
                        <div class="mt-4 grid gap-2">
                            <a :href="items[menu].url" wire:navigate class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 text-sm font-semibold text-white">
                                <i class='bx bx-file text-base' aria-hidden="true"></i> Issue this report
                            </a>
                            <button type="button" @click="openPreview(menu)" class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                                <i class='bx bx-show text-base' aria-hidden="true"></i> Preview report
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Full preview of a report: a blank copy, nothing is saved --}}
    <template x-teleport="body">
        <div x-show="preview !== null" x-cloak x-transition.opacity class="fixed inset-0 z-[95] flex flex-col bg-slate-900/60 sm:p-6"
             role="dialog" aria-modal="true" aria-labelledby="explore-preview-title" @click.self="preview = null">
            <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl" data-tour="sample-preview">
                <template x-if="preview !== null">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <h3 id="explore-preview-title" class="truncate text-sm font-semibold text-slate-900" x-text="items[preview].name"></h3>
                                <p class="text-xs text-slate-500">A blank copy of the report. Nothing is saved.</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <a :href="items[preview].url" wire:navigate data-tour="sample-start" class="inline-flex items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-sm font-semibold text-white hover:bg-[#24499A]">Issue <span class="hidden sm:inline">this report</span></a>
                                <button type="button" @click="preview = null" aria-label="Close preview"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900"><i class="bx bx-x text-2xl" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto bg-[#e7e5e1]" x-data="{ w: 842, h: 1171, s: 1 }"
                             x-init="const fit = () => s = Math.min(1, $el.clientWidth / w); fit(); new ResizeObserver(fit).observe($el)"
                             @message.window="if ($refs.pf && $event.source === $refs.pf.contentWindow && $event.data && $event.data.documatePreviewHeight) h = Math.ceil($event.data.documatePreviewHeight)">
                            <div class="mx-auto overflow-hidden" :style="`width:${w * s}px;height:${h * s}px`">
                                <iframe x-ref="pf" :src="items[preview].preview" title="Report preview" class="block border-0 bg-[#e7e5e1]"
                                        :style="`width:${w}px;height:${h}px;transform:scale(${s});transform-origin:top left;`"></iframe>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
