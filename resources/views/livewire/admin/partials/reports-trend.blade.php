{{-- Trend chart for the Reports page: one soft area per series over days, weeks or months.
     The checkboxes above it hide and show series; hovering a column shows its numbers.
     Expects $trend = ['unit' => day|week|month|year, 'buckets' => [[label, dates, …]], 'series' => [[key, label, colour, values[]]]], $empty,
     and optionally $comparison = ['label', 'dates' => [], 'series' => [key => values]], drawn as dashed lines.
     With $trend['axes'] = 'dual' (two metrics compared), each series gets its own scale: the first on the left, the second on the right. --}}
@php
    $buckets = $trend['buckets'];
    $series = $trend['series'];
    $n = count($buckets);
    $cmp = $comparison ?? null;
    $dual = ($trend['axes'] ?? null) === 'dual' && count($series) === 2;
    // Round gridline steps (1, 2, 5, 10, 20, 25, 50 …) so the axis reads easily.
    $niceTop = function ($peak) {
        $peak = max(1, (int) $peak);
        $raw = $peak / 4;
        $mag = 10 ** floor(log10(max(1, $raw)));
        $step = max(1, (int) (collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $raw && floor($s) == $s) ?? 10 * $mag));

        return $step * (int) ceil($peak / $step);
    };
    $top = $niceTop(collect($series)->flatMap(fn ($s) => $s[3])->concat($cmp ? collect($cmp['series'])->flatten() : [])->filter()->max());
    $tops = collect($series)->mapWithKeys(fn ($s) => [$s[0] => $dual ? $niceTop(max($s[3] ?: [0])) : $top])->all();
    $w = 600;
    $h = 200;
    $x = fn ($i) => $n <= 1 ? $w / 2 : round($i * $w / ($n - 1), 2);
    $y = fn ($v, $key = null) => round($h - $v / ($tops[$key] ?? $top) * $h, 2);
    $every = max(1, (int) ceil($n / 7));
    $hasData = collect($series)->contains(fn ($s) => array_sum($s[3]) > 0);
@endphp

