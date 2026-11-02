{{-- Full-screen frame for sign in, registration and password pages: the form on the left, a field of
     DocuMate-blue polygons on the right (drawn here, no images). Phones get a short polygon band on top. --}}
@props(['wide' => false, 'image' => null])
@php
    // A low-poly mesh: a jittered grid split into triangles, shaded from deep to bright DocuMate blue.
    $mesh = function (int $w, int $h, int $cols, int $rows, int $seed) {
        mt_srand($seed);
        $pts = [];
        for ($r = 0; $r <= $rows; $r++) {
            for ($c = 0; $c <= $cols; $c++) {
                $edgeX = $c === 0 || $c === $cols; $edgeY = $r === 0 || $r === $rows;
                $x = $c * $w / $cols + ($edgeX ? 0 : mt_rand(-28, 28) / 100 * $w / $cols);
                $y = $r * $h / $rows + ($edgeY ? 0 : mt_rand(-28, 28) / 100 * $h / $rows);
                $pts[$r][$c] = [round($x, 1), round($y, 1)];
            }
        }
        $shades = ['#16357a', '#1a3d88', '#1e4090', '#22479c', '#264fa8', '#2a57b4', '#3060c0', '#3768c8'];
        $tris = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                [$a, $b, $d, $e] = [$pts[$r][$c], $pts[$r][$c + 1], $pts[$r + 1][$c], $pts[$r + 1][$c + 1]];
                foreach ((($r + $c) % 2 ? [[$a, $b, $e], [$a, $e, $d]] : [[$a, $b, $d], [$b, $e, $d]]) as $t) {
                    $cy = ($t[0][1] + $t[1][1] + $t[2][1]) / 3; $cx = ($t[0][0] + $t[1][0] + $t[2][0]) / 3;
                    // brighter toward the top right, with a little noise
                    $k = (int) round((1 - $cy / $h) * 4 + ($cx / $w) * 3 + mt_rand(-10, 10) / 10);
                    $tris[] = [implode(' ', array_map(fn ($p) => $p[0] . ',' . $p[1], $t)), $shades[max(0, min(count($shades) - 1, $k))]];
                }
            }
        }

        return $tris;
    };
    $big = $mesh(800, 1000, 6, 8, 11);
    $band = $mesh(800, 160, 9, 2, 5);
@endphp

<div class="min-h-screen bg-white lg:grid {{ $wide ? 'lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]' : 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]' }}">

    {{-- Form side --}}
    <div class="relative flex min-h-screen flex-col">
        {{-- Phones: polygon band --}}
        <svg class="block h-24 w-full lg:hidden" viewBox="0 0 800 160" preserveAspectRatio="none" aria-hidden="true">
            @foreach ($band as [$p, $fill])<polygon points="{{ $p }}" fill="{{ $fill }}" stroke="{{ $fill }}" stroke-width="0.6"/>@endforeach
        </svg>

        <div class="flex flex-1 flex-col px-5 pb-6 pt-5 sm:px-10 lg:px-16 lg:pb-8 lg:pt-9 xl:px-24">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2" aria-label="DocuMate home">
                    <img src="{{ asset('images/DocumateLogo.png') }}" alt="" class="h-9 w-9 object-contain">
                    <span class="text-[1.7rem] leading-none text-[#2A57B4]" style="font-family: 'Gveret Levin', cursive;">DocuMate</span>
                </a>
                {{ $aside ?? '' }}
            </div>

            <div class="flex flex-1 flex-col justify-center py-8 sm:py-12">
                <div class="mx-auto w-full {{ $wide ? 'max-w-[44rem]' : 'max-w-[25rem]' }}">
                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-[#9a978f] lg:text-left">Leyte Normal University · Office of the Vice President for Student Development</p>
        </div>
    </div>

    {{-- Brand side: polygons --}}
    <aside class="relative hidden overflow-hidden bg-[#1e4090] lg:block" aria-label="About DocuMate"
           x-data="{ i: 0, slides: [
                ['Request forms online', 'Permits, slips and clearances from your phone. Your details are filled in for you.'],
                ['Follow every signature', 'See who has signed and who is next, without asking at the window.'],
                ['A copy anyone can check', 'Finished documents are sealed, with a QR code that proves they are genuine.'],
           ] }"
           x-init="setInterval(() => i = (i + 1) % slides.length, 5500)">
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            @foreach ($big as [$p, $fill])<polygon points="{{ $p }}" fill="{{ $fill }}" stroke="{{ $fill }}" stroke-width="0.8"/>@endforeach
            {{-- a few outlined shapes floating over the mesh --}}
            <g fill="none" stroke="#dcecfa" stroke-opacity=".35" stroke-width="1.5">
                <polygon class="dm-float" points="560,170 640,216 640,308 560,354 480,308 480,216"/>
                <polygon class="dm-float dm-float-2" points="150,640 240,800 60,800"/>
                <polygon class="dm-float dm-float-3" points="620,700 700,760 670,850 570,850 540,760"/>
            </g>
            <polygon points="560,170 640,216 640,308 560,354 480,308 480,216" fill="#ffffff" fill-opacity=".06"/>
        </svg>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0d253d]/55 via-transparent to-transparent" aria-hidden="true"></div>

        <div class="relative z-10 flex h-full flex-col justify-end p-12 xl:p-16">
            <div class="min-h-[9rem]">
                <template x-for="(s, k) in slides" :key="k">
                    <div x-show="i === k" x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        <p class="text-[2rem] font-semibold leading-tight tracking-[-0.025em] text-white xl:text-[2.4rem]" x-text="s[0]"></p>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-[#dbe5f7]" x-text="s[1]"></p>
                    </div>
                </template>
            </div>
            <div class="mt-6 flex gap-1.5" role="tablist" aria-label="Slides">
                <template x-for="(s, k) in slides" :key="'d' + k">
                    <button type="button" @click="i = k" :aria-label="'Show ' + s[0]" class="h-1.5 rounded-full transition-all"
                            :class="i === k ? 'w-7 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/70'"></button>
                </template>
            </div>
        </div>
    </aside>
</div>
