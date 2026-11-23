@php
    use App\Enums\ApprovalStatus;
    use App\Enums\TransactionStatus;

    $editable = $this->isEditable;
    $document = $this->document;

    // Filled in on the form itself (<x-doc.sheet>): each answer is a blank line with its caption under it, as on paper.
    $D = \App\Support\DocSheet::class;
    $box = 'mt-1 ' . $D::LINE;
    $boxLocked = 'mt-1 relative flex min-w-0 items-center gap-2 border-b border-dashed border-slate-300';
    $bare = $D::INPUT;
    $label = 'block text-[13px] font-medium text-slate-700';
    $caption = $D::CAPTION;
    $help = $D::HINT;
    $err = $D::ERROR;

    $signedCount = $approvals->where('status', ApprovalStatus::Approved)->count();
    $waitingOn = $approvals->firstWhere('status', ApprovalStatus::Pending);

    // One "what happens next" message under the status pill (the system-wide words and colours).
    // Amber = the student has to act.
    $nextText = match (true) {
        ! $ws || $status === TransactionStatus::Draft => null,
        $status === TransactionStatus::ActionRequired => 'Your next step: update the form below and resubmit.' . ($ws->workflow_note ? "\n" . '"' . $ws->workflow_note . '" (VPSD office)' : ' The VPSD office asked for changes.'),
        $status === TransactionStatus::Rejected => $ws->workflow_note ?: 'This transaction was rejected.',
        $status === TransactionStatus::Voided => $ws->void_reason ?: 'Voided by the VPSD office.',
        $status === TransactionStatus::Completed => 'Signed and finished. Save your copy as a PDF. Print it only if a paper copy is required.',
        $status === TransactionStatus::Approved => $afterReviewNote ?: 'Everyone has signed. The VPSD office will finish it.',
        $status === TransactionStatus::UnderReview && $approvals->isNotEmpty() => 'Signed ' . $signedCount . ' of ' . $approvals->count() . '.' . ($waitingOn ? ' Waiting for ' . ($waitingOn->role->label ?? $waitingOn->label) . '. They are notified automatically.' : '') . ($afterReviewNote ? "\n" . $afterReviewNote : ''),
        default => $afterReviewNote ?: 'The VPSD office is checking your submission. Nothing to do yet.',
    };
    $nextTone = $nextText !== null ? $status->tone() : null;
    $toneBox = [
        'amber' => 'bg-amber-50 text-amber-900',
        'rose' => 'bg-rose-50 text-rose-900',
        'slate' => 'bg-slate-100 text-slate-800',
        'emerald' => 'bg-emerald-50 text-emerald-900',
        'green' => 'bg-green-50 text-green-900',
        'orange' => 'bg-orange-50 text-orange-900',
        'violet' => 'bg-violet-50/60 text-slate-800',
        'blue' => 'bg-blue-50/60 text-slate-800',
        'indigo' => 'bg-indigo-50/60 text-slate-800',
    ];
    $closed = in_array($status, [TransactionStatus::Rejected, TransactionStatus::Voided], true);

    // "How this works" as a checklist: which guide steps are done, and which one is current.
    // Steps: 0 fill in, 1 submit, 2 VPSD review, 3 signatures, 4 final verification, 5 download.
    $guide = $document->issuedByOffice() ? [] : $document->guide($ws?->field_values ?? $values);
    $guideDone = match (true) {
        $status === TransactionStatus::Completed => 5,
        $status === TransactionStatus::Approved => 4,
        $status === TransactionStatus::UnderReview && $approvals->isNotEmpty() => 3,
        in_array($status, [TransactionStatus::Submitted, TransactionStatus::UnderReview], true) => 2,
        default => 0,
    };
    $guideAmber = $status === TransactionStatus::ActionRequired;

    // Filled in on the printed page (resources/js/pdf-fill.js): the blanks the PDF prints, and which editor an error belongs to.
    $printed = \App\Support\PdfFill::printed($document);
    $editorFor = function (string $key) use ($document): ?string {
        if ($key === 'nomineeId') {
            return '__nominee';
        }
        if (! str_starts_with($key, 'values.')) {
            return null;
        }
        $n = explode('.', substr($key, 7))[0];
        $f = $document->fields()[$n] ?? [];

        return match (true) {
            ($f['group'] ?? null) === 'schedule' => '__schedule',
            ! empty($f['salutation_of']) => $f['salutation_of'],
            default => $n,
        };
    };
    $errorEditors = collect($errors->keys())->map($editorFor)->filter()->unique()->values()->all();

    // Plain lines, as on paper: no icons in the blanks.
    $fieldIcon = fn (string $name, array $f): ?string => null;

    // Short answers share a row on wider screens; longer ones take the whole width.
    $isShort = fn (string $name, array $f): bool => empty($f['question']) && empty($f['salutation']) && (
        in_array($f['type'] ?? 'text', ['date', 'time', 'select', 'number'], true)
        || (($f['type'] ?? 'text') === 'text' && ($f['max'] ?? 255) <= 150)
    );

    $sysFields = $document->systemFields();
    $missing = collect($sysFields)->filter(fn ($l, $key) => trim((string) ($student[$key] ?? '')) === '')->values();
    $sigImg = $editable ? ($profileSignature ? route('profile.signature') : null) : $savedSignature;
    $needsSig = $document->requiresStudentSignature();
    $officerGate = $editable && $document->requiresOfficer();
    $hasPicker = collect($document->fields())->contains(fn ($f) => ($f['type'] ?? '') === 'students');

    // What the student needs before starting, for the "Before you start" box.
    $chainSteps = rescue(fn () => $document->chain($values), [], false);
    $needs = array_values(array_filter([
        $document->requiresOfficer() ? 'An officer record in your account (your organization adviser or the VPSD office adds it).' : null,
        $needsSig ? 'Your e-signature saved in your profile. It is applied to the form for you.' : null,
        $document->requiresItemPhoto() ? 'A clear photo of the item (JPG or PNG, up to 10 MB).' : null,
        collect($chainSteps)->contains(fn ($s) => ($s['type'] ?? '') === 'external') ? 'The title, full name and email address of each person who signs for you.' : null,
        $hasPicker ? 'The name, email or student number of each student you will list. Each must have a DocuMate account.' : null,
        $document->usesNomineePicker() ? 'The name or student number of the student you are nominating.' : null,
    ]));
