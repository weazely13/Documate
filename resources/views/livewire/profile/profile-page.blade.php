<div class="profile-page-shell">
    @if (session()->has('profile_saved'))
        <div class="profile-flash success">
            <i class='bx bx-check-circle'></i>
            <span>{{ session('profile_saved') }}</span>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <section class="profile-hero-card">
            <div class="profile-hero-stack">
                <div class="profile-avatar-frame">
                    @if($profile_picture)
                        <img src="{{ asset('storage/' . $profile_picture) }}"
                             alt="{{ $this->fullName }}"
                             class="profile-avatar-image">
                    @else
                        <div class="profile-avatar-fallback">
                            {{ $this->initials }}
                        </div>
                    @endif
                </div>

                <p class="profile-student-id">
                    {{ $student_number ?: 'No student number' }}
                </p>

                <h1 class="profile-display-name">
                    {{ $this->fullName }}
                </h1>

                <div class="profile-meta-row">
                    @if($this->academicLine)
                        <span class="profile-meta-chip warm">{{ $this->academicLine }}</span>
                    @endif

                    <span class="profile-meta-chip">{{ $role_name }}</span>
                    <span class="profile-meta-chip {{ $account_status === 'active' ? 'success' : 'muted' }}">
                        {{ ucfirst(str_replace('_', ' ', $account_status ?: 'inactive')) }}
                    </span>
                </div>
            </div>

            <button type="submit" class="profile-save-button" wire:loading.attr="disabled" wire:target="save">
                <i class='bx bx-check'></i>
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </section>

        <section class="profile-section-shell">
            <div class="profile-section-copy">
                <h2>Profile Information</h2>
                <p>All the information about yourself.</p>
            </div>

            <div class="profile-form-card">
                <div class="profile-card-heading">
                    <div class="profile-card-icon">
                        <i class='bx bx-user-circle'></i>
                    </div>

                    <div>
                        <h3>Personal Information</h3>
                        <p>How will the system call you.</p>
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div class="profile-field xl-span-4">
                        <label class="profile-label" for="profile_first_name">First Name</label>
                        <input id="profile_first_name" type="text" wire:model.live="first_name" class="profile-input" autocomplete="given-name">
                        @error('first_name') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-3">
                        <label class="profile-label" for="profile_middle_name">Middle Name</label>
                        <input id="profile_middle_name" type="text" wire:model.live="middle_name" class="profile-input" autocomplete="additional-name">
                        @error('middle_name') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-3">
                        <label class="profile-label" for="profile_last_name">Last Name</label>
                        <input id="profile_last_name" type="text" wire:model.live="last_name" class="profile-input" autocomplete="family-name">
                        @error('last_name') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-2">
                        <label class="profile-label" for="profile_suffix">Suffix</label>
                        <input id="profile_suffix" type="text" wire:model.live="suffix" class="profile-input" placeholder="Jr., III">
                        @error('suffix') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-4">
                        <label class="profile-label" for="profile_sex">Sex</label>
                        <select id="profile_sex" wire:model.live="sex" class="profile-input">
                            <option value="">Select sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        @error('sex') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-4">
                        <label class="profile-label" for="profile_birthdate">Date of Birth</label>
                        <input id="profile_birthdate" type="date" wire:model.live="date_of_birth" class="profile-input" autocomplete="bday">
                        @error('date_of_birth') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-6">
                        <label class="profile-label" for="profile_email">Email</label>
                        <input id="profile_email" type="email" wire:model.live="email" class="profile-input" autocomplete="email">
                        @error('email') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="profile-field xl-span-6">
                        <label class="profile-label" for="profile_contact_number">Contact Number</label>
                        <input id="profile_contact_number" type="text" wire:model.live="contact_number" class="profile-input" placeholder="+639123456789" autocomplete="tel">
                        @error('contact_number') <p class="profile-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>
    </form>
</div>
