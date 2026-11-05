{{-- A guided tour of the page (resources/js/tour.js), the same look as the template editor's tour.
     <x-dm.tour key="documate.tour.home.v1" :steps="[['target' => 'search', 'title' => '…', 'text' => '…'], …]" />
     Mark the parts with data-tour="…". Start it from a button with $dispatch('start-tour').
     :auto="false" keeps it from opening by itself on the first visit. --}}
@props(['key', 'steps' => [], 'auto' => true])

<div x-data="dmTour(@js($key), @js(array_values($steps)), @js((bool) $auto))"
     @start-tour.window="start()" @keydown.window="key($event)">
    <template x-teleport="body">
        <template x-if="step">
            <div class="fixed inset-0 z-[120]" role="dialog" aria-modal="true" aria-labelledby="dm-tour-title">
                {{-- The spotlight: everything but the highlighted part is dimmed. A click outside the card ends the tour. --}}
                <div class="absolute inset-0" @click="end()"></div>
                <div class="pointer-events-none absolute rounded-xl" :style="spot"></div>
                {{-- Shown only once placed beside its part, so it never passes through the middle of the screen --}}
                <div class="absolute rounded-2xl bg-white p-4 shadow-2xl transition-opacity duration-150" :style="card"
                     :class="ready ? 'opacity-100' : 'pointer-events-none opacity-0'">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-medium tabular-nums text-[#2A57B4]" x-text="`Step ${i + 1} of ${steps.length}`"></span>
                        <button type="button" @click="end()" class="text-xs font-medium text-slate-500 hover:text-slate-800">Skip tour</button>
                    </div>
                    <h2 id="dm-tour-title" class="mt-1.5 text-base font-semibold text-slate-900" x-text="step.title"></h2>
                    <p class="mt-1 text-[13px] leading-relaxed text-slate-600" x-text="step.text"></p>
                    <button type="button" x-show="step.cta" @click="cta()" class="mt-2 inline-flex items-center gap-1 text-[13px] font-medium text-[#2A57B4] hover:underline">
                        <span x-text="step.cta?.label"></span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
                    </button>
                    <div class="mt-4 flex items-center justify-between gap-2">
                        {{-- Dots for a short tour; a progress bar for a long one, so the buttons always fit --}}
                        <span class="flex min-w-0 gap-1" aria-hidden="true" x-show="steps.length <= 9">
                            <template x-for="(s, n) in steps" :key="n"><span class="h-1.5 rounded-full transition-all" :class="n === i ? 'w-4 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"></span></template>
                        </span>
                        <span class="block h-1.5 w-full min-w-0 max-w-[7rem] overflow-hidden rounded-full bg-slate-200" aria-hidden="true" x-show="steps.length > 9">
                            <span class="block h-full rounded-full bg-[#2A57B4] transition-all" :style="`width: ${Math.round((i + 1) / steps.length * 100)}%`"></span>
                        </span>
                        <span class="flex shrink-0 gap-2">
                            <button type="button" x-show="i > 0" @click="back()" class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-3 text-[13px] font-medium text-slate-700 hover:bg-slate-50">Back</button>
                            <button type="button" @click="next()" x-effect="ready && $nextTick(() => $el.focus({ preventScroll: true }))" :disabled="busy" class="inline-flex h-8 items-center rounded-lg bg-[#2A57B4] px-3.5 text-[13px] font-medium text-white hover:bg-[#24499A]"
                                    x-text="nextLabel"></button>
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </template>
</div>
