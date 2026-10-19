<?php

namespace App\Workflow\Documents;

use Illuminate\Validation\Rule;

/**
 * F-SDM-016 Rev. 1 (03-31-23): Parent's Consent for Student Assistantship Form.
 * The student names a parent/guardian. The parent signs through an emailed link + one-time code
 * (no account). The VPSD name is frozen as the addressee; the VPSD does not sign.
 */
final class ParentConsentForAssistantship extends WorkflowDocument
{
    private const RELATIONSHIPS = ['Son', 'Daughter', 'Ward'];

    public function key(): string { return 'parent_consent_assistantship'; }
    public function name(): string { return "Parent's Consent for Student Assistantship"; }
    public function description(): string { return 'Ask your parent or guardian to consent, online, to your working as a Student Assistant.'; }
    public function formCode(): string { return 'F-SDM-016 Rev. 1 (03-31-23)'; }

    public function fields(): array
    {
        return [
            'relationship' => [
                'label' => 'I am my parent/guardian\'s', 'type' => 'select', 'required' => true,
                'options' => self::RELATIONSHIPS,
                'rules' => [Rule::in(self::RELATIONSHIPS)],
            ],
            'parent_name' => [
                'label' => "Parent/Guardian's full name", 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Maria Dela Cruz', 'sensitive' => true,
            ],
            'parent_email' => [
                'label' => "Parent/Guardian's email address", 'type' => 'text', 'required' => true, 'max' => 150,
                'placeholder' => 'name@example.com', 'sensitive' => true,
                'help' => 'The consent form and a one-time code are sent here. Double-check it.',
                'rules' => [
                    'email:rfc',
                    function ($attribute, $value, $fail) {
                        $own = auth()->user()?->email;
                        if ($own && $value && strcasecmp(trim($value), $own) === 0) {
                            $fail('You cannot sign your own consent. Use your parent/guardian\'s email.');
                        }
                    },
                ],
            ],
        ];
    }

    public function chain(array $values = []): array
    {
        return [[
            'type' => 'external', 'kind' => 'consent', 'label' => 'Parent/Guardian Consent',
            'name_field' => 'parent_name', 'email_field' => 'parent_email',
        ]];
    }

    public function beforeSigning(array $values): array { return $this->withVpsdName($values); }

    public function studentNote(): ?string
    {
        return 'Your parent or guardian receives an email with a secure link. They confirm their email with a one-time code, then sign online. The VPSD office checks their details before sending it.';
    }

    public function pdfView(): string { return 'pdf.parent-consent-assistantship'; }

    public function summary(array $values): string
    {
        return collect(['Parent consent', $values['parent_name'] ?? null])->filter()->implode(' · ');
    }
}