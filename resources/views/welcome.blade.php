@extends('layouts.public')

@section('title', 'DocuMate · Student transactions at LNU')

@php
    $btnPrimary = 'inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-[#2a57b4] px-4 text-sm font-medium sm:h-11 sm:gap-2 sm:px-5 sm:text-[15px] text-white shadow-[0_6px_16px_-6px_rgba(42,87,180,0.55)] transition hover:bg-[#24499a] active:bg-[#1e4090] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2a57b4] focus-visible:ring-offset-2';
    $btnSecondary = 'inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-[#d4d1cb] bg-white px-4 text-sm font-medium sm:h-11 sm:gap-2 sm:px-5 sm:text-[15px] text-[#0d253d] shadow-sm transition hover:bg-[#f5f4f2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2a57b4] focus-visible:ring-offset-2';
    $h2 = 'text-[1.6rem] font-semibold leading-[1.1] tracking-[-0.025em] text-[#0d253d] sm:text-[2.3rem] lg:text-[2.75rem] [text-wrap:balance]';
    $lead = 'text-[14.5px] leading-relaxed text-[#4a5568] sm:text-base lg:text-[17px]';

@endphp

@section('content')

    {{-- ================= HERO ================= --}}
    <section class="dm-ledger relative overflow-hidden pt-24 sm:pt-32">
        <div class="relative mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)] items-end gap-6 px-5 sm:gap-10 sm:px-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:gap-6">

            <div class="relative z-10 pb-2 lg:pb-24">
                <h1 class="text-[2.05rem] font-semibold leading-[1.06] tracking-[-0.03em] text-[#0d253d] sm:text-[3.4rem] lg:text-[4.2rem] [text-wrap:balance]">
                    Request, track and sign student documents
                    <span class="relative whitespace-nowrap text-[#2a57b4]">in one place<svg class="absolute -bottom-2 left-0 h-3 w-full text-[#2a57b4]/35" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 8 C 80 2, 160 12, 298 4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></span>
                </h1>
                <p class="mt-5 max-w-xl sm:mt-7 {{ $lead }}">
                    DocuMate is the digital desk of the Office of the Vice President for Student Development at Leyte Normal University.
                    Fill in forms online, book a visit, follow every signature, and download the finished document.
                </p>
                <div class="mt-7 grid grid-cols-2 gap-2 sm:mt-9 sm:flex sm:gap-3">
                    <a href="{{ route('register') }}" class="{{ $btnSecondary }}"><span class="sm:hidden">Create account</span><span class="hidden sm:inline">Create a student account</span></a>
                </div>
            </div>

            {{-- The students --}}
            <div class="relative mx-auto h-[18.5rem] w-full max-w-[24rem] sm:h-[30rem] sm:max-w-[30rem] lg:h-[40rem] lg:max-w-none">
                <div class="absolute bottom-0 left-1/2 aspect-square w-[92%] -translate-x-1/2 translate-y-[18%] rounded-full bg-[#dcecfa]" aria-hidden="true"></div>
                <div class="absolute bottom-[20%] right-[6%] h-24 w-24 rounded-full bg-[#e6e0f5] sm:h-32 sm:w-32" aria-hidden="true"></div>
                <img src="{{ asset('images/fourcoverstudents.png') }}" alt="Four LNU students with their phones, laptops and papers"
                     class="absolute bottom-0 left-1/2 h-full w-auto max-w-none -translate-x-1/2 object-contain object-bottom">
            </div>
        </div>
    </section>

    {{-- ================= WHERE IT'S USED ================= --}}
    <section id="about" class="bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-5 sm:gap-4 sm:px-8 sm:py-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/lnulogo.png') }}" alt="Leyte Normal University" class="h-8 w-8 object-contain sm:h-9 sm:w-9">
                <img src="{{ asset('images/osdas-logo-256.png') }}" alt="Office of Student Development and Auxiliary Services" class="h-8 w-8 object-contain sm:h-9 sm:w-9">
                <p class="text-[13px] leading-snug sm:text-sm text-[#4a5568]"><span class="block font-medium text-[#0d253d] sm:inline">Leyte Normal University</span><span class="hidden sm:inline"> · </span>Office of the VP for Student Development, Tacloban City</p>
            </div>
            <p class="text-[13px] text-[#66707f] sm:text-sm">Used by students, organization officers, VPSD staff and the officials who sign.</p>
        </div>
    </section>

    {{-- ================= THE ROUTE (routing slip) ================= --}}
    <section id="how" class="bg-[#fafaf9] py-14 sm:py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:items-end">
                <h2 class="{{ $h2 }}">Every request travels the same route. Now you can watch it.</h2>
                <p class="{{ $lead }} lg:pb-1">No more asking at the window whether the Dean has signed. Each step is stamped the moment it happens, and you get an email when it's your turn.</p>
            </div>

            {{-- Desktop: a routing slip --}}
            <div class="mt-12 hidden overflow-hidden rounded-xl border border-[#d4d1cb] bg-white shadow-[0_24px_48px_-32px_rgba(13,37,61,0.4)] md:block">
                <div class="flex items-center justify-between border-b-2 border-[#0d253d] px-6 py-3">
                    <p class="text-sm font-semibold text-[#0d253d]">Routing slip · <span class="font-mono font-normal text-[#4a5568]">VPSD-2026-00143</span></p>
                    <p class="text-xs text-[#66707f]">Admission Slip</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#fafaf9] text-[13px] text-[#66707f]">
                        <tr>
                            <th class="w-[22%] px-6 py-2.5 font-medium">Desk</th>
                            <th class="px-6 py-2.5 font-medium">What happens there</th>
                            <th class="w-[18%] px-6 py-2.5 font-medium">When</th>
                            <th class="w-[17%] px-6 py-2.5 font-medium">Stamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ecebe7]">
                        @foreach ([
                            ['You', 'Pick the form. Your name, program and section are already filled in from your profile.', 'Nov 1 · 9:14 AM', 'Received', '#2a57b4', '-4deg'],
                            ['VPSD office', 'Staff check it, helped by an AI reading of anything you attached, or send it back with a note.', 'Nov 1 · 10:02 AM', 'Checked', '#0891b2', '3deg'],
                            ['Signatories', 'Each official gets an email in turn and signs online, in the order the form needs.', 'Nov 2 · 8:55 AM', 'Signed', '#6d4fc2', '-2deg'],
                            ['DocuMate', 'The finished document is sealed. Any change after this would break the seal.', 'Nov 2 · 9:03 AM', 'Sealed', '#059669', '4deg'],
                        ] as [$desk, $what, $when, $stamp, $color, $rot])
                            <tr>
                                <td class="px-6 py-5 align-top font-medium text-[#0d253d]">{{ $desk }}</td>
                                <td class="px-6 py-5 align-top leading-relaxed text-[#4a5568]">{{ $what }}</td>
                                <td class="px-6 py-5 align-top font-mono text-[12.5px] text-[#4a5568]">{{ $when }}</td>
                                <td class="px-6 py-4 align-top">
                                    <span class="dm-rubber inline-block" style="--c: {{ $color }}; transform: rotate({{ $rot }})">{{ $stamp }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Phones: the same route, top to bottom --}}
            <ol class="mt-8 space-y-2.5 md:hidden">
                @foreach ([
                    ['You', 'Pick the form. Your details are already filled in.', 'Received', '#2a57b4'],
                    ['VPSD office', 'Staff check it, or send it back with a note.', 'Checked', '#0891b2'],
                    ['Signatories', 'Each official signs online, in order.', 'Signed', '#6d4fc2'],
                    ['DocuMate', 'The finished document is sealed.', 'Sealed', '#059669'],
                ] as [$desk, $what, $stamp, $color])
                    <li class="flex items-start justify-between gap-3 rounded-xl border border-[#e7e5e1] bg-white p-3.5">
                        <div class="min-w-0">
                            <p class="text-[15px] font-medium text-[#0d253d]">{{ $desk }}</p>
                            <p class="mt-0.5 text-[13px] leading-relaxed text-[#4a5568]">{{ $what }}</p>
                        </div>
                        <span class="dm-rubber dm-rubber-sm shrink-0" style="--c: {{ $color }}; transform: rotate(-3deg)">{{ $stamp }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= TWO DESKS (file folders) ================= --}}
    <section id="features" class="bg-white py-14 sm:py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 class="{{ $h2 }} max-w-3xl">One record, two sides of the counter</h2>
            <p class="mt-5 max-w-2xl {{ $lead }}">Students and the office work on the same document, so what you see is exactly what they see.</p>

            <div class="mt-10 grid sm:mt-14 grid-cols-[minmax(0,1fr)] gap-x-6 gap-y-14 lg:grid-cols-2">
                {{-- Student folder --}}
                <article class="dm-folder relative" style="--tab: #dcecfa; --body: #dcecfa;">
                    <span class="dm-folder-tab">For students and officers</span>
                    <div class="relative overflow-hidden rounded-b-xl rounded-tr-xl bg-[var(--body)] p-5 sm:p-8">
                        <div class="relative z-10 sm:max-w-[19rem]">
                            <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0d253d] sm:text-2xl">Ask once. Follow it to the end.</h3>
                            <ul class="mt-4 space-y-2.5 text-sm text-[#273951] sm:mt-5 sm:space-y-3 sm:text-[15px]">
                                @foreach (['Request permits, slips and clearances online', 'Book a visit and get a queue number', 'See who has signed, and who is next', 'Download the sealed PDF when it is done', 'Check your clearance for the semester'] as $item)
                                    <li class="flex gap-2.5"><i class="bx bx-check mt-0.5 text-lg text-[#2a57b4]" aria-hidden="true"></i><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <img src="{{ asset('images/coverstudent.png') }}" alt="A student with his phone and his printed form"
                             class="absolute -bottom-6 -right-4 hidden h-[105%] w-auto object-contain sm:block">
                    </div>
                </article>

                {{-- Office folder --}}
                <article class="dm-folder relative" style="--tab: #e6e0f5; --body: #e6e0f5;">
                    <span class="dm-folder-tab">For the VPSD office and signatories</span>
                    <div class="rounded-b-xl rounded-tr-xl bg-[var(--body)] p-5 sm:p-8">
                        <h3 class="text-xl font-semibold tracking-[-0.02em] text-[#0d253d] sm:text-2xl">The whole desk, without the stack.</h3>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-[#273951] sm:text-[15px]">See what is new, what an AI check flagged, and what has waited too long, then send it for signing in one move.</p>
                        <div class="mt-6 overflow-hidden rounded-lg border border-white bg-white shadow-[0_18px_36px_-24px_rgba(13,37,61,0.5)]" aria-hidden="true">
                            <div class="flex items-center justify-between border-b border-[#e7e5e1] px-4 py-2.5">
                                <span class="text-[13px] font-semibold text-[#0d253d]">To review</span>
                                <span class="text-[11px] text-[#9f1239]">3 waiting 7+ school days</span>
                            </div>
                            @foreach ([
                                ['Permit to Conduct an In-Campus Activity', 'No issues', 'bg-[#d9f3e1] text-[#065f46]'],
                                ['Disciplinary Action Slip', 'Requires checking', 'bg-[#fef3c7] text-[#78350f]'],
                                ['Model Student Peer Rating', 'No issues', 'bg-[#d9f3e1] text-[#065f46]'],
                            ] as [$doc, $ai, $tone])
                                <div class="flex items-center gap-3 border-b border-[#f0eeea] px-4 py-2.5 last:border-0">
                                    <img src="{{ asset('images/lnulogo.png') }}" alt="" class="h-6 w-6 shrink-0 rounded-full object-contain">
                                    <span class="min-w-0 flex-1 truncate text-[12.5px] text-[#273951]">{{ $doc }}</span>
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10.5px] font-medium {{ $tone }}">{{ $ai }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ================= ASSISTANT (index cards) ================= --}}
    <section id="assistant" class="dm-ledger overflow-hidden border-t py-14 sm:py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
                <h2 class="{{ $h2 }}">Ask before you line up</h2>
                <p class="{{ $lead }}">The DocuMate Assistant sits on every page. It knows which screen you're on, LNU's offices and buildings, and the Student Handbook, and it tells you when something isn't in its records.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:mt-14 sm:gap-6 md:grid-cols-3">
                @foreach ([
                    ['Where is the Guidance Office?', 'Student Center Building, 1st Floor.', 'From the LNU office directory', '-1.2deg'],
                    ['What does the handbook say about the uniform?', 'Wear the complete uniform on weekdays, except Wednesday, which is wash day.', 'Student Handbook, p. 38', '0.8deg'],
                    ['Who is Mr. Juan dela Cruz, my math teacher?', "I don't have him in DocuMate's records. Your department office can help.", 'It says so when it doesn\'t know', '-0.6deg'],
                ] as [$q, $a, $src, $rot])
                    <figure class="dm-index-card" style="transform: rotate({{ $rot }})">
                        <blockquote>
                            <p class="text-sm font-medium text-[#0d253d] sm:text-[15px]">“{{ $q }}”</p>
                            <p class="mt-3 flex gap-2 border-t border-[#f3c2c8] pt-3 text-sm leading-relaxed text-[#273951] sm:mt-4 sm:pt-4 sm:text-[15px]"><i class="bx bx-bot mt-0.5 shrink-0 text-lg text-[#2a57b4]" aria-hidden="true"></i><span>{{ $a }}</span></p>
                        </blockquote>
                        <figcaption class="mt-4 text-xs text-[#66707f]">{{ $src }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="mt-8 text-center text-[13px] text-[#4a5568] sm:mt-10 sm:text-sm"><a href="{{ route('login') }}" class="font-medium text-[#2a57b4] underline-offset-4 hover:underline">Sign in</a> and look for the robot in the corner of any page.</p>
        </div>
    </section>

    {{-- ================= WHAT MAKES IT HOLD UP ================= --}}
    <section class="bg-[#0d253d] py-14 text-white sm:py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:gap-12 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-20">
            <div>
                <h2 class="text-[1.6rem] font-semibold leading-[1.1] tracking-[-0.025em] sm:text-[2.3rem] lg:text-[2.75rem] [text-wrap:balance]">A copy anyone can check, years from now</h2>
                <p class="mt-4 text-[14.5px] leading-relaxed text-[#c3cedd] sm:mt-5 sm:text-base lg:text-[17px]">
                    Every signature is sealed the moment it is given. If one word of a finished document changed, the seal would no longer match,
                    and the verify page would say so.
                </p>
                <a href="{{ route('document.lookup') }}" class="mt-6 inline-flex h-10 items-center gap-2 rounded-lg bg-white px-4 text-sm font-medium sm:mt-8 sm:h-11 sm:px-5 sm:text-[15px] text-[#0d253d] transition hover:bg-[#f0f4fb]">
                    <i class="bx bx-shield-quarter text-lg text-[#2a57b4]" aria-hidden="true"></i> Verify a document
                </a>
            </div>
            <dl class="grid gap-px overflow-hidden rounded-xl bg-white/10 sm:grid-cols-2">
                @foreach ([
                    ['A QR code on every copy', 'Scan it to open the sealed record behind the paper.'],
                    ['A fingerprint you can type', 'No camera? The reference and fingerprint printed at the bottom work too.'],
                    ['Withdrawn copies say so', 'If the office voids a document, its page shows it, and points to the corrected one.'],
                    ['Records that outlast the form', 'Finished documents stay readable even after a form is changed or retired.'],
                ] as [$t, $d])
                    <div class="bg-[#0d253d] p-4 sm:p-6">
                        <dt class="text-sm font-semibold sm:text-[15px]">{{ $t }}</dt>
                        <dd class="mt-1.5 text-[13px] leading-relaxed text-[#c3cedd] sm:mt-2 sm:text-sm">{{ $d }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ================= ORGANIZATION DIRECTORY ================= --}}
    <section id="orgchart" class="bg-white py-14 sm:py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
                <h2 class="{{ $h2 }}">Who's who at Leyte Normal University</h2>
                <p class="{{ $lead }}">The university's academic and administrative staff, by office. Search for a name or a role, or pick an office.</p>
            </div>

            <div class="mt-8 overflow-hidden rounded-xl border border-[#e7e5e1] bg-white shadow-[0_24px_48px_-36px_rgba(13,37,61,0.4)] sm:mt-10">
                {{-- Controls --}}
                <div class="flex flex-col gap-3 border-b border-[#e7e5e1] bg-[#fafaf9] p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                    <div class="grid grid-cols-2 rounded-lg bg-[#ecebe7] p-1 sm:inline-flex" role="tablist" aria-label="Staff group">
                        <button type="button" role="tab" data-orgtab="administrative" aria-selected="true" class="org-seg rounded-md px-3 py-1.5 text-[13px] font-medium sm:px-3.5 sm:text-sm">Administrative <span data-orgcount="administrative" class="ml-1 tabular-nums text-[#66707f]"></span></button>
                        <button type="button" role="tab" data-orgtab="academic" aria-selected="false" class="org-seg rounded-md px-3 py-1.5 text-[13px] font-medium sm:px-3.5 sm:text-sm">Academic <span data-orgcount="academic" class="ml-1 tabular-nums text-[#66707f]"></span></button>
                    </div>
                    <div class="relative sm:w-80">
                        <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#9a978f]" aria-hidden="true"></i>
                        <input id="org-search" type="search" placeholder="Search a name, office or role" aria-label="Search the directory"
                               class="h-10 w-full rounded-lg border-[#d4d1cb] bg-white pl-9 pr-3 text-sm text-[#0d253d] placeholder:text-[#9a978f] focus:border-[#2a57b4] focus:ring-2 focus:ring-[#2a57b4]/15">
                    </div>
                </div>

                <div class="grid lg:grid-cols-[17rem_minmax(0,1fr)]">
                    {{-- Offices --}}
                    <nav id="org-offices" aria-label="Offices"
                         class="flex gap-2 overflow-x-auto border-b border-[#e7e5e1] p-3 [scrollbar-width:none] lg:max-h-[42rem] lg:flex-col lg:gap-0.5 lg:overflow-y-auto lg:border-b-0 lg:border-r lg:p-3 [&::-webkit-scrollbar]:hidden"></nav>
                    {{-- People --}}
                    <div id="org-people" class="org-scroll p-3 sm:p-6 lg:max-h-[42rem] lg:overflow-y-auto" aria-live="polite"></div>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-[#9a978f] sm:mt-4 sm:text-xs">Source: Leyte Normal University, Academic &amp; Administrative Staffs directory. Photos will be added when available.</p>
        </div>
    </section>

    {{-- ================= LOCATION ================= --}}
    <section id="location" class="bg-[#fafaf9] py-14 sm:py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)] gap-8 px-5 sm:gap-10 sm:px-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-16">
            <div>
                <h2 class="{{ $h2 }}">Where to find the VPSD office</h2>
                <p class="mt-5 {{ $lead }}">Administration Building, 2nd Floor, at the main campus in Tacloban City.</p>
                <dl class="mt-6 divide-y divide-[#e7e5e1] border-y border-[#e7e5e1] sm:mt-8">
                    @foreach ([
                        ['bx-map', 'Address', 'P. Paterno St., Brgy. 50, Tacloban City, Leyte', null],
                        ['bx-time-five', 'Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
                        ['bx-phone', 'Phone', '+63 (53) 832 3205', 'tel:+63538323205'],
                        ['bx-envelope', 'Email', 'info@lnu.edu.ph', 'mailto:info@lnu.edu.ph'],
                    ] as [$icon, $label, $value, $href])
                        <div class="flex items-start gap-3 py-3 sm:gap-4 sm:py-4">
                            <i class="bx {{ $icon }} mt-0.5 text-lg text-[#2a57b4] sm:text-xl" aria-hidden="true"></i>
                            <dt class="w-24 shrink-0 text-[13px] text-[#66707f] sm:w-28 sm:text-sm">{{ $label }}</dt>
                            <dd class="min-w-0 text-sm font-medium text-[#0d253d] sm:text-[15px]">
                                @if ($href)<a href="{{ $href }}" class="hover:text-[#2a57b4]">{{ $value }}</a>@else{{ $value }}@endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div class="overflow-hidden rounded-xl border border-[#e7e5e1] bg-white p-1.5 shadow-[0_24px_48px_-36px_rgba(13,37,61,0.45)]">
                <iframe src="https://maps.google.com/maps?q=Leyte+Normal+University,+Tacloban+City,+Leyte,+Philippines&z=17&output=embed"
                        class="h-[240px] w-full rounded-lg sm:h-[360px] lg:h-[440px]" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map of Leyte Normal University"></iframe>
            </div>
        </div>
    </section>

    {{-- ================= FAQ PREVIEW ================= --}}
    <section id="faqs" class="bg-white py-14 sm:py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)] gap-6 px-5 sm:gap-10 sm:px-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16">
            <div>
                <h2 class="{{ $h2 }}">Questions people ask first</h2>
                <p class="mt-5 {{ $lead }}">Short answers to the common ones. The full list covers accounts, forms, appointments and privacy.</p>
                <a href="{{ route('faqs') }}" class="{{ $btnSecondary }} mt-6 sm:mt-8">Browse all FAQs <i class="bx bx-right-arrow-alt text-lg"></i></a>
            </div>
            @php $preview = collect(config('faqs'))->flatten(1)->take(5); @endphp
            <div class="divide-y divide-[#e7e5e1] border-y border-[#e7e5e1]">
                @foreach ($preview as $item)
                    <details class="faq-item group py-1" @if ($loop->first) open @endif>
                        <summary class="flex items-center justify-between gap-4 py-3.5 text-left sm:py-4">
                            <span class="text-[14.5px] font-medium sm:text-base text-[#0d253d] group-hover:text-[#1e4090]">{{ $item['q'] }}</span>
                            <i class="bx bx-plus shrink-0 text-xl text-[#66707f] transition-transform duration-300 group-open:rotate-45 group-open:text-[#2a57b4]" aria-hidden="true"></i>
                        </summary>
                        <p class="pb-4 pr-6 text-sm leading-relaxed text-[#4a5568] sm:pb-5 sm:pr-8 sm:text-[15px]">{{ strip_tags(preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $item['a'])) }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= CLOSING ================= --}}
    <section class="bg-[#fafaf9] px-5 py-14 sm:px-8 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-4xl text-center">
            <p class="text-[2.1rem] leading-none text-[#2a57b4] sm:text-[3rem] lg:text-[3.4rem]" style="font-family: 'Gveret Levin', cursive;">Signed, sealed, done.</p>
            <p class="mx-auto mt-5 max-w-xl {{ $lead }}">Your next form is a few taps away. Most requests never need a trip to the office.</p>
            <div class="mt-6 grid grid-cols-2 gap-2 sm:mt-8 sm:flex sm:justify-center sm:gap-3">
                <a href="{{ route('register') }}" class="{{ $btnPrimary }}"><span class="sm:hidden">Create account</span><span class="hidden sm:inline">Create a student account</span></a>
            </div>
        </div>
    </section>

    {{-- ================= TEAM ================= --}}
    <section id="creators" class="bg-white py-14 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
                <h2 class="{{ $h2 }}">Made at LNU, by LNU students</h2>
                <p class="{{ $lead }}">A team of four LNU students built DocuMate to make student transactions simpler for everyone.</p>
            </div>
            <div class="mt-8 overflow-hidden rounded-xl bg-[#dcecfa] sm:mt-10">
                <img src="{{ asset('images/coverpeople.png') }}" alt="The DocuMate team" class="mx-auto h-auto w-full max-w-4xl object-contain">
            </div>
            @php
                // Photos live in public/images/pfp1.png ... pfp4.png
                // TODO: replace each '#' with that person's real profile link.
                $creators = [
                    [
                        'photo'    => 'images/pfp1.png',
                        'name'     => 'Rujen Andrea Ecaldre',
                        'role'     => 'Project Leader',
                        'facebook' => '#',
                        'github'   => '#',
                        'bio'      => 'Leads the team by managing workflows, ensuring collaboration, and keeping the project aligned with its goals and deadlines.',
                        'tasks'    => ['Research', 'Management', 'System Design', 'Database'],
                    ],
                    [
                        'photo'    => 'images/pfp2.png',
                        'name'     => 'Clarisse Villa',
                        'role'     => 'Technical Writer',
                        'facebook' => '#',
                        'github'   => '#',
                        'bio'      => 'Produces structured technical documentation, ensuring all system components, workflows, and outputs are clearly communicated and well-documented.',
                        'tasks'    => ['Research', 'Documentation', 'System Analyst', 'Charts'],
                    ],
                    [
                        'photo'    => 'images/pfp3.png',
                        'name'     => 'Clarence Magpatoc',
                        'role'     => 'Research & Development',
                        'facebook' => '#',
                        'github'   => '#',
                        'bio'      => 'Conducts research, analyzes system requirements, and develops solutions to improve functionality, efficiency, and innovation within the project.',
                        'tasks'    => ['Research', 'Laravel', 'Tailwind', 'System Analyst'],
                    ],
                    [
                        'photo'    => 'images/pfp4.png',
                        'name'     => 'Khanley Mesa',
                        'role'     => 'Design & Development',
                        'facebook' => '#',
                        'github'   => '#',
                        'bio'      => 'Handles system design and implementation, integrating front-end and back-end components to deliver a functional and user-friendly application.',
                        'tasks'    => ['UI/UX', 'Backend', 'Laravel', 'Tailwind'],
                    ],
                ];
            @endphp

            <!-- INFINITE HORIZONTAL SCROLL (hover to pause). The set is rendered twice for a seamless loop. -->
            <div class="marquee mt-10">
                <div class="marquee-track">
                    @foreach ([0, 1] as $copy)
                        <div class="marquee-group" @if ($copy === 1) aria-hidden="true" @endif>
                            @foreach ($creators as $c)
                                <article class="creator-card w-48 sm:w-64 aspect-[5/7] shrink-0 rounded-xl bg-white border border-[#e7e5e1] shadow-[0_8px_24px_-16px_rgba(13,37,61,0.35)] p-4 sm:p-5 flex flex-col items-center text-center overflow-hidden">

                                    <!-- 1. Photo -->
                                    <img src="{{ asset($c['photo']) }}" alt="{{ $c['name'] }}"
                                         class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover ring-4 ring-[#dcecfa] shrink-0">

                                    <!-- 2. Name and role -->
                                    <h3 class="mt-3 font-semibold text-[#0d253d] text-sm sm:text-base leading-tight">{{ $c['name'] }}</h3>
                                    <p class="text-[11px] sm:text-xs text-[#2A57B4] font-medium">{{ $c['role'] }}</p>

                                    <!-- 3. Socials -->
                                    <div class="flex gap-3 mt-2">
                                        <a href="{{ $c['facebook'] }}" @if ($c['facebook'] !== '#') target="_blank" rel="noopener" @endif aria-label="{{ $c['name'] }} on Facebook" class="text-[#2A57B4]/70 hover:text-[#003399] transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2v-2.9h2.2V9.4c0-2.2 1.3-3.4 3.3-3.4.96 0 2 .17 2 .17v2.2h-1.1c-1.1 0-1.4.68-1.4 1.38v1.66h2.4l-.38 2.9h-2.02v7A10 10 0 0 0 22 12z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ $c['github'] }}" @if ($c['github'] !== '#') target="_blank" rel="noopener" @endif aria-label="{{ $c['name'] }} on GitHub" class="text-[#2A57B4]/70 hover:text-[#003399] transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                <path d="M12 .5C5.73.5.98 5.26.98 11.53c0 4.87 3.16 9 7.55 10.46.55.1.75-.24.75-.54v-2.02c-3.07.67-3.72-1.48-3.72-1.48-.5-1.27-1.23-1.6-1.23-1.6-1-.7.08-.69.08-.69 1.1.08 1.68 1.13 1.68 1.13.98 1.68 2.56 1.2 3.18.92.1-.7.38-1.2.7-1.48-2.45-.28-5.03-1.22-5.03-5.42 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .92-.29 3.02 1.13a10.5 10.5 0 0 1 5.5 0c2.1-1.42 3.02-1.13 3.02-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.21-2.58 5.14-5.04 5.41.39.34.74 1.01.74 2.04v3.02c0 .3.2.65.76.54 4.38-1.46 7.54-5.6 7.54-10.46C23.02 5.26 18.27.5 12 .5z"/>
                                            </svg>
                                        </a>
                                    </div>

                                    <!-- 4. Details -->
                                    <p class="mt-3 text-[10px] sm:text-xs text-gray-600 leading-snug line-clamp-3 sm:line-clamp-4">{{ $c['bio'] }}</p>

                                    <!-- 5. Tasks -->
                                    <div class="mt-auto pt-2 flex flex-wrap justify-center gap-1">
                                        @foreach ($c['tasks'] as $task)
                                            <span class="px-2` py-0.5 text-[10px] bg-[#f0f4fb] text-[#2a57b4] rounded-md">{{ $task }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Source: Leyte Normal University, Academic & Administrative Staffs directory */
    var orgChartData = {
        administrative: [
            { office: "Office of the University President", people: [
                { name: "Dr. Gil Nicetas B. Villarino", title: "University President" },
                { name: "Mr. Ronnyl E. Taldo", title: "Executive Assistant III" },
                { name: "Atty. Divina Patty B. Sibay", title: "Attorney IV", note: "Legal/Data Protection Officer and Concurrent OIC University and Board Secretary" },
                { name: "Ms. Nora C. Cabriana", title: "Internal Auditor III", note: "Head, Internal Audit Office" },
                { name: "Ms. Danielle Anne S. Durna", title: "Planning Officer III", note: "Head, Planning Office" },
                { name: "Ms. Roselle Y. Almayda", title: "Project Development Officer III", note: "Head, Project Development Office" },
                { name: "Ms. Michell T. Acala", title: "Director, Gender and Development Office" },
                { name: "Mr. Jobert C. Narte", title: "Director, LNU Heritage Museum" },
                { name: "Dr. Generoso N. Mazo", title: "Director, Alumni Affairs Office" },
                { name: "Mr. Miguel Lemuel Emmanuel T. Dumas", title: "Director, Student/Institutional Performing Arts" },
                { name: "Dr. Gillian Mae G. Villaflor", title: "Director, Public Affairs and Marketing Communications Office" },
                { name: "Dr. Mark Lester P. Laurente", title: "Director, Information Technology Support Office" },
                { name: "Prof. Jose Ismael S. Salamia", title: "Director, Sustainability, Climate, and Disaster Resilience Office" }
            ]},
            { office: "Office of the Vice President for Administration and Finance", people: [
                { name: "Dr. Solomon D. Faller, Jr.", title: "Vice President for Administration and Finance" },
                { name: "Mr. Oreste M. Ortega, Jr.", title: "Chief Administrative Officer for Administration" },
                { name: "Ms. Josisa C. Conchada", title: "Chief Administrative Officer for Finance" },
                { name: "Prof. Michael Jun M. Ponciano", title: "Director, Physical Plants and Facilities Office" },
                { name: "Dr. Ma. Victoria D. Naboya", title: "Director, Quality Management System – SUC Levelling and ISO" },
                { name: "Dr. Luis Luigi Eugenio A. Valencia", title: "Director, Auxiliary Services" },
                { name: "Ms. Maria Gina C. Durango", title: "Administrative Officer V", note: "Head, Supply and Property Management Office" },
                { name: "Ms. Jasmin M. Graveles", title: "Administrative Officer V", note: "Head, Human Resource Management Office" },
                { name: "Mr. Jumar O. Lumapak", title: "Administrative Officer V", note: "Head, Procurement Office" },
                { name: "Mr. Cesar B. Blanco", title: "Administrative Officer V", note: "Head, Records Management Office; Concurrent Head, Income Generating Office" },
                { name: "Ms. Jocelyn C. Aboy", title: "Administrative Officer V", note: "Head, Cashiering Office" },
                { name: "Ms. Arra Jen L. Dagalea, CPA", title: "Accountant II", note: "OIC Head, Accounting Office" },
                { name: "Ms. Cherylin E. Pido", title: "Administrative Officer IV", note: "OIC Head, Budget Office" },
                { name: "Dr. Ruthchel C. Zeta", title: "Medical Officer III", note: "Head, Medical Services Office" },
                { name: "Mr. Jan-Carlo L. De Veyra", title: "Dormitory Manager" }
            ]},
            { office: "Office of the Vice President for Student Development", people: [
                { name: "Dr. Joyce M. Magtolis", title: "Vice President for Student Development" },
                { name: "Prof. Melba M. Navarra", title: "Director, Guidance Office" },
                { name: "Prof. Glenn Rey A. Estrada", title: "Director, Sports Development Office" },
                { name: "Prof. Ariel G. Salarda", title: "Director, Student Organizations and Activities Office" },
                { name: "Prof. Liza T. Bacierra", title: "Head, Scholarship and Financial Assistance Office" },
                { name: "Mr. Mark Kim I. Herbon", title: "Adviser, Supreme Student Council" }
            ]},
            { office: "Office of the Vice President for Research, Innovation, Extension, and Internationalization", people: [
                { name: "Dr. Jonas P. Villas", title: "Vice President for Research, Innovation, Extension, and Internationalization" },
                { name: "Dr. Myra A. Abayon", title: "Executive Director, Office of Research and Innovation" },
                { name: "Dr. Michael Dell A. Tuazon", title: "Director, Center for Teacher Education Research, Technology, and Innovation (CTERTI)" },
                { name: "Prof. Jufran C. Agustin", title: "Journal Manager, Journal of Education and Society (JES)" },
                { name: "Dr. Lowell A. Quisumbing", title: "Director, Community Extension Services Office" },
                { name: "Prof. Christian G. Abalos", title: "Director, Research Ethics Office" },
                { name: "Dr. Myra Grace F. Loayon", title: "Director, Knowledge Creation and Innovation Center (KCIC)" },
                { name: "Dr. Orlando P. Vinculado, Jr.", title: "Director, Center for International Studies, Collaborations, Mobility, and University Rankings" }
            ]},
            { office: "Office of the Vice President for Academic Services", people: [
                { name: "Dr. Lina G. Fabian", title: "Vice President for Academic Services" },
                { name: "Dr. Nelson D. Bernardo", title: "Director, Curriculum Development Office" },
                { name: "Dr. Marife N. Daga", title: "Director, Center for Continuing Education and Lifelong Learning (C-CELL)" },
                { name: "Dr. Perlita M. Vivero", title: "Director, Expanded Tertiary Education Equivalency and Accreditation Program (ETEEAP)" },
                { name: "Dr. Justina T. Lantajo", title: "Director, Quality Management System for Academic Services" },
                { name: "Dr. Rowena N. Ariaso", title: "Director, Sentro ng Wika at Kultura" },
                { name: "Prof. Ryan G. Destura", title: "Director, Admissions Office" },
                { name: "Dr. Gay A. Pinote", title: "Registrar III", note: "Head, Registrar's Office" },
                { name: "Ms. Luzviminda F. Alcober", title: "College Librarian I", note: "OIC Head, Learning Resource Center" }
            ]}
        ],
        academic: [
            { office: "College Deans and Campus Directors", people: [
                { name: "Dr. Billy A. Danday", title: "Dean, College of Education" },
                { name: "Dr. Rommel L. Verecio", title: "Dean, College of Arts and Sciences" },
                { name: "Dr. Evangeline V. Sanchez", title: "Dean, College of Management and Entrepreneurship" },
                { name: "Dr. Cleofe L. Lajara", title: "Dean, Graduate School" },
                { name: "Dr. Victoria Y. Naboya", title: "Associate Dean, College of Education" },
                { name: "Dr. Las Johansen B. Caluza", title: "Associate Dean, College of Arts and Sciences" },
                { name: "Prof. Emily Jill T. Nival", title: "Associate Dean, College of Management and Entrepreneurship" },
                { name: "Dr. Leo Reaywen M. Tugonon", title: "Associate Dean, Graduate School" },
                { name: "Dr. Rulf J. Alcober", title: "Director, San Isidro Campus" }
            ]},
            { office: "Unit Heads and Supervisors", people: [
                { name: "Prof. Lorena M. Ripalda", title: "Supervisor, Integrated Laboratory School (ILS)" },
                { name: "Dr. Maria Lourdes G. Tan", title: "Unit Head, Professional Education" },
                { name: "Prof. Cristina N. Estolano", title: "Unit Head, BEED, BSNED, and BTLED" },
                { name: "Prof. Ronald E. Mocorro", title: "Unit Head, Mathematics Unit" },
                { name: "Dr. Jose G. Enrile", title: "Unit Head, Filipino Unit" },
                { name: "Dr. Maricar C. Tegero", title: "Unit Head, BPED Unit" },
                { name: "Prof. Charie Ann C. Padullo", title: "Unit Head, Social Science Unit" },
                { name: "Mr. Marc Y. Llarenas", title: "Unit Head, Tourism and Hospitality Management Unit" },
                { name: "Dr. Myrna O. Piedad", title: "Unit Head, Entrepreneurship Unit" },
                { name: "Prof. Eva L. Rosal", title: "Unit Head, BA Comm Unit" },
                { name: "Ms. Genevieve T. Bactasa", title: "Unit Head, English Unit" },
                { name: "Prof. Lilibeth B. Fallorina", title: "Unit Head, Social Work Unit" },
                { name: "Ms. Mary Ann S. Dalan", title: "Unit Head, BLIS Unit" },
                { name: "Dr. Micheline G. Apolinar", title: "Unit Head, BSIT and Computer Education Unit" },
                { name: "Prof. Facundo Rey M. Ladiao", title: "Unit Head, Science Unit" },
                { name: "Dr. Victor P. Bactol", title: "Unit Head, BAPOS Unit" },
                { name: "Ms. Marisol C. Abanilla", title: "Unit Head, BMME" }
            ]}
        ]
    };

    /* ---------- Directory: tabs, office filter, search. Everyone gets the LNU seal until photos arrive. ---------- */
    var LOGO = @json(asset('images/lnulogo.png'));
    var PER_OFFICE = 6;
    var state = { tab: 'administrative', office: 'all', q: '', expanded: {} };
    var officesEl = document.getElementById('org-offices');
    var peopleEl = document.getElementById('org-people');
    var searchEl = document.getElementById('org-search');

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
    function norm(s) { return String(s || '').toLowerCase(); }
    function matches(p, office) {
        if (!state.q) return true;
        var hay = norm(p.name + ' ' + p.title + ' ' + (p.note || '') + ' ' + office);
        return state.q.split(/\s+/).every(function (w) { return hay.indexOf(w) !== -1; });
    }
    function total(key) { return orgChartData[key].reduce(function (n, o) { return n + o.people.length; }, 0); }

    document.querySelectorAll('[data-orgcount]').forEach(function (el) { el.textContent = total(el.getAttribute('data-orgcount')); });

    function renderOffices() {
        var groups = orgChartData[state.tab];
        var btn = function (key, label, count) {
            var on = state.office === key;
            return '<button type="button" data-office="' + esc(key) + '" aria-pressed="' + on + '" class="org-office flex shrink-0 items-start justify-between gap-3 rounded-lg px-3 py-2 text-left text-[13.5px] leading-snug transition lg:w-full ' +
                (on ? 'bg-[#e2e9f7] font-medium text-[#1e4090]' : 'text-[#4a5568] hover:bg-[#f5f4f2]') + '">' +
                '<span class="whitespace-nowrap lg:whitespace-normal">' + esc(label) + '</span><span class="tabular-nums text-xs ' + (on ? 'text-[#2a57b4]' : 'text-[#9a978f]') + '">' + count + '</span></button>';
        };
        officesEl.innerHTML = btn('all', 'All offices', total(state.tab)) +
            groups.map(function (g, i) { return btn(String(i), g.office.replace(/^Office of the /, ''), g.people.length); }).join('');
    }

    function renderPeople() {
        var groups = orgChartData[state.tab];
        var html = '';
        var shown = 0;
        groups.forEach(function (g, i) {
            if (state.office !== 'all' && state.office !== String(i)) return;
            var list = g.people.filter(function (p) { return matches(p, g.office); });
            if (!list.length) return;
            shown += list.length;
            var open = state.q || state.office !== 'all' || state.expanded[state.tab + i];
            var visible = open ? list : list.slice(0, PER_OFFICE);
            html += '<section class="mb-8 last:mb-0">' +
                '<div class="mb-3 flex items-baseline justify-between gap-3 border-b border-[#e7e5e1] pb-2">' +
                    '<h3 class="text-[15px] font-semibold text-[#0d253d]">' + esc(g.office) + '</h3>' +
                    '<span class="shrink-0 text-xs tabular-nums text-[#9a978f]">' + list.length + (list.length === 1 ? ' person' : ' people') + '</span>' +
                '</div>' +
                '<ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">' +
                visible.map(function (p) {
                    return '<li class="flex items-start gap-3 rounded-lg border border-[#ecebe7] bg-white p-3 transition hover:border-[#d4d1cb]">' +
                        '<img src="' + LOGO + '" alt="" class="h-10 w-10 shrink-0 rounded-full bg-white object-contain ring-1 ring-[#e7e5e1]">' +
                        '<div class="min-w-0">' +
                            '<p class="text-[13.5px] font-semibold leading-snug text-[#0d253d]">' + esc(p.name) + '</p>' +
                            '<p class="mt-0.5 text-[12.5px] leading-snug text-[#1e4090]">' + esc(p.title) + '</p>' +
                            (p.note ? '<p class="mt-1 text-xs leading-snug text-[#66707f]">' + esc(p.note) + '</p>' : '') +
                        '</div></li>';
                }).join('') + '</ul>' +
                (!open && list.length > PER_OFFICE
                    ? '<button type="button" data-expand="' + i + '" class="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-[#2a57b4] hover:underline underline-offset-4">Show all ' + list.length + ' <i class="bx bx-chevron-down"></i></button>'
                    : '') +
                '</section>';
        });
        peopleEl.innerHTML = shown ? html :
            '<div class="py-16 text-center"><i class="bx bx-search-alt text-3xl text-[#d4d1cb]"></i><p class="mt-2 text-sm text-[#66707f]">No one matches “' + esc(state.q) + '”. Try a surname or an office.</p></div>';
    }

    function render() { renderOffices(); renderPeople(); }

    document.querySelectorAll('[data-orgtab]').forEach(function (b) {
        b.addEventListener('click', function () {
            state.tab = b.getAttribute('data-orgtab'); state.office = 'all';
            document.querySelectorAll('[data-orgtab]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
            render();
        });
    });
    officesEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-office]'); if (!b) return;
        state.office = b.getAttribute('data-office'); render();
        if (window.innerWidth >= 1024) peopleEl.scrollTop = 0;
    });
    peopleEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-expand]'); if (!b) return;
        state.expanded[state.tab + b.getAttribute('data-expand')] = true; renderPeople();
    });
    searchEl.addEventListener('input', function () { state.q = norm(searchEl.value.trim()); renderPeople(); });
    render();

});
</script>
@endpush

