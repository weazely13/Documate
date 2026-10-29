{{-- Bar chart for the Reports page: the same days, weeks, months or years as the line chart, one
     group of bars per step and a bar per series. With a comparison, a paler bar stands beside each.
     The checkboxes hide and show series; hovering a group shows its numbers.
     Expects $trend (see reports-trend; 'axes' => 'dual' gives each of two series its own scale),
     $comparison (null or ['label', 'dates' => [], 'series' => [key => values]]), $empty. --}}
@php
    $buckets = $trend['buckets'];
    $series = $trend['series'];
    $cmp = $comparison;
    $n = max(1, count($buckets));
    $all = collect($series)->flatMap(fn ($s) => $s[3]);
    if ($cmp) {
        $all = $all->concat(collect($cmp['series'])->flatten());
    }
    $dual = ($trend['axes'] ?? null) === 'dual' && count($series) === 2;
    $niceTop = function ($peak) {
        $peak = max(1, (int) $peak);
        $raw = $peak / 4;
        $mag = 10 ** floor(log10(max(1, $raw)));
        $step = max(1, (int) (collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $raw && floor($s) == $s) ?? 10 * $mag));

        return $step * (int) ceil($peak / $step);
    };
    $top = $niceTop($all->filter()->max());
    // Two metrics compared: each on its own scale, the first on the left axis, the second on the right.
    $tops = collect($series)->map(fn ($s) => $dual ? $niceTop(max($s[3] ?: [0])) : $top)->all();
    $w = 600;
    $h = 200;
    $slot = $w / $n;                                  // width of one day / week / month
    $perGroup = count($series) * ($cmp ? 2 : 1);
    $barW = $slot * 0.72 / max(1, $perGroup);
    $every = max(1, (int) ceil($n / 7));
    $hasData = $all->filter()->isNotEmpty();
@endphp

