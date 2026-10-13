@php
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
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

        {{-- Student picker --}}
        <div class="relative mt-5">
            <label class="block text-sm font-medium text-slate-700">Student <span class="text-rose-500">*</span></label>

            @if ($student)
                <div class="mt-1.5 flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm">
                    <span class="min-w-0 truncate">
                        <span class="font-semibold text-slate-900">{{ trim($student->first_name . ' ' . $student->last_name) }}</span>
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
                                <span class="truncate">{{ trim($s->first_name . ' ' . $s->last_name) }}</span>
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

        {{-- The document's fields --}}
        <div class="mt-5 space-y-4">
            @foreach ($this->document->fields() as $name => $f)
                @php $kind = $f['type'] ?? 'text'; @endphp
                <div wire:key="f-{{ $this->type }}-{{ $name }}">
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