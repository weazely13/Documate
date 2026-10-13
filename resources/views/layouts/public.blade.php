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
        header.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,.06); backdrop-filter: blur(8px); }

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
        .legal-content h4 { font-weight: 700; color: #1f2937; margin-top: 1.25rem; margin-bottom: .4rem; }
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

        /* ---------- Custom scrollbars ---------- */
        .org-scroll::-webkit-scrollbar,
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .org-scroll::-webkit-scrollbar-thumb,
        .modal-scroll::-webkit-scrollbar-thumb { background: rgba(42,87,180,.3); border-radius: 10px; }
    </style>

    @stack('head')
</head>

<body class="font-sans bg-white text-gray-800 scroll-smooth">

    @php
        // Anchors use url('/') so they also work from the FAQs page.
        $navLinks = [
            ['About',        url('/') . '#about'],
            ['System',       url('/') . '#system'],
            ['Organization', url('/') . '#orgchart'],
            ['Location',     url('/') . '#location'],
            ['FAQs',         route('faqs')],
            ['Creators',     url('/') . '#creators'],
        ];
    @endphp

    <!-- ================= HEADER ================= -->
    <header id="site-header" class="bg-white/90 fixed w-full z-50 transition-shadow duration-300">
        <div class="max-w-7xl mx-auto px-8 py-4 flex justify-between items-center">

            <!-- Logo + Title -->
            <a href="{{ url('/') }}" class="flex items-center space-x-3">
                <img src="{{ asset('images/DocumateLogo.png') }}" alt="DocuMate Logo" class="size-16 object-contain">
                <span class="text-4xl font-bold tracking-wide text-[#2A57B4]" style="font-family: 'Gveret Levin', cursive;">
                    DocuMate
                </span>
            </a>

            <!-- Navigation -->
            <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold">
                @foreach ($navLinks as [$label, $href])
                    <a href="{{ $href }}" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">{{ $label }}</a>
                @endforeach
            </nav>

            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" class="md:hidden text-[#2A57B4]" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <!-- Mobile menu panel -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 px-8 py-4 flex flex-col gap-4 text-base font-semibold">
            @foreach ($navLinks as [$label, $href])
                <a href="{{ $href }}" class="text-gray-700 hover:text-[#2A57B4] transition">{{ $label }}</a>
            @endforeach
        </div>
    </header>

    @yield('content')

    <!-- ================= FOOTER ================= -->
    <footer class="bg-[#2A57B4] text-white py-10">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">

            <div class="text-center md:text-left">
                © {{ date('Y') }} DocuMate. All rights reserved.
            </div>

            <div class="flex flex-wrap justify-center md:justify-end items-center gap-4">

                <button type="button" data-modal-open="privacy" class="hover:underline hover:opacity-80">
                    Privacy Policy
                </button>

                <button type="button" data-modal-open="terms" class="hover:underline hover:opacity-80">
                    Terms &amp; Conditions
                </button>

                <span class="opacity-100">
                    Ecaldre et al, 2026
                </span>

            </div>

        </div>
    </footer>
    <div class="bg-[#FFBF00] py-1"></div>

    <!-- ================= MODALS ================= -->

    <!-- PRIVACY POLICY MODAL -->
    <div id="modal-privacy" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4 py-8">
        <div class="modal-overlay absolute inset-0 bg-black/50" data-modal-close></div>
        <div class="modal-panel relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                <div>
                    <p class="legal-title">VPSD DocuMate — Leyte Normal University</p>
                    <h3 class="text-2xl font-bold text-[#2A57B4] mt-1">Privacy Policy</h3>
                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                </div>
                <button type="button" data-modal-close aria-label="Close" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="modal-scroll px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600 legal-content">
                @include('partials.privacy-content')
            </div>

            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" data-agree-checkbox="privacy" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                    <span>I have read and agree to the Privacy Policy.</span>
                </label>
                <button type="button" data-modal-close data-agree-button="privacy" disabled
                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-full font-semibold transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                    I Agree
                </button>
            </div>
        </div>
    </div>

    <!-- TERMS & CONDITIONS MODAL -->
    <div id="modal-terms" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-4 py-8">
        <div class="modal-overlay absolute inset-0 bg-black/50" data-modal-close></div>
        <div class="modal-panel relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                <div>
                    <p class="legal-title">VPSD DocuMate — Leyte Normal University</p>
                    <h3 class="text-2xl font-bold text-[#2A57B4] mt-1">Terms &amp; Conditions</h3>
                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                </div>
                <button type="button" data-modal-close aria-label="Close" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="modal-scroll px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600 legal-content">
                @include('partials.terms-content')
            </div>

            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" data-agree-checkbox="terms" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                    <span>I have read and agree to the Terms and Conditions.</span>
                </label>
                <button type="button" data-modal-close data-agree-button="terms" disabled
                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-full font-semibold transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                    I Agree
                </button>
            </div>
        </div>
    </div>

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
        menuBtn.addEventListener('click', function () { menu.classList.toggle('hidden'); });
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { menu.classList.add('hidden'); });
        });

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