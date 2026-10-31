@php
    use App\Services\Workflow\FormSupport as FS;
    $rows = [
        'Student' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Program being pursued' => $student ? FS::systemValue($student, 'program') : '',
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <p class="text-sm font-medium text-slate-700">Rating <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></p>
        <p class="text-xs text-slate-500">Based on your honest assessment of the student's interpersonal relations with classmates and teachers.</p>
        <div class="mt-2 grid gap-2">
            @foreach (\App\Workflow\Documents\ModelStudentRating::RATINGS as $n => $label)
                <label wire:key="rt-{{ $n }}" class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                    <input type="radio" wire:model="values.rating" value="{{ $n }}" class="h-4 w-4 accent-[#2A57B4]">
                    <span class="font-medium text-slate-800">{{ $label }}</span>
                    <span class="ml-auto text-slate-500">({{ $n }})</span>
                </label>
            @endforeach
        </div>
        @error('values.rating') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the rater, signed with your profile signature and today\'s date. The student is not notified.'])
</div>