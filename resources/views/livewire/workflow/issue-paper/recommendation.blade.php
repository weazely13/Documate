@php
    use App\Services\Workflow\FormSupport as FS;
    $rows = [
        'Student recommended' => $student ? FS::systemValue($student, 'formatted_full_name') : '',
        'Program, year and section' => $student ? FS::systemValue($student, 'program_year_section') : '',
        'Recommended to' => 'Vice President for Student Development',
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows])
    @include('livewire.workflow.issue-paper._note', ['text' => 'Nothing else to fill in. You appear as the recommending signatory, signed with your profile signature and today\'s date.'])
</div>