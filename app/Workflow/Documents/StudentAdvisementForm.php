<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;
use Illuminate\Validation\Rule;

/**
 * F-SDM-014 Rev. 1 (03-31-23): Student Advisement Form.
 * A faculty member, security guard or other signatory advises a student to see the University
 * Doctor, to have a haircut/mustache shave and report to the VPSD, or something else.
 * Same shape as the Disciplinary Referral Slip: the issuer's signature is captured at issue time.
 */
final class StudentAdvisementForm extends WorkflowDocument
{
    public function key(): string
    {
        return 'student_advisement_form';
    }

    public function name(): string
    {
        return 'Student Advisement Form';
    }

    public function description(): string
    {
        return 'An advisement to a student: see the University Doctor, have a haircut/mustache shave, or other.';
    }

    public function formCode(): string
    {
        return 'F-SDM-014 Rev. 1 (03-31-23)';
    }

    /** The student never starts it. */
    public function issuedByOffice(): bool
    {
        return true;
    }

    /** Any current signatory (faculty, guard, personnel) may issue it. */
    public function issuedBySignatory(): bool
    {
        return true;
    }

    public function fields(): array
    {
        return [
            'advise_doctor' => ['label' => 'Advised to see the University Doctor', 'type' => 'checkbox', 'sensitive' => true],
            'doctor_reason' => [
                'label' => 'Reason for seeing the University Doctor', 'type' => 'text', 'max' => 200,
                'sensitive' => true,
                'show_when' => ['advise_doctor' => true],
            ],
            'advise_haircut' => ['label' => 'Advised to have a haircut/mustache shave and report to the VPSD', 'type' => 'checkbox'],
            'haircut_report_date' => [
                'label' => 'Enter the date the student must report to the VPSD', 'type' => 'date',
                'future' => true,
                'rules' => ['after_or_equal:today'],
                'show_when' => ['advise_haircut' => true],
            ],
            'haircut_report_session' => [
                'label' => 'Session to report to the VPSD', 'type' => 'select',
                'options' => ['Morning', 'Afternoon'],
                'rules' => [Rule::in(['Morning', 'Afternoon'])],
                'help' => 'Morning 8:00 AM to 12:00 PM, afternoon 1:00 PM to 5:00 PM.',
                'show_when' => ['advise_haircut' => true],
            ],
            'advise_others' => ['label' => 'Others', 'type' => 'checkbox'],
            'others_specify' => [
                'label' => 'If others, please specify', 'type' => 'text', 'max' => 250,
                'show_when' => ['advise_others' => true],
            ],
        ];
    }

    public function beforeIssue(array $answers): array
    {
        if (empty($answers['advise_doctor'])) {
            $answers['doctor_reason'] = null;
        }
        if (empty($answers['advise_haircut'])) {
            $answers['haircut_report_date'] = null;
            $answers['haircut_report_session'] = null;
        }
        if (empty($answers['advise_others'])) {
            $answers['others_specify'] = null;
        }

        return $answers;
    }
    public function check(array $values): ?string
    {
        $doctor = ! empty($values['advise_doctor']);
        $haircut = ! empty($values['advise_haircut']);
        $others = ! empty($values['advise_others']);

        if (! $doctor && ! $haircut && ! $others) {
            return 'Tick at least one advisement.';
        }
        if ($doctor && trim((string) ($values['doctor_reason'] ?? '')) === '') {
            return 'Enter the reason for seeing the University Doctor.';
        }
        if ($haircut) {
            if (empty($values['haircut_report_date'])) {
                return 'Enter the date the student must report to the VPSD.';
            }
            if (empty($values['haircut_report_session'])) {
                return 'Choose the session the student must report to the VPSD.';
            }
            if (\App\Models\OfficeAvailability::isWeekend($values['haircut_report_date'])) {
                return 'The VPSD office is closed on Saturdays and Sundays. Choose a weekday.';
            }
        }
        if ($others && trim((string) ($values['others_specify'] ?? '')) === '') {
            return 'Please specify the "Others" advisement.';
        }

        $user = auth()->user();
        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this form.';
        }

        return null;
    }

    /** Only the haircut advisement needs a VPSD inspection. */
    public function chain(array $values = []): array
    {
        return empty($values['advise_haircut'])
            ? []
            : [['role' => 'vpsd', 'label' => 'Inspected']];
    }

    public function signerRecordsInspection(): bool
    {
        return true;
    }

    public function signingNote(): ?string
    {
        return 'Inspect the student first, then record your remarks in the signing box. Signing marks the inspection as done.';
    }

    public function appointment(array $values): ?array
    {
        if (empty($values['advise_haircut']) || empty($values['haircut_report_date'])) {
            return null;
        }

        return [
            'date' => $values['haircut_report_date'],
            'session' => strtolower((string) ($values['haircut_report_session'] ?? 'morning')),
            'purpose' => 'Haircut/mustache inspection (Student Advisement Form)',
        ];
    }

    public function issueNote(): ?string
    {
        return 'The student is notified right away. You appear on the form as the advising staff member, signed with your profile signature. The VPSD office then does the final verification.';
    }

    public function pdfView(): string
    {
        return 'pdf.student-advisement-form';
    }

    public function issuedMessage(array $values): string
    {
        $parts = [];

        if (! empty($values['advise_doctor'])) {
            $parts[] = 'see the University Doctor';
        }
        if (! empty($values['advise_haircut'])) {
            $date = ! empty($values['haircut_report_date'])
                ? ' on ' . Carbon::parse($values['haircut_report_date'])->format('l, F j, Y')
                . ' (' . strtolower($values['haircut_report_session'] ?? '') . ')'
                : '';
            $parts[] = 'have a haircut/mustache shave and report to the VPSD' . $date;
        }
        if (! empty($values['advise_others'])) {
            $parts[] = 'follow an additional advisement (open it to read the details)';
        }

        return 'A student advisement form was issued to you. You are advised to ' . implode('; ', $parts) . '.';
    }

    public function summary(array $values): string
    {
        $parts = [];
        if (! empty($values['advise_doctor'])) $parts[] = 'doctor';
        if (! empty($values['advise_haircut'])) $parts[] = 'haircut';
        if (! empty($values['advise_others'])) $parts[] = 'others';

        return 'Advisement: ' . implode(', ', $parts);
    }

    /* ---- Dashboard "Dates to Remember" card ---- */

    public function showsAsNotice(): bool
    {
        return true;
    }

    public function noticeDate(array $values): ?string
    {
        return ! empty($values['advise_haircut']) ? ($values['haircut_report_date'] ?? null) : null;
    }

    public function noticeTitle(array $values): string
    {
        return 'Student Advisement';
    }

    public function noticeLine(array $values): string
    {
        return $this->issuedMessage($values);
    }

    public function noticeTone(): string
    {
        return 'blue';
    }
}