<?php

namespace App\Workflow\Documents;

/** F-SDM-003 Rev. 1 (03-31-23): Request for Renewal/Reprinting of Student ID. */
final class StudentIdRenewal extends WorkflowDocument
{
    public function key(): string
    {
        return 'student_id_renewal';
    }

    public function name(): string
    {
        return 'Student ID Renewal/Reprinting';
    }

    public function description(): string
    {
        return 'Request a new student ID because it is worn out, destroyed or lost.';
    }

    public function formCode(): string
    {
        return 'F-SDM-003 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'not_presentable' => ['label' => 'It does not look presentable anymore', 'type' => 'checkbox'],
            'destroyed' => ['label' => 'It has been destroyed', 'type' => 'checkbox'],
            'lost' => ['label' => 'It was lost', 'type' => 'checkbox'],
            'other' => ['label' => 'Other reason/s', 'type' => 'checkbox'],
            'other_specify' => [
                'label' => 'If other, please explain', 'type' => 'textarea', 'max' => 300,
                'sensitive' => true,
            ],
            'surrender_ack' => [
                'label' => 'Since the use of more than one ID is a ground for disciplinary action, my old ID is, therefore, surrendered to this office.',
                'type' => 'checkbox',
            ],
        ];
    }

    public function check(array $values): ?string
    {
        $any = ! empty($values['not_presentable']) || ! empty($values['destroyed'])
            || ! empty($values['lost']) || ! empty($values['other']);

        if (! $any) {
            return 'Tick at least one reason for the request.';
        }
        if (! empty($values['other']) && trim((string) ($values['other_specify'] ?? '')) === '') {
            return 'Please explain the "Other reason/s".';
        }
        if (empty($values['surrender_ack'])) {
            return 'Please confirm that your old ID is surrendered to the office.';
        }

        return null;
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'vpsd', 'label' => 'Approved'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.student-id-renewal';
    }
    public function requiresStudentSignature(): bool
    {
        return true;
    }

    public function summary(array $values): string
    {
        $parts = [];
        if (! empty($values['not_presentable'])) $parts[] = 'worn out';
        if (! empty($values['destroyed'])) $parts[] = 'destroyed';
        if (! empty($values['lost'])) $parts[] = 'lost';
        if (! empty($values['other'])) $parts[] = 'other';

        return implode(', ', $parts);
    }
}