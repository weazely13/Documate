@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;
    $input = 'mt-1.5 w-full min-w-0 rounded-xl border border-slate-300 px-3.5 py-3 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100 sm:py-2.5 sm:text-sm';
    $student = $this->selectedStudent;
@endphp

<div class="mx-auto w-full max-w-2xl space-y-5">
    @php
        $staffIssued = $this->document->issuerIsStaff();
        $backUrl = $staffIssued ? route('signatory.inbox') : route('admin.workflow.index');
    @endphp
    <div>
        <a href="{{ $backUrl }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; {{ $staffIssued ? 'Signing Inbox' : 'Workflow Review' }}</a>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $staffIssued ? 'Issue a report to a student' : 'Issue a document to a student' }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">
            {{ $this->document->issueNote() ?? ($staffIssued
                ? 'The student is notified right away and can read it from their dashboard.'
                : 'You fill it in, it goes straight to the signatory, and the student is notified. Nothing is sent to the student for editing.') }}
        </p>
    </div>

    @error('form')
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
    @enderror

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        {{-- Document type (hidden when only one exists) --}}
        @if (count($documents) > 1)
            <label class="block text-sm font-medium text-slate-700">Document</label>
            <select wire:model.live="type" class="{{ $input }}">
                @foreach ($documents as $key => $doc)
                    <option value="{{ $key }}">{{ $doc->name() }}</option>
                @endforeach
            </select>
        @else
            <p class="text-sm font-semibold text-slate-900">{{ $this->document->name() }}</p>
            <p class="text-xs text-slate-500">{{ $this->document->description() }}</p>
        @endif
        @unless ($this->document->issuedToSelf())
            {{-- Student picker --}}
            <div class="relative mt-5">
                <label class="block text-sm font-medium text-slate-700">Student <span class="text-rose-500">*</span></label>

                @if ($student)
                    <div class="mt-1.5 flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm">
                        <span class="min-w-0 truncate">
                            <span class="font-semibold text-slate-900">{{ $student->full_name }}</span>
                            <span class="text-xs text-slate-400">({{ $student->student_number }}) {{ $student->program?->name }}</span>
                        </span>
                        <button type="button" wire:click="$set('studentId', null)" class="shrink-0 text-slate-400 hover:text-slate-700" aria-label="Change student">&#10005;</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="studentSearch" placeholder="Search by name or student number" class="{{ $input }}">

                    @if (strlen($studentSearch) >= 2)
                        <div class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                            @forelse ($this->studentResults as $s)
                                <button type="button" wire:click="selectStudent({{ $s->id }})" wire:key="stu-{{ $s->id }}"
                                        class="flex w-full items-center justify-between gap-2 px-3.5 py-2 text-left text-sm hover:bg-slate-50">
                                    <span class="truncate">{{ $s->full_name }}</span>
                                    <span class="shrink-0 text-xs text-slate-400">{{ $s->student_number }}</span>
                                </button>
                            @empty
                                <p class="px-3.5 py-2 text-sm text-slate-400">No active student found.</p>
                            @endforelse
                        </div>
                    @endif
                @endif
                @error('studentId') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
        @endunless

        @if ($this->document->sourceDocumentType())
            <div class="mt-5">
                <label class="block text-sm font-medium text-slate-700">Approved application <span class="text-rose-500">*</span></label>

                @if (! $student)
                    <p class="mt-1.5 text-xs text-slate-400">Select a student first.</p>
                @elseif ($this->sourceOptions->isEmpty())
                    <p class="mt-1.5 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                        This student has no submitted Student Assistantship Application.
                    </p>
                @else
                    <div class="mt-1.5 space-y-2">
                        @foreach ($this->sourceOptions as $o)
                            @php $sv = $o->field_values ?? []; @endphp
                            <label wire:key="src-{{ $o->workspace_id }}"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 p-3 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                                <input type="radio" name="source_application" value="{{ $o->workspace_id }}"
                                    wire:model.live="sourceId" class="mt-1 h-4 w-4 accent-[#2A57B4]">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-slate-600">{{ $o->transaction_code }}</span>
                                        <span class="text-xs text-slate-400">submitted {{ $o->submitted_at?->format('M j, Y') }}</span>
                                        @include('livewire.workflow._badge', ['status' => $o->workflow_status])
                                        <span class="text-xs font-semibold text-slate-700">{{ SA::fmt(SA::weeklyTotal($sv)) }} hrs/week</span>
                                    </span>
                                    <span class="mt-1.5 block space-y-0.5 text-xs text-slate-600">
                                        @foreach (SA::DAYS as $dk => [$dl])
                                            @php $am = SA::range($sv, $dk, 'am'); $pm = SA::range($sv, $dk, 'pm'); @endphp
                                            @if ($am || $pm)
                                                <span class="block"><strong>{{ $dl }}:</strong> {{ collect([$am, $pm])->filter()->implode(' · ') }}</span>
                                            @endif
                                        @endforeach
                                    </span>
                                    <a href="{{ route('admin.workflow.show', $o->workspace_id) }}" target="_blank"
                                    class="mt-1 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Open application</a>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('sourceId') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- The document's fields --}}

        @php
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
        @endphp

        @if ($paper && view()->exists("livewire.workflow.issue-paper.$paper"))
            <div class="mt-5" wire:key="paper-{{ $this->type }}">
                @include("livewire.workflow.issue-paper.$paper", ['student' => $student, 'doc' => $this->document])
            </div>
        @else
            {{-- ORIGINAL generic field loop (unchanged) --}}
            <div class="mt-5 space-y-4">
                <div class="mt-5 space-y-4">
                    @if (collect($this->document->fields())->contains(fn ($f) => ($f['type'] ?? '') === 'radio'))
                        <p class="rounded-xl bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600 sm:hidden">
                            <strong>Scale:</strong> 5 Outstanding &middot; 4 Very Satisfactory &middot; 3 Satisfactory &middot; 2 Good &middot; 1 Poor
                        </p>
                    @endif
                    @foreach ($this->document->fields() as $name => $f)
                        @continue(! empty($f['hidden']))
                        @continue(($f['group'] ?? null) === 'rating_rows')
                        @php
                            $kind = $f['type'] ?? 'text';
                            $dep = $f['show_when'] ?? null;
                            $xShow = $dep ? '$wire.values.' . array_key_first($dep) . ' === ' . json_encode($dep[array_key_first($dep)]) : null;
                        @endphp
                        <div wire:key="f-{{ $this->type }}-{{ $name }}" @if ($xShow) x-show="{{ $xShow }}" x-cloak @endif>
                            @if ($kind === 'checkbox')
                                <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700">
                                    <input type="checkbox" wire:model="values.{{ $name }}" class="h-4 w-4 accent-[#2A57B4]">
                                    {{ $f['label'] }}
                                </label>
                            @else
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $f['label'] }} @if ($f['required'] ?? false)<span class="text-rose-500">*</span>@endif
                                </label>

                                @if ($kind === 'textarea')
                                    <textarea wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}" class="{{ $input }}"></textarea>
                                @elseif ($kind === 'radio')
                                    <div class="mt-1.5 grid grid-cols-5 gap-1 sm:gap-2">
                                        @foreach ($f['options'] as $opt)
                                            <label wire:key="r-{{ $name }}-{{ $opt }}"
                                                class="flex min-w-0 cursor-pointer flex-col items-center rounded-xl border border-slate-300 px-1 py-2 text-center hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                                                <input type="radio" name="values_{{ $name }}" value="{{ $opt }}" wire:model="values.{{ $name }}"
                                                    class="h-4 w-4 accent-[#2A57B4]">
                                                <span class="mt-1 text-sm font-semibold text-slate-800">{{ $opt }}</span>
                                                @if (! empty($f['option_labels'][$opt]))
                                                    <span class="hidden w-full truncate text-[10px] leading-tight text-slate-500 sm:block">{{ $f['option_labels'][$opt] }}</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($kind === 'select')
                                    <select wire:model="values.{{ $name }}" class="{{ $input }}">
                                        <option value="">Choose…</option>
                                        @foreach ($f['options'] as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input wire:model="values.{{ $name }}"
                                        type="{{ ['date' => 'date', 'time' => 'time'][$kind] ?? 'text' }}"
                                        @if ($kind === 'date')
                                                @if (! empty($f['past'])) max="{{ now()->toDateString() }}"
                                                @else min="{{ now()->toDateString() }}" @endif
                                            @endif
                                        @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                        placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $input }}">
                                @endif

                                @if (! empty($f['help'])) <p class="mt-1 text-xs text-slate-400">{{ $f['help'] }}</p> @endif
                            @endif
                            @error("values.$name") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.workflow.index') }}" wire:navigate
               class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="button" wire:click="issue" wire:loading.attr="disabled"
                    wire:confirm="Issue this document and send it to the signatory?"
                    class="rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50">
                <span wire:loading.remove wire:target="issue">Issue and send for signing</span>
                <span wire:loading wire:target="issue">Issuing…</span>
            </button>
        </div>
    </section>
</div>