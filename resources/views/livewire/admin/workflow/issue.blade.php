@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;
    use App\Services\Workflow\FormSupport as FS;

    $document = $this->document;
    $student = $this->selectedStudent;
    $staffIssued = $document->issuerIsStaff();
    $me = auth()->user();
    [$backUrl, $backLabel] = match (true) {
        ! $staffIssued => [route('admin.workflow.index'), 'Workflow review'],
        $me->isSignatoryAccount() => [route('signatory.home'), 'Home'],
        default => [route('signatory.inbox'), 'Signing inbox'],
    };

    // Who the report is issued under: the issuing role when the document names one, else the person's first role.
    $issuerRole = $staffIssued
        ? ($me->signatoryAssignments()->current()->with('role')->get()
            ->sortByDesc(fn ($a) => ($a->role->key ?? null) === $document->issuedByRole())
            ->first()?->role?->label ?? 'Signatory')
        : 'Office of the Vice President for Student Development';
    $kindWord = $staffIssued ? 'report' : 'document';
    // Formal parts, numbered in the order they appear (some are only there for some documents).
    $partNo = 0;
    $part = function () use (&$partNo) { return ['I', 'II', 'III', 'IV', 'V', 'VI'][$partNo++] ?? (string) $partNo; };

    // Filled in on the form itself (<x-doc.sheet>): blank lines with their captions under them, as on paper.
    $D = \App\Support\DocSheet::class;
    $box = 'mt-1 ' . $D::LINE;
    $bare = $D::INPUT;
    // Used by the report-specific layouts (issue-paper/*) for their own inputs and textareas.
    $input = $D::FIELD;
    $caption = $D::CAPTION;
    $help = $D::HINT;
    $err = $D::ERROR;
    $partHead = 'flex items-baseline gap-2 ' . $D::HEADING;

    $paperMap = [
        'model_student_rating_signatory' => 'rating',
        'ymca_award_rating_signatory' => 'rating',
        'college_model_nomination_signatory' => 'nomination',
        'ymca_award_nomination_signatory' => 'nomination',
        'disciplinary_referral_slip' => 'referral',
        'disciplinary_action_slip' => 'action-slip',
        'student_advisement_form' => 'advisement',
        'student_assistantship_recommendation' => 'recommendation',
        'student_assistant_evaluation' => 'evaluation',
        'disciplinary_case_report' => 'case-report',
        'college_model_interview_rating' => 'interview-rating',
    ];
    $paper = $paperMap[$this->type] ?? null;
    $hasPaper = $paper && view()->exists("livewire.workflow.issue-paper.$paper");

    // Short answers share a row on wider screens; longer ones take the whole width.
    $isShort = fn (string $name, array $f): bool => empty($f['salutation']) && (
        in_array($f['type'] ?? 'text', ['date', 'time', 'select', 'number'], true)
        || (($f['type'] ?? 'text') === 'text' && ($f['max'] ?? 255) <= 150)
    );
@endphp

