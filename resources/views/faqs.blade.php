@extends('layouts.public')

@section('title', 'FAQs · DocuMate')

@php
    $faqs = config('faqs');
    $look = [
        'General' => ['bx-info-circle', '#dcecfa', '#1e4090'],
        'Accounts & Access' => ['bx-user-circle', '#e6e0f5', '#4c2f9e'],
        'Documents & Transactions' => ['bx-file', '#d9f3e1', '#065f46'],
        'Form Processing' => ['bx-edit', '#fef3c7', '#78350f'],
        'Security & Privacy' => ['bx-lock-alt', '#fde0ec', '#9f1239'],
        'Campus Services' => ['bx-buildings', '#fdece3', '#9a3412'],
    ];
    $total = collect($faqs)->flatten(1)->count();

    // Answers may hold [links](url), **bold**, blank-line paragraphs and "1." steps
    $render = function (string $text): string {
        $html = e($text);
        $html = preg_replace('/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^)\s]+)\)/', '<a href="$2" class="font-medium text-[#2a57b4] underline decoration-[#2a57b4]/30 underline-offset-2 hover:decoration-[#2a57b4]"' . ' target="_blank" rel="noopener">$1</a>', $html);
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-[#0d253d]">$1</strong>', $html);

        return collect(preg_split('/\n\s*\n/', $html))->map(function ($block) {
            $lines = array_filter(array_map('trim', explode("\n", $block)));
            if ($lines && collect($lines)->every(fn ($l) => preg_match('/^\d+\.\s/', $l))) {
                return '<ol class="list-decimal space-y-1 pl-5">' . collect($lines)->map(fn ($l) => '<li>' . preg_replace('/^\d+\.\s/', '', $l) . '</li>')->implode('') . '</ol>';
            }

            return '<p>' . implode('<br>', $lines) . '</p>';
        })->implode('');
    };
@endphp

@section('content')
    {{-- Header --}}
    <section class="dm-ledger border-b border-[#e7e5e1] pb-12 pt-28 sm:pb-16 sm:pt-36">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm text-[#66707f] hover:text-[#2a57b4]"><i class="bx bx-left-arrow-alt text-lg"></i> Home</a>
                <h1 class="mt-4 text-[2.6rem] font-semibold leading-[1] tracking-[-0.035em] text-[#0d253d] sm:text-[3.75rem]">Questions, answered</h1>
                <p class="mt-5 max-w-xl text-base leading-relaxed text-[#4a5568] sm:text-[17px]">{{ $total }} answers about DocuMate accounts, forms, appointments, privacy and campus services.</p>
            </div>
            <div>
                <label for="faq-search" class="sr-only">Search the FAQs</label>
                <div class="relative">
                    <i class="bx bx-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-[#9a978f]" aria-hidden="true"></i>
                    <input id="faq-search" type="search" placeholder="Search, e.g. forgot password, appointment, ID" autocomplete="off"
                           class="h-14 w-full rounded-xl border-[#d4d1cb] bg-white pl-12 pr-4 text-base text-[#0d253d] shadow-[0_12px_28px_-20px_rgba(13,37,61,0.45)] placeholder:text-[#9a978f] focus:border-[#2a57b4] focus:ring-4 focus:ring-[#2a57b4]/12">
                </div>
                <p id="faq-count" class="mt-2.5 h-5 text-sm text-[#66707f]" aria-live="polite"></p>
            </div>
        </div>
    </section>

    <div class="bg-white">
        {{-- Phones: category strip --}}
        <nav class="sticky top-[60px] z-30 border-b border-[#e7e5e1] bg-white/95 backdrop-blur lg:hidden" aria-label="FAQ categories">
            <div class="flex gap-2 overflow-x-auto px-5 py-3 [scrollbar-width:none] sm:px-8 [&::-webkit-scrollbar]:hidden">
                @foreach ($faqs as $category => $items)
                    <a href="#{{ Str::slug($category) }}" data-faq-link="{{ Str::slug($category) }}"
                       class="faq-chip shrink-0 whitespace-nowrap rounded-lg border border-[#e7e5e1] px-3 py-1.5 text-sm text-[#4a5568]">{{ $category }}</a>
                @endforeach
            </div>
        </nav>

        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[15.5rem_minmax(0,1fr)] lg:gap-16">
            {{-- Desktop: categories --}}
            <aside class="hidden lg:block">
                <nav class="sticky top-28" aria-label="FAQ categories">
                    <ul class="space-y-0.5">
                        @foreach ($faqs as $category => $items)
                            @php [$icon, $bg, $fg] = $look[$category] ?? ['bx-help-circle', '#ecebe7', '#273951']; @endphp
                            <li>
                                <a href="#{{ Str::slug($category) }}" data-faq-link="{{ Str::slug($category) }}"
                                   class="faq-side flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-[#4a5568] transition hover:bg-[#f5f4f2]">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md" style="background: {{ $bg }}; color: {{ $fg }}"><i class="bx {{ $icon }} text-base"></i></span>
                                    <span class="flex-1">{{ $category }}</span>
                                    <span class="text-xs tabular-nums text-[#9a978f]" data-faq-catcount="{{ Str::slug($category) }}">{{ count($items) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 rounded-xl border border-[#e7e5e1] bg-[#fafaf9] p-4">
                        <p class="flex items-center gap-2 text-sm font-semibold text-[#0d253d]"><i class="bx bx-bot text-lg text-[#2a57b4]"></i> Ask the assistant</p>
                        <p class="mt-1.5 text-[13px] leading-relaxed text-[#4a5568]">Signed in, the robot on every page can answer from these FAQs and your own records.</p>
                        <a href="{{ route('login') }}" class="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-[#2a57b4] hover:underline underline-offset-4">Sign in <i class="bx bx-right-arrow-alt"></i></a>
                    </div>
                </nav>
            </aside>

            {{-- Questions --}}
            <div class="min-w-0">
                @foreach ($faqs as $category => $items)
                    @php [$icon, $bg, $fg] = $look[$category] ?? ['bx-help-circle', '#ecebe7', '#273951']; @endphp
                    <section id="{{ Str::slug($category) }}" data-faq-group class="scroll-mt-36 pb-12 last:pb-0 lg:scroll-mt-28">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg" style="background: {{ $bg }}; color: {{ $fg }}"><i class="bx {{ $icon }} text-xl"></i></span>
                            <h2 class="text-xl font-semibold tracking-[-0.015em] text-[#0d253d] sm:text-2xl">{{ $category }}</h2>
                        </div>
                        <div class="mt-4 divide-y divide-[#ecebe7] border-y border-[#ecebe7]">
                            @foreach ($items as $item)
                                <details class="faq-item group" data-faq-item>
                                    <summary class="flex items-start justify-between gap-4 py-4 text-left">
                                        <span class="pt-0.5 text-[15.5px] font-medium leading-snug text-[#0d253d] group-hover:text-[#1e4090]">{{ $item['q'] }}</span>
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-[#66707f] transition group-open:bg-[#e2e9f7] group-open:text-[#2a57b4]">
                                            <i class="bx bx-plus text-xl transition-transform duration-300 group-open:rotate-45" aria-hidden="true"></i>
                                        </span>
                                    </summary>
                                    <div class="faq-answer max-w-3xl space-y-3 pb-5 pr-10 text-[15px] leading-relaxed text-[#4a5568]">{!! $render($item['a']) !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div id="faq-empty" class="hidden rounded-xl border border-dashed border-[#d4d1cb] px-6 py-14 text-center">
                    <i class="bx bx-search-alt text-4xl text-[#d4d1cb]" aria-hidden="true"></i>
                    <p class="mt-3 font-medium text-[#0d253d]">Nothing matches that yet</p>
                    <p class="mt-1 text-sm text-[#66707f]">Try fewer or different words, or ask the office below.</p>
                </div>

                {{-- Still stuck --}}
                <div class="mt-14 grid gap-px overflow-hidden rounded-xl border border-[#e7e5e1] bg-[#e7e5e1] sm:grid-cols-3">
                    @foreach ([
                        ['bx-envelope', 'Email the university', 'info@lnu.edu.ph', 'mailto:info@lnu.edu.ph'],
                        ['bx-map', 'Visit the VPSD office', 'Administration Building, 2nd Floor · Mon to Fri, 8 AM to 5 PM', null],
                        ['bx-bot', 'Ask the assistant', 'Sign in and tap the robot in the corner', route('login')],
                    ] as [$icon, $title, $text, $href])
                        <div class="bg-white p-5">
                            <i class="bx {{ $icon }} text-2xl text-[#2a57b4]" aria-hidden="true"></i>
                            <p class="mt-3 text-[15px] font-semibold text-[#0d253d]">{{ $title }}</p>
                            @if ($href)
                                <a href="{{ $href }}" class="mt-1 block text-sm text-[#2a57b4] hover:underline underline-offset-4">{{ $text }}</a>
                            @else
                                <p class="mt-1 text-sm leading-relaxed text-[#4a5568]">{{ $text }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('head')
<style>
    .faq-side.is-active { background: #e2e9f7; color: #1e4090; font-weight: 500; }
    .faq-chip.is-active { background: #e2e9f7; border-color: #c9d6f0; color: #1e4090; }
    .faq-answer mark { background: #fef3c7; color: inherit; border-radius: 3px; padding: 0 2px; }
    details.faq-item summary { list-style: none; cursor: pointer; }
    details.faq-item summary::-webkit-details-marker { display: none; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('faq-search');
    var groups = document.querySelectorAll('[data-faq-group]');
    var empty = document.getElementById('faq-empty');
    var count = document.getElementById('faq-count');
    var links = document.querySelectorAll('[data-faq-link]');

    // Open the question named in the address bar, e.g. /faqs#accounts-access
    if (location.hash) {
        var target = document.querySelector(location.hash);
        if (target && target.matches('[data-faq-group]')) { var first = target.querySelector('details'); if (first) first.open = true; }
    }

    input.addEventListener('input', function () {
        var words = input.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
        var hits = 0;
        groups.forEach(function (group) {
            var inGroup = 0;
            group.querySelectorAll('[data-faq-item]').forEach(function (item) {
                var text = item.textContent.toLowerCase();
                var match = !words.length || words.every(function (w) { return text.indexOf(w) !== -1; });
                item.classList.toggle('hidden', !match);
                item.open = words.length > 0 && match;
                if (match) inGroup++;
            });
            group.classList.toggle('hidden', inGroup === 0);
            var c = document.querySelector('[data-faq-catcount="' + group.id + '"]');
            if (c) c.textContent = inGroup;
            hits += inGroup;
        });
        empty.classList.toggle('hidden', hits > 0);
        count.textContent = words.length ? (hits ? hits + (hits === 1 ? ' answer' : ' answers') + ' found' : '') : '';
    });

    // Highlight the category being read
    if ('IntersectionObserver' in window) {
        var spy = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                links.forEach(function (l) { l.classList.toggle('is-active', l.getAttribute('data-faq-link') === e.target.id); });
            });
        }, { rootMargin: '-30% 0px -60% 0px' });
        groups.forEach(function (g) { spy.observe(g); });
    }
});
</script>
@endpush
