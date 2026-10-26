<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-023 Rev. 1 (03-31-23): Student Complaint/Grievance Form.
 * Student files it. Chain: parent/guardian, then up to two witnesses, each through an emailed
 * link + one-time code (no account). The complainant signs with their profile e-signature.
 */
final class StudentComplaintGrievance extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Reports; }

    private const WITNESSES = 2;

    public function key(): string { return 'student_complaint_grievance'; }
    public function name(): string { return 'Student Complaint/Grievance'; }
    public function description(): string { return 'File a complaint with the VPSD office. Your parent/guardian and witnesses sign online.'; }
    public function formCode(): string { return 'F-SDM-023 Rev. 1 (03-31-23)'; }

    public function requiresStudentSignature(): bool { return true; }

    public function fields(): array
    {
        $notSelf = function ($attribute, $value, $fail) {
            $own = auth()->user()?->email;
            if ($own && $value && strcasecmp(trim($value), $own) === 0) {
                $fail('Use another person\'s email, not your own.');
            }
        };

        $fields = [
            'respondent' => [
                'label' => 'Complaint against', 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'Name of the person/s complained of', 'sensitive' => true,
            ],
            'complaint' => [
                'label' => 'Reason/s for the complaint', 'type' => 'textarea', 'required' => true, 'max' => 1200,
                'help' => 'State what happened, when and where.', 'sensitive' => true,
            ],
            'parent_name' => [
                'label' => "Parent/Guardian's full name", 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Maria Dela Cruz', 'sensitive' => true,
            ],
            'parent_email' => [
                'label' => "Parent/Guardian's email address", 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'name@example.com', 'sensitive' => true,
                'help' => 'A secure signing link and one-time code are sent here. Double-check it.',
                'rules' => ['email:rfc', $notSelf],
            ],
        ];

        for ($i = 1; $i <= self::WITNESSES; $i++) {
            $fields["witness_{$i}_name"] = [
                'label' => "Witness #{$i} full name", 'type' => 'text', 'max' => 120, 'sensitive' => true,
            ] + ($i === 1 ? ['help' => 'Optional. Leave witness rows blank if you have none.'] : []);
            $fields["witness_{$i}_email"] = [
                'label' => "Witness #{$i} email address", 'type' => 'text', 'max' => 150,
                'placeholder' => 'name@example.com', 'sensitive' => true,
                'rules' => ['email:rfc', $notSelf],
            ];
        }

        return $fields;
    }

    public function check(array $values): ?string
    {
        $seen = [strtolower(trim((string) ($values['parent_email'] ?? '')))];

        for ($i = 1; $i <= self::WITNESSES; $i++) {
            $name = trim((string) ($values["witness_{$i}_name"] ?? ''));
            $email = strtolower(trim((string) ($values["witness_{$i}_email"] ?? '')));

            if ($name === '' && $email === '') {
                continue;
            }
            if ($name === '' || $email === '') {
                return "Witness #{$i} needs both a name and an email.";
            }
            if (in_array($email, $seen, true)) {
                return 'Each signer needs a different email address.';
            }
            $seen[] = $email;
        }

        return null;
    }

    public function chain(array $values = []): array
    {
        $steps = [[
            'type' => 'external', 'kind' => 'guardian', 'label' => 'Parent/Guardian',
            'name_field' => 'parent_name', 'email_field' => 'parent_email',
        ]];

        for ($i = 1; $i <= self::WITNESSES; $i++) {
            if (trim((string) ($values["witness_{$i}_name"] ?? '')) === '' && trim((string) ($values["witness_{$i}_email"] ?? '')) === '') {
                continue;
            }
            $steps[] = [
                'type' => 'external', 'kind' => 'witness', 'label' => "Witness #{$i}",
                'name_field' => "witness_{$i}_name", 'email_field' => "witness_{$i}_email",
            ];
        }

        return $steps;
    }

    /** What the parent and witnesses are shown on the signing page. Never the email fields. */
    public function externalOverviewFields(): array { return ['respondent', 'complaint']; }

    public function beforeSigning(array $values): array { return $this->withVpsdName($values); }

    public function studentNote(): ?string
    {
        return 'Your parent/guardian, then each witness, gets an email with a secure link. They confirm their email with a one-time code, read the complaint and sign online. The VPSD office checks their details before sending.';
    }

    public function pdfView(): string { return 'pdf.student-complaint-grievance'; }

    /** Shown in notifications: keep it free of case details. */
    public function summary(array $values): string { return 'Student complaint'; }
}