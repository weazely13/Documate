{{-- Labelled horizontal bars, optionally split into series and switched by chips.
     @include('livewire.partials.insights.breakdown', [
         'title' => 'Documents by type',
         'note' => null,                                    // optional line under the title
         'series' => [['key' => 'completed', 'label' => 'Completed', 'color' => '#2A57B4'], ...],
         'groups' => ['All' => [['label' => 'Leave form', 'completed' => 3, 'open' => 1], ...]],   // one group = no chips
         'percent' => false,                                // true: rows carry 'pct' and bars fill to it
         'empty' => 'Nothing yet.',
         'format' => fn ($row, $total) => ...,             // optional: the value shown at the end of each bar
     ])
     Colours were checked with the dataviz validator: #2A57B4 and #eb6834 pass every check on white. --}}
@php
    $percent = $percent ?? false;
    $note = $note ?? null;
    $groupNames = array_keys($groups);
@endphp

<section class="rounded-2xl border border-gray-200/70 bg-white p-5" x-data="{ group: @js($groupNames[0] ?? null) }">
    <h2 class="text-lg font-bold tracking-tight text-gray-900">{{ $title }}</h2>
    @if ($note)
        <p class="mt-0.5 text-sm text-gray-500">{{ $note }}</p>
    @endif

    @if (count($groupNames) > 1)
        <div class="mt-4 flex gap-2" role="tablist" aria-label="{{ $title }}">
            @foreach ($groupNames as $name)
                <button type="button" role="tab" @click="group = @js($name)" :aria-selected="group === @js($name)"
                        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                        :class="group === @js($name) ? 'border-transparent bg-gray-100 text-gray-900' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                    {{ $name }}
                </button>
            @endforeach
        </div>
    @endif

    @if (count($series) > 1)
        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
            @foreach ($series as $s)
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}" aria-hidden="true"></span>{{ $s['label'] }}</span>
            @endforeach
        </div>
    @endif

    @foreach ($groups as $name => $rows)
        @php
            $totals = collect($rows)->map(fn ($r) => collect($series)->sum(fn ($s) => $r[$s['key']]));
            $max = max(1, $totals->max() ?? 0);
        @endphp
        <div x-show="group === @js($name)" @if (! $loop->first) x-cloak @endif class="mt-4 space-y-4">
            @forelse ($rows as $i => $row)
                @php
                    $total = $totals[$i];
                    $width = $percent ? min(100, $row['pct']) : $total / $max * 100;
                @endphp
                <div class="group relative">
                    <p class="truncate text-sm text-gray-800" title="{{ $row['label'] }}">{{ $row['label'] }}</p>
                    <div class="mt-1.5 flex items-center gap-3">
                        <div class="h-2.5 flex-1 rounded-full bg-gray-100">
                            <div class="flex h-full gap-[2px]" style="width: {{ $width }}%">
                                @foreach ($series as $s)
                                    @if ($row[$s['key']] > 0)
                                        <span class="h-full min-w-[4px] rounded-full" style="flex: {{ $row[$s['key']] }} 1 0%; background: {{ $s['color'] }}"></span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <span class="w-14 shrink-0 text-right text-sm font-semibold tabular-nums text-gray-900">
                            {{ isset($format) ? $format($row, $total) : ($percent ? rtrim(rtrim(number_format($row['pct'], 1), '0'), '.') . '%' : number_format($total)) }}
                        </span>
                    </div>

                    {{-- Hover: the exact split --}}
                    <div class="pointer-events-none absolute bottom-full left-0 z-10 mb-1 hidden whitespace-nowrap rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block">
                        @foreach ($series as $s)
                            <span class="mr-2 inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }} <strong class="tabular-nums">{{ $row[$s['key']] }}</strong></span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">{{ $empty ?? 'Nothing yet.' }}</p>
            @endforelse
        </div>
    @endforeach
</section>
