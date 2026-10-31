<?php

namespace App\Workflow\Documents;

use Illuminate\Validation\Rule;

/**
 * F-SDM-026 Rev. 1 (03-31-23): College Model Student Interview/Talent Presentation Rating Form.
 * The student starts it, lists the students to be interviewed (up to 10, each picked from their
 * DocuMate account) and names the interviewer. The interviewer scores every interviewee on three
 * 10-point criteria while signing, from an emailed link or from their signatory account.
 * The listed students are notified on submission and get a read-only copy of the document.
 * Older records had the student as the only interviewee (row 1); they still print the same way.
 */
final class CollegeModelInterviewRating extends WorkflowDocument
{
    public const ROWS = 10;

    /** field suffix => column heading */
    public const CRITERIA = [
        'personality' => 'Personality',
        'communication' => 'Communication Skills',
        'talent' => 'Talent',
    ];

    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Awards; }

    public function key(): string { return 'college_model_interview_rating'; }
    public function name(): string { return 'College Model Student Interview/Talent Rating'; }
    public function description(): string { return 'List the students to be interviewed and ask the interviewer to rate their interview and talent presentation.'; }
    public function formCode(): string { return 'F-SDM-026 Rev. 1 (03-31-23)'; }

    public function fields(): array
    {
        return [
            'interviewees' => [
                'label' => 'Students to be interviewed', 'type' => 'students', 'required' => true,
                'max_items' => self::ROWS,
                'help' => 'Search by name, email or student number and add up to ' . self::ROWS . ' students. Each one must have a DocuMate account. They are notified when you submit and get a copy of the signed form.',
            ],
        ] + $this->signerContactFields('interviewer', 'Interviewer', 'They get a link to score and sign. If this email belongs to a DocuMate signatory account, they sign from their account instead.');
    }

    /** Listed interviewees, in order: [['id' => int, 'name' => string, 'number' => string], ...]. */
    public static function interviewees(array $values): array
    {
        $list = [];
        foreach (array_values(is_array($values['interviewees'] ?? null) ? $values['interviewees'] : []) as $row) {
            if (is_array($row) && ! empty($row['id'])) {
                $list[] = ['id' => (int) $row['id'], 'name' => (string) ($row['name'] ?? ''), 'number' => (string) ($row['number'] ?? '')];
            }
        }

        return array_slice($list, 0, self::ROWS);
    }

    public function check(array $values): ?string
    {
        $list = self::interviewees($values);
        if ($list === []) {
            return 'Add at least one student to be interviewed.';
        }
        if (count(array_unique(array_column($list, 'id'))) !== count($list)) {
            return 'Each student can only be listed once.';
        }

        return null;
    }

    /** The interviewer scores these while signing: three scores for each listed student. */
    public function signerFields(array $values = []): array
    {
        $scores = array_map('strval', range(1, 10));
        $rows = self::interviewees($values) ?: [['name' => $values['student_name'] ?? '']];
        $fields = [];

        foreach (array_values($rows) as $i => $row) {
            $n = $i + 1;
            foreach (self::CRITERIA as $key => $label) {
                $fields["score_{$n}_{$key}"] = [
                    'label' => trim(($row['name'] ?: "Interviewee {$n}") . " — {$label} (1 to 10)"),
                    'type' => 'select', 'required' => true,
                    'options' => $scores,
                    'rules' => [Rule::in($scores)],
                    'sensitive' => true,
                    'group' => $row['name'] ?: "Interviewee {$n}",
                ];
            }
        }

        return $fields;
    }

    /** One row per interviewee, with the total out of 30. */
    public function signerValues(array $answers, array $values): array
    {
        $rows = self::interviewees($values) ?: [['name' => $values['student_name'] ?? null, 'number' => $values['student_number'] ?? null]];

        foreach (array_values($rows) as $i => $row) {
            $n = $i + 1;
            $answers["interviewee_{$n}_name"] = $row['name'] ?? null;
            $answers["interviewee_{$n}_number"] = $row['number'] ?? null;
            $answers["total_{$n}"] = (string) array_sum(array_map(
                fn ($key) => (int) ($answers["score_{$n}_{$key}"] ?? 0),
                array_keys(self::CRITERIA)
            ));
        }

        return $answers;
    }

    public function participants(array $values): array
    {
        $ids = [];
        foreach (self::interviewees($values) as $row) {
            $ids[$row['id']] = 'Interviewee';
        }

        return $ids;
    }

    public function externalOverviewFields(): array
    {
        return ['interviewees'];
    }

    public function formatValue(array $f, mixed $v): string
    {
        if (($f['type'] ?? '') === 'students') {
            return collect(self::interviewees(['interviewees' => $v]))
                ->map(fn ($r, $i) => ($i + 1) . '. ' . $r['name'] . ($r['number'] ? " ({$r['number']})" : ''))
                ->implode("\n");
        }

        return parent::formatValue($f, $v);
    }

    public function chain(array $values = []): array
    {
        return [
            ['type' => 'external', 'label' => 'Interviewer', 'name_field' => 'interviewer_name', 'email_field' => 'interviewer_email'],
        ];
    }

    public function pdfView(): string { return 'pdf.college-model-interview-rating'; }

    public function summary(array $values): string
    {
        $count = count(self::interviewees($values));

        return collect([
            $count ? $count . ' ' . ($count === 1 ? 'interviewee' : 'interviewees') : null,
            ! empty($values['interviewer_name']) ? 'Interviewer: ' . $this->personName($values, 'interviewer_name') : null,
        ])->filter()->implode(' · ') ?: 'Interview/Talent rating';
    }
}
