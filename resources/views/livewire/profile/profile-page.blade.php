@php
    $user = auth()->user()->loadMissing('signatoryAssignments.role');
    $isStudentSide = ! $this->isStaffAccount;
    $done = $this->completeness;

    $tabs = array_filter([
        'profile' => ['Profile', 'bx-user', 'Name, contact and photo'],
        'academic' => $isStudentSide ? ['Academic', 'bx-book-open', 'College, program and section'] : null,
        'password' => ['Password', 'bx-lock-alt', 'Change how you sign in'],
        'signature' => ['E-signature', 'bx-pen', 'Your signature for documents'],
        'account' => ['Account', 'bx-shield-quarter', 'Status, role and verification'],
    ]);

    // After a failed save, open the tab that holds the first problem
    $academicKeys = ['college_id', 'program_id', 'organization_id', 'year_level', 'section', 'academic_status'];
    $errorTab = null;
    if ($errors->any()) {
        $keys = $errors->keys();
        $errorTab = match (true) {
            collect($keys)->contains(fn ($k) => in_array($k, ['current_password', 'new_password', 'new_password_confirmation'], true)) => 'password',
            collect($keys)->contains(fn ($k) => in_array($k, ['signatureData', 'signature_password'], true)) => 'signature',
            collect($keys)->diff($academicKeys)->isEmpty() => 'academic',
            default => 'profile',
        };
    }

    $label = 'block text-[13px] font-medium text-[#273951]';
    $input = 'mt-1.5 block h-10 w-full rounded-lg border-[#d4d1cb] bg-white px-3 text-sm text-[#0d253d] shadow-sm placeholder:text-[#9a978f] focus:border-[#2a57b4] focus:ring-2 focus:ring-[#2a57b4]/15 disabled:cursor-not-allowed disabled:bg-[#f5f4f2] disabled:text-[#66707f]';
    $locked = 'mt-1.5 flex h-10 w-full items-center gap-2 rounded-lg border border-[#e7e5e1] bg-[#f5f4f2] px-3 text-sm text-[#4a5568]';
    $err = 'mt-1.5 flex items-center gap-1 text-xs text-[#dc2626]';
    $req = '<span class="text-[#dc2626]" aria-hidden="true">*</span>';
    $btnPrimary = 'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#2a57b4] px-4 text-sm font-medium text-white shadow-sm transition hover:bg-[#24499a] active:bg-[#1e4090] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2a57b4] focus-visible:ring-offset-2 disabled:opacity-60';
    $btnSecondary = 'inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-[#e7e5e1] bg-white px-4 text-sm font-medium text-[#273951] shadow-sm transition hover:bg-[#f5f4f2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2a57b4] focus-visible:ring-offset-2 disabled:opacity-60';
@endphp

