{{-- The handbook's contents: one group per part, collapsible, with a filter. Used in the sidebar and the phone drawer. --}}
<div class="px-3 pb-3 pt-1">
    <div class="relative">
        <i class="bx bx-filter-alt pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
        <input type="search" x-model="tocFilter" placeholder="Filter topics" aria-label="Filter the contents"
               class="h-9 w-full rounded-lg border border-slate-200 bg-white py-0 pl-8 pr-2 text-[13px] text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/15">
    </div>
</div>
<nav class="space-y-1 px-2 pb-6" aria-label="Contents">
    <template x-for="g in tocGroups().filter(x => x.available)" :key="'g' + g.idx">
        <div x-show="groupMatches(g)">
            <button type="button" @click="g.children.length ? toggleGroup(g) : jumpToPage(g.idx, $event)" :aria-expanded="g.children.length ? isGroupOpen(g) : null"
                    class="group flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left transition"
                    :class="groupIsActive(g) ? 'bg-[#e2e9f7]' : 'hover:bg-slate-100'">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md" :class="groupIsActive(g) ? 'bg-[#2A57B4] text-white' : 'bg-white text-[#2A57B4] ring-1 ring-slate-200'">
                    <i class="bx text-base" :class="g.icon" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span x-show="g.number" class="block text-[11px] font-medium text-slate-500" x-text="g.number"></span>
                    <span class="block text-[13px] font-semibold leading-snug" :class="groupIsActive(g) ? 'text-[#1E4090]' : 'text-slate-800'" x-text="g.label"></span>
                </span>
                <i x-show="g.children.length" class="bx bx-chevron-down shrink-0 text-lg text-slate-400 transition-transform" :class="isGroupOpen(g) ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>
            <ul x-show="g.children.length && isGroupOpen(g)" class="relative mb-2 ml-[1.35rem] mt-0.5 border-l border-slate-200 pl-2">
                <template x-for="c in visibleChildren(g)" :key="'c' + c.idx">
                    <li>
                        <a x-show="c.page !== null" :href="'#anchor-' + c.idx" @click="jumpToPage(c.idx, $event)"
                           class="relative flex gap-1.5 rounded-md py-1.5 pr-2 leading-snug transition"
                           :style="'padding-left:' + (0.5 + (c.level - 1) * 0.85) + 'rem'"
                           :class="[c.idx === activeTocIndex ? 'bg-white font-medium text-[#1E4090] shadow-sm ring-1 ring-[#2A57B4]/15' : 'text-slate-600 hover:bg-white hover:text-slate-900', c.level > 1 ? 'text-[12.5px]' : 'text-[13px]']">
                            <span x-show="c.idx === activeTocIndex" class="absolute -left-[0.6rem] top-1.5 bottom-1.5 w-0.5 rounded-full bg-[#2A57B4]" aria-hidden="true"></span>
                            <span x-show="splitLabel(c.label).num" class="shrink-0 tabular-nums" :class="c.idx === activeTocIndex ? 'text-[#2A57B4]' : 'text-slate-400'" x-text="splitLabel(c.label).num"></span>
                            <span x-text="splitLabel(c.label).text"></span>
                        </a>
                        <span x-show="c.page === null" class="flex gap-1.5 py-1.5 pr-2 text-[12.5px] leading-snug text-slate-300" :style="'padding-left:' + (0.5 + (c.level - 1) * 0.85) + 'rem'" title="Not in this digital edition">
                            <span x-text="c.label"></span>
                        </span>
                    </li>
                </template>
            </ul>
        </div>
    </template>

    {{-- Parts of the printed handbook that are not in this edition --}}
    <div x-data="{ more: false }" class="pt-2" x-show="!tocFilter.trim()">
        <button type="button" @click="more = !more" :aria-expanded="more" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-xs font-medium text-slate-500 hover:bg-slate-100">
            <i class="bx bx-lock-alt text-sm" aria-hidden="true"></i>
            <span class="flex-1">In the printed handbook only</span>
            <i class="bx bx-chevron-down text-base transition-transform" :class="more ? 'rotate-180' : ''" aria-hidden="true"></i>
        </button>
        <ul x-show="more" class="ml-[1.35rem] border-l border-slate-200 pl-3 text-[12.5px] leading-snug text-slate-400">
            <template x-for="g in tocGroups().filter(x => !x.available)" :key="'u' + g.idx">
                <li class="py-1" x-text="(g.number ? g.number + ': ' : '') + g.label"></li>
            </template>
        </ul>
    </div>
</nav>
