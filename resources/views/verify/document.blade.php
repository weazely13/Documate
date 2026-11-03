@extends('layouts.public')

@section('title', 'Document check · DocuMate')

@push('head')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
@endpush

@php
    $state = ! $found ? 'missing' : (($voided ?? false) ? 'voided' : ($report['valid'] ? 'valid' : 'failed'));
    $tone = [
        'valid' => ['#d9f3e1', '#065f46', '#059669', 'bx-badge-check', 'Genuine and unaltered', 'VERIFIED'],
        'voided' => ['#fde0ec', '#9f1239', '#be123c', 'bx-block', 'Withdrawn by the VPSD office', 'VOIDED'],
        'failed' => ['#fde0ec', '#9f1239', '#be123c', 'bx-error-alt', 'Does not match its seal', 'NOT VALID'],
        'missing' => ['#ecebe7', '#273951', '#66707f', 'bx-search-alt', 'No record found', 'NO RECORD'],
    ][$state];
    [$bg, $fg, $ink, $icon, $headline, $stampWord] = $tone;
@endphp

@section('content')
    <section class="dm-ledger pb-20 pt-28 sm:pt-32">
        <div class="mx-auto max-w-5xl px-5 sm:px-8">
            <a href="{{ route('document.lookup') }}" class="inline-flex items-center gap-1 text-sm text-[#66707f] hover:text-[#2a57b4]"><i class="bx bx-left-arrow-alt text-lg"></i> Check another document</a>

            {{-- Verdict --}}
            <div class="relative mt-5 overflow-hidden rounded-xl border p-6 sm:p-8" style="background: {{ $bg }}; border-color: color-mix(in srgb, {{ $ink }} 25%, transparent);">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-white/70" style="color: {{ $ink }}"><i class="bx {{ $icon }} text-3xl"></i></span>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-[1.9rem] font-semibold leading-tight tracking-[-0.025em] sm:text-[2.3rem]" style="color: {{ $fg }}">{{ $headline }}</h1>
                        <p class="mt-2 max-w-2xl text-[15px] leading-relaxed" style="color: {{ $fg }}">
                            @switch($state)
                                @case('valid')
                                    This document was issued through DocuMate. Its contents and every signature match the sealed record, and nothing has changed since it was signed.
                                    @break
                                @case('voided')
                                    The VPSD office withdrew this document{{ $voidedAt ? ' on ' . $voidedAt->format('F j, Y') : '' }}. Do not accept it.
                                    @if ($replacement ?? null) A corrected document was issued as <span class="font-mono font-semibold">{{ $replacement }}</span>. @endif
                                    @break
                                @case('failed')
                                    The record behind this code no longer matches its seal. Do not accept this document, and let the VPSD office know.
                                    @break
                                @default
                                    This link doesn't match any finished document. Check that the whole QR code was scanned or the full link was copied. Until then, don't treat the document as genuine.
                            @endswitch
                        </p>
                    </div>
                </div>
                <svg class="pointer-events-none absolute -right-6 -top-6 hidden h-40 w-40 rotate-[-14deg] opacity-25 sm:block" viewBox="0 0 140 140" style="color: {{ $ink }}" aria-hidden="true">
                    <defs><path id="vRing" d="M70,70 m-50,0 a50,50 0 1,1 100,0 a50,50 0 1,1 -100,0"/></defs>
                    <circle cx="70" cy="70" r="64" fill="none" stroke="currentColor" stroke-width="3"/>
                    <circle cx="70" cy="70" r="38" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <text font-size="11.5" font-weight="600" letter-spacing="2.6" fill="currentColor"><textPath href="#vRing">DOCUMATE · VPSD · LNU · DOCUMATE ·</textPath></text>
                    <text x="70" y="74" text-anchor="middle" font-size="{{ strlen($stampWord) > 8 ? 9.5 : 12 }}" font-weight="700" letter-spacing="{{ strlen($stampWord) > 8 ? 0.4 : 1 }}" fill="currentColor">{{ $stampWord }}</text>
                </svg>
            </div>

            @if ($found)
                <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                    {{-- What the record says --}}
                    <article class="rounded-[6px] bg-white px-6 pb-6 pt-5 shadow-[0_30px_60px_-36px_rgba(13,37,61,0.45),0_1px_3px_rgba(13,37,61,0.06)] sm:px-8 sm:pt-7">
                        <div class="flex items-center gap-3 border-b-2 border-[#0d253d] pb-3">
                            <img src="{{ asset('images/lnulogo.png') }}" alt="" class="h-10 w-10 object-contain">
                            <div class="min-w-0 leading-tight">
                                <p class="truncate text-sm font-semibold text-[#0d253d]">{{ $documentName }}</p>
                                <p class="truncate text-xs text-[#66707f]">The sealed record, as kept by the VPSD office</p>
                            </div>
                        </div>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)] gap-3 sm:grid-cols-[11rem_minmax(0,1fr)]">
                                <dt class="text-[#66707f]">Reference No.</dt>
                                <dd class="border-b border-[#e7e5e1] pb-1 font-mono font-semibold text-[#0d253d]">{{ $ws->transaction_code }}</dd>
                            </div>
                            @foreach ($details as $label => $value)
                                <div class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)] gap-3 sm:grid-cols-[11rem_minmax(0,1fr)]">
                                    <dt class="text-[#66707f]">{{ $label }}</dt>
                                    <dd class="whitespace-pre-line break-words border-b border-[#e7e5e1] pb-1 font-medium text-[#0d253d]">{{ $value }}</dd>
                                </div>
                            @endforeach
                            <div class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)] gap-3 sm:grid-cols-[11rem_minmax(0,1fr)]">
                                <dt class="text-[#66707f]">Issued</dt>
                                <dd class="border-b border-[#e7e5e1] pb-1 font-medium text-[#0d253d]">{{ $ws->issued_at?->format('F j, Y · g:i A') ?? '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)] gap-3 sm:grid-cols-[11rem_minmax(0,1fr)]">
                                <dt class="text-[#66707f]">Fingerprint</dt>
                                <dd class="border-b border-[#e7e5e1] pb-1 font-mono font-semibold tracking-wide text-[#0d253d]">{{ $report['fingerprint'] }}</dd>
                            </div>
                        </dl>
                        <p class="mt-5 rounded-lg bg-[#fef3c7] px-3.5 py-2.5 text-[13px] leading-relaxed text-[#78350f]">
                            Compare every line, and the fingerprint, with the copy in your hands. If anything differs, the copy was changed.
                        </p>
                    </article>

                    <div class="space-y-6">
                        {{-- Signatures --}}
                        <section class="rounded-xl border border-[#e7e5e1] bg-white">
                            <h2 class="border-b border-[#e7e5e1] px-5 py-3.5 text-[15px] font-semibold text-[#0d253d]">Signatures</h2>
                            <ul class="divide-y divide-[#ecebe7]">
                                @forelse ($report['signers'] as $s)
                                    <li class="flex gap-3 px-5 py-3.5">
                                        <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full {{ $s['ok'] ? 'bg-[#d9f3e1] text-[#059669]' : 'bg-[#fde0ec] text-[#be123c]' }}">
                                            <i class="bx {{ $s['ok'] ? 'bx-check' : 'bx-x' }} text-base"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-[#0d253d]">{{ $s['name'] ?: '—' }}</p>
                                            <p class="text-[13px] text-[#4a5568]">{{ $s['role'] }}@if ($s['label'] && $s['label'] !== $s['role']) · {{ $s['label'] }}@endif</p>
                                            <p class="mt-0.5 text-xs text-[#66707f]">
                                                {{ $s['signed_at']?->format('M j, Y · g:i A') }}
                                                @if ($s['seal']) · seal <span class="font-mono">{{ $s['seal'] }}</span>@endif
                                            </p>
                                        </div>
                                    </li>
                                @empty
                                    <li class="px-5 py-4 text-sm text-[#66707f]">This document did not need signatures.</li>
                                @endforelse
                            </ul>
                        </section>

                        {{-- Checks --}}
                        <section class="rounded-xl border border-[#e7e5e1] bg-white">
                            <h2 class="border-b border-[#e7e5e1] px-5 py-3.5 text-[15px] font-semibold text-[#0d253d]">What was checked</h2>
                            <ul class="space-y-3 px-5 py-4 text-sm">
                                @foreach ([
                                    [$report['content_ok'], 'The answers match the sealed fingerprint'],
                                    [$report['signers_ok'], 'Every signature and its image are intact'],
                                    [$report['audit_ok'], 'The history of this document has not been altered'],
                                ] as [$ok, $label])
                                    <li class="flex items-center gap-2.5">
                                        <i class="bx {{ $ok ? 'bx-check-circle text-[#059669]' : 'bx-x-circle text-[#be123c]' }} text-xl"></i>
                                        <span class="{{ $ok ? 'text-[#273951]' : 'font-medium text-[#9f1239]' }}">{{ $label }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>

                        {{-- File check --}}
                        <section class="rounded-xl border border-[#e7e5e1] bg-white p-5">
                            <h2 class="text-[15px] font-semibold text-[#0d253d]">Have the PDF file?</h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-[#4a5568]">Check that it is byte-for-byte the file DocuMate issued. It is only compared, never kept.</p>
                            <form method="POST" action="{{ route('document.verify.file', $token) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                                @csrf
                                <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-lg border border-dashed border-[#d4d1cb] px-3 py-2.5 text-sm text-[#4a5568] hover:border-[#2a57b4]">
                                    <i class="bx bx-file text-lg text-[#2a57b4]"></i>
                                    <span class="truncate" data-file-label>Choose a PDF</span>
                                    <input type="file" name="pdf" accept="application/pdf" required class="sr-only"
                                           onchange="this.closest('label').querySelector('[data-file-label]').textContent = this.files[0] ? this.files[0].name : 'Choose a PDF'">
                                </label>
                                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#2a57b4] px-4 text-sm font-medium text-white hover:bg-[#24499a]">Compare</button>
                            </form>
                            @error('pdf') <p class="mt-2 text-sm text-[#be123c]">{{ $message }}</p> @enderror
                            @if (session('file_check') === 'match')
                                <p class="mt-3 flex gap-2 rounded-lg bg-[#d9f3e1] px-3 py-2.5 text-sm text-[#065f46]"><i class="bx bx-check-circle mt-0.5"></i>Identical to the PDF DocuMate issued.</p>
                            @elseif (session('file_check') === 'mismatch')
                                <p class="mt-3 flex gap-2 rounded-lg bg-[#fef3c7] px-3 py-2.5 text-sm text-[#78350f]"><i class="bx bx-error mt-0.5"></i><span>Not the same file. It may have been edited, or simply re-saved or printed to PDF again. Go by the record above and compare it line by line.</span></p>
                            @endif
                        </section>
                    </div>
                </div>
            @endif

            <p class="mt-10 text-center text-xs leading-relaxed text-[#66707f]">
                Checked against the sealed records of the Leyte Normal University VPSD office in DocuMate. The check does not depend on how a signature looks.
            </p>
        </div>
    </section>
@endsection
