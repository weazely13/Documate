<div>
    {{-- Header --}}
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
            Manage Users
        </h1>

        <p class="text-sm text-gray-500 mb-4">
            View, manage, and control user accounts. Assign roles, monitor activity status, and curate organizations,
            officer positions, and programs across the system.
        </p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-gray-200 mb-6">
        <button type="button" wire:click="setActiveTab('users')"
            class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                {{ $activeTab === 'users' ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Users
        </button>
        <button type="button" wire:click="setActiveTab('org-roles')"
            class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                {{ $activeTab === 'org-roles' ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Organizations &amp; Roles
        </button>
    </div>

    @if ($activeTab === 'org-roles')

        <livewire:admin.organization-role-manager />

    @else

        <div class="text-xs text-gray-400 mt-1 mb-3">
            Total Users: {{ $users->total() }}
        </div>

        {{-- Flash Message --}}
        @if (session()->has('message'))
            <div class="px-4 py-2 text-sm rounded-xl bg-green-50 text-green-700 border border-green-100 shadow-sm mb-4">
                {{ session('message') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">

            {{-- Search --}}
            <div class="relative w-full md:w-1/3">
                <input type="text"
                    wire:model.live="search"
                    placeholder="Search users..."
                    class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 bg-white/60 backdrop-blur focus:ring-2 focus:ring-gray-900/20 outline-none text-sm">

                <span class="absolute left-3 top-2 text-gray-400 text-sm">🔍</span>
            </div>

            {{-- Filters --}}
            <div class="flex gap-2">

                {{-- Role Filter --}}
                <div class="relative">
                    <select wire:model.live="roleFilter"
                        class="px-4 py-2 pr-10 rounded-xl border border-gray-200 bg-white/60 backdrop-blur text-sm appearance-none focus:ring-2 focus:ring-gray-900/20 outline-none">

                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">
                                {{ ucfirst($role->role_name) }}
                            </option>
                        @endforeach
                    </select>

                </div>

                {{-- Status Filter --}}
                <div class="relative">
                    <select wire:model.live="statusFilter"
                        class="px-4 py-2 pr-10 rounded-xl border border-gray-200 bg-white/60 backdrop-blur text-sm appearance-none focus:ring-2 focus:ring-gray-900/20 outline-none">

                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                {{-- Year Sort --}}
                <div class="relative">
                    <select wire:model.live="yearFilter"
                        class="px-4 py-2 pr-10 rounded-xl border border-gray-200 bg-white/60 backdrop-blur text-sm appearance-none focus:ring-2 focus:ring-gray-900/20 outline-none">

                        <option value="">Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- Table Container --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mt-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">

                    {{-- Head --}}
                    <thead class="bg-gray-50/80 text-xs uppercase text-gray-500 tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3.5 text-left font-semibold">User</th>
                            <th class="px-5 py-3.5 text-left font-semibold">Academic</th>
                            <th class="px-5 py-3.5 text-left font-semibold">Organization</th>
                            <th class="px-5 py-3.5 text-left font-semibold">Status</th>
                            <th class="px-5 py-3.5 text-left font-semibold">Role</th>
                            <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    {{-- Body --}}
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                            @php
                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '-';
                                $roleName = $user->role->role_name ?? 'unknown';
                                $isAdminRow = \Illuminate\Support\Str::lower($roleName) === 'admin';

                                $initials = strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1));

                                $avatarColors = [
                                    'admin' => 'bg-red-100 text-red-600',
                                    'officer' => 'bg-blue-100 text-blue-600',
                                    'student' => 'bg-emerald-100 text-emerald-600',
                                ];

                                $roleColors = [
                                    'admin' => 'bg-red-50 text-red-600 ring-red-100',
                                    'officer' => 'bg-blue-50 text-blue-600 ring-blue-100',
                                    'student' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                                ];
                            @endphp

                            <tr class="hover:bg-gray-50/70 transition-colors duration-150 align-top">

                                {{-- User --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-9 h-9 rounded-full overflow-hidden">
                                            @if($user->profile_picture)
                                                <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                    class="w-full h-full object-cover"
                                                    alt="{{ $fullName }}">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-xs font-semibold {{ $avatarColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $initials }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 truncate max-w-[170px]" title="{{ $fullName }}">
                                                {{ $fullName }}
                                            </div>
                                            <div class="text-xs text-gray-400 truncate max-w-[170px]" title="{{ $user->email }}">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Academic (Student No + Program + Year) --}}
                                <td class="px-5 py-4">
                                    <div class="text-gray-700">{{ $user->student_number ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 truncate max-w-[160px]">
                                        {{ $user->program->name ?? 'No program' }}
                                        @if($user->year_level)
                                            · Yr {{ $user->year_level }}
                                        @endif
                                    </div>
                                </td>

                                {{-- Organization --}}
                                <td class="px-5 py-4">
                                    <div class="text-gray-700 truncate max-w-[160px]">
                                        {{ $user->organization->name ?? '—' }}
                                    </div>
                                    @if ($user->isOfficer())
                                        <span class="inline-block mt-1 text-[10px] px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-600 truncate max-w-[160px]" title="{{ $user->officerRecord->position->title ?? 'Officer' }}">
                                            {{ $user->officerRecord->position->title ?? 'Officer' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full
                                        {{ $user->account_status === 'active'
                                            ? 'bg-[#2A57B4]/10 text-[#2A57B4]'
                                            : 'bg-red-50 text-red-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $user->account_status === 'active' ? 'bg-[#2A57B4]' : 'bg-red-500' }}"></span>
                                        {{ ucfirst($user->account_status ?? 'inactive') }}
                                    </span>
                                </td>

                                {{-- Role --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full ring-1 {{ $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600 ring-gray-200' }}">
                                        {{ ucfirst($roleName) }}
                                    </span>
                                </td>
                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click.stop="openModal({{ $user->id }})"
                                            title="View details"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-[#2A57B4] hover:bg-[#2A57B4]/5 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>

                                        @if (! $isAdminRow && $user->id !== auth()->id())
                                            <button wire:click.stop="confirmDelete({{ $user->id }})"
                                                title="Remove user"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

        <div class="mt-6 mb-12 space-y-2">

            {{ $users->links() }}

        </div>


        @if($showModal && $selectedUser)
            <div class="fixed top-0 left-0 w-screen h-screen z-[9999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
                wire:click.self="closeModal">

                <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden relative">

                    {{-- HEADER --}}
                    <div class="bg-gradient-to-br from-[#2A57B4] to-[#1d3f85] px-7 py-7 text-white relative">

                        <button wire:click="closeModal"
                            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-white/70 hover:text-white hover:bg-white/10 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl overflow-hidden ring-2 ring-white/40 shadow-lg flex-shrink-0">
                                @if($selectedUser->profile_picture)
                                    <img src="{{ asset('storage/' . $selectedUser->profile_picture) }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold bg-white/15">
                                        {{ strtoupper(substr($selectedUser->first_name, 0, 1) . substr($selectedUser->last_name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="text-lg font-semibold truncate">
                                    {{ $selectedUser->first_name }} {{ $selectedUser->last_name }}
                                </div>
                                <div class="text-sm text-white/70 truncate">
                                    {{ $selectedUser->email }}
                                </div>

                                <div class="flex flex-wrap gap-1.5 mt-2.5">
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                        {{ $selectedUser->account_status === 'active' ? 'bg-emerald-400/20 text-emerald-100' : 'bg-red-400/20 text-red-100' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $selectedUser->account_status === 'active' ? 'bg-emerald-300' : 'bg-red-300' }}"></span>
                                        {{ ucfirst($selectedUser->account_status ?? 'inactive') }}
                                    </span>

                                    <span class="text-xs px-2.5 py-1 rounded-full bg-white/15">
                                        {{ ucfirst($selectedUser->role->role_name ?? 'User') }}
                                    </span>

                                    @if ($selectedUser->isOfficer())
                                        <span class="text-xs px-2.5 py-1 rounded-full bg-amber-400/20 text-amber-100">
                                            {{ $selectedUser->officerRecord->position->title ?? 'Officer' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="p-7 space-y-6 max-h-[60vh] overflow-y-auto">

                        {{-- PERSONAL --}}
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#2A57B4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Personal</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4 bg-gray-50/70 rounded-2xl p-4 text-sm">
                                <div><div class="text-xs text-gray-400 mb-0.5">Full Name</div><div class="font-medium text-gray-800">{{ $selectedUser->first_name }} {{ $selectedUser->middle_name }} {{ $selectedUser->last_name }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Sex</div><div class="font-medium text-gray-800">{{ $selectedUser->sex ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Birthdate</div><div class="font-medium text-gray-800">{{ $selectedUser->date_of_birth ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Contact</div><div class="font-medium text-gray-800">{{ $selectedUser->contact_number ?? '—' }}</div></div>
                            </div>
                        </div>

                        {{-- ACADEMIC --}}
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-[#2A57B4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443" />
                                </svg>
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Academic</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4 bg-gray-50/70 rounded-2xl p-4 text-sm">
                                <div><div class="text-xs text-gray-400 mb-0.5">Student No.</div><div class="font-medium text-gray-800">{{ $selectedUser->student_number ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">College</div><div class="font-medium text-gray-800">{{ $selectedUser->college ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Program</div><div class="font-medium text-gray-800">{{ $selectedUser->program->name ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Year Level</div><div class="font-medium text-gray-800">{{ $selectedUser->year_level ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Organization</div><div class="font-medium text-gray-800">{{ $selectedUser->organization->name ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400 mb-0.5">Status</div><div class="font-medium text-gray-800">{{ $selectedUser->academic_status ?? '—' }}</div></div>
                            </div>
                        </div>

                    </div>

                    {{-- FOOTER --}}
                    <div class="px-7 py-4 border-t border-gray-100 flex justify-end">
                        <button wire:click="closeModal"
                            class="px-5 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 border border-gray-200 transition">
                            Close
                        </button>
                    </div>

                </div>
            </div>
        @endif

        @if($showDeleteModal && $userPendingDeletion)
            <div class="fixed top-0 left-0 z-[10000] flex h-screen w-screen items-center justify-center bg-black/60 px-4" wire:click.self="cancelDelete">
                <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <button wire:click="cancelDelete"
                        class="absolute right-4 top-4 text-lg text-gray-400 transition hover:text-gray-700">
                        ×
                    </button>

                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-2xl text-red-500">
                            !
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-xl font-semibold text-gray-900">
                                Remove User
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-gray-500">
                                Are you sure you want to remove this user?
                            </p>
                            <p class="mt-3 rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                <span class="font-semibold text-gray-900">{{ trim(($userPendingDeletion->first_name ?? '') . ' ' . ($userPendingDeletion->last_name ?? '')) ?: ('User #' . $userPendingDeletion->id) }}</span>
                                will be permanently deleted from the system.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="cancelDelete"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                            Cancel
                        </button>

                        <button wire:click="deleteUser"
                            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                            Yes, Remove User
                        </button>
                    </div>
                </div>
            </div>
        @endif

    @endif
</div>