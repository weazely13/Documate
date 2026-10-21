@php
    use App\Services\Workflow\FormSupport as FS;
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
    $cb = 'h-4 w-4 shrink-0 accent-[#2A57B4]';
    $rows = [
        "Student's name" => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'I.D. number' => $student ? FS::systemValue($student, 'student_number') : '',
        'Program, year and section' => $student ? FS::systemValue($student, 'program_year_section') : '',
    ];
@endphp
<div class="space-y-5">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <label class="block text-sm font-medium text-slate-700">Violation <span class="text-rose-500">*</span></label>
        <textarea wire:model="values.violation" rows="2" maxlength="400" class="{{ $input }}"></textarea>
        @error('values.violation') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Incident report <span class="text-rose-500">*</span></label>
        <textarea wire:model="values.incident_report" rows="5" maxlength="2000" class="{{ $input }}"></textarea>
        @error('values.incident_report') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Resolution <span class="text-rose-500">*</span></label>
        <textarea wire:model="values.resolution" rows="5" maxlength="2000" class="{{ $input }}"></textarea>
        @error('values.resolution') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-sm font-semibold text-slate-900">Recommendations <span class="text-rose-500">*</span></p>
        <p class="text-xs text-slate-400">Tick at least one.</p>
        <div class="mt-3 space-y-3 text-sm text-slate-800">
            <label class="flex items-center gap-3"><input type="checkbox" wire:model="values.rec_closed" class="{{ $cb }}"> Closed/Resolved</label>
            <div>
                <label class="flex items-center gap-3"><input type="checkbox" wire:model.live="values.rec_hearing" class="{{ $cb }}"> For continuing hearing</label>
                <div x-show="$wire.values.rec_hearing" x-cloak class="mt-2 pl-7">
                    <label class="block text-xs font-medium text-slate-600">Hearing date <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="values.hearing_date" min="{{ now()->toDateString() }}" class="{{ $input }} sm:max-w-xs">
                    @error('values.hearing_date') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-3"><input type="checkbox" wire:model="values.rec_formal" class="{{ $cb }}"> For formal investigation</label>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-sm font-semibold text-slate-900">Committee members <span class="text-rose-500">*</span></p>
        <p class="text-xs text-slate-400">After you sign, each member gets a secure signing link by email. Add at least one. Leave unused rows blank.</p>
        <div class="mt-3 space-y-3">
            @for ($i = 1; $i <= 5; $i++)
                <div wire:key="member-{{ $i }}">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input type="text" wire:model="values.member_{{ $i }}_name" maxlength="120" placeholder="Member {{ $i }} name" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100">
                        <input type="text" wire:model="values.member_{{ $i }}_email" maxlength="150" placeholder="Member {{ $i }} email" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100">
                    </div>
                    @error("values.member_{$i}_name") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                    @error("values.member_{$i}_email") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                </div>
            @endfor
        </div>
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the Chairman (Report Given By), signed with your profile signature.'])
</div>