@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $control = 'h-9 rounded-lg border-slate-300 bg-white py-0 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 cq-sm:text-sm [@media(pointer:coarse)]:h-11';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $iconAct = 'grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;

    // Roles as quiet tints (DESIGN.md pills: tint + deep text + 200 ring).
    $rolePill = fn (string $r) => match ($r) {
        'admin' => 'bg-violet-50 text-violet-800 ring-violet-200',
        'officer' => 'bg-[#fdece3] text-[#b4471a] ring-orange-200',
        'student' => 'bg-sky-50 text-[#1E4090] ring-sky-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $avatarTint = fn (string $r) => match ($r) {
        'admin' => 'bg-violet-50 text-violet-800',
        'officer' => 'bg-[#fdece3] text-[#b4471a]',
        default => 'bg-[#e2e9f7] text-[#1E4090]',
    };
    // The Workflow review page's search field and the Filters pop-up's selects
    $wfField = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
    $fsel = 'h-9 w-full py-0 text-sm [@media(pointer:coarse)]:h-11 ' . $wfField;
    $activeFilters = collect([$roleFilter, $statusFilter, $yearFilter])->filter(fn ($v) => (string) $v !== '')->count() + (int) $showRemoved;
@endphp

<x-admin.users-manager :active="$navKey">

    @if ($activeTab === 'org-roles')

        <livewire:admin.organization-role-manager />

    @else

        @if ($section === 'verifications')
            <livewire:admin.verification-management />
        @else

        <x-admin.alert :message="session('message')" type="success" />

        <div class="space-y-3">
            {{-- One row, as on Workflow review: Filters (role, status, year level, removed accounts), then the search --}}
            <div class="flex items-center gap-2">
                <x-admin.filters-popup :count="$activeFilters" reset="resetFilters">
                    <label class="block min-w-0">
                        <span class="mb-1 block text-xs font-medium text-slate-600">Role</span>
                        <select wire:model.live="roleFilter" class="{{ $fsel }}">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ ucfirst($role->role_name) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block min-w-0">
                        <span class="mb-1 block text-xs font-medium text-slate-600">Status</span>
                        <select wire:model.live="statusFilter" class="{{ $fsel }}">
                            <option value="">Any status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </label>
                    <label class="col-span-2 block">
                        <span class="mb-1 block text-xs font-medium text-slate-600">Year level</span>
                        <select wire:model.live="yearFilter" class="{{ $fsel }}">
                            <option value="">Any year</option>
                            <option value="1">1st year</option>
                            <option value="2">2nd year</option>
                            <option value="3">3rd year</option>
                            <option value="4">4th year</option>
                        </select>
                    </label>
                    <label class="col-span-2 flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <input type="checkbox" wire:model.live="showRemoved" class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        Show removed accounts
                    </label>
                </x-admin.filters-popup>

                <div class="relative min-w-0 flex-1">
                    <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email or student number" aria-label="Search accounts"
                           class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $wfField }}">
                </div>
            </div>

            {{-- Table: the Workflow review table --}}
            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity cq-md:block"
                 wire:loading.class="opacity-60" wire:target="search, roleFilter, statusFilter, yearFilter, showRemoved, resetFilters, gotoPage, nextPage, previousPage">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 text-xs">
                            <tr>
                                <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                                <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Name</th>
                                <th scope="col" class="hidden px-4 py-2.5 text-left font-medium text-slate-500 cq-lg:table-cell">Student no. and program</th>
                                <th scope="col" class="hidden px-4 py-2.5 text-left font-medium text-slate-500 cq-xl:table-cell">Organization</th>
                                <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Role</th>
                                <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Status</th>
                                <th scope="col" class="py-2.5 pl-4 pr-4 text-right font-medium text-slate-500"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($users as $user)
                                @php
                                    $fullName = ($user->full_name ?? '') ?: '-';
                                    $roleName = \Illuminate\Support\Str::lower($user->role->role_name ?? 'unknown');
                                    $isAdminRow = $roleName === 'admin';
                                @endphp
                                <tr wire:key="user-{{ $user->id }}" wire:click="openModal({{ $user->id }})"
                                    class="cursor-pointer transition-colors hover:bg-slate-50 {{ $user->trashed() ? 'text-slate-500' : '' }}">
                                    <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @include('livewire.admin.partials.feedback-avatar', ['u' => $user, 'label' => $fullName, 'size' => 'h-8 w-8 text-xs'])
                                            <div class="min-w-0">
                                                <button type="button" wire:click.stop="openModal({{ $user->id }})"
                                                        class="block max-w-[240px] truncate rounded text-left font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}" title="{{ $fullName }}">{{ $fullName }}</button>
                                                <p class="mt-0.5 max-w-[240px] truncate text-xs text-slate-500" title="{{ $user->email }}"><span class="tabular-nums cq-lg:hidden">{{ $user->student_number ? $user->student_number . ' · ' : '' }}</span>{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-3 cq-lg:table-cell">
                                        <p class="tabular-nums text-slate-700">{{ $user->student_number ?? '–' }}</p>
                                        <p class="mt-0.5 max-w-[200px] truncate text-xs text-slate-500">{{ $user->program->name ?? 'No program' }}@if ($user->year_level) · Year {{ $user->year_level }}@endif</p>
                                    </td>
                                    <td class="hidden px-4 py-3 cq-xl:table-cell">
                                        <p class="max-w-[180px] truncate text-slate-700">{{ $user->organization->name ?? '–' }}</p>
                                        @if ($user->isOfficer())
                                            <p class="mt-0.5 max-w-[180px] truncate text-xs text-slate-500">{{ $user->officerRecord->position->title ?? 'Officer' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $rolePill($roleName) }}">{{ ucfirst($roleName) }}</span>
                                        <x-admin.signatory-badges :user="$user" class="mt-1 max-w-[220px]" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($user->trashed())
                                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Removed</span>
                                        @else
                                            @php $on = $user->account_status === 'active'; @endphp
                                            <span class="inline-flex items-center gap-1.5 text-xs {{ $on ? 'text-slate-700' : 'text-rose-700' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $on ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ ucfirst(str_replace('_', ' ', $user->account_status ?? 'inactive')) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2 pl-4 pr-4" wire:click.stop>
                                        <div class="flex items-center justify-end gap-0.5">
                                            @if ($user->trashed())
                                                <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?"
                                                        class="{{ $btn }} !min-h-8 !px-3 text-emerald-700 hover:bg-emerald-50">Restore</button>
                                            @elseif (! $isAdminRow && $user->id !== auth()->id())
                                                <button wire:click="confirmDelete({{ $user->id }})" title="Remove user" aria-label="Remove {{ $fullName }}"
                                                        class="{{ $iconAct }} hover:bg-rose-50 hover:text-rose-700">
                                                    <i class='bx bx-trash text-lg' aria-hidden="true"></i>
                                                </button>
                                            @endif
                                            <button wire:click="openModal({{ $user->id }})" title="View details" aria-label="View details for {{ $fullName }}"
                                                    class="{{ $iconAct }} hover:bg-[#f0f4fb] hover:text-[#2A57B4]">
                                                <i class='bx bx-chevron-right text-xl' aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-16 text-center text-sm text-slate-500">No accounts match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cards on narrow screens, as on Workflow review --}}
            <ul class="grid gap-3 transition-opacity cq-md:hidden" aria-label="Accounts" wire:loading.class="opacity-60">
                @forelse ($users as $user)
                    @php
                        $fullName = ($user->full_name ?? '') ?: '-';
                        $roleName = \Illuminate\Support\Str::lower($user->role->role_name ?? 'unknown');
                        $isAdminRow = $roleName === 'admin';
                    @endphp
                    <li wire:key="user-card-{{ $user->id }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                        <button type="button" wire:click.stop="openModal({{ $user->id }})" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg text-left {{ $focus }}">
                            @include('livewire.admin.partials.feedback-avatar', ['u' => $user, 'label' => $fullName, 'size' => 'h-10 w-10 text-[13px]'])
                            <span class="min-w-0">
                                <span class="flex items-center gap-1.5">
                                    <span class="truncate text-[15px] font-semibold text-slate-900">{{ $fullName }}</span>
                                    @if ($user->trashed())
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" title="Removed"></span>
                                    @elseif ($user->account_status !== 'active')
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500" title="{{ ucfirst($user->account_status ?? 'inactive') }}"></span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">
                                    {{ ucfirst($roleName) }} · {{ $user->student_number ?? $user->email }}{{ $user->isOfficer() ? ' · ' . ($user->officerRecord->position->title ?? 'Officer') : '' }}
                                </span>
                            </span>
                        </button>
                        @if ($user->trashed())
                            <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Restore this user?"
                                    class="{{ $btn }} shrink-0 !px-3 text-emerald-700 hover:bg-emerald-50">Restore</button>
                        @elseif (! $isAdminRow && $user->id !== auth()->id())
                            <button wire:click.stop="confirmDelete({{ $user->id }})" aria-label="Remove {{ $fullName }}"
                                    class="{{ $iconAct }} shrink-0 hover:bg-rose-50 hover:text-rose-700">
                                <i class='bx bx-trash text-lg' aria-hidden="true"></i>
                            </button>
                        @endif
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">No accounts match these filters.</li>
                @endforelse
            </ul>

            @if ($users->hasPages())
                <div>{{ $users->links() }}</div>
            @endif
        </div>

        {{-- ============ USER DETAILS (settings-style rows: label left, value right) ============ --}}
        @if ($showModal && $selectedUser)
            @php
                $u = $selectedUser;
                $fmt = fn ($d, $f = 'M d, Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '–';
                $fullName = $u->full_name ?: '–';
                $age = $u->date_of_birth ? \Illuminate\Support\Carbon::parse($u->date_of_birth)->age : null;
                $roleName = \Illuminate\Support\Str::lower($u->role->role_name ?? 'user');
                $sigs = $u->signatoryAssignments->filter(fn ($a) => $a->role);
                $active = ! $u->trashed() && $u->account_status === 'active';

                $sections = [
                    'Personal information' => [
                        'Full name' => $fullName,
                        'Sex' => $u->sex ?: '–',
                        'Date of birth' => $fmt($u->date_of_birth) . ($age !== null ? " ({$age} yrs)" : ''),
                        'Contact number' => $u->contact_number ?: '–',
                        'Email' => $u->email ?: '–',
                        'Address' => $u->address ?: '–',
                    ],
                    'Academic profile' => [
                        'Student no.' => $u->student_number ?: '–',
                        'College' => $u->college ?: ($u->program->college->name ?? '–'),
                        'Program' => $u->program->name ?? '–',
                        'Year and section' => trim(($u->year_level ? 'Year ' . $u->year_level : '') . ($u->section ? ' · ' . $u->section : '')) ?: '–',
                        'Academic status' => $u->academic_status ?: '–',
                        'Organization' => $u->organization->name ?? '–',
                        'Officer position' => $u->isOfficer() ? ($u->officerRecord->position->title ?? 'Officer') : '–',
                    ],
                    'Account' => [
                        'Role' => ucfirst($roleName),
                        'Account status' => $u->trashed() ? 'Removed' : ucfirst(str_replace('_', ' ', $u->account_status ?? 'inactive')),
                        'Email verified' => $u->email_verified_at ? $fmt($u->email_verified_at, 'M d, Y g:i A') : 'Not verified',
                        'E-signature' => $u->signature_path ? 'On file' : 'Not set up',
                        'Created' => $fmt($u->created_at, 'M d, Y g:i A'),
                        'Last updated' => $fmt($u->updated_at, 'M d, Y g:i A'),
                        ...($u->trashed() ? ['Removed on' => $fmt($u->deleted_at, 'M d, Y g:i A')] : []),
                    ],
                ];
            @endphp

            @teleport('body')
            <div class="fixed inset-0 z-[9999] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
                 wire:click.self="closeModal" wire:keydown.escape.window="closeModal" role="dialog" aria-modal="true" aria-labelledby="ud-title">
                <div class="relative flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">

                    {{-- Header --}}
                    <div class="flex items-start gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                            @if ($u->profile_picture)
                                <img src="{{ asset('storage/' . $u->profile_picture) }}" class="h-full w-full object-cover" alt="">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-base font-semibold {{ $avatarTint($roleName) }}">
                                    {{ strtoupper(substr($u->first_name ?? 'U', 0, 1) . substr($u->last_name ?? '', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 id="ud-title" class="truncate text-lg font-semibold tracking-[-0.012em] text-slate-900">{{ $fullName }}</h2>
                            <p class="truncate text-sm text-slate-500">{{ $u->email }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $rolePill($roleName) }}">{{ ucfirst($roleName) }}</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $active ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $u->trashed() ? 'Removed' : ucfirst(str_replace('_', ' ', $u->account_status ?? 'inactive')) }}
                                </span>
                                @if ($u->isOfficer())
                                    <span class="inline-flex rounded-full bg-[#fdece3] px-2.5 py-0.5 text-xs font-medium text-[#b4471a] ring-1 ring-orange-200">{{ $u->officerRecord->position->title ?? 'Officer' }}</span>
                                @endif
                            </div>
                        </div>
                        <button wire:click="closeModal" aria-label="Close"
                                class="-mr-1 grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 {{ $focus }}">
                            <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 sm:px-6">
                        @foreach ($sections as $title => $fields)
                            <section class="border-b border-slate-200 py-4 last:border-b-0">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                                <dl class="mt-2 text-sm">
                                    @foreach ($fields as $label => $value)
                                        <div class="grid grid-cols-[minmax(0,9rem)_minmax(0,1fr)] gap-3 py-1.5 sm:grid-cols-[11rem_minmax(0,1fr)]">
                                            <dt class="text-slate-500">{{ $label }}</dt>
                                            <dd class="break-words text-slate-900 {{ $value === '–' ? 'text-slate-400' : '' }}">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                        @endforeach

                        <section class="py-4">
                            <h3 class="text-sm font-semibold text-slate-900">Signatory roles</h3>
                            @if ($sigs->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">This account holds no signatory role.</p>
                            @else
                                <ul class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200">
                                    @foreach ($sigs as $a)
                                        @php
                                            $today = \Illuminate\Support\Carbon::today();
                                            [$st, $stCls] = match (true) {
                                                ! $a->is_active => ['Ended', 'bg-slate-100 text-slate-600 ring-slate-200'],
                                                $a->active_from && $a->active_from->gt($today) => ['Upcoming', 'bg-sky-50 text-[#1E4090] ring-sky-200'],
                                                $a->active_until && $a->active_until->lt($today) => ['Expired', 'bg-amber-50 text-amber-800 ring-amber-200'],
                                                default => ['Can sign now', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
                                            };
                                        @endphp
                                        <li wire:key="modal-sig-{{ $a->id }}" class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm">
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-900">{{ $a->role->label }}</p>
                                                <p class="text-xs tabular-nums text-slate-500">
                                                    @if ($a->scope_name) For {{ $a->scope_name }} · @endif
                                                    {{ $a->active_from?->format('M j, Y') ?? 'Now' }} to {{ $a->active_until?->format('M j, Y') ?? 'ongoing' }}
                                                </p>
                                            </div>
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $stCls }}">{{ $st }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end sm:px-6">
                        <button wire:click="closeModal" class="{{ $btn }} border border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-50">Close</button>
                        @if ($u->trashed())
                            <button wire:click="restoreUser({{ $u->id }})" wire:confirm="Restore this user?" class="{{ $btn }} bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]">Restore user</button>
                        @endif
                    </div>
                </div>
            </div>
            @endteleport
        @endif

        {{-- ============ REMOVE USER ============ --}}
        @if ($showDeleteModal && $userPendingDeletion)
            @teleport('body')
            <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-slate-900/50 sm:items-center sm:px-4"
                 wire:click.self="cancelDelete" role="alertdialog" aria-modal="true" aria-labelledby="rm-title" aria-describedby="rm-lead">
                <div class="relative w-full max-w-md rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bx bx-user-x text-xl" aria-hidden="true"></i></span>
                        <div class="min-w-0">
                            <h3 id="rm-title" class="text-lg font-semibold tracking-[-0.012em] text-slate-900">Remove this account?</h3>
                            <p id="rm-lead" class="mt-1 text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ ($userPendingDeletion->full_name ?? '') ?: ('User #' . $userPendingDeletion->id) }}</span>
                                will no longer be able to sign in. Their documents, transactions and audit records are kept, and you can restore the account later.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button wire:click="cancelDelete" class="{{ $btn }} border border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-50">Cancel</button>
                        <button wire:click="deleteUser" class="{{ $btn }} bg-rose-600 text-white shadow-sm hover:bg-rose-700">Remove account</button>
                    </div>
                </div>
            </div>
            @endteleport
        @endif

        @endif {{-- accounts / verifications --}}

    @endif
</x-admin.users-manager>
