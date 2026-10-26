<?php

namespace App\Workflow\Documents;

/** Shared parts of F-SDM-025 Rev. 1 (03-31-23): College Model Student Nomination Form. */
abstract class CollegeModelNominationBase extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Awards; }

    public function formCode(): string { return 'F-SDM-025 Rev. 1 (03-31-23)'; }

    /** Only the nominator signs, so there are no approval steps. */
    public function chain(array $values = []): array { return []; }

    public function pdfView(): string { return 'pdf.college-model-nomination'; }

    public function summary(array $values): string { return 'College Model Student nomination'; }

    protected function contactFields(): array
    {
        return [
            'nominee_address' => [
                'label' => "Nominee's address", 'type' => 'textarea', 'required' => true, 'max' => 250,
                'sensitive' => true,
            ],
            'nominee_contact' => [
                'label' => "Nominee's contact number", 'type' => 'text', 'required' => true, 'max' => 30,
                'placeholder' => '09XXXXXXXXX', 'sensitive' => true,
            ],
        ];
    }
}