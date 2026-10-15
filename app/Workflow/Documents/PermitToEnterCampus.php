<?php

namespace App\Workflow\Documents;
use Carbon\Carbon;

/** F-SDM-002 Rev. 1 (03-31-23): Permit to Enter the Campus / to Attend Classes. */
final class PermitToEnterCampus extends WorkflowDocument
{
    public function key(): string
    {
        return 'permit_to_enter_campus';
    }

    public function name(): string
    {
        return 'Permit to Enter the Campus';
    }

    public function description(): string
    {
        return 'Permission to enter the campus to attend classes without an ID or school uniform.';
    }

    public function formCode(): string
    {
        return 'F-SDM-002 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'no_id' => ['label' => 'Without an ID', 'type' => 'checkbox'],
            'no_uniform' => ['label' => 'Not wearing school uniform', 'type' => 'checkbox'],
            'others' => ['label' => 'Others', 'type' => 'checkbox'],
            'others_specify' => [
                'label' => 'If others, please specify', 'type' => 'text', 'max' => 120,
                'placeholder' => 'e.g. Lost ID, replacement in process',
            ],
            'reasons' => [
                'label' => 'Reason/s', 'type' => 'textarea', 'required' => true, 'max' => 500,
                'help' => 'Briefly explain why you need this permit.',
                'sensitive' => true,
            ],
        ];
    }

    public function check(array $values): ?string
    {
        $any = ! empty($values['no_id']) || ! empty($values['no_uniform']) || ! empty($values['others']);

        if (! $any) {
            return 'Tick at least one reason for the permit.';
        }
        if (! empty($values['others']) && trim((string) ($values['others_specify'] ?? '')) === '') {
            return 'Please specify the "Others" reason.';
        }

        return null;
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'vpsd', 'label' => 'Approved'],
            ['role' => 'guidance_counselor', 'label' => 'Counselling Completed'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.permit-to-enter-campus';
    }

    public function summary(array $values): string
    {
        $parts = [];
        if (! empty($values['no_id'])) $parts[] = 'no ID';
        if (! empty($values['no_uniform'])) $parts[] = 'no uniform';
        if (! empty($values['others'])) $parts[] = 'others';

        return implode(', ', $parts);
    }
    public function reviewerFields(): array
    {
        return [
            'valid_until' => ['label' => 'Permit valid only until', 'type' => 'date', 'required' => true],
        ];
    }

   public function beforeSigning(array $values): array
    {
        return empty($values['counseling_date']) ? $values + static::counselingSlot() : $values;
    }

    public function noticeLine(array $values): string
    {
        return rtrim('Have your permit signed by the Guidance Counselor ' . $this->counselingWhen($values)) . '.';
    }

    public function studentNote(): ?string
    {
        return 'The VPSD signs your permit first. Then visit the Guidance Office on the scheduled Wednesday so the Guidance Counselor can sign it. Your permit is issued after both signatures.';
    }
    public function showsAsNotice(): bool { return true; }

    public function noticeDate(array $values): ?string { return $values['counseling_date'] ?? null; }

    public function noticeTitle(array $values): string { return 'Visit the Guidance Office'; }

}