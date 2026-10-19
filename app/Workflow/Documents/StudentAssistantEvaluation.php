<?php

namespace App\Workflow\Documents;

use Illuminate\Validation\Rule;

/**
 * F-SDM-018 Rev. 1 (03-31-23): Student Assistant Performance Evaluation Form.
 * The supervisor (any current signatory) rates the student assistant on 10 criteria. Only the
 * supervisor signs (on the paper form too), so the chain is empty: their signature is captured
 * when they issue it, then the VPSD does the final verification.
 */
final class StudentAssistantEvaluation extends WorkflowDocument
{
    /** field key => criterion label, in form order (1-5 left column, 6-10 right column). */
    public const CRITERIA = [
        'rate_communication' => 'Communication Skills',
        'rate_courtesy' => 'Courtesy (good manners/politeness)',
        'rate_decision' => 'Decision-making',
        'rate_dependability' => 'Dependability (reliability/loyalty)',
        'rate_honesty' => 'Honesty (sincerity/truthfulness)',
        'rate_self_reliance' => 'Self-reliance (independence/self-sufficiency)',
        'rate_knowledge' => 'Knowledge of Work',
        'rate_appearance' => 'Personal Appearance',
        'rate_responsiveness' => 'Responsiveness (openness/sensitivity)',
        'rate_timeliness' => 'Timeliness (punctuality and attendance)',
    ];

    public const SCALE = [5 => 'Outstanding', 4 => 'Very Satisfactory', 3 => 'Satisfactory', 2 => 'Good', 1 => 'Poor'];

    public const RENEW = 'Recommended for Renewal of Service as Student Assistant';
    public const NOT_RENEW = 'Not Recommended';

    public function key(): string { return 'student_assistant_evaluation'; }
    public function name(): string { return 'Student Assistant Performance Evaluation'; }
    public function description(): string { return 'A supervisor\'s performance rating of a student assistant.'; }
    public function formCode(): string { return 'F-SDM-018 Rev. 1 (03-31-23)'; }

    /** The student never starts it. */
    public function issuedByOffice(): bool { return true; }

    /** Any current signatory may issue it. Also triggers the issuer snapshot (name, role, signature). */
    public function issuedBySignatory(): bool { return true; }

    public function fields(): array
    {
        $fields = [
            'office_area' => [
                'label' => 'Office/Area Assignment', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Registrar\'s Office',
            ],
        ];

        $i = 1;
        foreach (self::CRITERIA as $key => $label) {
            $fields[$key] = [
                'label' => "{$i}. {$label}", 'type' => 'radio', 'required' => true,
                'options' => ['5', '4', '3', '2', '1'],
                'option_labels' => ['5' => 'Outstanding', '4' => 'Very Satisfactory', '3' => 'Satisfactory', '2' => 'Good', '1' => 'Poor'],
                'rules' => [Rule::in(['5', '4', '3', '2', '1'])],
                'sensitive' => true,
            ];
            $i++;
        }

        $fields['recommendation'] = [
            'label' => 'Recommendation', 'type' => 'select', 'required' => true,
            'options' => [self::RENEW, self::NOT_RENEW],
            'rules' => [Rule::in([self::RENEW, self::NOT_RENEW])],
            'sensitive' => true,
        ];
        $fields['comments'] = [
            'label' => 'Comment/s', 'type' => 'textarea', 'max' => 500, 'sensitive' => true,
        ];

        return array_map(fn ($f) => array_filter($f, fn ($v) => $v !== null), $fields);
    }

    /** Mean of the ten ratings, e.g. "4.30". Stored with the answers so it is sealed. */
    public static function mean(array $values): ?string
    {
        $sum = 0;
        foreach (array_keys(self::CRITERIA) as $key) {
            $n = (int) ($values[$key] ?? 0);
            if ($n < 1 || $n > 5) {
                return null;
            }
            $sum += $n;
        }

        return number_format($sum / count(self::CRITERIA), 2);
    }

    public function beforeIssue(array $answers): array
    {
        $answers['mean_rating'] = self::mean($answers);

        return $answers;
    }

    public function check(array $values): ?string
    {
        if (self::mean($values) === null) {
            return 'Rate all ten criteria from 1 to 5.';
        }

        $user = auth()->user();
        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this evaluation.';
        }

        return null;
    }

    /** No approval steps: the supervisor's signature is captured at issue time. */
    public function chain(array $values = []): array { return []; }

    public function issueNote(): ?string
    {
        return 'Rate the student assistant on all ten criteria. The mean rating is computed for you. You appear on the form as the supervisor (rater), signed with your profile signature. The VPSD office then does the final verification.';
    }

    public function pdfView(): string { return 'pdf.student-assistant-evaluation'; }

    public function issuedMessage(array $values): string
    {
        return 'Your student assistant performance evaluation was issued. Once the VPSD office completes it, you can download the PDF from your Documents page.';
    }

    public function summary(array $values): string
    {
        return 'SA evaluation' . (! empty($values['mean_rating']) ? ' · mean ' . $values['mean_rating'] : '');
    }
}