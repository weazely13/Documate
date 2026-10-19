<?php

namespace App\Workflow\Documents;

use App\Models\StudentDocumentWorkspace;
use Carbon\Carbon;
use App\Workflow\Documents\StudentAssistantshipApplication as SA;

/**
 * F-SDM-019 Rev. 1 (03-31-23): Student Assistantship Endorsement Form.
 * Issued BY the VPSD office TO a student assistant. The VPSD picks which of the student's
 * Student Assistantship Applications is the approved one; its schedule is copied in (and sealed).
 * The Vice President signs it.
 */
final class StudentAssistantshipEndorsement extends WorkflowDocument
{
    public function key(): string { return 'student_assistantship_endorsement'; }
    public function name(): string { return 'Student Assistantship Endorsement'; }
    public function description(): string { return 'Endorses a student assistant to an office, using their approved application. Issued by the VPSD office.'; }
    public function formCode(): string { return 'F-SDM-019 Rev. 1 (03-31-23)'; }

    public function issuedByOffice(): bool { return true; }

    public function sourceDocumentType(): ?string { return 'student_assistantship_application'; }

    public function fields(): array
    {
        $fields = [
            'office_name' => [
                'label' => 'Name of Office', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Registrar\'s Office',
            ],
            'start_date' => [
                'label' => 'Starting date', 'type' => 'date', 'required' => true,
            ],
            // Filled by fillFromSource(): hidden on the issue form.
            'source_application' => ['label' => 'Based on application', 'type' => 'text', 'max' => 40, 'hidden' => true],
            'source_application_id' => ['label' => 'Source application ID', 'type' => 'text', 'max' => 20, 'hidden' => true, 'staff_only' => true, 'sensitive' => true],
        ];

        foreach (SA::DAYS as $k => [$label]) {
            foreach (['am' => 'AM', 'pm' => 'PM'] as $h => $H) {
                foreach (['from', 'to'] as $p) {
                    $fields["{$k}_{$h}_{$p}"] = [
                        'label' => "{$label}: {$H} {$p}", 'type' => 'time', 'hidden' => true,
                        'group' => 'schedule', 'sensitive' => true,
                    ];
                }
            }
        }

        return $fields;
    }

    public function fillFromSource(array $answers, StudentDocumentWorkspace $source): array
    {
        $src = $source->field_values ?? [];

        foreach (SA::DAYS as $k => $_) {
            foreach (['am', 'pm'] as $h) {
                foreach (['from', 'to'] as $p) {
                    $t = $src["{$k}_{$h}_{$p}"] ?? null;
                    $answers["{$k}_{$h}_{$p}"] = $t ? substr((string) $t, 0, 5) : null;
                }
            }
        }

        $answers['source_application_id'] = (string) $source->workspace_id;
        $answers['source_application'] = (string) $source->transaction_code;

        return $answers;
    }

    public function check(array $values): ?string
    {
        return SA::weeklyTotal($values) > 0
            ? null
            : 'The chosen application has no vacant periods to copy.';
    }

    public function chain(array $values = []): array
    {
        return [['role' => 'vpsd', 'label' => 'Signed']];
    }

    public function issueNote(): ?string
    {
        return 'Choose the student, pick their approved application (its schedule is copied in), then enter the office and starting date. The student is notified, then the Vice President signs it.';
    }

    public function pdfView(): string { return 'pdf.student-assistantship-endorsement'; }

    public function issuedMessage(array $values): string
    {
        $office = trim((string) ($values['office_name'] ?? ''));
        $date = ! empty($values['start_date']) ? Carbon::parse($values['start_date'])->format('l, F j, Y') : null;

        return 'You were endorsed as a Student Assistant' . ($office !== '' ? " in the {$office}" : '')
            . ($date ? ", starting {$date}" : '') . '.';
    }

    public function summary(array $values): string
    {
        $date = ! empty($values['start_date']) ? Carbon::parse($values['start_date'])->format('M j, Y') : null;

        return collect([$values['office_name'] ?? null, $date ? "starts {$date}" : null])->filter()->implode(' · ');
    }

    public function showsAsNotice(): bool { return true; }
    public function noticeDate(array $values): ?string { return $values['start_date'] ?? null; }
    public function noticeTitle(array $values): string { return 'Student Assistant Endorsement'; }
    public function noticeLine(array $values): string { return $this->issuedMessage($values); }
    public function noticeTone(): string { return 'blue'; }
}