<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-008 Rev. 1 (03-31-23): Disciplinary Case Report Form.
 * Issued by the VPSD office. Chain: the Discipline Committee Chairman signs in DocuMate,
 * then each member signs through an emailed link that the Chairman sends by hand.
 */
final class DisciplinaryCaseReport extends WorkflowDocument
{
    private const MAX_MEMBERS = 5;

    public function key(): string
    {
        return 'disciplinary_case_report';
    }

    public function name(): string
    {
        return 'Disciplinary Case Report';
    }

    public function description(): string
    {
        return 'The Discipline Committee\'s report on a case, issued by the VPSD office.';
    }

    public function formCode(): string
    {
        return 'F-SDM-008 Rev. 1 (03-31-23)';
    }

    public function issuedByOffice(): bool
    {
        return true;
    }

    public function manualExternalInvites(): bool
    {
        return true;
    }

    public function fields(): array
    {
        $fields = [
            'violation' => [
                'label' => 'Violation', 'type' => 'textarea', 'required' => true, 'max' => 400, 'sensitive' => true,
            ],
            'incident_report' => [
                'label' => 'Incident report', 'type' => 'textarea', 'required' => true, 'max' => 2000, 'sensitive' => true,
            ],
            'resolution' => [
                'label' => 'Resolution', 'type' => 'textarea', 'required' => true, 'max' => 2000, 'sensitive' => true,
            ],
            'rec_closed' => ['label' => 'Recommendation: Closed/Resolved', 'type' => 'checkbox', 'sensitive' => true],
            'rec_hearing' => ['label' => 'Recommendation: For continuing hearing', 'type' => 'checkbox', 'sensitive' => true],
            'hearing_date' => [
                'label' => 'Hearing date', 'type' => 'date', 'sensitive' => true,
                'help' => 'Required if "For continuing hearing" is ticked.',
                'rules' => ['after_or_equal:today'],
            ],
            'rec_formal' => ['label' => 'Recommendation: For formal investigation', 'type' => 'checkbox', 'sensitive' => true],
        ];

        for ($i = 1; $i <= self::MAX_MEMBERS; $i++) {
            $fields["member_{$i}_name"] = [
                'label' => "Member {$i} name", 'type' => 'text', 'max' => 120, 'required' => $i === 1,
                'placeholder' => 'e.g. Prof. Juan Dela Cruz',
            ];
            $fields["member_{$i}_email"] = [
                'label' => "Member {$i} email", 'type' => 'text', 'max' => 150, 'required' => $i === 1,
                'placeholder' => 'name@example.com',
                'sensitive' => true,   // hidden on the public verification page
                'staff_only' => true,  // hidden from the student's view
                'rules' => ['email:rfc'],
            ] + ($i === 1 ? ['help' => 'The secure signing link goes here. Leave unused member rows blank.'] : []);
        }

        return $fields;
    }

    public function check(array $values): ?string
    {
        if (empty($values['rec_closed']) && empty($values['rec_hearing']) && empty($values['rec_formal'])) {
            return 'Tick at least one recommendation.';
        }
        if (! empty($values['rec_hearing']) && empty($values['hearing_date'])) {
            return 'Enter the hearing date for the continuing hearing.';
        }

        $count = 0;
        $seen = [];
        for ($i = 1; $i <= self::MAX_MEMBERS; $i++) {
            $name = trim((string) ($values["member_{$i}_name"] ?? ''));
            $email = strtolower(trim((string) ($values["member_{$i}_email"] ?? '')));

            if ($name === '' && $email === '') {
                continue;
            }
            if ($name === '' || $email === '') {
                return "Member {$i} needs both a name and an email.";
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Member {$i}'s email address is not valid.";
            }
            if (isset($seen[$email])) {
                return 'Two members have the same email address.';
            }
            $seen[$email] = true;
            $count++;
        }

        return $count === 0 ? 'Add at least one committee member.' : null;
    }

    /** Chairman first, then one external step per filled member row. */
    public function chain(array $values = []): array
    {
        $steps = [['role' => 'discipline_chairman', 'label' => 'Report Given By']];

        for ($i = 1; $i <= self::MAX_MEMBERS; $i++) {
            $name = trim((string) ($values["member_{$i}_name"] ?? ''));
            $email = trim((string) ($values["member_{$i}_email"] ?? ''));

            if ($name === '' && $email === '') {
                continue;
            }

            $steps[] = [
                'type' => 'external', 'kind' => 'review', 'label' => "Member {$i}",
                'name_field' => "member_{$i}_name", 'email_field' => "member_{$i}_email",
            ];
        }

        return $steps;
    }

    public function externalOverviewFields(): array
    {
        $keys = ['violation', 'incident_report', 'resolution', 'rec_closed', 'rec_hearing', 'hearing_date', 'rec_formal'];

        for ($i = 1; $i <= self::MAX_MEMBERS; $i++) {
            $keys[] = "member_{$i}_name"; // never the email fields
        }

        return $keys;
    }

    public function pdfView(): string
    {
        return 'pdf.disciplinary-case-report';
    }

    /** Shown in the student's notification, so keep it free of case details. */
    public function summary(array $values): string
    {
        return 'Disciplinary case report';
    }
    public function issuedByRole(): ?string
    {
        return 'discipline_chairman';
    }

    public function showsAsNotice(): bool
    {
        return true;
    }

    public function noticeDate(array $values): ?string
    {
        return ! empty($values['rec_hearing']) ? ($values['hearing_date'] ?? null) : null;
    }

    public function noticeTitle(array $values): string
    {
        return 'Disciplinary Case Report';
    }
    public function issueNote(): ?string
    {
        return 'The student is notified right away and can read it from their dashboard. You sign it next, then send it to the committee members.';
    }

    /** The dashboard is glanceable, so no case details here. */
    public function noticeLine(array $values): string
    {
        return ! empty($values['rec_hearing']) ? 'Continuing hearing. Tap to read the report.' : 'Tap to read the report.';
    }

    public function noticeTone(): string
    {
        return 'rose';
    }
}