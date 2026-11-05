@php
    // Someone already signed in (in this browser, e.g. another tab) gets a way back in, not "Sign in".
    $signedIn = auth()->user();
    $appHome = match ($signedIn?->role?->role_name) {
        'Admin' => url('/admin/dashboard'),
        'Signatory' => route('signatory.home'),
        null => null,
        default => $signedIn->account_status === 'active' ? url('/student/home') : url('/verify'),
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'DocuMate')</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600|montserrat:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- LOGO FONT (matches the app's sidebar logo text) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gveret+Levin&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #003399;
            --primary-light: #2A57B4;
            --accent: #FFBF00;
        }

        html { scroll-behavior: smooth; scroll-padding-top: 6rem; }

        /* ---------- Scroll reveal ---------- */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .7s cubic-bezier(.16,.84,.44,1), transform .7s cubic-bezier(.16,.84,.44,1);
        }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }
        .reveal-delay-1.is-visible { transition-delay: .08s; }
        .reveal-delay-2.is-visible { transition-delay: .16s; }
        .reveal-delay-3.is-visible { transition-delay: .24s; }
        .reveal-delay-4.is-visible { transition-delay: .32s; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            .float-slow { animation: none !important; }
            html { scroll-behavior: auto; }
        }

        /* ---------- Header shadow on scroll ---------- */
        header.scrolled { box-shadow: 0 1px 0 #e7e5e1, 0 8px 24px -12px rgba(13,37,61,.12); }
        ::selection { background: #e2e9f7; color: #0d253d; }

        /* Paper: faint ruled lines with a margin rule, like a ledger (home, FAQs, verify) */
        .dm-ledger {
            background-color: #fafaf9;
            background-image:
                linear-gradient(to right, transparent 0, transparent calc(3.5rem - 1px), rgba(42,87,180,.10) calc(3.5rem - 1px), rgba(42,87,180,.10) 3.5rem, transparent 3.5rem),
                repeating-linear-gradient(to bottom, transparent 0, transparent 31px, #eeece8 31px, #eeece8 32px);
        }
        @media (max-width: 639px) { .dm-ledger { background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 31px, #f0eeea 31px, #f0eeea 32px); } }

        /* ---------- Hero float animation ---------- */
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .float-slow { animation: floatSlow 5s ease-in-out infinite; }

        /* ---------- Modal ---------- */
        .modal-overlay { transition: opacity .25s ease; }
        .modal-panel { transition: opacity .25s ease, transform .25s ease; }

        /* ---------- Legal content ---------- */
        .legal-content h4 { font-weight: 700; color: #182c44; margin-top: 1.25rem; margin-bottom: .4rem; }
        .legal-content h4:first-child { margin-top: 0; }
        .legal-content p { margin-bottom: .6rem; }
        .legal-content ul { list-style: disc; padding-left: 1.25rem; margin-bottom: .6rem; }
        .legal-content li { margin-bottom: .25rem; }
        .legal-content .legal-title { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: #2A57B4; font-weight: 700; }

        /* ---------- Org chart accordion ---------- */
        details.office-group summary::-webkit-details-marker { display: none; }
        details.office-group[open] .chevron { transform: rotate(180deg); }
        details.office-group summary { list-style: none; cursor: pointer; }

        /* ---------- FAQ accordion ---------- */
        details.faq-item summary::-webkit-details-marker { display: none; }
        details.faq-item summary { list-style: none; cursor: pointer; }
        details.faq-item[open] .faq-chevron { transform: rotate(180deg); }

        /* ---------- Tab underline animation ---------- */
        .org-tab { position: relative; }
        .org-tab::after {
            content: '';
            position: absolute;
            left: 0; bottom: -2px;
            width: 0%; height: 3px;
            background: var(--accent);
            transition: width .3s ease;
        }
        .org-tab.active::after { width: 100%; }

        /* ---------- Full-height header link hover & active styles ---------- */
        .nav-link {
            position: relative;
            display: flex;
            align-items: center;
            height: 100%;
            padding: 0 1rem;
            border-radius: 0 !important; /* Explicitly forces sharp/square corners */
        }
        
        /* Yellow bottom light bar on hover */
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0%;
            height: 3px;
            background: var(--accent);
            transition: width .25s ease;
            z-index: 2;
        }
        .nav-link:hover::before {
            width: 100%;
        }

        /* Underline/Highlight line for active state */
        .nav-link::after {
            content: '';
            position: absolute;
            left: 0; 
            bottom: 0; 
            width: 0; 
            height: 3px;
            background: #2A57B4;
            transition: width .3s ease;
            z-index: 1;
        }
        .nav-link.active { color: #2A57B4; }
        .nav-link.active::after { width: 100%; }
    </style>

    @stack('head')
</head>

<body class="font-sans bg-white text-gray-800 scroll-smooth">

    @php
        // [label, href, section id used by the scroll-spy]
        $navLinks = [
            ['How it works', url('/') . '#how',      'how'],
            ['Features',     url('/') . '#features', 'features'],
            ['Organization', url('/') . '#orgchart', 'orgchart'],
            ['Location',     url('/') . '#location', 'location'],
            ['FAQs',         route('faqs'),          'faqs'],
            ['Team',         url('/') . '#creators', 'creators'],
        ];
    @endphp

    <!-- ================= HEADER ================= -->
    <header id="site-header" class="fixed z-50 w-full bg-white/90 backdrop-blur-md transition-shadow duration-300">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:h-20 sm:px-8">

            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2 sm:gap-2.5" aria-label="DocuMate home">
                <img src="{{ asset('images/DocumateLogo.png') }}" alt="" class="size-9 object-contain sm:size-11">
                <span class="text-[1.65rem] leading-none text-[#2A57B4] sm:text-[2rem]" style="font-family: 'Gveret Levin', cursive;">DocuMate</span>
            </a>

            <!-- Nav container matches the full height of the header row -->
            <nav class="hidden items-center h-full gap-0 whitespace-nowrap text-[15px] font-medium xl:flex" aria-label="Main">
                @foreach ($navLinks as [$label, $href, $key])
                    <a href="{{ $href }}" data-spy="{{ $key }}"
                        @if (request()->routeIs('faqs') && $key === 'faqs') aria-current="true" @endif
                        class="nav-link rounded-none text-[#4a5568] transition hover:text-[#0d253d] hover:bg-[#edf2f7] {{ request()->routeIs('faqs') && $key === 'faqs' ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden shrink-0 items-center gap-2 whitespace-nowrap xl:flex">
                <a href="{{ route('document.lookup') }}" class="inline-flex h-10 items-center gap-1.5 rounded-lg px-3 text-[15px] font-medium text-[#273951] transition hover:bg-[#f5f4f2]">
                    <i class="bx bx-shield-quarter text-lg text-[#2A57B4]" aria-hidden="true"></i> Verify<span class="hidden 2xl:inline">&nbsp;a document</span>
                </a>
                @if ($appHome)
                    <a href="{{ $appHome }}" class="inline-flex h-10 items-center rounded-lg bg-[#2A57B4] px-4 text-[15px] font-medium text-white shadow-sm transition hover:bg-[#24499a]">Go to DocuMate</a>
                @else
                <a href="{{ route('login') }}" class="inline-flex h-10 items-center rounded-lg bg-[#2A57B4] px-4 text-[15px] font-medium text-white shadow-sm transition hover:bg-[#24499a]">Sign in</a>
                @endif
            </div>

            <div class="flex items-center gap-2 xl:hidden">
                @if ($appHome)
                    <a href="{{ $appHome }}" class="inline-flex h-9 items-center rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white">Go to DocuMate</a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white">Sign in</a>
                @endif
                <button id="mobile-menu-btn" class="grid h-9 w-9 place-items-center rounded-lg text-[#273951] hover:bg-[#f5f4f2]" aria-label="Open menu" aria-controls="mobile-menu" aria-expanded="false">
                    <i class="bx bx-menu text-2xl" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Mobile menu panel -->
        <div id="mobile-menu" class="hidden border-t border-[#e7e5e1] bg-white px-4 pb-5 pt-2 sm:px-8 xl:hidden">
            <nav class="flex flex-col" aria-label="Main">
                @foreach ($navLinks as [$label, $href, $key])
                    <a href="{{ $href }}" data-spy="{{ $key }}"
                        class="flex items-center justify-between border-b border-[#f0eeea] py-3 text-base font-medium text-[#273951] hover:text-[#2A57B4] {{ request()->routeIs('faqs') && $key === 'faqs' ? 'text-[#2A57B4]' : '' }}">
                        {{ $label }} <i class="bx bx-chevron-right text-lg text-[#9a978f]" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <a href="{{ route('document.lookup') }}" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-[#d4d1cb] text-[15px] font-medium text-[#0d253d]">
                    <i class="bx bx-shield-quarter text-lg text-[#2A57B4]" aria-hidden="true"></i> Verify
                </a>
                <a href="{{ route('register') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-[#d4d1cb] text-[15px] font-medium text-[#0d253d]">Create account</a>
            </div>
        </div>
    </header>

    @yield('content')

    <!-- ================= FOOTER ================= -->
    <footer class="bg-[#0d253d] text-[#c3cedd]">
        <div class="mx-auto grid max-w-6xl gap-10 px-5 py-14 sm:px-8 md:grid-cols-[minmax(0,1.4fr)_repeat(2,minmax(0,1fr))]">
            <div>
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/DocumateLogo.png') }}" alt="" class="size-10 rounded-lg bg-white object-contain p-0.5">
                    <span class="text-[1.8rem] leading-none text-white" style="font-family: 'Gveret Levin', cursive;">DocuMate</span>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed">
                    The student transaction system of the Office of the Vice President for Student Development, Leyte Normal University, Tacloban City.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Get started</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @unless ($appHome)<li><a href="{{ route('login') }}" class="hover:text-white">Sign in</a></li>@endunless
                    <li><a href="{{ route('register') }}" class="hover:text-white">Create a student account</a></li>
                    <li><a href="{{ route('document.lookup') }}" class="hover:text-white">Verify a document</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Learn more</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('faqs') }}" class="hover:text-white">FAQs</a></li>
                    <li><button type="button" data-modal-open="privacy" class="hover:text-white">Privacy Policy</button></li>
                    <li><button type="button" data-modal-open="terms" class="hover:text-white">Terms &amp; Conditions</button></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-5 py-5 text-xs sm:flex-row sm:px-8">
                <p>© {{ date('Y') }} DocuMate. All rights reserved.</p>
                <p>Ecaldre et al., 2026</p>
            </div>
        </div>
    </footer>
    <div class="h-1 bg-[#FFBF00]" aria-hidden="true"></div>

    <!-- ================= MODALS ================= -->

    <x-legal-dialog name="privacy" mode="vanilla" />
    <x-legal-dialog name="terms" mode="vanilla" />

    <!-- ================= SHARED SCRIPTS ================= -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Header shadow on scroll ---------- */
        var header = document.getElementById('site-header');
        window.addEventListener('scroll', function () {
            header.classList.toggle('scrolled', window.scrollY > 12);
        });

        /* ---------- Mobile menu ---------- */
        var menuBtn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        menuBtn.addEventListener('click', function () {
            var open = menu.classList.toggle('hidden') === false;
            menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            menuBtn.querySelector('i').className = 'bx ' + (open ? 'bx-x' : 'bx-menu') + ' text-2xl';
        });
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                menu.classList.add('hidden');
                menuBtn.setAttribute('aria-expanded', 'false');
                menuBtn.querySelector('i').className = 'bx bx-menu text-2xl';
            });
        });
        /* ---------- Scroll-spy: highlight the nav link of the section in view ---------- */
        var spyLinks = document.querySelectorAll('[data-spy]');
        var spyIds = [];
        spyLinks.forEach(function (a) {
            var id = a.getAttribute('data-spy');
            if (spyIds.indexOf(id) === -1) spyIds.push(id);
        });
        var spySections = spyIds
            .map(function (id) { return document.getElementById(id); })
            .filter(Boolean);

        if (spySections.length) {
            var updateSpy = function () {
                var offset = header.offsetHeight + 40;
                var current = null;

                spySections.forEach(function (s) {
                    if (s.getBoundingClientRect().top <= offset) current = s.id;
                });

                if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
                    current = spySections[spySections.length - 1].id;
                }

                spyLinks.forEach(function (a) {
                    var on = a.getAttribute('data-spy') === current;
                    a.classList.toggle('active', on);
                    if (on) a.setAttribute('aria-current', 'true');
                    else a.removeAttribute('aria-current');
                });
            };

            window.addEventListener('scroll', updateSpy, { passive: true });
            window.addEventListener('resize', updateSpy);
            updateSpy();
        }

        /* ---------- Scroll reveal ---------- */
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });

        /* ---------- Modals ---------- */
        document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = document.getElementById('modal-' + btn.getAttribute('data-modal-open'));
                if (modal) modal.classList.remove('hidden');
            });
        });
        document.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                el.closest('.fixed').classList.add('hidden');
            });
        });

        /* ---------- "I Agree" checkbox gating ---------- */
        document.querySelectorAll('[data-agree-checkbox]').forEach(function (checkbox) {
            var key = checkbox.getAttribute('data-agree-checkbox');
            var agreeBtn = document.querySelector('[data-agree-button="' + key + '"]');
            if (!agreeBtn) return;

            checkbox.addEventListener('change', function () { agreeBtn.disabled = !checkbox.checked; });

            var openBtn = document.querySelector('[data-modal-open="' + key + '"]');
            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    checkbox.checked = false;
                    agreeBtn.disabled = true;
                });
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fixed:not(.hidden)').forEach(function (m) {
                    if (m.id && m.id.indexOf('modal-') === 0) m.classList.add('hidden');
                });
            }
        });
    });
    </script>

    @stack('scripts')
</body>
</html>