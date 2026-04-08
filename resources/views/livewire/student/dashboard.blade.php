<div class="space-y-6" wire:poll.5s>
    @php
        $dashboardStatusClasses = match ($currentClearanceStatus['status']) {
            'Cleared' => 'border-[#cfe9db] bg-[#f3fbf6] text-[#1f8a4c]',
            'Pending' => 'border-[#ffd9a1] bg-[#fff8ef] text-[#f59e0b]',
            'Uncleared' => 'border-[#efc8c1] bg-[#fdf3f1] text-[#b9432d]',
            default => 'border-slate-200 bg-white text-slate-700',
        };
    @endphp
    <section class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="rounded-[28px] border border-[#d7e0ee] bg-gradient-to-br from-[#ffffff] via-[#f8fbff] to-[#edf4ff] p-7 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#2A57B4]">
                Student Dashboard
            </p>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">
                Welcome back, {{ $fullName ?: ($user->first_name ?? 'Student') }}
            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Manage your DocuMate account, track your verification progress, and keep your personal information updated from one place.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('profile') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-[#2A57B4] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#214795]">
                    <i class='bx bx-user-circle text-lg'></i>
                    <span>Open Profile</span>
                </a>

                <a href="{{ route('student.new-transaction') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    <i class='bx bx-plus-circle text-lg'></i>
                    <span>New Transaction</span>
                </a>

                <a href="{{ route('student.clearance-status') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-[#2A57B4]/20 bg-[#2A57B4]/5 px-5 py-3 text-sm font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4]/10">
                    <i class='bx bx-check-circle text-lg'></i>
                    <span>View Clearance Status</span>
                </a>
            </div>
        </div>

        <div class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
            <div class="flex items-center gap-4">
                @if($user->profile_picture)
                    <img src="{{ asset('storage/' . $user->profile_picture) }}"
                         alt="{{ $fullName }}"
                         class="h-16 w-16 rounded-full object-cover shadow-sm">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#2A57B4] text-xl font-bold text-white shadow-sm">
                        {{ strtoupper(substr($user->first_name ?? 'D', 0, 1) . substr($user->last_name ?? 'M', 0, 1)) }}
                    </div>
                @endif

                <div>
                    <p class="text-lg font-bold text-slate-900">{{ $fullName ?: 'DocuMate User' }}</p>
                    <p class="text-sm text-[#2A57B4]">{{ $user->student_number ?: 'No student number' }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-slate-500">Email</span>
                    <span class="font-medium text-slate-800">{{ $user->email ?: '-' }}</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-slate-500">Program</span>
                    <span class="font-medium text-slate-800">{{ $user->program ?: '-' }}</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-slate-500">Year Level</span>
                    <span class="font-medium text-slate-800">{{ $formattedYearLevel }}</span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-slate-500">Status</span>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ($user->account_status ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ ucfirst(str_replace('_', ' ', $user->account_status ?? 'inactive')) }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Profile Access</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Profile Information</h2>
                    <p class="mt-2 text-sm text-slate-500">Review and edit your personal information using the same profile form available from the sidebar.</p>
                </div>

                <a href="{{ route('profile') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-[#2A57B4]/20 bg-[#2A57B4]/5 px-4 py-2 text-sm font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4]/10">
                    <i class='bx bx-edit-alt'></i>
                    <span>Edit Profile</span>
                </a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Personal</p>
                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><span class="font-semibold text-slate-900">Name:</span> {{ $fullName ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Sex:</span> {{ $user->sex ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Date of Birth:</span> {{ $user->date_of_birth ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Contact:</span> {{ $user->contact_number ?: '-' }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Academic</p>
                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><span class="font-semibold text-slate-900">Student No.:</span> {{ $user->student_number ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Program:</span> {{ $user->program ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Year:</span> {{ $formattedYearLevel }}</p>
                        <p><span class="font-semibold text-slate-900">Academic Status:</span> {{ $user->academic_status ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Clearance</p>
            <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Current Clearance Status</h2>

            <div class="mt-5 space-y-3">
                <div class="rounded-2xl border px-4 py-4 {{ $dashboardStatusClasses }}">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Current Status</p>
                    <p class="mt-2 text-4xl font-extrabold leading-none">{{ $currentClearanceStatus['status_word'] }}</p>
                    <p class="mt-2 text-xs font-black uppercase tracking-[0.14em] text-slate-700">{{ $currentClearanceStatus['period_label'] }}</p>
                    <div class="mt-4 space-y-1 text-sm text-slate-700">
                        <p><span class="font-semibold text-slate-900">Tagged by:</span> {{ $currentClearanceStatus['tagged_by'] ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Remarks:</span> {{ $currentClearanceStatus['remarks'] ?: '-' }}</p>
                        <p><span class="font-semibold text-slate-900">Last Updated:</span> {{ $currentClearanceStatus['last_updated'] }}</p>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Current Semester</p>
                    <p class="mt-2 text-lg font-bold text-slate-900">{{ currentSemester() ?? 'Not set' }}</p>
                    <p class="text-sm text-slate-500">{{ currentAcademicYear() ?? 'Academic year not set' }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Verification Window</p>
                    @if(isVerificationOpen())
                        <p class="mt-2 text-lg font-bold text-emerald-600">Open</p>
                        <p class="text-sm text-slate-500">Your account is currently eligible for verification review.</p>
                    @else
                        <p class="mt-2 text-lg font-bold text-amber-600">Closed</p>
                        <p class="text-sm text-slate-500">Verification is not currently active for this term.</p>
                    @endif
                </div>

                <a href="{{ route('student.clearance-status') }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i class='bx bx-check-circle'></i>
                    <span>Open Clearance Status</span>
                </a>
            </div>
        </div>
    </section>
</div>
