@php
    use App\Services\Workflow\FormSupport as FS;
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
    $rows = [
        'Student' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Program, year and section' => $student ? FS::systemValue($student, 'program_year_section') : '',
        'Date' => now()->format('F j, Y'),
        'To' => 'Vice President for Student Development',
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <label class="block text-sm font-medium text-slate-700">Reason for the referral <span class="text-rose-500">*</span></label>
        <textarea wire:model="values.reason" rows="4" maxlength="600" class="{{ $input }}"></textarea>
        <p class="mt-1 text-xs text-slate-400">State the reason for referring the student to the VPSD office.</p>
        @error('values.reason') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the referring Faculty/Security Guard/Personnel, signed with your profile signature.'])
</div>