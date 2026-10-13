<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/**
 * F-SDM-010 Rev. 1 (03-31-23): Disciplinary Action Slip Form.
 * Issued by a faculty member, security guard or other signatory. The student reports to the
 * Guidance Office; the Guidance Counselor signs once counselling is done.
 */
final class DisciplinaryActionSlip extends WorkflowDocument
{
    public function key(): string
    {
        return 'disciplinary_action_slip';
    }

    public function name(): string
    {
        return 'Disciplinary Action Slip';
    }

    public function description(): string
    {
        return 'A notice of an offense. The student reports to the Guidance Office for counselling.';
    }

    public function formCode(): string
    {
        return 'F-SDM-010 Rev. 1 (03-31-23)';
    }

    /** The student never starts it. */
    public function issuedByOffice(): bool
    {
        return true;
    }

    public function issuedBySignatory(): bool
    {
        return true;
    }

    public function fields(): array
    {
        return [
            'offense' => [
                'label' => 'Type of offense', 'type' => 'text', 'required' => true, 'max' => 200,
                'placeholder' => 'e.g. Not wearing the school uniform',
                'sensitive' => true,
            ],
            'offense_date' => [
                'label' => 'Date the offense was committed', 'type' => 'date', 'required' => true,
                'past' => true,
                'rules' => ['before_or_equal:today'],
                'sensitive' => true,
            ],
            'offense_time' => [
                'label' => 'Time the offense was committed', 'type' => 'time', 'required' => true,
                'sensitive' => true,
            ],
            'counseling_date' => [
                'label' => 'Guidance counselling date (optional)', 'type' => 'date',
                'help' => 'Counselling is held every Wednesday. Leave blank to use the next Wednesday.',
                'rules' => ['after_or_equal:today'],
            ],
            'counseling_time' => [
                'label' => 'Guidance counselling time (optional)', 'type' => 'time',
            ],
        ];
    }

    public function beforeIssue(array $answers): array
    {
        if (empty($answers['counseling_date'])) {
            $answers['counseling_date'] = Carbon::today()->next(Carbon::WEDNESDAY)->toDateString();
        }

        return $answers;
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'guidance_counselor', 'label' => 'Counselling Completed'],
        ];
    }

    public function signingNote(): ?string
    {
        return 'Sign only after the student has completed counselling.';
    }

    public function issueNote(): ?string
    {
        return 'The student and the guidance counselor are notified right away. The student reports to the Guidance Office, and the counselor signs once counselling is done. You appear on the slip as the reporter.';
    }

    public function pdfView(): string
    {
        return 'pdf.disciplinary-action-slip';
    }

    /* ---- Dashboard "Dates to Remember" card ---- */

    public function showsAsNotice(): bool
    {
        return true;
    }

    public function noticeDate(array $values): ?string
    {
        return $values['counseling_date'] ?? null;
    }

    public function noticeTitle(array $values): string
    {
        return 'Guidance Counselling';
    }

    public function noticeLine(array $values): string
    {
        $time = $this->time($values['counseling_time'] ?? null);

        return 'Report to the Guidance Office' . ($time ? ' · ' . $time : '');
    }

    public function noticeTone(): string
    {
        return 'rose';
    }

    public function summary(array $values): string
    {
        $date = ! empty($values['counseling_date']) ? Carbon::parse($values['counseling_date'])->format('M j, Y') : null;

        return collect(['Counselling', $date, $this->time($values['counseling_time'] ?? null)])->filter()->implode(' · ');
    }

    private function time(?string $t): ?string
    {
        return $t ? Carbon::createFromFormat('H:i', substr($t, 0, 5))->format('g:i A') : null;
    }
}