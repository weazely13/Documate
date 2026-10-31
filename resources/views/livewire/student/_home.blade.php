{{-- Home, inside the page-wide sky (partials.dash.header, page mode).
     First screen: the greeting carousel ($hero) and the Overview / Explore buttons sit in a fixed-height area,
     so the search card's centre always lands in the middle of the screen, whatever the slides do.
       Overview  the search drops down every form; below it, unfinished work
       Explore   the same search filters "Explore documents": form cards by category, each with a preview,
                 a ⋯ menu (description, start, preview) on hover, or on tap for touch screens
     Needs: $hero, $formItems, $categories, $resume. --}}
<div x-data="{
        tab: ['#explore', '#documents'].includes(location.hash) ? 'explore' : 'overview',
        items: @js($formItems),
        q: '', cat: 'all', open: false, active: 0,
        menu: null, preview: null, scale: 0.5,
        touch() { return window.matchMedia('(hover: none)').matches; },
        matches(i) {
            const words = this.q.toLowerCase().split(/\s+/).filter(Boolean);
            return (this.cat === 'all' || i.category === this.cat)
                && words.every(w => (i.name + ' ' + i.description + ' ' + i.categoryLabel).toLowerCase().includes(w));
        },
        get results() { return this.items.filter(i => this.matches(i)); },
        countIn(cat) { return this.items.filter(i => i.category === cat && this.matches(i)).length; },
        setTab(t) {
            this.tab = t;
            this.open = false;
            this.menu = null;
            history.replaceState(null, '', t === 'explore' ? '#explore' : location.pathname + location.search);
        },
        show() { if (this.tab === 'overview') { this.open = true; this.active = 0; } },
        pick(cat) { this.cat = this.cat === cat ? 'all' : cat; this.show(); this.$refs.q.focus(); },
        move(d) {
            if (this.tab !== 'overview') return;
            if (! this.open) return this.show();
            const n = this.results.length;
            if (n) { this.active = (this.active + d + n) % n; this.$nextTick(() => document.getElementById('form-opt-' + this.active)?.scrollIntoView({ block: 'nearest' })); }
        },
        go(i) { const r = this.results[i]; if (this.tab === 'overview' && r) Livewire.navigate(r.url); },
        openCard(k) { if (this.touch()) { this.menu = k; } else { Livewire.navigate(this.items[k].url); } },
        openPreview(k) { this.menu = null; this.preview = k; },
     }"
     @keydown.escape.window="menu = null; preview = null">

    {{-- ===== First screen: greeting carousel, buttons, then the search at the screen's middle ===== --}}
    <div class="flex min-h-[calc(50dvh-95px)] flex-col justify-end text-center">
        {!! $hero !!}

        {{-- Two separate, small, see-through round buttons; the chosen one is tinted a little more --}}
        <div class="mt-3 flex justify-center gap-2 pb-3" role="tablist" aria-label="Home view">
            @foreach (['overview' => ['Overview', 'bx-home-smile'], 'explore' => ['Explore', 'bx-compass']] as $key => [$label, $icon])
                <button type="button" role="tab" @click="setTab('{{ $key }}')" :aria-selected="(tab === '{{ $key }}').toString()"
                        class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold backdrop-blur-sm transition-colors [@media(pointer:coarse)]:min-h-[36px] [@media(pointer:coarse)]:px-3.5"
                        :class="tab === '{{ $key }}'
                            ? 'border-[#2A57B4]/40 bg-[#2A57B4]/10 text-[#1E4090] group-data-[ink=light]/greet:border-white/60 group-data-[ink=light]/greet:bg-white/20 group-data-[ink=light]/greet:text-white'
                            : 'border-slate-900/15 bg-transparent text-slate-700 hover:border-slate-900/30 hover:text-slate-900 group-data-[ink=light]/greet:border-white/25 group-data-[ink=light]/greet:text-white/80 group-data-[ink=light]/greet:hover:border-white/50 group-data-[ink=light]/greet:hover:text-white'">
                    <i class='bx {{ $icon }} text-sm' aria-hidden="true"></i>{{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Search: drops down on Overview, filters the cards on Explore --}}
    <div class="relative z-20 mx-auto max-w-2xl" @click.outside="open = false" @keydown.escape="open = false">
        <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_12px_32px_-12px_rgba(13,37,61,0.28)]">
            <label for="form-search" class="sr-only">Search forms</label>
            <div class="relative">
                <i class='bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xl text-slate-400' aria-hidden="true"></i>
                <input id="form-search" x-ref="q" type="text" autocomplete="off"
                       x-model="q" @focus="show()" @input="show()"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="go(active)"
                       role="combobox" aria-autocomplete="list" aria-controls="form-results"
                       :aria-expanded="open.toString()" :aria-activedescendant="open && results.length ? 'form-opt-' + active : null"
                       :placeholder="tab === 'overview' ? 'What do you need?' : 'Filter documents'"
                       placeholder="What do you need?"
                       class="w-full rounded-xl border-0 bg-transparent py-3.5 pl-11 pr-28 text-base text-slate-900 placeholder-slate-400 focus:ring-0">
                <button type="button" x-show="tab === 'overview'" @click="open ? open = false : (show(), $refs.q.focus())"
                        class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        :aria-label="open ? 'Close the list of forms' : 'Show all forms'">
                    <span x-text="open ? 'Close' : 'All forms'">All forms</span>
                    <i class='bx text-lg' :class="open ? 'bx-chevron-up' : 'bx-chevron-down'" aria-hidden="true"></i>
                </button>
                <button type="button" x-show="tab === 'explore' && (q || cat !== 'all')" x-cloak @click="q = ''; cat = 'all'"
                        class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                    Clear <i class='bx bx-x text-lg' aria-hidden="true"></i>
                </button>
            </div>

            {{-- Category filters --}}
            <div class="flex gap-1.5 overflow-x-auto px-1.5 pb-1.5 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($categories as $c)
                    <button type="button" wire:key="cat-{{ $c['key'] }}" @click="pick(@js($c['key']))"
                            :class="cat === @js($c['key']) ? 'border-[#2A57B4] bg-[#2A57B4] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'"
                            class="shrink-0 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-medium transition-colors">
                        {{ $c['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Results (Overview only) --}}
        <div x-show="open && tab === 'overview'" x-cloak x-transition.opacity.duration.150ms
             class="absolute inset-x-0 top-full mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-[0_16px_40px_-12px_rgba(13,37,61,0.3)]">
            <ul id="form-results" role="listbox" aria-label="Forms" class="max-h-[min(50vh,26rem)] overflow-y-auto py-1.5">
                <template x-for="(r, i) in results" :key="r.url">
                    <li>
                        <p x-show="i === 0 || results[i - 1].category !== r.category"
                           class="px-4 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="r.categoryLabel"></p>
                        <a :href="r.url" wire:navigate :id="'form-opt-' + i" role="option" :aria-selected="(active === i).toString()"
                           @mouseenter="active = i"
                           :class="active === i ? 'bg-[#2A57B4]/[0.06]' : ''"
                           class="mx-1.5 flex items-center gap-3 rounded-lg px-2.5 py-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                  :class="r.paper ? 'bg-amber-50 text-amber-700' : 'bg-[#2A57B4]/[0.08] text-[#2A57B4]'">
                                <i class='bx text-lg' :class="r.paper ? 'bx-printer' : 'bx-file'" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900" x-text="r.name"></span>
                                <span class="block truncate text-xs text-slate-500" x-text="r.description"></span>
                            </span>
                            <span class="shrink-0 text-[11px] font-medium" :class="r.paper ? 'text-amber-800' : 'text-slate-400'" x-text="r.paper ? 'Print' : 'Online'"></span>
                        </a>
                    </li>
                </template>
                <li x-show="! results.length" class="px-4 py-8 text-center text-sm text-slate-500">
                    No form matches your search.
                    <button type="button" @click="q = ''; cat = 'all'; $refs.q.focus()" class="ml-1 font-semibold text-[#2A57B4] hover:underline">Show all forms</button>
                </li>
            </ul>
            <p class="border-t border-slate-100 px-4 py-2 text-[11px] text-slate-500">
                <span class="font-medium text-slate-700">Online</span> forms are signed and finished here.
                <span class="font-medium text-amber-800">Print</span> forms are filled in here, then brought to the VPSD office.
            </p>
        </div>
    </div>

    {{-- ===== Overview: unfinished work ===== --}}
    <div x-show="tab === 'overview'" class="mt-10 space-y-8">
        @if ($resume)
            <section class="mx-auto max-w-4xl" aria-labelledby="resume-title">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="resume-title" class="text-base font-semibold text-slate-900 group-data-[ink=light]/greet:text-white">Pick up where you left off</h2>
                    <a href="{{ route('student.documents.index') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:hover:text-blue-100">My documents &rarr;</a>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($resume as $doc)
                        <a href="{{ $doc['href'] }}" wire:navigate wire:key="resume-{{ $doc['id'] }}"
                           class="group flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $doc['tone'] === 'you' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                <i class='bx {{ $doc['label'] === 'Draft' ? 'bx-edit' : 'bx-revision' }} text-lg' aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $doc['title'] }}</span>
                                <span class="mt-0.5 line-clamp-2 block text-xs text-slate-500">{{ $doc['next'] }}</span>
                                <span class="mt-2 flex items-center gap-2 text-xs">
                                    <span class="rounded-md px-1.5 py-0.5 font-semibold {{ \App\Support\DocumentProgress::TONES[$doc['tone']] }}">{{ $doc['label'] }}</span>
                                    @if ($doc['updated'])<span class="text-slate-400">Edited {{ $doc['updated'] }}</span>@endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @else
            <p class="text-center text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50">
                Nothing unfinished. Search above, or
                <button type="button" @click="setTab('explore')" class="font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:underline">explore every document</button>.
            </p>
        @endif

        {{-- Upcoming visits to the office, right under the unfinished work --}}
        <section class="mx-auto max-w-4xl" aria-labelledby="home-appts-title">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="home-appts-title" class="text-base font-semibold text-slate-900 group-data-[ink=light]/greet:text-white">Your appointments</h2>
                <a href="{{ route('student.appointments.index') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:hover:text-blue-100">All appointments &rarr;</a>
            </div>
            @if (! empty($appointments) && $appointments->isNotEmpty())
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($appointments as $a)
                        @php
                            [$sText, $sPill] = $a->status === 'approved'
                                ? ['Approved' . ($a->queue_number ? ' · #' . $a->queue_number : ''), 'bg-[#e2e9f7] text-[#1E4090] ring-sky-200']
                                : ['Waiting for approval', 'bg-amber-50 text-amber-800 ring-amber-200'];
                            $live = match (true) {
                                $a->status !== 'approved' || ! $a->appointment_date->isToday() => null,
                                $a->now_serving === $a->queue_number => ['It is your turn. Please go to the counter.', 'text-emerald-800'],
                                $a->now_serving !== null && $a->queue_number > $a->now_serving => ['Now serving #' . $a->now_serving . ' · ' . ($a->queue_number - $a->now_serving) . ' ahead of you', 'text-[#1E4090]'],
                                $a->now_serving !== null => ['Your number has passed. Please check in with the staff.', 'text-rose-800'],
                                default => ['Live queue · waiting for the first call', 'text-[#1E4090]'],
                            };
                        @endphp
                        <a href="{{ route('student.appointments.show', $a->appointment_id) }}" wire:navigate wire:key="home-appt-{{ $a->appointment_id }}"
                           class="group flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $a->appointment_date->isToday() ? 'bg-[#2A57B4] text-white' : 'bg-[#f0f4fb] text-[#1E4090]' }}" aria-hidden="true">
                                <span class="text-center leading-none">
                                    <span class="block text-[10px] font-medium">{{ $a->appointment_date->format('M') }}</span>
                                    <span class="mt-0.5 block text-lg font-semibold tabular-nums">{{ $a->appointment_date->format('j') }}</span>
                                </span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $a->purpose ?: 'General visit' }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">
                                    <span class="sr-only">{{ $a->appointment_date->format('l, F j') }}, </span>
                                    {{ $a->appointment_date->isToday() ? 'Today' : $a->appointment_date->format('l') }} · {{ $a->session === 'morning' ? 'Morning, 8–12' : 'Afternoon, 1–5' }}@if ($a->workspace) · {{ $a->workspace->documentName() }}@endif
                                </span>
                                @if ($live)
                                    <span class="mt-1 block text-xs {{ $live[1] }}" aria-live="polite">{{ $live[0] }}</span>
                                @endif
                                <span class="mt-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $sPill }}">{{ $sText }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-slate-300 bg-white/80 px-4 py-3.5">
                    <p class="text-sm text-slate-600">No upcoming visits to the VPSD office.</p>
                    <a href="{{ route('student.appointments.new') }}" wire:navigate
                       class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-11">
                        <i class="bx bx-plus text-base" aria-hidden="true"></i> Book an appointment
                    </a>
                </div>
            @endif
        </section>

        <p class="text-center text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50">
            Want to see how your requests are going?
            <a href="{{ route('student.dashboard') }}" wire:navigate class="font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:underline">Open your activity summary &rarr;</a>
        </p>
    </div>

    {{-- ===== Explore: every form a student can start, by category ===== --}}
    <section x-show="tab === 'explore'" x-cloak class="mt-10 pb-40 sm:pb-52" aria-labelledby="explore-title">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 id="explore-title" class="text-xl font-semibold tracking-tight text-slate-900 group-data-[ink=light]/greet:text-white">Explore documents</h2>
            <p class="text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50" aria-live="polite">
                <span x-text="results.length"></span> of {{ count($formItems) }} forms
            </p>
        </div>

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
                        @foreach ($formItems as $k => $item)
                            @continue($item['category'] !== $c['key'])
                            <div wire:key="explore-{{ $k }}" x-show="matches(items[{{ $k }}])" class="group relative min-w-0 snap-start">
                                <a href="{{ $item['url'] }}" @click.prevent="openCard({{ $k }})"
                                   class="block overflow-hidden rounded-lg border border-slate-200/70 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] motion-reduce:hover:translate-y-0 sm:rounded-2xl">
                                    {{-- Preview: the top of the form as it prints, completely blank --}}
                                    <div class="relative aspect-[3/4] overflow-hidden border-b border-slate-100 bg-[#e7e5e1] sm:aspect-auto sm:h-48" aria-hidden="true"
                                         x-data="{ s: 0.2, src: '' }" x-init="const fit = () => s = $el.clientWidth / 842; fit(); new ResizeObserver(fit).observe($el)"
                                         x-intersect.once.margin.200px="src = @js($item['preview'])">
                                        {{-- The preview loads only once its card is on screen (hidden iframes would all load at once) --}}
                                        @if ($item['preview'] && ! $item['paper'])
                                            <iframe :src="src || false" tabindex="-1" title=""
                                                    class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-[#e7e5e1]"
                                                    :style="`width: 842px; height: 1171px; transform: scale(${s})`" style="width: 842px; height: 1171px; transform: scale(0.2)"></iframe>
                                        @elseif ($item['preview'])
                                            <img :src="src || false" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-top">
                                        @else
                                            <div class="flex h-full items-center justify-center text-slate-400"><i class='bx bx-file text-4xl'></i></div>
                                        @endif
                                    </div>
                                    <div class="p-2.5 sm:flex sm:items-start sm:justify-between sm:gap-2 sm:p-3.5">
                                        <p class="line-clamp-2 text-xs font-semibold leading-snug text-slate-900 group-hover:text-[#2A57B4] sm:text-sm">{{ $item['name'] }}</p>
                                        <span class="mt-0.5 hidden shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold sm:inline-block {{ $item['paper'] ? 'bg-amber-50 text-amber-800' : 'bg-[#2A57B4]/[0.08] text-[#1E4090]' }}">{{ $item['paper'] ? 'Print' : 'Online' }}</span>
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
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $item['categoryLabel'] }} · {{ $item['paper'] ? 'Print' : 'Online' }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $item['description'] ?: 'No description yet.' }}</p>
                                    <div class="mt-3 grid gap-2">
                                        <a href="{{ $item['url'] }}" wire:navigate class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-sm font-semibold text-white hover:bg-[#24499A]">
                                            <i class='bx bx-edit text-base' aria-hidden="true"></i> Start this document
                                        </a>
                                        <button type="button" @click="openPreview({{ $k }})" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            <i class='bx bx-show text-base' aria-hidden="true"></i> Preview document
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
            No document matches your search.
            <button type="button" @click="q = ''; cat = 'all'" class="ml-1 font-semibold text-[#2A57B4] hover:underline">Show all</button>
        </p>
    </section>

    {{-- Touch screens: tapping a card opens its menu as a bottom sheet --}}
    <template x-teleport="body">
        <div x-show="menu !== null && touch()" x-cloak class="fixed inset-0 z-[90] flex items-end bg-slate-900/50" @click.self="menu = null"
             role="dialog" aria-modal="true" aria-label="Document options">
            <div x-show="menu !== null && touch()" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full"
                 class="w-full rounded-t-2xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl">
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300"></div>
                <template x-if="menu !== null">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="items[menu].categoryLabel + ' · ' + (items[menu].paper ? 'Print' : 'Online')"></p>
                        <p class="mt-1 text-base font-semibold text-slate-900" x-text="items[menu].name"></p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600" x-text="items[menu].description || 'No description yet.'"></p>
                        <div class="mt-4 grid gap-2">
                            <a :href="items[menu].url" wire:navigate class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-[#2A57B4] px-4 text-sm font-semibold text-white">
                                <i class='bx bx-edit text-base' aria-hidden="true"></i> Start this document
                            </a>
                            <button type="button" @click="openPreview(menu)" class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700">
                                <i class='bx bx-show text-base' aria-hidden="true"></i> Preview document
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Full preview of a form (a blank copy with the student's details; print forms show their template image) --}}
    <template x-teleport="body">
        <div x-show="preview !== null" x-cloak x-transition.opacity class="fixed inset-0 z-[95] flex flex-col bg-slate-900/60 sm:p-6"
             role="dialog" aria-modal="true" aria-labelledby="explore-preview-title" @click.self="preview = null">
            <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl">
                <template x-if="preview !== null">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <h3 id="explore-preview-title" class="truncate text-sm font-semibold text-slate-900" x-text="items[preview].name"></h3>
                                <p class="text-xs text-slate-500" x-text="items[preview].paper ? 'Sample of the printed form.' : 'A blank copy of the form. Nothing is saved.'"></p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <a :href="items[preview].url" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-sm font-semibold text-white hover:bg-[#24499A]">Start <span class="hidden sm:inline">this document</span></a>
                                <button type="button" @click="preview = null" aria-label="Close preview"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900"><i class="bx bx-x text-2xl" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto bg-[#e7e5e1]" x-data="{ w: 842, h: 1171, s: 1 }"
                             x-init="const fit = () => s = Math.min(1, $el.clientWidth / w); fit(); new ResizeObserver(fit).observe($el)"
                             @message.window="if ($refs.pf && $event.source === $refs.pf.contentWindow && $event.data && $event.data.documatePreviewHeight) h = Math.ceil($event.data.documatePreviewHeight)">
                            <template x-if="! items[preview].paper && items[preview].preview">
                                <div class="mx-auto overflow-hidden" :style="`width:${w * s}px;height:${h * s}px`">
                                    <iframe x-ref="pf" :src="items[preview].preview" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                                            :style="`width:${w}px;height:${h}px;transform:scale(${s});transform-origin:top left;`"></iframe>
                                </div>
                            </template>
                            <template x-if="items[preview].paper && items[preview].preview">
                                <img :src="items[preview].preview" alt="" class="mx-auto my-4 max-w-full bg-white shadow">
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
