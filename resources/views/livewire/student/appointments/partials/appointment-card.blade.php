@php
    $badgeStyle = match($appt->status) {
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        default => 'bg-amber-50 text-amber-700 border-amber-200/80',
    };
    $purpose = addslashes($appt->purpose ?: 'General Visit');
    $docName = addslashes($appt->workspace?->template?->name ?? 'General Visit');
@endphp

<div x-show="matchUpcoming('{{ $purpose }}', '{{ $docName }}', '{{ $appt->status }}')"
    class="group overflow-hidden rounded-xl border border-slate-200/80 bg-white transition-colors hover:border-[#2A57B4]/50">
    
    {{-- Minimal Live Queue Tracking Banner (Approved Only) --}}
    @if($appt->status === 'approved')
        @if(!$appt->appointment_date?->isToday())
            <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-3.5 sm:px-4 py-1.5 text-[11px] font-medium text-slate-500">
                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                Queue tracking opens {{ $appt->appointment_date?->format('M j') }}
            </div>
        @elseif(!$appt->isWithinServiceWindow())
            <div class="flex items-center gap-2 border-b border-amber-100/60 bg-amber-50/40 px-3.5 sm:px-4 py-1.5 text-[11px] font-medium text-amber-700">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                Outside service hours ({{ $appt->sessionLabel() }})
            </div>
        @elseif($appt->now_serving === $appt->queue_number)
            <div class="flex items-center gap-2 border-b border-emerald-100 bg-emerald-50/70 px-3.5 sm:px-4 py-1.5 text-[11px] font-semibold text-emerald-800">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                Your turn — Please proceed to counter
            </div>
        @elseif($appt->now_serving !== null && $appt->queue_number < $appt->now_serving)
            <div class="flex items-center gap-2 border-b border-rose-100 bg-rose-50/50 px-3.5 sm:px-4 py-1.5 text-[11px] font-medium text-rose-700">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                Queue passed — Check in with staff
            </div>
        @else
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-3.5 sm:px-4 py-1.5 text-[11px] text-slate-600">
                <div class="flex items-center gap-2 font-medium">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    </span>
                    @if($appt->now_serving !== null)
                        <span>Now Serving: <strong class="text-slate-800">#{{ $appt->now_serving }}</strong></span>
                    @else
                        <span>Live Queue · Waiting for call</span>
                    @endif
                </div>
                @if($appt->now_serving !== null)
                    <span class="font-semibold text-slate-500">
                        {{ $appt->queue_number - $appt->now_serving }} behind
                    </span>
                @endif
            </div>
        @endif
    @endif

    {{-- Main Card Body --}}
    <div class="p-3.5 sm:p-4">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 sm:gap-4">
            <a href="{{ route('student.appointments.show', $appt->appointment_id) }}" wire:navigate class="min-w-0 flex-1 space-y-2">
                <h4 class="text-base font-bold text-slate-900 leading-snug group-hover:text-[#2A57B4]">
                    {{ $appt->purpose ?: 'General Visit' }}
                </h4>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-500">
                    <div class="flex items-center gap-1.5 font-medium text-[#2A57B4]">
                        <svg class="h-3.5 w-3.5 shrink-0 text-[#2A57B4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>{{ $appt->workspace?->template?->name ?? 'General Visit' }}</span>
                    </div>

                    <span class="hidden sm:inline text-slate-300">•</span>

                    <div class="flex items-center gap-1 text-slate-500">
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $appt->appointment_date?->format('M j, Y') ?? 'N/A' }}</span>
                    </div>

                    <span class="hidden sm:inline text-slate-300">•</span>

                    <div class="flex items-center gap-1 text-slate-500">
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $appt->sessionLabel() }}</span>
                    </div>

                    @if($appt->reschedule_count > 0)
                        <span class="text-[11px] text-slate-400">(Rescheduled)</span>
                    @endif
                </div>
            </a>

            <div class="flex items-center justify-between sm:flex-col sm:items-end gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-slate-100">
                <div class="flex items-center gap-2">
                    @if(isset($appt->queue_number) && $appt->queue_number)
                        <div class="inline-flex items-center gap-1 rounded-md bg-slate-100/80 px-2 py-0.5 text-xs text-slate-600 border border-slate-200/60">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Queue</span>
                            <span class="text-slate-300">•</span>
                            <span class="font-bold text-slate-800">#{{ $appt->queue_number }}</span>
                        </div>
                    @endif

                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize {{ $badgeStyle }}">
                        {{ $appt->status }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    @if($appt->canBeDeleted())
                        <button type="button" 
                            wire:click="confirmRetract({{ $appt->appointment_id }})"
                            title="Retract Appointment"
                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif

                    @if($appt->status === 'missed' && $appt->canReapply())
                        <a href="{{ route('student.appointments.reapply', $appt->appointment_id) }}" wire:navigate
                            onclick="event.stopPropagation()"
                            class="rounded-lg bg-[#2A57B4] px-2.5 py-1 text-xs font-semibold text-white hover:bg-[#204491]">
                            Reapply
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>