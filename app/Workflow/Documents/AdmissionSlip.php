<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/** F-SDM-001 Rev. 1 (03-31-23): Admission Slip Form, Office of the VP for Student Development. */
final class AdmissionSlip extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Permits; }

    public function key(): string
    {
        return 'admission_slip';
    }

    public function name(): string
    {
        return 'Admission Slip';
    }

    public function description(): string
    {
        return 'Permission to attend class after an absence.';
    }

    public function formCode(): string
    {
        return 'F-SDM-001 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'subject' => [
                'label' => 'Subject', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Data Structures',
            ],
            'professor' => [
                'label' => 'Name of Professor', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Prof. Juan Dela Cruz',
            ],
            'absence_date' => [
                'label' => 'Date of Absence', 'type' => 'date', 'required' => true,
                'rules' => ['before_or_equal:today'],
            ],
            'reasons' => [
                'label' => 'Reason/s for absence', 'type' => 'textarea', 'required' => true, 'max' => 500,
                'help' => 'Briefly explain why you were absent.',
                'sensitive' => true, 
            ],
        ];
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'guidance_counselor', 'label' => 'Recommending Approval'],
            ['role' => 'vpsd', 'label' => 'Approved'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.admission-slip';
    }

    /** The paper form is printed twice per page (student copy / professor copy). Set to 1 for a single slip. */
    public function pdfCopies(): int
    {
        return 1;
    }

    public function summary(array $values): string
    {
        $date = ! empty($values['absence_date']) ? Carbon::parse($values['absence_date'])->format('M j, Y') : null;

        return collect([$values['subject'] ?? null, $date ? "absent {$date}" : null])->filter()->implode(' · ');
    }
}