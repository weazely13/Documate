{{-- A share out of 100 as a half-circle of ticks, with a legend and a link to the details.
     @include('livewire.partials.insights.gauge', [
         'title' => 'Clearance this semester', 'pct' => 67, 'caption' => '8 of 12 students cleared',
         'legend' => [['Pending', 3, 'bg-amber-400'], ...],   // optional
         'footnote' => '12 of 12 students verified',          // optional line under the legend
         'url' => route(...), 'link' => 'Show details',        // optional
     ]) --}}
@php
    $ticks = 36;
    $filled = (int) round(max(0, min(100, $pct)) / 100 * $ticks);
@endphp

<section class="rounded-2xl border border-gray-200/70 bg-white p-5">
    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>

    <div class="relative mx-auto mt-3 max-w-[15rem]">
        <svg viewBox="0 0 200 112" class="w-full" role="img" aria-label="{{ $pct }}%: {{ $caption }}">
            @for ($i = 0; $i < $ticks; $i++)
                @php
                    $a = deg2rad(180 - $i * 180 / ($ticks - 1));
                    [$c, $s] = [cos($a), sin($a)];
                @endphp
                <line x1="{{ round(100 + 74 * $c, 2) }}" y1="{{ round(104 - 74 * $s, 2) }}"
                      x2="{{ round(100 + 94 * $c, 2) }}" y2="{{ round(104 - 94 * $s, 2) }}"
                      stroke="{{ $i < $filled ? '#10b981' : '#e5e7eb' }}" stroke-width="5" stroke-linecap="round" />
            @endfor
        </svg>
        <div class="absolute inset-x-0 bottom-1 text-center">
            <p class="text-3xl font-bold leading-none tracking-tight tabular-nums text-gray-900">{{ $pct }}%</p>
            <p class="mt-1 text-xs text-gray-500">{{ $caption }}</p>
        </div>
    </div>

    @if (! empty($legend))
        <dl class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-xs">
            @foreach ($legend as [$label, $count, $color])
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $color }}" aria-hidden="true"></span>
                    <dt class="text-gray-600">{{ $label }}</dt>
                    <dd class="font-semibold tabular-nums text-gray-900">{{ $count }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if (! empty($footnote))
        <p class="mt-3 text-center text-xs text-gray-500">{{ $footnote }}</p>
    @endif

    @if (! empty($url))
        <div class="mt-4 text-center">
            <a href="{{ $url }}" wire:navigate
               class="inline-flex rounded-full border border-gray-200 px-3.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                {{ $link ?? 'Show details' }}
            </a>
        </div>
    @endif
</section>
