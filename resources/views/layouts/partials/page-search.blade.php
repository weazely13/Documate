{{-- Page search in the top bar, like VS Code's: type and every page this person can open drops down
     (Ctrl K / ⌘K from anywhere). Phones get a search button that opens the same box over the top bar.
     Needs: $navSections (from layouts.app). --}}
@php
    $user = auth()->user();
    $iconFor = [];
    $pages = collect();
    foreach ($navSections as $section) {
        foreach ($section['items'] as [$label, $icon, $routeName]) {
            $iconFor[$routeName] = $icon;
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                $pages->push(['label' => $label, 'group' => $section['label'], 'icon' => $icon, 'url' => route($routeName, [], false),
                    'hint' => \App\Support\ChatScreen::purpose($routeName) ?? '', 'keywords' => '']);
            }
        }
    }
    // The role's other pages (sub-pages, help pages) from the assistant's navigation map
    foreach (app(\App\Services\ChatbotService::class)->navigationPages($user) as $p) {
        if (empty($p['url']) || in_array($p['route'], ['login', 'register', 'password.request'], true)) {
            continue;
        }
        $label = \Illuminate\Support\Str::afterLast($p['label'], '› ');
        if ($pages->contains(fn ($x) => $x['url'] === $p['url'] && strcasecmp($x['label'], $label) === 0)) {
            continue;
        }
        $pages->push(['label' => $label, 'group' => str_contains($p['label'], '›') ? trim(\Illuminate\Support\Str::before($p['label'], '›')) : 'Pages',
            'icon' => $iconFor[$p['route']] ?? 'bx-file-blank', 'url' => $p['url'], 'hint' => $p['description'] ?? '', 'keywords' => $p['keywords'] ?? '']);
    }
    foreach ([['Feedback & Issues', 'bx-message-square-dots', 'support.feedback'], ['Handbook', 'bx-book', 'handbook'], ['FAQs', 'bx-help-circle', 'faqs']] as [$label, $icon, $r]) {
        if (\Illuminate\Support\Facades\Route::has($r) && ! $pages->contains('url', route($r, [], false))) {
            $pages->push(['label' => $label, 'group' => 'Help', 'icon' => $icon, 'url' => route($r, [], false), 'hint' => '', 'keywords' => 'help support']);
        }
    }
    $pages = $pages->unique('url')->values(); // one entry per page (the sidebar's name wins)
@endphp

<div class="page-search" x-data="{
        q: '', open: false, active: 0, mobile: false, records: [], loading: false, timer: null, asked: '',
        items: @js($pages),
        get pages() {
            const words = this.q.toLowerCase().split(/\s+/).filter(Boolean);
            const list = this.items.filter(i => words.every(w => (i.label + ' ' + i.group + ' ' + i.hint + ' ' + i.keywords).toLowerCase().includes(w)));
            // Page names that start with what was typed come first
            return words.length ? list.sort((a, b) => (b.label.toLowerCase().startsWith(words[0]) ? 1 : 0) - (a.label.toLowerCase().startsWith(words[0]) ? 1 : 0)) : list;
        },
        {{-- Pages first, then the documents and appointments the server found for the same words --}}
        get results() {
            const pages = this.pages.map(p => ({ ...p, group: this.q ? 'Pages' : p.group }));
            return this.q.trim().length >= 2 ? [...pages.slice(0, 6), ...this.records] : pages;
        },
        find() {
            clearTimeout(this.timer);
            const term = this.q.trim();
            if (term.length < 2) { this.records = []; this.loading = false; return; }
            this.loading = true;
            this.timer = setTimeout(async () => {
                try {
                    const r = await fetch(@js(route('search.records')) + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' } });
                    const data = r.ok ? await r.json() : { items: [] };
                    if (this.q.trim() === term) { this.records = data.items || []; this.active = 0; }
                } catch (e) { this.records = []; }
                if (this.q.trim() === term) this.loading = false;
            }, 250);
        },
        show() { this.open = true; this.active = 0; },
        focus() { this.mobile = window.matchMedia('(max-width: 767px)').matches; this.show(); this.$nextTick(() => { this.$refs.q.focus(); this.$refs.q.select(); }); },
        close() { this.open = false; this.mobile = false; this.q = ''; this.$refs.q.blur(); },
        move(d) { const n = this.results.length; if (! n) return; this.open = true; this.active = (this.active + d + n) % n; this.$nextTick(() => document.getElementById('ps-opt-' + this.active)?.scrollIntoView({ block: 'nearest' })); },
        go(i) { const r = this.results[i]; if (! r) return; this.close(); window.Livewire?.navigate ? Livewire.navigate(r.url) : (location.href = r.url); },
     }"
     :class="mobile && 'is-mobile-open'"
     @open-page-search.window="focus()"
     @keydown.escape="close()"
     @click.outside="if (open) close()">

    {{-- Phones: just the button --}}
    <button type="button" class="page-search-btn" @click="focus()" aria-label="Search pages" data-tooltip="Search pages">
        <i class='bx bx-search' aria-hidden="true"></i>
    </button>

    <div class="page-search-box" role="search">
        <i class='bx bx-search page-search-icon' aria-hidden="true"></i>
        <input x-ref="q" type="search" x-model="q" @focus="show()" @input="show(); find()"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go(active)"
               placeholder="Search pages, documents, appointments" autocomplete="off" aria-label="Search pages, documents and appointments"
               role="combobox" aria-autocomplete="list" aria-controls="page-search-results" :aria-expanded="open.toString()"
               :aria-activedescendant="open && results.length ? 'ps-opt-' + active : null">
        <kbd class="page-search-kbd" x-show="! q">Ctrl K</kbd>
        <button type="button" class="page-search-close" x-show="mobile" @click="close()" aria-label="Close search"><i class='bx bx-x'></i></button>

        <div class="page-search-results" x-show="open" x-cloak x-transition.opacity.duration.100ms>
            <ul id="page-search-results" role="listbox" aria-label="Pages">
                <template x-for="(r, i) in results" :key="r.url + r.label">
                    <li>
                        <p class="page-search-group" x-show="i === 0 || results[i - 1].group !== r.group" x-text="r.group"></p>
                        <a :href="r.url" :id="'ps-opt-' + i" role="option" :aria-selected="(active === i).toString()"
                           @click.prevent="go(i)" @mouseenter="active = i" :class="active === i && 'is-active'">
                            <i class="bx" :class="r.icon" aria-hidden="true"></i>
                            <span class="page-search-text">
                                <span class="page-search-label" x-text="r.label"></span>
                                <span class="page-search-hint" x-text="r.hint" x-show="r.hint"></span>
                            </span>
                        </a>
                    </li>
                </template>
                <li x-show="loading" class="page-search-empty"><i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i> Looking through your documents and appointments…</li>
                <li x-show="! results.length && ! loading" class="page-search-empty">Nothing matches “<span x-text="q"></span>”. Try a name, a transaction code or a date.</li>
            </ul>
            <p class="page-search-foot"><kbd>↑</kbd><kbd>↓</kbd> to move · <kbd>Enter</kbd> to open · <kbd>Esc</kbd> to close</p>
        </div>
    </div>
</div>
