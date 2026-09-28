<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px] items-start">
    <div class="space-y-6">
        <div x-data class="space-y-6">
            <!-- Header Nav -->
            <div class="flex items-center justify-between border-b border-slate-200/80 pb-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $appointment->user->first_name }} {{ $appointment->user->last_name }}</h2>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Appointment Details & Overview</p>
                </div>
                <a href="{{ route('admin.appointments.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to list
                </a>
            </div>

            <!-- Profile & Schedule Hero Grid -->
            <div class="grid gap-4 sm:grid-cols-2">
                <!-- User Profile Card -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all hover:border-slate-300">
                    <div class="flex items-center gap-4">
                        <img src="{{ $appointment->user->profile_picture ? asset('storage/' . $appointment->user->profile_picture) : asset('images/backdrop.jpg') }}"
                            class="h-16 w-16 rounded-xl object-cover ring-2 ring-slate-100 shadow-sm">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-slate-900">{{ $appointment->user->first_name }} {{ $appointment->user->last_name }}</p>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">{{ $appointment->user->student_number }}</p>
                            <span class="mt-2 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                {{ $appointment->user->program?->name ?: '-' }} · {{ $appointment->user->year_level }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Queue Card -->
                <div class="relative overflow-hidden rounded-2xl border border-blue-200/80 bg-gradient-to-br from-blue-50 via-white to-blue-50/30 p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Schedule</span>
                            <p class="mt-1 font-semibold text-slate-900">{{ $appointment->appointment_date->format('F j, Y') }}</p>
                            <p class="text-xs font-medium text-blue-600/90">{{ $appointment->sessionLabel() }}</p>
                        </div>
                        @if($appointment->queue_number)
                            <div class="text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Queue No.</span>
                                <p class="text-3xl font-extrabold text-[#2A57B4] tracking-tight">#{{ $appointment->queue_number }}</p>
                            </div>
                        @endif
                    </div>
                    @if(!$appointment->queue_number)
                        <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200/60 px-2.5 py-1.5">
                            <p class="text-xs font-medium text-amber-700">Queue number assigned upon approval</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Purpose & Clearance Row -->
            <div class="grid gap-4 sm:grid-cols-2">
                <!-- Purpose Card -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Purpose of transaction</p>
                    <p class="mt-1.5 text-sm font-medium text-slate-700 leading-relaxed">{{ $appointment->purpose }}</p>
                </div>

                <!-- Clearance Status Card (single, switchable) -->
                @php
                    $clearanceTheme = fn (string $status) => match ($status) {
                        'Cleared' => [
                            'badge' => 'bg-emerald-500/10 text-emerald-700 border-emerald-200/80',
                            'border' => 'border-emerald-200/60',
                            'bg' => 'bg-emerald-50/40',
                            'icon' => 'bx-check-shield',
                            'iconWrap' => 'bg-emerald-500 text-white shadow-emerald-500/25',
                            'word' => 'text-emerald-600',
                        ],
                        'Pending' => [
                            'badge' => 'bg-amber-500/10 text-amber-700 border-amber-200/80',
                            'border' => 'border-amber-200/60',
                            'bg' => 'bg-amber-50/40',
                            'icon' => 'bx-time-five',
                            'iconWrap' => 'bg-amber-500 text-white shadow-amber-500/25',
                            'word' => 'text-amber-600',
                        ],
                        'Uncleared' => [
                            'badge' => 'bg-rose-500/10 text-rose-700 border-rose-200/80',
                            'border' => 'border-rose-200/60',
                            'bg' => 'bg-rose-50/40',
                            'icon' => 'bx-x-circle',
                            'iconWrap' => 'bg-rose-500 text-white shadow-rose-500/25',
                            'word' => 'text-rose-600',
                        ],
                        default => [
                            'badge' => 'bg-slate-500/10 text-slate-700 border-slate-200',
                            'border' => 'border-slate-200',
                            'bg' => 'bg-slate-50/40',
                            'icon' => 'bx-help-circle',
                            'iconWrap' => 'bg-slate-500 text-white shadow-slate-500/25',
                            'word' => 'text-slate-600',
                        ],
                    };
                    $selectedClearanceTheme = $clearanceTheme($this->selectedClearance['status']);
                @endphp

                <div class="rounded-2xl border {{ $selectedClearanceTheme['border'] }} {{ $selectedClearanceTheme['bg'] }} p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Clearance Status</p>

                        @if($this->clearanceSemesterOptions->isNotEmpty())
                            <div class="relative">
                                <select wire:model.live="clearanceSemesterFilter"
                                        class="appearance-none rounded-lg border border-slate-200/80 bg-white/80 py-1 pl-2.5 pr-6 text-[11px] font-semibold text-slate-600 transition hover:bg-white focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/10">
                                    <option value="">Current Term</option>
                                    @foreach($this->clearanceSemesterOptions as $sem)
                                        <option value="{{ $sem }}">{{ $sem }}</option>
                                    @endforeach
                                </select>
                                <i class='bx bx-chevron-down pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-xs text-slate-400'></i>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm {{ $selectedClearanceTheme['iconWrap'] }}">
                            <i class='bx {{ $selectedClearanceTheme['icon'] }} text-xl'></i>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $this->selectedClearance['period_label'] }}</p>
                            <p class="text-xl font-black tracking-tight {{ $selectedClearanceTheme['word'] }}">
                                {{ $this->selectedClearance['status_word'] }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-200/60 pt-2.5 text-[11px]">
                        <div class="min-w-0">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Tagged By</span>
                            <p class="truncate font-semibold text-slate-700">{{ $this->selectedClearance['tagged_by'] ?: '-' }}</p>
                        </div>
                        <div class="min-w-0 text-right">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Last Updated</span>
                            <p class="truncate font-medium text-slate-500">{{ $this->selectedClearance['last_updated'] ?: '-' }}</p>
                        </div>
                    </div>

                    @if($this->selectedClearance['remarks'])
                        <p class="mt-2 truncate text-[11px] font-medium text-slate-600" title="{{ $this->selectedClearance['remarks'] }}">
                            {{ $this->selectedClearance['remarks'] }}
                        </p>
                    @endif
                </div>
            </div>
            

            <!-- Document Card -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Document</p>
                        <p class="mt-1 font-semibold text-slate-900">
                            {{ $appointment->workspace?->template?->name ?? 'No document attached' }}
                        </p>
                    </div>
                    @if($appointment->workspace?->generated_pdf_path)
                        <a href="{{ route('admin.transactions.download-pdf', $appointment->workspace) }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-[#2A57B4] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download PDF
                        </a>
                    @endif
                </div>

                <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                    @include('livewire.partials.document-preview', ['workspace' => $appointment->workspace])
                </div>

                @if($appointment->workspace?->missed_count > 0)
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-medium text-rose-600 bg-rose-50 rounded-lg p-2.5 border border-rose-100">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Missed {{ $appointment->workspace->missed_count }} previous appointment(s) for this document.
                    </div>
                @endif
            </div>
            <!-- AI Review Panel — ADD THIS -->
            @include('livewire.admin.appointments.partials.ai-review-panel', ['appointment' => $appointment])
            <!-- Reschedule Alert -->
            @if($appointment->reschedule_count > 0)
                <div class="rounded-2xl border border-blue-200/80 bg-blue-50/50 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            Rescheduled
                        </span>
                        <span class="text-xs font-medium text-blue-600">{{ $appointment->reschedule_count }} time(s)</span>
                    </div>
                    <div class="mt-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600/80">Reason for reschedule</p>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ $appointment->reschedule_reason ?: 'There is no reason for reschedule indicated.' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Action Controls for Pending -->
            @if($appointment->status === 'pending')
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button @click="$wire.showApproveConfirm = true" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">Approve</button>
                    <button @click="$wire.showReschedule = true" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">Reschedule</button>
                    <button @click="$wire.showReject = true" class="rounded-xl border border-rose-200 bg-rose-50/50 px-5 py-2.5 text-sm font-semibold text-rose-600 shadow-sm transition-all hover:bg-rose-100/70 active:scale-95">Reject</button>
                </div>
            @endif

            {{-- APPROVE CONFIRM MODAL — NEW --}}
            <template x-teleport="body">
                <div x-show="$wire.showApproveConfirm" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40">
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl transition-all">
                        <h3 class="text-lg font-bold text-slate-900">Approve Appointment</h3>
                        <p class="mt-2 text-xs font-medium text-slate-600 leading-relaxed">
                            Approve this appointment for <span class="font-bold text-slate-900">{{ $appointment->user->first_name }} {{ $appointment->user->last_name }}</span>? A queue number will be assigned.
                        </p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="$wire.showApproveConfirm = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                            <button wire:click="approve" @click="$wire.showApproveConfirm = false" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">Yes, approve</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- REJECT MODAL — reason dropdown added --}}
            <template x-teleport="body">
                <div x-show="$wire.showReject" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl transition-all">
                        <h3 class="text-lg font-bold text-slate-900">Reject Appointment</h3>
                        <p class="text-xs text-slate-500 mt-1">Select a reason, use a template, or type your own below.</p>

                        <select wire:change="useRejectionOption($event.target.value)" class="mt-3 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                            <option value="">-- Select Rejection Reason --</option>
                            <option value="Incomplete or unreadable supporting documents submitted.">Incomplete or unreadable supporting documents submitted.</option>
                            <option value="Duplicate or conflicting appointment request detected.">Duplicate or conflicting appointment request detected.</option>
                            <option value="Ineligible for requested appointment type or service.">Ineligible for requested appointment type or service.</option>
                            <option value="Custom">Other / Custom Reason</option>
                        </select>

                        <textarea wire:model="rejectionReason" rows="4" placeholder="Enter rejection reason..." class="mt-3 w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"></textarea>
                        @error('rejectionReason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="$wire.showReject = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                            <button wire:click="reject" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700">Reject</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- RESCHEDULE MODAL — reason dropdown added --}}
            <template x-teleport="body">
                <div x-show="$wire.showReschedule" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40">
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl transition-all">
                        <h3 class="text-lg font-bold text-slate-900">Reschedule Appointment</h3>
                        <input type="date" wire:model="rescheduleDate" class="mt-4 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <select wire:model="rescheduleSession" class="mt-2.5 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Select session</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                        </select>
                        @error('rescheduleDate') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror

                        <select wire:change="useRescheduleOption($event.target.value)" class="mt-2.5 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">-- Select Reason --</option>
                            <option value="Official event conflict or university closure.">Official event conflict or university closure.</option>
                            <option value="Assigned staff unavailable on original date.">Assigned staff unavailable on original date.</option>
                            <option value="System adjustment / Queue capacity reallocation.">System adjustment / Queue capacity reallocation.</option>
                            <option value="Custom">Other / Custom Reason</option>
                        </select>

                        <textarea wire:model="rescheduleReason" rows="3" placeholder="Reason for rescheduling (required)..."
                            class="mt-2.5 w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                        @error('rescheduleReason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="$wire.showReschedule = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                            <button wire:click="reschedule" class="rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Confirm</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ATTEND CONFIRM MODAL — teleported for the same backdrop fix --}}
            <template x-teleport="body">
                <div x-show="$wire.showAttendConfirm" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm bg-slate-900/40">
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl transition-all">
                        <h3 class="text-lg font-bold text-slate-900">Confirm Attendance</h3>
                        <p class="mt-2 text-xs font-medium text-slate-600 leading-relaxed">Mark queue <span class="font-bold text-slate-900">#{{ $appointment->queue_number }}</span> ({{ $appointment->user->first_name }}) as attended? This cannot be undone.</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button @click="$wire.showAttendConfirm = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                            <button wire:click="markAttended" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">Yes, mark attended</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Right Column Sidebar -->
    <div class="space-y-6">
        <!-- Currently Serving Banner -->
        @if($this->isNowServing)
            <div class="flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50/80 px-4 py-3.5 shadow-sm backdrop-blur-sm">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-xs font-bold text-white shadow-sm ring-2 ring-blue-200">
                    {{ $appointment->queue_number }}
                </span>
                <p class="text-sm font-semibold text-[#2A57B4]">You are currently serving this appointment.</p>
            </div>
        @endif

        <!-- Attendance & Notes Card (Approved & Today) -->
        @if($appointment->status === 'approved' && $appointment->appointment_date->isToday())
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes (optional)</label>
                    <textarea wire:model="adminNotes" rows="3" placeholder="Any remarks before marking this visit complete..."
                        class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all"></textarea>
                </div>
                <button type="button" @click="$wire.showAttendConfirm = true"
                    class="w-full rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">
                    Mark as Attended
                </button>
            </div>
        @endif

        <!-- Timeline Card (Unchanged) -->
        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-sm font-semibold text-slate-900">Timeline</h3>
            <div class="mt-4 space-y-4">
                @foreach($this->timeline as $item)
                    @php
                        $dot = match($item['tone']) {
                            'green' => 'bg-emerald-500', 'blue' => 'bg-blue-500',
                            'amber' => 'bg-amber-500', 'red' => 'bg-rose-500', default => 'bg-slate-300',
                        };
                    @endphp
                    <div class="relative pl-6">
                        <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                        @unless($loop->last)
                            <span class="absolute left-[4px] top-4 h-full w-px bg-slate-200"></span>
                        @endunless
                        <p class="text-sm font-medium text-slate-800">{{ $item['title'] }}</p>
                        <p class="text-xs text-slate-400">{{ $item['date'] }}</p>
                        @if(!empty($item['note']))
                            <p class="mt-1 text-xs text-slate-500">{{ $item['note'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>