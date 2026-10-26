{{-- resources/views/components/admin/users-manager.blade.php
     Shell for the Users and Accounts Manager, laid out like a settings page: a menu of every
     section on the left, the open section on the right.
     <x-admin.users-manager active="verifications"> ...page... </x-admin.users-manager>
     Keys: accounts, verifications, organizations, programs, positions, officers,
           sig-people, sig-add, sig-roles, sig-invites.

     Layout responds to the space this shell gets (container queries, `cq-*`), not the screen,
     so opening or collapsing the main sidebar reflows it correctly. Pages inside use `cq-*` too. --}}
@props(['active'])

@php
    $org = fn (string $panel, ?string $sig = null) => route('admin.users', array_filter(['tab' => 'org-roles', 'panel' => $panel, 'sig' => $sig]));

    $groups = [
        'Accounts' => [
            'accounts' => ['Manage Accounts', route('admin.users'), 'bx-user', 'Find, review and remove student, officer, signatory and admin accounts.'],
            'verifications' => ['Account Verifications', route('admin.verification'), 'bx-id-card', 'Open a verification period and see who has verified their account.'],
        ],
        'Organizations' => [
            'organizations' => ['Manage Organizations', $org('organizations'), 'bx-buildings', 'Create organizations and link them to programs or a year level.'],
            'programs' => ['Manage Programs', $org('programs'), 'bx-book-bookmark', 'Degree programs and the college each one belongs to.'],
            'positions' => ['Manage Officer Positions', $org('positions'), 'bx-medal', 'Titles an officer can hold, such as President or Auditor.'],
            'officers' => ['Assign Officers', $org('officers'), 'bx-user-check', 'Make eligible students officers of their organization.'],
        ],
        'Signatories' => [
            'sig-people' => ['Who can sign?', $org('signatories', 'people'), 'bx-pen', 'Everyone who can sign documents, and in which role.'],
            'sig-add' => ['Add a person', $org('signatories', 'add'), 'bx-user-plus', 'Give an existing account a signing role, or invite someone new.'],
            'sig-roles' => ['Roles', $org('signatories', 'roles'), 'bx-shield-quarter', 'The signing roles documents can ask for.'],
            'sig-invites' => ['Invitations', $org('signatories', 'invites'), 'bx-envelope', 'Invitations sent to people who do not have an account yet.'],
        ],
    ];

    $items = collect($groups)->flatMap(fn ($items) => $items);
    $active = $items->has($active) ? $active : 'accounts';
@endphp

<div class="cq mx-auto w-full max-w-[1400px]"
     x-data="{ active: @js($active), items: @js($items->map(fn ($i) => ['label' => $i[0], 'icon' => $i[2], 'note' => $i[3]])) }"
     @users-manager-nav.window="active = $event.detail">

    <div class="flex flex-col gap-5 cq-lg:flex-row cq-lg:items-start cq-lg:gap-6">

        {{-- ============ SETTINGS MENU ============ --}}
        {{-- Wide: a sticky panel. Narrow: a compact card with a section picker. --}}
        <aside class="w-full shrink-0 cq-lg:sticky cq-lg:top-4 cq-lg:w-72">
            <div class="rounded-2xl border border-gray-200/70 bg-white p-3 shadow-sm">
                <div class="px-2 pb-2 pt-1">
                    <h1 class="text-lg font-bold tracking-tight text-gray-900 cq-lg:text-xl">Users and Accounts Manager</h1>
                    <p class="mt-0.5 hidden text-xs text-gray-500 cq-lg:block">Accounts, organizations and who can sign.</p>
                </div>

                {{-- Narrow: one picker --}}
                <label class="block cq-lg:hidden">
                    <span class="sr-only">Section</span>
                    <select onchange="Livewire.navigate(this.value)"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 text-base font-medium text-gray-800 focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:text-sm">
                        @foreach ($groups as $group => $groupItems)
                            <optgroup label="{{ $group }}">
                                @foreach ($groupItems as $key => [$label, $url])
                                    <option value="{{ $url }}" @selected($key === $active)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                {{-- Wide: the full menu --}}
                <nav aria-label="Users and accounts" class="hidden cq-lg:block">
                    @foreach ($groups as $group => $groupItems)
                        <div class="{{ $loop->first ? 'mt-2' : 'mt-3 border-t border-gray-100 pt-3' }}">
                            <p class="px-2 pb-1 text-[13px] font-semibold text-gray-500">{{ $group }}</p>
                            <ul class="space-y-0.5">
                                @foreach ($groupItems as $key => [$label, $url, $icon])
                                    <li>
                                        <a href="{{ $url }}" wire:navigate
                                           :aria-current="active === @js($key) ? 'page' : null"
                                           class="group flex items-center gap-3 rounded-xl px-2 py-2 text-[15px] transition"
                                           :class="active === @js($key) ? 'bg-[#2A57B4]/10 font-semibold text-gray-900' : 'font-medium text-gray-700 hover:bg-gray-100'">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition"
                                                  :class="active === @js($key) ? 'bg-[#2A57B4] text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200'">
                                                <i class='bx {{ $icon }} text-lg' aria-hidden="true"></i>
                                            </span>
                                            <span class="min-w-0 truncate">{{ $label }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- ============ OPEN SECTION ============ --}}
        <section class="cq min-w-0 flex-1" aria-labelledby="um-section-title">
            <header class="mb-5 flex items-start gap-3">
                <span class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-[#2A57B4] cq-sm:flex">
                    <i class="bx text-2xl" :class="items[active]?.icon" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="um-section-title" class="text-xl font-bold tracking-tight text-gray-900" x-text="items[active]?.label">{{ $items[$active][0] }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500" x-text="items[active]?.note">{{ $items[$active][3] }}</p>
                </div>
            </header>

            {{ $slot }}
        </section>
    </div>
</div>
