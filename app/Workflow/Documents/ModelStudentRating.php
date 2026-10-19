<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-024 Rev. 1 (03-31-23): Model Student Faculty/Peer Rating Form.
 * The student names a rater (classmate or teacher). The rater needs no DocuMate account:
 * they get an emailed link, confirm their email with a one-time code, then rate and sign.
 */
class ModelStudentRating extends WorkflowDocument
{
    public const RATINGS = [
        5 => 'Outstanding',
        4 => 'Very Satisfactory',
        3 => 'Satisfactory',
        2 => 'Fair',
        1 => 'Poor',
    ];

    public function key(): string
    {
        return 'model_student_rating';
    }

    public function name(): string
    {
        return 'Model Student Peer Rating';
    }

    public function description(): string
    {
        return 'Ask a classmate or teacher to rate you. They sign online with their email, no account needed.';
    }

    public function formCode(): string
    {
        return 'F-SDM-024 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'rater_name' => [
                'label' => "Rater's full name", 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Maria Santos', 'sensitive' => true,
                'help' => 'A classmate or teacher who personally knows you.',
            ],
            'rater_relationship' => [
                'label' => "Rater's relationship to you", 'type' => 'text', 'required' => true, 'max' => 60,
                'placeholder' => 'e.g. Classmate, Professor', 'sensitive' => true,
            ],
            'rater_email' => [
                'label' => "Rater's email address", 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'name@example.com', 'sensitive' => true,
                'help' => 'The invitation and a one-time code are sent here. Double-check it.',
                'rules' => [
                    'email:rfc',
                    // Optional: set DOCUMATE_RATER_EMAIL_DOMAIN=lnu.edu.ph in .env to accept only school mailboxes.
                    function ($attribute, $value, $fail) {
                        $domain = config('documate.workflow.rater_email_domain');
                        if ($domain && $value && ! str_ends_with(strtolower(trim($value)), '@' . strtolower($domain))) {
                            $fail("The rater's email must end in @{$domain}.");
                        }
                    },
                    function ($attribute, $value, $fail) {
                        $own = auth()->user()?->email;
                        if ($own && $value && strcasecmp(trim($value), $own) === 0) {
                            $fail('You cannot rate yourself. Use another person\'s email.');
                        }
                    },
                ],
            ],
        ];
    }

    /** One external step. name_field / email_field point at the answers above. */
    public function chain(array $values = []): array
    {
        return [
            ['type' => 'external', 'label' => 'Rater', 'name_field' => 'rater_name', 'email_field' => 'rater_email'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.model-student-rating';
    }

    public function summary(array $values): string
    {
        return collect([$values['rater_name'] ?? null, $values['rater_relationship'] ?? null])->filter()->implode(' · ');
    }
}