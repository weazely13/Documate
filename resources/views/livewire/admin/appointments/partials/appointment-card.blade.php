@php
    $badge = match($appt->status) {
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'attended' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'missed' => 'bg-rose-50 text-rose-700 border-rose-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
        'retracted' => 'bg-slate-100 text-slate-500 border-slate-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $isNowServing = $appt->status === 'approved'
        && $appt->queue_number
        && $appt->queue_number === ($this->queueBoard[$appt->session]['now_serving'] ?? null);
    $selectable = ($selectable ?? true) && in_array($appt->status, ['pending', 'approved'], true);
    $showDate = $showDate ?? false; // NEW
    $aiTone = match($appt->ai_flag ?? 'pending') {
        'clean' => ['bg-emerald-50 text-emerald-600', 'Clean'],
        'flagged' => ['bg-rose-50 text-rose-600', 'Flagged'],
        'review_failed' => ['bg-amber-50 text-amber-600', 'Review failed'],
        default => ['bg-slate-100 text-slate-400', 'Reviewing…'],
    };
    $prob = $appt->ai_incorrect_probability;
    $purpose = $appt->purpose ?? 'General Visit';
    $docName = $appt->workspace?->template?->name ?? 'General Visit';
    $fullName = trim(($appt->user->first_name ?? '') . ' ' . ($appt->user->last_name ?? ''));
@endphp

<div class="group relative flex items-center justify-between gap-3 rounded-xl border bg-white p-3 shadow-sm transition hover:shadow-md {{ $isNowServing ? 'border-[#2A57B4] ring-2 ring-[#2A57B4]/20' : 'border-slate-200 hover:border-slate-300' }}">
    <div class="flex items-center gap-3 min-w-0 flex-1">
        @if($selectable)
            <input type="checkbox"
                wire:key="cb-{{ $appt->appointment_id }}-{{ in_array($appt->appointment_id, $selectedIds) ? '1' : '0' }}"
                wire:click="toggleSelect({{ $appt->appointment_id }})"
                @checked(in_array($appt->appointment_id, $selectedIds))
                class="h-4 w-4 shrink-0 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4] cursor-pointer">
        @endif

        <div class="min-w-0 flex-1 space-y-1">
            <a href="{{ route('admin.appointments.show', $appt->appointment_id) }}" wire:navigate
            title="{{ $purpose }}"
            class="block truncate font-bold text-base text-slate-900 group-hover:text-[#2A57B4] transition-colors">
                {{ $purpose }}
            </a>

            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <!-- User Name -->
                <span class="block max-w-[160px] truncate font-semibold text-slate-700 sm:max-w-[220px]" title="{{ $fullName }}">
                    {{ $fullName }}
                </span>

                @if($showDate)
                    <span>·</span>
                    <span class="inline-flex items-center gap-1 font-medium text-slate-600">
                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $appt->appointment_date->format('M j, Y') }}
                    </span>
                @endif

                <span>·</span>
                <!-- Document / General Visit Tag with Document Icon -->
                <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600 border border-slate-200" title="{{ $docName }}">
                    <svg class="h-3.5 w-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span class="truncate max-w-[140px]">{{ $docName }}</span>
                </span>
                <span>·</span>
                <span class="capitalize font-medium">{{ $appt->session }}</span>
            </div>
        </div>
    </div>

    <!-- Right Side Badges & Queue Number -->
    <div class="flex flex-col items-end gap-1.5 shrink-0">
        <div class="flex items-center gap-1.5">
            @if($appt->queue_number)
                <span class="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                    #{{ $appt->queue_number }}
                </span>
            @endif
            <span class="rounded-md border px-2 py-0.5 text-[10px] font-medium capitalize {{ $badge }}">
                {{ $appt->status }}
            </span>
        </div>

        <div class="flex items-center gap-1.5">
            @if(in_array($appt->status, ['pending', 'approved'], true))
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $aiTone[0] }}">
                    {{ $aiTone[1] }}{{ $prob !== null ? ' · ' . $prob . '%' : '' }}
                </span>
            @endif
            @if($isNowServing)
                <span class="inline-flex items-center gap-1 font-semibold text-[10px] text-[#2A57B4]">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#2A57B4]"></span>
                    Serving
                </span>
            @endif
        </div>
    </div>
</div>