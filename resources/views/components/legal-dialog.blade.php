{{-- Terms / Privacy reader, used on the public pages, sign in and registration.
     mode="vanilla": the public layout's script opens #modal-{name} and gates the agree button (data-agree-*).
     mode="alpine":  shown by $show; the checkbox binds $check; the agree button runs $agree. --}}
@props(['name', 'mode' => 'alpine', 'show' => null, 'check' => null, 'agree' => null])
@php
    $meta = [
        'terms' => ['Terms & Conditions', 'partials.terms-content', 'bx-file', '#dcecfa', '#1e4090', 'I have read and agree to the Terms and Conditions.'],
        'privacy' => ['Privacy Policy', 'partials.privacy-content', 'bx-shield-quarter', '#d9f3e1', '#065f46', 'I have read and agree to the Privacy Policy.'],
    ][$name];
    [$title, $view, $icon, $tint, $ink, $agreeText] = $meta;

    // Number the sections and build the contents list from the document's own headings
    $sections = [];
    $html = preg_replace_callback('/<h4>(.*?)<\/h4>/s', function ($m) use (&$sections, $name) {
        $id = 'legal-' . $name . '-' . count($sections);
        $sections[] = ['id' => $id, 'label' => trim(preg_replace('/^\d+\.\s*/', '', strip_tags($m[1])))];

        return '<h4 id="' . $id . '" class="scroll-mt-4">' . $m[1] . '</h4>';
    }, view($view)->render());
    $html = preg_replace('/<p class="legal-title">.*?<\/p>/s', '', $html, 1);
    $minutes = max(1, (int) round(str_word_count(strip_tags($html)) / 200));
    $vanilla = $mode === 'vanilla';
@endphp

<div data-legal
     @if ($vanilla) id="modal-{{ $name }}" class="fixed inset-0 z-[100] hidden"
     @else x-show="{{ $show }}" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-[100]" style="display:none;" @endif
     role="dialog" aria-modal="true" aria-labelledby="legal-title-{{ $name }}">
    <div class="absolute inset-0 bg-[#0d253d]/50 backdrop-blur-[2px]" @if ($vanilla) data-modal-close @else @click="activeModal = null" @endif></div>

    <div class="absolute inset-x-0 bottom-0 flex h-[92vh] flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:inset-auto sm:left-1/2 sm:top-1/2 sm:h-[min(88vh,50rem)] sm:w-[min(94vw,58rem)] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-xl">
        {{-- Header --}}
        <div class="flex items-start gap-3.5 px-5 pb-4 pt-5 sm:px-7 sm:pt-6">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" style="background: {{ $tint }}; color: {{ $ink }};"><i class="bx {{ $icon }} text-2xl"></i></span>
            <div class="min-w-0 flex-1">
                <h3 id="legal-title-{{ $name }}" class="text-xl font-semibold tracking-[-0.015em] text-[#0d253d] sm:text-2xl">{{ $title }}</h3>
                <p class="mt-0.5 text-[13px] text-[#66707f]">DocuMate · LNU VPSD · Updated November 2, 2026 · {{ $minutes }} min read</p>
            </div>
            <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-[#9a978f] hover:bg-[#f5f4f2] hover:text-[#273951]" aria-label="Close"
                    @if ($vanilla) data-modal-close @else @click="activeModal = null" @endif><i class="bx bx-x text-2xl"></i></button>
        </div>
        <div class="h-[3px] bg-[#ecebe7]"><div data-legal-progress class="h-full w-0 bg-[#2a57b4] transition-[width] duration-150"></div></div>

        {{-- Body --}}
        <div class="grid min-h-0 flex-1 md:grid-cols-[15rem_minmax(0,1fr)]">
            <nav class="hidden overflow-y-auto border-r border-[#ecebe7] bg-[#fafaf9] p-3 md:block" aria-label="Sections">
                <p class="px-2.5 pb-2 pt-1 text-[12px] font-medium text-[#66707f]">Contents</p>
                <ol class="space-y-0.5">
                    @foreach ($sections as $i => $s)
                        <li>
                            <button type="button" data-legal-jump="{{ $s['id'] }}"
                                    onclick="document.getElementById('{{ $s['id'] }}').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                    class="flex w-full gap-2 rounded-md px-2.5 py-1.5 text-left text-[13px] leading-snug text-[#4a5568] transition hover:bg-white hover:text-[#0d253d]">
                                <span class="w-4 shrink-0 tabular-nums text-[#9a978f]">{{ $i + 1 }}</span><span>{{ $s['label'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </nav>
            <div data-legal-scroll class="legal-content legal-doc min-h-0 overflow-y-auto px-5 py-5 text-[14.5px] leading-relaxed text-[#4a5568] sm:px-8 sm:py-6"
                 onscroll="var m = this.scrollHeight - this.clientHeight; this.closest('[data-legal]').querySelector('[data-legal-progress]').style.width = (m > 0 ? this.scrollTop / m * 100 : 100) + '%';">
                <div class="max-w-[42rem]">{!! $html !!}</div>
                <p class="mt-8 flex items-center gap-2 border-t border-[#ecebe7] pt-4 text-[13px] text-[#66707f]"><i class="bx bx-check-circle text-base text-[#059669]"></i> You've reached the end.</p>
            </div>
        </div>

        {{-- Agree --}}
        <div class="flex flex-col gap-3 border-t border-[#e7e5e1] bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <label class="flex cursor-pointer select-none items-start gap-2.5 text-sm text-[#273951]">
                <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#d4d1cb] text-[#2a57b4] focus:ring-[#2a57b4]/30"
                       @if ($vanilla) data-agree-checkbox="{{ $name }}" @else x-model="{{ $check }}" @endif>
                <span>{{ $agreeText }}</span>
            </label>
            <button type="button" class="h-11 shrink-0 rounded-lg bg-[#2a57b4] px-6 text-[15px] font-medium text-white transition hover:bg-[#24499a] disabled:cursor-not-allowed disabled:opacity-40"
                    @if ($vanilla) data-modal-close data-agree-button="{{ $name }}" disabled @else :disabled="!{{ $check }}" @click="{{ $agree }}" @endif>I agree</button>
        </div>
    </div>
</div>
