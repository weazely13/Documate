<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/**
 * F-SDM-007 Rev. 1 (03-31-23): Permit to Conduct/Participate in a Non-Curricular Off-Campus Activity Form.
 * Chain: Adviser / Dept. Chair (or external adviser) -> College Dean -> Vice President.
 */
final class PermitToConductOffCampus extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Permits; }

    use HasHostedBy, HasActivitySlots;

    public function key(): string
    {
        return 'permit_to_conduct_off_campus';
    }

    public function name(): string
    {
        return 'Permit to Conduct/Participate in an Off-Campus Activity';
    }

    public function description(): string
    {
        return 'Request approval to hold or join a non-curricular activity outside the campus.';
    }

    public function formCode(): string
    {
        return 'F-SDM-007 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            ...$this->officerQuestionField(),
            'activity' => [
                'label' => 'Activity/Event', 'type' => 'text', 'required' => true, 'max' => 200,
                'placeholder' => 'e.g. Leadership Summit',
            ],
            'purposes' => [
                'label' => 'Purpose/s', 'type' => 'textarea', 'required' => true, 'max' => 600,
                'help' => 'Briefly state why the activity is being held or attended.',
            ],
            'venue' => [
                'label' => 'Venue', 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'e.g. Ormoc City Hall',
            ],
            'persons_involved' => [
                'label' => 'Persons Involved', 'type' => 'textarea', 'required' => true, 'max' => 500,
                'help' => 'List the students or groups taking part.',
            ],
            ...$this->slotsField(),
            'parents_permit_ack' => [
                'label' => "I will submit the notarized Parents' Permit of Students to the VPSD office.",
                'type' => 'checkbox',
            ],
        ];
    }

    public function chain(array $values = []): array
    {
        return [
            $this->hostedByStep($values),
            ['role' => 'college_dean', 'scope' => 'college', 'label' => 'Recommending Approval'],
            ['role' => 'vpsd', 'label' => 'Approved'],
        ];
    }

    public function check(array $values): ?string
    {
        if ($m = $this->checkOfficer($values) ?? $this->checkSlots($values)) { return $m; }

        if (empty($values['parents_permit_ack'])) {
            return "Please confirm that you will submit the notarized Parents' Permit of Students.";
        }

        return $this->checkHostedBy($values);
    }

    /** "Name and Signature of the Student" is on the paper form. */
    public function requiresStudentSignature(): bool
    {
        return true;
    }

    public function pdfView(): string
    {
        return 'pdf.permit-to-conduct-off-campus';
    }

    public function summary(array $values): string
    {
        return collect([$values['activity'] ?? null, $this->slotsSummary($values)])->filter()->implode(' · ');
    }
}