<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/**
 * F-SDM-009 Rev. 1 (03-31-23): Request for Excused Absence Form.
 * Chain: VPSD -> College Dean (recommending approval) -> VP for Academic Services (approved).
 */
final class RequestForExcusedAbsence extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Requests; }

    public function key(): string
    {
        return 'request_for_excused_absence';
    }

    public function name(): string
    {
        return 'Request for Excused Absence';
    }

    public function description(): string
    {
        return 'Ask for permission to be absent from your classes on certain dates.';
    }

    public function formCode(): string
    {
        return 'F-SDM-009 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'absence_from' => [
                'label' => 'First day of absence', 'type' => 'date', 'required' => true,
                'future' => true,
                'rules' => ['after_or_equal:today'],
            ],
            'absence_to' => [
                'label' => 'Last day of absence', 'type' => 'date', 'required' => true,
                'future' => true,
                'help' => 'Same as the first day if you will be absent for one day only.',
                'rules' => ['after_or_equal:today'],
            ],
            'reasons' => [
                'label' => 'Reason/s for absence', 'type' => 'textarea', 'required' => true, 'max' => 500,
                'help' => 'Briefly explain why you will be absent.',
                'sensitive' => true,
            ],
        ];
    }

    public function check(array $values): ?string
    {
        $from = $values['absence_from'] ?? null;
        $to = $values['absence_to'] ?? null;

        if ($from && $to && Carbon::parse($to)->lt(Carbon::parse($from))) {
            return 'The last day of absence cannot be before the first day.';
        }

        return null;
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'vpsd', 'label' => 'Recommending Approval'],
            ['role' => 'college_dean', 'scope' => 'college', 'label' => 'Recommending Approval'],
            ['role' => 'vpas', 'label' => 'Approved'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.request-for-excused-absence';
    }

    public function summary(array $values): string
    {
        $from = ! empty($values['absence_from']) ? Carbon::parse($values['absence_from']) : null;
        $to = ! empty($values['absence_to']) ? Carbon::parse($values['absence_to']) : null;

        $range = match (true) {
            $from && $to && $from->isSameDay($to) => $from->format('M j, Y'),
            $from && $to => $from->format('M j') . ' to ' . $to->format('M j, Y'),
            default => null,
        };

        return collect(['Excused absence', $range])->filter()->implode(' · ');
    }
}