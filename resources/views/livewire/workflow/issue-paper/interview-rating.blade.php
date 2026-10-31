@php
    use App\Services\Workflow\FormSupport as FS;
    use App\Workflow\Documents\CollegeModelInterviewRating as R;

    $rows = [
        'Interviewer' => FS::systemValue(auth()->user(), 'formatted_full_name'),
        'Date' => now()->format('F j, Y'),
    ];
@endphp
<div class="space-y-4">
    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5">
        <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold text-[#2A57B4]">Filled in from your account</p>
            <span class="rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 ring-1 ring-blue-100">Read-only</span>
        </div>
        <dl class="mt-2.5 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
            @foreach ($rows as $label => $value)
                <div wire:key="iv-{{ \Illuminate\Support\Str::slug($label) }}">
                    <dt class="text-[11px] font-medium text-slate-500">{{ $label }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="rounded-xl border border-slate-200 p-3.5">
        <p class="text-sm font-semibold text-slate-900">Directions</p>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">
            Evaluate each interviewee on a 10-point scale, where 10 is the highest and 1 the lowest.
        </p>
        <ul class="mt-2 grid gap-1 text-xs text-slate-600 sm:grid-cols-3">
            @foreach (R::CRITERIA as $label)
                <li class="rounded-lg bg-slate-50 px-2.5 py-1.5"><span class="font-semibold text-slate-800">{{ $label }}</span> (10 points)</li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-slate-500">
            Search each interviewee and pick them from the list. Only students with an active account can be rated.
            The total (out of 30) is computed. Leave unused cards empty (up to {{ R::ROWS }} interviewees).
        </p>
    </div>

    @include('livewire.workflow._rating-grid')

    @foreach (array_keys($this->document->fields()) as $fieldName)
        @error("values.$fieldName") <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
    @endforeach

    @include('livewire.workflow.issue-paper._note', ['text' => 'No student is notified. You appear as the interviewer, signed with your profile signature and today\'s date. The VPSD office then does the final verification.'])
</div>