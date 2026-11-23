{{-- A form as a sheet of paper: the LNU letterhead, the form's title and code, then whatever is filled in on it.
     The fill-in pages for students, issuers and signers all use it, so what people type on looks like what prints.
     <x-doc.sheet title="Admission Slip Form" code="LNU-VPSD-001"> … </x-doc.sheet>
     Inside, use the classes in App\Support\DocSheet (blank lines, captions, printed values). --}}
@props(['title', 'code' => null])
<article {{ $attributes->merge(['class' => 'relative mx-auto w-full min-w-0 rounded-[3px] bg-white shadow-[0_1px_2px_rgba(13,37,61,0.06),0_12px_32px_-16px_rgba(13,37,61,0.28)] ring-1 ring-slate-200/80']) }}>
    <div class="px-4 pb-7 pt-5 sm:px-10 sm:pb-10 sm:pt-8 lg:px-14">
        <header class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <img src="{{ asset('images/lnu-seal.png') }}" alt="" class="h-11 w-11 shrink-0 sm:h-14 sm:w-14" aria-hidden="true">
                <div class="min-w-0 font-serif text-[11px] leading-tight text-slate-800 sm:text-[13px]">
                    <p>LEYTE NORMAL UNIVERSITY</p>
                    <p class="text-slate-600">Tacloban City</p>
                    <p class="font-semibold">OFFICE OF THE VICE PRESIDENT FOR STUDENT DEVELOPMENT</p>
                </div>
            </div>
            @if ($code)
                <span class="hidden shrink-0 font-mono text-[10px] text-slate-400 sm:block">{{ $code }}</span>
            @endif
        </header>
        <h2 class="mt-6 text-center text-[15px] font-bold uppercase tracking-[0.04em] text-slate-900 sm:mt-8 sm:text-lg">{{ $title }}</h2>
        <div class="mt-6 sm:mt-8">{{ $slot }}</div>
    </div>
</article>
