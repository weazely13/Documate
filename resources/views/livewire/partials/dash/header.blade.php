{{-- Page header for the dashboards: a landscape whose sky tells the time, with the greeting, a live clock,
     a line of facts, and the period picker.
     @include('livewire.partials.dash.header', ['name' => 'Maria', 'facts' => ['2026-2027 | First Semester']])
     'action' => ['label' => 'New transaction', 'url' => route(...), 'icon' => 'bx-plus'] adds a primary button beside the picker.
     The host component needs a public int $range (bound to the picker); 'picker' => false leaves the picker out.

     The scene follows the viewer's own clock (re-checked every 30s):
       sky   night (19-5) · dawn (5-7) · morning (7-12) · afternoon (12-17) · dusk (17-19)
       sun   rises at 5:30 and sets at 19:00 on an arc across the open sky at the right (the text holds the left);
             the moon takes the night on the same arc. Phones flatten the arc into the strip above the heading.
     Styling hangs off data-sky / data-ink / data-day, so the server's first guess and the browser's clock never fight over classes. --}}
@php
    // Same rules as the Alpine component below, so the first paint already matches (app timezone is the office's).
    $minutes = (int) now()->format('G') * 60 + (int) now()->format('i');
    $h = intdiv($minutes, 60);
    $sky = match (true) {
        $h < 5 || $h >= 19 => 'night',
        $h < 7 => 'dawn',
        $h < 12 => 'morning',
        $h < 17 => 'afternoon',
        default => 'dusk',
    };
    $ink = in_array($sky, ['dusk', 'night'], true) ? 'light' : 'dark';
    $greeting = $h >= 5 && $h < 12 ? 'Good morning' : ($h >= 12 && $h < 18 ? 'Good afternoon' : 'Good evening');

    $isDay = $minutes >= 330 && $minutes < 1140;
    $p = $isDay ? ($minutes - 330) / 810 : (($minutes >= 1140 ? $minutes - 1140 : $minutes + 300) / 630);
    // Wider screens: --bx across, --by height. Phones (the heading fills the width): --bxm, --bym in the strip beside the date.
    $arc = sin($p * M_PI);
    $bodyStyle = sprintf('--bx: %.2f%%; --by: %.2f%%; --bxm: %.2f%%; --bym: %.2f%%;', 52 + $p * 44, 78 - $arc * 66, 66 + $p * 28, 20 - $arc * 10);
@endphp

