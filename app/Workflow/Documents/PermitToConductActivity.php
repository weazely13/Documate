<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/** F-SDM-006 Rev. 1 (03-31-23): Permit to Conduct an In-Campus Student Activity Form. */
final class PermitToConductActivity extends WorkflowDocument
{
    use HasHostedBy, HasActivitySlots;

    public function key(): string
    {
        return 'permit_to_conduct_activity';
    }

    public function name(): string
    {
        return 'Permit to Conduct an In-Campus Activity';
    }

    public function description(): string
    {
        return 'Request approval to hold a student activity or event inside the campus.';
    }

    public function formCode(): string
    {
        return 'F-SDM-006 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'activity' => [
                'label' => 'Activity/Event', 'type' => 'text', 'required' => true, 'max' => 200,
                'placeholder' => 'e.g. Intramurals Opening Program',
            ],
            'purposes' => [
                'label' => 'Purpose/s', 'type' => 'textarea', 'required' => true, 'max' => 600,
                'help' => 'Briefly state why the activity is being held.',
            ],
            'venue' => [
                'label' => 'Venue', 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'e.g. University Gymnasium',
            ],
            'persons_involved' => [
                'label' => 'Persons Involved', 'type' => 'text', 'required' => true, 'max' => 250,
                'placeholder' => 'e.g. BSIT students, faculty advisers',
            ],
            ...$this->slotsField(),
        ];
    }

    public function check(array $values): ?string
    {
        return $this->checkSlots($values) ?? $this->checkHostedBy($values);
    }

    public function summary(array $values): string
    {
        return collect([$values['activity'] ?? null, $this->slotsSummary($values)])->filter()->implode(' · ');
    }

    public function chain(array $values = []): array
    {
        return [
            $this->hostedByStep($values),
            ['role' => 'vpsd', 'label' => 'Approved'],
        ];
    }


    /** "Name and Signature of the Student" is on the paper form. */
    public function requiresStudentSignature(): bool
    {
        return true;
    }

    public function pdfView(): string
    {
        return 'pdf.permit-to-conduct-activity';
    }

}