<?php

namespace App\Workflow\Documents;

final class DocumentRegistry
{
    /** @return array<string, WorkflowDocument> keyed by document key */
    public static function all(): array
    {
        static $documents = null;

        return $documents ??= collect([
            new AdmissionSlip(),
            new DisciplinaryActionSlip(),
            new DisciplinaryCaseReport(),
            new DisciplinaryReferralSlip(),
            new LostFoundItemForm(),
            new ModelStudentRating(),
            new PermitToEnterCampus(),
            new PermitToConductActivity(),
            new PermitToConductOffCampus(),
            new RequestForExcusedAbsence(),
            new StudentIdRenewal(),
            new StudentAppointmentSlip(),
        ])->keyBy(fn (WorkflowDocument $d) => $d->key())->all();
    }

    public static function has(?string $key): bool
    {
        return $key !== null && isset(self::all()[$key]);
    }

    public static function find(?string $key): ?WorkflowDocument
    {
        return $key !== null ? (self::all()[$key] ?? null) : null;
    }

    public static function get(?string $key): WorkflowDocument
    {
        return self::find($key) ?? abort(404, 'Unknown document type.');
    }
}