<div class="mx-auto max-w-6xl"
     x-data="{
        tabs: @js(array_keys($tabs)),
        tab: 'profile',
        go(t) { this.tab = t; history.replaceState(null, '', '#' + t); },
        fromHash() {
            const h = location.hash.slice(1);
            if (this.tabs.includes(h)) this.tab = h;
        },
        init() { this.fromHash(); },
     }"
     @hashchange.window="fromHash()">

    {{-- Title --}}
    <div class="mb-5 sm:mb-6">
        <h1 class="text-2xl font-semibold tracking-[-0.02em] text-[#0d253d]">Account settings</h1>
        <p class="mt-1 text-sm text-[#66707f]">Your details fill in your forms automatically, so keep them current.</p>
    </div>

    @if ($errorTab)
        <div wire:key="error-tab-{{ md5(json_encode($errors->keys())) }}" x-init="go(@js($errorTab))" class="hidden"></div>
    @endif

    <div class="grid grid-cols-[minmax(0,1fr)] gap-5 lg:grid-cols-[15.5rem_minmax(0,1fr)] lg:gap-6">

        {{-- ============ SETTINGS MENU ============ --}}
        <aside class="min-w-0 lg:sticky lg:top-4 lg:self-start">
            {{-- Phones and tablets: a scrolling tab strip --}}
            <nav class="-mx-4 overflow-x-auto border-b border-[#e7e5e1] px-4 [scrollbar-width:none] md:-mx-6 md:px-6 lg:hidden [&::-webkit-scrollbar]:hidden" aria-label="Settings sections">
                <div class="flex gap-1" role="tablist">
                    @foreach ($tabs as $key => [$name, $icon])
                        <button type="button" role="tab" @click="go('{{ $key }}')" :aria-selected="(tab === '{{ $key }}').toString()"
                                x-effect="if (tab === '{{ $key }}') $el.parentElement.parentElement.scrollTo({ left: $el.offsetLeft - 16, behavior: 'smooth' })"
                                class="relative flex shrink-0 items-center gap-1.5 px-3 pb-3 pt-1 text-sm font-medium transition"
                                :class="tab === '{{ $key }}' ? 'text-[#1e4090]' : 'text-[#66707f] hover:text-[#0d253d]'">
                            <i class="bx {{ $icon }} text-base" aria-hidden="true"></i>{{ $name }}
                            <span x-show="tab === '{{ $key }}'" class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-[#2a57b4]"></span>
                        </button>
                    @endforeach
                </div>
            </nav>

            {{-- Desktop: menu card --}}
            <div class="hidden overflow-hidden rounded-xl border border-[#e7e5e1] bg-white shadow-[0_1px_2px_rgba(13,37,61,0.04)] lg:block">
                <nav class="p-2" aria-label="Settings sections">
                    @foreach ($tabs as $key => [$name, $icon, $hint])
                        <button type="button" @click="go('{{ $key }}')" :aria-current="tab === '{{ $key }}' ? 'page' : null"
                                class="group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition"
                                :class="tab === '{{ $key }}' ? 'bg-[#e2e9f7]' : 'hover:bg-[#f5f4f2]'">
                            <span x-show="tab === '{{ $key }}'" class="absolute -left-2 top-2 bottom-2 w-[3px] rounded-r-full bg-[#2a57b4]" aria-hidden="true"></span>
                            <i class="bx {{ $icon }} text-lg" :class="tab === '{{ $key }}' ? 'text-[#2a57b4]' : 'text-[#9a978f] group-hover:text-[#4a5568]'" aria-hidden="true"></i>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium" :class="tab === '{{ $key }}' ? 'text-[#1e4090]' : 'text-[#273951]'">{{ $name }}</span>
                                <span class="block truncate text-xs text-[#66707f]">{{ $hint }}</span>
                            </span>
                        </button>
                    @endforeach
                </nav>

                {{-- How complete the profile is --}}
                <div class="border-t border-[#e7e5e1] p-4">
                    <div class="flex items-baseline justify-between">
                        <p class="text-[13px] font-medium text-[#273951]">Profile details</p>
                        <p class="text-[13px] font-semibold tabular-nums {{ $done['percent'] === 100 ? 'text-[#059669]' : 'text-[#0d253d]' }}">{{ $done['percent'] }}%</p>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#ecebe7]" role="progressbar" aria-valuenow="{{ $done['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Profile details complete">
                        <div class="h-full rounded-full {{ $done['percent'] === 100 ? 'bg-[#059669]' : 'bg-[#2a57b4]' }} transition-[width] duration-500" style="width: {{ $done['percent'] }}%"></div>
                    </div>
                    @if ($done['missing'])
                        <p class="mt-2 text-xs leading-relaxed text-[#66707f]">Still missing: {{ implode(', ', $done['missing']) }}.</p>
                    @else
                        <p class="mt-2 text-xs text-[#66707f]">Everything your forms need is filled in.</p>
                    @endif
                </div>
            </div>
        </aside>

        {{-- ============ PANEL ============ --}}
        <section class="min-w-0 rounded-xl border border-[#e7e5e1] bg-white shadow-[0_1px_2px_rgba(13,37,61,0.04)]">

            @foreach (['profile_saved', 'password_saved'] as $flash)
                @if (session($flash))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition.opacity
                         class="mx-4 mt-4 flex items-center gap-2.5 rounded-lg bg-[#d9f3e1] px-3.5 py-2.5 text-sm text-[#065f46] sm:mx-6 sm:mt-6" role="status">
                        <i class="bx bx-check-circle text-lg text-[#059669]" aria-hidden="true"></i>
                        <span class="flex-1">{{ session($flash) }}</span>
                        <button type="button" @click="show = false" class="grid h-6 w-6 place-items-center rounded-md text-[#065f46]/70 hover:bg-white/50" aria-label="Dismiss"><i class="bx bx-x text-lg"></i></button>
                    </div>
                @endif
            @endforeach

            <form id="profile-form" wire:submit="save">

                {{-- ---------- PROFILE ---------- --}}
                <div x-show="tab === 'profile'" class="p-4 sm:p-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-6">
                        <div class="relative h-24 w-24 shrink-0 sm:h-28 sm:w-28">
                            <div class="h-full w-full overflow-hidden rounded-full bg-[#dcecfa] ring-4 ring-white shadow-[0_6px_16px_-6px_rgba(13,37,61,0.3)]">
                                @if ($this->photoUrl)
                                    <img src="{{ $this->photoUrl }}" class="h-full w-full object-cover" alt="{{ $this->fullName }}">
                                @else
                                    <span class="grid h-full w-full place-items-center text-3xl font-semibold text-[#2a57b4]">{{ $this->initials }}</span>
                                @endif
                            </div>
                            <label for="photo-upload" class="absolute bottom-0.5 right-0.5 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-[#2a57b4] text-white ring-[3px] ring-white transition hover:bg-[#24499a]" title="Upload a new photo">
                                <i class="bx bx-camera text-lg" aria-hidden="true"></i>
                                <span class="sr-only">Upload a new photo</span>
                            </label>
                            <div wire:loading.flex wire:target="photo,removePhoto" class="absolute inset-0 items-center justify-center rounded-full bg-[#0d253d]/45">
                                <i class="bx bx-loader-alt animate-spin text-2xl text-white" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-lg font-semibold tracking-[-0.012em] text-[#0d253d]">{{ $this->fullName }}</p>
                            <p class="mt-0.5 text-sm text-[#66707f]">{{ $this->academicLine }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <label for="photo-upload" class="{{ $btnPrimary }} cursor-pointer">
                                    <i class="bx bx-upload text-base" aria-hidden="true"></i> Upload
                                </label>
                                @if ($profile_picture)
                                    <button type="button" wire:click="removePhoto" wire:confirm="Remove your profile photo?" class="{{ $btnSecondary }}">Remove photo</button>
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-[#66707f]">JPG, PNG or WebP, up to 2 MB. It saves as soon as you pick it.</p>
                            <input id="photo-upload" type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                            @error('photo') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="my-6 border-t border-[#e7e5e1]"></div>

                    <h2 class="text-[15px] font-semibold text-[#0d253d]">Personal details</h2>
                    <div class="mt-4 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="{{ $label }}">First name {!! $req !!}</label>
                            <input wire:model="first_name" id="first_name" type="text" autocomplete="given-name" class="{{ $input }}">
                            @error('first_name') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name" class="{{ $label }}">Last name {!! $req !!}</label>
                            <input wire:model="last_name" id="last_name" type="text" autocomplete="family-name" class="{{ $input }}">
                            @error('last_name') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="middle_name" class="{{ $label }}">Middle name</label>
                            <input wire:model="middle_name" id="middle_name" type="text" autocomplete="additional-name" class="{{ $input }}">
                            @error('middle_name') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="suffix" class="{{ $label }}">Suffix</label>
                            <input wire:model="suffix" id="suffix" type="text" placeholder="Jr., III" autocomplete="honorific-suffix" class="{{ $input }}">
                            @error('suffix') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>

                        <fieldset>
                            <legend class="{{ $label }}">Sex @if ($isStudentSide){!! $req !!}@endif</legend>
                            <div class="mt-1.5 grid grid-cols-2 gap-2">
                                @foreach ($sexOptions as $option)
                                    <label class="flex h-10 cursor-pointer items-center gap-2.5 rounded-lg border px-3 text-sm transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2a57b4]/30"
                                           :class="$wire.sex === @js($option) ? 'border-[#2a57b4] bg-[#f0f4fb] text-[#1e4090]' : 'border-[#d4d1cb] text-[#273951] hover:bg-[#fafaf9]'">
                                        <input type="radio" wire:model="sex" value="{{ $option }}" class="h-4 w-4 border-[#d4d1cb] text-[#2a57b4] focus:ring-0 focus:ring-offset-0">
                                        {{ $option }}
                                    </label>
                                @endforeach
                            </div>
                            @error('sex') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </fieldset>
                        <div>
                            <label for="date_of_birth" class="{{ $label }}">Date of birth @if ($isStudentSide){!! $req !!}@endif</label>
                            <input wire:model="date_of_birth" id="date_of_birth" type="date" max="{{ now()->toDateString() }}" autocomplete="bday" class="{{ $input }}">
                            @error('date_of_birth') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <h2 class="mt-8 text-[15px] font-semibold text-[#0d253d]">Contact</h2>
                    <div class="mt-4 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div>
                            <label for="email" class="{{ $label }}">Email {!! $req !!}</label>
                            <input wire:model.live.debounce.500ms="email" id="email" type="email" autocomplete="email" placeholder="you@gmail.com" class="{{ $input }}">
                            @error('email')
                                <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p>
                            @else
                                @unless ($role_name === 'Signatory')
                                    <p class="mt-1.5 text-xs text-[#66707f]">Gmail, Yahoo or Outlook.</p>
                                @endunless
                            @enderror
                        </div>
                        <div>
                            <label for="contact_number" class="{{ $label }}">Mobile number @if ($isStudentSide){!! $req !!}@endif</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 mt-1.5 flex items-center gap-1.5 border-r border-[#e7e5e1] pl-3 pr-2.5 text-sm text-[#66707f]">
                                    <span class="text-xs font-medium">PH</span>
                                </span>
                                <input wire:model="contact_number" id="contact_number" type="tel" inputmode="tel" autocomplete="tel" placeholder="09XX XXX XXXX" class="{{ $input }} pl-14 tabular-nums">
                            </div>
                            @error('contact_number') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                        @if ($student_number)
                            <div>
                                <span class="{{ $label }}">Student number</span>
                                <div class="{{ $locked }}" title="Your student number can't be changed">
                                    <span class="flex-1 truncate tabular-nums">{{ $student_number }}</span>
                                    <i class="bx bx-lock-alt text-[#9a978f]" aria-hidden="true"></i>
                                </div>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <label for="address" class="{{ $label }}">Home address</label>
                            <textarea wire:model="address" id="address" rows="3" maxlength="250" autocomplete="street-address"
                                      placeholder="House no., street, barangay, city or municipality, province"
                                      class="{{ str_replace('h-10', 'min-h-[5.5rem] py-2.5', $input) }} resize-y"></textarea>
                            @error('address') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- ---------- ACADEMIC ---------- --}}
                @if ($isStudentSide)
                    <div x-show="tab === 'academic'" x-cloak class="p-4 sm:p-6">
                        <h2 class="text-[15px] font-semibold text-[#0d253d]">Academic details</h2>
                        <p class="mt-1 text-sm text-[#66707f]">Used on clearances and forms that ask for your program, year and section.</p>

                        @if ($isOfficerLocked)
                            <div class="mt-4 flex gap-2.5 rounded-lg bg-[#fef3c7] px-3.5 py-3 text-sm text-[#78350f]">
                                <i class="bx bx-lock-alt mt-0.5 text-base" aria-hidden="true"></i>
                                <p>You're an officer of your organization, so your college, program and organization are locked. Ask the VPSD office if one of them needs to change.</p>
                            </div>
                        @endif

                        <div class="mt-5 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="college_id" class="{{ $label }}">College {!! $req !!}</label>
                                @if ($isOfficerLocked)
                                    <div class="{{ $locked }}"><span class="flex-1 truncate">{{ $college ?: 'Not set' }}</span><i class="bx bx-lock-alt text-[#9a978f]"></i></div>
                                @else
                                    <select wire:model.live="college_id" id="college_id" class="{{ $input }}">
                                        <option value="">Choose your college</option>
                                        @foreach ($this->collegeOptions as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('college_id') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="program_id" class="{{ $label }}">Program {!! $req !!}</label>
                                @if ($isOfficerLocked)
                                    <div class="{{ $locked }}"><span class="flex-1 truncate">{{ $this->selectedProgramName ?: 'Not set' }}</span><i class="bx bx-lock-alt text-[#9a978f]"></i></div>
                                @else
                                    <select wire:model.live="program_id" id="program_id" @disabled(! $college_id) class="{{ $input }}">
                                        <option value="">{{ $college_id ? 'Choose your program' : 'Choose a college first' }}</option>
                                        @foreach ($this->programOptions as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('program_id') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="year_level" class="{{ $label }}">Year level {!! $req !!}</label>
                                <select wire:model="year_level" id="year_level" class="{{ $input }}">
                                    <option value="">Choose</option>
                                    @foreach ($yearLevelOptions as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                                @error('year_level') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="section" class="{{ $label }}">Section</label>
                                <input wire:model="section" id="section" type="text" placeholder="e.g. A" class="{{ $input }}">
                                @error('section') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="academic_status" class="{{ $label }}">Academic status {!! $req !!}</label>
                                <select wire:model="academic_status" id="academic_status" class="{{ $input }}">
                                    <option value="">Choose</option>
                                    @foreach ($academicStatusOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('academic_status') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="organization_id" class="{{ $label }}">Organization</label>
                                @if ($isOfficerLocked)
                                    <div class="{{ $locked }}"><span class="flex-1 truncate">{{ $this->selectedOrganizationName ?: 'None' }}</span><i class="bx bx-lock-alt text-[#9a978f]"></i></div>
                                @else
                                    <select wire:model="organization_id" id="organization_id" @disabled(! $program_id) class="{{ $input }}">
                                        <option value="">{{ $program_id ? 'None' : 'Choose a program first' }}</option>
                                        @foreach ($this->organizationOptions as $org)
                                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('organization_id') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Save bar for Profile and Academic --}}
                <div x-show="tab === 'profile' || tab === 'academic'"
                     class="sticky bottom-0 z-10 flex flex-col-reverse gap-2 rounded-b-xl border-t border-[#e7e5e1] bg-white py-3 pl-4 pr-[4.75rem] sm:flex-row sm:items-center sm:justify-end sm:py-4 sm:pl-6 sm:pr-24">
                    @if ($errors->any() && ! in_array($errorTab, ['password', 'signature'], true))
                        <p class="text-xs text-[#dc2626] sm:mr-auto" role="alert"><i class="bx bx-error-circle align-[-2px]"></i> Some details need fixing before they can be saved.</p>
                    @endif
                    <button type="button" wire:click="discard" class="{{ $btnSecondary }} w-full sm:w-auto">Discard changes</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="{{ $btnPrimary }} w-full sm:w-auto sm:min-w-[9rem]">
                        <i wire:loading wire:target="save" class="bx bx-loader-alt animate-spin" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="save">Save changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </form>

            {{-- ---------- PASSWORD ---------- --}}
            <div x-show="tab === 'password'" x-cloak class="p-4 sm:p-6">
                <h2 class="text-[15px] font-semibold text-[#0d253d]">Change password</h2>
                <p class="mt-1 text-sm text-[#66707f]">Use at least 8 characters. You'll stay signed in on this device.</p>

                <form wire:submit="updatePassword" class="mt-5 max-w-md space-y-4">
                    @foreach ([
                        ['current_password', 'Current password', 'current-password'],
                        ['new_password', 'New password', 'new-password'],
                        ['new_password_confirmation', 'Confirm new password', 'new-password'],
                    ] as [$field, $text, $auto])
                        <div x-data="{ show: false }">
                            <label for="{{ $field }}" class="{{ $label }}">{{ $text }}</label>
                            <div class="relative">
                                <input wire:model="{{ $field }}" id="{{ $field }}" :type="show ? 'text' : 'password'" autocomplete="{{ $auto }}" class="{{ $input }} pr-11">
                                <button type="button" @click="show = !show" class="absolute right-1 top-1.5 grid h-10 w-10 place-items-center rounded-lg text-[#9a978f] hover:text-[#273951]"
                                        :aria-label="show ? 'Hide password' : 'Show password'">
                                    <i class="bx text-lg" :class="show ? 'bx-hide' : 'bx-show'" aria-hidden="true"></i>
                                </button>
                            </div>
                            @error($field) <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="pt-2">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="{{ $btnPrimary }} w-full sm:w-auto">
                            <i wire:loading wire:target="updatePassword" class="bx bx-loader-alt animate-spin" aria-hidden="true"></i>
                            <span wire:loading.remove wire:target="updatePassword">Update password</span>
                            <span wire:loading wire:target="updatePassword">Updating…</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ---------- E-SIGNATURE ---------- --}}
            <div x-show="tab === 'signature'" x-cloak class="p-4 sm:p-6">
                <h2 class="text-[15px] font-semibold text-[#0d253d]">E-signature</h2>
                <p class="mt-1 text-sm text-[#66707f]">Placed on documents you sign in DocuMate. It's stored privately, and changing it never alters documents you already signed.</p>

                <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,15rem)_minmax(0,1fr)]">
                    <div>
                        <p class="{{ $label }}">Current signature</p>
                        <div class="mt-1.5 flex h-32 items-center justify-center overflow-hidden rounded-lg border border-[#e7e5e1] bg-[#fafaf9] p-3">
                            @if ($user->signature_path)
                                <img src="{{ route('profile.signature') }}?v={{ md5($user->signature_path) }}" class="h-full w-full object-contain" alt="Your saved signature">
                            @else
                                <p class="text-center text-sm text-[#9a978f]">No signature saved yet</p>
                            @endif
                        </div>
                        @if (! $signature_enabled)
                            <p class="mt-2 rounded-lg bg-[#fef3c7] px-3 py-2 text-xs text-[#78350f]">Your signature isn't being placed on documents. Save one to start using it.</p>
                        @endif
                    </div>

                    <div wire:ignore
                         x-on:signature-saved.window="clear()"
                         x-data="{
                            drawing: false, has: false, ctx: null,
                            init() { const c = this.$refs.c; this.ctx = c.getContext('2d'); this.ctx.lineWidth = 5; this.ctx.lineCap = 'round'; this.ctx.lineJoin = 'round'; this.ctx.strokeStyle = '#0d253d'; },
                            pos(e) { const c = this.$refs.c, r = c.getBoundingClientRect(); return [(e.clientX - r.left) * c.width / r.width, (e.clientY - r.top) * c.height / r.height]; },
                            start(e) { this.drawing = true; const [x, y] = this.pos(e); this.ctx.beginPath(); this.ctx.moveTo(x, y); this.ctx.lineTo(x + 0.1, y + 0.1); this.ctx.stroke(); },
                            move(e) { if (!this.drawing) return; const [x, y] = this.pos(e); this.ctx.lineTo(x, y); this.ctx.stroke(); this.has = true; },
                            end() { if (!this.drawing) return; this.drawing = false; if (this.has) { $wire.$set('signatureData', this.$refs.c.toDataURL('image/png'), false); } },
                            clear() { this.ctx.clearRect(0, 0, 1200, 600); this.has = false; $wire.$set('signatureData', '', false); }
                         }">
                        <div class="flex items-baseline justify-between">
                            <p class="{{ $label }}">{{ $user->signature_path ? 'Draw a new signature' : 'Draw your signature' }}</p>
                            <button type="button" @click="clear()" x-show="has" class="text-xs font-medium text-[#66707f] hover:text-[#dc2626]">Clear</button>
                        </div>
                        <div class="relative mt-1.5">
                            <canvas x-ref="c" width="1200" height="600"
                                    class="block w-full touch-none rounded-lg border border-dashed border-[#d4d1cb] bg-white"
                                    style="aspect-ratio: 2 / 1; cursor: crosshair;"
                                    @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)"
                                    @pointerup="end()" @pointerleave="end()"></canvas>
                            <div x-show="!has" class="pointer-events-none absolute inset-x-6 bottom-[22%] border-b border-[#d4d1cb]"></div>
                            <p x-show="!has" class="pointer-events-none absolute inset-x-0 top-1/3 text-center text-sm text-[#9a978f]">Sign here with your finger, stylus or mouse</p>
                        </div>
                    </div>
                </div>
                @error('signatureData') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col gap-3 border-t border-[#e7e5e1] pt-5 sm:flex-row sm:items-end">
                    <div class="sm:w-72">
                        <label for="signature_password" class="{{ $label }}">Confirm with your password</label>
                        <input type="password" id="signature_password" wire:model="signature_password" wire:keydown.enter.prevent="saveSignature" autocomplete="current-password" class="{{ $input }}">
                    </div>
                    <button type="button" wire:click="saveSignature" wire:loading.attr="disabled" wire:target="saveSignature" class="{{ $btnPrimary }} w-full sm:w-auto">
                        <i wire:loading wire:target="saveSignature" class="bx bx-loader-alt animate-spin" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="saveSignature">Save signature</span>
                        <span wire:loading wire:target="saveSignature">Saving…</span>
                    </button>
                </div>
                @error('signature_password') <p class="{{ $err }}"><i class="bx bx-error-circle"></i>{{ $message }}</p> @enderror
            </div>

            {{-- ---------- ACCOUNT ---------- --}}
            <div x-show="tab === 'account'" x-cloak class="p-4 sm:p-6">
                <h2 class="text-[15px] font-semibold text-[#0d253d]">Account</h2>
                <p class="mt-1 text-sm text-[#66707f]">These are set by DocuMate and the VPSD office.</p>

                @php
                    $statusTone = match ($account_status) {
                        'active' => 'bg-[#d9f3e1] text-[#065f46]',
                        'pending_verification' => 'bg-[#fef3c7] text-[#78350f]',
                        'suspended' => 'bg-[#fde0ec] text-[#9f1239]',
                        default => 'bg-[#ecebe7] text-[#4a5568]',
                    };
                    $verified = $isStudentSide ? $user->isVerifiedForCurrentPeriod() : null;
                @endphp

                <dl class="mt-5 divide-y divide-[#e7e5e1] rounded-lg border border-[#e7e5e1]">
                    <div class="flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="text-[13px] text-[#66707f] sm:w-44">Account status</dt>
                        <dd><span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[13px] font-medium {{ $statusTone }}"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $this->statusLabel }}</span></dd>
                    </div>
                    <div class="flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="text-[13px] text-[#66707f] sm:w-44">Role</dt>
                        <dd class="text-sm font-medium text-[#0d253d]">{{ $role_name }}</dd>
                    </div>
                    @if ($isStudentSide)
                        <div class="flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                            <dt class="text-[13px] text-[#66707f] sm:w-44">Enrollment verification</dt>
                            <dd class="flex flex-1 flex-wrap items-center justify-between gap-2 text-sm">
                                @if ($verified === true)
                                    <span class="inline-flex items-center gap-1.5 font-medium text-[#059669]"><i class="bx bx-badge-check text-base"></i>Verified for this period</span>
                                @elseif ($verified === false)
                                    <span class="inline-flex items-center gap-1.5 font-medium text-[#b45309]"><i class="bx bx-error text-base"></i>Not verified for this period</span>
                                    <a href="{{ route('verification.notice') }}" wire:navigate class="text-[13px] font-medium text-[#2a57b4] hover:underline">Verify now</a>
                                @else
                                    <span class="text-[#66707f]">No verification period is open</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($user->signatoryAssignments->isNotEmpty())
                        <div class="flex flex-col gap-1.5 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                            <dt class="text-[13px] text-[#66707f] sm:w-44">Signs as</dt>
                            <dd class="min-w-0 flex-1"><x-admin.signatory-badges :user="$user" /></dd>
                        </div>
                    @endif
                    <div class="flex flex-col gap-1 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="text-[13px] text-[#66707f] sm:w-44">Member since</dt>
                        <dd class="text-sm text-[#0d253d]">{{ $user->created_at?->format('F j, Y') ?? '—' }}</dd>
                    </div>
                </dl>

                {{-- Phones: the completeness card lives here --}}
                <div class="mt-5 rounded-lg bg-[#f0f4fb] p-4 lg:hidden">
                    <div class="flex items-baseline justify-between">
                        <p class="text-[13px] font-medium text-[#273951]">Profile details</p>
                        <p class="text-[13px] font-semibold tabular-nums text-[#0d253d]">{{ $done['percent'] }}%</p>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                        <div class="h-full rounded-full bg-[#2a57b4]" style="width: {{ $done['percent'] }}%"></div>
                    </div>
                    @if ($done['missing'])
                        <p class="mt-2 text-xs text-[#4a5568]">Still missing: {{ implode(', ', $done['missing']) }}.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