<div x-data="{ show: @js(collect($series)->mapWithKeys(fn ($s) => [$s[0] => true])), hover: null }">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        @foreach ($series as [$key, $label, $colour, $values])
            <label class="inline-flex cursor-pointer select-none items-center gap-2 text-xs font-medium text-slate-700">
                <input type="checkbox" x-model="show.{{ $key }}" class="h-4 w-4 rounded border-slate-300 focus:ring-[#2A57B4]" style="color: {{ $colour }}">
                {{ $label }}
                <span class="tabular-nums text-slate-400">{{ number_format(array_sum($values)) }}@if ($cmp) <span class="text-slate-300">vs</span> {{ number_format(array_sum(array_filter($cmp['series'][$key] ?? []))) }}@endif</span>
            </label>
        @endforeach
        @if ($cmp)
            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500"><span class="w-4 border-t-2 border-dashed border-slate-400"></span>{{ $cmp['label'] }}</span>
        @endif
    </div>

    @if (! $hasData)
        <div class="flex h-56 flex-col items-center justify-center text-center">
            <i class="bx bx-line-chart text-4xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm text-slate-500">{{ $empty }}</p>
        </div>
    @else
        <div class="mt-4 pl-8 {{ $dual ? 'pr-8' : '' }}">
            <div class="relative h-56" @mouseleave="hover = null">
                @foreach ([0, 0.25, 0.5, 0.75, 1] as $f)
                    <div class="pointer-events-none absolute inset-x-0 flex items-center" style="bottom: {{ $f * 100 }}%" aria-hidden="true">
                        <span class="absolute -left-8 w-6 translate-y-1/2 text-right text-[11px] tabular-nums" style="color: {{ $dual ? $series[0][2] : '#9a978f' }}">{{ (int) round(($dual ? $tops[$series[0][0]] : $top) * $f) }}</span>
                        @if ($dual)<span class="absolute -right-8 w-6 translate-y-1/2 text-[11px] tabular-nums" style="color: {{ $series[1][2] }}">{{ (int) round($tops[$series[1][0]] * $f) }}</span>@endif
                        <span class="h-px w-full translate-y-1/2 {{ $f === 0 ? 'bg-slate-300' : 'bg-slate-100' }}"></span>
                    </div>
                @endforeach

                <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                    @foreach ($series as [$key, $label, $colour, $values])
                        @php
                            $line = collect($values)->map(fn ($v, $i) => ($i ? 'L' : 'M') . $x($i) . ' ' . $y($v, $key))->implode(' ');
                            $area = $n > 1 ? $line . ' L' . $x($n - 1) . ' ' . $h . ' L0 ' . $h . ' Z' : '';
                        @endphp
                        <g x-show="show.{{ $key }}">
                            @if ($area && ! $dual)<path d="{{ $area }}" fill="{{ $colour }}" fill-opacity="0.12" />@endif
                            <path d="{{ $line }}" fill="none" stroke="{{ $colour }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            @if ($cmp && isset($cmp['series'][$key]))
                                @php
                                    $pen = 'M';
                                    $dash = collect($cmp['series'][$key])->map(function ($v, $i) use (&$pen, $x, $y) {
                                        if ($v === null) { $pen = 'M'; return ''; }
                                        $seg = $pen . $x($i) . ' ' . $y($v); $pen = 'L';
                                        return $seg;
                                    })->filter()->implode(' ');
                                @endphp
                                <path d="{{ $dash }}" fill="none" stroke="{{ $colour }}" stroke-opacity="0.55" stroke-width="2" stroke-dasharray="5 4" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            @endif
                        </g>
                    @endforeach
                </svg>

                {{-- Hover: a crosshair, a dot per visible series, and the numbers --}}
                <div class="absolute inset-0 flex">
                    @foreach ($buckets as $i => $b)
                        <div class="h-full flex-1" @mouseenter="hover = {{ $i }}"></div>
                    @endforeach
                </div>
                @foreach ($buckets as $i => $b)
                    @php $left = $n <= 1 ? 50 : $i / ($n - 1) * 100; @endphp
                    <div x-show="hover === {{ $i }}" x-cloak class="pointer-events-none absolute inset-y-0" style="left: {{ $left }}%">
                        <span class="absolute inset-y-0 w-px -translate-x-1/2 bg-slate-300"></span>
                        @foreach ($series as [$key, $label, $colour, $values])
                            <span x-show="show.{{ $key }}" class="absolute h-2.5 w-2.5 -translate-x-1/2 translate-y-1/2 rounded-full ring-2 ring-white" style="bottom: {{ $values[$i] / ($tops[$key] ?? $top) * 100 }}%; background: {{ $colour }}"></span>
                        @endforeach
                        <div class="absolute top-0 z-10 w-52 rounded-lg bg-white p-2.5 text-xs shadow-lg ring-1 ring-slate-200 {{ $left > 60 ? 'right-3' : 'left-3' }}">
                            <p class="font-semibold text-slate-800">{{ $b['dates'] }}</p>
                            @if ($cmp && ($cmp['dates'][$i] ?? null))<p class="text-[11px] text-slate-500">vs {{ $cmp['dates'][$i] }}</p>@endif
                            @foreach ($series as [$key, $label, $colour, $values])
                                <p x-show="show.{{ $key }}" class="mt-1 flex items-center justify-between gap-3 text-slate-600">
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: {{ $colour }}"></span>{{ $label }}</span>
                                    <span class="tabular-nums"><b class="text-slate-900">{{ $values[$i] }}</b>@if ($cmp) <span class="text-slate-400">vs {{ $cmp['series'][$key][$i] ?? '—' }}</span>@endif</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="relative mt-2 h-4">
                @php $shown = 0; @endphp
                @foreach ($buckets as $i => $b)
                    @if (($i % $every === 0 && $n - 1 - $i >= $every / 2) || $i === $n - 1)
                        <span class="absolute whitespace-nowrap text-[11px] text-slate-500 {{ $shown++ % 2 === 1 && $i !== $n - 1 ? 'max-sm:hidden' : '' }} {{ $i === 0 ? '' : ($i === $n - 1 ? '-translate-x-full' : '-translate-x-1/2') }}" style="left: {{ $n <= 1 ? 50 : $i / ($n - 1) * 100 }}%">{{ $b['label'] }}</span>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="sr-only"><table>
            <thead><tr><th scope="col">{{ ucfirst($trend['unit']) }}</th>@foreach ($series as [$key, $label])<th scope="col">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>@foreach ($buckets as $i => $b)<tr><td>{{ $b['dates'] }}</td>@foreach ($series as $s)<td>{{ $s[3][$i] }}</td>@endforeach</tr>@endforeach</tbody>
        </table></div>
    @endif
</div>
