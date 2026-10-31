@php
    $a = $appointment;
    $u = $a->user;
    $ws = $a->workspace;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $primary = $btn . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $secondary = $btn . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $danger = $btn . ' border border-rose-200 bg-white text-rose-700 hover:bg-rose-50';
    $card = 'rounded-2xl border border-slate-200 bg-white p-4 sm:p-5';
    $field = 'w-full rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
    $row = 'grid grid-cols-[minmax(104px,128px)_minmax(0,1fr)] items-baseline gap-3 py-2.5';

    [$statusText, $statusPill] = match ($a->status) {
        'pending' => ['Pending review', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'approved' => ['Approved', 'bg-[#e2e9f7] text-[#1E4090] ring-sky-200'],
        'attended' => ['Attended', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'missed' => ['Missed', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'rejected' => ['Rejected', 'bg-rose-50 text-rose-800 ring-rose-200'],
        'retracted' => ['Withdrawn by the student', 'bg-slate-100 text-slate-600 ring-slate-200'],
        default => [ucfirst($a->status), 'bg-slate-100 text-slate-600 ring-slate-200'],
    };

    $parts = preg_split('/\s+/', trim($u?->full_name ?? '')) ?: [];
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) ?: '?';
    $avatar = $u?->profile_picture ? asset('storage/' . $u->profile_picture) : null;

    $paperless = $ws && $ws->document_type && $ws->workflow_status && $ws->workflow_status->value !== 'draft';
    $previewSrc = $paperless ? route('admin.workflow.preview', $ws->workspace_id) : null;

    $clearance = $this->selectedClearance;
    [$clrTint, $clrText, $clrDot] = match ($clearance['status']) {
        'Cleared' => ['bg-emerald-50', 'text-emerald-800', 'bg-emerald-600'],
        'Pending' => ['bg-amber-50', 'text-amber-800', 'bg-amber-500'],
        'Uncleared' => ['bg-rose-50', 'text-rose-800', 'bg-red-600'],
        default => ['bg-slate-100', 'text-slate-700', 'bg-slate-400'],
    };
    $tone = ['green' => 'bg-emerald-600 ring-emerald-600', 'red' => 'bg-red-600 ring-red-600', 'amber' => 'bg-white ring-amber-500', 'blue' => 'bg-white ring-[#2A57B4]'];
@endphp

<div class="mx-auto max-w-[1280px] space-y-5" x-data>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            <span class="relative grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-[#e2e9f7] text-lg font-semibold text-[#1E4090]" aria-hidden="true">
                {{ $initials }}
                @if ($avatar)
                    <img src="{{ $avatar }}" alt="" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                @endif
            </span>
            <div class="min-w-0">
                <h1 class="break-words text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $u?->full_name }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ collect([$u?->student_number, $u?->program?->name, $u?->year_level ? 'Year ' . $u->year_level : null])->filter()->implode(' · ') }}
                </p>
                <p class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusPill }}">{{ $statusText }}</span>
                    @if ($a->queue_number)
                        <span class="inline-flex items-center rounded-full bg-[#2A57B4] px-2.5 py-0.5 text-xs font-medium tabular-nums text-white">Queue #{{ $a->queue_number }}</span>
                    @endif
                    @if ($this->isNowServing)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-600 motion-reduce:animate-none" aria-hidden="true"></span> Being served now
                        </span>
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('admin.appointments.index', ['view' => 'list', 'date' => $a->appointment_date->toDateString()]) }}" wire:navigate class="{{ $secondary }} shrink-0 self-start">
            <i class="bx bx-arrow-back text-base text-slate-500" aria-hidden="true"></i> Back to appointments
        </a>
    </div>

    {{-- Phones: one column in reading order. Large screens: the record on the left, the office's side on the right. --}}
    <div class="flex flex-col gap-5 lg:grid lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">

        <div class="contents lg:block lg:min-w-0 lg:space-y-5">
            {{-- The visit --}}
            <section class="{{ $card }} order-1" aria-labelledby="rec-visit">
                <h2 id="rec-visit" class="text-[15px] font-semibold text-slate-900">Visit</h2>
                <dl class="mt-2 divide-y divide-slate-100 text-sm">
                    <div class="{{ $row }}">
                        <dt class="text-slate-500">When</dt>
                        <dd class="text-slate-900">{{ $a->appointment_date->format('l, F j, Y') }} · {{ ucfirst($a->session) }} ({{ $a->sessionLabel() }})</dd>
                    </div>
                    <div class="{{ $row }}">
                        <dt class="text-slate-500">Purpose</dt>
                        <dd class="min-w-0 break-words text-slate-900">{{ $a->purpose ?: 'General visit' }}</dd>
                    </div>
                    <div class="{{ $row }}">
                        <dt class="text-slate-500">Queue number</dt>
                        <dd class="tabular-nums text-slate-900">{{ $a->queue_number ? '#' . $a->queue_number : 'Given when approved' }}</dd>
                    </div>
                    <div class="{{ $row }}">
                        <dt class="text-slate-500">Requested</dt>
                        <dd class="text-slate-900">{{ $a->created_at?->format('M j, Y, g:i A') }}</dd>
                    </div>
                    @if ($a->reviewed_at)
                        <div class="{{ $row }}">
                            <dt class="text-slate-500">Reviewed</dt>
                            <dd class="text-slate-900">{{ $a->reviewed_at->format('M j, Y, g:i A') }}{{ $a->reviewer ? ' by ' . $a->reviewer->full_name : '' }}</dd>
                        </div>
                    @endif
                    @if ($a->reschedule_count > 0)
                        <div class="{{ $row }}">
                            <dt class="text-slate-500">Rescheduled</dt>
                            <dd class="min-w-0 break-words text-slate-900">
                                {{ $a->reschedule_count }} {{ str('time')->plural($a->reschedule_count) }}
                                <span class="block text-xs text-slate-500">{{ $a->reschedule_reason ?: 'No reason was given.' }}</span>
                            </dd>
                        </div>
                    @endif
                    @if ($a->status === 'rejected' && $a->rejection_reason)
                        <div class="{{ $row }}">
                            <dt class="text-slate-500">Reason</dt>
                            <dd class="min-w-0 break-words text-slate-900">{{ $a->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- The document --}}
            <section class="{{ $card }} order-5" aria-labelledby="rec-doc">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="rec-doc" class="text-[15px] font-semibold text-slate-900">Document</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            @if ($ws)
                                {{ $ws->documentName() }}@if ($ws->transaction_code) · <span class="tabular-nums">{{ $ws->transaction_code }}</span>@endif
                                · {{ $ws->workflow_status?->label() ?? ucfirst($ws->status ?? '') }}
                            @else
                                The student did not attach a document.
                            @endif
                        </p>
                    </div>
                    @if ($ws)
                        <div class="flex shrink-0 flex-wrap justify-end gap-2">
                            @if ($paperless)
                                <a href="{{ route('admin.workflow.show', $ws->workspace_id) }}" wire:navigate class="{{ $secondary }} !px-3">Open transaction</a>
                            @elseif ($ws->generated_pdf_path)
                                <a href="{{ route('admin.transactions.download-pdf', $ws) }}" class="{{ $secondary }} !px-3">
                                    <i class="bx bx-download text-base text-slate-500" aria-hidden="true"></i> Download PDF
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($paperless)
                    <button type="button" @click="$dispatch('open-appt-doc')"
                            class="group relative mt-4 block aspect-[210/297] w-full max-w-[280px] overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1] text-left {{ $focus }}"
                            x-data="{ s: .3 }" x-init="new ResizeObserver(() => s = $el.clientWidth / 842).observe($el)"
                            aria-label="Preview {{ $ws->documentName() }}">
                        <iframe src="{{ $previewSrc }}" loading="lazy" tabindex="-1" title="" aria-hidden="true"
                                class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                                :style="`width:842px;height:1191px;transform:scale(${s})`"></iframe>
                        <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-white/95 py-2 text-sm font-medium text-[#2A57B4]">
                            <i class="bx bx-show text-base" aria-hidden="true"></i> Preview
                        </span>
                    </button>
                @elseif ($ws)
                    <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-3">
                        @include('livewire.partials.document-preview', ['workspace' => $ws])
                    </div>
                @endif

                @if ($ws?->missed_count > 0)
                    <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800">
                        Missed {{ $ws->missed_count }} earlier {{ str('appointment')->plural($ws->missed_count) }} for this document.
                    </p>
                @endif
            </section>

        </div>

        {{-- The office's side: decide, clearance, AI check, notes, checkpoints --}}
        <div class="contents lg:block lg:space-y-5">
            @if (in_array($a->status, ['pending', 'approved'], true))
                <section class="{{ $card }} order-2" aria-labelledby="rec-decide">
                    <h2 id="rec-decide" class="text-[15px] font-semibold text-slate-900">{{ $a->status === 'pending' ? 'Decide' : 'At the counter' }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $a->status === 'pending'
                            ? 'Approving gives the student the next queue number for this session and notifies them.'
                            : 'Mark the visit as attended once the student has been served.' }}
                    </p>
                    <div class="mt-4 grid gap-2 sm:flex sm:flex-wrap">
                        @if ($a->status === 'pending')
                            <button type="button" wire:click="$set('showApproveConfirm', true)" class="{{ $primary }}">Approve</button>
                            <button type="button" wire:click="$set('showReject', true)" class="{{ $danger }}">Reject</button>
                        @else
                            <button type="button" wire:click="$set('showAttendConfirm', true)" class="{{ $primary }}">Mark as attended</button>
                        @endif
                        <button type="button" wire:click="$set('showReschedule', true)" class="{{ $secondary }}">Reschedule</button>
                    </div>
                </section>
            @endif

            {{-- Clearance status --}}
            <section class="{{ $card }} order-3" aria-labelledby="rec-clearance">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="rec-clearance" class="text-[15px] font-semibold text-slate-900">Clearance status</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $clearance['period_label'] }}</p>
                    </div>
                    @if ($this->clearanceSemesterOptions->isNotEmpty())
                        <label class="block">
                            <span class="sr-only">Term</span>
                            <select wire:model.live="clearanceSemesterFilter" class="h-9 py-0 [@media(pointer:coarse)]:h-11 {{ $field }} !w-auto">
                                <option value="">Current term</option>
                                @foreach ($this->clearanceSemesterOptions as $sem)
                                    <option value="{{ $sem }}">{{ $sem }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-3 rounded-xl px-4 py-3 {{ $clrTint }}">
                    <span class="h-3 w-3 shrink-0 rounded-full {{ $clrDot }}" aria-hidden="true"></span>
                    <p class="text-lg font-semibold tracking-[-0.01em] {{ $clrText }}">{{ $clearance['status_word'] }}</p>
                </div>

                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Tagged by</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $clearance['tagged_by'] ?: '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Last updated</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $clearance['last_updated'] ?: '–' }}</dd>
                    </div>
                    @if ($clearance['remarks'])
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">Remarks</dt>
                            <dd class="mt-0.5 break-words text-slate-900">{{ $clearance['remarks'] }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <div class="{{ $card }} order-4">
                @include('livewire.admin.appointments.partials.ai-check', ['appt' => $a, 'run' => 'rerunAiReview'])
            </div>

            <section class="{{ $card }} order-6" aria-labelledby="rec-notes">
                <h2 id="rec-notes" class="text-[15px] font-semibold text-slate-900">Notes</h2>
                <p class="mt-0.5 text-sm text-slate-500">For the office only. The student does not see these.</p>
                <label for="admin-notes" class="sr-only">Notes</label>
                <textarea id="admin-notes" wire:model="adminNotes" rows="4" maxlength="2000"
                          placeholder="e.g. Brought the signed permit; asked to come back for the dean's signature."
                          class="mt-3 px-3 py-2.5 {{ $field }}"></textarea>
                @error('adminNotes') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                <div class="mt-2 flex justify-end">
                    <button type="button" wire:click="saveNotes" wire:loading.attr="disabled" wire:target="saveNotes" class="{{ $secondary }}">Save notes</button>
                </div>
            </section>

            <section class="{{ $card }} order-7" aria-labelledby="rec-checkpoints">
                <h2 id="rec-checkpoints" class="text-[15px] font-semibold text-slate-900">Checkpoints</h2>
                <ol class="mt-3">
                    @foreach (array_reverse($this->timeline) as $i => $item)
                        <li class="relative flex gap-3 pb-4 last:pb-0" wire:key="cp-{{ $i }}">
                            @unless ($loop->last)
                                <span class="absolute left-[5px] top-4 h-[calc(100%-0.75rem)] w-px bg-slate-200" aria-hidden="true"></span>
                            @endunless
                            <span class="relative mt-1 h-[11px] w-[11px] shrink-0 rounded-full ring-2 {{ $loop->first ? 'bg-white ring-[#c9531f]' : ($tone[$item['tone']] ?? 'bg-white ring-slate-300') }}" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-medium text-slate-900">@if ($loop->first)<span class="sr-only">Latest: </span>@endif{{ $item['title'] }}</p>
                                <p class="text-xs tabular-nums text-slate-500">{{ $item['date'] }}</p>
                                @if (! empty($item['note']))
                                    <p class="mt-0.5 break-words text-xs text-slate-600">{{ $item['note'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>

    @if ($paperless)
        @include('livewire.admin.workflow._preview-modal', [
            'event' => 'open-appt-doc',
            'src' => $previewSrc,
            'version' => $ws->updated_at?->timestamp,
            'title' => $ws->documentName() . ($ws->transaction_code ? ' · ' . $ws->transaction_code : ''),
            'note' => 'The document this appointment is about.',
        ])
    @endif

    {{-- Dialogs --}}
    @php
        $dialogOpen = $showApproveConfirm ? 'approve' : ($showReject ? 'reject' : ($showReschedule ? 'reschedule' : ($showAttendConfirm ? 'attend' : null)));
    @endphp
    @if ($dialogOpen)
        @php
            $close = match ($dialogOpen) { 'approve' => 'showApproveConfirm', 'reject' => 'showReject', 'reschedule' => 'showReschedule', default => 'showAttendConfirm' };
        @endphp
        {{-- Moved to <body>, so the dimmed backdrop covers the whole window, top bar included. --}}
        <template x-teleport="body">
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="$set('{{ $close }}', false)" wire:click.self="$set('{{ $close }}', false)"
             role="dialog" aria-modal="true" aria-labelledby="dlg-title" aria-describedby="dlg-lead">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                 x-data x-init="$nextTick(() => $el.querySelector('select, input, textarea, button')?.focus())">
                <h2 id="dlg-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">
                    {{ ['approve' => 'Approve appointment', 'reject' => 'Reject appointment', 'reschedule' => 'Reschedule appointment', 'attend' => 'Mark as attended'][$dialogOpen] }}
                </h2>
                <p id="dlg-lead" class="mt-1 text-sm text-slate-500">
                    @switch($dialogOpen)
                        @case('approve') {{ $u?->full_name }} gets the next queue number for {{ $a->appointment_date->format('M j') }}, {{ $a->session }}, and is notified. @break
                        @case('reject') The student sees this reason. @break
                        @case('reschedule') An approved visit goes back to pending and loses its queue number. The student is notified. @break
                        @default Queue #{{ $a->queue_number }} ({{ $u?->first_name }}) is recorded as attended. This cannot be undone. Any notes above are saved with it.
                    @endswitch
                </p>

                @if ($dialogOpen === 'reschedule')
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="block text-sm font-medium text-slate-800">New day <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <input type="date" wire:model="rescheduleDate" min="{{ now()->toDateString() }}" required class="mt-1 h-9 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                        </label>
                        <label class="block">
                            <span class="block text-sm font-medium text-slate-800">Session <span class="text-rose-600" aria-hidden="true">*</span></span>
                            <select wire:model="rescheduleSession" required class="mt-1 h-9 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                                <option value="">Choose…</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </label>
                    </div>
                    @error('rescheduleDate') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                    @error('rescheduleSession') <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @endif

                @if (in_array($dialogOpen, ['reject', 'reschedule'], true))
                    @php
                        $opts = $dialogOpen === 'reject'
                            ? ['Incomplete or unreadable supporting documents submitted.', 'Duplicate or conflicting appointment request detected.', 'Ineligible for requested appointment type or service.', 'The purpose does not match the document attached.']
                            : ['Official event conflict or university closure.', 'Assigned staff unavailable on original date.', 'System adjustment / Queue capacity reallocation.'];
                        $model = $dialogOpen === 'reject' ? 'rejectionReason' : 'rescheduleReason';
                        $method = $dialogOpen === 'reject' ? 'useRejectionOption' : 'useRescheduleOption';
                    @endphp
                    <label for="dlg-reason-pick" class="mt-4 block text-sm font-medium text-slate-800">Reason <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select id="dlg-reason-pick" wire:change="{{ $method }}($event.target.value)" class="mt-1 h-9 py-0 [@media(pointer:coarse)]:h-11 {{ $field }}">
                        <option value="">Choose a reason…</option>
                        @foreach ($opts as $o)
                            <option value="{{ $o }}">{{ $o }}</option>
                        @endforeach
                        <option value="Custom">Write my own</option>
                    </select>
                    @if ($dialogOpen === 'reject' && $templates->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($templates as $tpl)
                                <button type="button" wire:click="useTemplate({{ $tpl->id }})" class="rounded-full border border-slate-200 px-2.5 py-1 text-xs text-slate-700 hover:border-[#2A57B4] hover:text-[#2A57B4] {{ $focus }}">{{ $tpl->label }}</button>
                            @endforeach
                        </div>
                    @endif
                    <label for="dlg-reason" class="sr-only">Reason text</label>
                    <textarea id="dlg-reason" wire:model="{{ $model }}" rows="3" maxlength="500" class="mt-2 px-3 py-2.5 {{ $field }}"></textarea>
                    @error($model) <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @endif

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('{{ $close }}', false)" class="{{ $secondary }}">Cancel</button>
                    @php $action = ['approve' => 'approve', 'reject' => 'reject', 'reschedule' => 'reschedule', 'attend' => 'markAttended'][$dialogOpen]; @endphp
                    <button type="button" wire:click="{{ $action }}"
                            wire:loading.attr="disabled" wire:target="{{ $action }}"
                            class="{{ $dialogOpen === 'reject' ? $btn . ' bg-rose-600 text-white shadow-sm hover:bg-rose-700' : $primary }}">
                        {{ ['approve' => 'Approve', 'reject' => 'Reject', 'reschedule' => 'Reschedule', 'attend' => 'Mark as attended'][$dialogOpen] }}
                    </button>
                </div>
            </div>
        </div>
        </template>
    @endif
</div>
