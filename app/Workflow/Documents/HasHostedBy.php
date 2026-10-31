<?php

namespace App\Workflow\Documents;

use App\Models\Organization;
use App\Models\SignatoryAssignment;
use App\Models\SignatoryRole;
use Illuminate\Validation\Rule;

/**
 * Shared "Activity is hosted by" logic for the in-campus and off-campus permits.
 *   Organization              -> internal Organization Adviser (needs an account), student must be an officer
 *   Department                -> internal Program Chair (needs an account), student names the department
 *   Non-Academic Organization -> internal adviser IF that organization has an assigned adviser account;
 *                                otherwise EXTERNAL adviser: emailed link + one-time code, no account
 */
trait HasHostedBy
{
    private const NON_ACADEMIC = 'Non-Academic Organization';

    /** signatory_roles.key used for organization advisers. */
    private const ADVISER_ROLE = 'org_adviser';

    /** Set to false to let non-officers request organization-hosted permits too. */
    private const REQUIRE_OFFICER_FOR_ORG = true;

    /**
     * Non-academic organizations that currently have an adviser with a DocuMate account.
     * @return array<string, int> organization name => organization id
     */
    protected function nonAcademicOrgsWithAdviser(): array
    {
        $role = SignatoryRole::active()->where('key', self::ADVISER_ROLE)->first();
        if (! $role) {
            return [];
        }

        $orgIds = SignatoryAssignment::current()
            ->where('signatory_role_id', $role->id)
            ->where('scope_type', 'organization')
            ->pluck('scope_id');

        if ($orgIds->isEmpty()) {
            return [];
        }

        return Organization::active()
            ->where('is_academic', false)
            ->whereNull('year_level')
            ->whereIn('id', $orgIds)
            ->pluck('id', 'name')
            ->all();
    }

    /** The chosen non-academic org's id, but only if it has an adviser account. */
    protected function nonAcademicAdviserOrgId(array $values): ?int
    {
        $name = trim((string) ($values['non_academic_org'] ?? ''));
        $id = $this->nonAcademicOrgsWithAdviser()[$name] ?? null;

        return $id ? (int) $id : null;
    }

    /** Only organization officers may request these permits. The form asks this first. */
    public function requiresOfficer(): bool
    {
        return true;
    }

    /** Spread into fields() first:  ...$this->officerQuestionField() */
    protected function officerQuestionField(): array
    {
        return [
            'is_officer' => [
                'label' => 'Are you an officer of a student organization?', 'type' => 'choice', 'required' => true,
                'options' => ['Yes', 'No'],
                'option_labels' => ['Yes' => 'Yes, I am an officer', 'No' => 'No, I am not an officer'],
                'rules' => [Rule::in(['Yes', 'No'])],
                'help' => 'Only officers of a recognized student organization can request this permit. Your position is taken from your officer record.',
            ],
        ];
    }

    /** The student said they are an officer, and their account has an officer record. */
    protected function checkOfficer(array $values): ?string
    {
        if (($values['is_officer'] ?? null) !== 'Yes') {
            return 'Only officers of a student organization can request this permit. Ask an officer of your organization to file it.';
        }

        $user = auth()->user();
        $user?->loadMissing('officerRecord.position');
        if (! $user?->officerRecord) {
            return 'You answered that you are an officer, but your account has no officer record yet. Ask your organization adviser or the VPSD office to add you, then try again.';
        }

        return null;
    }

