<div>
    {{-- Header --}}
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
            Manage Users
        </h1>

        <p class="text-sm text-gray-500 mb-6">
            View, manage, and control user accounts. Assign roles, monitor activity status, and ensure proper access control across the system.
        </p>

        <div class="text-xs text-gray-400 mt-1">
            Total Users: {{ $users->total() }}
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="px-4 py-2 text-sm rounded-xl bg-green-50 text-green-700 border border-green-100 shadow-sm">
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

    {{-- Table Container (FIXED) --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-x-auto mt-4">

        <table class="w-full text-sm table-auto">

            {{-- Head --}}
            <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4] tracking-wider">
                <tr>
                    <th class="px-4 py-4 text-left font-medium">User</th>
                    <th class="px-4 py-4 text-left font-medium">Email</th>
                    <th class="px-4 py-4 text-left font-medium">Student No.</th>
                    <th class="px-4 py-4 text-left font-medium">Program</th>
                    <th class="px-4 py-4 text-left font-medium">Organization</th>
                    <th class="px-4 py-4 text-left font-medium">Year</th>
                    <th class="px-4 py-4 text-left font-medium">Status</th>
                    <th class="px-4 py-4 text-left font-medium">Role</th>
                    <th class="px-4 py-4 text-left font-medium">Modify</th>
                    <th class="px-4 py-4 text-left font-medium">Action</th>
                </tr>
            </thead>

            {{-- Body --}}
            <tbody class="divide-y divide-gray-100/70">

                @foreach($users as $user)
                    @php
                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '-';
                        $roleName = $user->role->role_name ?? 'unknown';

                        $colors = [
                            'admin' => 'bg-red-50 text-red-600',
                            'officer' => 'bg-blue-50 text-blue-600',
                            'student' => 'bg-green-50 text-green-600',
                        ];
                    @endphp
                    
                    <tr class="hover:bg-white/60 transition duration-200">

                        {{-- User --}}
                        <td class="px-4 py-4">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-900 max-w-[180px] truncate"
                                      title="{{ $fullName }}">
                                    {{ $fullName }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    ID: {{ $user->id }}
                                </span>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-4 py-4 text-gray-600 max-w-[250px] truncate"
                            title="{{ $user->email }}">
                            {{ $user->email }}
                        </td>

                        {{-- Student Number --}}
                        <td class="px-4 py-4 text-gray-500 max-w-[150px] truncate">
                            {{ $user->student_number ?? '-' }}
                        </td>

                        {{-- Program --}}
                        <td class="px-4 py-4 text-gray-600 max-w-[180px] truncate">
                            {{ $user->program ?? '-' }}
                        </td>

                        {{-- Organization --}}
                        <td class="px-4 py-4 text-gray-600 max-w-[180px] truncate">
                            {{ $user->organization ?? '-' }}
                        </td>

                        {{-- Year --}}
                        <td class="px-4 py-4 text-gray-600">
                            {{ $user->year_level ?? '-' }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-4">
                            <span class="text-xs font-medium px-2 py-1 rounded-full 
                                {{ $user->account_status === 'active'
                                    ? 'bg-[#2A57B4]/10 text-[#2A57B4]'
                                    : 'bg-red-50 text-red-600' }}">
                                {{ ucfirst($user->account_status ?? 'inactive') }}
                            </span>
                        </td>

                        {{-- Role --}}
                        <td class="px-4 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $colors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($roleName) }}
                            </span>
                        </td>

                        {{-- Change Role --}}
                        <td class="px-4 py-4">
                            <select wire:change="updateRole({{ $user->id }}, $event.target.value)"
                                class="w-[140px] bg-white/60 backdrop-blur border border-gray-200/70 rounded-xl px-3 py-1.5 text-sm focus:ring-2 focus:ring-gray-900/20 focus:outline-none transition">

                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                        {{ ucfirst($role->role_name) }}
                                    </option>
                                @endforeach

                            </select>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click.stop="openModal({{ $user->id }})"
                                    class="text-xs font-medium px-4 py-1.5 rounded-xl 
                                    bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">
                                    View
                                </button>

                                <button wire:click.stop="confirmDelete({{ $user->id }})"
                                    class="text-xs font-medium px-4 py-1.5 rounded-xl 
                                    bg-red-50 text-red-600 hover:bg-red-100 transition border border-red-100 shadow-sm">
                                    Delete
                                </button>
                            </div>
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    <div class="mt-6 mb-12 space-y-2">

        {{ $users->links() }}

    </div>


    @if($showModal && $selectedUser)
        <div class="fixed top-0 left-0 w-screen h-screen z-[9999] bg-black/60 flex items-center justify-center">

            <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden relative">

                {{-- HEADER --}}
                <div class="bg-gradient-to-r from-[#2A57B4] to-[#1d3f85] p-6 text-white">

                    <button wire:click="closeModal"
                        class="absolute top-4 right-4 text-white/70 hover:text-white text-lg">
                        ✕
                    </button>

                    <div class="flex items-center gap-5">

                        {{-- Profile --}}
                        <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white shadow">

                            @if($selectedUser->profile_picture)
                                <img src="{{ asset('storage/' . $selectedUser->profile_picture) }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white text-xl font-bold bg-[#2A57B4]">
                                    {{ strtoupper(substr($selectedUser->first_name, 0, 1) . substr($selectedUser->last_name, 0, 1)) }}
                                </div>
                            @endif

                        </div>

                        <div>
                            <div class="text-xl font-semibold">
                                {{ $selectedUser->first_name }} {{ $selectedUser->last_name }}
                            </div>

                            <div class="text-sm text-white/80">
                                {{ $selectedUser->email }}
                            </div>

                            <div class="flex gap-2 mt-2">
                                <span class="text-xs px-3 py-1 rounded-full 
                                    {{ $selectedUser->account_status === 'active' ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200' }}">
                                    {{ ucfirst($selectedUser->account_status ?? 'inactive') }}
                                </span>

                                <span class="text-xs px-3 py-1 rounded-full bg-white/20">
                                    {{ ucfirst($selectedUser->role->role_name ?? 'User') }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- BODY --}}
                <div class="p-6 space-y-6">

                    {{-- PERSONAL --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">Personal</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-400">Full Name</span><div class="font-medium">{{ $selectedUser->first_name }} {{ $selectedUser->middle_name }} {{ $selectedUser->last_name }}</div></div>
                            <div><span class="text-gray-400">Sex</span><div>{{ $selectedUser->sex ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Birthdate</span><div>{{ $selectedUser->date_of_birth ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Contact</span><div>{{ $selectedUser->contact_number ?? '-' }}</div></div>
                        </div>
                    </div>

                    {{-- ACADEMIC --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">Academic</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-400">Student No.</span><div>{{ $selectedUser->student_number ?? '-' }}</div></div>
                            <div><span class="text-gray-400">College</span><div>{{ $selectedUser->college ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Program</span><div>{{ $selectedUser->program ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Year Level</span><div>{{ $selectedUser->year_level ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Organization</span><div>{{ $selectedUser->organization ?? '-' }}</div></div>
                            <div><span class="text-gray-400">Status</span><div>{{ $selectedUser->academic_status ?? '-' }}</div></div>
                        </div>
                    </div>

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
</div>
