<?php

namespace App\Workflow\Documents;

/**
 * Shared parts of the three Student Clearance forms (F-SDM-020, 021, 022).
 * The paper form is printed three times side by side on a landscape long bond sheet (8.5 x 13 in).
 * The student signs with the profile e-signature; every other signature is a role in the chain.
 */
abstract class ClearanceBase extends WorkflowDocument
{
    /** True for the forms only an organization officer may file. */
    protected function officersOnly(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [];
    }

    public function requiresStudentSignature(): bool
    {
        return true;
    }

    public function systemFields(): array
    {
        $fields = parent::systemFields();

        if ($this->officersOnly()) {
            $fields += ['officer_position' => 'Officer position', 'officer_organization' => 'Organization'];
        }

        return $fields;
    }

    public function check(array $values): ?string
    {
        if ($this->officersOnly()) {
            $user = auth()->user();
            $user?->loadMissing('officerRecord');

            if (! $user?->officerRecord) {
                return 'Only a student officer can file this clearance. Use the General Students\' Clearance instead.';
            }
        }

        return null;
    }


    public function summary(array $values): string
    {
        return collect([$values['semester'] ?? null, $values['academic_year'] ?? null])->filter()->implode(' · ');
    }

    /** One approval step. Leave $scope null for a global role. Omit $scopeId to use the student's own. */
    protected function step(string $role, string $label, ?string $scope = null, ?int $scopeId = null): array
    {
        return array_filter(
            ['role' => $role, 'label' => $label, 'scope' => $scope, 'scope_id' => $scopeId],
            fn ($v) => $v !== null
        );
    }
}