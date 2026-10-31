@php
    use App\Services\Workflow\FormSupport as FS;
    $input ??= 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20';
    $card = 'rounded-xl border border-slate-200 p-3.5';
    $cb = 'mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]';
    $rows = [
        'Name of student' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Date' => now()->format('F j, Y'),
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])

    <div>
        <p class="text-sm font-medium text-slate-700">Advisement <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></p>
        <p class="text-xs text-slate-500">Tick at least one.</p>
    </div>

    <div class="{{ $card }}">
        <label class="flex items-start gap-3 text-sm font-medium text-slate-800">
            <input type="checkbox" wire:model.live="values.advise_doctor" class="{{ $cb }}">
            Advised to see the University Doctor
        </label>
        <div x-show="$wire.values.advise_doctor" x-cloak class="mt-3 pl-7">
            <label for="f-doctor_reason" class="block text-[13px] font-medium text-slate-700">Reason for seeing the University Doctor <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
            <input id="f-doctor_reason" type="text" wire:model="values.doctor_reason" maxlength="200" class="{{ $input }}">
            @error('values.doctor_reason') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="{{ $card }}">
        <label class="flex items-start gap-3 text-sm font-medium text-slate-800">
            <input type="checkbox" wire:model.live="values.advise_haircut" class="{{ $cb }}">
            Advised to have a haircut/mustache shave and report to the VPSD for further inspection
        </label>
        <div x-show="$wire.values.advise_haircut" x-cloak class="mt-3 grid gap-3 pl-7 sm:grid-cols-2">
            <div>
                <label for="f-haircut_report_date" class="block text-[13px] font-medium text-slate-700">Date to report to the VPSD <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                <input id="f-haircut_report_date" type="date" wire:model="values.haircut_report_date" min="{{ now()->toDateString() }}" class="{{ $input }}">
                @error('values.haircut_report_date') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="f-haircut_report_session" class="block text-[13px] font-medium text-slate-700">Session <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                <select id="f-haircut_report_session" wire:model="values.haircut_report_session" class="{{ $input }}">
                    <option value="">Choose…</option>
                    <option value="Morning">Morning (8:00 AM to 12:00 PM)</option>
                    <option value="Afternoon">Afternoon (1:00 PM to 5:00 PM)</option>
                </select>
                @error('values.haircut_report_session') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="{{ $card }}">
        <label class="flex items-start gap-3 text-sm font-medium text-slate-800">
            <input type="checkbox" wire:model.live="values.advise_others" class="{{ $cb }}">
            Others
        </label>
        <div x-show="$wire.values.advise_others" x-cloak class="mt-3 pl-7">
            <label for="f-others_specify" class="block text-[13px] font-medium text-slate-700">Please specify <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
            <input id="f-others_specify" type="text" wire:model="values.others_specify" maxlength="250" class="{{ $input }}">
            @error('values.others_specify') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @include('livewire.workflow.issue-paper._note', ['text' => 'You appear as the advising Faculty/Security Guard/Personnel, signed with your profile signature. If a haircut is advised, the VPSD does the inspection when the student reports.'])
</div>