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
    
    <style>
        .scan-status-bar-container {
            max-width: 24rem;
            width: calc(100% - 2rem);
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (min-width: 768px) {
            .scan-status-bar-container {
                left: 17.5rem !important; /* sidebar open (264px + 1rem gap) */
            }
            .scan-status-bar-container.sidebar-collapsed {
                left: 5.25rem !important; /* rail only (68px + 1rem gap) */
            }
        }
        @media (max-width: 767px) {
            .scan-status-bar-container { left: 0.5rem !important; }
        }
    </style>
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
                ['Dashboard', 'bx-home', 'student.dashboard', ['student.dashboard'], 0],
                ['Create Transaction', 'bx-plus-circle', 'student.new-transaction', ['student.new-transaction*', 'student.transactions.new'], 0],
                ['Documents', 'bx-folder', 'student.documents.index', ['student.documents.*', 'student.transactions.show'], $slipCount],
                ['Appointments', 'bx-calendar', 'student.appointments.index', ['student.appointments.*'], 0],
                ['Clearance Status', 'bx-check-circle', 'student.clearance-status', ['student.clearance-status'], 0],
            ]];
            if ($role === 'Officer') {
                $navSections[] = ['key' => 'officer', 'label' => 'Student Officer', 'items' => [
                    ['Clearance Tagging', 'bx-check-shield', 'officer.clearance', ['officer.clearance'], 0],
                ]];
            }
        }

        if ($role === 'Admin') {
            $reviewCount = \App\Support\ReviewQueue::query('attention')->count();
            $navSections[] = ['key' => 'workspace', 'label' => 'Workspace', 'items' => [
                ['Dashboard', 'bx-home', 'admin.dashboard', ['admin.dashboard'], 0],
                ['Workflow Review', 'bx-task', 'admin.workflow.index', ['admin.workflow.*'], $reviewCount],
                ['Appointments', 'bx-calendar', 'admin.appointments.index', ['admin.appointments.*'], 0],
                ['Transaction Records', 'bx-transfer', 'admin.transactions.index', ['admin.transactions.*'], 0],
            ]];
            // Templates and Uploads share one area; accounts, verifications, organizations and signatories share another
            $navSections[] = ['key' => 'manage', 'label' => 'Management', 'items' => [
                ['Templates & Uploads', 'bx-file', 'admin.templates', ['admin.document-uploads.*', 'admin.templates*'], 0],
                ['Clearance', 'bx-clipboard', 'admin.clearance-monitoring', ['admin.clearance-monitoring*', 'admin.semesters'], 0],
                ['Users Manager', 'bx-group', 'admin.users', ['admin.users', 'admin.verification'], 0],
                ['Reports', 'bx-bar-chart', 'admin.reports', ['admin.reports'], 0],
            ]];
        }

        if (auth()->check() && ! $inactive && auth()->user()->isSignatory()) {
            $signItems = [
                ['Signing Inbox', 'bx-pen', 'signatory.inbox', ['signatory.inbox', 'signatory.show'], \App\Models\TransactionApproval::assignableTo(auth()->user())->count()],
            ];
            if (count(auth()->user()->issuableDocuments()) > 0) {
                $signItems[] = ['Issue a Report', 'bx-file', 'signatory.issue', ['signatory.issue'], 0];
                $signItems[] = ['Issued Documents', 'bx-folder-open', 'signatory.issued', ['signatory.issued*'], 0];
            }
            if (auth()->user()->holdsSignatoryRole('guidance_counselor')) {
                $signItems[] = ['My Availability', 'bx-time', 'signatory.availability', ['signatory.availability'], 0];
            }
            if (auth()->user()->isVpsd()) {
                $signItems[] = ['All Documents', 'bx-show', 'signatory.oversight', ['signatory.oversight*'], 0];
            }
            // Nest signing under one row when it sits beside another role's pages
            $navSections[] = ['key' => 'signing', 'label' => 'Signing', 'items' => $signItems,
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

            <a href="{{ $profileUrl }}" wire:navigate class="nav-account {{ $profileActive ? 'active' : '' }}" data-tooltip="Profile">
                <span class="nav-account-img">
                    <img src="{{ $avatarUrl }}" alt="">
                </span>
                <span class="nav-account-info">
                    <p class="name">{{ $sidebarFirstName }}</p>
                    <p class="sub">{{ $sidebarSub }}</p>
                </span>
                <i class='bx bx-chevron-right'></i>
            </a>

            <div class="nav-search">
                <button type="button" class="nav-search-btn" data-nav-search-open data-tooltip="Search pages" aria-label="Search pages">
                    <i class='bx bx-search'></i>
                </button>
                <span class="nav-search-field">
                    <input type="search" id="navSearch" placeholder="Search" autocomplete="off" aria-label="Search pages">
                    <kbd data-nav-kbd>Ctrl K</kbd>
                </span>
            </div>
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
                                    <a href="{{ route($routeName) }}" wire:navigate data-nav-item
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
                            <a href="{{ route($routeName) }}" wire:navigate data-nav-item
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
                @if(in_array($role, ['Student', 'Officer'], true))
                    <a href="{{ route('handbook') }}" wire:navigate data-nav-item
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

            {{-- RIGHT --}}
            <div class="topbar-right shrink-0">
                {{-- topbar-right in layouts.app --}}
                @if(in_array($role, ['Student', 'Officer']))
                    @livewire('account-verify-badge', key('account-verify-badge-' . auth()->id()))
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
     

        <div class="content flex-1 overflow-y-auto {{ request()->routeIs('handbook*') ? 'p-0' : 'p-4 md:p-6' }}">
            {{ $slot }}
        </div>
        
        @if($role === 'Admin')
            <div class="scan-status-bar-container fixed bottom-0 left-0 z-40 flex flex-col items-start">
                @livewire('admin.scan-status-bar')
            </div>
        @endif  

        @if(!$inactive && $role !== 'Signatory')
            <div class="chatbot-btn" id="chatbotBtn" data-tooltip="AI Assistant">
                <i class='bx bx-message-dots'></i>
            </div>

            <div class="chatbot-panel" id="chatbotPanel">
                <div class="chatbot-header">
                    <div class="chatbot-header-info">
                        <div class="chatbot-avatar">
                            <i class='bx bx-bot'></i>
                        </div>
                        <div>
                            <p class="chatbot-header-title">DocuMate Assistant</p>
                            <p class="chatbot-header-status">
                                <span class="chatbot-status-dot"></span>
                                Online
                            </p>
                        </div>
                    </div>
                    <button id="closeChatbot" aria-label="Close chat">&times;</button>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col">
                    @livewire('chatbot.chat-widget')
                </div>
            </div>
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

            // NEW: keeps the scan status overlay's left offset in sync with the sidebar
            const syncScanBarOffset = (collapsed) => {
                document.querySelectorAll('.scan-status-bar-container').forEach(bar => {
                    bar.classList.toggle('sidebar-collapsed', collapsed);
                });
            };

            if (!isMobile()) {
                const savedState = localStorage.getItem('documate-sidebar-collapsed');
                const isCollapsed = savedState === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.add('collapsed');
                }
                syncScanBarOffset(isCollapsed); // NEW: sync on initial load
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
                    syncScanBarOffset(isCollapsed); // NEW: sync on manual toggle
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
                    syncScanBarOffset(isCollapsed); // NEW: sync on resize back to desktop
                } else {
                    sidebar.classList.remove('collapsed');
                    toggleBtn.classList.add('collapsed');
                    syncScanBarOffset(false); // NEW: mobile always uses the mobile offset, ignore collapsed state
                }
            });

            toggleBtn.dataset.bound = 'true';
        }

        function bindChatbot() {
            const chatbotBtn = document.getElementById('chatbotBtn');
            const chatbotPanel = document.getElementById('chatbotPanel');
            const closeChatbot = document.getElementById('closeChatbot');

            if (!chatbotBtn || !chatbotPanel || !closeChatbot) {
                return;
            }

            const openPanel = () => {
                chatbotPanel.classList.add('chatbot-panel-open');
                chatbotBtn.classList.add('open');
                chatbotBtn.querySelector('i').className = 'bx bx-x';
            };

            const closePanel = () => {
                chatbotPanel.classList.remove('chatbot-panel-open');
                chatbotBtn.classList.remove('open');
                chatbotBtn.querySelector('i').className = 'bx bx-message-dots';
            };

            if (chatbotBtn.dataset.bound !== 'true') {
                chatbotBtn.addEventListener('click', () => {
                    chatbotPanel.classList.contains('chatbot-panel-open') ? closePanel() : openPanel();
                });
                chatbotBtn.dataset.bound = 'true';
            }

            if (closeChatbot.dataset.bound !== 'true') {
                closeChatbot.addEventListener('click', closePanel);
                closeChatbot.dataset.bound = 'true';
            }
        }

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
                tooltip.style.opacity = '1';
            }, true);

            document.addEventListener('mousemove', (event) => {
                if (window.innerWidth < 768 || !isValidElementTarget(event.target)) return;

                const el = event.target.closest('[data-tooltip]');
                if (!el) return;

                tooltip.style.left = (event.clientX + 12) + 'px';
                tooltip.style.top = (event.clientY + 12) + 'px';
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
                    const search = document.getElementById('navSearch');
                    if (!search) return;
                    event.preventDefault();
                    revealSidebar();
                    search.focus();
                    search.select();
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