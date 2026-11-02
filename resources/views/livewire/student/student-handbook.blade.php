{{-- Student › Handbook: the LNU Student Handbook (2022) as one long, searchable page with a contents sidebar.
     The text is formatted in the browser by resources/js/lnu-handbook.js (Alpine "lnuHandbook"). --}}
<div wire:ignore.self x-data="lnuHandbook()" class="min-h-screen rounded-2xl border border-slate-200 bg-white text-slate-800">

    {{-- Header: title, search, and how far you have read --}}
    <header class="sticky top-0 z-30 rounded-t-2xl border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="flex h-14 items-center gap-3 px-3 sm:px-5">
            <button type="button" @click="sidebarOpen = true" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open the contents">
                <i class="bx bx-menu-alt-left text-2xl" aria-hidden="true"></i>
            </button>
            <img src="{{ asset('images/lnu-seal.png') }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-contain" onerror="this.src='{{ asset('images/lnulogo.png') }}'">
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold text-slate-900">LNU Student Handbook</p>
                <p class="truncate text-xs text-slate-500">2022 edition · Leyte Normal University</p>
            </div>

            <div class="ml-auto flex items-center gap-1">
                <div class="relative">
                    <i class="bx bx-search pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                    <input x-model.debounce.250ms="search" @keydown.enter.prevent="$event.shiftKey ? prevMatch() : nextMatch()" type="search"
                           placeholder="Search the handbook" aria-label="Search the handbook"
                           class="h-9 w-32 rounded-lg border border-slate-200 bg-slate-50 py-0 pl-8 pr-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/15 sm:w-64">
                </div>
                <template x-if="search.trim()">
                    <div class="flex shrink-0 items-center text-xs text-slate-500">
                        <span class="whitespace-nowrap px-1.5 tabular-nums" x-text="matchCount ? currentMatch + ' of ' + matchCount : 'No match'" role="status"></span>
                        <button type="button" @click="prevMatch()" :disabled="!matchCount" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-slate-100 disabled:opacity-40" aria-label="Previous match"><i class="bx bx-chevron-up text-lg"></i></button>
                        <button type="button" @click="nextMatch()" :disabled="!matchCount" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-slate-100 disabled:opacity-40" aria-label="Next match"><i class="bx bx-chevron-down text-lg"></i></button>
                    </div>
                </template>
            </div>
        </div>
        <div class="h-0.5 bg-slate-100" aria-hidden="true"><div class="h-full bg-[#2A57B4] transition-[width] duration-150" :style="`width:${progress}%`"></div></div>
    </header>

    <div class="flex items-start">
        {{-- Contents (wide screens) --}}
        <aside class="sticky top-[3.625rem] hidden h-[calc(100vh-3.625rem)] w-72 shrink-0 overflow-y-auto border-r border-slate-200 bg-[#f7f8fa] lg:block" aria-label="Table of contents">
            <div class="flex items-center justify-between px-5 pb-2 pt-4">
                <p class="text-[13px] font-semibold text-slate-900">Contents</p>
                <span class="text-xs tabular-nums text-slate-400" x-text="progress + '% read'"></span>
            </div>
            @include('livewire.student.partials.handbook-toc')
        </aside>

        {{-- Contents (phones and tablets) --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Table of contents" @keydown.escape.window="sidebarOpen = false">
            <div class="absolute inset-0 bg-slate-900/40" @click="sidebarOpen = false" x-transition.opacity></div>
            <aside class="absolute inset-y-0 left-0 flex w-80 max-w-[88vw] flex-col bg-[#f7f8fa] shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">Contents</p>
                    <button type="button" @click="sidebarOpen = false" class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Close the contents"><i class="bx bx-x text-2xl"></i></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto pt-3">
                    @include('livewire.student.partials.handbook-toc')
                </div>
            </aside>
        </div>

        {{-- The handbook --}}
        <main class="min-w-0 flex-1">
            <div class="mx-auto max-w-3xl px-5 pb-24 pt-8 sm:px-8 sm:pt-10">

                {{-- Cover --}}
                <section x-show="!search.trim()" class="relative mb-12 overflow-hidden rounded-2xl bg-gradient-to-br from-[#e6f0fc] via-[#eee9f9] to-[#fdeef4] p-6 sm:p-8">
                    <img src="{{ asset('images/lnu-seal.png') }}" alt="" aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-56 w-56 opacity-[0.08] sm:h-72 sm:w-72" onerror="this.remove()">
                    <div class="relative flex items-center gap-4">
                        <img src="{{ asset('images/lnu-seal.png') }}" alt="Leyte Normal University seal" class="h-16 w-16 shrink-0 rounded-full bg-white object-contain p-1 shadow-sm sm:h-20 sm:w-20" onerror="this.src='{{ asset('images/lnulogo.png') }}'">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#1E4090]">Leyte Normal University</p>
                            <h1 class="text-2xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-3xl">Student Handbook</h1>
                            <p class="mt-0.5 text-sm text-slate-600">2022 edition · in effect from the first semester of A.Y. 2022–2023</p>
                        </div>
                    </div>
                    <p class="relative mt-5 max-w-2xl text-[15px] leading-relaxed text-slate-700">Your guide to the university: what it stands for, the services open to you, the academic and campus rules, and how conduct and discipline are handled. Search it, or jump to a part below.</p>
                    <div class="relative mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <template x-for="s in groupedSections()" :key="'cover-' + s.id">
                            <button type="button" @click="jumpToSection(s.id)" class="group flex items-center gap-2.5 rounded-xl bg-white/80 p-2.5 text-left shadow-sm ring-1 ring-white transition hover:bg-white hover:shadow">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg" :class="s.tint"><i class="bx text-lg" :class="s.icon"></i></span>
                                <span class="min-w-0">
                                    <span class="block text-[11px] text-slate-500" x-text="s.number"></span>
                                    <span class="block truncate text-[13px] font-semibold text-slate-900 group-hover:text-[#2A57B4]" x-text="s.name"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </section>

                <template x-for="section in groupedSections()" :key="section.id">
                    <section :id="'section-' + section.id" class="mb-16 scroll-mt-20">
                        {{-- The part's header --}}
                        <div class="mb-8 flex items-start gap-4 rounded-2xl p-5 sm:p-6" :class="section.tint">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-white/80 shadow-sm"><i class="bx text-2xl" :class="section.icon"></i></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium opacity-80" x-text="section.number"></p>
                                <h2 class="text-xl font-semibold tracking-[-0.015em] text-slate-900 sm:text-2xl" x-text="section.name"></h2>
                                <p class="mt-1 text-sm leading-relaxed text-slate-700" x-text="section.blurb"></p>
                                <p class="mt-2 text-xs text-slate-600"><span x-text="topicCount(section.id)"></span> topics</p>
                            </div>
                        </div>

                        <div class="handbook-text" x-html="formatPage(section.block.text)"></div>
                    </section>
                </template>

                <div x-show="search.trim() && matchCount === 0" x-cloak class="rounded-2xl border border-dashed border-slate-300 py-16 text-center">
                    <i class="bx bx-search-alt text-4xl text-slate-300" aria-hidden="true"></i>
                    <p class="mt-2 text-sm text-slate-600">Nothing in the handbook matches “<strong x-text="search"></strong>”.</p>
                </div>

                <footer class="mt-8 flex items-center gap-3 border-t border-slate-200 pt-6 text-xs text-slate-500">
                    <img src="{{ asset('images/lnulogo.png') }}" alt="" class="h-6 w-6 opacity-70" onerror="this.remove()">
                    <span>Leyte Normal University · Office of the Vice President for Student Development. The printed handbook prevails where this edition differs.</span>
                </footer>
            </div>
        </main>
    </div>

    {{-- Back to the top --}}
    <button type="button" x-show="showBackToTop" x-cloak x-transition.opacity @click="scrollToTop()"
            class="fixed bottom-24 right-5 z-30 grid h-11 w-11 place-items-center rounded-full bg-white text-[#2A57B4] shadow-lg ring-1 ring-slate-200 hover:bg-slate-50" aria-label="Back to the top">
        <i class="bx bx-up-arrow-alt text-2xl" aria-hidden="true"></i>
    </button>

    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }

        /* Part colours (DESIGN.md work tints) */
        .hb-tint-sky { background: #dcecfa; color: #1E4090; }
        .hb-tint-mint { background: #d9f3e1; color: #1f6b3a; }
        .hb-tint-lavender { background: #e6e0f5; color: #5b3fa0; }
        .hb-tint-rose { background: #fde0ec; color: #9d2457; }
        .hb-tint-yellow { background: #fef3c7; color: #8a5a00; }

        /* Reading text */
        .handbook-text { --step: 1.6rem; color: #334155; font-size: 15.5px; line-height: 1.75; overflow-wrap: anywhere; }
        .handbook-text > * { margin-left: calc(var(--d, 0) * var(--step)); }
        .handbook-text p { margin-top: 0; margin-bottom: .9rem; }
        .handbook-text [id^="anchor-"] { scroll-margin-top: 5rem; }
        .handbook-text .hb-anchor { display: block; height: 0; }

        /* Topic heading (I. II. …) with its icon */
        .handbook-text .hb-h2 {
            display: flex; align-items: center; gap: .75rem;
            margin: 2.75rem 0 1rem; padding-bottom: .65rem; border-bottom: 1px solid #e2e8f0;
            color: #0f172a; font-size: 1.25rem; line-height: 1.3; font-weight: 600; letter-spacing: -.01em;
        }
        .handbook-text .hb-h2:first-child, .handbook-text .hb-anchor + .hb-h2 { margin-top: .5rem; }
        .handbook-text .hb-h2-icon {
            display: grid; place-items: center; flex-shrink: 0; width: 2.25rem; height: 2.25rem;
            border-radius: .6rem; background: #e2e9f7; color: #2A57B4; font-size: 1.2rem;
        }
        .handbook-text .hb-roman { color: #2A57B4; font-weight: 600; margin-right: .15rem; }

        /* Numbered heading (1. 1.1) */
        .handbook-text .hb-h3 {
            display: flex; align-items: baseline; gap: .6rem;
            margin-top: 1.6rem; margin-bottom: .55rem;
            color: #0f172a; font-size: 1.05rem; line-height: 1.4; font-weight: 600;
        }
        .handbook-text .hb-h3[style*="--d:1"], .handbook-text .hb-h3[style*="--d:2"] { font-size: .98rem; margin-top: 1.2rem; }
        .handbook-text .hb-h4 { margin: 1.5rem 0 .4rem; color: #1E4090; font-size: 1rem; font-weight: 600; }

        /* Number badges and hanging indents */
        .handbook-text .hb-num {
            flex-shrink: 0; min-width: 1.6rem; padding: .05rem .4rem; border-radius: .4rem;
            background: #eef2f8; color: #2A57B4; font-size: .78rem; font-weight: 600; line-height: 1.45rem;
            text-align: center; font-variant-numeric: tabular-nums;
        }
        .handbook-text .hb-h3 .hb-num { background: #2A57B4; color: #fff; }
        .handbook-text .hb-num-letter { background: #fef3c7; color: #8a5a00; }
        .handbook-text .hb-item { display: flex; align-items: flex-start; gap: .65rem; margin-bottom: .55rem; }
        .handbook-text .hb-item > .hb-num { margin-top: .12rem; }
        .handbook-text .hb-item > div { min-width: 0; flex: 1; }
        .handbook-text .hb-item-lead > div { font-weight: 600; color: #1e293b; }

        /* Bullets */
        .handbook-text .hb-bullets { list-style: none; margin-top: .2rem; margin-bottom: .9rem; padding: 0; }
        .handbook-text .hb-bullets li { position: relative; padding-left: 1.15rem; margin-bottom: .35rem; }
        .handbook-text .hb-bullets li::before { content: ''; position: absolute; left: .2rem; top: .7em; width: .38rem; height: .38rem; border-radius: 9999px; background: #2A57B4; }

        /* Emphasis */
        .handbook-text strong, .handbook-text .hb-term { font-weight: 600; color: #0f172a; }
        .handbook-text .hb-dash { color: #94a3b8; }
        .handbook-text em { font-style: italic; color: #64748b; }
        .handbook-text .hb-law { color: #1E4090; font-weight: 500; }

        /* Vision / Mission */
        .handbook-text .hb-callout {
            margin-top: .25rem; margin-bottom: 1.25rem; padding: .9rem 1.1rem; border-left: 3px solid #2A57B4;
            border-radius: 0 .75rem .75rem 0; background: #f0f5fd; color: #1e293b; font-size: 1.05rem; font-style: italic; line-height: 1.6;
        }

        /* Offense levels */
        .handbook-text .hb-sev { display: inline-block; width: .65rem; height: .65rem; margin-right: .5rem; border-radius: 9999px; vertical-align: .05em; }
        .handbook-text .hb-sev-light { background: #f59e0b; }
        .handbook-text .hb-sev-serious { background: #ea580c; }
        .handbook-text .hb-sev-very { background: #e11d48; }

        /* Tables */
        .handbook-text .hb-table-wrap { overflow-x: auto; margin-top: .5rem; margin-bottom: 1.25rem; border: 1px solid #e2e8f0; border-radius: .75rem; }
        .handbook-text table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        .handbook-text th, .handbook-text td { padding: .55rem .8rem; text-align: left; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
        .handbook-text th { background: #f8fafc; color: #0f172a; font-weight: 600; border-bottom-color: #e2e8f0; }
        .handbook-text tr:last-child td { border-bottom: 0; }
        .handbook-text td:first-child { font-weight: 500; color: #0f172a; white-space: nowrap; }

        /* The hymn and the committee */
        .handbook-text .hb-credit { margin-bottom: .2rem; color: #475569; font-size: .9rem; }
        .handbook-text .hb-credit span { display: inline-block; min-width: 3.6rem; color: #94a3b8; }
        .handbook-text .hb-credit + .hb-verse { margin-top: 1rem; }
        .handbook-text .hb-verse { margin: 0 0 .15rem; padding-left: 1rem; border-left: 2px solid #fef3c7; color: #1e293b; font-family: Georgia, 'Times New Roman', serif; font-size: 1.02rem; font-style: italic; line-height: 1.7; }
        .handbook-text .hb-person { display: inline-flex; align-items: center; gap: .6rem; width: calc(50% - .5rem); margin: 0 .5rem .6rem 0; vertical-align: top; }
        @media (max-width: 640px) { .handbook-text .hb-person { width: 100%; } }
        .handbook-text .hb-person-icon { display: grid; place-items: center; flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; background: #e2e9f7; color: #2A57B4; }
        .handbook-text .hb-person-cred { display: block; color: #64748b; font-size: .82rem; font-weight: 400; }

        /* Search */
        .handbook-text mark { background: #fde68a; color: inherit; border-radius: .2rem; padding: 0 .1rem; }
        .handbook-text mark.hb-mark-active { background: #f59e0b; color: #fff; box-shadow: 0 0 0 2px #fde68a; }

        .hb-flash { animation: hbFlash 1.6s ease-out; border-radius: .5rem; }
        @keyframes hbFlash { 0% { background-color: rgba(42, 87, 180, .14); } 100% { background-color: transparent; } }

        @media (max-width: 640px) {
            .handbook-text { --step: .9rem; font-size: 15px; }
            .handbook-text .hb-h2 { font-size: 1.12rem; }
        }
    </style>

    {{-- The handbook's text (one block per part). --}}
    <script type="application/json" id="lnu-handbook-data">
        {!! $handbookJson !!}
    </script>
</div>