@push('head')
<style>

    /* A rubber stamp on the routing slip */
    .dm-rubber {
        display: inline-block; padding: .3rem .6rem; border: 2px solid var(--c); border-radius: 6px;
        color: var(--c); font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
        opacity: .88; mix-blend-mode: multiply;
        -webkit-mask-image: radial-gradient(circle at 30% 40%, #000 60%, rgba(0,0,0,.75) 100%);
                mask-image: radial-gradient(circle at 30% 40%, #000 60%, rgba(0,0,0,.75) 100%);
    }

    .dm-rubber-sm { padding: .2rem .45rem; font-size: 10.5px; letter-spacing: .12em; }
    @media (max-width: 639px) {
        .dm-index-card { transform: none !important; padding: 1rem 1.1rem .9rem; }
        .dm-folder { padding-top: 1.9rem; }
        .dm-folder-tab { height: 2rem; font-size: 12px; padding: 0 .9rem; }
    }

    /* File folders: a tab on the top-left edge */
    .dm-folder { padding-top: 2.1rem; }
    .dm-folder-tab {
        position: absolute; top: 0; left: 0; display: inline-flex; align-items: center; height: 2.2rem;
        padding: 0 1.1rem 0 1rem; border-radius: 10px 14px 0 0; background: var(--tab);
        font-size: 13px; font-weight: 600; color: #273951;
    }

    /* Index cards: ruled, with a blue margin rule */
    .dm-index-card {
        margin: 0; padding: 1.25rem 1.4rem 1.1rem 1.4rem; border-radius: 4px; background-color: #fff;
        border-top: 3px solid #2a57b4;
        box-shadow: 0 20px 36px -24px rgba(13,37,61,.4), 0 1px 2px rgba(13,37,61,.06);
        transition: transform .3s cubic-bezier(.2,.8,.2,1);
    }
    .dm-index-card:hover { transform: rotate(0deg) translateY(-3px) !important; }

    /* Directory tabs */
    .org-seg { color: #4a5568; }
    .org-seg[aria-selected="true"] { background: #fff; color: #0d253d; box-shadow: 0 1px 2px rgba(13,37,61,.08); }

    /* Team: infinite horizontal scroll */
    .marquee { overflow: hidden; padding: .75rem 0 1.25rem;
        -webkit-mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent);
                mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent); }
    .marquee-track { display: flex; width: max-content; animation: marquee-scroll 40s linear infinite; }
    .marquee-group { display: flex; gap: 1.25rem; padding-right: 1.25rem; flex-shrink: 0; }
    .marquee:hover .marquee-track, .marquee:focus-within .marquee-track { animation-play-state: paused; }
    @keyframes marquee-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    @media (prefers-reduced-motion: reduce) {
        .marquee { overflow-x: auto; -webkit-mask-image: none; mask-image: none; }
        .marquee-track { animation: none; }
        .marquee-group[aria-hidden="true"] { display: none; }
    }
</style>
@endpush
