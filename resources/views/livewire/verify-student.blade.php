<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mb-3">
            <i class='bx bx-id-card text-3xl'></i>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Verify Your Enrollment</h1>
        <p class="text-sm text-slate-500 mt-1">Upload your enrollment slip to unlock full account access.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-7 space-y-5">

        <form wire:submit.prevent="verify">
            <label for="e_slip"
                class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-200 rounded-xl py-8 px-4 cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 transition">
                <i class='bx bx-cloud-upload text-3xl text-slate-400'></i>
                <span class="text-sm font-medium text-slate-600">
                    {{ $e_slip ? $e_slip->getClientOriginalName() : 'Tap to upload your e-slip' }}
                </span>
                <span class="text-xs text-slate-400">JPG or PNG, up to 5MB</span>
                <input id="e_slip" type="file" wire:model="e_slip" class="hidden">
            </label>

            @error('e_slip')
                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
            @enderror

            <button type="submit" wire:loading.attr="disabled" wire:target="verify"
                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="verify">Verify Account</span>
                <span wire:loading wire:target="verify" class="flex items-center gap-2">
                    <i class='bx bx-loader-alt animate-spin'></i> Processing OCR…
                </span>
            </button>
        </form>

        @if(!empty($matchDetails))
            <div class="pt-5 border-t border-slate-100 space-y-4">

                <div class="flex items-center justify-between p-4 rounded-xl {{ $isVerified ? 'bg-green-50' : 'bg-red-50' }}">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide {{ $isVerified ? 'text-green-600' : 'text-red-600' }}">
                            {{ $isVerified ? 'Verified' : 'Not Verified' }}
                        </p>
                        <p class="text-2xl font-bold {{ $isVerified ? 'text-green-700' : 'text-red-700' }}">
                            {{ $matchScore }}% match
                        </p>
                    </div>
                    <i class='bx {{ $isVerified ? "bx-check-circle text-green-500" : "bx-x-circle text-red-500" }} text-4xl'></i>
                </div>

                {{-- FIELD-BY-FIELD COMPARISON --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <tr>
                                <th class="p-2.5 text-left">Field</th>
                                <th class="p-2.5 text-left">Your Record</th>
                                <th class="p-2.5 text-left">Extracted from Slip</th>
                                <th class="p-2.5 text-center">Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                'student_number' => 'Student Number', 'first_name' => 'First Name', 'last_name' => 'Last Name',
                                'college' => 'College', 'course' => 'Course', 'year' => 'Year Level',
                                'semester' => 'Semester', 'academic_year' => 'Academic Year',
                            ] as $key => $label)
                                @php
                                    $userValue = match($key) {
                                        'course' => optional(auth()->user()->program)->name ?? '—',
                                        'college' => auth()->user()->college,
                                        'year' => auth()->user()->year_level,
                                        'semester' => currentSemester(),
                                        'academic_year' => currentAcademicYear(),
                                        default => auth()->user()->{$key} ?? '—',
                                    };
                                    $ocrValue = $ocrResult[$key] ?? '—';
                                    $match = $matchDetails[$label] ?? false;
                                @endphp
                                <tr class="border-t border-slate-100 {{ $match ? 'bg-green-50/40' : 'bg-red-50/40' }}">
                                    <td class="p-2.5 font-medium text-slate-600">{{ $label }}</td>
                                    <td class="p-2.5 text-slate-700">{{ $userValue }}</td>
                                    <td class="p-2.5 text-slate-700">{{ $ocrValue ?: '—' }}</td>
                                    <td class="p-2.5 text-center">
                                        <i class='bx {{ $match ? "bx-check text-green-600" : "bx-x text-red-600" }}'></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ENROLLMENT STAMP DETAIL --}}
                @if($ocrResult['officially_enrolled'] ?? false)
                    <div class="p-3.5 bg-green-50 border border-green-200 rounded-xl text-sm space-y-1">
                        <p class="font-semibold text-green-700 flex items-center gap-1.5">
                            <i class='bx bx-badge-check'></i> Enrollment stamp detected
                        </p>
                        @if(!empty($ocrResult['stamp_registrar']))
                            <p class="text-slate-600">Issued by: <span class="font-medium">{{ $ocrResult['stamp_registrar'] }}</span></p>
                        @endif
                        @if(!empty($ocrResult['stamp_date']))
                            <p class="text-slate-600">Date stamped: <span class="font-medium">{{ $ocrResult['stamp_date'] }}</span></p>
                        @endif
                        @if(!empty($ocrResult['stamp_text']))
                            <p class="text-slate-500 italic mt-1">"{{ $ocrResult['stamp_text'] }}"</p>
                        @endif
                    </div>
                @else
                    <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
                        No enrollment stamp was detected on the uploaded slip. Make sure the registrar's stamp is fully visible and not cropped or blurred.
                    </div>
                @endif
                @php
                    $procOk = ($matchDetails['Processed By'] ?? false)
                        && ($matchDetails['Processor Signature'] ?? false)
                        && ($matchDetails['Processed Date & Time'] ?? false);
                @endphp
                <div class="p-3.5 rounded-xl text-sm space-y-1 border {{ $procOk ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <p class="font-semibold flex items-center gap-1.5 {{ $procOk ? 'text-green-700' : 'text-red-600' }}">
                        <i class='bx {{ $procOk ? "bx-badge-check" : "bx-error-circle" }}'></i>
                        Processing details
                    </p>

                    <p class="text-slate-600">
                        Processed by:
                        <span class="font-medium">{{ $ocrResult['processed_by'] ?? 'Not found' }}</span>
                    </p>
                    <p class="text-slate-600">
                        Signature:
                        <span class="font-medium">{{ ($ocrResult['has_signature'] ?? false) ? 'Present' : 'Missing' }}</span>
                    </p>
                    <p class="text-slate-600">
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

                @if($isVerified)
                    <button wire:click="goToSystem"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition">
                        Continue to System
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>