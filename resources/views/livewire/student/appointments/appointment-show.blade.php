<div wire:poll.10s="refreshStatus" class="mx-auto max-w-4xl space-y-4 sm:space-y-6 px-3 py-3 sm:px-4 sm:py-6">

    {{-- Dynamic Title Header --}}
    @php
        $docTitle = $appointment->workspace?->template?->name;
        $hasDocument = !empty($docTitle);
        $displayTitle = $hasDocument ? $docTitle : ($appointment->purpose ?: 'General Visit');
        
        $statusBadge = match($appointment->status) {
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
            'attended' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
            'missed' => 'bg-rose-50 text-rose-700 border-rose-200/80',
            'retracted' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200/80',
        };
    @endphp

    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 sm:pb-4">
        <div class="flex items-start gap-3 min-w-0">
            <a href="{{ route('student.appointments.index') }}" wire:navigate 
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-xs transition-all hover:bg-slate-50 hover:text-slate-900 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg sm:text-2xl font-bold tracking-tight text-slate-900 truncate">
                        {{ $displayTitle }}
                    </h2>
                </div>
                <p class="text-xs text-slate-500 truncate">Live queue tracking & appointment details</p>
            </div>
        </div>

        {{-- Top-Right Status Badge --}}
        <div class="shrink-0 pt-0.5">
            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold capitalize shadow-xs {{ $statusBadge }}">
                {{ $appointment->status }}
            </span>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
        
        {{-- Left / Main Section --}}
        <div class="min-w-0 space-y-4 sm:space-y-5">

            {{-- Main Info Card --}}
            <div class="min-w-0 w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Appointment Information
                    </h3>
                    @if($hasDocument)
                        <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-[#2A57B4]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Document Attached
                        </span>
                    @endif
                </div>

                {{-- Mobile-Optimized Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <div class="rounded-xl bg-slate-50/60 p-3 sm:bg-transparent sm:p-0 min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Purpose</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800 break-words">{{ $appointment->purpose ?: 'General Visit' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50/60 p-3 sm:bg-transparent sm:p-0 min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Document Requested</p>
                        <p class="mt-0.5 text-sm font-semibold text-[#2A57B4] break-words">
                            {{ $docTitle ?? 'No document attached' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50/60 p-3 sm:bg-transparent sm:p-0 min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Appointment Date</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $appointment->appointment_date?->format('F j, Y') ?? 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50/60 p-3 sm:bg-transparent sm:p-0 min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Time Session</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $appointment->sessionLabel() }}</p>
                    </div>
                </div>

                {{-- Attached Document Preview Container --}}
                @if($appointment->workspace)
                    <div x-data="{ scale: 1, minScale: 0.5, maxScale: 2.5 }" class="pt-2 border-t border-slate-100 min-w-0 max-w-full">
                        <div class="mb-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Attached Document Preview</p>
                        </div>
                        
                        {{-- Mobile Friendly File Banner --}}
                        <div class="mb-3 flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50/50 p-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#2A57B4] text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-900 truncate">{{ $docTitle }}</p>
                                    <p class="text-[11px] text-slate-500">Ready for review</p>
                                </div>
                            </div>
                        </div>

                        {{-- Fully Fitted & Interactive Zoomable Container --}}
                        <div class="relative w-full overflow-auto max-h-[600px] rounded-xl border border-slate-200 bg-slate-100/70 p-2 sm:p-4 touch-pan-x touch-pan-y">
                            <div class="w-full flex justify-start items-start">
                                <div class="w-full max-w-full origin-top-left transition-transform duration-150"
                                     :style="`transform: scale(${scale});`">
                                    <div class="[&_img]:w-full [&_img]:h-auto [&_img]:max-w-full [&_svg]:w-full [&_svg]:h-auto [&_iframe]:w-full [&_iframe]:min-h-[500px]">
                                        @include('livewire.partials.document-preview', ['workspace' => $appointment->workspace])
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Zoom Controls below Preview --}}
                        <div class="mt-3 flex items-center justify-end">
                            <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 shadow-xs">
                                <button type="button" 
                                    @click="scale = Math.max(minScale, scale - 0.25)" 
                                    title="Zoom Out"
                                    class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 shadow-xs hover:bg-slate-100 active:scale-95 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>

                                <span class="px-2 text-xs font-semibold text-slate-600 min-w-[3rem] text-center" x-text="Math.round(scale * 100) + '%'"></span>

                                <button type="button" 
                                    @click="scale = Math.min(maxScale, scale + 0.25)" 
                                    title="Zoom In"
                                    class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 shadow-xs hover:bg-slate-100 active:scale-95 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>

                                <button type="button" 
                                    @click="scale = 1" 
                                    title="Reset Zoom"
                                    class="ml-1 rounded-md px-2 py-1 text-[11px] font-medium text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Alerts / Notes Cards --}}
            @if($appointment->admin_notes)
                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#2A57B4]">
                        <svg class="h-4 w-4 shrink-0 text-[#2A57B4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <span>Note from the Office</span>
                    </div>
                    <p class="mt-1.5 text-xs sm:text-sm text-slate-700 leading-relaxed">{{ $appointment->admin_notes }}</p>
                </div>
            @endif

            @if($appointment->reschedule_count > 0)
                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-[#2A57B4]">Rescheduled</span>
                        <span class="text-xs text-slate-500">{{ $appointment->reschedule_count }} time(s)</span>
                    </div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 pt-1">Reason for Reschedule</p>
                    <p class="text-xs sm:text-sm text-slate-700">
                        {{ $appointment->reschedule_reason ?: 'No reason specified.' }}
                    </p>
                </div>
            @endif

            @if($appointment->status === 'missed' && $appointment->canReapply())
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-amber-200/80 bg-amber-50/60 p-4">
                    <div class="space-y-0.5">
                        <p class="text-xs font-bold text-amber-900">Missed Appointment</p>
                        <p class="text-xs text-amber-700">You missed this scheduled visit. Select a new date to reapply.</p>
                    </div>
                    <a href="{{ route('student.appointments.reapply', $appointment->appointment_id) }}" wire:navigate
                        class="inline-flex w-full sm:w-auto shrink-0 items-center justify-center rounded-xl bg-[#2A57B4] px-4 py-2.5 sm:py-2 text-xs font-semibold text-white transition-all hover:bg-[#204491] active:scale-95">
                        Reapply Appointment
                    </a>
                </div>
            @endif
        </div>

        {{-- Right Column Sidebar --}}
        <div class="min-w-0 space-y-4">
            {{-- Queue Tracking Block --}}
            @if($appointment->status === 'approved')
                <div class="rounded-xl border-2 border-[#2A57B4] bg-blue-50 p-4">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#2A57B4] opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-[#2A57B4]"></span>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#2A57B4]">Queue Status</p>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="rounded-xl bg-white/70 p-3 text-center">
                            <p class="text-xs text-blue-500">Your Number</p>
                            <p class="text-3xl sm:text-4xl font-bold text-[#2A57B4]">{{ $appointment->queue_number }}</p>
                        </div>
                        <div class="rounded-xl bg-white/70 p-3 text-center">
                            <p class="text-xs text-blue-500">Now Serving</p>
                            <p class="text-3xl sm:text-4xl font-bold text-slate-900">{{ $this->currentServingNumber ?? '—' }}</p>
                        </div>
                    </div>

                    @if(!$appointment->appointment_date->isToday())
                        <p class="mt-3 text-sm text-blue-700">Queue tracking opens on {{ $appointment->appointment_date->format('F j, Y') }}.</p>
                    @elseif(!$appointment->isWithinServiceWindow())
                        <p class="mt-3 text-sm text-blue-700">It's outside your session's office hours ({{ $appointment->sessionLabel() }}). Please return during that window.</p>
                    @elseif($this->currentServingNumber === $appointment->queue_number)
                        <p class="mt-3 text-sm font-medium text-emerald-600">It's your turn — please proceed to the office.</p>
                    @elseif($this->currentServingNumber !== null && $appointment->queue_number < $this->currentServingNumber)
                        <p class="mt-3 text-sm font-medium text-rose-600">You may have been overtaken — please check in with the office.</p>
                    @else
                        <p class="mt-3 text-sm text-blue-700">Please wait for your number to be called.</p>
                    @endif

                    {{-- All queue numbers with indicators --}}
                    @if($this->queueList->isNotEmpty())
                        <div class="mt-4 rounded-xl bg-white/70 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">All Queue Numbers — {{ $appointment->appointment_date->format('M j') }} · {{ $appointment->sessionLabel() }}</p>
                            <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-2">
                                @foreach($this->queueList as $q)
                                    @php
                                        $isMe = $q->appointment_id === $appointment->appointment_id;
                                        $style = match(true) {
                                            $q->status === 'attended' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            $q->status === 'retracted' => 'bg-slate-100 text-slate-400 border-slate-200 line-through',
                                            $q->queue_number === $this->currentServingNumber => 'bg-[#2A57B4] text-white border-[#2A57B4]',
                                            default => 'bg-white text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <div class="flex h-10 items-center justify-center rounded-lg border text-sm font-semibold {{ $style }} {{ $isMe ? 'ring-2 ring-offset-1 ring-[#2A57B4]' : '' }}">
                                        {{ $q->queue_number }}
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2.5 sm:gap-3 text-xs text-slate-500">
                                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Attended</span>
                                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#2A57B4]"></span> Now serving</span>
                                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span> Retracted</span>
                                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-white border border-slate-300"></span> Not yet served</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Timeline Block --}}
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
</div>