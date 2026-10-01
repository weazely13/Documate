@extends('layouts.public')

@section('title', 'DocuMate')

@section('content')

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

        <!-- Mobile order: ABOUT, DOCU MATE, paragraph. Desktop: title left, ABOUT + paragraph right. -->
        <div class="max-w-5xl mx-auto px-4 md:px-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 items-center text-center md:text-left">

            <h2 class="order-1 md:order-none md:col-start-2 md:row-start-1 md:self-end md:pl-4 text-3xl font-bold md:mb-0 reveal reveal-delay-1">
                ABOUT
            </h2>

            <div class="order-2 md:order-none md:col-start-1 md:row-start-1 md:row-span-2 md:self-center text-center md:text-right md:pr-4 reveal">
                <h1 class="text-[26vw] md:text-9xl font-bold leading-[0.9] tracking-tight"
                    style="font-family: 'Montserrat', sans-serif;">
                    DOCU<br>
                    MATE
                </h1>
            </div>

            <p class="order-3 md:order-none md:col-start-2 md:row-start-2 md:self-start md:pl-4 text-white/90 leading-relaxed text-lg reveal reveal-delay-1">
                DocuMate is a centralized student transaction system designed to enhance
                efficiency, transparency, and accountability. It integrates structured
                workflows, digital records management, and role-based access control
                to streamline university operations.
            </p>

        </div>

    </section>

    <!-- ================= ABOUT THE SYSTEM ================= -->
    <section id="system" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-6">

            <h1 class="text-3xl font-medium text-[#2A57B4] text-center  max-w-3xl mx-auto mb-12 leading-relaxed reveal">What it does?</h1>

            @php
                $features = [
                    ['Student accounts',        'Registration and account management for students.'],
                    ['Document submission',     'Submit and manage documents and requests online.'],
                    ['Transaction management',  'Track each transaction from request to completion.'],
                    ['Centralized records',     'Student records and files kept in one organized place.'],
                    ['Appointments and requests', 'Schedule and manage appointments and requests.'],
                    ['Clearance management',    'Handle clearance-related processes digitally.'],
                    ['Search and retrieval',    'Find the records you need quickly.'],
                    ['AI-assisted help',        'Document and office assistance for users.'],
                ];
            @endphp

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-14">
                @foreach ($features as $i => [$title, $desc])
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 border-t-4 border-t-[#2A57B4] shadow-sm reveal reveal-delay-{{ ($i % 4) + 1 }}">
                        <h3 class="font-semibold text-gray-800 mb-1">{{ $title }}</h3>
                        <p class="text-sm text-gray-600">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Who uses it -->
            <div class="text-center reveal">
                <p class="text-sm font-semibold text-[#2A57B4] mb-3">Who uses it</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <span class="px-4 py-1.5 text-sm bg-blue-100 text-blue-700 rounded-full">Students</span>
                    <span class="px-4 py-1.5 text-sm bg-purple-100 text-purple-700 rounded-full">VPSD Secretary / Admin</span>
                    <span class="px-4 py-1.5 text-sm bg-cyan-100 text-cyan-700 rounded-full">Authorized VPSD staff</span>
                    <span class="px-4 py-1.5 text-sm bg-amber-100 text-amber-700 rounded-full">Student organization officers</span>
                </div>
            </div>

            <p class="text-center text-gray-500 max-w-2xl mx-auto mt-12 text-sm reveal">
                DocuMate makes student documents easier to organize and retrieve, reducing the difficulty
                of traditional manual record keeping.
            </p>
        </div>
    </section>

    <!-- ================= ORGANIZATION CHART ================= -->
    <section id="orgchart" class="py-24 bg-gray-50">
        <div class="max-w-5xl mx-auto px-6">

            <h2 class="text-3xl font-medium text-[#2A57B4] text-center reveal">Leyte Normal University</h2>
            <h1 class="text-4xl md:text-6xl font-semibold text-[#2A57B4] mb-4 text-center reveal">Organization Chart</h1>
            <p class="text-center text-gray-600 max-w-2xl mx-auto mb-10 reveal">
                Directory of academic and administrative staffs, organized by office. Expand a section to view its members.
            </p>

            <!-- DROPDOWN TOGGLE -->
            <div class="text-center mb-6 reveal">
                <button id="orgchart-toggle" type="button" aria-expanded="false" aria-controls="orgchart-body"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#2A57B4] text-white rounded-full font-semibold hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-[#2A57B4]/20">
                    <span id="orgchart-toggle-label">Show organization chart</span>
                    <svg id="orgchart-toggle-icon" class="w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            <div id="orgchart-body" class="hidden">

                <!-- TABS -->
                <div class="flex justify-center gap-6 sm:gap-10 border-b border-gray-200 mb-6">
                    <button data-orgtab="administrative" class="org-tab active pb-3 text-base sm:text-lg font-semibold text-[#2A57B4]">
                        Administrative Staffs
                    </button>
                    <button data-orgtab="academic" class="org-tab pb-3 text-base sm:text-lg font-semibold text-gray-500">
                        Academic Staffs
                    </button>
                </div>

                <!-- PANELS -->
                <div id="orgchart-panels" class="org-scroll max-h-[70vh] md:max-h-[640px] overflow-y-auto pr-1 sm:pr-2">
                    <div data-orgpanel="administrative"></div>
                    <div data-orgpanel="academic" class="hidden"></div>
                </div>

            </div>

            <p class="text-center text-xs text-gray-400 mt-6">
                Source: Leyte Normal University — Academic &amp; Administrative Staffs directory.
            </p>
        </div>
    </section>

    <!-- ================= LOCATION ================= -->
    <section id="location" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-6">

            <h2 class="text-3xl font-medium text-[#2A57B4] text-center reveal">Visit us</h2>
            <h1 class="text-5xl md:text-6xl font-semibold text-[#2A57B4] mb-10 text-center reveal">Campus Location</h1>

            <div class="grid md:grid-cols-3 gap-8 items-start">

                <!-- MAP -->
                <div class="md:col-span-2 rounded-2xl overflow-hidden shadow-lg border border-gray-200 reveal">
                    <iframe
                        src="https://maps.google.com/maps?q=Leyte+Normal+University,+Tacloban+City,+Leyte,+Philippines&z=17&output=embed"
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

    <!-- ================= FAQ PREVIEW ================= -->
    <section id="faqs" class="pt-24 pb-28 bg-white">
        <div class="max-w-4xl mx-auto px-6">

            <h1 class="text-left text-6xl font-semibold text-[#2A57B4] mb-5 reveal">
                Frequently asked questions...
            </h1>
            <h2 class="text-left text-xl text-gray-600 mb-10 reveal">
                These are the commonly asked questions about the system, its features, and its security measures. Can't find your question here? Feel free to contact us for more information!
            </h2>

            @php
                // First three questions from config/faqs.php
                $preview = collect(config('faqs'))->flatten(1)->take(3);
            @endphp

            <div class="space-y-12">
                @foreach ($preview as $item)
                    <div class="flex flex-col gap-5 reveal">
                        <div class="flex justify-end">
                            <div class="bg-white border border-gray-200 text-[#2A57B4]
                                        font-semibold px-6 py-4 rounded-3xl rounded-tr-md
                                        max-w-md text-base shadow-md hover:shadow-lg transition">
                                {{ $item['q'] }}
                            </div>
                        </div>
                        <div class="flex justify-start">
                            <div class="bg-[#2A57B4] text-white font-semibold
                                        px-6 py-4 rounded-3xl rounded-tl-md
                                        max-w-md text-base shadow-md hover:shadow-lg transition">
                                {{ $item['a'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-14 reveal">
                <a href="{{ route('faqs') }}"
                   class="inline-block px-10 py-3 bg-[#2A57B4] text-white rounded-full font-bold hover:opacity-90 hover:scale-[1.03] active:scale-95 transition-all shadow-lg shadow-[#2A57B4]/20">
                    View all FAQs
                </a>
            </div>
        </div>
    </section>

    <!-- ================= CREATORS (moved to the bottom) ================= -->
    <section id="creators" class="py-24 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6 text-center">

            <h2 class="text-xl md:text-3xl font-medium text-[#2A57B4] reveal">MEET THE TEAM</h2>
            <h1 class="text-4xl md:text-6xl font-semibold text-[#2A57B4] mb-4 reveal">THE CREATORS</h1>
            <h2 class="text-base md:text-2xl text-gray-600 px-2 md:px-28 mb-8 reveal">
                A dedicated team committed to building an efficient, innovative, and user-centered student transaction system.
            </h2>

            <!-- GROUP PHOTO -->
            <div class="w-full mb-10 reveal">
                <img src="{{ asset('images/coverpeople.png') }}"
                    alt="DocuMate Team"
                    class="w-full h-auto object-contain">
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
            <div class="marquee">
                <div class="marquee-track">
                    @foreach ([0, 1] as $copy)
                        <div class="marquee-group" @if ($copy === 1) aria-hidden="true" @endif>
                            @foreach ($creators as $c)
                                <article class="creator-card w-56 sm:w-64 aspect-[5/7] shrink-0 rounded-2xl bg-white border border-[#2A57B4]/20 shadow-md p-4 sm:p-5 flex flex-col items-center text-center overflow-hidden">

                                    <!-- 1. Photo -->
                                    <img src="{{ asset($c['photo']) }}" alt="{{ $c['name'] }}"
                                         class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover ring-4 ring-[#2A57B4]/20 shrink-0">

                                    <!-- 2. Name and role -->
                                    <h3 class="mt-3 font-bold text-[#003399] text-sm sm:text-base leading-tight">{{ $c['name'] }}</h3>
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
                                            <span class="px-2 py-0.5 text-[10px] bg-[#2A57B4]/10 text-[#2A57B4] rounded-full">{{ $task }}</span>
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
                '<div class="flex items-start gap-3 sm:gap-4 py-3 border-b border-gray-100 last:border-0">' +
                    '<div class="w-9 h-9 sm:w-11 sm:h-11 shrink-0 rounded-full bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20 border border-[#2A57B4]/20 flex items-center justify-center text-xs sm:text-sm font-bold text-[#2A57B4]">' +
                        initials(p.name) +
                    '</div>' +
                    '<div class="min-w-0">' +
                        '<p class="font-semibold text-gray-800 text-sm break-words">' + p.name + '</p>' +
                        '<p class="text-xs text-[#2A57B4]">' + p.title + '</p>' +
                        (p.note ? '<p class="text-xs text-gray-500 italic mt-0.5">' + p.note + '</p>' : '') +
                    '</div>' +
                '</div>';
        }).join('');

        return '' +
            '<details class="office-group bg-white rounded-xl border border-gray-200 mb-3 overflow-hidden">' +
                '<summary class="flex items-start sm:items-center justify-between gap-3 px-4 sm:px-5 py-3 sm:py-4">' +
                    '<span class="font-semibold text-gray-800 text-sm sm:text-base">' + office.office + '</span>' +
                    '<span class="flex items-center gap-2 text-xs text-gray-400 shrink-0 whitespace-nowrap">' +
                        office.people.length + ' member' + (office.people.length === 1 ? '' : 's') +
                        '<svg class="chevron w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>' +
                    '</span>' +
                '</summary>' +
                '<div class="px-4 sm:px-5 pb-3 sm:pb-4">' + rows + '</div>' +
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
                b.classList.remove('active', 'text-[#2A57B4]');
                b.classList.add('text-gray-500');
            });
            btn.classList.add('active', 'text-[#2A57B4]');
            btn.classList.remove('text-gray-500');

            document.querySelectorAll('[data-orgpanel]').forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-orgpanel') !== key);
            });
        });
    });

    /* ---------- Organization chart dropdown ---------- */
    var orgToggle = document.getElementById('orgchart-toggle');
    var orgBody   = document.getElementById('orgchart-body');
    var orgLabel  = document.getElementById('orgchart-toggle-label');
    var orgIcon   = document.getElementById('orgchart-toggle-icon');

    function setOrgOpen(open) {
        orgBody.classList.toggle('hidden', !open);
        orgToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        orgLabel.textContent = open ? 'Hide organization chart' : 'Show organization chart';
        orgIcon.classList.toggle('rotate-180', open);
    }

    orgToggle.addEventListener('click', function () {
        setOrgOpen(orgBody.classList.contains('hidden'));
    });

    // Opening via the header "Organization" link or a #orgchart URL
    document.querySelectorAll('a[href$="#orgchart"]').forEach(function (a) {
        a.addEventListener('click', function () { setOrgOpen(true); });
    });
    if (window.location.hash === '#orgchart') setOrgOpen(true);
});
</script>
@endpush

@push('head')
<style>
    /* ---------- Creators: infinite horizontal scroll ---------- */
    .marquee {
        overflow: hidden;
        padding: .75rem 0 1.25rem;
        -webkit-mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent);
                mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent);
    }
    .marquee-track {
        display: flex;
        width: max-content;
        animation: marquee-scroll 35s linear infinite;
    }
    .marquee-group {
        display: flex;
        gap: 1.25rem;
        padding-right: 1.25rem;
        flex-shrink: 0;
    }
    .marquee:hover .marquee-track,
    .marquee:focus-within .marquee-track { animation-play-state: paused; }

    @keyframes marquee-scroll {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    /* Reduced motion: no animation, let people swipe/scroll instead */
    @media (prefers-reduced-motion: reduce) {
        .marquee { overflow-x: auto; -webkit-mask-image: none; mask-image: none; }
        .marquee-track { animation: none; }
        .marquee-group[aria-hidden="true"] { display: none; }
    }
</style>
@endpush