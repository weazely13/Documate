{{-- Stat cards that pick a metric, a line chart of it per day, and the period picker.
     @include('livewire.partials.insights.trend', ['title' => 'Office activity', 'metrics' => [...DashboardInsights::metric()], 'range' => $range])
     The host component needs a public int $range (bound to the picker); pass 'picker' => false to leave it out.
     'note' adds a line under the title. --}}
@php $titleId = 'trend-' . \Illuminate\Support\Str::slug($title); @endphp
<section class="rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="{{ $titleId }}"
         wire:key="trend-{{ md5(json_encode($metrics)) }}" x-data="insightTrend(@js($metrics))">

    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 id="{{ $titleId }}" class="text-lg font-bold tracking-tight text-gray-900">{{ $title }}</h2>
            @isset($note)
                <p class="mt-0.5 text-sm text-gray-500">{{ $note }}</p>
            @endisset
        </div>
        @if ($picker ?? true)
        <label>
            <span class="sr-only">Period</span>
            <select wire:model.live="range"
                    class="rounded-full border-0 bg-transparent py-1 pl-3 pr-8 text-sm font-medium text-gray-600 hover:bg-gray-100 focus:ring-2 focus:ring-[#2A57B4]">
                @foreach (\App\Support\DashboardInsights::RANGES as $days)
                    <option value="{{ $days }}">{{ $days }} days</option>
                @endforeach
            </select>
        </label>
        @endif
    </div>

    {{-- Stat cards: the selected one drives the chart --}}
    <div class="-mx-5 mt-4 flex snap-x gap-3 overflow-x-auto px-5 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="Metric">
        <template x-for="(m, i) in metrics" :key="m.key">
            <button type="button" role="tab" :aria-selected="selected === i" @click="select(i)"
                    class="min-w-[9.5rem] flex-1 snap-start rounded-2xl border-2 px-4 py-3 text-left transition"
                    :class="selected === i ? 'border-gray-900 bg-white' : 'border-transparent bg-gray-100 hover:bg-gray-200/70'">
                <span class="block truncate text-sm text-gray-500" x-text="m.label"></span>
                <span class="mt-1 block text-2xl font-bold tabular-nums text-gray-900" x-text="m.total.toLocaleString()"></span>
                <span class="mt-0.5 block truncate text-xs font-medium" :class="deltaClass(m)" x-text="deltaText(m)"></span>
            </button>
        </template>
    </div>

    {{-- Line chart of the selected metric --}}
    <div class="relative mt-8 h-44 pl-9">
        <template x-for="(t, k) in ticks" :key="k">
            <div class="pointer-events-none absolute inset-x-0 flex items-center" :style="`top: ${k * 50}%`" aria-hidden="true">
                <span class="w-8 -translate-y-1/2 pr-2 text-right text-[11px] tabular-nums text-gray-400" x-text="t"></span>
                <span class="h-px flex-1 -translate-y-1/2 bg-gray-100"></span>
            </div>
        </template>

        <div class="relative h-full touch-pan-y" @pointermove="track($event)" @pointerleave="hover = null">
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
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
    <div class="mt-2 flex justify-between pl-9 text-xs text-gray-400" aria-hidden="true">
        <template x-for="(l, k) in xLabels" :key="k"><span x-text="l"></span></template>
    </div>

    {{-- The same series as a table for screen readers --}}
    <table class="sr-only">
        <caption x-text="current.label + ' per day'"></caption>
        <thead><tr><th scope="col">Day</th><th scope="col">Count</th></tr></thead>
        <tbody>
            <template x-for="p in series" :key="p.d"><tr><td x-text="p.d"></td><td x-text="p.v"></td></tr></template>
        </tbody>
    </table>
</section>
