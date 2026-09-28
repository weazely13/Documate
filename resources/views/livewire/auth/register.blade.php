{{--
    CHANGES MADE ON THIS PAGE:
    1. Logo font: already set to font-family: 'Gveret Levin', cursive (same as the
       landing page). Make sure your shared layout's <head> also loads it:

           <link rel="preconnect" href="https://fonts.googleapis.com">
           <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
           <link href="https://fonts.googleapis.com/css2?family=Gveret+Levin&display=swap" rel="stylesheet">

    2. "Back to Sign In" link added top-right, next to the logo.

    3. Step 3 (Account) now has Terms & Conditions / Privacy Policy links that open
       modals structured exactly like the landing page's (checkbox inside the modal
       gates its own "I Agree" button). The Register button stays disabled until
       BOTH have been agreed to, in addition to the existing password rules and
       verification check.

       NOTE: the modals below @include('partials.terms-content') and
       @include('partials.privacy-content') — create these two partials with the
       same legal text used in the landing page's modals (e.g.
       resources/views/partials/terms-content.blade.php and
       .../privacy-content.blade.php) so all three pages stay in sync. If you'd
       rather not create partials, just paste the legal HTML directly in place of
       the @include lines.
--}}

<div x-data="{ showError: false }"
     x-on:validation-error.window="showError = true">

