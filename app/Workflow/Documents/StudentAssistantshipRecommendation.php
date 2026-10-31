<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-017 Rev. 1 (03-31-23): Recommendation for Student Assistantship Form.
 * The student asks someone who knows them (faculty, office personnel, etc.) to recommend them to the VPSD.
 * The recommender signs from an emailed link, or from their signatory account when the email belongs to one.
 * Older records were issued by the recommending signatory directly; they still print from the issuer snapshot.
 */
final class StudentAssistantshipRecommendation extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Requests; }

    public function key(): string { return 'student_assistantship_recommendation'; }
    public function name(): string { return 'Recommendation for Student Assistantship'; }
    public function description(): string { return 'Ask a faculty member or office personnel who knows you to recommend you for Student Assistantship.'; }
    public function formCode(): string { return 'F-SDM-017 Rev. 1 (03-31-23)'; }

    public function fields(): array
    {
        return $this->signerContactFields('recommender', "Recommender", 'They get a link to sign. If this email belongs to a DocuMate signatory account, they sign from their account instead.')
            + [
                'recommender_position' => [
                    'label' => "Recommender's position", 'type' => 'text', 'required' => true, 'max' => 80,
                    'placeholder' => 'e.g. Faculty, Office Head',
                ],
            ];
    }

    public function chain(array $values = []): array
    {
        return [
            ['type' => 'external', 'label' => 'Recommending signatory', 'name_field' => 'recommender_name', 'email_field' => 'recommender_email'],
        ];
    }

    /** Freeze the VPSD's name as the addressee. */
    public function beforeSigning(array $values): array { return $this->withVpsdName($values); }

    public function pdfView(): string { return 'pdf.student-assistantship-recommendation'; }

    public function summary(array $values): string
    {
        return collect([$values['recommender_name'] ?? null, $values['recommender_position'] ?? null])->filter()->implode(' · ')
            ?: 'Recommendation for Student Assistantship';
    }
}
