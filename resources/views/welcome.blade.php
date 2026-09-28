<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>DocuMate</title>
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

        html { scroll-behavior: smooth; }

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

        /* ---------- Header shadow on scroll ---------- */
        header.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,.06); backdrop-filter: blur(8px); }

        /* ---------- Hero float animation ---------- */
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .float-slow { animation: floatSlow 5s ease-in-out infinite; }

        /* ---------- Placeholder blocks ---------- */
        .photo-placeholder {
            background-image:
                repeating-linear-gradient(45deg, rgba(42,87,180,.06) 0, rgba(42,87,180,.06) 10px, transparent 10px, transparent 20px);
        }

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

        /* ---------- Custom scrollbar for org chart panel ---------- */
        .org-scroll::-webkit-scrollbar { width: 6px; }
        .org-scroll::-webkit-scrollbar-thumb { background: rgba(42,87,180,.3); border-radius: 10px; }

        /* ---------- Custom scrollbar for modal body ---------- */
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .modal-scroll::-webkit-scrollbar-thumb { background: rgba(42,87,180,.3); border-radius: 10px; }
    </style>
</head>

<body class="font-sans bg-white text-gray-800 scroll-smooth">

    <!-- ================= HEADER ================= -->
    <header id="site-header" class="bg-white/90 fixed w-full z-50 transition-shadow duration-300">
        <div class="max-w-7xl mx-auto px-8 py-4 flex justify-between items-center">

            <!-- Logo + Title -->
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/DocumateLogo.png') }}" alt="DocuMate Logo" class="size-16 object-contain">

                <h1 class="text-4xl font-bold tracking-wide text-[#2A57B4]" style="font-family: 'Gveret Levin', cursive;">
                    DocuMate
                </h1>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold">

                <a href="#about" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">About</a>
                <a href="#creators" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">Creators</a>
                <a href="#orgchart" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">Organization</a>
                <a href="#location" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">Location</a>
                <a href="#faqs" class="text-gray-700 text-lg hover:text-[#2A57B4] transition">FAQs</a>

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
            <a href="#about" class="text-gray-700 hover:text-[#2A57B4] transition">About</a>
            <a href="#creators" class="text-gray-700 hover:text-[#2A57B4] transition">Creators</a>
            <a href="#orgchart" class="text-gray-700 hover:text-[#2A57B4] transition">Organization</a>
            <a href="#location" class="text-gray-700 hover:text-[#2A57B4] transition">Location</a>
            <a href="#faqs" class="text-gray-700 hover:text-[#2A57B4] transition">FAQs</a>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="min-h-screen flex items-center bg-white pt-28 overflow-hidden">

        <div class="w-full grid md:grid-cols-2 items-center">

            <!-- LEFT SIDE -->
            <div class="px-8 md:px-16 reveal">

                <h1 class="text-6xl md:text-8xl font-semibold tracking-wide text-[#2A57B4] leading-tight" style="font-family: 'Montserrat', sans-serif;">
                    Tap,
                    select,
                    access.
                </h1>

                <p class="text-lg text-gray-600 my-8 leading-relaxed max-w-lg">
                    A smarter way to manage student transactions, automate workflows,
                    and ensure transparent multi-office approvals.
                </p>

                <div class="flex flex-col sm:mt-20 mb-20 gap-4 items-start">

                    <!-- Login Button -->
                    <a href="{{ route('login') }}"
                    class="px-20 py-3 bg-[#2A57B4] text-white rounded-full font-bold hover:opacity-90 hover:scale-[1.03] active:scale-95 transition-all shadow-lg shadow-[#2A57B4]/20">
                        Sign in
                    </a>

                    <!-- Sign Up Text Link -->
                    <a href="{{ route('register') }}"
                    class="text-sm text-gray-600 hover:text-[#2A57B4] transition">
                        Don't have an account yet? Sign up here.
                    </a>

                </div>

            </div>

            <!-- RIGHT SIDE -->
            <div class="h-full flex items-end reveal reveal-delay-1">

                <img src="{{ asset('images/coverboy.png') }}"
                    alt="DocuMate Cover"
                    class="w-full h-full object-cover float-slow">

            </div>

        </div>

    </section>

    <!-- ================= ABOUT ================= -->
    <section id="about" class="py-24 bg-[#2A57B4] text-white overflow-hidden">

        <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-6 items-center">

            <!-- LEFT SIDE -->
            <!-- Centered on mobile per feedback; right-aligned again at md+ -->
            <div class="text-center md:text-right md:pr-4 reveal">
                <h1 class="text-6xl md:text-9xl font-bold leading-none tracking-tight"
                    style="font-family: 'Montserrat', sans-serif;">
                    DOCU<br>
                    MATE
                </h1>
            </div>

            <!-- RIGHT SIDE -->
            <div class="text-left md:pl-4 reveal reveal-delay-1">

                <h2 class="text-3xl font-bold mb-4">ABOUT</h2>

                <p class="text-white/90 leading-relaxed text-lg">
                    DocuMate is a centralized student transaction system designed to enhance
                    efficiency, transparency, and accountability. It integrates structured
                    workflows, digital records management, and role-based access control
                    to streamline university operations.
                </p>

            </div>

        </div>

    </section>

    <!-- ================= CREATORS ================= -->
    <section id="creators" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-6 text-center">

            <h2 class="text-3xl font-medium text-[#2A57B4] reveal">MEET THE TEAM</h2>
            <h1 class="text-6xl font-semibold text-[#2A57B4] mb-6 reveal">THE CREATORS</h1>
            <h2 class="text-centered text-2x1 text-gray-600 px-4 md:px-28 font-regular mb-10 reveal">
                A dedicated team committed to building an efficient, innovative, and user-centered student transaction system.
            </h2>

            <!-- GROUP PHOTO -->
            <!-- No card treatment: no rounded corners, no shadow, no crop.
                 h-auto + object-contain shows the full photo as-is. -->
            <div class="w-full mb-14 reveal">
                <img src="{{ asset('images/coverpeople.png') }}"
                    alt="DocuMate Team"
                    class="w-full h-auto object-contain">
            </div>

            <!-- 2x2 GRID -->
            <div class="grid md:grid-cols-2 gap-10">

                <!-- CARD 1 -->
                <div class="bg-white px-8 pt-8 pb-4 rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300
                            text-center flex flex-col items-center min-h-[480px] reveal">

                    {{-- TODO: replace initials avatar with images/pfp1.png once ready --}}
                    <div class="w-44 h-44 rounded-full mb-6 flex items-center justify-center text-4xl font-bold text-[#2A57B4] bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border-2 border-dashed border-[#2A57B4]/30">
                        RE
                    </div>

                    <h3 class="font-semibold text-2xl">Rujen Andrea Ecaldre</h3>
                    <p class="text-sm text-[#2A57B4] font-medium mb-3">Project Leader</p>

                    <div class="flex gap-4 mb-5">
                        <a href="https://facebook.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2v-2.9h2.2V9.4c0-2.2 1.3-3.4 3.3-3.4.96 0 2 .17 2 .17v2.2h-1.1c-1.1 0-1.4.68-1.4 1.38v1.66h2.4l-.38 2.9h-2.02v7A10 10 0 0 0 22 12z"/>
                            </svg>
                        </a>
                        <a href="https://github.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 .5C5.73.5.98 5.26.98 11.53c0 4.87 3.16 9 7.55 10.46.55.1.75-.24.75-.54v-2.02c-3.07.67-3.72-1.48-3.72-1.48-.5-1.27-1.23-1.6-1.23-1.6-1-.7.08-.69.08-.69 1.1.08 1.68 1.13 1.68 1.13.98 1.68 2.56 1.2 3.18.92.1-.7.38-1.2.7-1.48-2.45-.28-5.03-1.22-5.03-5.42 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .92-.29 3.02 1.13a10.5 10.5 0 0 1 5.5 0c2.1-1.42 3.02-1.13 3.02-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.21-2.58 5.14-5.04 5.41.39.34.74 1.01.74 2.04v3.02c0 .3.2.65.76.54 4.38-1.46 7.54-5.6 7.54-10.46C23.02 5.26 18.27.5 12 .5z"/>
                            </svg>
                        </a>
                    </div>

                    <p class="text-sm text-gray-600 mt-4 max-w-sm">
                        Leads the team by managing workflows, ensuring collaboration, and keeping the project aligned with its goals and deadlines.
                    </p>

                    <div class="mt-auto flex flex-wrap justify-center gap-2">
                        <span class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Research</span>
                        <span class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full">Management</span>
                        <span class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full">System Design</span>
                        <span class="px-3 py-1 text-xs bg-lime-100 text-lime-700 rounded-full">Database</span>
                    </div>
                </div>

                <!-- CARD 2 -->
                <div class="bg-white px-8 pt-8 pb-4 rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300
                            text-center flex flex-col items-center min-h-[480px] reveal reveal-delay-1">

                    <div class="w-44 h-44 rounded-full mb-6 flex items-center justify-center text-4xl font-bold text-[#2A57B4] bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border-2 border-dashed border-[#2A57B4]/30">
                        CV
                    </div>

                    <h3 class="font-semibold text-2xl">Clarisse Villa</h3>
                    <p class="text-sm text-[#2A57B4] font-medium mb-3">Technical Writer</p>

                    <div class="flex gap-4 mb-5">
                        <a href="https://facebook.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2v-2.9h2.2V9.4c0-2.2 1.3-3.4 3.3-3.4.96 0 2 .17 2 .17v2.2h-1.1c-1.1 0-1.4.68-1.4 1.38v1.66h2.4l-.38 2.9h-2.02v7A10 10 0 0 0 22 12z"/>
                            </svg>
                        </a>
                        <a href="https://github.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 .5C5.73.5.98 5.26.98 11.53c0 4.87 3.16 9 7.55 10.46.55.1.75-.24.75-.54v-2.02c-3.07.67-3.72-1.48-3.72-1.48-.5-1.27-1.23-1.6-1.23-1.6-1-.7.08-.69.08-.69 1.1.08 1.68 1.13 1.68 1.13.98 1.68 2.56 1.2 3.18.92.1-.7.38-1.2.7-1.48-2.45-.28-5.03-1.22-5.03-5.42 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .92-.29 3.02 1.13a10.5 10.5 0 0 1 5.5 0c2.1-1.42 3.02-1.13 3.02-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.21-2.58 5.14-5.04 5.41.39.34.74 1.01.74 2.04v3.02c0 .3.2.65.76.54 4.38-1.46 7.54-5.6 7.54-10.46C23.02 5.26 18.27.5 12 .5z"/>
                            </svg>
                        </a>
                    </div>

                    <p class="text-sm text-gray-600 mt-4 max-w-sm">
                        Produces structured technical documentation, ensuring all system components, workflows, and outputs are clearly communicated and well-documented.
                    </p>

                    <div class="mt-auto flex flex-wrap justify-center gap-2">
                        <span class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Research</span>
                        <span class="px-3 py-1 text-xs bg-cyan-100 text-cyan-700 rounded-full">Documentation</span>
                        <span class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full">System Analyst</span>
                        <span class="px-3 py-1 text-xs bg-amber-100 text-amber-700 rounded-full">Charts</span>
                    </div>
                </div>

                <!-- CARD 3 -->
                <div class="bg-white px-8 pt-8 pb-4 rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300
                            text-center flex flex-col items-center min-h-[480px] reveal">

                    <div class="w-44 h-44 rounded-full mb-6 flex items-center justify-center text-4xl font-bold text-[#2A57B4] bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border-2 border-dashed border-[#2A57B4]/30">
                        CM
                    </div>

                    <h3 class="font-semibold text-2xl">Clarence Magpatoc</h3>
                    <p class="text-sm text-[#2A57B4] font-medium mb-3">Research & Development</p>

                    <div class="flex gap-4 mb-5">
                        <a href="https://facebook.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2v-2.9h2.2V9.4c0-2.2 1.3-3.4 3.3-3.4.96 0 2 .17 2 .17v2.2h-1.1c-1.1 0-1.4.68-1.4 1.38v1.66h2.4l-.38 2.9h-2.02v7A10 10 0 0 0 22 12z"/>
                            </svg>
                        </a>
                        <a href="https://github.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 .5C5.73.5.98 5.26.98 11.53c0 4.87 3.16 9 7.55 10.46.55.1.75-.24.75-.54v-2.02c-3.07.67-3.72-1.48-3.72-1.48-.5-1.27-1.23-1.6-1.23-1.6-1-.7.08-.69.08-.69 1.1.08 1.68 1.13 1.68 1.13.98 1.68 2.56 1.2 3.18.92.1-.7.38-1.2.7-1.48-2.45-.28-5.03-1.22-5.03-5.42 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .92-.29 3.02 1.13a10.5 10.5 0 0 1 5.5 0c2.1-1.42 3.02-1.13 3.02-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.21-2.58 5.14-5.04 5.41.39.34.74 1.01.74 2.04v3.02c0 .3.2.65.76.54 4.38-1.46 7.54-5.6 7.54-10.46C23.02 5.26 18.27.5 12 .5z"/>
                            </svg>
                        </a>
                    </div>

                    <p class="text-sm text-gray-600 mt-4 max-w-sm">
                        Conducts research, analyzes system requirements, and develops solutions to improve functionality, efficiency, and innovation within the project.
                    </p>

                    <div class="mt-auto flex flex-wrap justify-center gap-2">
                        <span class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Research</span>
                        <span class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full">Laravel</span>
                        <span class="px-3 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">Tailwind</span>
                        <span class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full">System Analyst</span>
                    </div>
                </div>

                <!-- CARD 4 -->
                <div class="bg-white px-8 pt-8 pb-4 rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300
                            text-center flex flex-col items-center min-h-[480px] reveal reveal-delay-1">

                    <div class="w-44 h-44 rounded-full mb-6 flex items-center justify-center text-4xl font-bold text-[#2A57B4] bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border-2 border-dashed border-[#2A57B4]/30">
                        KM
                    </div>

                    <h3 class="font-semibold text-2xl">Khanley Mesa</h3>
                    <p class="text-sm text-[#2A57B4] font-medium mb-3">Design & Development</p>

                    <div class="flex gap-4 mb-5">
                        <a href="https://facebook.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2.2v-2.9h2.2V9.4c0-2.2 1.3-3.4 3.3-3.4.96 0 2 .17 2 .17v2.2h-1.1c-1.1 0-1.4.68-1.4 1.38v1.66h2.4l-.38 2.9h-2.02v7A10 10 0 0 0 22 12z"/>
                            </svg>
                        </a>
                        <a href="https://github.com" target="_blank" class="text-gray-500 hover:text-[#003399] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 .5C5.73.5.98 5.26.98 11.53c0 4.87 3.16 9 7.55 10.46.55.1.75-.24.75-.54v-2.02c-3.07.67-3.72-1.48-3.72-1.48-.5-1.27-1.23-1.6-1.23-1.6-1-.7.08-.69.08-.69 1.1.08 1.68 1.13 1.68 1.13.98 1.68 2.56 1.2 3.18.92.1-.7.38-1.2.7-1.48-2.45-.28-5.03-1.22-5.03-5.42 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .92-.29 3.02 1.13a10.5 10.5 0 0 1 5.5 0c2.1-1.42 3.02-1.13 3.02-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.21-2.58 5.14-5.04 5.41.39.34.74 1.01.74 2.04v3.02c0 .3.2.65.76.54 4.38-1.46 7.54-5.6 7.54-10.46C23.02 5.26 18.27.5 12 .5z"/>
                            </svg>
                        </a>
                    </div>

                    <p class="text-sm text-gray-600 mt-4 max-w-sm">
                        Handles system design and implementation, integrating front-end and back-end components to deliver a functional and user-friendly application.
                    </p>

                    <div class="mt-auto flex flex-wrap justify-center gap-2">
                        <span class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full">UI/UX</span>
                        <span class="px-3 py-1 text-xs bg-fuchsia-100 text-fuchsia-700 rounded-full">Backend</span>
                        <span class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full">Laravel</span>
                        <span class="px-3 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">Tailwind</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ================= ORGANIZATION CHART ================= -->
    <section id="orgchart" class="py-24 bg-gray-50">
        <div class="max-w-5xl mx-auto px-6">

            <h2 class="text-3xl font-medium text-[#2A57B4] text-center reveal">Leyte Normal University</h2>
            <h1 class="text-5xl md:text-6xl font-semibold text-[#2A57B4] mb-4 text-center reveal">Organization Chart</h1>
            <p class="text-center text-gray-600 max-w-2xl mx-auto mb-10 reveal">
                Directory of academic and administrative staffs, organized by office. Expand a section to view its members.
            </p>

            <!-- TABS -->
            <div class="flex justify-center gap-10 border-b border-gray-200 mb-8 reveal">
                <button data-orgtab="administrative" class="org-tab active pb-3 text-lg font-semibold text-[#2A57B4]">
                    Administrative Staffs
                </button>
                <button data-orgtab="academic" class="org-tab pb-3 text-lg font-semibold text-gray-500">
                    Academic Staffs
                </button>
            </div>

            <!-- PANELS -->
            <div id="orgchart-panels" class="org-scroll max-h-[640px] overflow-y-auto pr-2 reveal">
                <div data-orgpanel="administrative"></div>
                <div data-orgpanel="academic" class="hidden"></div>
            </div>

            <p class="text-center text-xs text-gray-400 mt-6">
                Source: Leyte Normal University — Academic &amp; Administrative Staffs directory.
            </p>
        </div>
    </section>

    <!-- ================= LOCATION ================= -->
    <section id="location" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-6">

            <h2 class="text-3xl font-medium text-[#2A57B4] text-center reveal">VISIT US</h2>
            <h1 class="text-5xl md:text-6xl font-semibold text-[#2A57B4] mb-10 text-center reveal">Campus Location</h1>

            <div class="grid md:grid-cols-3 gap-8 items-start">

                <!-- MAP -->
                <div class="md:col-span-2 rounded-2xl overflow-hidden shadow-lg border border-gray-200 reveal">
                    <iframe
                        src="https://maps.google.com/maps?q=P.+Paterno+St,+Brgy.+50,+Tacloban+City,+Leyte,+Philippines&output=embed"
                        class="w-full h-[420px]"
                        style="border:0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Campus location map">
                    </iframe>
                </div>

                <!-- DETAILS -->
                <div class="flex flex-col gap-6 reveal reveal-delay-1">

                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2A57B4] shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-800">Address</p>
                            <p class="text-sm text-gray-600">P. Paterno St., Brgy. 50, Tacloban City, Leyte, Philippines</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2A57B4] shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5c0 8.3 6.7 15 15 15h1a1 1 0 0 0 1-1v-2.7a1 1 0 0 0-.8-1L17 14a1 1 0 0 0-1.1.5l-.7 1.4a12 12 0 0 1-5.1-5.1L11.5 10a1 1 0 0 0 .5-1.1L10.7 5.8a1 1 0 0 0-1-.8H7a1 1 0 0 0-1 1Z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-800">Phone</p>
                            <p class="text-sm text-gray-600">+63 (53) 832 3205</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2A57B4] shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3V6Zm0 0 9 7 9-7"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-800">Email</p>
                            <p class="text-sm text-gray-600">info@lnu.edu.ph</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ================= FAQ ================= -->
    <section id="faqs" class="pt-24 pb-28 bg-white">
        <div class="max-w-4xl mx-auto px-6">

            <h1 class="text-left text-6xl font-semibold text-[#2A57B4] mb-5 reveal">
                Frequently asked questions...
            </h1>
            <h2 class="text-left text-xl font-regular text-grey mb-10 reveal">
                These are the commonly asked questions about the system, its features, and its security measures. Can't find your question here? Feel free to contact us for more information!
            </h2>

            <div class="space-y-12">

                <div class="flex flex-col gap-5 reveal">
                    <div class="flex justify-end">
                        <div class="bg-white border border-gray-200 text-[#2A57B4]
                                    font-semibold px-6 py-4 rounded-3xl rounded-tr-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            What is DocuMate?
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="bg-[#2A57B4] text-white font-semibold
                                    px-6 py-4 rounded-3xl rounded-tl-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            A digital system designed to streamline student transactions,
                            improve workflow efficiency, and ensure transparent approvals.
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-5 reveal">
                    <div class="flex justify-end">
                        <div class="bg-white border border-gray-200 text-[#2A57B4] font-semibold
                                    px-6 py-4 rounded-3xl rounded-tr-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            Who can use the system?
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="bg-[#2A57B4] text-white font-semibold
                                    px-6 py-4 rounded-3xl rounded-tl-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            Students, officers, and administrators within the institution
                            can access and utilize the system based on their roles.
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-5 reveal">
                    <div class="flex justify-end">
                        <div class="bg-white border border-gray-200 text-[#2A57B4] font-semibold
                                    px-6 py-4 rounded-3xl rounded-tr-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            Is my data secure?
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="bg-[#2A57B4] text-white font-semibold
                                    px-6 py-4 rounded-3xl rounded-tl-md
                                    max-w-md text-base shadow-md hover:shadow-lg transition">
                            Yes. The system implements role-based access control and
                            secure authentication to protect user data and ensure privacy.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

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
                    Terms & Conditions
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
                <button type="button" data-modal-close class="text-gray-400 hover:text-gray-700 transition shrink-0">
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
                    <h3 class="text-2xl font-bold text-[#2A57B4] mt-1">Terms & Conditions</h3>
                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                </div>
                <button type="button" data-modal-close class="text-gray-400 hover:text-gray-700 transition shrink-0">
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

    <!-- ================= SCRIPTS ================= -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Header shadow on scroll ---------- */
        var header = document.getElementById('site-header');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 12) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        });

        /* ---------- Mobile menu ---------- */
        var menuBtn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        menuBtn.addEventListener('click', function () {
            menu.classList.toggle('hidden');
        });
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { menu.classList.add('hidden'); });
        });

        /* ---------- Scroll reveal ---------- */
        var revealEls = document.querySelectorAll('.reveal');
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        revealEls.forEach(function (el) { observer.observe(el); });

        /* ---------- Modals ---------- */
        document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = 'modal-' + btn.getAttribute('data-modal-open');
                var modal = document.getElementById(id);
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

            checkbox.addEventListener('change', function () {
                agreeBtn.disabled = !checkbox.checked;
            });

            // Reset the checkbox/button each time this modal is opened
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

        /* ---------- Organization chart data ---------- */
        /* Source: Leyte Normal University — Academic & Administrative Staffs directory */
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

        function initials(name) {
            var stop = ['dr.', 'mr.', 'ms.', 'prof.', 'atty.', 'jr.', 'sr.', 'cpa'];
            var tokens = name.replace(/,/g, '').split(' ').filter(function (t) {
                return stop.indexOf(t.toLowerCase()) === -1;
            });
            var letters = tokens.filter(function (t) { return /^[A-Z]/.test(t); }).map(function (t) { return t[0]; });
            if (letters.length === 0) return '??';
            return letters[0] + (letters.length > 1 ? letters[letters.length - 1] : '');
        }

        function renderOffice(office) {
            var rows = office.people.map(function (p) {
                return '' +
                    '<div class="flex items-start gap-4 py-3 border-b border-gray-100 last:border-0">' +
                        '<div class="w-11 h-11 shrink-0 rounded-full bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border border-[#2A57B4]/20 flex items-center justify-center text-sm font-bold text-[#2A57B4]">' +
                            initials(p.name) +
                        '</div>' +
                        '<div>' +
                            '<p class="font-semibold text-gray-800 text-sm">' + p.name + '</p>' +
                            '<p class="text-xs text-[#2A57B4]">' + p.title + '</p>' +
                            (p.note ? '<p class="text-xs text-gray-500 italic mt-0.5">' + p.note + '</p>' : '') +
                        '</div>' +
                    '</div>';
            }).join('');

            return '' +
                '<details class="office-group bg-white rounded-xl border border-gray-200 mb-3 overflow-hidden">' +
                    '<summary class="flex items-center justify-between px-5 py-4">' +
                        '<span class="font-semibold text-gray-800">' + office.office + '</span>' +
                        '<span class="flex items-center gap-2 text-xs text-gray-400">' +
                            office.people.length + ' member' + (office.people.length === 1 ? '' : 's') +
                            '<svg class="chevron w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>' +
                        '</span>' +
                    '</summary>' +
                    '<div class="px-5 pb-4">' + rows + '</div>' +
                '</details>';
        }

        function renderPanel(key) {
            var panel = document.querySelector('[data-orgpanel="' + key + '"]');
            panel.innerHTML = orgChartData[key].map(renderOffice).join('');
        }

        renderPanel('administrative');
        renderPanel('academic');

        document.querySelectorAll('[data-orgtab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-orgtab');

                document.querySelectorAll('[data-orgtab]').forEach(function (b) {
                    b.classList.remove('active');
                    b.classList.remove('text-[#2A57B4]');
                    b.classList.add('text-gray-500');
                });
                btn.classList.add('active');
                btn.classList.remove('text-gray-500');
                btn.classList.add('text-[#2A57B4]');

                document.querySelectorAll('[data-orgpanel]').forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute('data-orgpanel') !== key);
                });
            });
        });

    });
    </script>

</body>
</html>