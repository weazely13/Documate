@extends('layouts.public')

@section('title', 'Verify a document · DocuMate')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="dm-ledger min-h-[calc(100vh-4rem)] pb-20 pt-28 sm:pt-36">
        <div class="mx-auto grid max-w-6xl gap-12 px-5 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-16">

            {{-- Ask --}}
            <div>
                <h1 class="text-[2.5rem] font-semibold leading-[1] tracking-[-0.035em] text-[#0d253d] sm:text-[3.4rem]">Is this document genuine?</h1>
                <p class="mt-5 max-w-lg text-base leading-relaxed text-[#4a5568] sm:text-[17px]">
                    Check a printed or PDF copy against the sealed record kept by the VPSD office. It takes the two codes at the bottom of the page, or the link from its QR code.
                </p>

                <form method="POST" action="{{ route('document.lookup.find') }}" class="mt-8 rounded-xl border border-[#e7e5e1] bg-white p-5 shadow-[0_24px_48px_-36px_rgba(13,37,61,0.45)] sm:p-6">
                    @csrf
                    @if ($errors->any())
                        <div class="mb-5 flex gap-2.5 rounded-lg bg-[#fde0ec] px-3.5 py-3 text-sm text-[#9f1239]" role="alert">
                            <i class="bx bx-error-circle mt-0.5 text-base"></i><span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <label for="reference" class="block text-[13px] font-medium text-[#273951]">Reference No. <span class="font-normal text-[#66707f]">or the link from the QR code</span></label>
                    <input id="reference" name="reference" value="{{ old('reference') }}" required maxlength="300" autocomplete="off" autofocus
                           placeholder="VPSD-2026-00143"
                           class="mt-1.5 h-12 w-full rounded-lg border-[#d4d1cb] px-3.5 font-mono text-[15px] tracking-wide text-[#0d253d] placeholder:text-[#9a978f] focus:border-[#2a57b4] focus:ring-2 focus:ring-[#2a57b4]/15">

                    <label for="fingerprint" class="mt-4 block text-[13px] font-medium text-[#273951]">Fingerprint</label>
                    <input id="fingerprint" name="fingerprint" value="{{ old('fingerprint') }}" maxlength="32" autocomplete="off"
                           placeholder="A1B2-C3D4-E5F6-0718"
                           class="mt-1.5 h-12 w-full rounded-lg border-[#d4d1cb] px-3.5 font-mono text-[15px] uppercase tracking-wide text-[#0d253d] placeholder:normal-case placeholder:text-[#9a978f] focus:border-[#2a57b4] focus:ring-2 focus:ring-[#2a57b4]/15">
                    <p class="mt-1.5 text-xs text-[#66707f]">Not needed if you pasted the link.</p>

                    <button type="submit" class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#0d253d] text-[15px] font-medium text-white transition hover:bg-[#273951]">
                        <i class="bx bx-shield-quarter text-lg"></i> Check document
                    </button>
                </form>

                <p class="mt-5 text-[13px] leading-relaxed text-[#66707f]">
                    Only online forms finished in DocuMate carry these codes. Paper forms are checked by the office that signed them.
                    Nothing about the student is shown unless the codes match.
                </p>
            </div>

            {{-- Where to find the codes --}}
            <div aria-hidden="true" class="lg:pt-6">
                <p class="text-sm font-medium text-[#273951]">Where to find them</p>
                <p class="mt-1 text-[13px] text-[#66707f]">At the bottom of every DocuMate PDF:</p>
                <div class="mt-4 rounded-[6px] bg-white px-5 pb-5 pt-0 shadow-[0_30px_60px_-36px_rgba(13,37,61,0.5),0_2px_6px_rgba(13,37,61,0.06)] sm:px-7">
                    <div class="flex"><span class="h-[3px] w-20 bg-[#2a57b4]"></span><span class="mt-px h-px flex-1 bg-[#cfd3da]"></span></div>
                    <div class="mt-4 flex items-start gap-4">
                        <div class="relative grid h-[4.5rem] w-[4.5rem] shrink-0 grid-cols-7 grid-rows-7 gap-px border border-[#cfd3da] p-1.5 ring-2 ring-[#f59e0b]/70 ring-offset-2">
                            @foreach (str_split('1110111101010111101111000100101101011101010000101011111011110101111011111') as $i => $bit)
                                @if ($i < 49)<span class="{{ $bit ? 'bg-[#0d253d]' : '' }}"></span>@endif
                            @endforeach
                            <span class="absolute -right-2.5 -top-2.5 grid h-5 w-5 place-items-center rounded-full bg-[#f59e0b] text-[10px] font-semibold text-white">3</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] font-semibold text-[#1e3f8a]">Check that this document is genuine</p>
                            <p class="mt-0.5 text-[11px] text-[#66707f]">Scan the QR code, or enter both codes at <b class="font-semibold text-[#4a5568]">{{ preg_replace('#^https?://#', '', route('document.lookup')) }}</b></p>
                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-3">
                                <div class="relative">
                                    <p class="text-[9px] tracking-[0.06em] text-[#8a8a8a]">REFERENCE NO.</p>
                                    <p class="mt-0.5 inline-block border border-[#d3dcee] bg-[#f1f4fa] px-1.5 font-mono text-[12.5px] font-bold text-[#0d253d] ring-2 ring-[#f59e0b]/70 ring-offset-2">VPSD-2026-00143</p>
                                    <span class="absolute -right-3 top-2 grid h-5 w-5 place-items-center rounded-full bg-[#f59e0b] text-[10px] font-semibold text-white">1</span>
                                </div>
                                <div class="relative">
                                    <p class="text-[9px] tracking-[0.06em] text-[#8a8a8a]">FINGERPRINT</p>
                                    <p class="mt-0.5 inline-block border border-[#d3dcee] bg-[#f1f4fa] px-1.5 font-mono text-[12.5px] font-bold text-[#0d253d] ring-2 ring-[#f59e0b]/70 ring-offset-2">A1B2-C3D4-E5F6-0718</p>
                                    <span class="absolute -right-3 top-2 grid h-5 w-5 place-items-center rounded-full bg-[#f59e0b] text-[10px] font-semibold text-white">2</span>
                                </div>
                            </div>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="text-[12px] font-bold text-[#2a57b4]">DocuMate</p>
                            <p class="text-[10px] leading-relaxed text-[#777]">Leyte Normal University · VPSD<br>Issued November 2, 2026</p>
                        </div>
                    </div>
                </div>
                <ol class="mt-8 space-y-3 text-sm text-[#4a5568]">
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-[#fef3c7] text-xs font-semibold text-[#78350f]">1</span><span><b class="font-medium text-[#0d253d]">Reference No.</b> starts with VPSD and the year.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-[#fef3c7] text-xs font-semibold text-[#78350f]">2</span><span><b class="font-medium text-[#0d253d]">Fingerprint</b> is the 16-character code under it.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-[#fef3c7] text-xs font-semibold text-[#78350f]">3</span><span>Or scan the <b class="font-medium text-[#0d253d]">QR code</b> with your phone camera to skip this form.</span></li>
                </ol>
            </div>
        </div>
    </section>
@endsection
