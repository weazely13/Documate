@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;
    $cellInput = 'w-full rounded-lg border border-slate-300 px-1.5 py-1.5 text-xs outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-blue-100';
@endphp

<div class="mt-5">
    <p class="text-sm font-medium text-slate-700">Schedule of vacant time</p>
    <p class="text-xs text-slate-400">
        Pick the start and end time of each vacant period. AM: 7:00 AM to 12:00 PM. PM: 1:00 PM to 8:00 PM. Hours are computed for you.
        Total must be {{ SA::WEEKLY_HOURS }} hours a week, with no more than {{ SA::MAX_DAILY_HOURS }} hours a day.
        Monday &amp; Thursday and Tuesday &amp; Friday each count for two days.
    </p>

    @if ($editable)
        <div wire:ignore class="mt-3 space-y-3"
             x-data="{
                v: {},
                rows: @js(collect(SA::DAYS)->map(fn ($d) => $d[1])),
                init() { this.v = { ...$wire.values }; },
                dur(a, b) {
                    if (!a || !b) return 0;
                    const [h1, m1] = a.split(':'), [h2, m2] = b.split(':');
                    const d = (h2 * 60 + +m2) - (h1 * 60 + +m1);
                    return d > 0 ? d / 60 : 0;
                },
                half(k, h) { return this.dur(this.v[k + '_' + h + '_from'], this.v[k + '_' + h + '_to']); },
                day(k) { return this.half(k, 'am') + this.half(k, 'pm'); },
                week() { return Object.entries(this.rows).reduce((s, [k, d]) => s + this.day(k) * d, 0); },
                fmt(n) { return n > 0 ? String(Math.round(n * 100) / 100) : ''; },
                warn: '',
                win: { am: ['07:00', '12:00'], pm: ['13:00', '20:00'] },
                pick(k, h, p) {
                    const key = k + '_' + h + '_' + p;
                    const val = (this.v[key] || '').slice(0, 5);
                    const [min, max] = this.win[h];
                    if (val && (val < min || val > max)) {
                        this.v[key] = '';
                        this.warn = h === 'am'
                            ? 'AM times must be between 7:00 AM and 12:00 PM.'
                            : 'PM times must be between 1:00 PM and 8:00 PM.';
                    } else {
                        this.warn = '';
                    }
                    $wire.$set('values.' + key, this.v[key] || null, false);
                },
             }">
            @foreach (SA::DAYS as $k => [$label, $days])
                <div wire:key="sched-{{ $k }}" class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                        <p class="text-xs text-slate-500">
                            Per day: <b class="text-slate-800" x-text="fmt(day('{{ $k }}')) || '0'"></b> hrs
                            @if ($days > 1) <span class="text-slate-400">(counts {{ $days }}x)</span> @endif
                        </p>
                    </div>
                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach (['am' => 'Morning · 7:00 AM to 12:00 PM', 'pm' => 'Afternoon · 1:00 PM to 8:00 PM'] as $h => $H)
                            @php [$wMin, $wMax] = SA::WINDOWS[$h]; @endphp
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $H }}</p>
                                <div class="mt-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <input type="time" x-model="v.{{ $k }}_{{ $h }}_from" @change="pick('{{ $k }}', '{{ $h }}', 'from')"
                                           min="{{ $wMin }}" max="{{ $wMax }}" class="w-full min-w-0 rounded-lg border border-slate-300 px-2 py-2.5 text-base outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-blue-100 sm:text-sm">
                                    <span class="text-xs text-slate-400">to</span>
                                    <input type="time" x-model="v.{{ $k }}_{{ $h }}_to" @change="pick('{{ $k }}', '{{ $h }}', 'to')"
                                           min="{{ $wMin }}" max="{{ $wMax }}" class="w-full min-w-0 rounded-lg border border-slate-300 px-2 py-2.5 text-base outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-blue-100 sm:text-sm">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3.5 py-3 text-sm font-semibold">
                <span class="text-slate-600">Total hours per week</span>
                <span :class="Math.abs(week() - {{ SA::WEEKLY_HOURS }}) < 0.001 ? 'text-emerald-600' : 'text-amber-600'">
                    <span x-text="fmt(week()) || '0'"></span> / {{ SA::WEEKLY_HOURS }}
                </span>
            </div>
            <p x-show="warn" x-cloak x-text="warn" class="text-xs font-medium text-rose-600"></p>
        </div>
    @else
        <div class="mt-2 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[560px] w-full border-collapse text-center text-xs">
                <thead class="bg-slate-50 text-[11px] font-semibold text-slate-600">
                    <tr>
                        <th class="border border-slate-200 px-2 py-2 text-left">Days</th>
                        <th class="border border-slate-200 px-2 py-2">AM</th>
                        <th class="border border-slate-200 px-2 py-2">Hours</th>
                        <th class="border border-slate-200 px-2 py-2">PM</th>
                        <th class="border border-slate-200 px-2 py-2">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (SA::DAYS as $k => [$label])
                        <tr>
                            <td class="border border-slate-200 px-2 py-2 text-left font-semibold text-slate-700">{{ $label }}</td>
                            <td class="border border-slate-200 px-2 py-2">{{ SA::range($values, $k, 'am') ?: '—' }}</td>
                            <td class="border border-slate-200 px-2 py-2">{{ SA::fmt(SA::halfHours($values, $k, 'am')) }}</td>
                            <td class="border border-slate-200 px-2 py-2">{{ SA::range($values, $k, 'pm') ?: '—' }}</td>
                            <td class="border border-slate-200 px-2 py-2">{{ SA::fmt(SA::halfHours($values, $k, 'pm')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-semibold">
                        <td colspan="4" class="border border-slate-200 px-2 py-2 text-right text-slate-600">Total hours per week</td>
                        <td class="border border-slate-200 px-2 py-2">{{ SA::fmt(SA::weeklyTotal($values)) ?: '0' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    @foreach ($errors->getMessages() as $key => $messages)
        @if (str_starts_with($key, 'values.') && (str_contains($key, '_from') || str_contains($key, '_to')))
            <p class="mt-1 text-xs font-medium text-rose-500">{{ $messages[0] }}</p>
        @endif
    @endforeach
</div>