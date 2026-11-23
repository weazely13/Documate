<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * What the words in a table column mean, for the (i) beside its header (<x-admin.legend-tip :items>).
 * Each item is [dot class, word, range or '', meaning], in the colours of the pills the column shows.
 */
final class Legends
{
    /** A transaction's status pill, online and paper, in the order work moves. */
    public static function transactionStatuses(): array
    {
        return collect([
            'submitted' => 'Sent by the student. The office has not opened it yet.',
            'under_review' => 'The office is checking the answers.',
            StatusStyle::SIGNING => 'Sent to the signatories. Waiting for their signatures.',
            'action_required' => 'Sent back to the student to fix and resubmit.',
            'approved' => 'Everyone has signed. Ready for the office to complete and issue.',
            'pending' => 'A paper form that has not been marked complete yet.',
            'completed' => 'Finished. The record is sealed and the official copy can be issued.',
            'rejected' => 'Turned down. The student would need to start a new transaction.',
            'voided' => 'A completed document the office withdrew. Kept for the record.',
        ])->map(function (string $meaning, string $state) {
            $style = StatusStyle::transaction($state);

            return [StatusStyle::DOTS[$style['tone']] ?? 'bg-slate-400', $style['label'], '', $meaning];
        })->values()->all();
    }

    /** How a transaction was made. */
    public static function modes(): array
    {
        return [
            ['bg-[#2A57B4]', 'Online', '', 'Filled in and signed in DocuMate, start to finish.'],
            ['bg-slate-400', 'Paper', '', 'A printed form, scanned and uploaded to DocuMate.'],
        ];
    }

    /** A clearance status pill. */
    public static function clearance(): array
    {
        return collect([
            StatusStyle::NOT_TAGGED => 'No status has been tagged for this semester yet.',
            'Pending' => 'The organization is still verifying the student\'s records.',
            'Cleared' => 'All requirements are fulfilled.',
            'Uncleared' => 'Requirements are incomplete. The remarks say what is due.',
        ])->map(function (string $meaning, string $status) {
            $style = StatusStyle::clearance($status);

            return [StatusStyle::DOTS[$style['tone']] ?? 'bg-slate-400', $style['label'], '', $meaning];
        })->values()->all();
    }

    /** The audit trail's action pills, in the trail's colours. */
    public static function auditActions(): array
    {
        $meanings = [
            'login' => ['bg-emerald-500', 'Someone signed in.'],
            'logout' => ['bg-slate-400', 'Someone signed out.'],
            'login_failed' => ['bg-rose-500', 'A sign-in failed, for example a wrong password.'],
            'created' => ['bg-[#2A57B4]', 'A record was added.'],
            'updated' => ['bg-amber-500', 'A record was changed. Open the row to see before and after.'],
            'deleted' => ['bg-rose-500', 'A record was removed.'],
            'restored' => ['bg-[#2A57B4]', 'A removed record was brought back.'],
            'exported' => ['bg-slate-400', 'Data was downloaded, such as a CSV or report.'],
            'workflow' => ['bg-indigo-500', 'A step in a transaction: submitted, reviewed, signed, completed and so on.'],
        ];

        return collect(AuditLog::ACTIONS)
            ->map(fn (string $label, string $key) => [$meanings[$key][0] ?? 'bg-slate-400', $label, '', $meanings[$key][1] ?? ''])
            ->values()->all();
    }

    /** The audit trail's module chips, in the trail's colours. */
    public static function auditModules(): array
    {
        return [
            ['bg-emerald-500', 'Authentication', '', 'Signing in and out, and failed sign-ins.'],
            ['bg-indigo-500', 'Transactions', '', 'Online and paper transactions and every step in them.'],
            ['bg-violet-500', 'Appointments', '', 'Appointment requests, approvals and office limits.'],
            ['bg-teal-500', 'Clearance', '', 'Clearance tags for each student and semester.'],
            ['bg-sky-500', 'Users', '', 'Accounts: added, changed, deactivated or removed.'],
            ['bg-lime-500', 'Verification', '', 'Student account verifications.'],
            ['bg-amber-500', 'Semesters & Verification', '', 'Semesters, their dates and the current one.'],
            ['bg-orange-500', 'Templates', '', 'Paper form templates and their versions.'],
            ['bg-fuchsia-500', 'Signatories', '', 'Signing roles and who holds them.'],
            ['bg-cyan-500', 'Organizations', '', 'Student organizations and their officers.'],
            ['bg-pink-500', 'Feedback', '', 'Feedback from users and the office\'s replies.'],
            ['bg-slate-400', 'Reports', '', 'Reports viewed, saved or downloaded.'],
            ['bg-slate-400', 'Audit Trail', '', 'Downloads of this trail.'],
        ];
    }
}
