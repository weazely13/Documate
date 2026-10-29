@php
    $pill = 'shrink-0 rounded-full border-gray-200 bg-gray-50 py-1.5 pl-3 pr-8 text-xs font-medium text-gray-700 focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $avatarColors = ['admin' => 'bg-red-100 text-red-600', 'officer' => 'bg-blue-100 text-blue-600', 'student' => 'bg-emerald-100 text-emerald-600'];
    $roleColors = ['admin' => 'bg-red-50 text-red-700', 'officer' => 'bg-blue-50 text-blue-700', 'student' => 'bg-emerald-50 text-emerald-700'];
@endphp

<x-admin.users-manager :active="$navKey">

    @if ($activeTab === 'org-roles')

        <livewire:admin.organization-role-manager />

    @else

        <x-admin.alert :message="session('message')" type="success" />

        <div class="overflow-hidden rounded-2xl border border-gray-200/70 bg-white shadow-sm">

            {{-- Search and filters --}}
            <div class="flex flex-col gap-2 border-b border-gray-100 p-2.5 cq-lg:flex-row cq-lg:items-center">
                <div class="relative min-w-0 cq-lg:flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                    </svg>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email or student number" aria-label="Search accounts"
                        class="w-full rounded-full border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-base focus:border-[#2A57B4] focus:ring-[#2A57B4] cq-sm:text-sm">
                </div>

                {{-- One row; scrolls sideways when it does not fit --}}
                <div class="flex min-w-0 items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <select wire:model.live="roleFilter" aria-label="Filter by role" class="{{ $pill }}">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ ucfirst($role->role_name) }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="statusFilter" aria-label="Filter by status" class="{{ $pill }}">
                        <option value="">Any status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <select wire:model.live="yearFilter" aria-label="Filter by year level" class="{{ $pill }}">
                        <option value="">Any year</option>
                        <option value="1">1st year</option>
                        <option value="2">2nd year</option>
                        <option value="3">3rd year</option>
                        <option value="4">4th year</option>
                    </select>
                    <label class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700">
                        <input type="checkbox" wire:model.live="showRemoved" class="h-3.5 w-3.5 rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                        Removed
                    </label>
                </div>
            </div>
            <p class="border-b border-gray-100 px-4 py-2 text-xs text-gray-500">{{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}</p>

            {{-- Wide: table --}}
            <div class="hidden overflow-x-auto cq-lg:block">
                <table class="w-full text-sm">
                    <thead class="text-xs font-medium text-gray-500">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left font-medium">Account</th>
                            <th class="px-4 py-3 text-left font-medium">Academic</th>
                            <th class="hidden px-4 py-3 text-left font-medium cq-xl:table-cell">Organization</th>
                            <th class="px-4 py-3 text-left font-medium">Role and status</th>
                            <th class="px-5 py-3 text-right font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
                            @php
                                $fullName = ($user->full_name ?? '') ?: '-';
                                $roleName = \Illuminate\Support\Str::lower($user->role->role_name ?? 'unknown');
                                $isAdminRow = $roleName === 'admin';
                                $initials = strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1));
                            @endphp
                            <tr wire:key="user-{{ $user->id }}" class="align-middle transition-colors hover:bg-gray-50/70">
                                <td class="px-5 py-3.5">
                                    <button type="button" wire:click.stop="openModal({{ $user->id }})" class="flex items-center gap-3 text-left">
                                        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                            @if($user->profile_picture)
                                                <img src="{{ asset('storage/' . $user->profile_picture) }}" class="h-full w-full object-cover" alt="">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-xs font-semibold {{ $avatarColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">{{ $initials }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block max-w-[220px] truncate font-semibold text-gray-900 hover:text-[#2A57B4]" title="{{ $fullName }}">{{ $fullName }}</span>
                                            <span class="block max-w-[220px] truncate text-xs text-gray-500" title="{{ $user->email }}">{{ $user->email }}</span>
                                        </span>
                                    </button>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-gray-800">{{ $user->student_number ?? '—' }}</div>
                                    <div class="max-w-[200px] truncate text-xs text-gray-500">
                                        {{ $user->program->name ?? 'No program' }}@if($user->year_level) · Year {{ $user->year_level }}@endif
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3.5 cq-xl:table-cell">
                                    <div class="max-w-[180px] truncate text-gray-800">{{ $user->organization->name ?? '—' }}</div>
                                    @if ($user->isOfficer())
                                        <div class="max-w-[180px] truncate text-xs text-amber-700">{{ $user->officerRecord->position->title ?? 'Officer' }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($roleName) }}</span>
                                        @if ($user->trashed())
                                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Removed</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs {{ $user->account_status === 'active' ? 'text-gray-600' : 'text-red-600' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $user->account_status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>{{ ucfirst($user->account_status ?? 'inactive') }}
                                            </span>
                                        @endif
                                    </div>
                                    <x-admin.signatory-badges :user="$user" class="mt-1.5 max-w-[220px]" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click.stop="openModal({{ $user->id }})" title="View details" aria-label="View details for {{ $fullName }}"
                                            class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-[#2A57B4]">
                                            <i class='bx bx-show text-lg' aria-hidden="true"></i>
                                        </button>
                                        @if ($user->trashed())
                                            <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?"
                                                class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                                        @elseif (! $isAdminRow && $user->id !== auth()->id())
                                            <button wire:click.stop="confirmDelete({{ $user->id }})" title="Remove user" aria-label="Remove {{ $fullName }}"
                                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-red-50 hover:text-red-600">
                                                <i class='bx bx-trash text-lg' aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center text-sm text-gray-500">No accounts match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Narrow: one row per account --}}
            <ul class="divide-y divide-gray-100 cq-lg:hidden">
                @forelse ($users as $user)
                    @php
                        $fullName = ($user->full_name ?? '') ?: '-';
                        $roleName = \Illuminate\Support\Str::lower($user->role->role_name ?? 'unknown');
                        $isAdminRow = $roleName === 'admin';
                        $initials = strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1));
                    @endphp
                    <li wire:key="user-card-{{ $user->id }}" class="flex items-center gap-3 px-4 py-3.5">
                        <button type="button" wire:click.stop="openModal({{ $user->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                            <span class="h-11 w-11 shrink-0 overflow-hidden rounded-full">
                                @if($user->profile_picture)
                                    <img src="{{ asset('storage/' . $user->profile_picture) }}" class="h-full w-full object-cover" alt="">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-sm font-semibold {{ $avatarColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">{{ $initials }}</span>
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-gray-900">{{ $fullName }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ $user->student_number ?? $user->email }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($roleName) }}</span>
                                    @if ($user->trashed())
                                        <span class="text-[11px] text-gray-500">Removed</span>
                                    @elseif ($user->account_status !== 'active')
                                        <span class="text-[11px] text-red-600">{{ ucfirst($user->account_status ?? 'inactive') }}</span>
                                    @endif
                                    @if ($user->isOfficer())
                                        <span class="text-[11px] text-amber-700">{{ $user->officerRecord->position->title ?? 'Officer' }}</span>
                                    @endif
                                </span>
                            </span>
                        </button>
                        @if ($user->trashed())
                            <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?"
                                class="shrink-0 rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Restore</button>
                        @elseif (! $isAdminRow && $user->id !== auth()->id())
                            <button wire:click.stop="confirmDelete({{ $user->id }})" aria-label="Remove {{ $fullName }}"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-red-50 hover:text-red-600">
                                <i class='bx bx-trash text-lg' aria-hidden="true"></i>
                            </button>
                        @endif
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-sm text-gray-500">No accounts match these filters.</li>
                @endforelse
            </ul>
        </div>

        @if ($users->hasPages())
            <div class="mb-12 mt-4">{{ $users->links() }}</div>
        @endif

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

            @teleport('body')
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
            @endteleport
        @endif

        {{-- ============ REMOVE USER MODAL ============ --}}
        @if($showDeleteModal && $userPendingDeletion)
            @teleport('body')
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
                                <span class="font-semibold text-gray-900">{{ ($userPendingDeletion->full_name ?? '') ?: ('User #' . $userPendingDeletion->id) }}</span>
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
            @endteleport
        @endif

    @endif
</x-admin.users-manager>