<div x-data="{ show: @js(collect($series)->map(fn () => true)->values()), hover: null }">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        @foreach ($series as $si => [$key, $label, $colour, $values])
            <label class="inline-flex cursor-pointer select-none items-center gap-2 text-xs font-medium text-slate-700">
                <input type="checkbox" x-model="show[{{ $si }}]" class="h-4 w-4 rounded border-slate-300 focus:ring-[#2A57B4]" style="color: {{ $colour }}">
                {{ $label }}
                <span class="tabular-nums text-slate-400">{{ number_format(array_sum($values)) }}@if ($cmp) <span class="text-slate-300">vs</span> {{ number_format(array_sum(array_filter($cmp['series'][$key] ?? []))) }}@endif</span>
            </label>
        @endforeach
        @if ($cmp)
            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500"><span class="h-2.5 w-2.5 rounded-sm bg-slate-400/40"></span>Paler bars: {{ $cmp['label'] }}</span>
        @endif
    </div>

    @if (! $hasData)
        <div class="flex h-56 flex-col items-center justify-center text-center">
            <i class="bx bx-bar-chart-alt-2 text-4xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm text-slate-500">{{ $empty }}</p>
        </div>
    @else
        <div class="mt-4 pl-8 {{ $dual ? 'pr-8' : '' }}">
            <div class="relative h-56" @mouseleave="hover = null">
                @foreach ([0, 0.25, 0.5, 0.75, 1] as $f)
                    <div class="pointer-events-none absolute inset-x-0 flex items-center" style="bottom: {{ $f * 100 }}%" aria-hidden="true">
                        <span class="absolute -left-8 w-6 translate-y-1/2 text-right text-[11px] tabular-nums" style="color: {{ $dual ? $series[0][2] : '#94a3b8' }}">{{ (int) round($tops[0] * $f) }}</span>
                        @if ($dual)<span class="absolute -right-8 w-6 translate-y-1/2 text-[11px] tabular-nums" style="color: {{ $series[1][2] }}">{{ (int) round($tops[1] * $f) }}</span>@endif
                        <span class="h-px w-full translate-y-1/2 {{ $f === 0 ? 'bg-slate-300' : 'bg-slate-100' }}"></span>
                    </div>
                @endforeach

                <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="absolute inset-0 h-full w-full" aria-hidden="true">
                    @foreach ($buckets as $i => $b)
                        <rect x="{{ $i * $slot }}" y="0" width="{{ $slot }}" height="{{ $h }}" fill="#f1f5f9" x-show="hover === {{ $i }}" />
                    @endforeach
                    @foreach ($series as $si => [$key, $label, $colour, $values])
                        <g x-show="show[{{ $si }}]">
                            @foreach ($buckets as $i => $b)
                                @php
                                    $x0 = $i * $slot + $slot * 0.14 + $si * ($cmp ? 2 : 1) * $barW;
                                    $v = $values[$i] ?? 0;
                                    $c = $cmp['series'][$key][$i] ?? null;
                                @endphp
                                @if ($v > 0)<rect x="{{ round($x0, 2) }}" y="{{ round($h - $v / $tops[$si] * $h, 2) }}" width="{{ round($barW * 0.92, 2) }}" height="{{ round($v / $tops[$si] * $h, 2) }}" fill="{{ $colour }}" rx="1" />@endif
                                @if ($cmp && $c)<rect x="{{ round($x0 + $barW, 2) }}" y="{{ round($h - $c / $tops[$si] * $h, 2) }}" width="{{ round($barW * 0.92, 2) }}" height="{{ round($c / $tops[$si] * $h, 2) }}" fill="{{ $colour }}" fill-opacity="0.35" rx="1" />@endif
                            @endforeach
                        </g>
                    @endforeach
                </svg>

                <div class="absolute inset-0 flex">
                    @foreach ($buckets as $i => $b)
                        <div class="h-full flex-1" @mouseenter="hover = {{ $i }}"></div>
                    @endforeach
                </div>
                @foreach ($buckets as $i => $b)
                    @php $left = ($i + 0.5) / $n * 100; @endphp
                    <div x-show="hover === {{ $i }}" x-cloak class="pointer-events-none absolute top-0 z-10 w-56 rounded-lg bg-white p-2.5 text-xs shadow-lg ring-1 ring-slate-200" style="{{ $left > 60 ? 'right: ' . (100 - $left) . '%; margin-right: .75rem' : 'left: ' . $left . '%; margin-left: .75rem' }}">
                        <p class="font-semibold text-slate-800">{{ $b['dates'] }}</p>
                        @if ($cmp && ($cmp['dates'][$i] ?? null))<p class="text-[11px] text-slate-500">vs {{ $cmp['dates'][$i] }}</p>@endif
                        @foreach ($series as $si => [$key, $label, $colour, $values])
                            <p x-show="show[{{ $si }}]" class="mt-1 flex items-center justify-between gap-3 text-slate-600">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: {{ $colour }}"></span>{{ $label }}</span>
                                <span class="tabular-nums"><b class="text-slate-900">{{ $values[$i] }}</b>@if ($cmp) <span class="text-slate-400">vs {{ $cmp['series'][$key][$i] ?? '—' }}</span>@endif</span>
                            </p>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="relative mt-2 h-4">
                @php $shown = 0; @endphp
                @foreach ($buckets as $i => $b)
                    @if ($i % $every === 0 && $n - 1 - $i >= $every / 2 || $i === $n - 1)
                        <span class="absolute whitespace-nowrap text-[11px] text-slate-500 {{ $shown++ % 2 === 1 && $i !== $n - 1 ? 'max-sm:hidden' : '' }} {{ $i === 0 && $n > 1 ? '' : ($i === $n - 1 && $n > 1 ? '-translate-x-full' : '-translate-x-1/2') }}" style="left: {{ $n > 1 && $i === 0 ? 0 : ($n > 1 && $i === $n - 1 ? 100 : ($i + 0.5) / $n * 100) }}%">{{ $b['label'] }}</span>
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
