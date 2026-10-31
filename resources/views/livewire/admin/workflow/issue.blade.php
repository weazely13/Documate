@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;
    use App\Services\Workflow\FormSupport as FS;

    $document = $this->document;
    $student = $this->selectedStudent;
    $staffIssued = $document->issuerIsStaff();
    $backUrl = $staffIssued ? route('signatory.inbox') : route('admin.workflow.index');
    $backLabel = $staffIssued ? 'Signing inbox' : 'Workflow review';

    // Same field styling as the student transaction form (DESIGN.md: 8px inputs, brand focus ring).
    $box = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3.5 shadow-sm transition focus-within:border-[#2A57B4] focus-within:ring-[3px] focus-within:ring-[#2A57B4]/20';
    $boxLocked = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5';
    $bare = 'w-full min-w-0 border-0 bg-transparent p-0 py-3 text-base text-slate-900 placeholder-slate-500 outline-none focus:ring-0 sm:py-2.5 sm:text-sm';
    // Used by the report-specific layouts (issue-paper/*) for their own inputs.
    $input = 'mt-1 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-base text-slate-900 shadow-sm outline-none placeholder-slate-500 focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20 sm:py-2.5 sm:text-sm';
    $label = 'block text-[13px] font-medium text-slate-700';
    $help = 'mt-1 text-xs leading-relaxed text-slate-500';
    $err = 'mt-1 flex items-start gap-1 text-xs font-medium text-rose-600';

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

    $fieldIcon = fn (string $name, array $f) => $f['icon'] ?? match (true) {
        ($f['type'] ?? '') === 'date' => 'bx-calendar',
        ($f['type'] ?? '') === 'time' => 'bx-time-five',
        str_ends_with($name, '_email') => 'bx-envelope',
        str_ends_with($name, '_name') => 'bx-user',
        str_contains($name, 'venue') || str_contains($name, 'place') => 'bx-map',
        default => null,
    };
@endphp

<div class="mx-auto w-full max-w-2xl xl:grid xl:max-w-[1440px] xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:items-start xl:gap-6">
<div class="min-w-0 space-y-5">

    {{-- Header --}}
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
        <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-1 text-sm text-slate-500">
                <li><a href="{{ $backUrl }}" wire:navigate class="rounded font-medium hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">{{ $backLabel }}</a></li>
                <li aria-hidden="true"><i class="bx bx-chevron-right text-base text-slate-400"></i></li>
                <li class="font-medium text-slate-700" aria-current="page">{{ $staffIssued ? 'Issue a report' : 'Issue a document' }}</li>
            </ol>
        </nav>

        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-x-4 sm:gap-y-2">
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $staffIssued ? 'Issue a report to a student' : 'Issue a document to a student' }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $document->issueNote() ?? ($staffIssued
                        ? 'The student is notified right away and can read it from their dashboard.'
                        : 'You fill it in, it goes straight to the signatory, and the student is notified. Nothing is sent to the student for editing.') }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                    <button type="button" @click="$dispatch('open-intro')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
                            aria-label="Before you start: what to check before issuing" title="Before you start">
                        <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                    </button>
            <button type="button" @click="show()"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 xl:hidden [@media(pointer:coarse)]:min-h-11">
                <i class="bx bx-show text-base text-slate-500" aria-hidden="true"></i> Preview document
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
                            <h2 id="issue-preview-title" class="text-sm font-semibold text-slate-900">Document preview</h2>
                            <p class="text-xs text-slate-500">Shows your current answers. Nothing is saved or issued by previewing.</p>
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
                {{ $errors->count() === 1 ? 'Please fix 1 problem before issuing' : 'Please fix ' . $errors->count() . ' problems before issuing' }}
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
                               @click.prevent="const t = document.getElementById('{{ $target }}') || document.querySelector('[wire\\:model$=\'{{ substr($target, 2) }}\']'); if (t) { t.scrollIntoView({ block: 'center' }); t.focus({ preventScroll: true }); }">{{ $messages[0] }}</a>
                        @else
                            {{ $messages[0] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="issue" novalidate wire:confirm="Issue this document and send it for signing?"
          class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm"
          x-data @input="$dispatch('doc-changed')" @change="$dispatch('doc-changed')" aria-labelledby="issue-form-title">
        <h2 id="issue-form-title" class="sr-only">Fill in the report</h2>

        {{-- Before you start: a popup that opens with the page (reopen it from the info button). --}}
        @include('livewire.workflow._intro-modal', [
            'storageKey' => 'issue.' . ($staffIssued ? 'staff' : 'office'),
            'lead' => $staffIssued ? 'Issuing a report to a student.' : 'Issuing a document to a student.',
            'items' => array_values(array_filter([
                'Fields marked * are required. Greyed-out fields come from the student\'s account and cannot be typed over.',
                $document->issuedToSelf() ? null : 'Search for the student by name, email or student number, then pick them from the list.',
                ($document->issuedBySignatory() || $staffIssued) ? 'Your profile e-signature is applied for you. Add it in your profile first if it is not set.' : null,
                'The preview shows the printed form as you type. Nothing is saved until you issue it.',
                'Issuing asks you to confirm the details are accurate and that you are authorized to issue it.',
            ])),
            'startLabel' => 'Start the report',
        ])

        {{-- 1. Document --}}
        <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="sec-doc">
            <h3 id="sec-doc" class="text-sm font-semibold text-slate-900">Document</h3>
            @if (count($documents) > 1)
                <label for="f-type" class="{{ $label }} mt-3">Which document are you issuing? <span class="text-rose-600" aria-hidden="true">*</span></label>
                <div class="{{ $box }}">
                    <i class="bx bx-file text-base text-slate-400" aria-hidden="true"></i>
                    <select id="f-type" wire:model.live="type" class="{{ $bare }} cursor-pointer" aria-describedby="h-type">
                        @foreach ($documents as $key => $doc)
                            <option value="{{ $key }}">{{ $doc->name() }}</option>
                        @endforeach
                    </select>
                </div>
                <p id="h-type" class="{{ $help }}">{{ $document->description() }}</p>
            @else
                <p class="mt-1 text-sm font-medium text-slate-900">{{ $document->name() }}</p>
                <p class="{{ $help }}">{{ $document->description() }}</p>
            @endif
        </section>

        {{-- 2. Student --}}
        @unless ($document->issuedToSelf())
            <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="sec-student">
                <h3 id="sec-student" class="text-sm font-semibold text-slate-900">Student information</h3>
                <p class="{{ $help }}">Pick the student. Their details are copied from their account and printed on the form.</p>

                <div class="relative mt-3" x-data @keydown.escape="$wire.set('studentSearch', '')">
                    <label for="f-student" class="{{ $label }}">Student <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>

                    @if ($student)
                        <div class="mt-1 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $student->full_name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ collect([$student->student_number, $student->program?->name])->filter()->implode(' · ') }}</p>
                            </div>
                            <button type="button" id="f-student" wire:click="$set('studentId', null)"
                                    class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30"
                                    aria-label="Change student: remove {{ $student->full_name }}">
                                <i class="bx bx-transfer-alt" aria-hidden="true"></i> Change student
                            </button>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach (['formatted_full_name' => 'Name', 'student_number' => 'Student number', 'program_year_section' => 'Program, year and section', 'college' => 'College'] as $key => $sysLabel)
                                @php $sysVal = FS::systemValue($student, $key); @endphp
                                <div wire:key="sys-{{ $key }}">
                                    <label for="sys-{{ $key }}" class="{{ $label }}">{{ $sysLabel }}</label>
                                    <div class="{{ $boxLocked }}">
                                        <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                                        <input id="sys-{{ $key }}" type="text" value="{{ $sysVal }}" disabled placeholder="Not on the student's account"
                                               class="{{ $bare }} cursor-not-allowed text-slate-700">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="{{ $box }} {{ $errors->has('studentId') ? 'border-rose-400' : '' }}">
                            <i class="bx bx-search text-base text-slate-400" aria-hidden="true"></i>
                            <input id="f-student" type="search" wire:model.live.debounce.300ms="studentSearch" autocomplete="off" @keydown.enter.prevent
                                   placeholder="Search by name, email or student number" class="{{ $bare }}"
                                   aria-required="true" aria-describedby="h-student @error('studentId') e-student @enderror" @error('studentId') aria-invalid="true" @enderror
                                   @keydown.arrow-down.prevent="$el.closest('.relative').querySelector('[data-pick]')?.focus()">
                        </div>
                        <p id="h-student" class="{{ $help }}">Type at least 2 characters, then pick the student from the list.</p>

                        @if (strlen($studentSearch) >= 2)
                            <div class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg" role="list" aria-label="Matching students"
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

        {{-- 3. Source application (endorsement) --}}
        @if ($document->sourceDocumentType())
            <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="sec-source">
                <h3 id="sec-source" class="text-sm font-semibold text-slate-900">Approved application <span class="text-rose-600" aria-hidden="true">*</span></h3>
                <p class="{{ $help }}">The schedule on the endorsement is copied from the application you pick.</p>

                @if (! $student)
                    <p class="mt-3 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">Pick a student first.</p>
                @elseif ($this->sourceOptions->isEmpty())
                    <p class="mt-3 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">This student has no submitted Student Assistantship Application.</p>
                @else
                    <fieldset class="mt-3 space-y-2" id="f-source" tabindex="-1" @error('sourceId') aria-describedby="e-source" @enderror>
                        <legend class="sr-only">Choose the student's application</legend>
                        @foreach ($this->sourceOptions as $o)
                            @php $sv = $o->field_values ?? []; @endphp
                            <label wire:key="src-{{ $o->workspace_id }}"
                                   class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-300 p-3 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                <input type="radio" name="source_application" value="{{ $o->workspace_id }}" wire:model.live="sourceId" class="mt-1 h-4 w-4 accent-[#2A57B4]">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-slate-600">{{ $o->transaction_code }}</span>
                                        <span class="text-xs text-slate-500">submitted {{ $o->submitted_at?->format('M j, Y') }}</span>
                                        @include('livewire.workflow._badge', ['status' => $o->workflow_status])
                                        <span class="text-xs font-semibold tabular-nums text-slate-700">{{ SA::fmt(SA::weeklyTotal($sv)) }} hrs/week</span>
                                    </span>
                                    <span class="mt-1.5 block space-y-0.5 text-xs text-slate-600">
                                        @foreach (SA::DAYS as $dk => [$dl])
                                            @php $am = SA::range($sv, $dk, 'am'); $pm = SA::range($sv, $dk, 'pm'); @endphp
                                            @if ($am || $pm)
                                                <span class="block"><strong>{{ $dl }}:</strong> {{ collect([$am, $pm])->filter()->implode(' · ') }}</span>
                                            @endif
                                        @endforeach
                                    </span>
                                    <a href="{{ route('admin.workflow.show', $o->workspace_id) }}" target="_blank" rel="noopener"
                                       class="mt-1 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Open application<span class="sr-only"> (opens in a new tab)</span></a>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                @error('sourceId') <p id="e-source" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
            </section>
        @endif

        {{-- 4. Report details --}}
        <section class="px-4 py-5 sm:px-5" aria-labelledby="sec-details">
            <h3 id="sec-details" class="text-sm font-semibold text-slate-900">Report details</h3>
            <p class="{{ $help }}">Answer each item as it should appear on the printed form.</p>

            @if ($hasPaper)
                <div class="mt-4" wire:key="paper-{{ $this->type }}">
                    @include("livewire.workflow.issue-paper.$paper", ['student' => $student, 'doc' => $document, 'input' => $input])
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @if (collect($document->fields())->contains(fn ($f) => ($f['type'] ?? '') === 'radio'))
                        <p class="rounded-lg bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600">
                            <strong>Scale:</strong> 5 Outstanding &middot; 4 Very Satisfactory &middot; 3 Satisfactory &middot; 2 Good &middot; 1 Poor
                        </p>
                    @endif
                    @foreach ($document->fields() as $name => $f)
                        @continue(! empty($f['hidden']) || ! empty($f['salutation_of']))
                        @continue(($f['group'] ?? null) === 'rating_rows')
                        @php
                            $kind = $f['type'] ?? 'text';
                            $dep = $f['show_when'] ?? null;
                            $xShow = $dep ? '$wire.values.' . array_key_first($dep) . ' === ' . json_encode($dep[array_key_first($dep)]) : null;
                            $required = $f['required'] ?? false;
                            $hasError = $errors->has("values.$name");
                            $describedBy = trim((! empty($f['help']) ? "h-$name " : '') . ($hasError ? "e-$name" : ''));
                            $icon = $fieldIcon($name, $f);
                            $titleKey = $f['salutation'] ?? null;
                        @endphp
                        <div wire:key="f-{{ $this->type }}-{{ $name }}" @if ($xShow) x-show="{{ $xShow }}" x-cloak @endif>
                            @if ($kind === 'checkbox')
                                <label for="f-{{ $name }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm text-slate-700 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50/50">
                                    <input id="f-{{ $name }}" type="checkbox" wire:model="values.{{ $name }}" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]">
                                    <span class="font-medium">{{ $f['label'] }}</span>
                                </label>
                            @elseif ($kind === 'radio')
                                <fieldset id="f-{{ $name }}" tabindex="-1" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                                    <legend class="{{ $label }}">{{ $f['label'] }} @if ($required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</legend>
                                    <div class="mt-1.5 grid grid-cols-5 gap-1 sm:gap-2">
                                        @foreach ($f['options'] as $opt)
                                            <label wire:key="r-{{ $name }}-{{ $opt }}"
                                                   class="flex min-h-11 min-w-0 cursor-pointer flex-col items-center justify-center rounded-lg border border-slate-300 px-1 py-2 text-center hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                                <input type="radio" name="values_{{ $name }}" value="{{ $opt }}" wire:model="values.{{ $name }}" class="sr-only">
                                                <span class="text-sm font-semibold text-slate-800">{{ $opt }}</span>
                                                @if (! empty($f['option_labels'][$opt]))
                                                    <span class="hidden w-full truncate text-[10px] leading-tight text-slate-500 sm:block">{{ $f['option_labels'][$opt] }}</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @else
                                <label for="f-{{ $name }}" class="{{ $label }}">
                                    {{ $f['label'] }} @if ($required)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                                </label>
                                @php $aria = ($required ? 'aria-required="true" ' : '') . ($describedBy ? 'aria-describedby="' . e($describedBy) . '" ' : '') . ($hasError ? 'aria-invalid="true"' : ''); @endphp

                                @if ($titleKey)
                                    @php $titleField = $document->fields()[$titleKey] ?? []; @endphp
                                    <div class="mt-1 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2">
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} pr-2">
                                            <label for="f-{{ $titleKey }}" class="sr-only">Title</label>
                                            <select id="f-{{ $titleKey }}" wire:model="values.{{ $titleKey }}" class="{{ $bare }} cursor-pointer">
                                                <option value="">Title</option>
                                                @foreach ($titleField['options'] ?? [] as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="{{ str_replace('mt-1 ', '', $box) }} {{ $hasError ? 'border-rose-400' : '' }}">
                                            @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                            <input id="f-{{ $name }}" type="text" wire:model="values.{{ $name }}" @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                                   placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}" {!! $aria !!}>
                                        </div>
                                    </div>
                                @elseif ($kind === 'textarea')
                                    <div x-data="{ n: ($wire.values['{{ $name }}'] || '').length }" class="{{ $box }} items-start {{ $hasError ? 'border-rose-400' : '' }}">
                                        <textarea id="f-{{ $name }}" wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}" @input="n = $el.value.length"
                                                  placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }} resize-y" {!! $aria !!}></textarea>
                                        <span class="pointer-events-none absolute bottom-1.5 right-3 text-[11px] tabular-nums text-slate-500" aria-hidden="true"><span x-text="n"></span>/{{ $f['max'] ?? 500 }}</span>
                                    </div>
                                @elseif ($kind === 'select')
                                    <div class="{{ $box }} {{ $hasError ? 'border-rose-400' : '' }}">
                                        @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                        <select id="f-{{ $name }}" wire:model="values.{{ $name }}" class="{{ $bare }} cursor-pointer" {!! $aria !!}>
                                            <option value="">Choose…</option>
                                            @foreach ($f['options'] as $opt)
                                                <option value="{{ $opt }}">{{ $f['option_labels'][$opt] ?? $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    @php $isEmail = str_ends_with($name, '_email'); @endphp
                                    <div class="{{ $box }} {{ $hasError ? 'border-rose-400' : '' }}">
                                        @if ($icon)<i class="bx {{ $icon }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                        <input id="f-{{ $name }}" wire:model="values.{{ $name }}"
                                               type="{{ $isEmail ? 'email' : (['date' => 'date', 'time' => 'time'][$kind] ?? 'text') }}"
                                               @if ($kind === 'date')
                                                   @if (! empty($f['past'])) max="{{ now()->toDateString() }}" @else min="{{ now()->toDateString() }}" @endif
                                               @endif
                                               @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                               placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $bare }}" {!! $aria !!}>
                                    </div>
                                @endif
                            @endif
                            @if (! empty($f['help'])) <p id="h-{{ $name }}" class="{{ $help }}">{{ $f['help'] }}</p> @endif
                            @error("values.$name") <p id="e-{{ $name }}" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Confirmation and actions --}}
        <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-5">
            <label for="f-confirmed" class="flex cursor-pointer items-start gap-3 rounded-xl border {{ $errors->has('confirmed') ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50/70' }} px-3.5 py-3 text-sm text-slate-700">
                <input id="f-confirmed" type="checkbox" wire:model="confirmed" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]"
                       aria-required="true" aria-describedby="confirmed-text @error('confirmed') e-confirmed @enderror" @error('confirmed') aria-invalid="true" @enderror>
                <span id="confirmed-text">
                    <span class="font-semibold text-slate-900">I confirm.</span>
                    The details are accurate, I am authorized to issue this {{ $staffIssued ? 'report' : 'document' }}, and the student's personal data is used only for this purpose under the Data Privacy Act of 2012.
                </span>
            </label>
            @error('confirmed') <p id="e-confirmed" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

            <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                <a href="{{ $backUrl }}" wire:navigate
                   class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-9 sm:w-auto sm:py-1.5 [@media(pointer:coarse)]:min-h-11">Cancel and go back</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="issue"
                        class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 py-2 text-center text-sm font-medium leading-snug text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:opacity-50 sm:min-h-9 sm:w-auto sm:py-1.5 [@media(pointer:coarse)]:min-h-11">
                    <i class="bx bx-send text-base" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="issue">Issue and send for signing</span>
                    <span wire:loading wire:target="issue">Issuing…</span>
                </button>
            </div>
        </div>
    </form>
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
                fit() { const cw = this.$refs.box ? this.$refs.box.clientWidth : 0; if (cw > 0) this.scale = Math.min(1, cw / this.w); },
                measure(e) {
                    if (this.$refs.frame && e.source === this.$refs.frame.contentWindow && e.data && e.data.documatePreviewHeight) {
                        this.h = Math.ceil(e.data.documatePreviewHeight);
                    }
                }
           }"
           x-init="fit(); new ResizeObserver(() => fit()).observe($refs.box); refresh()"
           @doc-changed.window.debounce.900ms="refresh()"
           @resize.window.debounce.300ms="html ? fit() : refresh()"
           @message.window="measure($event)"
           wire:key="issue-preview">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-slate-900">Preview</h2>
                <p class="text-xs text-slate-500">Updates as you type. Nothing is saved by previewing.</p>
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
            <iframe x-ref="frame" x-show="html" :srcdoc="html" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                    :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
        </div>
    </aside>
</div>
