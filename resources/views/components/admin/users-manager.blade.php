{{-- resources/views/components/admin/users-manager.blade.php
     Shell for the Users and Accounts Manager, laid out like a settings page (Untitled UI's account
     settings, in DocuMate's Notion/Stripe system): a page title over a hairline, a quiet text menu on
     the left, and the open section on the right under its own title and hairline.
     <x-admin.users-manager active="verifications"> ...page... </x-admin.users-manager>
     Keys: accounts, verifications, organizations, programs, positions, officers,
           sig-people, sig-roles, sig-invites ("Add a person" is a button on Who can sign?, so sig-add shows Who can sign?).

     Layout responds to the space this shell gets (container queries, `cq-*`), not the screen.
     Narrow: the menu folds into one select-like button that opens a sheet of every section. --}}
@props(['active'])

@php
    $org = fn (string $panel, ?string $sig = null) => route('admin.users', array_filter(['tab' => 'org-roles', 'panel' => $panel, 'sig' => $sig]));

    $groups = [
        'Accounts' => [
            'accounts' => ['Accounts', route('admin.users'), 'bx-user', 'Find, review and remove student, officer, signatory and admin accounts.'],
            'verifications' => ['Verifications', route('admin.users', ['view' => 'verifications']), 'bx-id-card', 'Open a verification period and see who has verified their account.'],
        ],
        'School calendar' => [
            'semesters' => ['Semesters', route('admin.semesters'), 'bx-calendar-alt', 'Each semester runs between a start and an end date. The one that contains today becomes current by itself.'],
        ],
        'Organizations' => [
            'organizations' => ['Organizations', $org('organizations'), 'bx-buildings', 'Create organizations and link them to programs or a year level.'],
            'programs' => ['Programs', $org('programs'), 'bx-book-bookmark', 'Degree programs and the college each one belongs to.'],
            'positions' => ['Officer positions', $org('positions'), 'bx-medal', 'Titles an officer can hold, such as President or Auditor.'],
            'officers' => ['Officers', $org('officers'), 'bx-user-check', 'Make eligible students officers of their organization.'],
        ],
        'Signatories' => [
            'sig-people' => ['Who can sign', $org('signatories', 'people'), 'bx-pen', 'Everyone who can sign documents, and in which role.'],
            'sig-roles' => ['Signing roles', $org('signatories', 'roles'), 'bx-shield-quarter', 'The signing roles documents can ask for.'],
            'sig-invites' => ['Invitations', $org('signatories', 'invites'), 'bx-envelope', 'Invitations sent to people who do not have an account yet.'],
        ],
    ];

    $items = collect($groups)->flatMap(fn ($items) => $items);
    $active = $active === 'sig-add' ? 'sig-people' : ($items->has($active) ? $active : 'accounts');
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
@endphp

<div class="cq mx-auto w-full max-w-[1280px] pb-16"
     x-data="{ active: @js($active), sheet: false, items: @js($items->map(fn ($i) => ['label' => $i[0], 'icon' => $i[2], 'note' => $i[3]])) }"
     @users-manager-nav.window="active = $event.detail"
     @keydown.escape.window="sheet = false">

    {{-- Page title --}}
    <header class="border-b border-slate-200 pb-4">
        <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 cq-sm:text-2xl">Users and accounts</h1>
        <p class="mt-0.5 text-sm text-slate-500">Accounts, organizations and who can sign.</p>
    </header>

    <div class="mt-5 flex flex-col gap-5 cq-lg:flex-row cq-lg:items-start cq-lg:gap-10">

        {{-- ============ MENU ============ --}}
        <aside class="w-full shrink-0 cq-lg:sticky cq-lg:top-4 cq-lg:w-52">

            {{-- Narrow: one select-like button --}}
            <button type="button" @click="sheet = true" aria-haspopup="dialog" :aria-expanded="sheet"
                    class="flex h-11 w-full items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3 text-left text-sm shadow-sm cq-lg:hidden {{ $focus }}">
                <i class="bx text-lg text-[#2A57B4]" :class="items[active]?.icon" aria-hidden="true"></i>
                <span class="min-w-0 flex-1 truncate font-medium text-slate-900" x-text="items[active]?.label">{{ $items[$active][0] }}</span>
                <i class='bx bx-chevron-down text-xl text-slate-400' aria-hidden="true"></i>
            </button>

            {{-- Narrow: the sheet of sections --}}
            <template x-teleport="body">
                <div x-show="sheet" x-cloak class="fixed inset-0 z-[9998]" role="dialog" aria-modal="true" aria-label="Switch section">
                    <div x-show="sheet" x-transition.opacity.duration.200ms @click="sheet = false" class="absolute inset-0 bg-slate-900/50"></div>
                    <div x-show="sheet"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                         class="absolute inset-x-0 bottom-0 mx-auto flex max-h-[85dvh] max-w-lg flex-col rounded-t-2xl bg-white shadow-2xl">
                        <div class="flex justify-center pb-1 pt-3" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-slate-300"></span></div>
                        <p class="border-b border-slate-200 px-5 pb-3 pt-1 text-[15px] font-semibold text-slate-900">Users and accounts</p>
                        <nav aria-label="Users and accounts" class="min-h-0 flex-1 overflow-y-auto px-3 pb-[max(1rem,env(safe-area-inset-bottom))] pt-2">
                            @foreach ($groups as $group => $groupItems)
                                <p class="px-2.5 pb-1 pt-3 text-xs font-medium text-slate-500">{{ $group }}</p>
                                @foreach ($groupItems as $key => [$label, $url, $icon])
                                    <a href="{{ $url }}" wire:navigate @click="sheet = false"
                                       :aria-current="active === @js($key) ? 'page' : null"
                                       class="flex min-h-11 items-center gap-2.5 rounded-md px-2.5 text-[15px] {{ $focus }}"
                                       :class="active === @js($key) ? 'bg-[#e2e9f7] font-medium text-[#24499A]' : 'text-slate-700 active:bg-[#ecebe7]'">
                                        <i class='bx {{ $icon }} text-lg' :class="active === @js($key) ? 'text-[#2A57B4]' : 'text-slate-400'" aria-hidden="true"></i>
                                        <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                                        <i class='bx bx-check text-xl text-[#2A57B4]' x-show="active === @js($key)" aria-hidden="true"></i>
                                    </a>
                                @endforeach
                            @endforeach
                        </nav>
                    </div>
                </div>
            </template>

            {{-- Wide: a quiet text menu --}}
            <nav aria-label="Users and accounts" class="hidden cq-lg:block">
                @foreach ($groups as $group => $groupItems)
                    <p class="px-2.5 pb-1 text-xs font-medium text-slate-500 {{ $loop->first ? '' : 'pt-4' }}">{{ $group }}</p>
                    <ul class="space-y-px">
                        @foreach ($groupItems as $key => [$label, $url, $icon])
                            <li>
                                <a href="{{ $url }}" wire:navigate
                                   :aria-current="active === @js($key) ? 'page' : null"
                                   class="flex items-center gap-2.5 rounded-md px-2.5 py-[7px] text-sm transition-colors {{ $focus }}"
                                   :class="active === @js($key) ? 'bg-[#e2e9f7] font-medium text-[#24499A]' : 'text-slate-700 hover:bg-[#ecebe7]'">
                                    <i class='bx {{ $icon }} text-base' :class="active === @js($key) ? 'text-[#2A57B4]' : 'text-slate-400'" aria-hidden="true"></i>
                                    <span class="min-w-0 truncate">{{ $label }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
        </aside>

        {{-- ============ OPEN SECTION ============ --}}
        <section class="min-w-0 flex-1" aria-labelledby="um-section-title">
            <header class="mb-5 border-b border-slate-200 pb-4">
                <h2 id="um-section-title" class="text-lg font-semibold tracking-[-0.012em] text-slate-900" x-text="items[active]?.label">{{ $items[$active][0] }}</h2>
                <p class="mt-0.5 text-sm text-slate-500" x-text="items[active]?.note">{{ $items[$active][3] }}</p>
            </header>

            <div class="cq">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
