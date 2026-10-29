@php
    use App\Enums\ApprovalStatus;
    use App\Enums\TransactionStatus;

    $editable = $this->isEditable;
    $document = $this->document;
    $input = 'mt-1.5 w-full min-w-0 rounded-xl border border-slate-300 px-3.5 py-3 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100 sm:py-2.5 sm:text-sm';

    $signedCount = $approvals->where('status', ApprovalStatus::Approved)->count();
    $waitingOn = $approvals->firstWhere('status', ApprovalStatus::Pending);

    // One "what happens next" message. Amber = the student has to act.
    [$nextTitle, $nextText, $nextTone] = match (true) {
        ! $ws || $status === TransactionStatus::Draft => [null, null, null],
        $status === TransactionStatus::ActionRequired => ['Your next step: update and resubmit', $ws->workflow_note ? '"' . $ws->workflow_note . '" (VPSD office)' : 'The VPSD office asked for changes. Update the form below and resubmit.', 'amber'],
        $status === TransactionStatus::Rejected => ['Rejected', $ws->workflow_note ?: 'This transaction was rejected.', 'rose'],
        $status === TransactionStatus::Voided => ['Voided by the VPSD office', $ws->void_reason ?: 'This document was withdrawn.', 'slate'],
        $status === TransactionStatus::Completed => ['Completed and signed', 'Save your copy as a PDF. Print it only if a paper copy is required.', 'emerald'],
        $status === TransactionStatus::Approved => ['Almost done', $afterReviewNote ?: 'Everyone has signed. The VPSD office will finish it.', 'blue'],
        $status === TransactionStatus::UnderReview && $approvals->isNotEmpty() => ['In signing', 'Signed ' . $signedCount . ' of ' . $approvals->count() . '.' . ($waitingOn ? ' Waiting for ' . ($waitingOn->role->label ?? $waitingOn->label) . '. They are notified automatically.' : '') . ($afterReviewNote ? "\n" . $afterReviewNote : ''), 'blue'],
        default => ['With the VPSD office', $afterReviewNote ?: 'The VPSD office is checking your submission. Nothing to do yet.', 'blue'],
    };
    $toneBox = [
        'amber' => 'bg-amber-50 text-amber-900',
        'rose' => 'bg-rose-50 text-rose-900',
        'slate' => 'bg-slate-100 text-slate-800',
        'emerald' => 'bg-emerald-50 text-emerald-900',
        'blue' => 'bg-[#2A57B4]/5 text-slate-800',
    ];
    $closed = in_array($status, [TransactionStatus::Rejected, TransactionStatus::Voided], true);
@endphp

