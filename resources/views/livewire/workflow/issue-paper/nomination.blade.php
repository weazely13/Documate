@php
    use App\Services\Workflow\FormSupport as FS;
    $g = fn ($k) => $student ? FS::systemValue($student, $k) : '';
    $rows = [
        'Name of nominee' => $g('formatted_full_name'),
        'College' => $g('college'),
        'Program being pursued' => $g('program'),
        'Year and section' => $g('year_section'),
        'Address' => $g('address'),
        'Contact number' => $g('contact_number'),
    ];
@endphp
<div class="space-y-4">
    @include('livewire.workflow.issue-paper._details', ['student' => $student, 'rows' => $rows, 'heading' => "Nominee details (from the student's profile)"])
    @include('livewire.workflow.issue-paper._note', ['text' => 'Nothing else to fill in. If something shows a dash, ask the student to complete their profile first. You appear as the nominator, signed with your profile signature and today\'s date.'])
</div>