<div class="min-h-screen grid grid-cols-1 md:grid-cols-4 bg-white">

    <!-- LEFT -->
    <div class="md:col-span-3 flex items-center justify-center p-10 bg-white">

        <div class="w-full max-w-3xl">

            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <img src="/images/favicon.png" class="w-10 h-10 object-contain" alt="DocuMate Logo">
                </div>

                <!-- BACK TO SIGN IN -->
                <a href="/login" class="inline-flex items-center gap-1 text-sm font-medium text-[#2A57B4] hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Sign In
                </a>
            </div>

            <h1 class="text-2xl font-bold text-[#2A57B4] mb-6" style="font-family: 'Montserrat', sans-serif;">
                Create your account
            </h1>

            <!-- PROGRESS -->
            <div class="mb-8">
                <div class="flex justify-between text-sm mb-2">
                    <span class="{{ $step >= 1 ? 'text-[#2A57B4] font-semibold' : 'text-gray-400' }}">Personal</span>
                    <span class="{{ $step >= 2 ? 'text-[#2A57B4] font-semibold' : 'text-gray-400' }}">Academic</span>
                    <span class="{{ $step >= 3 ? 'text-[#2A57B4] font-semibold' : 'text-gray-400' }}">Account</span>
                </div>

                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300
                        {{ $step == 1 ? 'w-1/3 bg-[#2A57B4]' : '' }}
                        {{ $step == 2 ? 'w-2/3 bg-[#2A57B4]' : '' }}
                        {{ $step == 3 ? 'w-full bg-[#FFBF00]' : '' }}">
                    </div>
                </div>
            </div>

            <form wire:submit.prevent="register" class="space-y-4">

                {{-- STEP 1 --}}
                @if($step == 1)
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label class="text-sm font-medium text-gray-700">Student Number <span class="text-red-500">*</span></label>
                        <input wire:model.live="student_number" placeholder="e.g. 202312345"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('student_number') border-red-500 @enderror
                            @if($student_number && !$errors->has('student_number')) border-green-500 @endif">
                        @error('student_number') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input wire:model.live="email" placeholder="e.g. juan@gmail.com"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('email') border-red-500 @enderror
                            @if($email && !$errors->has('email')) border-green-500 @endif">
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="first_name" placeholder="Enter your first name"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('first_name') border-red-500 @enderror
                            @if($first_name && !$errors->has('first_name')) border-green-500 @endif">
                        @error('first_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Middle Name</label>
                        <input wire:model.live="middle_name" placeholder="Optional"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="last_name" placeholder="Enter your last name"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('last_name') border-red-500 @enderror
                            @if($last_name && !$errors->has('last_name')) border-green-500 @endif">
                        @error('last_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Suffix</label>
                        <input wire:model.live="suffix" placeholder="Optional"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Sex <span class="text-red-500">*</span></label>
                        <select wire:model.live="sex"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('sex') border-red-500 @enderror
                            @if($sex && !$errors->has('sex')) border-green-500 @endif">
                            <option value="">Select</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                        @error('sex') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.live="date_of_birth"
                            max="{{ now()->subYears(15)->toDateString() }}"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('date_of_birth') border-red-500 @enderror
                            @if($date_of_birth && !$errors->has('date_of_birth')) border-green-500 @endif">
                        @error('date_of_birth') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700">Contact Number <span class="text-red-500">*</span></label>
                        <input wire:model.live="contact_number" placeholder="e.g. 09123456789"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('contact_number') border-red-500 @enderror
                            @if($contact_number && !$errors->has('contact_number')) border-green-500 @endif">
                        @error('contact_number') <p class="error">{{ $message }}</p> @enderror
                    </div>

                </div>
                @endif


                {{-- STEP 2 --}}
                @if($step == 2)
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label class="text-sm font-medium text-gray-700">College <span class="text-red-500">*</span></label>
                        <select wire:model.live="college"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('college') border-red-500 @enderror
                            @if($college && !$errors->has('college')) border-green-500 @endif">
                            <option value="">Select your college</option>
                            <option>College of Arts and Sciences</option>
                            <option>College of Education</option>
                            <option>College of Management and Entrepreneurship</option>
                        </select>
                        @error('college') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Program <span class="text-red-500">*</span></label>

                        <select wire:model.live="program_id"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('program_id') border-red-500 @enderror
                            @if($program_id && !$errors->has('program_id')) border-green-500 @endif">
                            <option value="">Select your program</option>
                            @if ($this->bachelorPrograms->isNotEmpty())
                                <optgroup label="Bachelor's">
                                    @foreach ($this->bachelorPrograms as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($this->masterPrograms->isNotEmpty())
                                <optgroup label="Master's">
                                    @foreach ($this->masterPrograms as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>

                        @error('program_id') <p class="error">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">Don't see your program? Ask the admin to add it.</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Year Level <span class="text-red-500">*</span></label>
                        <select wire:model.live="year_level"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('year_level') border-red-500 @enderror
                            @if($year_level && !$errors->has('year_level')) border-green-500 @endif">
                            <option value="">Select</option>
                            <option>1</option><option>2</option><option>3</option><option>4</option>
                        </select>
                        @error('year_level') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Academic Status <span class="text-red-500">*</span></label>
                        <select wire:model.live="academic_status"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('academic_status') border-red-500 @enderror
                            @if($academic_status && !$errors->has('academic_status')) border-green-500 @endif">
                            <option value="">Select</option>
                            <option>Regular</option>
                            <option>Irregular</option>
                            <option>Shiftee</option>
                            <option>Transferee</option>
                        </select>
                        @error('academic_status') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700">Organization</label>

                        <select wire:model.live="organization_id"
                            class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @if($organization_id && !$errors->has('organization_id')) border-green-500 @endif">
                            <option value="">None / Not applicable</option>
                            @foreach ($this->organizationOptions as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('organization_id') <p class="error">{{ $message }}</p> @enderror
                    </div>

                </div>
                @endif


                {{-- STEP 3 --}}
                @if($step == 3)

                <div x-data="passwordChecker()" class="space-y-6" @keydown.escape.window="activeModal = null">

                    <!-- PROFILE -->
                    <div class="flex flex-col items-center">

                        <div class="w-40 h-40 rounded-full border-2 border-dashed border-[#2A57B4]/30 flex items-center justify-center overflow-hidden bg-gradient-to-br from-[#2A57B4]/10 to-[#FFBF00]/20">

                            @if($profile_picture)
                                <img src="{{ $profile_picture->temporaryUrl() }}"
                                    class="w-full h-full object-cover">
                            @else
                                <span class="text-gray-400 text-sm text-center px-2">
                                    No profile picture added
                                </span>
                            @endif

                        </div>

                        <input type="file" wire:model="profile_picture"
                            class="mt-3 input-file text-sm">

                        <!-- REQUIREMENTS -->
                        <p class="text-xs text-gray-500 mt-2">
                            JPG or PNG • Max 5MB • 1x1 photo recommended
                        </p>
                    </div>
                    <div>
                        <h1 class="font-bold text-[#2A57B4]" style="font-family: 'Montserrat', sans-serif;">Student Verification</h1>

                        @if (session()->has('message'))
                            <div>{{ session('message') }}</div>
                        @endif

                        <input type="file" wire:model="e_slip" wire:loading.attr="disabled" wire:target="e_slip" class="mt-2 input-file text-sm">
                        @error('e_slip') <span class="error">{{ $message }}</span> @enderror
                        <!-- VERIFICATION STATUS INDICATOR -->

                        <div class="mt-3 space-y-2">

                            <!-- LOADING -->
                            <div wire:loading wire:target="e_slip"
                                class="flex items-center gap-2 text-[#2A57B4] text-sm">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                </svg>
                                Scanning and verifying e-slip...
                            </div>

                            <!-- VERIFIED -->
                            @if($e_slip && $isVerified)
                                <div class="flex items-center gap-2 bg-green-50 text-green-700 px-3 py-2 rounded-lg text-sm border border-green-200">
                                    <span class="text-lg">✔</span>
                                    <div>
                                        <p class="font-semibold">Student Verified</p>
                                        <p class="text-xs text-green-600">You can proceed with registration</p>
                                    </div>
                                </div>
                            @endif

                            <!-- FAILED -->
                            @if($e_slip && !$isVerified && $ocrResult)
                                <div class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-2 rounded-lg text-sm border border-red-200">
                                    <span class="text-lg">✖</span>
                                    <div>
                                        <p class="font-semibold">Verification Failed</p>
                                        <p class="text-xs text-red-600">Please upload a valid e-slip</p>
                                    </div>
                                </div>
                            @endif

                        </div>

                        <div wire:loading wire:target="e_slip" class="text-sm text-[#2A57B4] mt-2">
                            Scanning e-slip...
                        </div>
                        @if($ocrResult)
                            <button wire:click="$set('showOcrModal', true)"
                                class="text-[#2A57B4] text-xs underline mt-1 hover:opacity-80">
                                View verification details
                            </button>
                        @endif
                    </div>


                    <!-- PASSWORD -->
                    <div>
                        <label class="text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>

                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                x-model="password"
                                wire:model.live="password"
                                @input="checkStrength($event.target.value)"
                                placeholder="Minimum 8 characters"
                                class="input pr-10 focus:ring-2 focus:ring-[#2A57B4]/30"
                                :class="strengthClass">

                            <!-- EYE ICON -->
                            <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-3 text-gray-500 hover:text-[#2A57B4] transition">

                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>

                                <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M6.1 6.1A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.043 5.207M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3v6" />
                                </svg>

                            </button>
                        </div>

                        <!-- STRENGTH LABEL -->
                        <p class="text-sm mt-2"
                        :class="strengthTextClass"
                        x-text="strengthLabel"></p>

                        <!-- CHECKLIST -->
                        <div class="text-sm mt-2 space-y-1">

                            <p :class="rules.length ? 'text-green-600' : 'text-gray-400'">✔ At least 8 characters</p>
                            <p :class="rules.number ? 'text-green-600' : 'text-gray-400'">✔ Contains a number</p>
                            <p :class="rules.upper ? 'text-green-600' : 'text-gray-400'">✔ One uppercase letter</p>
                            <p :class="rules.symbol ? 'text-green-600' : 'text-gray-400'">✔ One symbol</p>

                        </div>
                    </div>


                    <!-- CONFIRM PASSWORD -->
                    <div>
                        <label class="text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>

                        <div class="relative">
                            <input
                                :type="showConfirm ? 'text' : 'password'"
                                x-model="password_confirmation"
                                wire:model.live="password_confirmation"
                                placeholder="Re-enter password"
                                class="input pr-10 focus:ring-2 focus:ring-[#2A57B4]/30"
                                :class="confirmClass">

                            <!-- EYE ICON -->
                            <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-3 text-gray-500 hover:text-[#2A57B4] transition">

                            </button>
                        </div>

                        <p x-show="password_confirmation && password !== password_confirmation"
                        class="text-red-500 text-sm mt-1">
                        Passwords do not match
                        </p>

                    </div>

                    <!-- TERMS & PRIVACY AGREEMENT (required to enable Register) -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-sm font-medium text-gray-700 mb-2">Before you register, please review and accept:</p>
                        <div class="flex flex-wrap gap-4">
                            <button type="button" @click="activeModal = 'terms'"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#2A57B4] hover:underline">
                                <span x-show="agreedTerms" class="text-green-600">✔</span>
                                Terms and Conditions
                            </button>
                            <button type="button" @click="activeModal = 'privacy'"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#2A57B4] hover:underline">
                                <span x-show="agreedPrivacy" class="text-green-600">✔</span>
                                Privacy Policy
                            </button>
                        </div>
                        <p x-show="!agreedTerms || !agreedPrivacy" class="text-xs text-gray-400 mt-2">
                            You must open and agree to both before you can register.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            :disabled="!canSubmit"
                            :class="canSubmit
                                ? 'bg-[#FFBF00] hover:opacity-90 text-[#2A57B4]'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                            class="w-full px-5 py-3 rounded-full font-bold transition">
                            Register
                        </button>
                    </div>

                    <!-- ===== TERMS MODAL — same structure as landing page ===== -->
                    <div x-show="activeModal === 'terms'" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-8" style="display:none;">
                        <div class="absolute inset-0 bg-black/50" @click="activeModal = null"></div>
                        <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

                            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                                <div>
                                    <p class="text-[.7rem] uppercase tracking-wider text-[#2A57B4] font-bold">VPSD DocuMate — Leyte Normal University</p>
                                    <h3 class="text-2xl font-bold text-[#2A57B4] mt-1">Terms & Conditions</h3>
                                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                                </div>
                                <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600">
                                @include('partials.terms-content')
                            </div>

                            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                    <input type="checkbox" x-model="termsBoxChecked" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                                    <span>I have read and agree to the Terms and Conditions.</span>
                                </label>
                                <button type="button" :disabled="!termsBoxChecked"
                                    @click="agreedTerms = true; activeModal = null"
                                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-full font-semibold transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                                    I Agree
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PRIVACY MODAL — same structure as landing page ===== -->
                    <div x-show="activeModal === 'privacy'" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-8" style="display:none;">
                        <div class="absolute inset-0 bg-black/50" @click="activeModal = null"></div>
                        <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

                            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                                <div>
                                    <p class="text-[.7rem] uppercase tracking-wider text-[#2A57B4] font-bold">VPSD DocuMate — Leyte Normal University</p>
                                    <h3 class="text-2xl font-bold text-[#2A57B4] mt-1">Privacy Policy</h3>
                                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                                </div>
                                <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600">
                                @include('partials.privacy-content')
                            </div>

                            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                    <input type="checkbox" x-model="privacyBoxChecked" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                                    <span>I have read and agree to the Privacy Policy.</span>
                                </label>
                                <button type="button" :disabled="!privacyBoxChecked"
                                    @click="agreedPrivacy = true; activeModal = null"
                                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-full font-semibold transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                                    I Agree
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                @endif


                <!-- BUTTONS -->
                <div class="flex justify-between pt-6">
                    @if($step > 1)
                        <button type="button" wire:click="prevStep"
                            class="px-5 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-full transition">
                            Back
                        </button>
                    @endif

                    @if($step < 3)
                        <button type="button" wire:click="nextStep"
                            class="px-5 py-2 bg-[#2A57B4] hover:opacity-90 text-white rounded-full font-semibold transition ml-auto">
                            Next
                        </button>
                    @endif
                </div>

            </form>

        </div>
    </div>

    <div class="hidden md:flex md:col-span-1 relative bg-cover bg-center items-end"
         style="background-image: url('/images/backdrop.jpg');">
        <div class="absolute inset-0 bg-gradient-to-t from-[#2A57B4]/90 via-[#2A57B4]/40 to-transparent"></div>
        <div class="relative z-10 p-8 text-white">
            <p class="text-2xl font-bold leading-snug" style="font-family: 'Montserrat', sans-serif;">
                Join DocuMate.
            </p>
            <p class="text-sm text-white/80 mt-2">
                Set up your account to start filing transactions.
            </p>
        </div>
    </div>

</div>


<!-- MODAL: incomplete form error -->
<div x-show="showError"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">

    <div class="bg-white p-6 rounded-2xl text-center shadow-2xl">
        <h2 class="text-red-600 font-semibold mb-2">Incomplete Form</h2>
        <p class="mb-4 text-gray-600">Please fill all required fields correctly.</p>
        <button @click="showError=false"
            class="px-6 py-2 bg-[#2A57B4] hover:opacity-90 text-white rounded-full transition">
            OK
        </button>
    </div>

</div>

<div x-data="{ open: @entangle('showOcrModal') }"
     x-show="open"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 overflow-auto">

    <div class="bg-white p-6 rounded-2xl w-[600px] max-h-[90vh] overflow-y-auto shadow-2xl">

        <h2 class="text-lg font-bold mb-4 text-center text-[#2A57B4]" style="font-family: 'Montserrat', sans-serif;">Verification Details</h2>

        <div class="mb-4 text-center">

            @if($isVerified)
                <div class="flex items-center justify-center gap-2 text-green-600 font-semibold text-lg">
                    <span class="text-2xl">✔</span>
                    Verified Student
                </div>
                <p class="text-sm text-gray-500">All required fields matched successfully</p>
            @else
                <div class="flex items-center justify-center gap-2 text-red-600 font-semibold text-lg">
                    <span class="text-2xl">✖</span>
                    Verification Failed
                </div>
                <p class="text-sm text-gray-500">Some required fields did not match</p>
            @endif

        </div>
        <!-- SCORE -->
        <div class="flex justify-center mb-6">
            <div class="relative w-28 h-28">
                <svg class="w-28 h-28 transform -rotate-90">
                    <circle cx="56" cy="56" r="50"
                        stroke="#e5e7eb" stroke-width="10" fill="none"/>

                    @php
                        $scoreColor = $matchScore >= 75 ? '#16a34a' : '#dc2626'; // green or red
                    @endphp

                    <circle cx="56" cy="56" r="50"
                        stroke="{{ $scoreColor }}"
                        stroke-width="10"
                        fill="none"
                        stroke-dasharray="314"
                        stroke-dashoffset="{{ 314 - (314 * $matchScore) / 100 }}"
                        stroke-linecap="round"/>
                </svg>

                <div class="absolute inset-0 flex items-center justify-center font-bold text-lg text-[#2A57B4]">
                    {{ $matchScore }}%
                </div>
            </div>
        </div>

        <!-- COMPARISON TABLE -->
        <div class="grid grid-cols-3 gap-2 text-sm font-semibold border-b pb-2 text-gray-700">
            <div>Field</div>
            <div>OCR Data</div>
            <div>Input Data</div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-sm mt-2">

            @php
                $fields = [
                    'student_number' => 'Student Number',
                    'first_name' => 'First Name',
                    'last_name' => 'Last Name',
                    'enrollment_date' => 'Enrollment Date',
                    'birth_date' => 'Birth Date',
                    'semester' => 'Semester',
                    'academic_year' => 'Academic Year',
                    'college' => 'College',
                    'course' => 'Course',
                    'year' => 'Year',
                    'section' => 'Section',
                ];
            @endphp

            @foreach($fields as $key => $label)

                @php
                    $status = $this->fieldStatus[$key] ?? 'neutral';

                    $color = match($status) {
                        'match' => 'text-green-600',
                        'mismatch' => 'text-red-600',
                        default => 'text-gray-400',
                    };

                    $badge = match($status) {
                        'match' => ['✔ Match', 'bg-green-100 text-green-700'],
                        'mismatch' => ['✖ Mismatch', 'bg-red-100 text-red-700'],
                        default => ['• Not Required', 'bg-gray-100 text-gray-500'],
                    };

                    $ocrValue = $key === 'birth_date'
                        ? null
                        : ($ocrResult[$key] ?? null);

                    $inputValue = $key === 'enrollment_date'
                        ? null
                        : ($this->userData[$key] ?? null);
                @endphp

                <div class="font-medium flex flex-col">
                    <span>{{ $label }}</span>

                    <span class="text-xs px-2 py-1 rounded mt-1 w-fit {{ $badge[1] }}">
                        {{ $badge[0] }}
                    </span>
                </div>

                <!-- OCR -->
                <div class="{{ $color }}">
                    {{ $ocrValue ?? '—' }}
                </div>

                <!-- INPUT -->
                <div class="{{ $color }}">
                    {{ $inputValue ?? '—' }}
                </div>

            @endforeach

            @php
                $status = ($ocrResult['officially_enrolled'] ?? false) ? 'match' : 'mismatch';

                $color = $status === 'match' ? 'text-green-600' : 'text-red-600';
                $icon = $status === 'match' ? '✔' : '✖';
                $enrolled = $ocrResult['officially_enrolled'] ?? false;
            @endphp

            <div class="col-span-3 mt-4 pt-4 border-t">
                @if($enrolled)
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm space-y-1">
                        <p class="font-semibold text-green-700 flex items-center gap-1.5">
                            <span class="text-base">✔</span> Enrollment stamp detected
                        </p>
                        @if(!empty($ocrResult['stamp_registrar']))
                            <p class="text-gray-600">Issued by: <span class="font-medium">{{ $ocrResult['stamp_registrar'] }}</span></p>
                        @endif
                        @if(!empty($ocrResult['stamp_date']))
                            <p class="text-gray-600">Date stamped: <span class="font-medium">{{ $ocrResult['stamp_date'] }}</span></p>
                        @endif
                        @if(!empty($ocrResult['stamp_text']))
                            <p class="text-gray-500 italic mt-1">"{{ $ocrResult['stamp_text'] }}"</p>
                        @endif
                    </div>
                @else
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                        No enrollment stamp was detected on the uploaded slip. Make sure the registrar's stamp is fully visible and not cropped or blurred.
                    </div>
                @endif
            </div>
            {{-- PROCESSING DETAILS --}}
            @php
                $procOk = ($matchDetails['Processed By'] ?? false)
                    && ($matchDetails['Processor Signature'] ?? false)
                    && ($matchDetails['Processed Date & Time'] ?? false);
            @endphp

            <div class="col-span-3 mt-4 pt-4 border-t">
                <div class="p-3 rounded-lg text-sm space-y-1 border {{ $procOk ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <p class="font-semibold flex items-center gap-1.5 {{ $procOk ? 'text-green-700' : 'text-red-600' }}">
                        <span class="text-base">{{ $procOk ? '✔' : '✖' }}</span> Processing details
                    </p>

                    <p class="text-gray-600">
                        Processed by:
                        <span class="font-medium">{{ $ocrResult['processed_by'] ?? 'Not found' }}</span>
                    </p>
                    <p class="text-gray-600">
                        Signature:
                        <span class="font-medium">{{ ($ocrResult['has_signature'] ?? false) ? 'Present' : 'Missing' }}</span>
                    </p>
                    <p class="text-gray-600">
                        Date &amp; time processed:
                        <span class="font-medium">
                            {{ trim(($ocrResult['processed_date'] ?? '') . ' ' . ($ocrResult['processed_time'] ?? '')) ?: 'Not found' }}
                        </span>
                    </p>

                    @unless($procOk)
                        <p class="text-red-600 mt-1">
                            The slip must show who processed it, their signature, and the date and time of processing.
                        </p>
                    @endunless
                </div>
            </div>

        </div>

        <!-- RESULT -->
        <div class="mt-6 text-center">
            @if($isVerified)
                <p class="text-green-600 font-semibold">
                    ✔ Verified — You can proceed
                </p>
            @else
                <p class="text-red-600 font-semibold">
                    ✖ Verification failed
                </p>
            @endif
        </div>

        <button @click="open = false"
            class="mt-4 w-full bg-[#2A57B4] hover:opacity-90 text-white py-2 rounded-full transition">
            Close
        </button>
    </div>
</div>

<script>
function passwordChecker() {
    return {
        showPassword: false,
        showConfirm: false,
        password: '',
        password_confirmation: '',

        strengthClass: '',
        strengthLabel: '',
        strengthTextClass: '',

        // Terms & Privacy agreement state
        activeModal: null,        // 'terms' | 'privacy' | null — which modal is open
        agreedTerms: false,       // becomes true once "I Agree" clicked in Terms modal
        agreedPrivacy: false,     // becomes true once "I Agree" clicked in Privacy modal
        termsBoxChecked: false,   // the checkbox INSIDE the Terms modal
        privacyBoxChecked: false, // the checkbox INSIDE the Privacy modal

        rules: {
            length: false,
            number: false,
            upper: false,
            symbol: false
        },

        checkStrength(value) {

            this.rules.length = value.length >= 8;
            this.rules.number = /\d/.test(value);
            this.rules.upper = /[A-Z]/.test(value);
            this.rules.symbol = /[^A-Za-z0-9]/.test(value);

            let score = Object.values(this.rules).filter(Boolean).length;

            if (score <= 2) {
                this.strengthClass = 'border-red-500';
                this.strengthLabel = 'Weak Password';
                this.strengthTextClass = 'text-red-500';
            } else if (score === 3) {
                this.strengthClass = 'border-yellow-500';
                this.strengthLabel = 'Good Password';
                this.strengthTextClass = 'text-yellow-500';
            } else {
                this.strengthClass = 'border-green-500';
                this.strengthLabel = 'Strong Password';
                this.strengthTextClass = 'text-green-600';
            }
        },

        get confirmClass() {
            if (!this.password_confirmation) return '';
            return this.password === this.password_confirmation
                ? 'border-green-500'
                : 'border-red-500';
        },

        get canSubmit() {
            let score = Object.values(this.rules).filter(Boolean).length;
            return score === 4
                && this.password === this.password_confirmation
                && this.agreedTerms
                && this.agreedPrivacy
                && @this.isVerified === true;
        }
    }
}
</script>

</div>