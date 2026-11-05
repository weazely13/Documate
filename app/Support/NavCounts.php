<?php

namespace App\Support;

use App\Enums\TransactionStatus;
use App\Models\Appointment;
use App\Models\StudentDocumentWorkspace;
use App\Models\StudentVerification;
use App\Models\User;

/**
 * The numbers on the sidebar: how much is waiting on each page. Only work someone has to act on is counted,
 * so a number always means "there is something for you here" (none shows when it is 0).
 */
class NavCounts
{
    /** Admin, Appointments: requests still waiting for approval (today on). */
    public static function pendingAppointments(): int
    {
        return Appointment::query()->where('status', 'pending')->whereDate('appointment_date', '>=', today())->count();
    }

    /** Admin, Templates & Uploads: signed paper forms brought in whose photo still has to be uploaded (or re-uploaded). */
    public static function uploadsToDo(): int
    {
        return StudentDocumentWorkspace::query()
            ->whereNull('workflow_status')
            ->whereHas('template.currentVersion')
            ->whereHas('appointments', fn ($q) => $q->where('status', 'attended'))
            ->where(fn ($q) => $q->whereNull('analysis_status')->orWhere('analysis_status', '!=', 'approved'))
            ->where(fn ($q) => $q->whereNull('processing')->orWhere('processing', false))
            ->count();
    }

    /** Admin, Users Manager: enrollment-slip verifications waiting for a person to check them. */
    public static function pendingVerifications(): int
    {
        return StudentVerification::query()->where('status', 'pending')->count();
    }

    /** Student, Appointments: visits still ahead (waiting for approval, or approved). */
    public static function upcomingAppointments(User $user): int
    {
        return Appointment::query()->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('appointment_date', '>=', today())
            ->count();
    }

    /** Student, Clearance Status: 1 when they are tagged Uncleared for the current semester. */
    public static function uncleared(User $user): int
    {
        $sem = \App\Models\Semester::current();

        return $sem && \App\Models\ClearanceStatus::query()->where('user_id', $user->id)->where('semester_id', $sem->id)
            ->latest('id')->value('status') === 'Uncleared' ? 1 : 0;
    }

    /** Officer, Clearance Tagging: members of their organization not yet tagged this semester. */
    public static function untaggedMembers(User $user): int
    {
        $sem = \App\Models\Semester::current();
        if (! $sem || ! $user->organization_id) {
            return 0;
        }

        return User::query()
            ->where('organization_id', $user->organization_id)
            ->whereHas('role', fn ($r) => $r->whereIn('role_name', ['Student', 'Officer']))
            ->whereDoesntHave('clearanceStatuses', fn ($c) => $c->where('semester_id', $sem->id))
            ->count();
    }

    /** Signatory, Issued Documents: reports they issued that came back for changes. */
    public static function issuedNeedingChanges(User $user): int
    {
        return StudentDocumentWorkspace::usingWorkflow()->issuedBy($user)
            ->where('workflow_status', TransactionStatus::ActionRequired->value)
            ->count();
    }
}
