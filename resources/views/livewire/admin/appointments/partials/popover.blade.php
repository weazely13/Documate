{{-- A calendar appointment's pop-up card: who, what, where it stands, the rundown so far, and the way to the full record.
     Beside the card on wide screens (opening toward the free side), a bottom sheet on phones.
     Needs: $p (appointment with user.program, workspace, statusLogs), $side ('left' | 'right'), $statusFor, $aiFor, $initials, $avatar, $focus. --}}
@php
    [$sText, $sPill] = $statusFor($p);
    [$aText, $aPill, $spin] = $aiFor($p);
    $logs = $p->statusLogs->sortByDesc('created_at')->take(3)->sortBy('created_at')->values();
    $logLabel = fn ($l) => match ($l->status) {
        'pending' => $l->note ?: 'Requested',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'attended' => 'Visit completed',
        'missed' => 'Missed',
        'retracted' => 'Withdrawn',
        default => ucfirst($l->status),
    };
@endphp
<div class="fixed inset-x-3 bottom-3 z-50 md:absolute md:inset-x-auto md:bottom-auto md:top-0 md:w-80 {{ $side === 'left' ? 'md:right-full md:mr-2' : 'md:left-full md:ml-2' }}"
     role="dialog" aria-modal="false" aria-labelledby="pop-title-{{ $p->appointment_id }}"
     x-data x-init="$nextTick(() => $refs.popTitle.focus())"
     @keydown.escape.window="if (! $wire.dialog) $wire.closePop()" @click.outside="if (! $wire.dialog) $wire.closePop()">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-lg">
        <div class="flex items-start gap-3">
            <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                {{ $initials($p->user) }}
                @if ($avatar($p->user))
                    <img src="{{ $avatar($p->user) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                @endif
            </span>
            <div class="min-w-0 flex-1">
                <p id="pop-title-{{ $p->appointment_id }}" x-ref="popTitle" tabindex="-1" class="truncate text-sm font-semibold text-slate-900 focus:outline-none">{{ $p->user?->full_name }}</p>
                <p class="flex flex-wrap gap-x-2 text-xs text-slate-500">
                    <span class="tabular-nums">{{ $p->user?->student_number }}</span>
                    @if ($p->user?->email) <span class="truncate">{{ $p->user->email }}</span> @endif
                </p>
            </div>
            <button type="button" wire:click="closePop"
                    class="-mr-1 -mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                    aria-label="Close">
                <i class="bx bx-x text-xl" aria-hidden="true"></i>
            </button>
        </div>

        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 pt-3 text-xs">
            <div class="min-w-0">
                <dt class="text-slate-500">Purpose</dt>
                <dd class="mt-0.5 break-words font-medium text-slate-900">{{ $p->purpose ?: 'General visit' }}</dd>
            </div>
            <div class="min-w-0">
                <dt class="text-slate-500">Document</dt>
                <dd class="mt-0.5 break-words font-medium text-slate-900">{{ $p->workspace?->documentName() ?? 'None' }}</dd>
            </div>
            <div class="min-w-0">
                <dt class="text-slate-500">When</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ $p->appointment_date->format('M j') }} · {{ $p->session === 'morning' ? '8:00–12:00' : '1:00–5:00' }}</dd>
            </div>
            <div class="min-w-0">
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-0.5 flex flex-wrap gap-1">
                    <span class="inline-flex rounded-full px-2 py-px text-[11px] font-medium ring-1 {{ $sPill }}">{{ $sText }}</span>
                    @if ($p->status === 'pending')
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-px text-[11px] font-medium ring-1 {{ $aPill }}">
                            @if ($spin) <i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i> @endif<span class="sr-only">AI check:</span> {{ $aText }}
                        </span>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($logs->isNotEmpty())
            <div class="mt-3">
                <p class="text-xs text-slate-500">Rundown</p>
                <ol class="mt-1.5 rounded-xl bg-slate-50 p-2.5">
                    @foreach ($logs as $l)
                        <li class="relative flex items-start gap-2.5 pb-2 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[34px] top-3 h-full w-px bg-[#2A57B4]/30" aria-hidden="true"></span>
                            @endunless
                            <span class="w-6 shrink-0 pt-px text-[10px] tabular-nums text-slate-500">{{ $l->created_at->format('H:i') }}</span>
                            <span class="relative mt-1 h-2 w-2 shrink-0 rounded-full bg-white ring-2 ring-[#2A57B4]" aria-hidden="true"></span>
                            <span class="min-w-0 text-xs font-medium text-slate-800">
                                {{ $logLabel($l) }}<span class="font-normal text-slate-500"> · {{ $l->created_at->format('M j') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="mt-3 grid gap-2">
            @if ($p->status === 'pending')
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="openDialog('reject', 'one', {{ $p->appointment_id }})"
                            class="inline-flex min-h-9 items-center justify-center rounded-lg border border-rose-200 text-sm font-medium text-rose-700 hover:bg-rose-50 [@media(pointer:coarse)]:min-h-11 {{ $focus }}">Reject</button>
                    <button type="button" wire:click="openDialog('approve', 'one', {{ $p->appointment_id }})"
                            class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#2A57B4] text-sm font-medium text-white hover:bg-[#24499A] [@media(pointer:coarse)]:min-h-11 {{ $focus }}">Approve</button>
                </div>
            @endif
            <a href="{{ route('admin.appointments.show', $p->appointment_id) }}" wire:navigate
               class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 hover:bg-slate-50 [@media(pointer:coarse)]:min-h-11 {{ $focus }}">
                See full record <i class="bx bx-up-arrow-alt rotate-45 text-base" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
