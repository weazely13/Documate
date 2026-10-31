@php
    use App\Enums\ApprovalStatus;
    use App\Enums\TransactionStatus;

    $editable = $this->isEditable;
    $document = $this->document;

    // One bordered box per field: the label sits inside the box, above the value (with an optional icon).
    $box = 'group/box relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3.5 transition focus-within:border-[#2A57B4] focus-within:ring-[3px] focus-within:ring-[#2A57B4]/20';
    $boxLocked = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5';
    $bare = 'w-full min-w-0 border-0 bg-transparent p-0 py-3 text-base text-slate-900 placeholder-slate-400 outline-none focus:ring-0 sm:py-2.5 sm:text-sm';
    $label = 'block text-[13px] font-medium text-slate-700';
    $help = 'mt-1 text-xs leading-relaxed text-slate-500';
    $err = 'mt-1 flex items-start gap-1 text-xs font-medium text-rose-600';

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

    $fieldIcon = function (string $name, array $f): ?string {
        if (! empty($f['icon'])) return $f['icon'];
        return match (true) {
            ($f['type'] ?? '') === 'date' => 'bx-calendar',
            ($f['type'] ?? '') === 'time' => 'bx-time-five',
            str_ends_with($name, '_email') => 'bx-envelope',
            str_ends_with($name, '_name') || $name === 'professor' => 'bx-user',
            in_array($name, ['venue', 'address'], true) => 'bx-map',
            in_array($name, ['subject', 'activity'], true) => 'bx-book-open',
            str_contains($name, 'contact') || str_contains($name, 'phone') => 'bx-phone',
            default => null,
        };
    };

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

