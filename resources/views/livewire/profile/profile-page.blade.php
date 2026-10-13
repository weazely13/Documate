<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">

    {{-- Flash messages --}}
    @if (session('profile_saved'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('profile_saved') }}
        </div>
    @endif

    @if (session('password_saved'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">
            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('password_saved') }}
        </div>
    @endif

    {{-- ============ HERO / IDENTITY HEADER ============ --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-[#2A57B4] shadow-lg shadow-[#2A57B4]/20">
        <div class="pointer-events-none absolute inset-0 opacity-10"
             style="background-image:radial-gradient(circle at 1px 1px, white 1px, transparent 1px); background-size:20px 20px;"></div>

        <div class="relative px-5 py-8 sm:px-8 sm:py-10">
            <div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:items-center sm:gap-6 sm:text-left">

                {{-- Avatar --}}
                <div class="relative shrink-0">
                    <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-white/10 ring-4 ring-white/30 sm:h-28 sm:w-28">
                        @if ($editing && $photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover" alt="New photo preview">
                        @elseif ($this->photoUrl)
                            <img src="{{ $this->photoUrl }}" class="h-full w-full object-cover" alt="{{ $this->fullName }}">
                        @else
                            <span class="text-2xl font-semibold text-white sm:text-3xl">{{ $this->initials }}</span>
                        @endif
                    </div>

                    @if ($editing)
                        <label for="photo-upload"
                               class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-white text-[#2A57B4] shadow-md ring-2 ring-blue-200 transition hover:bg-blue-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.822 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                            </svg>
                            <input id="photo-upload" type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                    @endif

                    <div wire:loading wire:target="photo" class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40">
                        <svg class="h-6 w-6 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                        </svg>
                    </div>
                </div>

                {{-- Name / meta --}}
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-xl font-bold text-white sm:text-2xl">{{ $this->fullName }}</h1>
                    <p class="mt-1 text-sm text-blue-100">{{ $this->academicLine }}</p>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                        <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white ring-1 ring-inset ring-white/25">
                            {{ $role_name }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $this->statusBadgeClass }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ $this->statusLabel }}
                        </span>
                        @if ($student_number)
                            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white ring-1 ring-inset ring-white/25">
                                {{ $student_number }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex w-full shrink-0 justify-center gap-2 sm:w-auto sm:justify-end">
                    @if (! $editing)
                        <button type="button" wire:click="toggleEdit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-[#2A57B4] shadow transition hover:bg-blue-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                            Edit Profile
                        </button>
                    @else
                        <button type="button" wire:click="toggleEdit" wire:loading.attr="disabled" wire:target="save"
                                class="rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/30 transition hover:bg-white/20">
                            Cancel
                        </button>
                        <button type="submit" form="profile-form" wire:loading.attr="disabled" wire:target="save,photo"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-[#2A57B4] shadow transition hover:bg-blue-50 disabled:opacity-60">
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">Save Changes</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="profile-form" wire:submit="save">
        <div class="grid grid-cols-1 gap-6 {{ $this->isAdmin ? 'lg:grid-cols-2' : 'lg:grid-cols-3' }}">

            {{-- ============ PERSONAL INFORMATION ============ --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-5 py-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Personal Information</h2>
                        <p class="text-xs text-gray-500">Your basic personal details</p>
                    </div>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">First Name</label>
                            @if ($editing)
                                <input wire:model="first_name" id="first_name" type="text" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $first_name ?: '—' }}">
                                    {{ $first_name ?: '—' }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="last_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Last Name</label>
                            @if ($editing)
                                <input wire:model="last_name" id="last_name" type="text" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $last_name ?: '—' }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="middle_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Middle Name</label>
                            @if ($editing)
                                <input wire:model="middle_name" id="middle_name" type="text" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('middle_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $middle_name ?: '—' }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="suffix" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Suffix</label>
                            @if ($editing)
                                <input wire:model="suffix" id="suffix" type="text" placeholder="Jr., III, etc." class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('suffix') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $suffix ?: '—' }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="sex" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Sex</label>
                            @if ($editing)
                                <select wire:model="sex" id="sex"
                                        class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                                    <option value="">Select…</option>
                                    @foreach ($sexOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('sex') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $sex ?: '—' }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Date of Birth</label>
                            @if ($editing)
                                <input wire:model="date_of_birth" id="date_of_birth" type="date" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $date_of_birth ? \Illuminate\Support\Carbon::parse($date_of_birth)->format('M d, Y') : '—' }}
                                </div>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <label for="contact_number" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Contact Number</label>
                            @if ($editing)
                                <input wire:model="contact_number" id="contact_number" type="text" placeholder="09XXXXXXXXX" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                @error('contact_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    {{ $contact_number ?: '—' }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ ACADEMIC PROFILE (Hidden for Admins) ============ --}}
            @if (! $this->isAdmin)
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-gray-100 px-5 py-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Academic Profile</h2>
                            <p class="text-xs text-gray-500">College, program, and standing</p>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="college_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    College
                                    @if ($isOfficerLocked)
                                        <span class="ml-1 normal-case font-normal text-amber-600">(locked — officer)</span>
                                    @endif
                                </label>
                                @if ($editing && ! $isOfficerLocked)
                                    <select wire:model.live="college_id" id="college_id"
                                            class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                                        <option value="">Select…</option>
                                        @foreach ($this->collegeOptions as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('college_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $college ?: '—' }}">
                                        {{ $college ?: '—' }}
                                    </div>
                                @endif
                            </div>

                            <div class="sm:col-span-2">
                                <label for="program_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Program</label>
                                @if ($editing && ! $isOfficerLocked)
                                    <select wire:model.live="program_id" id="program_id" @disabled(! $college_id)
                                            class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] disabled:bg-gray-100">
                                        <option value="">{{ $college_id ? 'Select…' : 'Select a college first' }}</option>
                                        @foreach ($this->programOptions as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('program_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $this->selectedProgramName ?: '—' }}">
                                        {{ $this->selectedProgramName ?: '—' }}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="year_level" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Year Level</label>
                                @if ($editing)
                                    <select wire:model="year_level" id="year_level"
                                            class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                                        <option value="">Select…</option>
                                        @foreach ($yearLevelOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('year_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                        {{ $yearLevelOptions[$year_level] ?? ($year_level ?: '—') }}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="academic_status" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Status</label>
                                @if ($editing)
                                    <select wire:model="academic_status" id="academic_status"
                                            class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                                        <option value="">Select…</option>
                                        @foreach ($academicStatusOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('academic_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                        {{ $academic_status ?: '—' }}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="organization_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Organization
                                    @if ($isOfficerLocked)
                                        <span class="ml-1 normal-case font-normal text-amber-600">(locked — officer)</span>
                                    @endif
                                </label>
                                @if ($editing)
                                    @if ($isOfficerLocked)
                                        <div class="mt-1.5 truncate rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800" title="{{ $this->selectedOrganizationName ?: '—' }}">
                                            {{ $this->selectedOrganizationName ?: '—' }}
                                        </div>
                                        <p class="mt-1 text-xs text-gray-400">You're an officer of this organization, so it can't be changed here. Contact the admin if this needs to change.</p>
                                    @else
                                        <select wire:model="organization_id" id="organization_id" @disabled(! $program_id)
                                                class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] disabled:bg-gray-100">
                                            <option value="">{{ $program_id ? 'None' : 'Select a program first' }}</option>
                                            @foreach ($this->organizationOptions as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('organization_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @endif
                                @else
                                    <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $this->selectedOrganizationName ?: '—' }}">
                                        {{ $this->selectedOrganizationName ?: '—' }}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="section" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Section</label>
                                @if ($editing)
                                    <input wire:model="section" id="section" type="text" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                                    @error('section') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $section ?: '—' }}">
                                        {{ $section ?: '—' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============ ACCOUNT DETAILS ============ --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-5 py-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Account Details</h2>
                        <p class="text-xs text-gray-500">Login, status, and security</p>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    @if ($student_number)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Student Number</label>
                                <div class="mt-1 flex items-center justify-between gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                    <span class="min-w-0 truncate" title="{{ $student_number }}">{{ $student_number }}</span>
                                    <span class="shrink-0 rounded bg-gray-200/60 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-600">Locked</span>
                                </div>
                        </div>
                    @endif

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Email Address</label>
                        @if ($editing)
                            <input wire:model="email" id="email" type="email" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" />
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <div class="mt-1 break-all rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                {{ $email ?: '—' }}
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Account Status</label>
                        <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                            {{ $this->statusLabel }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Role</label>
                        <div class="mt-1 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900" title="{{ $role_name }}">
                            {{ $role_name }}
                        </div>
                    </div>

                    {{-- Password Section --}}
                    <div class="border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Password</label>
                            <button type="button" wire:click="togglePasswordForm"
                                    class="text-xs font-semibold text-[#2A57B4] hover:text-blue-700">
                                {{ $showPasswordForm ? 'Cancel' : 'Change Password' }}
                            </button>
                        </div>

                        @if (! $showPasswordForm)
                            <div class="mt-1 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                ••••••••
                            </div>
                        @else
                            <div class="mt-3 space-y-3 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200/60">
                                
                                {{-- Current Password --}}
                                <div x-data="{ show: false }">
                                    <label for="current_password" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Current Password</label>
                                    <div class="relative mt-1.5">
                                        <input wire:model="current_password" id="current_password" :type="show ? 'text' : 'password'" 
                                            class="block w-full rounded-md border-gray-300 pr-10 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" autocomplete="current-password" />
                                        
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                            {{-- Eye Icon (Visible) --}}
                                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            {{-- Eye Off Icon (Hidden) --}}
                                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- New Password --}}
                                <div x-data="{ show: false }">
                                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">New Password</label>
                                    <div class="relative mt-1.5">
                                        <input wire:model="new_password" id="new_password" :type="show ? 'text' : 'password'" 
                                            class="block w-full rounded-md border-gray-300 pr-10 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" autocomplete="new-password" />
                                        
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('new_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Confirm New Password --}}
                                <div x-data="{ show: false }">
                                    <label for="new_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Confirm New Password</label>
                                    <div class="relative mt-1.5">
                                        <input wire:model="new_password_confirmation" id="new_password_confirmation" :type="show ? 'text' : 'password'" 
                                            class="block w-full rounded-md border-gray-300 pr-10 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]" autocomplete="new-password" />
                                        
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.52 10.52 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <button type="button" wire:click="updatePassword" wire:loading.attr="disabled" wire:target="updatePassword"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                                    <span wire:loading wire:target="updatePassword">Updating…</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>