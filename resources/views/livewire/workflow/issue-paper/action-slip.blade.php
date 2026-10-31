@php
    use App\Services\Workflow\FormSupport as FS;
    $input ??= 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20';
    $rows = [
        'Name of student' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Program' => $student ? FS::systemValue($student, 'program') : '',
        'Year and section' => $student ? FS::systemValue($student, 'year_section') : '',
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <label for="f-offense" class="block text-[13px] font-medium text-slate-700">Type of offense <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
        <input id="f-offense" type="text" wire:model="values.offense" maxlength="200" placeholder="e.g. Not wearing the school uniform" class="{{ $input }}">
        @error('values.offense') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="f-offense_date" class="block text-[13px] font-medium text-slate-700">Date the offense was committed <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
            <input id="f-offense_date" type="date" wire:model="values.offense_date" max="{{ now()->toDateString() }}" class="{{ $input }}">
            @error('values.offense_date') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="f-offense_time" class="block text-[13px] font-medium text-slate-700">Time the offense was committed <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
            <input id="f-offense_time" type="time" wire:model="values.offense_time" class="{{ $input }}">
            @error('values.offense_time') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the Security Guard/Faculty/Personnel, signed with your profile signature. The Guidance Counselor sets the counselling date and time later (Violation Clearance Slip).'])
</div>