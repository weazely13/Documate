<div wire:poll.10s="refreshStatus" class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Live Queue</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $appointment->appointment_date->format('F j, Y') }} · {{ $appointment->sessionLabel() }}</p>
        </div>
        <a href="{{ route('student.appointments.show', $appointment->appointment_id) }}" wire:navigate class="text-sm font-medium text-slate-500">← Details</a>
    </div>

    @if($appointment->status !== 'approved')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            This appointment is {{ $appointment->status }}. Live queue tracking only applies to approved appointments.
        </div>
    @else
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Your Number</p>
                <p class="mt-2 text-5xl font-bold text-[#2A57B4]">{{ $appointment->queue_number }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Now Serving</p>
                <p class="mt-2 text-5xl font-bold text-slate-900">{{ $this->currentServingNumber ?? '—' }}</p>
            </div>
        </div>

        @if($appointment->isWithinServiceWindow() && $this->currentServingNumber === $appointment->queue_number)
            <div class="rounded-xl bg-emerald-50 p-4 text-center text-sm font-medium text-emerald-700">
                It's your turn — please proceed to the office now.
            </div>
        @elseif(!$appointment->appointment_date->isToday())
            <div class="rounded-xl bg-blue-50 p-4 text-center text-sm text-blue-700">
                Queue tracking activates on your scheduled date.
            </div>
        @elseif(!$appointment->isWithinServiceWindow())
            <div class="rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-600">
                It's outside your session's office hours ({{ $appointment->sessionLabel() }}). Please come back during that window.
            </div>
        @elseif($this->currentServingNumber !== null && $appointment->queue_number < $this->currentServingNumber)
            <div class="rounded-xl bg-rose-50 p-4 text-center text-sm text-rose-600">
                You may have been overtaken — please check in with the office.
            </div>
        @else
            <div class="rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-600">
                Please wait for your number to be called.
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-sm font-semibold text-slate-900">All Queue Numbers Today</h3>
            <div class="mt-3 grid grid-cols-6 gap-2 sm:grid-cols-8">
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
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Attended</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#2A57B4]"></span> Now serving</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span> Retracted</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-white border border-slate-300"></span> Not yet served</span>
            </div>
        </div>
    @endif
</div>