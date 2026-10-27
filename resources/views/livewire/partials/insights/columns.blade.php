{{-- Vertical columns over time, one series: a value per period on a shared zero baseline.
     @include('livewire.partials.insights.columns', [
         'title' => 'Entries per month',
         'note' => null,                                   // optional line under the title
         'columns' => ['Jan 2024' => 106, 'Feb 2024' => 98], // label ("M Y") => value, oldest first
         'unit' => ['entry', 'entries'],                   // tooltip wording, singular and plural
         'empty' => 'Nothing yet.',
     ])
     Labels thin out to about twelve; every column keeps its hover value and the screen-reader table. --}}
@php
    $note = $note ?? null;
    $unit = $unit ?? ['entry', 'entries'];
    $count = count($columns);
    $max = max(1, $count ? max($columns) : 0);

    // Round the top of the scale up to 1, 2, 2.5 or 5 of its power of ten.
    $pow = 10 ** floor(log10($max));
    $top = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $pow)->first(fn ($t) => $t >= $max);
    $every = max(1, (int) ceil($count / 12));
    // Past six labels, narrow cards show every other one (never one that starts a year).
    $thin = $count / $every > 6;
    $titleId = 'columns-' . \Illuminate\Support\Str::slug($title);
@endphp

<section class="cq rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="{{ $titleId }}">
    <h2 id="{{ $titleId }}" class="text-lg font-bold tracking-tight text-gray-900">{{ $title }}</h2>
    @if ($note)
        <p class="mt-0.5 text-sm text-gray-500">{{ $note }}</p>
    @endif

    @if ($count)
        <div class="relative mt-8 h-52 pl-10" aria-hidden="true">
            @foreach ([$top, $top / 2, 0] as $k => $tick)
                <div class="pointer-events-none absolute inset-x-0 flex h-0 items-center" style="top: {{ $k * 50 }}%">
                    <span class="w-9 -translate-y-1/2 pr-2 text-right text-[11px] tabular-nums text-gray-400">{{ number_format($tick, fmod($tick, 1) ? 1 : 0) }}</span>
                    <span class="h-px flex-1 -translate-y-1/2 {{ $tick ? 'bg-gray-100' : 'bg-gray-200' }}"></span>
                </div>
            @endforeach

            <div class="relative flex h-full items-end gap-[2px]">
                @foreach ($columns as $label => $value)
                    <div class="group relative flex h-full min-w-0 flex-1 items-end justify-center">
                        <div class="w-full max-w-[3rem] rounded-t-[4px] bg-[#2A57B4] transition-colors group-hover:bg-[#1E4090]"
                             style="height: {{ $value ? max(0.75, $value / $top * 100) : 0 }}%"></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block">
                            <span class="text-gray-300">{{ $label }}</span>
                            <span class="ml-1 font-semibold tabular-nums">{{ number_format($value) }}</span>
                            {{ $value === 1 ? $unit[0] : $unit[1] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Month under its column; the year only where it starts or changes --}}
        <div class="mt-2 flex gap-[2px] pl-10 text-[11px] leading-tight text-gray-500" aria-hidden="true">
            @php $lastYear = null; @endphp
            @foreach (array_keys($columns) as $i => $label)
                @php [$month, $year] = array_pad(explode(' ', $label, 2), 2, ''); @endphp
                <span class="min-w-0 flex-1 overflow-visible whitespace-nowrap text-center">
                    @if ($i % $every === 0)
                        @php $startsYear = $year !== $lastYear; $lastYear = $year; @endphp
                        <span class="{{ $thin && ! $startsYear && ($i / $every) % 2 ? 'invisible cq-md:visible' : '' }}">{{ $month }}</span>
                        @if ($startsYear)
                            <span class="block font-semibold text-gray-700">{{ $year }}</span>
                        @endif
                    @endif
                </span>
            @endforeach
        </div>

        <table class="sr-only">
            <caption>{{ $title }}</caption>
            <thead><tr><th scope="col">Period</th><th scope="col">{{ ucfirst($unit[1]) }}</th></tr></thead>
            <tbody>
                @foreach ($columns as $label => $value)
                    <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">{{ $empty ?? 'Nothing yet.' }}</p>
    @endif
</section>
