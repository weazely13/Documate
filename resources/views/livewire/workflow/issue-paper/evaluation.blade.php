@php
    use App\Services\Workflow\FormSupport as FS;
    use App\Workflow\Documents\StudentAssistantEvaluation as Ev;
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
    $rows = [
        'Name of student assistant' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Program, year and section' => $student ? FS::systemValue($student, 'program_year_section') : '',
    ];
    $scale = [5 => 'Outstanding', 4 => 'Very Satisfactory', 3 => 'Satisfactory', 2 => 'Good', 1 => 'Poor'];
@endphp
<div class="space-y-5">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <label class="block text-sm font-medium text-slate-700">Office/Area assignment <span class="text-rose-500">*</span></label>
        <input type="text" wire:model="values.office_area" maxlength="120" placeholder="e.g. Registrar's Office" class="{{ $input }}">
        @error('values.office_area') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="text-sm font-semibold text-slate-900">Performance ratings</p>
        <p class="text-xs text-slate-400">Rate on a scale of 5, where 5 is the highest and 1 the lowest. The mean rating is computed for you.</p>

        <div class="mt-3 space-y-3">
            @php $i = 1; @endphp
            @foreach (Ev::CRITERIA as $key => $label)
                <div wire:key="crit-{{ $key }}" class="rounded-xl border border-slate-200 p-3">
                    <p class="text-sm font-medium text-slate-800">{{ $i++ }}. {{ $label }} <span class="text-rose-500">*</span></p>
                    <div class="mt-2 grid grid-cols-5 gap-1 sm:gap-2">
                        @foreach ($scale as $n => $text)
                            <label wire:key="r-{{ $key }}-{{ $n }}" class="flex min-w-0 cursor-pointer flex-col items-center rounded-xl border border-slate-300 px-1 py-2 text-center hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                                <input type="radio" name="r_{{ $key }}" wire:model="values.{{ $key }}" value="{{ $n }}" class="h-4 w-4 accent-[#2A57B4]">
                                <span class="mt-1 text-sm font-semibold text-slate-800">{{ $n }}</span>
                                <span class="hidden w-full truncate text-[10px] leading-tight text-slate-500 sm:block">{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error("values.$key") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <p class="text-sm font-medium text-slate-700">Recommendation <span class="text-rose-500">*</span></p>
        <div class="mt-2 grid gap-2">
            @foreach ([Ev::RENEW, Ev::NOT_RENEW] as $opt)
                <label wire:key="rec-{{ $loop->index }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                    <input type="radio" wire:model="values.recommendation" value="{{ $opt }}" class="h-4 w-4 accent-[#2A57B4]">
                    {{ $opt }}
                </label>
            @endforeach
        </div>
        @error('values.recommendation') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Comment/s</label>
        <textarea wire:model="values.comments" rows="3" maxlength="500" class="{{ $input }}"></textarea>
        @error('values.comments') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the Supervisor (Rater), signed with your profile signature and today\'s date.'])
</div>