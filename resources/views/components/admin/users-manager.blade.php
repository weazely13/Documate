{{-- resources/views/components/admin/users-manager.blade.php
     Shell for the Users and Accounts Manager: one sidebar for every page in it, the page in the slot.
     <x-admin.users-manager active="verifications"> ...page... </x-admin.users-manager>
     Keys: accounts, verifications, organizations, programs, positions, officers,
           sig-people, sig-add, sig-roles, sig-invites. --}}
@props(['active'])

@php
    $org = fn (string $panel, ?string $sig = null) => route('admin.users', array_filter(['tab' => 'org-roles', 'panel' => $panel, 'sig' => $sig]));

    $groups = [
        'Accounts' => [
            'accounts' => ['Manage Accounts', route('admin.users'), 'bx-user', 'Student, officer, signatory and admin accounts.'],
            'verifications' => ['Account Verifications', route('admin.verification'), 'bx-id-card', 'Verification periods and each student\'s status.'],
        ],
        'Organizations' => [
            'organizations' => ['Manage Organizations', $org('organizations'), 'bx-buildings', 'Organizations and the programs or year level they cover.'],
            'programs' => ['Manage Programs', $org('programs'), 'bx-book-bookmark', 'Degree programs and the college each one belongs to.'],
            'positions' => ['Manage Officer Positions', $org('positions'), 'bx-medal', 'Titles an officer can hold, such as President or Auditor.'],
            'officers' => ['Assign Officers', $org('officers'), 'bx-user-check', 'Make eligible students officers of their organization.'],
        ],
        'Signatories' => [
            'sig-people' => ['Who can sign?', $org('signatories', 'people'), 'bx-pen', 'Everyone who can sign documents, and in which role.'],
            'sig-add' => ['Add a person', $org('signatories', 'add'), 'bx-user-plus', 'Give an existing account a signing role, or invite someone new.'],
            'sig-roles' => ['Roles', $org('signatories', 'roles'), 'bx-shield-quarter', 'The signing roles documents can ask for.'],
            'sig-invites' => ['Invitations', $org('signatories', 'invites'), 'bx-envelope', 'Invitations sent to people without an account yet.'],
        ],
    ];

    $items = collect($groups)->flatMap(fn ($items) => $items);
    $current = $items[$active] ?? $items['accounts'];
@endphp

<div class="mx-auto max-w-7xl"
     x-data="{ active: @js($active), items: @js($items->map(fn ($i) => ['label' => $i[0], 'note' => $i[3]])) }"
     @users-manager-nav.window="active = $event.detail">

    <header class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Users and Accounts Manager</h1>
        <p class="mt-1 text-sm text-gray-500" x-text="items[active]?.note">{{ $current[3] }}</p>
    </header>

    <div class="flex flex-col gap-6 lg:flex-row">

        {{-- Phones and tablets: one dropdown instead of the sidebar --}}
        <label class="lg:hidden">
            <span class="sr-only">Section</span>
            <select onchange="Livewire.navigate(this.value)"
                    class="w-full rounded-xl border-gray-200 py-3 text-base font-medium text-gray-800 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                @foreach ($groups as $group => $groupItems)
                    <optgroup label="{{ $group }}">
                        @foreach ($groupItems as $key => [$label, $url])
                            <option value="{{ $url }}" @selected($key === $active)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </label>

        {{-- Sidebar --}}
        <aside class="hidden w-60 shrink-0 lg:block">
            <nav aria-label="Users and accounts" class="sticky top-6 space-y-6">
                @foreach ($groups as $group => $groupItems)
                    <div>
                        <p class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $group }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($groupItems as $key => [$label, $url, $icon])
                                <li>
                                    <a href="{{ $url }}" wire:navigate
                                       :aria-current="active === @js($key) ? 'page' : null"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition"
                                       :class="active === @js($key)
                                           ? 'bg-[#2A57B4]/10 font-semibold text-[#2A57B4]'
                                           : 'font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900'">
                                        <i class='bx {{ $icon }} text-lg' aria-hidden="true"></i>
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>
</div>
