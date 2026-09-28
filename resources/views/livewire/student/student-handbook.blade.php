<div
    wire:ignore.self
    x-data="lnuHandbook()"
    x-init="init()"
    class="min-h-screen bg-white text-slate-800"
>
    <!-- Full-width banner (NOT sticky) -->
    
    <header class="sticky top-0 z-30 w-full h-16 bg-[#2A57B4] border-b-4 border-[#d8ca06] shadow-sm">
        <div class="h-full w-full px-3 sm:px-6 flex items-center gap-3">

            <!-- Mobile sidebar toggle -->
            <button
                @click="sidebarOpen = true"
                type="button"
                class="lg:hidden shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-white hover:bg-white/10"
                aria-label="Open table of contents"
            >
                <i class='bx bx-menu text-2xl'></i>
            </button>

            <img
                src="{{ asset('images/lnulogo.png') }}"
                alt="Leyte Normal University Logo"
                class="h-10 w-10 rounded-full bg-white object-contain p-0.5 shrink-0"
                onerror="this.style.display='none'"
            >

            <div class="min-w-0 leading-tight">
                <div class="text-white font-bold text-sm sm:text-base truncate">
                    LNU Student Handbook
                </div>
                <div class="text-blue-100 text-[11px] sm:text-xs truncate">
                    2022 Edition &bull; Leyte Normal University
                </div>
            </div>

            <div class="ml-auto shrink-0">
                <div class="flex items-center gap-1">
                    <div class="relative">
                        <i class='bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-blue-200 text-base'></i>
                        <input
                            x-model.debounce.200ms="search"
                            @keydown.enter.prevent="$event.shiftKey ? prevMatch() : nextMatch()"
                            type="search"
                            placeholder="Find in handbook..."
                            class="w-36 sm:w-56 rounded-full border border-white/20 bg-white/10 pl-8 pr-3 py-1.5 text-sm text-white placeholder-blue-200 outline-none focus:ring-2 focus:ring-[#d8ca06] focus:bg-white/20"
                        >
                    </div>

                    <template x-if="search.trim()">
                        <div class="flex items-center gap-0.5 text-blue-100 text-xs shrink-0">
                            <span class="tabular-nums px-1 whitespace-nowrap" x-text="matchCount ? currentMatch + '/' + matchCount : '0/0'"></span>
                            <button
                                @click="prevMatch()"
                                type="button"
                                :disabled="!matchCount"
                                class="w-7 h-7 rounded-full flex items-center justify-center hover:bg-white/10 disabled:opacity-40"
                                aria-label="Previous match"
                            >
                                <i class='bx bx-chevron-up text-lg'></i>
                            </button>
                            <button
                                @click="nextMatch()"
                                type="button"
                                :disabled="!matchCount"
                                class="w-7 h-7 rounded-full flex items-center justify-center hover:bg-white/10 disabled:opacity-40"
                                aria-label="Next match"
                            >
                                <i class='bx bx-chevron-down text-lg'></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </header>

    <div class="flex items-start">

        <!-- Desktop sidebar: full Table of Contents (NOT sticky) -->
        <aside class="hidden lg:block w-72 shrink-0 self-start sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto border-r border-slate-200 bg-slate-50">
            <nav class="p-4 space-y-0.5">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2 px-3">
                    Table of Contents
                </div>
                <template x-for="(item, idx) in toc" :key="idx">
                    <div>
                        <a
                            x-show="item.page !== null"
                            :href="'#anchor-' + idx"
                            @click="jumpToPage(idx, $event)"
                            :style="'padding-left:' + (12 + item.level * 14) + 'px'"
                            class="block py-1.5 pr-3 rounded-md text-sm leading-snug border-l-4 transition-colors"
                            :class="tocItemClass(item, idx)"
                            x-text="item.label"
                        ></a>
                        <span
                            x-show="item.page === null"
                            :style="'padding-left:' + (12 + item.level * 14) + 'px'"
                            class="block py-1.5 pr-3 text-sm leading-snug border-l-4 border-transparent text-slate-300 cursor-not-allowed select-none"
                            :class="item.level === 0 ? 'font-bold uppercase tracking-wide text-[11px] mt-3' : ''"
                            x-text="item.label"
                            title="Not included in this digital edition"
                        ></span>
                    </div>
                </template>
            </nav>
        </aside>

        <!-- Mobile sidebar drawer -->
        <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/40" @click="sidebarOpen = false"></div>
            <aside class="absolute left-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-xl overflow-y-auto">
                <div class="h-16 flex items-center justify-between px-4 bg-[#2A57B4] border-b-4 border-[#d8ca06]">
                    <span class="text-white font-bold text-sm">Table of Contents</span>
                    <button @click="sidebarOpen = false" type="button" class="text-white w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10" aria-label="Close">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>
                <nav class="p-4 space-y-0.5">
                    <template x-for="(item, idx) in toc" :key="'m-' + idx">
                        <div>
                            <a
                                x-show="item.page !== null"
                                :href="'#anchor-' + idx"
                                @click="jumpToPage(idx, $event)"
                                :style="'padding-left:' + (12 + item.level * 14) + 'px'"
                                class="block py-1.5 pr-3 rounded-md text-sm leading-snug border-l-4 transition-colors"
                                :class="tocItemClass(item, idx)"
                                x-text="item.label"
                            ></a>
                            <span
                                x-show="item.page === null"
                                :style="'padding-left:' + (12 + item.level * 14) + 'px'"
                                class="block py-1.5 pr-3 text-sm leading-snug border-l-4 border-transparent text-slate-300 cursor-not-allowed select-none"
                                :class="item.level === 0 ? 'font-bold uppercase tracking-wide text-[11px] mt-3' : ''"
                                x-text="item.label"
                                title="Not included in this digital edition"
                            ></span>
                        </div>
                    </template>
                </nav>
            </aside>
        </div>

        <!-- Long-scroll content -->
        <main class="flex-1 min-w-0">
            <div class="max-w-3xl mx-auto px-5 sm:px-8 py-10">

                <template x-for="section in groupedSections()" :key="section.id">
                    <section :id="'section-' + section.id" class="mb-14 scroll-mt-20">

                        <div class="mb-6 pb-3 border-b-2 border-[#2A57B4]">
                            <h2 class="text-xl sm:text-2xl font-bold text-[#2A57B4]" x-text="section.name"></h2>
                        </div>

                        <div
                            class="handbook-text text-[15px] leading-7 text-slate-700"
                            x-html="formatPage(section.block.text)"
                        ></div>

                    </section>
                </template>

                <div x-show="search && matchCount === 0" x-cloak class="py-16 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                        <i class='bx bx-search-alt text-xl'></i>
                    </div>
                    <p class="text-slate-500 text-sm">No matches found for <strong x-text="search"></strong>.</p>
                </div>

            </div>
        </main>
    </div>

    <style>
        [x-cloak] { display: none !important; }

        html {
            scroll-behavior: smooth;
        }

        .handbook-text {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.78;
            overflow-wrap: anywhere;
        }

        .handbook-text p {
            margin: 0 0 1rem;
        }

        .handbook-text .hb-section-title,
        .handbook-text .hb-heading-1 {
            margin: 2.25rem 0 .9rem;
            color: #2A57B4;
            font-size: 1.15rem;
            line-height: 1.35;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .handbook-text .hb-heading-2 {
            margin: 1.85rem 0 .65rem;
            color: #1f3f85;
            font-size: 1.05rem;
            line-height: 1.4;
            font-weight: 750;
        }

        .handbook-text .hb-heading-3 {
            margin: 1.45rem 0 .5rem;
            color: #334155;
            font-size: .98rem;
            line-height: 1.45;
            font-weight: 700;
        }

        .handbook-text .hb-heading-4 {
            margin: 1.15rem 0 .4rem;
            color: #475569;
            font-size: .94rem;
            line-height: 1.45;
            font-weight: 650;
        }

        .handbook-text .hb-heading-5 {
            margin: .9rem 0 .3rem;
            color: #64748b;
            font-size: .9rem;
            line-height: 1.45;
            font-weight: 650;
        }

        /* Numbered hierarchy: 1. / 1.1 / 1.1.1 / 1.1.1.1 */
        .handbook-text .hb-numbered {
            display: block;
            margin: .38rem 0;
            padding-left: 1.5rem;
            text-indent: -1.5rem;
        }

        .handbook-text .hb-numbered strong.hb-number {
            font-weight: 750;
            color: #1e293b;
        }

        /* Bulleted requirements/items */
        .handbook-text .hb-list {
            margin: .45rem 0 1.15rem;
            padding-left: 1.6rem;
        }

        .handbook-text .hb-list li {
            margin: .38rem 0;
            padding-left: .2rem;
        }

        .handbook-text .hb-list li::marker {
            color: #2A57B4;
            font-weight: 700;
        }

        .handbook-text .hb-list--nested {
            margin-top: .2rem;
            margin-bottom: .45rem;
            padding-left: 1.45rem;
        }

        .handbook-text .hb-paragraph {
            margin: 0 0 1rem;
        }

        .handbook-text .hb-label {
            font-weight: 700;
            color: #1e293b;
        }

        .handbook-text strong {
            font-weight: 750;
            color: #1e293b;
        }

        .handbook-text em {
            font-style: italic;
            color: #475569;
        }

        .handbook-text mark {
            background: rgba(216, 202, 6, .45);
            border-radius: .2rem;
            padding: 0 .1rem;
        }
        .handbook-text mark.hb-mark-active {
            background: #d8ca06;
            box-shadow: 0 0 0 2px #2A57B4;
            border-radius: .2rem;
        }

        .handbook-text .hb-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin: 1rem 0 1.25rem;
        }

        .handbook-text table {
            width: 100%;
            border-collapse: collapse;
            font-size: .92rem;
        }

        .handbook-text th,
        .handbook-text td {
            padding: .65rem .75rem;
            border: 1px solid #cbd5e1;
            text-align: left;
            vertical-align: top;
        }

        .handbook-text th {
            font-weight: 750;
            background: #f1f5f9;
            color: #1e293b;
        }

        .hb-flash {
            animation: hbFlash 1.6s ease-out;
        }

        @keyframes hbFlash {
            0% {
                background-color: rgba(216, 202, 6, .55);
            }
            100% {
                background-color: transparent;
            }
        }
    </style>

    {{-- Page data lives in a JSON script tag. The original handbook text is preserved. --}}
    <script type="application/json" id="lnu-handbook-data">
        {!! $handbookJson !!}
    </script>
</div>