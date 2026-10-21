<?php

namespace App\Workflow\Documents;

use Illuminate\Validation\Rule;

/** F-SDM-024 Rev. 1 (03-31-23), issued by a signatory who rates the student directly. */
class ModelStudentRatingBySignatory extends WorkflowDocument
{
    public function key(): string { return 'model_student_rating_signatory'; }
    public function name(): string { return 'Model Student Faculty/Peer Rating (by signatory)'; }
    public function description(): string { return 'A faculty member or other signatory rates a student for the Model Student award.'; }
    public function formCode(): string { return 'F-SDM-024 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.model-student-rating'; }

    public function issuedByOffice(): bool { return true; }
    public function issuedBySignatory(): bool { return true; }
    public function notifiesStudent(): bool { return false; }

    public function fields(): array
    {
        $values = array_map('strval', array_keys(ModelStudentRating::RATINGS));

        return [
            'rating' => [
                'label' => 'Rating', 'type' => 'radio', 'required' => true,
                'options' => $values,
                'option_labels' => array_map(fn ($l) => $l, ModelStudentRating::RATINGS),
                'rules' => [Rule::in($values)],
                'sensitive' => true, 'staff_only' => true,
            ],
        ];
    }

    public function check(array $values): ?string
    {
        if (! isset(ModelStudentRating::RATINGS[(int) ($values['rating'] ?? 0)])) {
            return 'Choose a rating.';
        }

        $user = auth()->user();
        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this rating.';
        }

        return null;
    }

    public function chain(array $values = []): array { return []; }

    public function issueNote(): ?string
    {
        return 'Pick the student and choose the rating. You appear as the rater, signed with your profile signature. The student is not notified. The VPSD office then does the final verification.';
    }

    public function issuedMessage(array $values): string
    {
        return 'A faculty/peer rating was submitted concerning you.';
    }

    public function summary(array $values): string { return 'Model Student peer rating'; }
}