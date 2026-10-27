{{-- Submissions per weekday as rounded columns; the busiest day is picked out in brand blue.
     @include('livewire.partials.insights.weekdays', ['title' => 'Busiest days', 'note' => '...', 'times' => DashboardInsights::activeTimes(...)]) --}}
@php
    $totals = collect($times['days'])->map(fn ($d) => array_sum($d['bars']));
    $max = max(1, $totals->max());
    $peak = $times['total'] ? $totals->search($totals->max()) : null;
    $window = $times['top'][0] ?? null;
@endphp

<section class="rounded-2xl border border-gray-200/70 bg-white p-5">
    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
    @isset($note)
        <p class="mt-0.5 text-sm text-gray-500">{{ $note }}</p>
    @endisset

    @if ($times['total'])
        <div class="mt-5 flex h-40 items-end gap-2" aria-hidden="true">
            @foreach ($times['days'] as $i => $day)
                <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                    <span class="mb-1.5 text-xs font-semibold tabular-nums text-gray-900 {{ $i === $peak ? '' : 'invisible group-hover:visible' }}">{{ number_format($totals[$i]) }}</span>
                    <div class="w-full max-w-[2.25rem] rounded-full transition-colors {{ $i === $peak ? 'bg-[#2A57B4]' : 'bg-gray-100 group-hover:bg-gray-200' }}"
                         style="height: {{ max(8, $totals[$i] / $max * 82) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex gap-2 text-center text-xs" aria-hidden="true">
            @foreach ($times['days'] as $i => $day)
                <span class="flex-1 {{ $i === $peak ? 'font-semibold text-[#2A57B4]' : 'text-gray-500' }}">{{ $day['short'] }}</span>
            @endforeach
        </div>

        <table class="sr-only">
            <caption>{{ $title }}</caption>
            <tbody>
                @foreach ($times['days'] as $i => $day)
                    <tr><th scope="row">{{ $day['name'] }}</th><td>{{ $totals[$i] }}</td></tr>
                @endforeach
            </tbody>
        </table>

        @if ($window)
            <p class="mt-4 border-t border-gray-100 pt-3 text-sm text-gray-600">
                Busiest window: <span class="font-semibold text-gray-900">{{ $window['day'] }}, {{ $window['window'] }}</span>
            </p>
        @endif
    @else
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">No submissions in this period yet.</p>
    @endif
</section>
