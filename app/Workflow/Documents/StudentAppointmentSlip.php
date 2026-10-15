<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

/**
 * F-SDM-005 Rev. 1 (03-31-23): Student Appointment Slip Form.
 * Issued BY the VPSD office TO a student (the student never starts it). The office fills in
 * every field, the transaction goes straight to signing, and the Vice President signs it.
 */
final class StudentAppointmentSlip extends WorkflowDocument
{
    public function key(): string
    {
        return 'student_appointment_slip';
    }

    public function name(): string
    {
        return 'Student Appointment Slip';
    }

    public function description(): string
    {
        return 'A summons from the Vice President for Student Development, issued by the VPSD office.';
    }

    public function formCode(): string
    {
        return 'F-SDM-005 Rev. 1 (03-31-23)';
    }

    public function issuedByOffice(): bool
    {
        return true;
    }

    /** Filled in by the VPSD office when issuing (not by the student). */
    public function fields(): array
    {
        return [
            'subject' => [
                'label' => 'RE (subject of the meeting)', 'type' => 'text', 'required' => true, 'max' => 200,
                'placeholder' => 'e.g. Clarification regarding your absences',
                'sensitive' => true, // hidden on the public verification page
            ],
            'appointment_date' => [
                'label' => 'Date', 'type' => 'date', 'required' => true,
                'rules' => ['after_or_equal:today'],
            ],
            'appointment_time' => [
                'label' => 'Time', 'type' => 'time', 'required' => true,
            ],
        ];
    }

    public function chain(array $values = []): array
    {
        return [
            ['role' => 'vpsd', 'label' => 'Signed'],
        ];
    }

    public function pdfView(): string
    {
        return 'pdf.student-appointment-slip';
    }

    public function summary(array $values): string
    {
        $date = ! empty($values['appointment_date']) ? Carbon::parse($values['appointment_date'])->format('M j, Y') : null;
        $time = ! empty($values['appointment_time'])
            ? Carbon::createFromFormat('H:i', substr((string) $values['appointment_time'], 0, 5))->format('g:i A')
            : null;

        return collect([$values['subject'] ?? null, trim($date . ' ' . $time)])->filter()->implode(' · ');
    }
    public function showsAsNotice(): bool
    {
        return true;
    }

    public function noticeDate(array $values): ?string
    {
        return $values['appointment_date'] ?? null;
    }

    public function noticeTitle(array $values): string
    {
        return 'Student Appointment';
    }

    public function issuedMessage(array $values): string
    {
        $date = ! empty($values['appointment_date'])
            ? Carbon::parse($values['appointment_date'])->format('l, F j, Y') : null;
        $time = ! empty($values['appointment_time'])
            ? Carbon::createFromFormat('H:i', substr((string) $values['appointment_time'], 0, 5))->format('g:i A') : null;

        return 'A student appointment slip was issued to you. See the Vice President for Student Development'
            . ($time ? " at {$time}" : '')
            . ($date ? " on {$date}" : '') . '.';
    }

    public function noticeLine(array $values): string
    {
        return $this->issuedMessage($values);
}
}