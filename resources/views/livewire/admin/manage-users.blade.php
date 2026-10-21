@php
    $areas = ['users' => 'Users', 'org-roles' => 'Organizations & Roles'];
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
@endphp

<div>
    <x-admin.page-header
        title="Users & Access"
        description="Manage accounts, roles, organizations, officer positions, programs and signatories."
        :crumbs="['Admin' => $homeUrl, 'Users & Access' => null]" />

    {{-- Area tabs --}}
    <nav aria-label="Users and access sections" class="mb-6 flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200">
        @foreach ($areas as $key => $text)
            <button type="button" wire:click="setActiveTab('{{ $key }}')"
                @if ($activeTab === $key) aria-current="page" @endif
                class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                    {{ $activeTab === $key
                        ? 'border-[#2A57B4] text-[#2A57B4]'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $text }}
            </button>
        @endforeach
    </nav>

    @if ($activeTab === 'org-roles')

        <livewire:admin.organization-role-manager />

    @else

        <x-admin.alert :message="session('message')" type="success" />

        {{-- Toolbar --}}
        <div class="mb-4 grid grid-cols-2 gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:flex sm:flex-wrap sm:items-center">
            <div class="relative col-span-2 sm:min-w-[220px] sm:flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, student no…"
                    class="w-full pl-9 pr-3 {{ $fieldClass }}">
            </div>

            <select wire:model.live="roleFilter" aria-label="Filter by role" class="w-full sm:w-auto {{ $fieldClass }}">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ ucfirst($role->role_name) }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" aria-label="Filter by status" class="w-full sm:w-auto {{ $fieldClass }}">
                <option value="">Any status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>

            <select wire:model.live="yearFilter" aria-label="Filter by year level" class="w-full sm:w-auto {{ $fieldClass }}">
                <option value="">Any year</option>
                <option value="1">1st year</option>
                <option value="2">2nd year</option>
                <option value="3">3rd year</option>
                <option value="4">4th year</option>
            </select>

            <label class="inline-flex items-center gap-2 px-1 py-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="showRemoved" class="rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                Removed
            </label>

            <span class="col-span-2 text-xs text-gray-500 sm:ml-auto">{{ number_format($users->total()) }} users</span>
        </div>

        {{-- Table --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">User</th>
                            <th class="px-5 py-3 text-left">Academic</th>
                            <th class="px-5 py-3 text-left">Organization</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Role</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
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

                            <tr wire:key="user-{{ $user->id }}" class="align-top transition-colors hover:bg-gray-50/70">

                                {{-- User --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 flex-shrink-0 overflow-hidden rounded-full">
                                            @if($user->profile_picture)
                                                <img src="{{ asset('storage/' . $user->profile_picture) }}" class="h-full w-full object-cover" alt="{{ $fullName }}">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-xs font-semibold {{ $avatarColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $initials }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="max-w-[170px] truncate font-medium text-gray-900" title="{{ $fullName }}">{{ $fullName }}</div>
                                            <div class="max-w-[170px] truncate text-xs text-gray-400" title="{{ $user->email }}">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Academic --}}
                                <td class="px-5 py-4">
                                    <div class="text-gray-700">{{ $user->student_number ?? '—' }}</div>
                                    <div class="max-w-[160px] truncate text-xs text-gray-400">
                                        {{ $user->program->name ?? 'No program' }}@if($user->year_level) · Yr {{ $user->year_level }}@endif
                                    </div>
                                </td>

                                {{-- Organization --}}
                                <td class="px-5 py-4">
                                    <div class="max-w-[160px] truncate text-gray-700">{{ $user->organization->name ?? '—' }}</div>
                                    @if ($user->isOfficer())
                                        <span class="mt-1 inline-block max-w-[160px] truncate rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] text-amber-600" title="{{ $user->officerRecord->position->title ?? 'Officer' }}">
                                            {{ $user->officerRecord->position->title ?? 'Officer' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4">
                                    @if ($user->trashed())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Removed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium
                                            {{ $user->account_status === 'active' ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-red-50 text-red-600' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $user->account_status === 'active' ? 'bg-[#2A57B4]' : 'bg-red-500' }}"></span>
                                            {{ ucfirst($user->account_status ?? 'inactive') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Role --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600 ring-gray-200' }}">
                                        {{ ucfirst($roleName) }}
                                    </span>
                                    <x-admin.signatory-badges :user="$user" class="mt-1.5 max-w-[170px]" />
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click.stop="openModal({{ $user->id }})" title="View details" aria-label="View details"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-[#2A57B4]/5 hover:text-[#2A57B4]">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>

                                        @if ($user->trashed())
                                            <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?" title="Restore user"
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                Restore
                                            </button>
                                        @elseif (! $isAdminRow && $user->id !== auth()->id())
                                            <button wire:click.stop="confirmDelete({{ $user->id }})" title="Remove user" aria-label="Remove user"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No users match your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        {{-- Mobile: card list (the table above is hidden below md) --}}
        <div class="space-y-3 md:hidden">
            @forelse ($users as $user)
                @php
                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '-';
                    $roleName = $user->role->role_name ?? 'unknown';
                    $isAdminRow = \Illuminate\Support\Str::lower($roleName) === 'admin';
                    $initials = strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1));
                    $avatarColors = ['admin' => 'bg-red-100 text-red-600', 'officer' => 'bg-blue-100 text-blue-600', 'student' => 'bg-emerald-100 text-emerald-600'];
                    $roleColors = ['admin' => 'bg-red-50 text-red-600 ring-red-100', 'officer' => 'bg-blue-50 text-blue-600 ring-blue-100', 'student' => 'bg-emerald-50 text-emerald-600 ring-emerald-100'];
                @endphp
                <div wire:key="user-card-{{ $user->id }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-11 w-11 shrink-0 overflow-hidden rounded-full">
                            @if($user->profile_picture)
                                <img src="{{ asset('storage/' . $user->profile_picture) }}" class="h-full w-full object-cover" alt="{{ $fullName }}">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-sm font-semibold {{ $avatarColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">{{ $initials }}</div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900">{{ $fullName }}</p>
                            <p class="truncate text-xs text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600 ring-gray-200' }}">{{ ucfirst($roleName) }}</span>
                        @if ($user->trashed())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Removed</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $user->account_status === 'active' ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-red-50 text-red-600' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $user->account_status === 'active' ? 'bg-[#2A57B4]' : 'bg-red-500' }}"></span>{{ ucfirst($user->account_status ?? 'inactive') }}
                            </span>
                        @endif
                        @if ($user->isOfficer())
                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs text-amber-600">{{ $user->officerRecord->position->title ?? 'Officer' }}</span>
                        @endif
                        <x-admin.signatory-badges :user="$user" class="mt-1.5 max-w-[170px]" />
                    </div>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-400">Student no.</dt><dd class="text-gray-700">{{ $user->student_number ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-400">Program</dt><dd class="truncate text-right text-gray-700">{{ $user->program->name ?? 'No program' }}@if($user->year_level) · Yr {{ $user->year_level }}@endif</dd></div>
                        <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-400">Organization</dt><dd class="truncate text-right text-gray-700">{{ $user->organization->name ?? '—' }}</dd></div>
                    </dl>

                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-3">
                        <button wire:click.stop="openModal({{ $user->id }})"
                            class="flex-1 rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">View details</button>
                        @if ($user->trashed())
                            <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?"
                                class="flex-1 rounded-lg border border-emerald-200 bg-emerald-50 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                        @elseif (! $isAdminRow && $user->id !== auth()->id())
                            <button wire:click.stop="confirmDelete({{ $user->id }})"
                                class="flex-1 rounded-lg border border-red-200 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">Remove</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No users match your filters.</p>
            @endforelse
        </div>

        <div class="mb-12 mt-4">{{ $users->links() }}</div>

        {{-- ============ USER DETAILS MODAL ============ --}}
        @if ($showModal && $selectedUser)
            @php
                $u = $selectedUser;
                $fmt = fn ($d, $f = 'M d, Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
                $fullName = trim(implode(' ', array_filter([$u->first_name, $u->middle_name, $u->last_name, $u->suffix]))) ?: '—';
                $age = $u->date_of_birth ? \Illuminate\Support\Carbon::parse($u->date_of_birth)->age : null;
                $roleName = $u->role->role_name ?? 'User';
                $sigs = $u->signatoryAssignments->filter(fn ($a) => $a->role);

                $sections = [
                    'Personal information' => [
                        'Full name' => $fullName,
                        'Sex' => $u->sex ?: '—',
                        'Date of birth' => $fmt($u->date_of_birth) . ($age !== null ? " ({$age} yrs)" : ''),
                        'Contact number' => $u->contact_number ?: '—',
                        'Email' => $u->email ?: '—',
                        'Address' => $u->address ?: '—',
                    ],
                    'Academic profile' => [
                        'Student no.' => $u->student_number ?: '—',
                        'College' => $u->college ?: ($u->program->college->name ?? '—'),
                        'Program' => $u->program->name ?? '—',
                        'Year level' => $u->year_level ? 'Year ' . $u->year_level : '—',
                        'Section' => $u->section ?: '—',
                        'Academic status' => $u->academic_status ?: '—',
                        'Organization' => $u->organization->name ?? '—',
                        'Officer position' => $u->isOfficer() ? ($u->officerRecord->position->title ?? 'Officer') : '—',
                    ],
                    'Account' => [
                        'Role' => ucfirst($roleName),
                        'Account status' => $u->trashed() ? 'Removed' : ucfirst(str_replace('_', ' ', $u->account_status ?? 'inactive')),
                        'Email verified' => $u->email_verified_at ? $fmt($u->email_verified_at, 'M d, Y g:i A') : 'Not verified',
                        'E-signature' => $u->signature_path ? ($u->signature_enabled ? 'On file, enabled' : 'On file, disabled') : 'Not set up',
                        'Created' => $fmt($u->created_at, 'M d, Y g:i A'),
                        'Last updated' => $fmt($u->updated_at, 'M d, Y g:i A'),
                        ...($u->trashed() ? ['Removed on' => $fmt($u->deleted_at, 'M d, Y g:i A')] : []),
                    ],
                ];
                $wide = ['Address', 'Email', 'Program'];
            @endphp

            <div class="fixed inset-0 z-[9999] flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center sm:p-4"
                wire:click.self="closeModal" role="dialog" aria-modal="true" aria-label="User details">
                <div class="relative flex max-h-[92dvh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">

                    {{-- Header --}}
                    <div class="relative bg-gradient-to-br from-[#2A57B4] to-[#1d3f85] px-5 py-6 text-white sm:px-7">
                        <button wire:click="closeModal" aria-label="Close"
                                class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>

                        <div class="flex items-center gap-4 pr-8 sm:gap-5">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl shadow-lg ring-2 ring-white/40">
                                @if ($u->profile_picture)
                                    <img src="{{ asset('storage/' . $u->profile_picture) }}" class="h-full w-full object-cover" alt="">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-white/15 text-xl font-bold">
                                        {{ strtoupper(substr($u->first_name ?? 'U', 0, 1) . substr($u->last_name ?? '', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-lg font-semibold sm:text-xl">{{ $fullName }}</div>
                                <div class="truncate text-sm text-white/70">{{ $u->email }}</div>
                                <div class="mt-2.5 flex flex-wrap gap-1.5">
                                    @if ($u->trashed())
                                        <span class="rounded-full bg-gray-300/25 px-2.5 py-1 text-xs">Removed</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs {{ $u->account_status === 'active' ? 'bg-emerald-400/20 text-emerald-100' : 'bg-red-400/20 text-red-100' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $u->account_status === 'active' ? 'bg-emerald-300' : 'bg-red-300' }}"></span>
                                            {{ ucfirst(str_replace('_', ' ', $u->account_status ?? 'inactive')) }}
                                        </span>
                                    @endif
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-xs">{{ ucfirst($roleName) }}</span>
                                    @if ($u->isOfficer())
                                        <span class="rounded-full bg-amber-400/20 px-2.5 py-1 text-xs text-amber-100">{{ $u->officerRecord->position->title ?? 'Officer' }}</span>
                                    @endif
                                    @foreach ($sigs->where('is_active', true) as $a)
                                        <span class="rounded-full bg-violet-300/20 px-2.5 py-1 text-xs text-violet-100">{{ $a->role->label }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5 sm:p-7">
                        @foreach ($sections as $title => $fields)
                            <section>
                                <h3 class="mb-3 text-sm font-semibold text-gray-700">{{ $title }}</h3>
                                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl bg-gray-50/70 p-4 text-sm sm:grid-cols-2">
                                    @foreach ($fields as $label => $value)
                                        <div class="min-w-0 {{ in_array($label, $wide, true) ? 'sm:col-span-2' : '' }}">
                                            <dt class="mb-0.5 text-xs text-gray-400">{{ $label }}</dt>
                                            <dd class="break-words font-medium text-gray-800">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                        @endforeach

                        {{-- Signatory roles --}}
                        <section>
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">Signatory roles</h3>
                            @if ($sigs->isEmpty())
                                <p class="rounded-xl bg-gray-50/70 p-4 text-sm text-gray-400">This account holds no signatory role.</p>
                            @else
                                <ul class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-100">
                                    @foreach ($sigs as $a)
                                        @php
                                            $today = \Illuminate\Support\Carbon::today();
                                            [$st, $stCls] = match (true) {
                                                ! $a->is_active => ['Ended', 'bg-gray-100 text-gray-500'],
                                                $a->active_from && $a->active_from->gt($today) => ['Upcoming', 'bg-blue-50 text-blue-600'],
                                                $a->active_until && $a->active_until->lt($today) => ['Expired', 'bg-amber-50 text-amber-700'],
                                                default => ['Can sign now', 'bg-emerald-50 text-emerald-700'],
                                            };
                                        @endphp
                                        <li wire:key="modal-sig-{{ $a->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800">{{ $a->role->label }}</p>
                                                @if ($a->scope_name)<p class="text-xs text-gray-400">for {{ $a->scope_name }}</p>@endif
                                            </div>
                                            <div class="text-right">
                                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $stCls }}">{{ $st }}</span>
                                                <p class="mt-1 text-[11px] text-gray-400">{{ $a->active_from?->format('M j, Y') ?? 'Now' }} &rarr; {{ $a->active_until?->format('M j, Y') ?? 'Ongoing' }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                        @if ($u->trashed())
                            <button wire:click="restoreUser({{ $u->id }})" wire:confirm="Restore this user?"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 sm:py-2">Restore user</button>
                        @endif
                        <button wire:click="closeModal"
                                class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:py-2">Close</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ REMOVE USER MODAL ============ --}}
        @if($showDeleteModal && $userPendingDeletion)
            <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-black/60 sm:items-center sm:px-4"
                wire:click.self="cancelDelete" role="dialog" aria-modal="true">
                <div class="relative w-full max-w-md rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                    <button wire:click="cancelDelete" aria-label="Close"
                        class="absolute right-4 top-4 text-lg text-gray-400 transition hover:text-gray-700">&times;</button>

                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-2xl text-red-500">!</div>
                        <div class="min-w-0">
                            <h3 class="text-xl font-semibold text-gray-900">Remove user</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-500">Are you sure you want to remove this user?</p>
                            <p class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                <span class="font-semibold text-gray-900">{{ trim(($userPendingDeletion->first_name ?? '') . ' ' . ($userPendingDeletion->last_name ?? '')) ?: ('User #' . $userPendingDeletion->id) }}</span>
                                will be removed and can no longer log in. Their documents, transactions and audit records are kept.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                        <button wire:click="cancelDelete"
                            class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:py-2">Cancel</button>
                        <button wire:click="deleteUser"
                            class="rounded-lg bg-red-600 px-4 py-2.5 text-sm sm:py-2 font-semibold text-white transition hover:bg-red-700">Remove user</button>
                    </div>
                </div>
            </div>
        @endif

    @endif
</div>