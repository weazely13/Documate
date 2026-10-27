{{-- The selected metric's total beside its daily area chart, with the metrics as a strip to switch between.
     @include('livewire.partials.insights.trend-hero', ['title' => 'Office activity', 'metrics' => [...DashboardInsights::metric()], 'range' => 30])
     Same data and Alpine component (insightTrend) as the trend partial. --}}
@php $titleId = 'hero-' . \Illuminate\Support\Str::slug($title); @endphp
<section class="cq rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="{{ $titleId }}"
         wire:key="hero-{{ md5(json_encode($metrics)) }}" x-data="insightTrend(@js($metrics))">
    <h2 id="{{ $titleId }}" class="text-base font-semibold text-gray-900">{{ $title }}</h2>

    <div class="mt-4 grid gap-6 cq-md:grid-cols-[minmax(0,12rem)_minmax(0,1fr)] cq-md:items-center">
        {{-- Headline: the selected metric --}}
        <div aria-live="polite">
            <p class="text-sm text-gray-500" x-text="current.label"></p>
            <p class="mt-1 text-4xl font-bold leading-none tracking-tight tabular-nums text-gray-900" x-text="(current.total ?? 0).toLocaleString()"></p>
            <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                <template x-if="current.delta !== null && current.delta !== undefined">
                    <span class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-semibold"
                          :class="current.delta > 0 ? 'bg-emerald-50 text-emerald-700' : (current.delta < 0 ? 'bg-rose-50 text-rose-700' : 'bg-gray-100 text-gray-600')">
                        <i class='bx text-sm' :class="current.delta >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt'" aria-hidden="true"></i>
                        <span x-text="Math.abs(current.delta) + '%'"></span>
                    </span>
                </template>
                <span class="text-gray-500"
                      x-text="current.delta === null || current.delta === undefined ? deltaText(current) : 'vs. the {{ $range }} days before'"></span>
            </p>
        </div>

        {{-- Area chart --}}
        <div>
            <div class="relative h-40 pl-8">
                <template x-for="(t, k) in ticks" :key="k">
                    <div class="pointer-events-none absolute inset-x-0 flex h-0 items-center" :style="`top: ${k * 50}%`" aria-hidden="true">
                        <span class="w-7 -translate-y-1/2 pr-1.5 text-right text-[11px] tabular-nums text-gray-400" x-text="t"></span>
                        <span class="h-px flex-1 -translate-y-1/2" :class="k === 2 ? 'bg-gray-200' : 'bg-gray-100'"></span>
                    </div>
                </template>

                <div class="relative h-full touch-pan-y" @pointerdown="track($event)" @pointermove="track($event)" @pointerleave="hover = null">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                        <defs>
                            <linearGradient id="{{ $titleId }}-fill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2A57B4" stop-opacity="0.22" />
                                <stop offset="100%" stop-color="#2A57B4" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path :d="area" fill="url(#{{ $titleId }}-fill)" />
                        <path :d="path" fill="none" stroke="#2A57B4" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        <line x-show="point" :x1="hover === null ? 0 : x(hover)" :x2="hover === null ? 0 : x(hover)" y1="0" y2="100"
                              stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke" />
                    </svg>
                    <span x-show="point" x-cloak
                          class="pointer-events-none absolute h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-[#2A57B4] shadow"
                          :style="point ? `left: ${x(hover)}%; top: ${y(point.v)}%` : ''"></span>
                    <div x-show="point" x-cloak
                         class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 -translate-y-[calc(100%+0.5rem)] whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs text-white shadow-lg"
                         :style="point ? `left: ${Math.min(85, Math.max(15, x(hover)))}%` : ''">
                        <span class="text-gray-300" x-text="point?.d"></span>
                        <span class="ml-1 font-semibold tabular-nums" x-text="point?.v"></span>
                        <span x-text="current.label?.toLowerCase()"></span>
                    </div>
                </div>
            </div>
            <div class="mt-2 flex justify-between pl-8 text-xs text-gray-400" aria-hidden="true">
                <template x-for="(l, k) in xLabels" :key="k"><span x-text="l"></span></template>
            </div>
        </div>
    </div>

    {{-- Metric strip: the selected one is underlined in brand blue --}}
    <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 rounded-xl border border-gray-200/70 p-4 cq-md:grid-cols-4" role="tablist" aria-label="Metric">
        <template x-for="(m, i) in metrics" :key="m.key">
            <button type="button" role="tab" :aria-selected="selected === i" @click="select(i)"
                    class="group min-w-0 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2">
                <span class="block text-lg font-bold tabular-nums text-gray-900" x-text="m.total.toLocaleString()"></span>
                <span class="block truncate text-xs" :class="selected === i ? 'font-semibold text-[#2A57B4]' : 'text-gray-500 group-hover:text-gray-800'" x-text="m.label"></span>
                <span class="mt-2 block h-1 rounded-full transition-colors" :class="selected === i ? 'bg-[#2A57B4]' : 'bg-gray-100 group-hover:bg-gray-200'"></span>
            </button>
        </template>
    </div>

    <table class="sr-only">
        <caption x-text="current.label + ' per day'"></caption>
        <thead><tr><th scope="col">Day</th><th scope="col">Count</th></tr></thead>
        <tbody>
            <template x-for="p in series" :key="p.d"><tr><td x-text="p.d"></td><td x-text="p.v"></td></tr></template>
        </tbody>
    </table>
</section>