<header data-sky="{{ $sky }}" :data-sky="sky" data-ink="{{ $ink }}" :data-ink="ink" data-day="{{ $isDay ? 'yes' : 'no' }}" :data-day="body.day ? 'yes' : 'no'"
        class="group/greet relative isolate overflow-hidden rounded-2xl border border-slate-200/70 px-5 pb-8 pt-6 sm:px-7 sm:pb-10 sm:pt-7 data-[ink=light]:border-transparent"
        x-data="{
            now: new Date(),
            get minutes() { return this.now.getHours() * 60 + this.now.getMinutes(); },
            get sky() {
                const h = this.now.getHours();
                if (h < 5 || h >= 19) return 'night';
                if (h < 7) return 'dawn';
                if (h < 12) return 'morning';
                if (h < 17) return 'afternoon';
                return 'dusk';
            },
            get ink() { return ['dusk', 'night'].includes(this.sky) ? 'light' : 'dark'; },
            get greeting() {
                const h = this.now.getHours();
                return h >= 5 && h < 12 ? 'Good morning' : (h >= 12 && h < 18 ? 'Good afternoon' : 'Good evening');
            },
            get clock() { return this.now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },
            // Sun 5:30 to 19:00, moon the rest; both ride the same arc across the right half of the sky.
            get body() {
                const m = this.minutes;
                const day = m >= 330 && m < 1140;
                const p = day ? (m - 330) / 810 : ((m >= 1140 ? m - 1140 : m + 300) / 630);
                const arc = Math.sin(p * Math.PI);
                return { day, style: `--bx: ${(52 + p * 44).toFixed(2)}%; --by: ${(78 - arc * 66).toFixed(2)}%; --bxm: ${(66 + p * 28).toFixed(2)}%; --bym: ${(20 - arc * 10).toFixed(2)}%;` };
            },
        }"
        x-init="setInterval(() => { now = new Date() }, 30000)">

    {{-- SCENE --}}
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        {{-- Skies, cross-fading --}}
        <div class="absolute inset-0 bg-gradient-to-b from-[#0b1a42] via-[#14306b] to-[#1E4090] opacity-0 transition-opacity duration-1000 group-data-[sky=night]/greet:opacity-100"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-[#c9d8f6] via-[#e9e6f2] to-[#fcd9c4] opacity-0 transition-opacity duration-1000 group-data-[sky=dawn]/greet:opacity-100"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-[#cfe0fb] via-[#e6efff] to-[#f6f9ff] opacity-0 transition-opacity duration-1000 group-data-[sky=morning]/greet:opacity-100"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-[#9cc2f5] via-[#c6dbfa] to-[#eaf2ff] opacity-0 transition-opacity duration-1000 group-data-[sky=afternoon]/greet:opacity-100"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-[#1E4090] via-[#8a4f7d] to-[#eb6834] opacity-0 transition-opacity duration-1000 group-data-[sky=dusk]/greet:opacity-100"></div>

        {{-- Stars (night; faint at dusk) --}}
        <div class="absolute inset-0 opacity-0 transition-opacity duration-1000 group-data-[sky=dusk]/greet:opacity-40 group-data-[sky=night]/greet:opacity-100">
            @foreach ([[8, 14, 2], [17, 34, 1.5], [26, 10, 1.5], [37, 26, 2], [45, 8, 1.5], [53, 30, 1.5], [61, 16, 2], [70, 6, 1.5], [78, 28, 1.5], [86, 12, 2], [93, 32, 1.5], [33, 44, 1], [66, 42, 1], [90, 48, 1]] as [$x, $y, $r])
                <span class="absolute rounded-full bg-white" style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $r * 2 }}px; height: {{ $r * 2 }}px;"></span>
            @endforeach
        </div>

        {{-- Sun or moon on its arc --}}
        <div class="absolute left-[var(--bxm)] top-[var(--bym)] -translate-x-1/2 -translate-y-1/2 sm:left-[var(--bx)] sm:top-[var(--by)]" style="{{ $bodyStyle }}" :style="body.style">
            {{-- sun: yellow by day, orange when low at dawn and dusk --}}
            <span class="hidden h-20 w-20 items-center justify-center rounded-full sm:h-24 sm:w-24 bg-[radial-gradient(circle,rgba(253,224,71,0.45)_0%,rgba(253,224,71,0)_68%)] group-data-[day=yes]/greet:flex group-data-[sky=dawn]/greet:bg-[radial-gradient(circle,rgba(235,104,52,0.45)_0%,rgba(235,104,52,0)_68%)] group-data-[sky=dusk]/greet:bg-[radial-gradient(circle,rgba(235,104,52,0.5)_0%,rgba(235,104,52,0)_68%)]">
                <span class="h-8 w-8 rounded-full sm:h-10 sm:w-10 bg-[#fcd34d] group-data-[sky=dawn]/greet:bg-[#f59e6b] group-data-[sky=dusk]/greet:bg-[#eb6834]"></span>
            </span>
            {{-- moon: a crescent drawn by an inset shadow, so no patch of sky colour has to match the gradient --}}
            <span class="hidden h-20 w-20 items-center justify-center rounded-full sm:h-24 sm:w-24 bg-[radial-gradient(circle,rgba(255,255,255,0.16)_0%,rgba(255,255,255,0)_66%)] group-data-[day=no]/greet:flex">
                <span class="h-7 w-7 -rotate-[25deg] rounded-full shadow-[inset_-8px_-2px_0_0_#f1f5ff] sm:h-9 sm:w-9 sm:shadow-[inset_-10px_-2px_0_0_#f1f5ff]"></span>
            </span>
        </div>

        {{-- Clouds (day) --}}
        <div class="absolute inset-0 opacity-0 transition-opacity duration-1000 group-data-[sky=morning]/greet:opacity-100 group-data-[sky=afternoon]/greet:opacity-100 group-data-[sky=dawn]/greet:opacity-60">
            <span class="absolute left-[58%] top-[16%] h-5 w-24 rounded-full bg-white/80"></span>
            <span class="absolute left-[61%] top-[9%] h-7 w-14 rounded-full bg-white/80"></span>
            <span class="absolute left-[80%] top-[30%] h-4 w-20 rounded-full bg-white/60"></span>
            <span class="absolute left-[83%] top-[24%] h-6 w-10 rounded-full bg-white/60"></span>
        </div>

        {{-- Mountains: three ranges, brand blues by day, deep navy silhouettes after sunset --}}
        <svg class="absolute inset-x-0 bottom-0 h-28 w-full sm:h-32" viewBox="0 60 1200 180" preserveAspectRatio="none">
            <path class="fill-[#2A57B4]/[0.14] transition-colors duration-1000 group-data-[sky=dawn]/greet:fill-[#7c6aa8]/25 group-data-[sky=dusk]/greet:fill-[#14306b]/60 group-data-[sky=night]/greet:fill-[#24499A]/45"
                  d="M0 150 L90 122 L170 140 L262 96 L360 134 L470 88 L560 124 L660 72 L760 118 L852 82 L960 128 L1062 92 L1200 118 V240 H0 Z" />
            <path class="fill-[#2A57B4]/[0.24] transition-colors duration-1000 group-data-[sky=dawn]/greet:fill-[#5c5a9c]/35 group-data-[sky=dusk]/greet:fill-[#14306b]/80 group-data-[sky=night]/greet:fill-[#14306b]/80"
                  d="M0 186 L120 162 L212 176 L330 132 L442 170 L560 142 L690 104 L800 150 L902 120 L1012 160 L1120 136 L1200 150 V240 H0 Z" />
            <path class="fill-[#2A57B4]/[0.36] transition-colors duration-1000 group-data-[sky=dawn]/greet:fill-[#3f4f97]/45 group-data-[sky=dusk]/greet:fill-[#0b1a42] group-data-[sky=night]/greet:fill-[#0b1a42]"
                  d="M0 214 L150 198 L282 210 L420 180 L560 204 L702 170 L860 200 L980 176 L1100 196 L1200 186 V240 H0 Z" />
        </svg>
    </div>

    {{-- CONTENT --}}
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-5">
        <div class="min-w-0">
            {{-- Time line: sun or moon, the time, the date (its own line on phones) --}}
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-medium text-slate-700 group-data-[ink=light]/greet:text-blue-50">
                <span class="inline-flex items-center gap-1.5 font-semibold text-[#1E4090] group-data-[sky=dawn]/greet:text-[#b4471a] group-data-[ink=light]/greet:text-white">
                    <span class="hidden group-data-[day=yes]/greet:inline-flex" aria-hidden="true"><i class='bx bxs-sun text-lg text-[#eb6834]'></i></span>
                    <span class="hidden group-data-[day=no]/greet:inline-flex" aria-hidden="true"><i class='bx bxs-moon text-lg text-[#fcd34d]'></i></span>
                    <time class="tabular-nums" x-text="clock">{{ now()->format('g:i A') }}</time>
                </span>
                <span class="hidden text-slate-400 sm:inline group-data-[ink=light]/greet:text-white/40" aria-hidden="true">·</span>
                <span class="w-full sm:w-auto">{{ now()->format('l, F j') }}</span>
            </p>

            {{-- The greeting, with the name in the brand's script face --}}
            <h1 class="mt-2.5 text-[2rem] font-bold leading-[1.1] tracking-tight text-slate-900 sm:text-[2.5rem] lg:text-[2.75rem] group-data-[ink=light]/greet:text-white">
                <span x-text="greeting">{{ $greeting }}</span>,
                <span class="whitespace-nowrap font-['Gveret_Levin',cursive] text-[1.15em] font-semibold leading-none tracking-normal text-[#2A57B4] group-data-[ink=light]/greet:text-white">{{ $name }}</span>
            </h1>

            @if (! empty($facts))
                <p class="mt-3 text-sm font-medium text-slate-700 group-data-[ink=light]/greet:text-blue-50">
                    @foreach ($facts as $i => $fact)
                        @if ($i > 0)<span aria-hidden="true" class="mx-1 text-slate-400 group-data-[ink=light]/greet:text-white/40">·</span>@endif{{ $fact }}
                    @endforeach
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @isset($action)
                <a href="{{ $action['url'] }}" wire:navigate
                   class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2 [@media(pointer:coarse)]:min-h-[44px]
                       group-data-[ink=light]/greet:bg-white group-data-[ink=light]/greet:text-[#1E4090] group-data-[ink=light]/greet:hover:bg-blue-50 group-data-[ink=light]/greet:focus-visible:ring-white group-data-[ink=light]/greet:focus-visible:ring-offset-[#1E4090]">
                    <i class='bx {{ $action['icon'] ?? 'bx-plus' }} text-base' aria-hidden="true"></i>{{ $action['label'] }}
                </a>
            @endisset
            @if ($picker ?? true)
            <label class="relative">
                <span class="sr-only">Period for the charts</span>
                <i class='bx bx-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-gray-500' aria-hidden="true"></i>
                <select wire:model.live="range"
                        class="rounded-xl border-gray-200 bg-white py-2 pl-9 pr-9 text-sm font-medium text-gray-700 shadow-sm focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-[44px] group-data-[ink=light]/greet:border-white/20">
                    @foreach (\App\Support\DashboardInsights::RANGES as $days)
                        <option value="{{ $days }}" @selected($days == ($range ?? null))>Last {{ $days }} days</option>
                    @endforeach
                </select>
            </label>
            @endif
        </div>
    </div>
</header>
