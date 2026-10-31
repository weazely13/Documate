<?php

namespace App\Workflow\Documents;

use Illuminate\Validation\Rule;

/**
 * F-SDM-018 Rev. 1 (03-31-23): Student Assistant Performance Evaluation Form.
 * The student assistant starts it and names their supervisor. The supervisor rates them on 10 criteria
 * while signing, from an emailed link or from their signatory account when the email belongs to one.
 * Older records were issued by the supervisor directly, with the ratings in the answers.
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

    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Requests; }

    public function key(): string { return 'student_assistant_evaluation'; }
    public function name(): string { return 'Student Assistant Performance Evaluation'; }
    public function description(): string { return 'Ask your supervisor to rate your performance as a student assistant.'; }
    public function formCode(): string { return 'F-SDM-018 Rev. 1 (03-31-23)'; }

    public function fields(): array
    {
        return [
            'office_area' => [
                'label' => 'Office/Area Assignment', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Registrar\'s Office',
            ],
        ] + $this->signerContactFields('supervisor', 'Supervisor', 'They get a link to rate and sign. If this email belongs to a DocuMate signatory account, they sign from their account instead.');
    }

    /** The supervisor fills these in while signing. */
    public function signerFields(array $values = []): array
    {
        $fields = [];
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

    /** The mean is stored with the ratings so it is sealed too. */
    public function signerValues(array $answers, array $values): array
    {
        $answers['mean_rating'] = self::mean($answers);

        return $answers;
    }

    public function chain(array $values = []): array
    {
        return [
            ['type' => 'external', 'label' => 'Supervisor', 'name_field' => 'supervisor_name', 'email_field' => 'supervisor_email'],
        ];
    }

    /** Shown to the supervisor above the ratings. */
    public function externalOverviewFields(): array { return ['office_area']; }

    public function pdfView(): string { return 'pdf.student-assistant-evaluation'; }

    public function summary(array $values): string
    {
        return 'SA evaluation' . (! empty($values['mean_rating']) ? ' · mean ' . $values['mean_rating'] : '');
    }
}