<div class="mx-auto w-full max-w-2xl xl:grid xl:max-w-[1440px] xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:items-start xl:gap-6">
<div class="min-w-0 space-y-5">

    {{-- Header. The full A4 document (narrow screens), from the live answers while editable. Nothing is saved by previewing. --}}
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
        <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-1 text-sm text-slate-500">
                <li><a href="{{ $back['url'] }}" wire:navigate class="rounded font-medium hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">{{ $back['label'] }}</a></li>
                <li aria-hidden="true"><i class="bx bx-chevron-right text-base text-slate-400"></i></li>
                <li class="min-w-0 truncate font-medium text-slate-700" aria-current="page">{{ $ws ? ($ws->transaction_code ?: 'Draft') : 'New transaction' }}</li>
            </ol>
        </nav>

        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-x-4 sm:gap-y-2">
            <div class="min-w-0 flex-1">
                <h1 class="break-words text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $document->name() }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $document->description() }}
                    @if ($ws?->submitted_at) <span class="whitespace-nowrap">&middot; Submitted {{ $ws->submitted_at->format('M j, Y') }}</span> @endif
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($editable)
                    <button type="button" @click="$dispatch('open-intro')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
                            aria-label="Before you start: what you will need" title="Before you start">
                        <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                    </button>
                @endif
                @if ($guide)
                    <button type="button" @click="$dispatch('open-guide')" x-ref="guideBtn"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:min-h-11 [@media(pointer:coarse)]:w-11"
                            aria-label="How this works: steps and checklist" title="How this works">
                        <i class="bx bx-help-circle text-xl" aria-hidden="true"></i>
                    </button>
                @endif
                <button type="button" @click="show()"
                        class="inline-flex min-h-9 items-center gap-1.5 py-1.5 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] xl:hidden [@media(pointer:coarse)]:min-h-[44px]">
                    <i class="bx bx-show text-base text-slate-500" aria-hidden="true"></i> Preview document
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
                            <h2 id="doc-preview-title" class="text-sm font-semibold text-slate-900">Document preview</h2>
                            <p class="text-xs text-slate-500">
                                @if ($editable)
                                    Shows your current answers. Nothing is saved or submitted by previewing.
                                @else
                                    The document as it stands now.
                                @endif
                                Signatures appear as they are given; the QR code and fingerprint appear once the document is issued.
                            </p>
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
                            <a href="#{{ $target }}" class="underline decoration-rose-300 underline-offset-2 hover:decoration-rose-600"
                               @click.prevent="const t = document.getElementById('{{ $target }}'); if (t) { t.scrollIntoView({ block: 'center' }); t.focus({ preventScroll: true }); }">{{ $messages[0] }}</a>
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
        <section x-data="{ signers: false }" class="space-y-4 rounded-2xl border border-slate-200/70 bg-white p-4 sm:p-5" aria-labelledby="status-title">
            <h2 id="status-title" class="sr-only">Status</h2>
            @unless ($closed)
                @php $step = $progress ?? 0; @endphp
                <ol class="grid grid-cols-4 gap-2">
                    @foreach (['Submitted', 'VPSD review', 'Signatures', 'Done'] as $i => $stepLabel)
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

            @if ($nextTitle)
                <div class="rounded-xl px-4 py-3.5 {{ $toneBox[$nextTone] }}" @if ($nextTone === 'amber') role="status" @endif>
                    <p class="text-sm font-semibold">{{ $nextTitle }}</p>
                    <p class="mt-0.5 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ $nextText }}</p>

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
                                ApprovalStatus::Pending => 'Waiting for signature',
                                ApprovalStatus::Rejected => 'Rejected',
                                ApprovalStatus::Skipped => 'Cancelled',
                                default => 'Not started',
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

    {{-- The form. Edits tell the side preview to refresh. --}}
    <form wire:submit="submit" novalidate
          @if ($editable) wire:confirm="Submit to the VPSD office? You can't edit it afterwards unless they ask for changes." @endif
          class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm"
          x-data @input="$dispatch('doc-changed')" @change="$dispatch('doc-changed')"
          aria-labelledby="form-title">
        <h2 id="form-title" class="sr-only">{{ $editable ? 'Fill in the form' : 'Your answers' }}</h2>

        {{-- Before you start: a popup that opens with the form (reopen it from the info button). --}}
        @if ($editable)
            @include('livewire.workflow._intro-modal', [
                'storageKey' => 'student.' . $document->key(),
                'lead' => $document->name() . ': ' . $document->description(),
                'items' => array_merge(
                    ['Fields marked * are required. Greyed-out fields come from your account and can only be changed in your profile.'],
                    array_map(fn ($n) => 'You will need: ' . $n, $needs),
                    ['You can save a draft at any time and finish later. Submitting asks you to confirm and give your consent.'],
                ),
                'note' => $document->studentNote(),
            ])
        @elseif ($document->studentNote())
            <p class="border-b border-slate-100 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:px-5">{{ $document->studentNote() }}</p>
        @endif

        {{-- 1. Student information: from the account, shown locked so the student sees what is printed. --}}
        <section x-data="{ open: {{ $editable && ($missing->isNotEmpty() || ($needsSig && ! $sigImg)) ? 'true' : 'false' }} }"
                 class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="sec-student">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="student-info"
                        class="-ml-1 inline-flex items-center gap-1.5 rounded-lg px-1 py-1 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                    <i class="bx text-xl text-slate-400 transition-transform" :class="open ? 'bx-chevron-down' : 'bx-chevron-right'" aria-hidden="true"></i>
                    <span id="sec-student" class="text-sm font-semibold text-slate-900">Student information</span>
                    <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">From your account</span>
                </button>
                @if ($editable)
                    <a href="{{ route('profile') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-[#2A57B4] hover:underline">
                        <i class="bx bx-edit-alt" aria-hidden="true"></i> Edit in profile
                    </a>
                @endif
            </div>

            <div id="student-info" x-show="open" x-cloak class="mt-3">
                <p class="{{ $help }} mt-0">These details are filled in for you and printed on the form. They cannot be typed over here.</p>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($sysFields as $key => $sysLabel)
                        @php $sysVal = trim((string) ($student[$key] ?? '')); @endphp
                        <div wire:key="sys-{{ $key }}" class="{{ $key === 'student_program' ? 'sm:col-span-2' : '' }}">
                            <label for="sys-{{ $key }}" class="{{ $label }}">{{ $sysLabel }}</label>
                            <div class="{{ $boxLocked }} {{ $sysVal === '' ? 'border-amber-300 bg-amber-50' : '' }}">
                                <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                                <input id="sys-{{ $key }}" type="text" value="{{ $sysVal }}" disabled
                                       placeholder="{{ $sysVal === '' ? 'Missing — add it in your profile' : '' }}"
                                       class="{{ $bare }} cursor-not-allowed text-slate-700 disabled:text-slate-700 {{ $sysVal === '' ? 'placeholder-amber-700' : '' }}">
                            </div>
                        </div>
                    @endforeach
                    @if ($needsSig)
                        <div class="sm:col-span-2" wire:key="sys-signature">
                            <p class="{{ $label }}">Signature (from your profile)</p>
                            <div class="mt-1 flex items-center gap-3 rounded-xl border {{ $sigImg ? 'border-slate-200 bg-slate-50' : 'border-amber-300 bg-amber-50' }} px-3.5 py-2">
                                <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                                @if ($sigImg)
                                    <img src="{{ $sigImg }}" alt="Your saved e-signature" class="h-12 rounded-lg bg-white p-1">
                                @else
                                    <span class="text-sm font-semibold text-amber-800">Not set — add your e-signature in your profile</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($editable && ($missing->isNotEmpty() || ($needsSig && ! $sigImg)))
                <p class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status">
                    <i class="bx bx-error mt-0.5 text-base" aria-hidden="true"></i>
                    <span>
                        Missing from your account: {{ $missing->implode(', ') }}{{ $needsSig && ! $sigImg ? ($missing->isNotEmpty() ? ', ' : '') . 'e-signature' : '' }}.
                        <a href="{{ route('profile') }}" wire:navigate class="font-semibold underline">Update your profile</a> before submitting.
                    </span>
                </p>
            @endif
        </section>

        {{-- 2. Officer check (permits): asked first; the rest of the form opens only for officers. --}}
        @if ($document->requiresOfficer())
            @php $officerField = $document->fields()['is_officer'] ?? null; @endphp
            <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="sec-officer">
                <h3 id="sec-officer" class="text-sm font-semibold text-slate-900">Organization officer</h3>
                <p class="{{ $help }}">{{ $officerField['help'] ?? '' }}</p>

                @if ($editable && $officerField)
                    <fieldset class="mt-3" id="f-is_officer" tabindex="-1"
                              @error('values.is_officer') aria-describedby="e-is_officer" @enderror>
                        <legend class="{{ $label }}">{{ $officerField['label'] }} <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></legend>
                        <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($officerField['options'] as $opt)
                                <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm transition hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                    <input type="radio" name="is_officer" wire:model.live="values.is_officer" value="{{ $opt }}" class="h-4 w-4 accent-[#2A57B4]">
                                    <span class="font-medium text-slate-800">{{ $officerField['option_labels'][$opt] ?? $opt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('values.is_officer') <p id="e-is_officer" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

                    @if (($values['is_officer'] ?? null) === 'No')
                        <p class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-900" role="status">
                            <i class="bx bx-block mt-0.5 text-base" aria-hidden="true"></i>
                            Only officers of a student organization can request this permit. Ask an officer of your organization to file it for your group.
                        </p>
                    @endif
                @endif

                @if (! $editable || ($values['is_officer'] ?? null) === 'Yes')
                    <div class="mt-3">
                        <label for="sys-officer" class="{{ $label }}">Position held in the organization</label>
                        <div class="{{ $boxLocked }} {{ $officerLine ? '' : 'border-amber-300 bg-amber-50' }}">
                            <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                            <input id="sys-officer" type="text" value="{{ $officerLine }}" disabled
                                   placeholder="No officer record on your account"
                                   class="{{ $bare }} cursor-not-allowed text-slate-700 placeholder-amber-700">
                        </div>
                        <p class="{{ $help }}">
                            @if ($officerLine)
                                Printed beside your signature on the permit.
                            @else
                                Your organization adviser or the VPSD office must add your officer record before you can submit.
                            @endif
                        </p>
                    </div>
                @endif
            </section>
        @endif

        {{-- 3. Request details --}}
        <div @if ($officerGate) x-show="$wire.values.is_officer === 'Yes'" x-cloak @endif>
        <section class="px-4 py-5 sm:px-5" aria-labelledby="sec-details">
            <h3 id="sec-details" class="text-sm font-semibold text-slate-900">{{ $editable ? 'Request details' : 'Your answers' }}</h3>
            @if ($editable)
                <p class="{{ $help }}">Answer each item as it should appear on the printed form. The preview shows the result as you type.</p>
            @endif

            @if ($document->usesNomineePicker())
                <div class="relative mt-4" x-data @keydown.escape="$wire.set('nomineeSearch', '')">
                    <label for="f-nominee" class="{{ $label }}">
                        Nominee @if ($editable)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                    </label>

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
                            <p class="mt-2 text-[11px] text-slate-500">Filled in from the nominee's profile. Their address and contact number are added to the form automatically.</p>
                        </div>
                    @elseif ($editable)
                        <div class="{{ $box }}">
                            <i class="bx bx-search text-base text-slate-400" aria-hidden="true"></i>
                            <input id="f-nominee" type="search" wire:model.live.debounce.300ms="nomineeSearch" autocomplete="off" @keydown.enter.prevent
                                   placeholder="Search by name or student number" class="{{ $bare }}"
                                   aria-describedby="h-nominee @error('nomineeId') e-nominee @enderror" @error('nomineeId') aria-invalid="true" @enderror>
                        </div>
                        <p id="h-nominee" class="{{ $help }}">Type at least 2 letters, then pick the student from the list.</p>

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
            @endif

            <div class="mt-4 space-y-4">
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
                    @if ($type === 'slots')
                        @include('livewire.workflow._slots-field', ['name' => $name, 'f' => $f])
                        @continue
                    @endif

                    <div wire:key="f-{{ $name }}" @if ($editable && $xShow) x-show="{{ $xShow }}" x-cloak @endif>
                        @if ($type === 'checkbox')
                            @if ($editable)
                                <label for="f-{{ $name }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm text-slate-700 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50/50">
                                    <input id="f-{{ $name }}" type="checkbox" wire:model="values.{{ $name }}"
                                           class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                                    <span class="font-medium">{{ $f['label'] }}</span>
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
                                <p class="{{ $label }}" id="l-{{ $name }}">
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
                                <legend class="{{ $label }}">{{ $f['label'] }} @if ($editable && $required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</legend>
                                @if ($editable)
                                    <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach ($f['options'] as $opt)
                                            <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                                                <input type="radio" name="{{ $name }}" wire:model.live="values.{{ $name }}" value="{{ $opt }}" class="h-4 w-4 accent-[#2A57B4]">
                                                <span class="font-medium text-slate-800">{{ $f['option_labels'][$opt] ?? $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-1 text-sm text-slate-800">{{ $f['option_labels'][$values[$name] ?? ''] ?? ($values[$name] ?? '—') }}</p>
                                @endif
                            </fieldset>
                            @if (! empty($f['help']) && $editable) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif

                        @else
                            <label for="f-{{ $name }}" class="{{ $label }}">
                                {{ $f['label'] }} @if ($editable && $required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                            </label>

                            @if ($editable)
                                @if ($titleKey)
                                    {{-- Title (Mr./Ms./Dr./Prof.…) and name on one row. --}}
                                    @php $titleField = $document->fields()[$titleKey] ?? []; @endphp
                                    <div class="mt-1 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2">
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $errors->has("values.$titleKey") ? 'border-rose-400' : '' }} pr-2">
                                            <label for="f-{{ $titleKey }}" class="sr-only">Title for {{ strtolower($f['label']) }}</label>
                                            <select id="f-{{ $titleKey }}" wire:model="values.{{ $titleKey }}" class="{{ $bare }} cursor-pointer pr-1"
                                                    @if ($titleField['required'] ?? false) aria-required="true" @endif
                                                    @error("values.$titleKey") aria-invalid="true" aria-describedby="e-{{ $name }}" @enderror>
                                                <option value="">Title</option>
                                                @foreach ($titleField['options'] ?? [] as $opt)
                                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $errors->has("values.$name") ? 'border-rose-400' : '' }}">
                                            @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                            <input id="f-{{ $name }}" type="text" wire:model="values.{{ $name }}" autocomplete="off"
                                                   @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                                   placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}"
                                                   @if ($required) aria-required="true" @endif
                                                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                   @error("values.$name") aria-invalid="true" @enderror>
                                        </div>
                                    </div>
                                @elseif ($type === 'textarea')
                                    <div x-data="{ n: ($wire.values['{{ $name }}'] || '').length }"
                                         class="{{ $box }} items-start {{ $hasError ? 'border-rose-400' : '' }}">
                                        <textarea id="f-{{ $name }}" wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}"
                                                  @input="n = $el.value.length" placeholder="{{ $f['placeholder'] ?? '' }}"
                                                  class="{{ $bare }} resize-y"
                                                  @if ($required) aria-required="true" @endif
                                                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                  @if ($hasError) aria-invalid="true" @endif></textarea>
                                        <span class="pointer-events-none absolute bottom-1.5 right-3 text-[11px] tabular-nums text-slate-500" aria-hidden="true"><span x-text="n"></span>/{{ $f['max'] ?? 500 }}</span>
                                    </div>
                                @elseif ($type === 'select')
                                    <div class="{{ $box }} {{ $hasError ? 'border-rose-400' : '' }}">
                                        @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                        <select id="f-{{ $name }}" wire:model{{ ! empty($f['live']) ? '.live' : '' }}="values.{{ $name }}" class="{{ $bare }} cursor-pointer"
                                                @if ($required) aria-required="true" @endif
                                                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                                @if ($hasError) aria-invalid="true" @endif>
                                            <option value="">Choose…</option>
                                            @foreach ($f['options'] as $opt)
                                                <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
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
                                    <div class="{{ $box }} {{ $hasError ? 'border-rose-400' : '' }}">
                                        @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                        <input id="f-{{ $name }}" wire:model{{ $isEmail ? '.blur' : '' }}="values.{{ $name }}"
                                               type="{{ $inputType }}"
                                               @if ($isEmail) inputmode="email" autocomplete="off" spellcheck="false" @endif
                                               @if ($minDate) min="{{ $minDate }}" @endif
                                               @if ($maxDate) max="{{ $maxDate }}" @endif
                                               @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                               placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}"
                                               @if ($required) aria-required="true" @endif
                                               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                                               @if ($hasError) aria-invalid="true" @endif>
                                    </div>
                                @endif

                                @if (! empty($f['help'])) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif
                                @if ($isEmail && ! empty($values[$name]) && \App\Services\Workflow\ExternalSigning::accountFor($values[$name], auth()->id()))
                                    <p class="mt-1 text-xs font-medium text-emerald-700">This person has a DocuMate signatory account. They will sign from their account, no email link needed.</p>
                                @endif
                            @else
                                @php
                                    $shown = $titleKey ? $document->personName($values, $name) : $document->formatValue($f, $values[$name] ?? null);
                                @endphp
                                <p class="mt-1 whitespace-pre-wrap rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800">{{ $shown ?: '—' }}</p>
                            @endif
                        @endif

                        @if ($hasError)
                            <p id="e-{{ $name }}" class="{{ $err }}">
                                <i class="bx bx-error-circle mt-px" aria-hidden="true"></i>
                                {{ $errors->first("values.$name") ?: $errors->first("values.$titleKey") }}
                            </p>
                        @endif
                    </div>
                @endforeach
                @if ($document->key() === 'student_assistantship_application')
                    @include('livewire.workflow._schedule-grid')
                @endif
            </div>
        </section>

        @if ($document->requiresItemPhoto())
            <section class="border-t border-slate-100 px-4 py-5 sm:px-5" aria-labelledby="sec-photo">
                <h3 id="sec-photo" class="text-sm font-semibold text-slate-900">
                    Photo of the item @if ($editable)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                </h3>
                <p class="{{ $help }}">JPG or PNG, up to 10 MB. Used by the office to identify the item; not printed on the form.</p>

                @if ($itemPhoto)
                    <img src="{{ $itemPhoto->temporaryUrl() }}" alt="Preview of the item photo you selected"
                         class="mt-3 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
                @elseif ($hasPhoto)
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}?v={{ $ws->updated_at?->timestamp }}" alt="Photo of the item attached to this request"
                         class="mt-3 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
                @endif

                @if ($editable)
                    <div class="mt-3 grid grid-cols-2 gap-2 md:max-w-xs md:grid-cols-1">
                        {{-- Camera: phones/tablets only --}}
                        <label class="cursor-pointer rounded-lg bg-[#2A57B4] px-3.5 py-2.5 text-center text-sm font-medium text-white hover:bg-[#24499A] has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20 md:hidden">
                            <i class="bx bx-camera mr-1" aria-hidden="true"></i> Take a photo
                            <input type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" capture="environment" class="sr-only">
                        </label>
                        <label class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20 md:border-transparent md:bg-[#2A57B4] md:text-white md:hover:bg-[#24499A]">
                            <span class="md:hidden">Choose from gallery</span>
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

        {{-- Consent and actions --}}
        @if ($editable)
            <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-5">
                <label for="f-consent" class="flex cursor-pointer items-start gap-3 rounded-xl border {{ $errors->has('consent') ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50/70' }} px-3.5 py-3 text-sm text-slate-700">
                    <input id="f-consent" type="checkbox" wire:model="consent" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]"
                           aria-required="true" aria-describedby="consent-text @error('consent') e-consent @enderror"
                           @error('consent') aria-invalid="true" @enderror>
                    <span id="consent-text">
                        <span class="font-semibold text-slate-900">I confirm and consent.</span>
                        The information I entered is true and complete. I agree that Leyte Normal University may process my personal data,
                        and share it with the people who sign this form, for this request only, under the Data Privacy Act of 2012.
                    </span>
                </label>
                @error('consent') <p id="e-consent" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-slate-500">Only submitting needs the consent box. Drafts are private to you.</p>

                <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:gap-3">
                        <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,submit"
                                class="inline-flex w-full items-center justify-center gap-1.5 min-h-11 py-2 text-center leading-snug rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 sm:min-h-9 sm:py-1.5 sm:w-auto [@media(pointer:coarse)]:min-h-11">
                            <i class="bx bx-save text-base" aria-hidden="true"></i>
                            <span wire:loading.remove wire:target="saveDraft">Save as draft</span>
                            <span wire:loading wire:target="saveDraft">Saving…</span>
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveDraft,submit"
                                @if ($officerGate) :disabled="$wire.values.is_officer !== 'Yes'" @endif
                                class="inline-flex w-full items-center justify-center gap-1.5 min-h-11 py-2 text-center leading-snug rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-9 sm:py-1.5 sm:w-auto [@media(pointer:coarse)]:min-h-11">
                            <i class="bx bx-send text-base" aria-hidden="true"></i>
                            <span wire:loading.remove wire:target="submit">{{ $status === TransactionStatus::ActionRequired ? 'Resubmit to the VPSD office' : 'Submit to the VPSD office' }}</span>
                            <span wire:loading wire:target="submit">Submitting…</span>
                        </button>
                    </div>
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

    {{-- Wide screens: the document beside the form, refreshed from the live answers (nothing is saved). --}}
    <aside class="hidden rounded-2xl border border-slate-200/70 bg-white p-4 xl:sticky xl:top-0 xl:block xl:max-h-[calc(100dvh-7rem)] xl:overflow-y-auto"
           aria-label="Document preview"
           x-data="{
                html: '', busy: false, w: 842, h: 1171, scale: 1, seq: 0,
                wide() { return window.matchMedia('(min-width: 1280px)').matches; },
                async refresh() {
                    if (! this.wide()) return;
                    const n = ++this.seq;
                    this.busy = true;
                    const html = await this.$wire.previewDocument();
                    if (n !== this.seq) return;
                    this.html = html;
                    this.busy = false;
                },
                fit() {
                    const cw = this.$refs.box ? this.$refs.box.clientWidth : 0;
                    if (cw > 0) this.scale = Math.min(1, cw / this.w);
                },
                measure(e) {
                    if (this.$refs.frame && e.source === this.$refs.frame.contentWindow && e.data && e.data.documatePreviewHeight) {
                        this.h = Math.ceil(e.data.documatePreviewHeight);
                    }
                }
           }"
           x-init="fit(); new ResizeObserver(() => fit()).observe($refs.box); refresh()"
           @doc-changed.window.debounce.900ms="refresh()"
           @resize.window.debounce.300ms="html ? fit() : refresh()"
           @message.window="measure($event)">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-slate-900">Preview</h2>
                <p class="text-xs text-slate-500">
                    {{ $editable ? 'Updates as you type. Nothing is saved by previewing.' : 'The document as it stands now.' }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span x-show="busy && html" x-cloak class="inline-flex items-center gap-1 text-xs text-slate-500" role="status">
                    <i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i> Updating
                </span>
                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500">
                    <i class="bx bxs-file-pdf text-sm" aria-hidden="true"></i> A4 PDF
                </span>
            </div>
        </div>
        <div x-ref="box" wire:ignore class="overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1]" :style="`height:${h * scale}px`">
            <div x-show="! html" class="flex h-64 items-center justify-center gap-2 text-sm text-slate-600" role="status">
                <i class="bx bx-loader-alt bx-spin text-xl" aria-hidden="true"></i> Preparing preview…
            </div>
            <iframe x-ref="frame" x-show="html" :srcdoc="html" title="Document preview"
                    class="block border-0 bg-[#e7e5e1]"
                    :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
        </div>
    </aside>
</div>