<div class="mx-auto w-full max-w-3xl space-y-4 sm:space-y-5">

    {{-- Header. "PDF" opens the exact printed copy from the live answers (nothing is saved). --}}
    <div x-data="{
            open: false, loading: false, html: '', w: 842, h: 1171, scale: 1, wrapEl: null, frameEl: null,
            async show() {
                this.open = true; this.loading = true;
                this.html = await this.$wire.previewDocument();
                this.loading = false;
                this.$nextTick(() => this.fit());
            },
            fit() { const cw = this.wrapEl ? this.wrapEl.clientWidth : 0; if (cw > 0) this.scale = Math.min(1, cw / this.w); },
            measure(e) {
                if (this.frameEl && e.source === this.frameEl.contentWindow && e.data && e.data.documatePreviewHeight) {
                    this.h = Math.ceil(e.data.documatePreviewHeight);
                }
            }
         }"
         @message.window="measure($event)" @resize.window="fit()">
        <div class="flex items-center justify-between gap-3">
            @if ($showPicker)
                {{-- The title is the picker: the admin switches the document from here. Switching clears the answers,
                     so it asks first when some are filled in. --}}
                @php $filled = collect($this->values)->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->isNotEmpty() || $studentId; @endphp
                <h1 class="min-w-0" x-data="{ prev: @js($type) }">
                    <label for="issue-doc" class="sr-only">Document to issue</label>
                    <select id="issue-doc" wire:key="issue-doc-{{ $type }}" title="Choose the document to issue"
                            @change="if (@js((bool) $filled) && ! confirm('Switch documents? The answers you have filled in will be cleared.')) { $el.value = prev } else { prev = $el.value; $wire.set('type', $el.value) }"
                            class="block max-w-full cursor-pointer truncate rounded-lg border-0 bg-transparent py-1 pl-1.5 pr-9 -ml-1.5 text-lg font-semibold tracking-[-0.015em] text-slate-900 shadow-none transition-colors hover:bg-slate-100 focus:ring-2 focus:ring-[#2A57B4]/30 sm:text-xl">
                        @foreach ($this->documentChoices as $key => [$docName, $docDesc])
                            <option value="{{ $key }}" @selected($key === $type) class="text-base font-normal">{{ $docName }}</option>
                        @endforeach
                    </select>
                </h1>
            @else
                <h1 class="min-w-0 truncate text-lg font-semibold tracking-[-0.015em] text-slate-900 sm:text-xl">{{ $document->name() }}</h1>
            @endif
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="$dispatch('open-intro')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
                        aria-label="Before you start: what to check before issuing" title="Before you start">
                    <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                </button>
                <button type="button" @click="show()" title="See the printed copy"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11">
                    <i class="bx bxs-file-pdf text-base text-rose-600" aria-hidden="true"></i> PDF<span class="sr-only"> preview of the printed copy</span>
                </button>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="open" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[80] flex flex-col bg-slate-900/50 p-0 sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="issue-preview-title"
                 @keydown.escape.window="open = false">
                <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl" @click.outside="open = false">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="issue-preview-title" class="text-sm font-semibold text-slate-900">Printed copy</h2>
                            <p class="text-xs text-slate-500">With your answers so far. Nothing is saved.</p>
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
                            <iframe x-init="frameEl = $el" :srcdoc="html" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                                    :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Error summary: every problem in one place, each linking to its field. --}}
    @if ($errors->any())
        <div role="alert" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())"
             class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400">
            <p class="flex items-center gap-2 font-semibold">
                <i class="bx bx-error-circle text-lg" aria-hidden="true"></i>
                {{ $errors->count() === 1 ? 'Fix 1 thing to issue' : 'Fix ' . $errors->count() . ' things to issue' }}
            </p>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-9">
                @foreach ($errors->getMessages() as $key => $messages)
                    @php
                        $target = match (true) {
                            str_starts_with($key, 'values.') => 'f-' . explode('.', substr($key, 7))[0],
                            $key === 'studentId' => 'f-student',
                            $key === 'sourceId' => 'f-source',
                            $key === 'confirmed' => 'f-confirmed',
                            default => null,
                        };
                    @endphp
                    <li>
                        @if ($target)
                            <a href="#{{ $target }}" class="underline decoration-rose-300 underline-offset-2 hover:decoration-rose-600"
                               @click.prevent="$dispatch('fill-open', { name: @js(str_starts_with($key, 'values.') ? explode('.', substr($key, 7))[0] : null) }); $nextTick(() => { const t = document.getElementById('{{ $target }}'); if (t && t.offsetParent && ! t.closest('[data-editor].fixed')) { t.scrollIntoView({ block: 'center' }); t.focus({ preventScroll: true }); } })">{{ $messages[0] }}</a>
                        @else
                            {{ $messages[0] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Which document to issue: signatories pick it before coming here (Explore / Home, ?doc=…); the admin picks it
         from the title at the top of the page. --}}

    @php
        // Filled in on the printed page (resources/js/pdf-fill.js): the blanks the PDF prints, and which editor an error belongs to.
        $printed = \App\Support\PdfFill::printed($document);
        $editorFor = function (string $key) use ($document): ?string {
            if (! str_starts_with($key, 'values.')) {
                return null;
            }
            $n = explode('.', substr($key, 7))[0];

            return ($document->fields()[$n]['salutation_of'] ?? null) ?: $n;
        };
        $errorEditors = collect($errors->keys())->map($editorFor)->filter()->unique()->values()->all();
    @endphp

    <form wire:submit="issue" novalidate wire:confirm="Certify and issue this {{ $kindWord }}? It is recorded under your name and sent for signing."
          class="space-y-4"
          x-data="pdfFill({ editable: true, placed: @js($printed), deps: @js(\App\Support\PdfFill::deps($document)) })"
          @input.debounce.500ms="refresh()" @change.debounce.250ms="refresh()"
          @fill-open.window="$event.detail.name && placed[$event.detail.name] && open($event.detail.name)"
          aria-labelledby="issue-form-title">
        <template data-errors="{{ json_encode($errorEditors) }}"></template>
        <h2 id="issue-form-title" class="sr-only">Fill in the {{ $kindWord }}: click a blank on the page</h2>

        {{-- Before you start: what this document is and how to fill it in. Opens with the page (reopen it from the info button). --}}
        @include('livewire.workflow._intro-modal', [
            'storageKey' => 'issue.' . $document->key(),
            'title' => $document->name(),
            'lead' => $document->description(),
            'items' => array_values(array_filter([
                $document->issuedToSelf() ? null : 'Pick the student first: their details print on the form.',
                'Click a blue blank on the form to fill it in.',
                ($document->issuedBySignatory() || $staffIssued) ? 'Your profile e-signature is applied for you.' : null,
                $staffIssued ? 'The student is notified once you issue it.' : 'It goes straight to signing; the student is notified.',
                'Nothing is saved until you issue it.',
            ])),
            'note' => $document->issueNote(),
            'startLabel' => 'Start',
        ])

        {{-- Student concerned: picked first, then printed on the form --}}
        @unless ($document->issuedToSelf())
            <section class="rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200/80" aria-labelledby="sec-student">
                <h3 id="sec-student" class="sr-only">Student concerned</h3>
                <div class="group/f relative" x-data @keydown.escape="$wire.set('studentSearch', '')">
                    @if ($student)
                        <div class="flex items-center justify-between gap-3">
                            <p class="min-w-0 truncate text-sm">
                                <span class="font-semibold text-slate-900">{{ $student->full_name }}</span>
                                <span class="text-slate-500">· {{ collect([$student->student_number, $student->program?->name])->filter()->implode(' · ') }}</span>
                            </p>
                            <button type="button" id="f-student" wire:click="$set('studentId', null)"
                                    class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-lg px-2 text-xs font-medium text-[#2A57B4] hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]"
                                    aria-label="Change student: remove {{ $student->full_name }}">
                                <i class="bx bx-transfer-alt" aria-hidden="true"></i> Change
                            </button>
                        </div>
                    @else
                        <div class="{{ $box }} !mt-0 {{ $errors->has('studentId') ? $D::ERROR_LINE : '' }}">
                            <i class="bx bx-search text-base text-slate-400" aria-hidden="true"></i>
                            <input id="f-student" type="search" wire:model.live.debounce.300ms="studentSearch" autocomplete="off" @keydown.enter.prevent
                                   placeholder="Student: type a name, email or student number" class="{{ $bare }}"
                                   aria-required="true" aria-label="Student concerned" aria-describedby="@error('studentId') e-student @enderror" @error('studentId') aria-invalid="true" @enderror
                                   @keydown.arrow-down.prevent="$el.closest('.relative').querySelector('[data-pick]')?.focus()">
                        </div>

                        @if (strlen($studentSearch) >= 2)
                            <div class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg" role="list" aria-label="Matching students"
                                 @keydown.arrow-down.prevent="$event.target.nextElementSibling?.focus()"
                                 @keydown.arrow-up.prevent="($event.target.previousElementSibling ?? document.getElementById('f-student')).focus()">
                                @forelse ($this->studentResults as $s)
                                    <button type="button" data-pick wire:click="selectStudent({{ $s->id }})" wire:key="stu-{{ $s->id }}" role="listitem"
                                            class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm hover:bg-slate-50 focus:bg-blue-50 focus:outline-none">
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-slate-900">{{ $s->full_name }}</span>
                                            <span class="block truncate text-xs text-slate-500">{{ $s->program?->name }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $s->student_number }}<span class="sr-only">. Select {{ $s->full_name }}</span></span>
                                    </button>
                                @empty
                                    <p class="px-3.5 py-2.5 text-sm text-slate-500" role="status">No active student matches "{{ $studentSearch }}".</p>
                                @endforelse
                            </div>
                        @endif
                    @endif
                    @error('studentId') <p id="e-student" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                </div>
            </section>
        @endunless

        {{-- Source application (endorsement) --}}
        @if ($document->sourceDocumentType())
            <section class="rounded-xl bg-white px-4 py-4 shadow-sm ring-1 ring-slate-200/80" aria-labelledby="sec-source">
                <h3 id="sec-source" class="{{ $D::HEADING }}">Approved application <span class="text-rose-600" aria-hidden="true">*</span></h3>

                @if (! $student)
                    <p class="mt-2 text-sm italic text-slate-500">Pick a student first.</p>
                @elseif ($this->sourceOptions->isEmpty())
                    <p class="mt-2 text-sm italic text-slate-500">This student has no submitted Student Assistantship Application.</p>
                @else
                    <fieldset class="mt-3 space-y-2" id="f-source" tabindex="-1" @error('sourceId') aria-describedby="e-source" @enderror>
                        <legend class="sr-only">Choose the student's application (its schedule is copied)</legend>
                        @foreach ($this->sourceOptions as $o)
                            @php $sv = $o->field_values ?? []; @endphp
                            <label wire:key="src-{{ $o->workspace_id }}"
                                   class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50/60 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                <input type="radio" name="source_application" value="{{ $o->workspace_id }}" wire:model.live="sourceId" class="mt-1 h-4 w-4 accent-[#2A57B4]">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-slate-600">{{ $o->transaction_code }}</span>
                                        <span class="text-xs text-slate-500">{{ $o->submitted_at?->format('M j, Y') }}</span>
                                        @include('livewire.workflow._badge', ['ws' => $o])
                                        <span class="text-xs font-semibold tabular-nums text-slate-700">{{ SA::fmt(SA::weeklyTotal($sv)) }} hrs/week</span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                @error('sourceId') <p id="e-source" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
            </section>
        @endif

        {{-- The printed page itself: click a blue blank to fill it in. What you see is what prints. --}}
        <div>
            <x-doc.pdf-page :label="$document->name() . ': click a blank to fill it in'" />
        </div>

        {{-- What the page does not print (and each blank's editor): a short list under the page --}}
        <div data-extra :class="hasExtra ? 'rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-200/80 sm:px-6' : ''">
            <h3 x-show="hasExtra" x-cloak class="{{ $D::HEADING }} mb-4">Also needed (not printed)</h3>
            @if (collect($document->fields())->contains(fn ($f) => ($f['type'] ?? '') === 'radio'))
                <p x-show="hasExtra" class="mb-3 text-xs text-slate-500">
                    <strong>Scale:</strong> 5 Outstanding &middot; 4 Very Satisfactory &middot; 3 Satisfactory &middot; 2 Good &middot; 1 Poor
                </p>
            @endif
            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                @foreach ($document->fields() as $name => $f)
                    @continue(! empty($f['hidden']) || ! empty($f['salutation_of']))
                    @continue(($f['group'] ?? null) === 'rating_rows')
                    @php
                        $kind = $f['type'] ?? 'text';
                        $dep = $f['show_when'] ?? null;
                        $xShow = $dep ? '$wire.values.' . array_key_first($dep) . ' === ' . json_encode($dep[array_key_first($dep)]) : null;
                        $required = $f['required'] ?? false;
                        $hasError = $errors->has("values.$name") || (($f['salutation'] ?? null) && $errors->has('values.' . $f['salutation']));
                        $describedBy = trim((! empty($f['help']) ? "h-$name " : '') . ($hasError ? "e-$name" : ''));
                        $titleKey = $f['salutation'] ?? null;
                        $captionHtml = e($f['label']) . ($required ? '<span class="not-italic text-rose-600" aria-hidden="true"> *</span><span class="sr-only">(required)</span>' : '');
                        $wide = ! $isShort($name, $f) || in_array($kind, ['checkbox', 'radio', 'textarea'], true);
                    @endphp
                    <x-doc.pop :name="$name">
                    <div wire:key="f-{{ $this->type }}-{{ $name }}" class="group/f min-w-0 {{ $wide ? 'sm:col-span-2' : '' }}" @if ($xShow) x-show="{{ $xShow }}" x-cloak @endif>
                        @if ($kind === 'checkbox')
                            <label for="f-{{ $name }}" class="{{ $D::OPTION }} -mx-2 items-start">
                                <input id="f-{{ $name }}" type="checkbox" wire:model="values.{{ $name }}" class="mt-1 h-4 w-4 shrink-0 rounded-sm accent-[#2A57B4]">
                                <span>{{ $f['label'] }}</span>
                            </label>
                        @elseif ($kind === 'radio')
                            <fieldset id="f-{{ $name }}" tabindex="-1" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                                <legend class="text-[15px] text-slate-800">{{ $f['label'] }} @if ($required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</legend>
                                <div class="mt-1.5 grid grid-cols-5 gap-1 sm:gap-2 {{ $hasError ? 'rounded-lg bg-rose-50 p-1' : '' }}">
                                    @foreach ($f['options'] as $opt)
                                        <label wire:key="r-{{ $name }}-{{ $opt }}"
                                               class="flex min-h-11 min-w-0 cursor-pointer flex-col items-center justify-center rounded-lg border border-slate-200 px-1 py-2 text-center transition-colors hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-[#2A57B4] has-[:checked]:text-white has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/30">
                                            <input type="radio" name="values_{{ $name }}" value="{{ $opt }}" wire:model="values.{{ $name }}" class="sr-only">
                                            <span class="text-sm font-semibold">{{ $opt }}</span>
                                            @if (! empty($f['option_labels'][$opt]))
                                                <span class="hidden w-full truncate text-[10px] leading-tight opacity-80 sm:block">{{ $f['option_labels'][$opt] }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @else
                            @php $aria = ($required ? 'aria-required="true" ' : '') . ($describedBy ? 'aria-describedby="' . e($describedBy) . '" ' : '') . ($hasError ? 'aria-invalid="true"' : ''); @endphp

                            @if ($titleKey)
                                <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-3 sm:grid-cols-[7rem_minmax(0,1fr)]">
                                    <div class="{{ str_replace('mt-1 ', '', $box) }}">
                                        <x-dm.honorific-input id="f-{{ $titleKey }}" model="values.{{ $titleKey }}" class="{{ $bare }}" />
                                    </div>
                                    <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $hasError ? $D::ERROR_LINE : '' }}">
                                        <input id="f-{{ $name }}" type="text" wire:model="values.{{ $name }}" @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                               placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}" {!! $aria !!}>
                                    </div>
                                    <label for="f-{{ $titleKey }}" class="{{ $caption }} !mt-0">Title</label>
                                    <label for="f-{{ $name }}" class="{{ $caption }} !mt-0">{!! $captionHtml !!}</label>
                                </div>
                            @elseif ($kind === 'textarea')
                                <div x-data="{ n: ($wire.values['{{ $name }}'] || '').length }" class="relative rounded-sm transition-colors focus-within:bg-[#2A57B4]/[0.03] {{ $hasError ? 'bg-rose-50/50' : '' }}">
                                    <textarea id="f-{{ $name }}" wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}" @input="n = $el.value.length"
                                              placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $D::LINED }}" {!! $aria !!}></textarea>
                                    <span class="pointer-events-none absolute -bottom-5 right-0 hidden text-[11px] tabular-nums text-slate-400 group-focus-within/f:block" aria-hidden="true"><span x-text="n"></span>/{{ $f['max'] ?? 500 }}</span>
                                </div>
                                <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                            @elseif ($kind === 'select')
                                <div class="{{ $box }} {{ $hasError ? $D::ERROR_LINE : '' }}">
                                    <select id="f-{{ $name }}" wire:model="values.{{ $name }}" class="{{ $bare }} cursor-pointer pr-8" {!! $aria !!}>
                                        <option value="">Choose…</option>
                                        @foreach ($f['options'] as $opt)
                                            <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                            @else
                                @php $isEmail = str_ends_with($name, '_email'); @endphp
                                <div class="{{ $box }} {{ $hasError ? $D::ERROR_LINE : '' }}">
                                    <input id="f-{{ $name }}" wire:model="values.{{ $name }}"
                                           type="{{ $isEmail ? 'email' : (['date' => 'date', 'time' => 'time'][$kind] ?? 'text') }}"
                                           @if ($kind === 'date')
                                               @if (! empty($f['past'])) max="{{ now()->toDateString() }}" @else min="{{ now()->toDateString() }}" @endif
                                           @endif
                                           @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                           placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}" {!! $aria !!}>
                                </div>
                                <label for="f-{{ $name }}" class="{{ $caption }}">{!! $captionHtml !!}</label>
                            @endif
                        @endif
                        @if (! empty($f['help'])) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif
                        @error("values.$name") <p id="e-{{ $name }}" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                    </div>
                    </x-doc.pop>
                @endforeach
            </div>
        </div>
        {{-- Certify and issue: below the form page --}}
        <div class="py-2">
            <label for="f-confirmed" class="flex cursor-pointer items-start gap-2.5 text-[13px] leading-snug text-slate-700">
                <input id="f-confirmed" type="checkbox" wire:model="confirmed" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]"
                       aria-required="true" aria-describedby="confirmed-text @error('confirmed') e-confirmed @enderror" @error('confirmed') aria-invalid="true" @enderror>
                <span id="confirmed-text" class="{{ $errors->has('confirmed') ? 'text-rose-700' : '' }}">
                    This is accurate, I may issue it, and the student's data is used only for this (Data Privacy Act of 2012).
                </span>
            </label>
            @error('confirmed') <p id="e-confirmed" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
            <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:items-center sm:justify-end">
                <a href="{{ $backUrl }}" wire:navigate
                   class="inline-flex min-h-11 items-center justify-center rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-9 {{ $staffIssued ? '' : 'col-span-1' }}">Cancel</a>
                @if ($staffIssued)
                    <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft"
                            class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 sm:min-h-9">
                        <i class="bx bx-save text-base text-slate-500" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="saveDraft">{{ $draftId ? 'Update draft' : 'Save draft' }}</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </button>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="issue"
                        class="{{ $staffIssued ? 'col-span-2' : '' }} inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 sm:min-h-9">
                    <i class="bx bx-check-shield text-base" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="issue">Certify and issue</span>
                    <span wire:loading wire:target="issue">Issuing…</span>
                </button>
            </div>
        </div>
    </form>
</div>