<div class="mx-auto w-full max-w-2xl space-y-5">

    {{-- Header --}}
    <div>
        <a href="{{ $back['url'] }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-[#2A57B4]">&larr; {{ $back['label'] }}</a>
        <h2 class="mt-2 break-words text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $document->name() }}</h2>
        @if ($ws?->transaction_code)
            <p class="mt-0.5 text-xs text-slate-500">
                {{ $ws->transaction_code }}
                @if ($ws->submitted_at) &middot; Submitted {{ $ws->submitted_at->format('M j, Y') }} @endif
            </p>
        @endif
    </div>

    @if (session('workflow_message'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('workflow_message') }}</div>
    @endif
    @error('form')
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
    @enderror

    {{-- Status: one stepper and one next-step message --}}
        <section x-data="{ guide: {{ $ws ? 'false' : 'true' }}, signers: false }" class="space-y-4 rounded-2xl border border-slate-200/70 bg-white p-4 sm:p-5">
            @unless ($closed)
                @php
                    // 0 = filling in, 1 = VPSD review, 2 = signatures, 3 = office finishing, 4 = done
                    $step = $progress ?? 0;
                @endphp
                <ol class="grid grid-cols-4 gap-2">
                    @foreach (['Submitted', 'VPSD review', 'Signatures', 'Done'] as $i => $label)
                        @php
                            $done = $step >= 4 || $i < $step;
                            $current = ! $done && $i === $step;
                            $bar = $done ? 'bg-emerald-500' : ($current ? ($nextTone === 'amber' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200');
                        @endphp
                        <li class="min-w-0">
                            <div class="h-1.5 rounded-full {{ $bar }}"></div>
                            <p class="mt-1.5 text-[11px] leading-tight sm:text-xs {{ $current ? 'font-semibold text-slate-900' : ($done ? 'text-slate-700' : 'text-slate-500') }}">{{ $label }}</p>
                        </li>
                    @endforeach
                </ol>
            @endunless

            @if ($nextTitle)
                <div class="rounded-xl px-4 py-3.5 {{ $toneBox[$nextTone] }}">
                    <p class="text-sm font-semibold">{{ $nextTitle }}</p>
                    <p class="mt-0.5 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ $nextText }}</p>

                    @if ($status === TransactionStatus::Completed)
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                                <span wire:loading wire:target="downloadPdf">Preparing…</span>
                            </button>
                            @if ($ws->verification_token)
                                <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" class="text-sm font-semibold text-emerald-800 hover:underline">Verification page</a>
                            @endif
                        </div>
                    @elseif ($status === TransactionStatus::Voided && $ws->superseded_by)
                        <a href="{{ route('student.transactions.show', $ws->superseded_by) }}" wire:navigate
                           class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">Open the corrected draft &rarr;</a>
                    @endif
                </div>
            @endif

            @if ($document->studentNote())
                <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $document->studentNote() }}</p>
            @endif

            <div class="flex flex-wrap gap-x-5 gap-y-1">
                @unless ($document->issuedByOffice())
                    <button type="button" @click="guide = !guide" class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]"
                            x-text="guide ? 'Hide how this works' : 'How this works'"></button>
                @endunless
                @if ($approvals->isNotEmpty())
                    <button type="button" @click="signers = !signers" class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]"
                            x-text="signers ? 'Hide signatures' : 'Signatures ({{ $signedCount }} of {{ $approvals->count() }})'"></button>
                @endif
            </div>

            @unless ($document->issuedByOffice())
                <ol x-show="guide" x-cloak class="space-y-3 border-t border-slate-100 pt-4">
                    @foreach ($document->guide($ws?->field_values ?? $values) as $i => [$title, $text])
                        <li class="flex gap-3" wire:key="guide-{{ $i }}">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endunless

            @if ($approvals->isNotEmpty())
                <ol x-show="signers" x-cloak class="space-y-3 border-t border-slate-100 pt-4">
                    @foreach ($approvals as $a)
                        @php
                            $dot = match ($a->status) {
                                ApprovalStatus::Approved => 'bg-emerald-500',
                                ApprovalStatus::Pending => 'bg-[#2A57B4]',
                                ApprovalStatus::Rejected => 'bg-rose-500',
                                default => 'bg-slate-300',
                            };
                            $text = match ($a->status) {
                                ApprovalStatus::Approved => 'Signed' . ($a->acted_at ? ' · ' . $a->acted_at->format('M j, g:i A') : ''),
                                ApprovalStatus::Pending => 'Waiting for signature',
                                ApprovalStatus::Rejected => 'Rejected',
                                ApprovalStatus::Skipped => 'Cancelled',
                                default => 'Not started',
                            };
                        @endphp
                        <li class="flex items-start gap-3" wire:key="appr-{{ $a->id }}">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $a->role->label ?? $a->label }}</p>
                                <p class="text-xs text-slate-500">{{ $text }}</p>
                                @if ($a->status === ApprovalStatus::Rejected && $a->reason)
                                    <p class="mt-1 text-xs text-rose-600">{{ $a->reason }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

    {{-- The form --}}
    <section class="rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm sm:p-5">
        @php
            $sysFields = $document->systemFields();
            $missing = collect($sysFields)->filter(fn ($label, $key) => trim((string) ($student[$key] ?? '')) === '')->values();
            $sigImg = $editable
                ? ($profileSignature ? route('profile.signature') : null)
                : $savedSignature;
            $needsSig = $document->requiresStudentSignature();
            $sysLine = collect($sysFields)->keys()->map(fn ($key) => trim((string) ($student[$key] ?? '')))->filter()->implode(' · ');
        @endphp

        {{-- Details from the student's account, shown on one line --}}
        <div x-data="{ open: false }" class="border-b border-slate-100 pb-4">
            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                <p class="min-w-0 text-sm text-slate-600">{{ $sysLine ?: 'Your details' }}</p>
                <div class="flex shrink-0 items-center gap-4 text-sm">
                    <button type="button" @click="open = !open" class="font-medium text-slate-500 hover:text-slate-800" x-text="open ? 'Hide details' : 'Details'"></button>
                    @if ($editable)
                        <a href="{{ route('profile') }}" wire:navigate class="font-medium text-[#2A57B4] hover:underline">Edit in profile</a>
                    @endif
                </div>
            </div>

            <dl x-show="open" x-cloak class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                @foreach ($sysFields as $key => $label)
                    <div wire:key="sys-{{ $key }}">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ trim((string) ($student[$key] ?? '')) ?: '—' }}</dd>
                    </div>
                @endforeach
                @if ($needsSig)
                    <div class="sm:col-span-2" wire:key="sys-signature">
                        <dt class="text-xs text-slate-500">Signature (from your profile)</dt>
                        <dd>
                            @if ($sigImg)
                                <img src="{{ $sigImg }}" alt="Your signature" class="mt-0.5 h-12 rounded-lg border border-slate-200 bg-white p-1">
                            @else
                                <span class="text-sm font-semibold text-amber-700">Not set</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>

            @if ($editable && ($missing->isNotEmpty() || ($needsSig && ! $sigImg)))
                <p class="mt-2.5 text-sm text-amber-800">
                    Some details are missing from your account{{ $needsSig && ! $sigImg ? ', including your signature' : '' }}.
                    <a href="{{ route('profile') }}" wire:navigate class="font-semibold underline">Update your profile</a> before submitting.
                </p>
            @endif

            @if (in_array($document->key(), ['permit_to_conduct_activity', 'permit_to_conduct_off_campus'], true))
                @if (! empty($student['officer_position']))
                    <p class="mt-2 text-sm text-slate-600">Officer: {{ $student['officer_position'] }}, {{ $student['officer_organization'] }}</p>
                @else
                    <p class="mt-2 text-sm text-amber-800">You are not an officer. Organization-hosted permits can only be requested by officers.</p>
                @endif
            @endif
        </div>

        @if ($document->usesNomineePicker())
            <div class="relative mt-5">
                <label class="text-sm font-medium text-slate-700">
                    Nominee @if ($editable)<span class="text-rose-500">*</span>@endif
                </label>

                @if ($nomineeCard)
                    <div class="mt-1.5 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $nomineeCard['name'] }}</p>
                                <dl class="mt-1.5 grid grid-cols-1 gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                                    <div><dt class="text-slate-400">College</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['college'] ?: '—' }}</dd></div>
                                    <div><dt class="text-slate-400">Year and section</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['year_section'] ?: '—' }}</dd></div>
                                    <div class="sm:col-span-2"><dt class="text-slate-400">Program</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['program'] ?: '—' }}</dd></div>
                                </dl>
                            </div>
                            @if ($editable)
                                <button type="button" wire:click="clearNominee" class="shrink-0 text-slate-400 hover:text-slate-700" aria-label="Change nominee">&#10005;</button>
                            @endif
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400">Filled in from the nominee's profile. Their address and contact number are added to the form automatically.</p>
                    </div>
                @elseif ($editable)
                    <input type="text" wire:model.live.debounce.300ms="nomineeSearch" placeholder="Search by name or student number" class="{{ $input }}">

                    @if (strlen($nomineeSearch) >= 2)
                        <div class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                            @forelse ($this->nomineeResults as $s)
                                <button type="button" wire:click="selectNominee({{ $s->id }})" wire:key="nom-{{ $s->id }}"
                                        class="flex w-full items-center justify-between gap-2 px-3.5 py-2 text-left text-sm hover:bg-slate-50">
                                    <span class="min-w-0 truncate">
                                        {{ $s->full_name }}
                                        <span class="block truncate text-[11px] text-slate-400">{{ $s->program?->name }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs text-slate-400">{{ $s->student_number }}</span>
                                </button>
                            @empty
                                <p class="px-3.5 py-2 text-sm text-slate-400">No active student found.</p>
                            @endforelse
                        </div>
                    @endif
                @endif

                @error('nomineeId') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="mt-5 space-y-4">
            @foreach ($document->fields() as $name => $f)
                @php
                    $dep = $f['show_when'] ?? null;
                    $depKey = $dep ? array_key_first($dep) : null;
                    $depVal = $dep ? $dep[$depKey] : null;

                    $hide = $f['hide_when'] ?? null;
                    $hiddenNow = $hide && in_array((string) ($values[$hide['field']] ?? ''), $hide['values'], true);

                    $xShow = null;
                    if ($dep) {
                        $xShow = '$wire.values.' . $depKey . ' === ' . json_encode($depVal);
                    }
                    if ($hide) {
                        $xShow = ($xShow ? $xShow . ' && ' : '') . '!' . json_encode($hide['values']) . '.includes($wire.values.' . $hide['field'] . ')';
                    }
                @endphp
                @continue(! empty($f['staff_only']))
                @continue(! $editable && (($dep && ($values[$depKey] ?? null) !== $depVal) || $hiddenNow))
                @continue(($f['group'] ?? null) === 'schedule')
                @if (($f['type'] ?? '') === 'slots')
                    @include('livewire.workflow._slots-field', ['name' => $name, 'f' => $f])
                    @continue
                @endif
                <div wire:key="f-{{ $name }}"
                    @if ($editable && $xShow) x-show="{{ $xShow }}" x-cloak @endif>
                    @if ($f['type'] === 'checkbox')
                        @if ($editable)
                            <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700">
                                <input type="checkbox" wire:model="values.{{ $name }}" class="h-4 w-4 accent-[#2A57B4]">
                                {{ $f['label'] }}
                            </label>
                        @else
                            <p class="text-sm text-slate-800">
                                <span class="font-medium text-slate-700">{{ $f['label'] }}:</span>
                                {{ $document->formatValue($f, $values[$name] ?? null) }}
                            </p>
                        @endif
                    @else
                        <label class="text-sm font-medium text-slate-700">
                            {{ $f['label'] }} @if ($editable && ($f['required'] ?? false))<span class="text-rose-500">*</span>@endif
                        </label>

                        @if ($editable)
                            @if ($f['type'] === 'textarea')
                                <textarea wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}" class="{{ $input }}"></textarea>
                            @elseif ($f['type'] === 'select')
                                <select wire:model="values.{{ $name }}" class="{{ $input }}">
                                    <option value="">Choose…</option>
                                    @foreach ($f['options'] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input wire:model{{ str_ends_with($name, '_email') ? '.blur' : '' }}="values.{{ $name }}"
                                    type="{{ ['date' => 'date', 'time' => 'time'][$f['type']] ?? 'text' }}"
                                    @if ($f['type'] === 'date')
                                        @if (! empty($f['future'])) min="{{ now()->toDateString() }}"
                                        @else max="{{ now()->toDateString() }}" @endif
                                    @endif
                                    @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                    placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $input }}">
                            @endif
                            @if (! empty($f['help'])) <p class="mt-1 text-xs text-slate-400">{{ $f['help'] }}</p> @endif
                            @if ($editable && str_ends_with($name, '_email') && ! empty($values[$name]) && \App\Services\Workflow\ExternalSigning::accountFor($values[$name], auth()->id()))
                                <p class="mt-1 text-xs font-medium text-emerald-600">This person has a DocuMate signatory account. They will sign from their account, no email link needed.</p>
                            @endif
                        @else
                            <p class="mt-1 whitespace-pre-wrap rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-800">{{ $document->formatValue($f, $values[$name] ?? null) ?: '—' }}</p>
                        @endif
                    @endif
                    @error("values.$name") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
            @if ($document->key() === 'student_assistantship_application')
                @include('livewire.workflow._schedule-grid')
            @endif
        </div>
        @if ($document->requiresItemPhoto())
            <div class="mt-5">
                <label class="text-sm font-medium text-slate-700">
                    Photo of the item @if ($editable)<span class="text-rose-500">*</span>@endif
                </label>

                @if ($itemPhoto)
                    <img src="{{ $itemPhoto->temporaryUrl() }}" alt="Item photo preview"
                        class="mt-2 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
                @elseif ($hasPhoto)
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}?v={{ $ws->updated_at?->timestamp }}" alt="Item photo"
                        class="mt-2 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
                @endif

                @if ($editable)
                    <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-1 md:max-w-xs">
                        {{-- Camera: phones/tablets only --}}
                        <label class="cursor-pointer rounded-xl bg-[#2A57B4] px-3.5 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#24499A] md:hidden">
                            Take a photo
                            <input type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" capture="environment" class="hidden">
                        </label>
                        <label class="cursor-pointer rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 md:bg-[#2A57B4] md:text-white md:hover:bg-[#24499A]">
                            <span class="md:hidden">Choose from gallery</span>
                            <span class="hidden md:inline">Upload a photo</span>
                            <input type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" class="hidden">
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">JPG or PNG, up to 10 MB. Not printed on the form.</p>
                    <div wire:loading wire:target="itemPhoto" class="mt-1 text-xs text-[#2A57B4]">Uploading photo…</div>
                @endif

                @error('itemPhoto') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
        @endif

        @if ($editable)
            <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                        class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50 sm:w-auto sm:border-0 sm:py-2.5">Save draft</button>
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        wire:confirm="Submit to the VPSD office? You can't edit it afterwards unless they ask for changes."
                        class="w-full rounded-xl bg-[#2A57B4] px-6 py-3 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50 sm:w-auto sm:py-2.5">
                    {{ $status === TransactionStatus::ActionRequired ? 'Resubmit' : 'Submit' }}
                </button>
            </div>
        @endif
    </section>

    {{-- Activity, folded away --}}
    @if (count($timeline))
        <div x-data="{ open: false }" class="text-center">
            <button type="button" @click="open = !open" class="text-sm font-medium text-slate-500 hover:text-slate-800"
                    x-text="open ? 'Hide activity' : 'Show activity ({{ count($timeline) }})'"></button>
            <section x-show="open" x-cloak class="mt-3 space-y-3 rounded-2xl border border-slate-200/70 bg-white p-5 text-left">
                @foreach ($timeline as $item)
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                            @if ($item['note']) <p class="text-xs text-slate-500">{{ $item['note'] }}</p> @endif
                        </div>
                        <p class="shrink-0 text-xs text-slate-500">{{ $item['at']->format('M j, Y g:i A') }}</p>
                    </div>
                @endforeach
            </section>
        </div>
    @endif
</div>