@endphp

<div class="mx-auto w-full max-w-3xl">
<div class="min-w-0 space-y-4 sm:space-y-5">

    {{-- Header. "PDF" opens the exact printed copy, from the live answers while editable. Nothing is saved by it. --}}
    <div x-data="{
            open: false, loading: false, html: '', w: 842, h: 1171, scale: 1, wrapEl: null, frameEl: null,
            async show() {
                this.open = true;
                this.loading = true;
                this.html = await this.$wire.previewDocument();
                this.loading = false;
                this.$nextTick(() => this.fit());
            },
            fit() {
                const cw = this.wrapEl ? this.wrapEl.clientWidth : 0;
                if (cw > 0) this.scale = Math.min(1, cw / this.w);
            },
            measure(e) {
                if (this.frameEl && e.source === this.frameEl.contentWindow && e.data && e.data.documatePreviewHeight) {
                    this.h = Math.ceil(e.data.documatePreviewHeight);
                }
            }
         }"
         @message.window="measure($event)"
         @resize.window="fit()">

        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1" data-tour="form-head">
                <h1 class="truncate text-lg font-semibold tracking-[-0.015em] text-slate-900 sm:text-xl">{{ $document->name() }}</h1>
                @if ($ws?->submitted_at)
                    <p class="text-xs text-slate-500">Submitted {{ $ws->submitted_at->format('M j, Y') }}</p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($editable)
                    <button type="button" @click="$dispatch('open-intro')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
                            aria-label="Before you start: what you will need" title="Before you start">
                        <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                    </button>
                @endif
                <button type="button" @click="$dispatch('start-tour')" x-ref="guideBtn" data-tour="form-help"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:min-h-11 [@media(pointer:coarse)]:w-11"
                        aria-label="How this works: take a tour of this form" title="How this works">
                    <i class="bx bx-help-circle text-xl" aria-hidden="true"></i>
                </button>
                <button type="button" @click="show()" data-tour="form-preview" title="See the printed copy"
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:min-h-11">
                    <i class="bx bxs-file-pdf text-base text-rose-600" aria-hidden="true"></i> PDF<span class="sr-only"> preview of the printed copy</span>
                </button>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[80] flex flex-col bg-slate-900/50 p-0 sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="doc-preview-title"
                 @keydown.escape.window="open = false">
                <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl"
                     @click.outside="open = false">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="doc-preview-title" class="text-sm font-semibold text-slate-900">Printed copy</h2>
                            <p class="text-xs text-slate-500">{{ $editable ? 'With your answers so far. Nothing is saved.' : 'As it stands now.' }}</p>
                        </div>
                        <button type="button" @click="open = false" x-init="$watch('open', v => v && $nextTick(() => $el.focus()))"
                                class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                                aria-label="Close preview">
                            <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto bg-[#e7e5e1]" x-init="wrapEl = $el">
                        <div x-show="loading" class="flex h-64 items-center justify-center gap-2 text-sm text-slate-600" role="status">
                            <i class="bx bx-loader-alt bx-spin text-xl" aria-hidden="true"></i> Preparing preview…
                        </div>
                        <div x-show="!loading" class="mx-auto overflow-hidden" :style="`width:${w * scale}px;height:${h * scale}px`">
                            <iframe x-init="frameEl = $el" :srcdoc="html" title="Document preview"
                                    class="block border-0 bg-[#e7e5e1]"
                                    :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- "How this works": an overlay with the steps as a checklist that follows the transaction. --}}
    @if ($guide)
        <div x-data="{ open: false, opener: null }"
             @open-guide.window="opener = document.activeElement; open = true; $nextTick(() => $refs.close.focus())">
            <template x-teleport="body">
                <div x-show="open" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[85] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-6"
                     role="dialog" aria-modal="true" aria-labelledby="guide-title" aria-describedby="guide-desc"
                     @keydown.escape.window="if (open) { open = false; opener && opener.focus(); }">
                    <div class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
                         @click.outside="open = false; opener && opener.focus()"
                         @keydown.tab="
                            const f = [...$el.querySelectorAll('button, a[href]')].filter(e => e.offsetParent !== null);
                            if (! f.length) return;
                            if ($event.shiftKey && document.activeElement === f[0]) { $event.preventDefault(); f[f.length - 1].focus(); }
                            else if (! $event.shiftKey && document.activeElement === f[f.length - 1]) { $event.preventDefault(); f[0].focus(); }">
                        <div class="sticky top-0 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-5 py-4">
                            <div class="min-w-0">
                                <h2 id="guide-title" class="text-base font-semibold text-slate-900">How this works</h2>
                                <p id="guide-desc" class="mt-0.5 text-xs text-slate-500">
                                    @if ($closed)
                                        This transaction is closed. The steps are shown for reference.
                                    @else
                                        {{ $guideDone }} of {{ count($guide) }} steps done. Ticks update as your transaction moves along.
                                    @endif
                                </p>
                            </div>
                            <button type="button" x-ref="close" @click="open = false; opener && opener.focus()"
                                    class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                                    aria-label="Close how this works">
                                <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                            </button>
                        </div>

                        @unless ($closed)
                            <div class="px-5 pt-4" aria-hidden="true">
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ round($guideDone / max(1, count($guide)) * 100) }}%"></div>
                                </div>
                            </div>
                        @endunless

                        <ol class="space-y-1 px-3 py-4 sm:px-4">
                            @foreach ($guide as $i => [$title, $text])
                                @php
                                    $isDone = ! $closed && $i < $guideDone;
                                    $isNow = ! $closed && $i === $guideDone;
                                @endphp
                                <li class="flex gap-3 rounded-xl px-2 py-2.5 {{ $isNow ? ($guideAmber ? 'bg-amber-50' : 'bg-[#2A57B4]/5') : '' }}" wire:key="guide-{{ $i }}">
                                    @if ($isDone)
                                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-xs font-semibold text-white">{{ $i + 1 }}</span>
                                    @elseif ($isNow)
                                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 {{ $guideAmber ? 'border-amber-500 text-amber-700' : 'border-[#2A57B4] text-[#2A57B4]' }} text-xs font-semibold">{{ $i + 1 }}</span>
                                    @else
                                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-slate-200 text-xs font-semibold text-slate-500">{{ $i + 1 }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold {{ $isDone ? 'text-slate-500 line-through decoration-slate-300' : 'text-slate-900' }}">
                                            {{ $title }}
                                            <span class="sr-only">{{ $isDone ? '(done)' : ($isNow ? '(current step)' : '(to do)') }}</span>
                                            @if ($isNow)
                                                <span class="ml-1 rounded-full px-1.5 py-0.5 text-[11px] font-medium no-underline ring-1 {{ $guideAmber ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-blue-50 text-[#24499A] ring-blue-200' }}" aria-hidden="true">{{ $guideAmber ? 'Action needed' : 'Now' }}</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </template>
        </div>
    @endif

    {{-- Messages --}}
    @if (session('workflow_message'))
        <div role="status" class="flex items-start gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('workflow_message') }}
        </div>
    @endif

    @if (! $isOwner)
        <div class="flex items-start gap-3 rounded-xl border border-[#2A57B4]/15 bg-[#2A57B4]/5 px-4 py-3 text-sm text-slate-700">
            <i class="bx bx-share-alt mt-0.5 text-lg text-[#2A57B4]" aria-hidden="true"></i>
            <p><span class="font-semibold text-slate-900">{{ $owner }}</span> listed you on this document. This is your read-only view. The official PDF becomes available here once the VPSD office issues the completed document.</p>
        </div>
    @endif

    {{-- Error summary: every problem in one place, each linking to its field. --}}
    @if ($errors->any())
        <div role="alert" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())"
             class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400">
            <p class="flex items-center gap-2 font-semibold">
                <i class="bx bx-error-circle text-lg" aria-hidden="true"></i>
                {{ $errors->count() === 1 ? 'Please fix 1 problem before continuing' : 'Please fix ' . $errors->count() . ' problems before continuing' }}
            </p>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-9">
                @foreach ($errors->getMessages() as $key => $messages)
                    @php
                        $target = match (true) {
                            str_starts_with($key, 'values.') => 'f-' . explode('.', substr($key, 7))[0],
                            $key === 'consent' => 'f-consent',
                            $key === 'itemPhoto' => 'f-photo',
                            $key === 'nomineeId' => 'f-nominee',
                            default => null,
                        };
                    @endphp
                    <li>
                        @if ($target)
                            {{-- Opens the blank on the page when the field is printed; otherwise scrolls to it in the list. --}}
                            <a href="#{{ $target }}" class="underline decoration-rose-300 underline-offset-2 hover:decoration-rose-600"
                               @click.prevent="$dispatch('fill-open', { name: @js($editorFor($key)) }); $nextTick(() => { const t = document.getElementById('{{ $target }}'); if (t && t.offsetParent && ! t.closest('[data-editor].fixed')) { t.scrollIntoView({ block: 'center' }); t.focus({ preventScroll: true }); } })">{{ $messages[0] }}</a>
                        @else
                            {{ $messages[0] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Status: one stepper and one next-step message --}}
    @if ($ws && $status !== TransactionStatus::Draft)
        <section x-data="{ signers: false }" data-tour="form-status" class="space-y-4 rounded-2xl border border-slate-200/70 bg-white p-4 sm:p-5" aria-labelledby="status-title">
            <h2 id="status-title" class="sr-only">Status</h2>
            @unless ($closed)
                @php $step = $progress ?? 0; @endphp
                <ol class="grid grid-cols-4 gap-2">
                    @foreach (['Submitted', 'Review', 'Signing', 'Done'] as $i => $stepLabel)
                        @php
                            $done = $step >= 4 || $i < $step;
                            $current = ! $done && $i === $step;
                            $bar = $done ? 'bg-emerald-500' : ($current ? ($nextTone === 'amber' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200');
                        @endphp
                        <li class="min-w-0" @if ($current) aria-current="step" @endif>
                            <div class="h-1.5 rounded-full {{ $bar }}" aria-hidden="true"></div>
                            <p class="mt-1.5 text-[11px] leading-tight sm:text-xs {{ $current ? 'font-semibold text-slate-900' : ($done ? 'text-slate-700' : 'text-slate-500') }}">
                                {{ $stepLabel }}<span class="sr-only">{{ $done ? ', done' : ($current ? ', current' : '') }}</span>
                            </p>
                        </li>
                    @endforeach
                </ol>
            @endunless

            @if ($nextText !== null)
                <div class="rounded-xl px-4 py-3.5 {{ $toneBox[$nextTone] ?? $toneBox['slate'] }}" @if ($nextTone === 'amber') role="status" @endif>
                    <p><x-status-badge :workspace="$ws" /></p>
                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ $nextText }}</p>

                    @if ($status === TransactionStatus::Completed)
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                                    class="inline-flex min-h-9 items-center gap-1.5 py-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:opacity-50">
                                <i class="bx bx-download text-base" aria-hidden="true"></i>
                                <span wire:loading.remove wire:target="downloadPdf">Download PDF copy</span>
                                <span wire:loading wire:target="downloadPdf">Preparing…</span>
                            </button>
                            @if ($ws->verification_token)
                                <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-emerald-800 hover:underline">
                                    Open verification page<span class="sr-only"> (opens in a new tab)</span>
                                </a>
                            @endif
                        </div>
                    @elseif ($status === TransactionStatus::Voided && $ws->superseded_by && $isOwner)
                        <a href="{{ route('student.transactions.show', $ws->superseded_by) }}" wire:navigate
                           class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">Open the corrected draft &rarr;</a>
                    @endif
                </div>
            @endif

            @if ($approvals->isNotEmpty())
                <button type="button" @click="signers = !signers" :aria-expanded="signers" aria-controls="signer-list"
                        class="inline-flex items-center gap-1 rounded text-sm font-medium text-[#2A57B4] hover:text-[#1E4090] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                    <i class="bx text-lg" :class="signers ? 'bx-chevron-up' : 'bx-chevron-down'" aria-hidden="true"></i>
                    <span x-text="signers ? 'Hide signatures' : 'Show signatures ({{ $signedCount }} of {{ $approvals->count() }} signed)'">Show signatures</span>
                </button>
                <ol id="signer-list" x-show="signers" x-cloak class="space-y-3 border-t border-slate-100 pt-4">
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
                                ApprovalStatus::Pending => 'Pending',
                                ApprovalStatus::Rejected => 'Rejected',
                                ApprovalStatus::Skipped => 'Cancelled',
                                default => 'Queued',
                            };
                        @endphp
                        <li class="flex items-start gap-3" wire:key="appr-{{ $a->id }}">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
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
    @endif

    {{-- The form itself: answers are typed straight onto the sheet. Edits tell the PDF view to refresh. --}}
    <form wire:submit="submit" novalidate
          @if ($editable) wire:confirm="Submit to the VPSD office? You can't edit it afterwards unless they ask for changes." @endif
          class="space-y-4"
          x-data="pdfFill({ editable: @js($editable), placed: @js($editable ? $printed : []), deps: @js(\App\Support\PdfFill::deps($document)) })"
          @input.debounce.500ms="refresh()" @change.debounce.250ms="refresh()" @fill-refresh.debounce.400ms="refresh()"
          @fill-open.window="$event.detail.name && placed[$event.detail.name] && open($event.detail.name)"
          aria-labelledby="form-title">
        <template x-ref="errs" data-errors="{{ json_encode($errorEditors) }}"></template>
        <h2 id="form-title" class="sr-only">{{ $editable ? 'Fill in the form' : 'Your answers' }}</h2>

        {{-- Before you start: a popup that opens with the form (reopen it from the info button). --}}
        @if ($editable)
            @include('livewire.workflow._intro-modal', [
                'storageKey' => 'student.' . $document->key(),
                'title' => $document->name(),
                'lead' => $document->description(),
                // Start to finish, in order (WorkflowDocument::guide: the real signers of this form, in their order).
                'steps' => $guide,
                'items' => $needs,
                'note' => $document->studentNote(),
            ])
        @elseif ($document->studentNote())
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $document->studentNote() }}</p>
        @endif

        {{-- The printed page itself: tap a blue blank to fill it in. What you see is what prints. --}}
        <div data-tour="form-details">
            <x-doc.pdf-page :label="$document->name() . ($editable ? ': tap a blank to fill it in' : '')" />
        </div>

        {{-- Your details print from your account --}}
        <section aria-labelledby="sec-student" data-tour="form-student">
            <h3 id="sec-student" class="sr-only">Your details, from your account</h3>
            @if ($editable && ($missing->isNotEmpty() || ($needsSig && ! $sigImg)))
                <p class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status">
                    <i class="bx bx-error mt-0.5 text-base" aria-hidden="true"></i>
                    <span>
                        Missing: {{ $missing->implode(', ') }}{{ $needsSig && ! $sigImg ? ($missing->isNotEmpty() ? ', ' : '') . 'e-signature' : '' }}.
                        <button type="button" wire:click="saveAndEditProfile" class="font-semibold underline">Add it in your profile</button>
                    </span>
                </p>
            @elseif ($editable)
                {{-- Saves what was typed as a draft before leaving for the profile --}}
                <p class="flex flex-wrap items-center justify-between gap-x-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1"><i class="bx bx-lock-alt" aria-hidden="true"></i>Your name and program print from your profile.</span>
                    <button type="button" wire:click="saveAndEditProfile" wire:loading.attr="disabled" wire:target="saveAndEditProfile"
                            class="inline-flex min-h-8 items-center gap-1 rounded font-medium text-[#2A57B4] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                        <i class="bx bx-loader-alt animate-spin" aria-hidden="true" wire:loading wire:target="saveAndEditProfile"></i> Edit in profile
                    </button>
                </p>
            @endif
        </section>

        {{-- What the page does not print (and, on a phone, each blank's editor sheet): a short list under the page. --}}
        <div data-extra class="space-y-6" :class="hasExtra ? 'rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-200/80 sm:px-6' : ''">
            <h3 x-show="hasExtra" x-cloak class="{{ $D::HEADING }} mb-4">{{ $editable ? 'Also needed (not printed)' : 'Other details' }}</h3>
        {{-- 2. Officer check (permits): asked first; the rest of the form opens only for officers. --}}
        @if ($document->requiresOfficer())
            @php $officerField = $document->fields()['is_officer'] ?? null; @endphp
            <section data-always aria-labelledby="sec-officer">
                <h3 id="sec-officer" class="sr-only">Organization officer</h3>

                @if ($editable && $officerField)
                    <fieldset id="f-is_officer" tabindex="-1"
                              @error('values.is_officer') aria-describedby="e-is_officer" @enderror>
                        <legend class="text-[15px] text-slate-800">{{ $officerField['label'] }} <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></legend>
                        <div class="mt-1 flex flex-wrap gap-x-4">
                            @foreach ($officerField['options'] as $opt)
                                <label class="{{ $D::OPTION }}">
                                    <input type="radio" name="is_officer" wire:model.live="values.is_officer" value="{{ $opt }}" class="h-4 w-4 accent-[#2A57B4]">
                                    <span>{{ $officerField['option_labels'][$opt] ?? $opt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('values.is_officer') <p id="e-is_officer" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

                    @if (($values['is_officer'] ?? null) === 'No')
                        <p class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-900" role="status">
                            <i class="bx bx-block mt-0.5 text-base" aria-hidden="true"></i>
                            Only organization officers can request this. Ask an officer to file it.
                        </p>
                    @endif
                @endif

                @if (! $editable || ($values['is_officer'] ?? null) === 'Yes')
                    <div class="mt-3">
                        <p id="sys-officer" class="{{ $D::VALUE }} {{ $officerLine ? '' : '!border-amber-400 !font-normal italic !text-amber-700' }}">{{ $officerLine ?: 'No officer record on your account' }}</p>
                        <span class="{{ $caption }} inline-flex items-center gap-1"><i class="bx bx-lock-alt not-italic" aria-hidden="true"></i>Position in the organization</span>
                        @unless ($officerLine)
                            <p class="mt-1 text-xs text-amber-800">Your adviser or the VPSD office must add it first.</p>
                        @endunless
                    </div>
                @endif
            </section>
        @endif

        {{-- 3. Request details --}}
        <div @if ($officerGate) x-show="$wire.values.is_officer === 'Yes'" x-cloak @endif>
        <section aria-labelledby="sec-details">
            <h3 id="sec-details" class="sr-only">{{ $editable ? 'Your answers: type on the lines' : 'Your answers' }}</h3>

            @if ($document->usesNomineePicker() && ($editable || ! in_array('__nominee', $printed, true)))
                <x-doc.pop name="__nominee" :pop="$editable">
                <div data-field class="group/f relative mb-5" x-data @keydown.escape="$wire.set('nomineeSearch', '')">

                    @if ($nomineeCard)
                        <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $nomineeCard['name'] }}</p>
                                    <dl class="mt-1.5 grid grid-cols-1 gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                                        <div><dt class="text-slate-500">College</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['college'] ?: '—' }}</dd></div>
                                        <div><dt class="text-slate-500">Year and section</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['year_section'] ?: '—' }}</dd></div>
                                        <div class="sm:col-span-2"><dt class="text-slate-500">Program</dt><dd class="font-medium text-slate-700">{{ $nomineeCard['program'] ?: '—' }}</dd></div>
                                    </dl>
                                </div>
                                @if ($editable)
                                    <button type="button" id="f-nominee" wire:click="clearNominee"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                                            aria-label="Remove {{ $nomineeCard['name'] }} and choose another nominee">
                                        <i class="bx bx-x text-xl" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                            <p class="mt-2 text-[11px] italic text-slate-500">Nominee · from their profile</p>
                        </div>
                    @elseif ($editable)
                        <div class="{{ $box }} {{ $errors->has('nomineeId') ? $D::ERROR_LINE : '' }}">
                            <i class="bx bx-search text-base text-slate-400" aria-hidden="true"></i>
                            <input id="f-nominee" type="search" wire:model.live.debounce.300ms="nomineeSearch" autocomplete="off" @keydown.enter.prevent
                                   placeholder="Type a name or student number" class="{{ $bare }}"
                                   aria-describedby="h-nominee @error('nomineeId') e-nominee @enderror" @error('nomineeId') aria-invalid="true" @enderror>
                        </div>
                        <label for="f-nominee" class="{{ $caption }}">Nominee <span class="not-italic text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <p id="h-nominee" class="{{ $help }}">Pick the student from the list.</p>

                        @if (strlen($nomineeSearch) >= 2)
                            <div class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg" role="list" aria-label="Matching students">
                                @forelse ($this->nomineeResults as $s)
                                    <button type="button" wire:click="selectNominee({{ $s->id }})" wire:key="nom-{{ $s->id }}" role="listitem"
                                            class="flex w-full items-center justify-between gap-2 px-3.5 py-2.5 text-left text-sm hover:bg-slate-50 focus:bg-blue-50 focus:outline-none">
                                        <span class="min-w-0 truncate">
                                            {{ $s->full_name }}
                                            <span class="block truncate text-[11px] text-slate-500">{{ $s->program?->name }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-500">{{ $s->student_number }}</span>
                                    </button>
                                @empty
                                    <p class="px-3.5 py-2.5 text-sm text-slate-500" role="status">No active student found.</p>
                                @endforelse
                            </div>
                        @endif
                    @endif

                    @error('nomineeId') <p id="e-nominee" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                </div>
                </x-doc.pop>
            @endif

            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                @foreach ($document->fields() as $name => $f)
                    @php
                        $type = $f['type'] ?? 'text';
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

                        $required = $f['required'] ?? false;
                        $titleKey = $f['salutation'] ?? null;
                        $icon = $fieldIcon($name, $f);
                        $hasError = $errors->has("values.$name") || ($titleKey && $errors->has("values.$titleKey"));
                        $describedBy = trim((! empty($f['help']) ? "h-$name " : '') . ($hasError ? "e-$name" : ''));
                        $isEmail = str_ends_with($name, '_email');
                    @endphp
                    @continue(! empty($f['staff_only']) || ! empty($f['hidden']) || ! empty($f['salutation_of']))
                    @continue($name === 'is_officer' && $document->requiresOfficer())
                    @continue(! $editable && (($dep && ($values[$depKey] ?? null) !== $depVal) || $hiddenNow))
                    @continue(($f['group'] ?? null) === 'schedule')
                    {{-- Read-only: what the page prints is read there, not repeated here. --}}
                    @continue(! $editable && in_array($name, $printed, true))
                    @if ($type === 'slots')
                        <x-doc.pop :name="$name" :pop="$editable"><div data-field class="sm:col-span-2">@include('livewire.workflow._slots-field', ['name' => $name, 'f' => $f])</div></x-doc.pop>
                        @continue
                    @endif

                    <x-doc.pop :name="$name" :pop="$editable">
                    <div wire:key="f-{{ $name }}" class="group/f min-w-0 {{ $isShort($name, $f) ? '' : 'sm:col-span-2' }}" @if ($editable && $xShow) x-show="{{ $xShow }}" x-cloak @endif>
                        {{-- 'question' => '...' puts a question above a field (e.g. before a set of checkboxes) --}}
                        @if (! empty($f['question']))
                            <p class="mb-1 text-[15px] text-slate-800">{{ $f['question'] }}@if ($editable && ! empty($f['question_required']))<span class="text-rose-600" aria-hidden="true"> *</span><span class="sr-only">(required)</span>@endif</p>
                        @endif
                        @if ($type === 'checkbox')
                            @if ($editable)
                                <label for="f-{{ $name }}" class="{{ $D::OPTION }} -mx-2 items-start">
                                    <input id="f-{{ $name }}" type="checkbox" wire:model="values.{{ $name }}"
                                           class="mt-1 h-4 w-4 shrink-0 rounded-sm accent-[#2A57B4]" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                                    <span>{{ $f['label'] }}</span>
                                </label>
                            @else
                                <p class="flex items-center gap-2 text-sm text-slate-800">
                                    <i class="bx {{ ! empty($values[$name]) ? 'bx-checkbox-checked text-emerald-600' : 'bx-checkbox text-slate-400' }} text-xl" aria-hidden="true"></i>
                                    <span><span class="font-medium text-slate-700">{{ $f['label'] }}:</span> {{ $document->formatValue($f, $values[$name] ?? null) }}</span>
                                </p>
                            @endif

                        @elseif ($type === 'students')
                            {{-- A list of students picked from their accounts (like line items). --}}
                            @php
                                $list = array_values($values[$name] ?? []);
                                $max = $f['max_items'] ?? 10;
                            @endphp
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-[15px] text-slate-800" id="l-{{ $name }}">
                                    {{ $f['label'] }} @if ($editable && $required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                                </p>
                                <span class="text-xs tabular-nums text-slate-500" aria-live="polite">{{ count($list) }} of {{ $max }} added</span>
                            </div>
                            @if (! empty($f['help']) && $editable) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif

                            @if ($list)
                                <ol class="mt-2 space-y-2" aria-labelledby="l-{{ $name }}">
                                    @foreach ($list as $i => $row)
                                        <li wire:key="st-{{ $name }}-{{ $row['id'] ?? $i }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5">
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-slate-500 ring-1 ring-slate-200" aria-hidden="true">{{ $i + 1 }}</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-slate-900">{{ $row['name'] ?? '' }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ collect([$row['number'] ?? null, $row['program'] ?? null])->filter()->implode(' · ') }}</p>
                                            </div>
                                            @if ($editable)
                                                <button type="button" wire:click="removeStudent({{ $i }})"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                                                        aria-label="Remove {{ $row['name'] ?? 'student' }} from the list">
                                                    <i class="bx bx-trash text-lg" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @elseif (! $editable)
                                <p class="mt-1 text-sm text-slate-500">—</p>
                            @endif

                            @if ($editable && count($list) < $max)
                                <div class="relative mt-2" x-data @keydown.escape="$wire.set('studentSearch', '')">
                                    <label for="f-{{ $name }}" class="sr-only">Search for a student to add</label>
                                    <div class="{{ $box }} border-dashed">
                                        <i class="bx bx-user-plus text-lg text-slate-400" aria-hidden="true"></i>
                                        <input id="f-{{ $name }}" type="search" wire:model.live.debounce.300ms="studentSearch" autocomplete="off" @keydown.enter.prevent
                                               placeholder="Add a student: name, email or student number"
                                               class="{{ $bare }}" aria-controls="r-{{ $name }}"
                                               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                               @if ($hasError) aria-invalid="true" @endif
                                               @keydown.arrow-down.prevent="$el.closest('.relative').querySelector('[data-pick]')?.focus()">
                                        <span wire:loading wire:target="studentSearch" class="text-slate-400" aria-hidden="true"><i class="bx bx-loader-alt bx-spin"></i></span>
                                    </div>
                                    @if (mb_strlen(trim($studentSearch)) >= 2)
                                        <div id="r-{{ $name }}" class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                                             role="list" aria-label="Matching students"
                                             @keydown.arrow-down.prevent="$event.target.nextElementSibling?.focus()"
                                             @keydown.arrow-up.prevent="($event.target.previousElementSibling ?? document.getElementById('f-{{ $name }}')).focus()">
                                            @forelse ($this->studentResults as $s)
                                                <button type="button" data-pick wire:click="addStudent({{ $s->id }})" wire:key="pick-{{ $s->id }}" role="listitem"
                                                        class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm hover:bg-slate-50 focus:bg-blue-50 focus:outline-none">
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-medium text-slate-900">{{ $s->full_name }}</span>
                                                        <span class="block truncate text-xs text-slate-500">{{ $s->email }}</span>
                                                    </span>
                                                    <span class="flex shrink-0 items-center gap-2 text-xs text-slate-500">
                                                        {{ $s->student_number }}
                                                        <i class="bx bx-plus-circle text-lg text-[#2A57B4]" aria-hidden="true"></i>
                                                        <span class="sr-only">Add {{ $s->full_name }}</span>
                                                    </span>
                                                </button>
                                            @empty
                                                <p class="px-3.5 py-2.5 text-sm text-slate-500" role="status">No student account matches "{{ $studentSearch }}". They need an active DocuMate account to be listed.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            @endif

                        @elseif ($type === 'choice')
                            <fieldset id="f-{{ $name }}" tabindex="-1" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                                <legend class="text-[15px] text-slate-800">{{ $f['label'] }} @if ($editable && $required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</legend>
                                @if ($editable)
                                    <div class="-mx-2 mt-0.5 flex flex-wrap gap-x-3 {{ $hasError ? 'rounded-md bg-rose-50/60' : '' }}">
                                        @foreach ($f['options'] as $opt)
                                            <label class="{{ $D::OPTION }}">
                                                <input type="radio" name="{{ $name }}" wire:model.live="values.{{ $name }}" value="{{ $opt }}" class="h-4 w-4 accent-[#2A57B4]">
                                                <span>{{ $f['option_labels'][$opt] ?? $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="{{ $D::VALUE }}">{{ $f['option_labels'][$values[$name] ?? ''] ?? ($values[$name] ?? '—') }}</p>
                                @endif
                            </fieldset>
                            @if (! empty($f['help']) && $editable) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif

                        @else
                            @php
                                // The caption under the line names what goes there, like the printed form.
                                $captionHtml = e($f['label']) . ($editable && $required ? '<span class="not-italic text-rose-600" aria-hidden="true"> *</span><span class="sr-only">(required)</span>' : '');
                            @endphp

                            @if ($editable)
                                @if ($titleKey)
                                    {{-- Honorifics (Mr./Ms./Dr./Prof.… or typed) and name on one line. --}}
                                    @php $titleField = $document->fields()[$titleKey] ?? []; @endphp
                                    <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[7rem_minmax(0,1fr)]">
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $errors->has("values.$titleKey") ? $D::ERROR_LINE : '' }}">
                                            <label for="f-{{ $titleKey }}" class="sr-only">Honorifics for {{ strtolower($f['label']) }}</label>
                                            <x-dm.honorific-input id="f-{{ $titleKey }}" model="values.{{ $titleKey }}" class="{{ $bare }} pr-1"
                                                    :aria-required="($titleField['required'] ?? false) ? 'true' : null"
                                                    :aria-invalid="$errors->has('values.' . $titleKey) ? 'true' : null" />
                                        </div>
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $errors->has("values.$name") ? $D::ERROR_LINE : '' }}">
                                            <input id="f-{{ $name }}" type="text" wire:model="values.{{ $name }}" autocomplete="off"
                                                   @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                                   placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}"
                                                   @if ($required) aria-required="true" @endif
                                                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                   @error("values.$name") aria-invalid="true" @enderror>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[7rem_minmax(0,1fr)]">
                                        <label for="f-{{ $titleKey }}" class="{{ $caption }}">Title</label>
                                        <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                                    </div>
                                @elseif ($type === 'textarea')
                                    {{-- Ruled lines, as on paper --}}
                                    <div x-data="{ n: ($wire.values['{{ $name }}'] || '').length }"
                                         class="relative rounded-sm transition-colors focus-within:bg-[#2A57B4]/[0.03] {{ $hasError ? 'bg-rose-50/50' : '' }}">
                                        <textarea id="f-{{ $name }}" wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}"
                                                  @input="n = $el.value.length" placeholder="{{ $f['placeholder'] ?? '' }}"
                                                  class="{{ $D::LINED }}"
                                                  @if ($required) aria-required="true" @endif
                                                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                  @if ($hasError) aria-invalid="true" @endif></textarea>
                                        <span class="pointer-events-none absolute -bottom-5 right-0 hidden text-[11px] tabular-nums text-slate-400 group-focus-within/f:block" aria-hidden="true"><span x-text="n"></span>/{{ $f['max'] ?? 500 }}</span>
                                    </div>
                                    <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                                @elseif ($type === 'select')
                                    <div class="{{ $box }} {{ $hasError ? $D::ERROR_LINE : '' }}">
                                        <select id="f-{{ $name }}" wire:model{{ ! empty($f['live']) ? '.live' : '' }}="values.{{ $name }}" class="{{ $bare }} cursor-pointer pr-8"
                                                @if ($required) aria-required="true" @endif
                                                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                @if ($hasError) aria-invalid="true" @endif>
                                            <option value="">Choose…</option>
                                            @foreach ($f['options'] as $opt)
                                                <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                                @else
                                    @php
                                        $inputType = $isEmail ? 'email' : (['date' => 'date', 'time' => 'time'][$type] ?? 'text');
                                        [$minDate, $maxDate] = match (true) {
                                            $type !== 'date' => [null, null],
                                            ! empty($f['past_only']) => [null, now()->subDay()->toDateString()],
                                            ! empty($f['future']) => [now()->toDateString(), null],
                                            default => [null, now()->toDateString()],
                                        };
                                    @endphp
                                    <div class="{{ $box }} {{ $hasError ? $D::ERROR_LINE : '' }}">
                                        <input id="f-{{ $name }}" wire:model{{ $isEmail ? '.blur' : '' }}="values.{{ $name }}"
                                               type="{{ $inputType }}"
                                               @if ($isEmail) inputmode="email" autocomplete="off" spellcheck="false" @endif
                                               @if ($minDate) min="{{ $minDate }}" @endif
                                               @if ($maxDate) max="{{ $maxDate }}" @endif
                                               @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                               placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}"
                                               @if ($required) aria-required="true" @endif
                                               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                               @if ($hasError) aria-invalid="true" @endif
                                               @if ($type === 'date' && ! empty($f['today_warning']))
                                                   x-data x-on:change="if ($event.target.value === @js(now()->toDateString())) $dispatch('today-warning', { text: @js($f['today_warning']) })"
                                               @endif>
                                    </div>
                                    <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                                @endif

                                @if (! empty($f['help'])) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif
                                @if ($isEmail && ! empty($values[$name]) && \App\Services\Workflow\ExternalSigning::accountFor($values[$name], auth()->id()))
                                    <p class="mt-1 text-xs font-medium text-emerald-700">Has a DocuMate account: signs from there, no email link.</p>
                                @endif
                            @else
                                @php
                                    $shown = $titleKey ? $document->personName($values, $name) : $document->formatValue($f, $values[$name] ?? null);
                                @endphp
                                <p class="{{ $D::VALUE }}">{{ $shown ?: '—' }}</p>
                                <span class="{{ $caption }}">{{ $f['label'] }}</span>
                            @endif
                        @endif

                        @if ($hasError)
                            <p id="e-{{ $name }}" class="{{ $err }}">
                                <i class="bx bx-error-circle mt-px" aria-hidden="true"></i>
                                {{ $errors->first("values.$name") ?: $errors->first("values.$titleKey") }}
                            </p>
                        @endif
                    </div>
                    </x-doc.pop>
                @endforeach
                @if ($document->key() === 'student_assistantship_application' && ($editable || ! in_array('__schedule', $printed, true)))
                    <x-doc.pop name="__schedule" :pop="$editable"><div data-field class="sm:col-span-2">@include('livewire.workflow._schedule-grid')</div></x-doc.pop>
                @endif
            </div>
        </section>

        @if ($document->requiresItemPhoto())
            <section data-always aria-labelledby="sec-photo">
                <h3 id="sec-photo" class="text-[15px] text-slate-800">
                    Photo of the item @if ($editable)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                </h3>
                <p class="text-xs text-slate-500">JPG or PNG, up to 10 MB. Not printed.</p>

                @if ($itemPhoto)
                    <img src="{{ $itemPhoto->temporaryUrl() }}" alt="Preview of the item photo you selected"
                         class="mt-3 max-h-56 rounded-lg border border-slate-200 bg-white object-contain p-1">
                @elseif ($hasPhoto)
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}?v={{ $ws->updated_at?->timestamp }}" alt="Photo of the item attached to this request"
                         class="mt-3 max-h-56 rounded-lg border border-slate-200 bg-white object-contain p-1">
                @endif

                @if ($editable)
                    <div class="mt-3 grid grid-cols-2 gap-2 md:max-w-xs md:grid-cols-1">
                        {{-- Camera: phones/tablets only --}}
                        <label class="cursor-pointer rounded-lg bg-[#2A57B4] px-3.5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#24499A] has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20 md:hidden">
                            <i class="bx bx-camera mr-1" aria-hidden="true"></i> Take a photo
                            <input type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" capture="environment" class="sr-only">
                        </label>
                        <label class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20 md:border-transparent md:bg-[#2A57B4] md:text-white md:hover:bg-[#24499A]">
                            <span class="md:hidden">From gallery</span>
                            <span class="hidden md:inline"><i class="bx bx-upload mr-1" aria-hidden="true"></i> Upload a photo</span>
                            <input id="f-photo" type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" class="sr-only"
                                   @error('itemPhoto') aria-invalid="true" aria-describedby="e-photo" @enderror>
                        </label>
                    </div>
                    <div wire:loading wire:target="itemPhoto" class="mt-1 text-xs text-[#2A57B4]" role="status">Uploading photo…</div>
                @endif

                @error('itemPhoto') <p id="e-photo" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
            </section>
        @endif
        </div>{{-- officer gate --}}

        </div>{{-- also needed --}}
        {{-- Consent and actions: below the form page. --}}
        @if ($editable)
            <div class="py-2" data-tour="form-actions">
                <label for="f-consent" class="flex cursor-pointer items-start gap-2.5 text-[13px] leading-snug text-slate-700">
                    <input id="f-consent" type="checkbox" wire:model="consent" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]"
                           aria-required="true" aria-describedby="consent-text @error('consent') e-consent @enderror"
                           @error('consent') aria-invalid="true" @enderror>
                    <span id="consent-text" class="{{ $errors->has('consent') ? 'text-rose-700' : '' }}">
                        This is true, and LNU may share it with the signers for this request (Data Privacy Act of 2012).
                    </span>
                </label>
                @error('consent') <p id="e-consent" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

                <div class="mt-3 flex gap-2 sm:justify-end">
                    {{-- Phones: step through the blanks on the page, one at a time --}}
                    <button type="button" @click="start()" x-show="order.length" x-cloak
                            class="inline-flex min-h-11 shrink-0 items-center justify-center gap-1 rounded-lg border border-[#2A57B4]/40 bg-[#2A57B4]/5 px-3 text-sm font-medium text-[#2A57B4] hover:bg-[#2A57B4]/10 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-9">
                        <i class="bx bx-edit-alt text-base" aria-hidden="true"></i> Fill in
                    </button>
                    <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,submit"
                            class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 sm:min-h-9 sm:flex-none">
                        <i class="bx bx-save text-base" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="saveDraft">Draft</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveDraft,submit"
                            @if ($officerGate) :disabled="$wire.values.is_officer !== 'Yes'" @endif
                            class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-9 sm:flex-none">
                        <i class="bx bx-send text-base" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="submit">{{ $status === TransactionStatus::ActionRequired ? 'Resubmit' : 'Submit' }}</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                </div>
            </div>
        @endif
    </form>
    {{-- Activity, folded away --}}
    @if (count($timeline))
        <div x-data="{ open: false }" class="text-center">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="activity-log"
                    class="rounded text-sm font-medium text-slate-500 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                    x-text="open ? 'Hide activity log' : 'Show activity log ({{ count($timeline) }})'">Show activity log</button>
            <section id="activity-log" x-show="open" x-cloak class="mt-3 space-y-3 rounded-2xl border border-slate-200/70 bg-white p-5 text-left" aria-label="Activity">
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
</div>{{-- left column --}}


    {{-- A date field with 'today_warning' => '...' shows this when today is picked (e.g. an absence starting today). --}}
    <div x-data="{ text: null }" @today-warning.window="text = $event.detail.text" @keydown.escape.window="text = null">
        <template x-teleport="body">
            <div x-show="text" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4"
                 role="alertdialog" aria-modal="true" aria-labelledby="today-warning-title" @click.self="text = null">
                <div class="w-full max-w-md rounded-t-2xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl sm:rounded-2xl sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="bx bx-error text-2xl" aria-hidden="true"></i></span>
                        <div class="min-w-0">
                            <h2 id="today-warning-title" class="text-base font-semibold text-slate-900">Your date of absence is today</h2>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600" x-text="text"></p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="button" @click="text = null" x-effect="if (text) $nextTick(() => $el.focus())"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:min-h-11">I understand</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- "How this works": a guided tour of this form, from the question mark in the header --}}
    @php
        $tourSteps = array_values(array_filter([
            ['target' => 'form-head', 'title' => $document->name(), 'text' => 'You fill this in right on the form. Here is how.'],
            ['target' => 'form-status', 'title' => 'Where it stands', 'text' => 'After you submit: VPSD checks it, signers sign, then you download it.'],
            ['target' => 'form-student', 'title' => 'Your details', 'text' => 'Printed from your account. Wrong? Use Edit in profile.'],
            ['target' => 'form-details', 'type' => 'Sample answer', 'title' => 'Type on the lines', 'text' => 'Type each answer on its line, like this sample. * means required.'],
            ['target' => 'form-preview', 'title' => 'The printed copy', 'text' => 'PDF shows exactly how it prints. Nothing is saved.'],
            ['target' => 'form-actions', 'title' => 'Save or submit', 'text' => 'Save a draft to finish later. Tick the box, then Submit.'],
            $guide ? ['target' => 'form-help', 'title' => 'Steps and checklist', 'text' => 'Tap the question mark anytime to see this tour again.', 'cta' => ['label' => 'See the steps and checklist', 'event' => 'open-guide']]
                   : ['target' => 'form-help', 'title' => 'Need this again?', 'text' => 'Tap the question mark anytime to see this tour again.'],
        ]));
    @endphp
    <x-dm.tour key="documate.tour.form.v2" :steps="$tourSteps" :auto="false" />

    {{-- Officer-only forms: a student without an officer record is stopped here (save and submit refuse too). --}}
    @if ($this->blockedNotOfficer)
        <template x-teleport="body">
            <div class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                 role="alertdialog" aria-modal="true" aria-labelledby="officer-only-title" aria-describedby="officer-only-text">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl" x-data x-init="$nextTick(() => $el.querySelector('a')?.focus())">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="bx bx-lock-alt text-2xl" aria-hidden="true"></i></span>
                    <h2 id="officer-only-title" class="mt-3 text-lg font-semibold text-slate-900">For organization officers only</h2>
                    <p id="officer-only-text" class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        Only officers of a recognized student organization can use the {{ $document->name() }}.
                        Your account has no officer record. If you are an officer, ask your organization adviser or the VPSD office to add you.
                    </p>
                    <div class="mt-5 grid gap-2">
                        <a href="{{ route('student.home') }}" wire:navigate
                           class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30">Back to Home</a>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
