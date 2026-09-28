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
                left: 17.5rem !important; /* sidebar open */
            }
            .scan-status-bar-container.sidebar-collapsed {
                left: 5.5rem !important; /* sidebar collapsed (72px + 1rem gap) */
            }
        }
        @media (max-width: 767px) {
            .scan-status-bar-container { left: 0.5rem !important; }
        }
    </style>
</head>
<body class="bg-gray-50 antialiased overflow-x-hidden">

<div class="layout flex h-screen overflow-hidden">
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
    @endphp

    {{-- OVERLAY FOR MOBILE BACKDROP --}}
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    {{-- SIDEBAR --}}
    <aside class="sidebar {{ $inactive ? 'pointer-events-none opacity-50' : '' }}" id="sidebar">

        {{-- HEADER --}}
        <div class="sidebar-header">
            <div class="header-left">
                <img src="{{ asset('images/favicon.png') }}" class="logo" alt="DocuMate Logo">
                <span class="logo-text">DocuMate</span>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="sidebar-content">

            {{-- STUDENT --}}
            @if($role === 'Student')
                <div class="sidebar-section">
                    <p class="section-title">Student</p>

                    <a href="{{ route('student.dashboard') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                        <i class='bx bx-home link-icon'></i>
                        <span class="link-text">Dashboard</span>
                    </a>
                    <a href="{{ route('student.new-transaction') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.new-transaction*') ? 'active' : '' }}" data-tooltip="New Transaction">
                        <i class='bx bx-plus-circle link-icon'></i>
                        <span class="link-text">New Transaction</span>
                    </a>
                    <a href="{{ route('student.appointments.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.appointments.*') ? 'active' : '' }}" data-tooltip="Appointments">
                        <i class='bx bx-calendar link-icon'></i>
                        <span class="link-text">Appointments</span>
                    </a>
                    <a href="{{ route('student.documents.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.documents.*') ? 'active' : '' }}" data-tooltip="Documents">
                        <i class='bx bx-folder link-icon'></i>
                        <span class="link-text">Documents</span>
                    </a>
                    <a href="{{ route('student.clearance-status') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.clearance-status') ? 'active' : '' }}" data-tooltip="Clearance Status">
                        <i class='bx bx-check-circle link-icon'></i>
                        <span class="link-text">Clearance Status</span>
                    </a>
                    <a href="{{ route('profile') }}" wire:navigate class="sidebar-link {{ request()->routeIs('profile') ? 'active' : '' }}" data-tooltip="Profile">
                        <i class='bx bx-user-circle link-icon'></i>
                        <span class="link-text">Profile</span>
                    </a>
                    <a href="{{ route('handbook') }}" wire:navigate class="sidebar-link {{ request()->routeIs('handbook') ? 'active' : '' }}" data-tooltip="Handbook">
                        <i class='bx bx-book link-icon'></i>
                        <span class="link-text">Handbook</span>
                    </a>
                </div>
            @endif

            {{-- OFFICER --}}
            @if($role === 'Officer')
                <div class="sidebar-section">
                    <p class="section-title">Student Officer</p>
                    
                    <a href="{{ route('student.dashboard') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                        <i class='bx bx-home link-icon'></i>
                        <span class="link-text">Dashboard</span>
                    </a>
                    <a href="{{ route('student.new-transaction') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.new-transaction*') ? 'active' : '' }}" data-tooltip="New Transaction">
                        <i class='bx bx-plus-circle link-icon'></i>
                        <span class="link-text">New Transaction</span>
                    </a>
                    <a href="{{ route('student.appointments.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.appointments.*') ? 'active' : '' }}" data-tooltip="Appointments">
                        <i class='bx bx-calendar link-icon'></i>
                        <span class="link-text">Appointments</span>
                    </a>
                    <a href="{{ route('student.documents.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.documents.*') ? 'active' : '' }}" data-tooltip="Documents">
                        <i class='bx bx-folder link-icon'></i>
                        <span class="link-text">Documents</span>
                    </a>
                    <a href="{{ route('student.clearance-status') }}" wire:navigate class="sidebar-link {{ request()->routeIs('student.clearance-status') ? 'active' : '' }}" data-tooltip="Clearance Status">
                        <i class='bx bx-check-circle link-icon'></i>
                        <span class="link-text">Clearance Status</span>
                    </a>
                    <a href="{{ route('officer.clearance') }}" wire:navigate class="sidebar-link {{ request()->routeIs('officer.clearance') ? 'active' : '' }}" data-tooltip="Clearance Tagging">
                        <i class='bx bx-check-shield link-icon'></i>
                        <span class="link-text">Clearance Tagging</span>
                    </a>
                    <a href="{{ route('profile') }}" wire:navigate class="sidebar-link {{ request()->routeIs('profile') ? 'active' : '' }}" data-tooltip="Profile">
                        <i class='bx bx-user-circle link-icon'></i>
                        <span class="link-text">Profile</span>
                    </a>
                    <a href="{{ route('handbook') }}" wire:navigate class="sidebar-link {{ request()->routeIs('handbook') ? 'active' : '' }}" data-tooltip="Handbook">
                        <i class='bx bx-book link-icon'></i>
                        <span class="link-text">Handbook</span>
                    </a>
                </div>
            @endif

            {{-- ADMIN --}}
            @if($role === 'Admin')
                <div class="sidebar-section">
                    <p class="section-title">Admin</p>

                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                        <i class='bx bx-home link-icon'></i>
                        <span class="link-text">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.transactions.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" data-tooltip="Transactions">
                        <i class='bx bx-transfer link-icon'></i>
                        <span class="link-text">Transactions</span>
                    </a>
                    <a href="{{ route('admin.appointments.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}" data-tooltip="Appointments">
                        <i class='bx bx-calendar link-icon'></i>
                        <span class="link-text">Appointments</span>
                    </a>
                    <a href="{{ route('admin.document-uploads.index') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.document-uploads.*') ? 'active' : '' }}" data-tooltip="Document Uploads">
                        <i class='bx bx-camera link-icon'></i>
                        <span class="link-text">Document Uploads</span>
                    </a>
                    <a href="{{ route('admin.clearance-monitoring') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.clearance-monitoring') ? 'active' : '' }}" data-tooltip="Clearance Monitoring">
                        <i class='bx bx-clipboard link-icon'></i>
                        <span class="link-text">Clearance Monitoring</span>
                    </a>
                    <a href="{{ route('admin.verification') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.verification') ? 'active' : '' }}" data-tooltip="Verification">
                        <i class='bx bx-id-card link-icon'></i>
                        <span class="link-text">Verification</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.reports') ? 'active' : ''}}" data-tooltip="Reports">
                        <i class='bx bx-bar-chart link-icon'></i>
                        <span class="link-text">Reports</span>
                    </a>
                    <a href="{{ route('admin.users') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" data-tooltip="Manage Users">
                        <i class='bx bx-group link-icon'></i>
                        <span class="link-text">Manage Users</span>
                    </a>
                    <a href="{{ route('admin.templates') }}" wire:navigate class="sidebar-link {{ request()->routeIs('admin.templates*') ? 'active' : '' }}" data-tooltip="Document Templates">
                        <i class='bx bx-file link-icon'></i>
                        <span class="link-text">Document Templates</span>
                    </a>
                    <a href="{{ route('profile') }}" wire:navigate class="sidebar-link {{ request()->routeIs('profile') ? 'active' : '' }}" data-tooltip="Profile">
                        <i class='bx bx-user-circle link-icon'></i>
                        <span class="link-text">Profile</span>
                    </a>
                </div>
            @endif

        </div>

        {{-- PROFILE --}}
        <div class="sidebar-profile">
            <a href="{{ $profileUrl }}" class="profile-row {{ $profileActive ? 'active' : '' }}" data-tooltip="Profile">
                <img src="{{ auth()->user()->profile_picture 
                    ? asset('storage/' . auth()->user()->profile_picture) 
                    : asset('images/backdrop.jpg') }}" 
                class="profile-img" alt="Profile Picture">

                <div class="profile-info">
                    <p class="name">{{ $sidebarFirstName }}</p>
                    <p class="sub">{{ $sidebarSub }}</p>
                </div>
            </a>
        </div>

    </aside>

    {{-- MAIN CONTENT AREA --}}
    <main class="main-content flex flex-col flex-1 h-screen overflow-hidden">

        <div class="topbar bg-white border-b border-gray-200 sticky top-0 z-40 shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)]">

            {{-- LEFT --}}
            <div class="topbar-left">
                <button id="toggleSidebar" class="collapse-btn" aria-label="Toggle Sidebar" data-tooltip="Toggle Sidebar">
                    <span class="toggle-icon-box">
                        <i class='bx bx-sidebar'></i>
                    </span>
                </button>

                <h3 class="topbar-title">{{ $title ?? 'Dashboard' }}</h3>
            </div>

            {{-- RIGHT --}}
            <div class="topbar-right">
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
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-icon" aria-label="Logout">
                        <i class='bx bx-log-out'></i>
                    </button>
                </form>
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

        @if(!$inactive)
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

            window.addEventListener('resize', () => {
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

        function initializeLayoutUi() {
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