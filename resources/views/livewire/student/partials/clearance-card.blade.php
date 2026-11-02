{{-- A coloured clearance card. Everything about the semester is inside it; the status is the headline.
     Landscape at every size; text scales with the card (container units).
     $t theme, $e entry [status, semester, academic_year, remarks, tagged_by, tagged_at, is_current], $hero, $holder, $number, $means, $updated --}}
@php
    $hero = $hero ?? false;
    $sem = trim((string) ($e['semester'] ?? ''));
    $semLabel = $sem === '' ? 'This semester' : (str_contains(strtolower($sem), 'semester') || str_contains(strtolower($sem), 'summer') ? $sem : $sem . ' Semester');
    $status = $e['status'] === 'Not Tagged' ? 'Not tagged yet' : $e['status'];
    $date = ! empty($e['tagged_at']) ? $e['tagged_at']->format('M j, Y') : null;
@endphp
<div class="relative w-full overflow-hidden rounded-xl text-white shadow-[0_20px_36px_-24px_rgba(13,37,61,0.55)] [container-type:inline-size]
            {{ $hero ? '[aspect-ratio:1.3/1] sm:[aspect-ratio:2.3/1]' : '[aspect-ratio:1.42/1]' }}"
     style="background: linear-gradient(135deg, {{ $t['c1'] }} 0%, {{ $t['c2'] }} 60%, {{ $t['c3'] }} 100%);">
    <div class="pointer-events-none absolute -right-[10%] -top-[35%] h-[85%] w-[50%] rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
    <i class="bx {{ $t['icon'] }} pointer-events-none absolute -bottom-[8%] -right-[3%] text-[38cqw] leading-none text-white/[0.08]" aria-hidden="true"></i>

    <div class="relative flex h-full flex-col {{ $hero ? 'p-[5cqw] sm:p-[3.6cqw]' : 'p-[5.5cqw] sm:p-[6cqw]' }}">
        {{-- Semester --}}
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate font-semibold leading-tight {{ $hero ? 'text-[4.3cqw] sm:text-[2.6cqw]' : 'text-[4.6cqw] sm:text-[5.6cqw]' }}">{{ $semLabel }}</p>
                <p class="text-white/75 {{ $hero ? 'text-[3.2cqw] sm:text-[1.8cqw]' : 'text-[3.4cqw] sm:text-[4cqw]' }}">{{ $e['academic_year'] ? 'A.Y. ' . $e['academic_year'] : 'No semester tagged yet' }}</p>
            </div>
            @if ($e['is_current'] ?? false)
                <span class="shrink-0 rounded-md bg-white/20 font-medium {{ $hero ? 'px-[1.8cqw] py-[0.7cqw] text-[2.9cqw] sm:px-[1.1cqw] sm:py-[0.45cqw] sm:text-[1.6cqw]' : 'px-[1.9cqw] py-[0.8cqw] text-[3cqw] sm:px-[2.2cqw] sm:py-[0.9cqw] sm:text-[3.6cqw]' }}">This semester</span>
            @endif
        </div>

        {{-- Status: the headline --}}
        <div class="{{ $hero ? 'mt-[3cqw] sm:mt-[2cqw]' : 'mt-[3cqw] sm:mt-[3.4cqw]' }}">
            <p class="flex items-center font-semibold leading-none tracking-[-0.02em] {{ $hero ? 'gap-[1.8cqw] text-[8.2cqw] sm:gap-[1.2cqw] sm:text-[5.4cqw]' : 'gap-[1.8cqw] text-[7cqw] sm:gap-[2.2cqw] sm:text-[8.4cqw]' }}">
                <i class="bx {{ $t['icon'] }} {{ $hero ? 'text-[7.4cqw] sm:text-[4.8cqw]' : 'text-[6.6cqw] sm:text-[8cqw]' }}"></i>{{ $status }}
            </p>
            @if ($hero && ! empty($means))
                <p class="mt-[2cqw] text-white/85 sm:mt-[1cqw] {{ 'text-[3.2cqw] sm:text-[1.75cqw]' }} line-clamp-2">{{ $means }}</p>
            @endif
        </div>

        {{-- Remarks --}}
        <p class="mt-auto shrink-0 rounded-lg bg-black/15 leading-snug text-white/95 {{ $hero ? 'px-[2.6cqw] py-[1.8cqw] text-[3cqw] sm:px-[1.6cqw] sm:py-[1.1cqw] sm:text-[1.7cqw]' : 'px-[2.8cqw] py-[1.9cqw] text-[3.2cqw] sm:px-[3.2cqw] sm:py-[2.2cqw] sm:text-[3.9cqw]' }}" title="{{ $e['remarks'] }}">
            <span class="line-clamp-2">{{ $e['remarks'] ?: 'No remarks.' }}</span>
        </p>

        {{-- Who and when --}}
        <div class="flex items-end justify-between gap-3 {{ $hero ? 'mt-[2.6cqw] text-[2.9cqw] sm:mt-[1.6cqw] sm:text-[1.65cqw]' : 'mt-[2.6cqw] text-[3.1cqw] sm:mt-[3cqw] sm:text-[3.7cqw]' }}">
            @if ($hero)
                <div class="min-w-0">
                    <p class="text-white/70">Student</p>
                    <p class="truncate font-medium">{{ $holder }}{{ $number ? ' · ' . $number : '' }}</p>
                </div>
            @endif
            <div class="min-w-0 {{ $hero ? 'text-center' : '' }}">
                <p class="text-white/70">Tagged by</p>
                <p class="truncate font-medium">{{ $e['tagged_by'] ?: 'Not yet' }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-white/70">Updated</p>
                <p class="font-medium tabular-nums">{{ $date ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>
