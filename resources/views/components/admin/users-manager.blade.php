{{-- resources/views/components/admin/users-manager.blade.php
     Shell for the Users and Accounts Manager, laid out like a settings page: a menu of every
     section on the left, the open section on the right.
     <x-admin.users-manager active="verifications"> ...page... </x-admin.users-manager>
     Keys: accounts, verifications, organizations, programs, positions, officers,
           sig-people, sig-roles, sig-invites ("Add a person" is a button on Who can sign?, so sig-add shows Who can sign?).

     Layout responds to the space this shell gets (container queries, `cq-*`), not the screen,
     so opening or collapsing the main sidebar reflows it correctly. Pages inside use `cq-*` too.
     Narrow: the open section's name is a button that slides a sheet of every section up from
     the bottom (like switching accounts on TikTok). --}}
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
            'sig-roles' => ['Roles', $org('signatories', 'roles'), 'bx-shield-quarter', 'The signing roles documents can ask for.'],
            'sig-invites' => ['Invitations', $org('signatories', 'invites'), 'bx-envelope', 'Invitations sent to people who do not have an account yet.'],
        ],
    ];

    $items = collect($groups)->flatMap(fn ($items) => $items);
    $active = $active === 'sig-add' ? 'sig-people' : ($items->has($active) ? $active : 'accounts');
@endphp

<div class="cq mx-auto w-full max-w-[1400px]"
     x-data="{ active: @js($active), sheet: false, items: @js($items->map(fn ($i) => ['label' => $i[0], 'icon' => $i[2], 'note' => $i[3]])) }"
     @users-manager-nav.window="active = $event.detail"
     @keydown.escape.window="sheet = false">

    <div class="flex flex-col gap-4 cq-lg:flex-row cq-lg:items-start cq-lg:gap-6">

        {{-- ============ SETTINGS MENU ============ --}}
        <aside class="w-full shrink-0 cq-lg:sticky cq-lg:top-4 cq-lg:w-72">

            {{-- Narrow: the open section, tap to switch --}}
            <div class="cq-lg:hidden">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Users and Accounts Manager</p>
                <button type="button" @click="sheet = true" aria-haspopup="dialog" :aria-expanded="sheet"
                        class="-ml-1 flex max-w-full items-center gap-2.5 rounded-xl py-1 pl-1 pr-2 text-left transition active:bg-gray-100">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-white">
                        <i class="bx text-xl" :class="items[active]?.icon" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 truncate text-xl font-bold tracking-tight text-gray-900" x-text="items[active]?.label">{{ $items[$active][0] }}</span>
                    <i class='bx bx-chevron-down shrink-0 text-2xl text-gray-500 transition-transform' :class="sheet && 'rotate-180'" aria-hidden="true"></i>
                </button>
                <p class="mt-1 text-sm text-gray-500" x-text="items[active]?.note">{{ $items[$active][3] }}</p>
            </div>

            {{-- Narrow: the sheet of sections, slid up from the bottom --}}
            <template x-teleport="body">
                <div x-show="sheet" x-cloak class="fixed inset-0 z-[9998]" role="dialog" aria-modal="true" aria-label="Switch section">
                    <div x-show="sheet" x-transition.opacity.duration.200ms @click="sheet = false" class="absolute inset-0 bg-gray-900/50"></div>

                    <div x-show="sheet"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                         class="absolute inset-x-0 bottom-0 mx-auto flex max-h-[85dvh] max-w-lg flex-col rounded-t-3xl bg-white shadow-2xl">
                        <div class="flex justify-center pb-1 pt-3" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-gray-300"></span></div>
                        <p class="px-5 pb-2 pt-1 text-center text-base font-bold text-gray-900">Users and Accounts Manager</p>

                        <nav aria-label="Users and accounts" class="min-h-0 flex-1 overflow-y-auto px-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                            @foreach ($groups as $group => $groupItems)
                                <div class="{{ $loop->first ? '' : 'mt-2 border-t border-gray-100 pt-2' }}">
                                    <p class="px-2 pb-1 pt-1 text-xs font-semibold text-gray-400">{{ $group }}</p>
                                    @foreach ($groupItems as $key => [$label, $url, $icon])
                                        <a href="{{ $url }}" wire:navigate @click="sheet = false"
                                           :aria-current="active === @js($key) ? 'page' : null"
                                           class="flex items-center gap-3 rounded-2xl px-2 py-2.5 transition active:bg-gray-100"
                                           :class="active === @js($key) ? 'bg-[#2A57B4]/10' : ''">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                                  :class="active === @js($key) ? 'bg-[#2A57B4] text-white' : 'bg-gray-100 text-gray-600'">
                                                <i class='bx {{ $icon }} text-xl' aria-hidden="true"></i>
                                            </span>
                                            <span class="min-w-0 flex-1 truncate text-[15px]"
                                                  :class="active === @js($key) ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'">{{ $label }}</span>
                                            <i class='bx bx-check text-2xl text-[#2A57B4]' x-show="active === @js($key)" aria-hidden="true"></i>
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </template>

            {{-- Wide: the full menu --}}
            <div class="hidden rounded-2xl border border-gray-200/70 bg-white p-3 shadow-sm cq-lg:block">
                <div class="px-2 pb-2 pt-1">
                    <h1 class="text-xl font-bold tracking-tight text-gray-900">Users and Accounts Manager</h1>
                    <p class="mt-0.5 text-xs text-gray-500">Accounts, organizations and who can sign.</p>
                </div>

                <nav aria-label="Users and accounts">
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
        <section class="min-w-0 flex-1" aria-labelledby="um-section-title">
            {{-- Wide only: on narrow the section's name is already the picker above. --}}
            <header class="mb-5 hidden items-start gap-3 cq-lg:flex">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-[#2A57B4]">
                    <i class="bx text-2xl" :class="items[active]?.icon" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="um-section-title" class="text-xl font-bold tracking-tight text-gray-900" x-text="items[active]?.label">{{ $items[$active][0] }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500" x-text="items[active]?.note">{{ $items[$active][3] }}</p>
                </div>
            </header>

            <div class="cq">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
