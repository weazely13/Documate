<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DocuMate</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- GOOGLE CURSIVE FONT --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gveret+Levin&display=swap" rel="stylesheet">
    @once
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    @endonce

    {{-- VITE ONLY --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- ICONS --}}
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4" defer></script>
    
</head>
<body class="bg-gray-50 antialiased overflow-x-hidden">

<div class="layout flex h-dvh overflow-hidden">
    @php
        $inactive = auth()->check() && auth()->user()->account_status !== 'active';
        $role = auth()->user()->role->role_name ?? null;
        $profileActive = request()->routeIs('profile');
        $profileUrl = route('profile');
        $fullName = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            auth()->user()->first_name ?? null,
            auth()->user()->middle_name ?? null,
            auth()->user()->last_name ?? null,
        ]))));
        $sidebarFirstName = auth()->user()->first_name ?: ($fullName ?: 'User');
        $roleLabel = auth()->user()->role->role_name ?? 'User';
        $sidebarSub = auth()->user()->student_number
            ? auth()->user()->student_number . ' | ' . $roleLabel
            : (auth()->user()->email ? auth()->user()->email . ' | ' . $roleLabel : $roleLabel);
        $slipCount = in_array($role, ['Student', 'Officer'], true)
            ? \App\Models\StudentDocumentWorkspace::officeNoticesFor(auth()->id())->count()
            : 0;
    @endphp

    @php
        // Sidebar sections, split by dashed rules. Each item: [label, icon, route, active patterns, badge].
        // A section with 'parent' renders as one expandable row with its items as children.
        $navSections = [];

        if (in_array($role, ['Student', 'Officer'], true)) {
            $navSections[] = ['key' => 'workspace', 'label' => 'Workspace', 'items' => [
                ['Home', 'bx-home', 'student.home', ['student.home', 'student.new-transaction*', 'student.transactions.new'], 0],
                ['My Documents', 'bx-folder', 'student.documents.index', ['student.documents.*', 'student.transactions.show'], $slipCount],
                ['Appointments', 'bx-calendar', 'student.appointments.index', ['student.appointments.*'], \App\Support\NavCounts::upcomingAppointments(auth()->user())],
                ['Clearance Status', 'bx-check-circle', 'student.clearance-status', ['student.clearance-status'], \App\Support\NavCounts::uncleared(auth()->user())],
            ]];
            if ($role === 'Officer') {
                $navSections[] = ['key' => 'officer', 'label' => 'Student Officer', 'items' => [
                    ['Clearance Tagging', 'bx-check-shield', 'officer.clearance', ['officer.clearance'], \App\Support\NavCounts::untaggedMembers(auth()->user())],
                ]];
            }
        }

        if ($role === 'Admin') {
            $reviewCount = \App\Support\ReviewQueue::query('attention')->count();
            $navSections[] = ['key' => 'workspace', 'label' => 'Workspace', 'items' => [
                ['Dashboard', 'bx-home', 'admin.dashboard', ['admin.dashboard'], 0],
                ['Workflow Review', 'bx-task', 'admin.workflow.index', ['admin.workflow.*'], $reviewCount],
                ['Appointments', 'bx-calendar', 'admin.appointments.index', ['admin.appointments.*'], \App\Support\NavCounts::pendingAppointments()],
                ['Transaction Records', 'bx-transfer', 'admin.transactions.index', ['admin.transactions.*'], 0],
            ]];
            // Templates and Uploads share one area; accounts, verifications, organizations and signatories share another
            $navSections[] = ['key' => 'manage', 'label' => 'Management', 'items' => [
                ['Templates & Uploads', 'bx-file', 'admin.templates', ['admin.document-uploads.*', 'admin.templates*'], \App\Support\NavCounts::uploadsToDo()],
                ['Clearance', 'bx-clipboard', 'admin.clearance-monitoring', ['admin.clearance-monitoring*'], 0],
                ['Users Manager', 'bx-group', 'admin.users', ['admin.users', 'admin.verification', 'admin.semesters'], \App\Support\NavCounts::pendingVerifications()],
                ['Reports', 'bx-bar-chart', 'admin.reports', ['admin.reports'], 0],
            ]];
            $navSections[] = ['key' => 'system', 'label' => 'System', 'items' => [
                ['Feedback & Issues', 'bx-message-square-dots', 'admin.feedback', ['admin.feedback'], \App\Models\FeedbackReport::where('status', 'new')->count()],
                ['Audit Trail', 'bx-history', 'admin.audit-trail', ['admin.audit-trail'], 0],
            ]];
        }

        if (auth()->check() && ! $inactive && auth()->user()->isSignatory()) {
            // A dedicated signing account gets its own Home and Activity summary, like a student's.
            // Students and admins who also sign keep their own Home and summary; signing nests under them.
            $ownAccount = auth()->user()->isSignatoryAccount();
            $signItems = [];
            if ($ownAccount) {
                $signItems[] = ['Home', 'bx-home', 'signatory.home', ['signatory.home', 'signatory.issue'], 0];
            }
            $signItems[] = ['Signing Inbox', 'bx-pen', 'signatory.inbox', ['signatory.inbox', 'signatory.show'], \App\Models\TransactionApproval::assignableTo(auth()->user())->count()];
            if (count(auth()->user()->issuableDocuments()) > 0) {
                if (! $ownAccount) {
                    $signItems[] = ['Issue a Report', 'bx-file', 'signatory.issue', ['signatory.issue'], 0];
                }
                $signItems[] = ['Issued Documents', 'bx-folder-open', 'signatory.issued', ['signatory.issued*'], \App\Support\NavCounts::issuedNeedingChanges(auth()->user())];
            }
            if (auth()->user()->holdsSignatoryRole('guidance_counselor')) {
                $signItems[] = ['My Availability', 'bx-time', 'signatory.availability', ['signatory.availability'], 0];
            }
            if (auth()->user()->isVpsd()) {
                $signItems[] = ['All Documents', 'bx-show', 'signatory.oversight', ['signatory.oversight*'], 0];
            }
            if ($ownAccount) {
                $signItems[] = ['Activity Summary', 'bx-line-chart', 'signatory.activity', ['signatory.activity'], 0];
            }
            // Nest signing under one row when it sits beside another role's pages
            $navSections[] = ['key' => 'signing', 'label' => $ownAccount ? 'Workspace' : 'Signing', 'items' => $signItems,
                'parent' => (! empty($navSections) && count($signItems) > 1) ? ['Signing', 'bx-pen'] : null];
        }

        if (empty($navSections)) {
            $navSections[] = ['key' => 'account', 'label' => 'Account', 'items' => [
                ['Profile', 'bx-user-circle', 'profile', ['profile'], 0],
            ]];
        }

        $currentGroup = null;
        foreach ($navSections as $i => $section) {
            $navSections[$i]['active'] = false;
            $navSections[$i]['badge'] = 0;
            foreach ($section['items'] as $item) {
                $navSections[$i]['badge'] += $item[4];
                if (request()->routeIs(...$item[3])) {
                    $navSections[$i]['active'] = true;
                    $currentGroup ??= $section['label'];
                }
            }
        }
        $currentGroup ??= $profileActive ? 'Account' : null;
        $avatarUrl = auth()->user()->profile_picture
            ? asset('storage/' . auth()->user()->profile_picture)
            : asset('images/backdrop.jpg');
        $homeRoute = $navSections[0]['items'][0][2];
    @endphp

    {{-- OVERLAY FOR MOBILE BACKDROP --}}
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    {{-- SIDEBAR --}}
    <aside class="sidebar {{ $inactive ? 'pointer-events-none opacity-50' : '' }}" id="sidebar" aria-label="Main navigation">

        <div class="nav-top">
            <a href="{{ route($homeRoute) }}" wire:navigate class="nav-brand" data-tooltip="DocuMate">
                <img src="{{ asset('images/favicon.png') }}" alt="DocuMate">
                <span class="logo-text">DocuMate</span>
            </a>

            <a href="{{ $profileUrl }}" wire:navigate data-tour="nav-profile" class="nav-account {{ $profileActive ? 'active' : '' }}" data-tooltip="Profile">
                <span class="nav-account-img">
                    <img src="{{ $avatarUrl }}" alt="">
                </span>
                <span class="nav-account-info">
                    <p class="name">{{ $sidebarFirstName }}</p>
                    <p class="sub">{{ $sidebarSub }}</p>
                </span>
                <i class='bx bx-chevron-right'></i>
            </a>

        </div>

        <nav class="nav-body">
            @foreach($navSections as $section)
                <div class="nav-section">
                    @if(! empty($section['parent']))
                        @php [$parentLabel, $parentIcon] = $section['parent']; @endphp
                        <div class="nav-parent" data-nav-parent="{{ $section['key'] }}" @if($section['active']) data-nav-current @endif>
                            <button type="button" class="nav-link" data-nav-parent-toggle aria-expanded="true" data-tooltip="{{ $parentLabel }}">
                                <i class='bx {{ $parentIcon }}'></i>
                                <span class="nav-label">{{ $parentLabel }}</span>
                                <i class='bx bx-chevron-down nav-chevron'></i>
                                @if($section['badge'] > 0)
                                    <span class="nav-dot"></span>
                                @endif
                            </button>
                            <div class="nav-children">
                                @foreach($section['items'] as [$label, $icon, $routeName, $patterns, $badge])
                                    @php $isActive = request()->routeIs(...$patterns); @endphp
                                    <a href="{{ route($routeName) }}" wire:navigate data-nav-item data-tour="nav-{{ $routeName }}"
                                       class="nav-child {{ $isActive ? 'active' : '' }}"
                                       @if($isActive) aria-current="page" @endif>
                                        <span class="nav-label">{{ $label }}</span>
                                        @if($badge > 0)
                                            <span class="nav-badge">{{ $badge }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @foreach($section['items'] as [$label, $icon, $routeName, $patterns, $badge])
                            @php $isActive = request()->routeIs(...$patterns); @endphp
                            <a href="{{ route($routeName) }}" wire:navigate data-nav-item data-tour="nav-{{ $routeName }}"
                               class="nav-link {{ $isActive ? 'active' : '' }}" data-tooltip="{{ $label }}"
                               @if($isActive) aria-current="page" @endif>
                                <i class='bx {{ $icon }}'></i>
                                <span class="nav-label">{{ $label }}</span>
                                @if($badge > 0)
                                    <span class="nav-badge">{{ $badge }}</span>
                                    <span class="nav-dot"></span>
                                @endif
                            </a>
                        @endforeach
                    @endif
                </div>
            @endforeach

            <p class="nav-empty" data-nav-empty>No pages match your search.</p>

            <div class="nav-section is-foot">
                @if($role !== 'Admin')
                    <a href="{{ route('support.feedback') }}" wire:navigate data-nav-item data-tour="nav-support.feedback"
                       class="nav-link {{ request()->routeIs('support.feedback') ? 'active' : '' }}" data-tooltip="Feedback & Issues">
                        <i class='bx bx-message-square-dots'></i>
                        <span class="nav-label">Feedback & Issues</span>
                    </a>
                @endif
                @if(in_array($role, ['Student', 'Officer'], true))
                    <a href="{{ route('handbook') }}" wire:navigate data-nav-item data-tour="nav-handbook"
                       class="nav-link {{ request()->routeIs('handbook') ? 'active' : '' }}" data-tooltip="Handbook">
                        <i class='bx bx-book'></i>
                        <span class="nav-label">Handbook</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link logout" data-tooltip="Log out">
                        <i class='bx bx-log-out'></i>
                        <span class="nav-label">Log out</span>
                    </button>
                </form>
            </div>
        </nav>

    </aside>

    {{-- Apply saved sidebar state before paint so it doesn't flash open --}}
    <script>
        (() => {
            try {
                const sidebar = document.getElementById('sidebar');
                if (window.innerWidth >= 768 && localStorage.getItem('documate-sidebar-collapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                }
                const closed = JSON.parse(localStorage.getItem('documate-nav-closed') || '[]');
                sidebar.querySelectorAll('[data-nav-parent]').forEach(parent => {
                    const isClosed = closed.includes(parent.dataset.navParent) && !parent.hasAttribute('data-nav-current');
                    parent.classList.toggle('is-closed', isClosed);
                    parent.querySelector('[data-nav-parent-toggle]').setAttribute('aria-expanded', isClosed ? 'false' : 'true');
                });
                if (/Mac|iPhone|iPad/.test(navigator.platform)) {
                    sidebar.querySelector('[data-nav-kbd]').textContent = '⌘ K';
                }
            } catch (e) {}
        })();
    </script>

    {{-- MAIN CONTENT AREA --}}
    <main class="main-content flex flex-col flex-1 h-dvh overflow-hidden">

        <div class="topbar bg-white border-b border-gray-200 sticky top-0 z-40">

            {{-- LEFT --}}
            <div class="topbar-left min-w-0 flex-1">
                <button id="toggleSidebar" class="collapse-btn shrink-0" aria-label="Toggle Sidebar" data-tooltip="Toggle Sidebar">
                    <span class="toggle-icon-box">
                        <i class='bx bx-sidebar'></i>
                    </span>
                </button>

                <div class="topbar-crumbs">
                    @if($currentGroup && $currentGroup !== ($title ?? 'Dashboard'))
                        <span class="topbar-crumb">{{ $currentGroup }}</span>
                        <i class='bx bx-chevron-right topbar-crumb-sep'></i>
                    @endif
                    <h3 class="topbar-title min-w-0 truncate" title="{{ $title ?? 'Dashboard' }}">{{ $title ?? 'Dashboard' }}</h3>
                </div>
            </div>

            {{-- CENTER: search every page (Ctrl K) --}}
            @include('layouts.partials.page-search')

            {{-- RIGHT --}}
            <div class="topbar-right shrink-0">
                {{-- topbar-right in layouts.app --}}
                <div class="topbar-institutions">
                    <img src="{{ asset('images/lnulogo.png') }}" alt="Leyte Normal University" title="Leyte Normal University">
                    <img src="{{ asset('images/osdas-logo-256.png') }}" alt="Office of Student Development and Auxiliary Services" title="Office of Student Development and Auxiliary Services">
                    <span class="topbar-institutions-text">
                        <strong>OVPSD</strong>
                        <span class="topbar-institutions-name">Office of the Vice President for Student Development</span>
                    </span>
                </div>

                @if(in_array($role, ['Student', 'Officer']))
                    @livewire('account-verify-badge', key('account-verify-badge-' . auth()->id()))
                @endif

                @if($role === 'Admin')
                    @livewire('admin.scan-status-bar')
                @endif

                @if(!$inactive)
                    @livewire('notification-bell')
                @else
                    <span class="notif-locked" data-tooltip="Verify your account to enable notifications">
                        <i class='bx bx-bell-off'></i>
                    </span>
                @endif
            </div>

        </div>

       
        @livewire('account-lock-banner', key('account-lock-banner-' . auth()->id()))

        {{-- What the sidebar's numbers count, as a pop-up on every page --}}
        @unless ($inactive)
            @include('layouts.partials.attention-popup')
        @endunless
     

        <div class="content flex-1 overflow-y-auto overflow-x-hidden overscroll-x-none {{ request()->routeIs('handbook*') ? 'p-0' : 'p-4 md:p-6' }}">
            {{ $slot }}
        </div>
        


        @if(!$inactive)
            @php
                $chatRoute = request()->route()?->getName();
                $chatTip = \App\Support\ChatScreen::tip($chatRoute);
            @endphp
            <button type="button" class="chatbot-btn" id="chatbotBtn" aria-label="Open the DocuMate Assistant" aria-controls="chatbotPanel" aria-expanded="false">
                <i class='bx bxs-bot'></i>
            </button>

            {{-- On every page: a short tip from the assistant about what to do here --}}
            <div class="chatbot-tip" role="status" aria-live="polite"
                 x-data="{
                    show: false,
                    key: @js('documate-tip:' . ($chatRoute ?? request()->path())),
                    close() { this.show = false; sessionStorage.setItem(this.key, '1'); },
                    help() {
                        this.close();
                        const panel = document.getElementById('chatbotPanel');
                        if (panel && !panel.classList.contains('chatbot-panel-open')) document.getElementById('chatbotBtn')?.click();
                        setTimeout(() => window.dispatchEvent(new CustomEvent('chatbot-ask', { detail: 'What should I do on this page?' })), 250);
                    },
                 }"
                 x-init="if (!sessionStorage.getItem(key)) { setTimeout(() => { if (!document.querySelector('.chatbot-panel-open') && !document.getElementById('dm-tour-title')) show = true; }, 1200); setTimeout(() => show = false, 16000); }"
                 x-on:chatbot-opened.window="show = false" x-on:tour-started.window="show = false"
                 x-show="show" x-cloak
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-1">
                <button type="button" @click="close()" class="chatbot-tip-close" aria-label="Hide this tip"><i class='bx bx-x'></i></button>
                <p class="chatbot-tip-title"><i class='bx bx-bot' aria-hidden="true"></i>{{ $chatTip['page'] }}</p>
                <p class="chatbot-tip-text">{{ $chatTip['text'] }}</p>
                <button type="button" @click="help()" class="chatbot-tip-action">Show me what to do <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></button>
            </div>

            <section class="chatbot-panel" id="chatbotPanel" role="dialog" aria-label="DocuMate Assistant">
                @livewire('chatbot.chat-widget')
            </section>
        @endif

    </main>

</div>

<div id="tooltip"></div>
<x-toast-stack />
@livewireScripts

<script>
    (() => {
        let tooltipBound = false;

        function bindSidebarToggle() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (!sidebar || !toggleBtn || toggleBtn.dataset.bound === 'true') {
                return;
            }

            const isMobile = () => window.innerWidth < 768;


            if (!isMobile()) {
                const savedState = localStorage.getItem('documate-sidebar-collapsed');
                const isCollapsed = savedState === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.add('collapsed');
                }
            } else {
                // The drawer starts closed; show the same icon the resize handler uses for that state
                toggleBtn.classList.toggle('collapsed', !sidebar.classList.contains('mobile-open'));
            }

            const handleToggle = () => {
                if (isMobile()) {
                    const isOpen = sidebar.classList.toggle('mobile-open');
                    toggleBtn.classList.toggle('collapsed', !isOpen);
                    if (overlay) overlay.classList.toggle('active', isOpen);
                } else {
                    const isCollapsed = sidebar.classList.toggle('collapsed');
                    toggleBtn.classList.toggle('collapsed', isCollapsed);
                    localStorage.setItem('documate-sidebar-collapsed', isCollapsed ? 'true' : 'false');
                }
            };

            toggleBtn.addEventListener('click', handleToggle);

            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('mobile-open');
                    toggleBtn.classList.add('collapsed');
                    overlay.classList.remove('active');
                });
            }

            if (window.__documateResizeBound) {
                toggleBtn.dataset.bound = 'true';
                return;
            }
            window.__documateResizeBound = true;

            window.addEventListener('resize', () => {
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('toggleSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (!sidebar || !toggleBtn) return;

                if (!isMobile()) {
                    sidebar.classList.remove('mobile-open');
                    if (overlay) overlay.classList.remove('active');
                    const savedState = localStorage.getItem('documate-sidebar-collapsed');
                    const isCollapsed = savedState === 'true';
                    sidebar.classList.toggle('collapsed', isCollapsed);
                    toggleBtn.classList.toggle('collapsed', isCollapsed);
                } else {
                    sidebar.classList.remove('collapsed');
                    toggleBtn.classList.add('collapsed');
                }
            });

            toggleBtn.dataset.bound = 'true';
        }

        function bindChatbot() {
            const chatbotBtn = document.getElementById('chatbotBtn');
            const chatbotPanel = document.getElementById('chatbotPanel');

            if (!chatbotBtn || !chatbotPanel) {
                return;
            }

            const openPanel = () => {
                chatbotPanel.classList.add('chatbot-panel-open');
                chatbotBtn.classList.add('open');
                chatbotBtn.setAttribute('aria-expanded', 'true');
                chatbotBtn.querySelector('i').className = 'bx bx-x';
                window.dispatchEvent(new CustomEvent('chatbot-opened'));
            };

            const closePanel = () => {
                chatbotPanel.classList.remove('chatbot-panel-open');
                chatbotBtn.classList.remove('open');
                chatbotBtn.setAttribute('aria-expanded', 'false');
                chatbotBtn.querySelector('i').className = 'bx bxs-bot';
            };

            if (chatbotBtn.dataset.bound !== 'true') {
                chatbotBtn.addEventListener('click', () => {
                    chatbotPanel.classList.contains('chatbot-panel-open') ? closePanel() : openPanel();
                });
                chatbotBtn.dataset.bound = 'true';
            }

            if (!window.__chatbotDocBound) {
                window.__chatbotDocBound = true;
                // The close button lives inside the Livewire widget, so listen at the document
                document.addEventListener('click', (e) => {
                    if (e.target.closest && e.target.closest('[data-chatbot-close]')) {
                        const p = document.getElementById('chatbotPanel');
                        if (p && p.classList.contains('chatbot-panel-open')) document.getElementById('chatbotBtn')?.click();
                    }
                });
                document.addEventListener('keydown', (e) => {
                    const p = document.getElementById('chatbotPanel');
                    if (e.key === 'Escape' && p && p.classList.contains('chatbot-panel-open') && p.contains(document.activeElement)) {
                        document.getElementById('chatbotBtn')?.click();
                        document.getElementById('chatbotBtn')?.focus();
                    }
                });
            }
        }

        /**
         * What the assistant may know about the page: its address, title, headings, notices and visible text.
         * Only what is on screen in the main area (never the chat itself, hidden parts, or form inputs' values).
         */
        window.documateScreen = function () {
            const main = document.querySelector('.content');
            const visible = (el) => !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length)) && getComputedStyle(el).visibility !== 'hidden';
            const dialog = [...document.querySelectorAll('[role="dialog"]:not(#chatbotPanel), [aria-modal="true"]')].filter(visible).pop();
            const root = dialog || main || document.body;
            const pick = (sel, n) => [...root.querySelectorAll(sel)].filter(visible).map(el => el.innerText.trim()).filter(Boolean).slice(0, n);
            const headings = pick('h1, h2, h3', 10);
            const label = headings[0] || 'This page';
            // innerText skips hidden parts and never includes what is typed into inputs
            const text = (root.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 3000);
            return {
                path: location.pathname + location.search,
                title: document.title || '',
                label,
                headings,
                alerts: pick('[role="alert"], [role="status"]', 5),
                text,
            };
        };

        function bindTooltips() {
            const tooltip = document.getElementById('tooltip');
            if (!tooltip || tooltipBound) {
                return;
            }

            const isValidElementTarget = (target) => target && typeof target.closest === 'function';

            document.addEventListener('mouseenter', (event) => {
                if (window.innerWidth < 768 || !isValidElementTarget(event.target)) return;

                const el = event.target.closest('[data-tooltip]');
                if (!el) return;
                // Sidebar labels are visible when it's expanded; only the icon column needs tooltips
                if (el.closest('.sidebar:not(.collapsed)')) return;

                tooltip.textContent = el.getAttribute('data-tooltip');
                tooltip.style.whiteSpace = el.getAttribute('data-tooltip').length > 40 ? 'normal' : 'nowrap';
                tooltip.style.maxWidth = '16rem';
                tooltip.style.opacity = '1';
            }, true);

            document.addEventListener('mousemove', (event) => {
                if (window.innerWidth < 768 || !isValidElementTarget(event.target)) return;

                const el = event.target.closest('[data-tooltip]');
                if (!el) return;

                // Keep it on screen: flip to the left/up of the cursor near an edge
                const pad = 8, gap = 12;
                const w = tooltip.offsetWidth, h = tooltip.offsetHeight;
                let x = event.clientX + gap, y = event.clientY + gap;
                if (x + w > window.innerWidth - pad) x = event.clientX - gap - w;
                if (y + h > window.innerHeight - pad) y = event.clientY - gap - h;
                tooltip.style.left = Math.max(pad, Math.min(x, window.innerWidth - w - pad)) + 'px';
                tooltip.style.top = Math.max(pad, y) + 'px';
            }, true);

            document.addEventListener('mouseleave', (event) => {
                if (isValidElementTarget(event.target) && event.target.closest('[data-tooltip]')) {
                    tooltip.style.opacity = '0';
                }
            }, true);

            tooltipBound = true;
        }

        // Expands the sidebar (desktop) or opens the drawer (mobile) through the toggle, so its state handling is reused
        function revealSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            if (!sidebar || !toggleBtn) return;

            const hidden = window.innerWidth < 768
                ? !sidebar.classList.contains('mobile-open')
                : sidebar.classList.contains('collapsed');
            if (hidden) toggleBtn.click();
        }

        function filterNav(query) {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;

            const q = query.trim().toLowerCase();
            sidebar.classList.toggle('is-searching', q !== '');
            sidebar.querySelectorAll('[data-nav-hidden]').forEach(el => el.removeAttribute('data-nav-hidden'));

            let matches = 0;
            if (q !== '') {
                sidebar.querySelectorAll('[data-nav-item]').forEach(item => {
                    const hit = item.textContent.toLowerCase().includes(q);
                    if (hit) matches++; else item.setAttribute('data-nav-hidden', '');
                });
                sidebar.querySelectorAll('.nav-parent').forEach(parent => {
                    if (!parent.querySelector('[data-nav-item]:not([data-nav-hidden])')) parent.setAttribute('data-nav-hidden', '');
                });
            }
            sidebar.querySelector('[data-nav-empty]')?.classList.toggle('show', q !== '' && matches === 0);
        }

        function bindNav() {
            if (window.__documateNavBound) return;
            window.__documateNavBound = true;

            document.addEventListener('click', (event) => {
                if (event.target.closest?.('[data-nav-search-open]')) {
                    revealSidebar();
                    document.getElementById('navSearch')?.focus();
                    return;
                }

                const toggle = event.target.closest?.('[data-nav-parent-toggle]');
                if (!toggle) return;

                const sidebar = document.getElementById('sidebar');
                const parent = toggle.closest('[data-nav-parent]');
                let isClosed;
                if (sidebar.classList.contains('collapsed')) {
                    // From the icon column, open the sidebar with this parent expanded
                    revealSidebar();
                    isClosed = false;
                    parent.classList.remove('is-closed');
                } else {
                    isClosed = parent.classList.toggle('is-closed');
                }
                toggle.setAttribute('aria-expanded', isClosed ? 'false' : 'true');

                try {
                    const key = parent.dataset.navParent;
                    const closed = new Set(JSON.parse(localStorage.getItem('documate-nav-closed') || '[]'));
                    isClosed ? closed.add(key) : closed.delete(key);
                    localStorage.setItem('documate-nav-closed', JSON.stringify([...closed]));
                } catch (e) {}
            });

            document.addEventListener('input', (event) => {
                if (event.target.id === 'navSearch') filterNav(event.target.value);
            });

            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                    // The page search in the top bar
                    event.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-page-search'));
                    return;
                }

                if (event.target.id !== 'navSearch') return;

                if (event.key === 'Enter') {
                    const first = document.querySelector('#sidebar [data-nav-item]:not([data-nav-hidden])');
                    if (!first || !event.target.value.trim()) return;
                    event.preventDefault();
                    window.Livewire?.navigate ? window.Livewire.navigate(first.href) : (window.location.href = first.href);
                } else if (event.key === 'Escape') {
                    event.target.value = '';
                    filterNav('');
                    event.target.blur();
                }
            });
        }

        function initializeLayoutUi() {
            bindNav();
            bindSidebarToggle();
            bindChatbot();
            bindTooltips();
        }

        document.addEventListener('DOMContentLoaded', initializeLayoutUi);
        document.addEventListener('livewire:navigated', initializeLayoutUi);
    })();
</script>

</body>
</html>