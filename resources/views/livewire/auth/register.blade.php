<div x-data="{ showError: false }"
     x-on:validation-error.window="showError = true">

<div class="min-h-screen grid grid-cols-1 md:grid-cols-4">

    <!-- LEFT -->
    <div class="md:col-span-3 flex items-center justify-center p-10 bg-white">

        <div class="w-full max-w-3xl">

            <div class="flex items-center gap-3 mb-6">
                <img src="/images/favicon.png" class="w-10 h-10">
                <h1 class="text-2xl font-bold">Create your account</h1>
            </div>

            <!-- PROGRESS -->
            <div class="mb-8">
                <div class="flex justify-between text-sm mb-2">
                    <span class="{{ $step >= 1 ? 'text-blue-600 font-semibold' : '' }}">Personal</span>
                    <span class="{{ $step >= 2 ? 'text-blue-600 font-semibold' : '' }}">Academic</span>
                    <span class="{{ $step >= 3 ? 'text-blue-600 font-semibold' : '' }}">Account</span>
                </div>

                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300
                        {{ $step == 1 ? 'w-1/3 bg-blue-500' : '' }}
                        {{ $step == 2 ? 'w-2/3 bg-green-500' : '' }}
                        {{ $step == 3 ? 'w-full bg-green-500' : '' }}">
                    </div>
                </div>
            </div>

            <form wire:submit.prevent="register" class="space-y-4">

                {{-- STEP 1 --}}
                @if($step == 1)
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label>Student Number <span class="text-red-500">*</span></label>
                        <input wire:model.live="student_number" placeholder="e.g. 202312345"
                            class="input
                            @error('student_number') border-red-500 @enderror
                            @if($student_number && !$errors->has('student_number')) border-green-500 @endif">
                        @error('student_number') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>Email <span class="text-red-500">*</span></label>
                        <input wire:model.live="email" placeholder="e.g. juan@gmail.com"
                            class="input
                            @error('email') border-red-500 @enderror
                            @if($email && !$errors->has('email')) border-green-500 @endif">
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>First Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="first_name" placeholder="Enter your first name"
                            class="input
                            @error('first_name') border-red-500 @enderror
                            @if($first_name && !$errors->has('first_name')) border-green-500 @endif">
                        @error('first_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>Middle Name</label>
                        <input wire:model.live="middle_name" placeholder="Optional" class="input">
                    </div>

                    <div>
                        <label>Last Name <span class="text-red-500">*</span></label>
                        <input wire:model.live="last_name" placeholder="Enter your last name"
                            class="input
                            @error('last_name') border-red-500 @enderror
                            @if($last_name && !$errors->has('last_name')) border-green-500 @endif">
                        @error('last_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>Suffix</label>
                        <input wire:model.live="suffix" placeholder="Optional" class="input">
                    </div>

                    <div>
                        <label>Sex <span class="text-red-500">*</span></label>
                        <select wire:model.live="sex"
                            class="input
                            @error('sex') border-red-500 @enderror
                            @if($sex && !$errors->has('sex')) border-green-500 @endif">
                            <option value="">Select</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                        @error('sex') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>Date of Birth <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.live="date_of_birth"
                            class="input
                            @error('date_of_birth') border-red-500 @enderror
                            @if($date_of_birth && !$errors->has('date_of_birth')) border-green-500 @endif">
                        @error('date_of_birth') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2">
                        <label>Contact Number <span class="text-red-500">*</span></label>
                        <input wire:model.live="contact_number" placeholder="e.g. 09123456789"
                            class="input
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
                        <label>College <span class="text-red-500">*</span></label>
                        <select wire:model.live="college"
                            class="input
                            @error('college') border-red-500 @enderror
                            @if($college && !$errors->has('college')) border-green-500 @endif">
                            <option value="">Select your college</option>
                            <option>College of Arts and Sciences</option>
                            <option>College of Education</option>
                            <option>College of Management and Entrepreneurship</option>
                        </select>
                        @error('college') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="relative">
                        <label>Program <span class="text-red-500">*</span></label>

                        <input wire:model.live="program"
                            list="program-options"
                            placeholder="Select or type your program"
                            class="input
                            @error('program') border-red-500 @enderror
                            @if($program && !$errors->has('program')) border-green-500 @endif">

                        <datalist id="program-options">
                            @foreach($allPrograms as $programOption)
                                <option value="{{ $programOption }}"></option>
                            @endforeach
                        </datalist>

                        @error('program') <p class="error">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">Choose from the list or type your own program.</p>
                    </div>

                    <div>
                        <label>Year Level <span class="text-red-500">*</span></label>
                        <select wire:model.live="year_level"
                            class="input
                            @error('year_level') border-red-500 @enderror
                            @if($year_level && !$errors->has('year_level')) border-green-500 @endif">
                            <option value="">Select</option>
                            <option>1</option><option>2</option><option>3</option><option>4</option>
                        </select>
                        @error('year_level') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label>Academic Status <span class="text-red-500">*</span></label>
                        <select wire:model.live="academic_status"
                            class="input
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

                    <div class="col-span-2 relative">
                        <label>Organization</label>

                        <input wire:model.live="organization"
                            placeholder="Optional (e.g. SSC, JPIA)"
                            class="input
                            @if($organization && !$errors->has('organization')) border-green-500 @endif">

                        @if(!empty($orgSuggestions))
                            <div class="absolute z-10 bg-white border w-full rounded shadow mt-1">
                                @foreach($orgSuggestions as $item)
                                    <div wire:click="selectOrg('{{ $item }}')"
                                        class="p-2 hover:bg-gray-100 cursor-pointer">
                                        {{ $item }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
                @endif


                {{-- STEP 3 --}}
                @if($step == 3)

                <div x-data="passwordChecker()" class="space-y-6">

                    <!-- PROFILE -->
                    <div class="flex flex-col items-center">

                        <div class="w-40 h-40 rounded-full border flex items-center justify-center overflow-hidden bg-gray-100">

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
                        <h1>Student Verification</h1>

                        @if (session()->has('message'))
                            <div>{{ session('message') }}</div>
                        @endif

                        <input type="file" wire:model="e_slip" wire:loading.attr="disabled" wire:target="e_slip">
                        @error('e_slip') <span>{{ $message }}</span> @enderror
                        <!-- 🔥 VERIFICATION STATUS INDICATOR -->

                        <div class="mt-3 space-y-2">

                            <!-- ⏳ LOADING -->
                            <div wire:loading wire:target="e_slip"
                                class="flex items-center gap-2 text-blue-600 text-sm">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                </svg>
                                Scanning and verifying e-slip...
                            </div>

                            <!-- ✅ VERIFIED -->
                            @if($e_slip && $isVerified)
                                <div class="flex items-center gap-2 bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm border border-green-300">
                                    <span class="text-lg">✔</span>
                                    <div>
                                        <p class="font-semibold">Student Verified</p>
                                        <p class="text-xs text-green-600">You can proceed with registration</p>
                                    </div>
                                </div>
                            @endif

                            <!-- ❌ FAILED -->
                            @if($e_slip && !$isVerified && $ocrResult)
                                <div class="flex items-center gap-2 bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm border border-red-300">
                                    <span class="text-lg">✖</span>
                                    <div>
                                        <p class="font-semibold">Verification Failed</p>
                                        <p class="text-xs text-red-600">Please upload a valid e-slip</p>
                                    </div>
                                </div>
                            @endif

                        </div>

                        <div wire:loading wire:target="e_slip" class="text-sm text-blue-500 mt-2">
                            Scanning e-slip...
                        </div>
                        @if($ocrResult)
                            <button wire:click="$set('showOcrModal', true)"
                                class="text-blue-600 text-xs underline mt-1">
                                View verification details
                            </button>
                        @endif
                    </div>


                    <!-- PASSWORD -->
                    <div>
                        <label>Password <span class="text-red-500">*</span></label>

                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                x-model="password"
                                wire:model.live="password"
                                @input="checkStrength($event.target.value)"
                                placeholder="Minimum 8 characters"
                                class="input pr-10"
                                :class="strengthClass">

                            <!-- EYE ICON -->
                            <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-3 text-gray-500">
                                
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
                        <label>Confirm Password <span class="text-red-500">*</span></label>

                        <div class="relative">
                            <input
                                :type="showConfirm ? 'text' : 'password'"
                                x-model="password_confirmation"
                                wire:model.live="password_confirmation"
                                placeholder="Re-enter password"
                                class="input pr-10"
                                :class="confirmClass">

                            <!-- EYE ICON -->
                            <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-3 text-gray-500">
                                
                            </button>
                        </div>

                        <p x-show="password_confirmation && password !== password_confirmation"
                        class="text-red-500 text-sm mt-1">
                        Passwords do not match
                        </p>

                    </div>
                    <div class="pt-4">
                        <button type="submit"
                            :disabled="!canSubmit"
                            :class="canSubmit 
                                ? 'bg-yellow-500 hover:bg-yellow-600' 
                                : 'bg-gray-400 cursor-not-allowed'"
                            class="w-full px-5 py-3 text-white rounded-xl transition">
                            Register
                        </button>
                    </div>

                </div>

                @endif


                <!-- BUTTONS -->
                <div class="flex justify-between pt-6">
                    @if($step > 1)
                        <button type="button" wire:click="prevStep"
                            class="px-5 py-2 bg-red-500 text-white rounded-xl">
                            Back
                        </button>
                    @endif

                    @if($step < 3)
                        <button type="button" wire:click="nextStep"
                            class="px-5 py-2 bg-blue-600 text-white rounded-xl">
                            Next
                        </button>
                    @endif
                </div>

            </form>

        </div>
    </div>
    
    <div class="hidden md:block md:col-span-1 bg-cover bg-center"
         style="background-image: url('/images/backdrop.jpg');">
    </div>

</div>


<!-- MODAL -->
<div x-show="showError"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">

    <div class="bg-white p-6 rounded-xl text-center shadow-lg">
        <h2 class="text-red-600 font-semibold mb-2">Incomplete Form</h2>
        <p class="mb-4">Please fill all required fields correctly.</p>
        <button @click="showError=false"
            class="px-4 py-2 bg-blue-600 text-white rounded">
            OK
        </button>
    </div>

</div>
<div x-data="{ open: @entangle('showOcrModal') }"
     x-show="open"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 overflow-auto">

    <div class="bg-white p-6 rounded-xl w-[600px] max-h-[90vh] overflow-y-auto shadow-lg">

        <h2 class="text-lg font-bold mb-4 text-center">Verification Details</h2>

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
        <!-- 🔵 SCORE -->
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

                <div class="absolute inset-0 flex items-center justify-center font-bold text-lg">
                    {{ $matchScore }}%
                </div>
            </div>
        </div>

        <!-- 🔍 COMPARISON TABLE -->
        <div class="grid grid-cols-3 gap-2 text-sm font-semibold border-b pb-2">
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

                $rowBg = match($status) {
                    'match' => 'bg-green-50',
                    'mismatch' => 'bg-red-50',
                    default => 'bg-gray-50',
                };
            @endphp

            <div class="font-medium flex items-center gap-1 {{ $color }}">
                <span>{{ $icon }}</span> Enrolled
            </div>

            <div class="{{ $color }}">
                {{ ($ocrResult['officially_enrolled'] ?? false) ? 'YES' : 'NO' }}
            </div>

            <div class="text-gray-400">-</div>

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
            class="mt-4 w-full bg-blue-600 text-white py-2 rounded">
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
            return score === 4 && this.password === this.password_confirmation &&
                @this.isVerified === true;
        }
    }
}
</script>
</div>