    /** Spread into fields():  ...$this->hostedByFields() */
    protected function hostedByFields(): array
    {
        $nonAcademic = Organization::active()
            ->where('is_academic', false)
            ->whereNull('year_level')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $options = ['Organization', 'Department'];
        if ($nonAcademic !== []) {
            $options[] = self::NON_ACADEMIC;
        }

        $onNonAcademic = ['hosted_by' => self::NON_ACADEMIC];

        // Adviser name/email are not needed for these organizations (the adviser signs inside DocuMate).
        $hideAdviserFor = [
            'field' => 'non_academic_org',
            'values' => array_map('strval', array_keys($this->nonAcademicOrgsWithAdviser())),
        ];

        return [
            'hosted_by' => [
                'label' => 'Activity is hosted by', 'type' => 'select', 'required' => true,
                'options' => $options,
                'rules' => [Rule::in($options)],
            ],
            'department_name' => [
                'label' => 'Name of the Department', 'type' => 'text', 'max' => 150,
                'placeholder' => 'e.g. Department of Information Technology',
                'help' => 'Required when the activity is hosted by a Department.',
                'show_when' => ['hosted_by' => 'Department'],
            ],
            'non_academic_org' => [
                'label' => 'Non-Academic Organization', 'type' => 'select',
                'options' => $nonAcademic,
                'rules' => [Rule::in($nonAcademic)],
                'help' => 'If the organization\'s adviser already has a DocuMate account, you will not need to enter their email.',
                'show_when' => $onNonAcademic,
            ],
            'non_academic_position' => [
                'label' => 'Your position in the organization', 'type' => 'text', 'max' => 100,
                'placeholder' => 'e.g. President',
                'show_when' => $onNonAcademic,
            ],
            'adviser_title' => $this->salutationField('adviser_name', false) + [
                'show_when' => $onNonAcademic,
                'hide_when' => $hideAdviserFor,
            ],
            'adviser_name' => [
                'label' => "Adviser's full name", 'type' => 'text', 'max' => 120,
                'placeholder' => 'e.g. Maria Santos', 'salutation' => 'adviser_title',
                'show_when' => $onNonAcademic,
                'hide_when' => $hideAdviserFor,
            ],
            'adviser_email' => [
                'label' => "Adviser's email address", 'type' => 'text', 'max' => 150,
                'placeholder' => 'name@example.com',
                'help' => 'The adviser gets a secure link and a one-time code here. Double-check it.',
                'show_when' => $onNonAcademic,
                'hide_when' => $hideAdviserFor,
                'rules' => [
                    'email:rfc',
                    function ($attribute, $value, $fail) {
                        $own = auth()->user()?->email;
                        if ($own && $value && strcasecmp(trim($value), $own) === 0) {
                            $fail("You cannot be your own adviser. Use the adviser's email.");
                        }
                    },
                ],
            ],
        ];
    }

    /** First approval step, chosen by the "hosted by" answer. Must not depend on who is logged in. */
    protected function hostedByStep(array $values): array
    {
        return match ($values['hosted_by'] ?? 'Organization') {
            'Department' => ['role' => 'program_chair', 'scope' => 'program', 'label' => 'Recommending Approval'],
            self::NON_ACADEMIC => $this->nonAcademicStep($values),
            default => ['role' => self::ADVISER_ROLE, 'scope' => 'organization', 'label' => 'Recommending Approval'],
        };
    }

    private function nonAcademicStep(array $values): array
    {
        // The adviser has an account: sign inside DocuMate, scoped to THAT organization
        // (not the student's own organization_id, which is a different thing).
        if ($orgId = $this->nonAcademicAdviserOrgId($values)) {
            return [
                'role' => self::ADVISER_ROLE,
                'scope' => 'organization',
                'scope_id' => $orgId,
                'label' => 'Recommending Approval',
            ];
        }

        // No adviser account: emailed link + one-time code.
        return [
            'type' => 'external', 'kind' => 'endorse', 'label' => 'Recommending Approval',
            'name_field' => 'adviser_name', 'email_field' => 'adviser_email',
        ];
    }

    protected function checkHostedBy(array $values): ?string
    {
        $user = auth()->user();
        $user?->loadMissing('officerRecord.position');
        $hosted = $values['hosted_by'] ?? '';

        if ($hosted === 'Organization') {
            if (! $user?->organization_id) {
                return 'Your account has no organization. Choose "Department" or update your profile.';
            }

            if (self::REQUIRE_OFFICER_FOR_ORG) {
                $officer = $user->officerRecord;

                if (! $officer) {
                    return 'Only a student officer can request a permit for an organization activity. Choose "Department" or ask an officer of your organization.';
                }
                if ((int) $officer->organization_id !== (int) $user->organization_id) {
                    return 'Your officer record belongs to a different organization than your profile. Contact the VPSD office.';
                }
            }
        }

        if ($hosted === 'Department') {
            if (trim((string) ($values['department_name'] ?? '')) === '') {
                return 'Please specify the name of the department.';
            }
            if (! $user?->program_id) {
                return 'Your account has no program. Update your profile first.';
            }
        }

        if ($hosted === self::NON_ACADEMIC) {
            $required = [
                'non_academic_org' => 'Choose the non-academic organization.',
                'non_academic_position' => 'Enter your position in the organization.',
            ];

            // The adviser's name/email are only needed when the adviser has no account.
            if ($this->nonAcademicAdviserOrgId($values) === null) {
                $required['adviser_name'] = "Enter the adviser's full name.";
                $required['adviser_email'] = "Enter the adviser's email address.";
            }

            foreach ($required as $field => $message) {
                if (trim((string) ($values[$field] ?? '')) === '') {
                    return $message;
                }
            }

            if ($this->nonAcademicAdviserOrgId($values) === null
                && ! filter_var($values['adviser_email'], FILTER_VALIDATE_EMAIL)) {
                return "The adviser's email address is not valid.";
            }
        }

        return null;
    }
}