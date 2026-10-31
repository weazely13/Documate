@php
    use App\Services\Workflow\FormSupport as FS;
    $input ??= 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20';
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
        <label for="f-violation" class="block text-[13px] font-medium text-slate-700">Violation <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
        <textarea id="f-violation" wire:model="values.violation" rows="2" maxlength="400" class="{{ $input }}"></textarea>
        @error('values.violation') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="f-incident_report" class="block text-[13px] font-medium text-slate-700">Incident report <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
        <textarea id="f-incident_report" wire:model="values.incident_report" rows="5" maxlength="2000" class="{{ $input }}"></textarea>
        @error('values.incident_report') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="f-resolution" class="block text-[13px] font-medium text-slate-700">Resolution <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
        <textarea id="f-resolution" wire:model="values.resolution" rows="5" maxlength="2000" class="{{ $input }}"></textarea>
        @error('values.resolution') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-sm font-semibold text-slate-900">Recommendations <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></p>
        <p class="text-xs text-slate-500">Tick at least one.</p>
        <div class="mt-3 space-y-3 text-sm text-slate-800">
            <label class="flex items-center gap-3"><input type="checkbox" wire:model="values.rec_closed" class="{{ $cb }}"> Closed/Resolved</label>
            <div>
                <label class="flex items-center gap-3"><input type="checkbox" wire:model.live="values.rec_hearing" class="{{ $cb }}"> For continuing hearing</label>
                <div x-show="$wire.values.rec_hearing" x-cloak class="mt-2 pl-7">
                    <label for="f-hearing_date" class="block text-[13px] font-medium text-slate-700">Hearing date <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                    <input id="f-hearing_date" type="date" wire:model="values.hearing_date" min="{{ now()->toDateString() }}" class="{{ $input }} sm:max-w-xs">
                    @error('values.hearing_date') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-3"><input type="checkbox" wire:model="values.rec_formal" class="{{ $cb }}"> For formal investigation</label>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-sm font-semibold text-slate-900">Committee members <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></p>
        <p class="text-xs text-slate-500">After you sign, each member gets a secure signing link by email. Add at least one. Leave unused rows blank.</p>
        <div class="mt-3 space-y-3">
            @for ($i = 1; $i <= 5; $i++)
                <div wire:key="member-{{ $i }}">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label for="f-member_{{ $i }}_name" class="sr-only">Committee member {{ $i }}: title and full name</label>
                        <input id="f-member_{{ $i }}_name" type="text" wire:model="values.member_{{ $i }}_name" maxlength="120" autocomplete="off" placeholder="Member {{ $i }}: title and name, e.g. Dr. Maria Santos" class="bg-white shadow-sm w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20">
                        <label for="f-member_{{ $i }}_email" class="sr-only">Committee member {{ $i }}: email address</label>
                        <input id="f-member_{{ $i }}_email" type="email" inputmode="email" spellcheck="false" wire:model="values.member_{{ $i }}_email" maxlength="150" autocomplete="off" placeholder="Member {{ $i }} email" class="bg-white shadow-sm w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20">
                    </div>
                    @error("values.member_{$i}_name") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    @error("values.member_{$i}_email") <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endfor
        </div>
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the Chairman (Report Given By), signed with your profile signature.'])
</div>