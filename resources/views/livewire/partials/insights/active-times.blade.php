{{-- Weekday chips over submissions per three-hour window, and the busiest windows.
     @include('livewire.partials.insights.active-times', ['title' => 'Busiest times', 'times' => DashboardInsights::activeTimes(...), 'summary' => 'When submissions peak']) --}}
<section class="rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="active-times-title"
         wire:key="active-{{ md5(json_encode($times)) }}" x-data="insightActiveTimes(@js($times))">
    <h2 id="active-times-title" class="text-lg font-bold tracking-tight text-gray-900">{{ $title }}</h2>
    <p class="mt-0.5 text-sm text-gray-500">{{ $note ?? 'Based on' }} {{ $times['timezone'] }}</p>

    @if ($times['total'])
        <div class="mt-4 grid grid-cols-7 gap-1.5" role="tablist" aria-label="Day of the week">
            <template x-for="(d, i) in data.days" :key="d.short">
                <button type="button" role="tab" @click="day = i" :aria-selected="day === i" :aria-label="d.name"
                        class="rounded-full border py-2 text-sm font-semibold transition"
                        :class="day === i ? 'border-transparent bg-gray-100 text-gray-900' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        x-text="d.short"></button>
            </template>
        </div>

        <div class="mt-5 flex h-44 items-end gap-1.5 sm:gap-2" @mouseleave="hover = null">
            <template x-for="(v, i) in bars" :key="i">
                <div class="relative flex h-full flex-1 items-end" @mouseenter="hover = i" @click="hover = i">
                    <div class="w-full rounded-t-[4px] bg-[#2A57B4] transition-all duration-300"
                         :class="hover !== null && hover !== i ? 'opacity-60' : ''"
                         :style="`height: ${height(v)}%`"></div>
                    <div x-show="hover === i" x-cloak
                         class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2 py-1 text-xs text-white shadow-lg">
                        <span class="font-semibold tabular-nums" x-text="v"></span> <span x-text="v === 1 ? 'submission' : 'submissions'"></span>
                    </div>
                </div>
            </template>
        </div>
        <div class="mt-2 flex gap-1.5 text-center text-xs text-gray-400 sm:gap-2" aria-hidden="true">
            <template x-for="l in data.labels" :key="l"><span class="flex-1" x-text="l"></span></template>
        </div>

        <table class="sr-only">
            <caption x-text="'Submissions on ' + data.days[day].name + ', by three-hour window'"></caption>
            <tbody>
                <template x-for="(v, i) in bars" :key="i"><tr><th scope="row" x-text="data.labels[i]"></th><td x-text="v"></td></tr></template>
            </tbody>
        </table>

        @if ($times['top'])
            <div class="mt-6 border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $summary }}</h3>
                <dl class="mt-3 grid gap-3 sm:grid-cols-3">
                    @foreach ($times['top'] as $peak)
                        <div>
                            <dt class="text-sm font-semibold text-gray-800">{{ $peak['day'] }}</dt>
                            <dd class="text-sm tabular-nums text-gray-500">{{ $peak['window'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    @else
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">No submissions in this period yet.</p>
    @endif
</section>
