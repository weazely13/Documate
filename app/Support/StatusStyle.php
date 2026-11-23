<?php

namespace App\Support;

use App\Enums\TransactionStatus;
use Illuminate\Support\Str;

/**
 * The one set of status words and colours used across DocuMate, taken from Reports.
 *
 * One word each, the terms offices use. Requests use the TransactionStatus labels (Draft, Pending, Reviewing, Revision,
 * Signed, Completed, Rejected, Voided), plus Signing while the signatures are being collected (StatusStyle::state);
 * paper forms, which have no workflow, are Pending or
 * Completed. Clearance uses Cleared, Pending, Uncleared and Untagged. Appointments use Pending,
 * Approved, Attended, Missed, Rejected and Cancelled.
 *
 * Render a status with <x-status-badge :status="..."/>, :clearance="..." or :appointment="...".
 */
final class StatusStyle
{
    /** The pill: tint, deep text and a 200 ring. */
    public const BADGE = 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset';

    /** Status hues: tint, text and ring for the pill. */
    public const TONES = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'green' => 'bg-green-50 text-green-700 ring-green-200',
        'blue' => 'bg-blue-50 text-[#2A57B4] ring-blue-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];

    /** A solid dot or bar in the same hue. */
    public const DOTS = [
        'emerald' => 'bg-emerald-500',
        'green' => 'bg-green-500',
        'blue' => 'bg-[#2A57B4]',
        'indigo' => 'bg-indigo-500',
        'violet' => 'bg-violet-500',
        'amber' => 'bg-amber-500',
        'orange' => 'bg-orange-500',
        'rose' => 'bg-rose-500',
        'slate' => 'bg-slate-400',
    ];

    /** Tint and text as hex, for print (PDF) where Tailwind classes do not apply. */
    public const PRINT = [
        'emerald' => ['#DCFCE7', '#166534'],
        'green' => ['#DCFCE7', '#15803D'],
        'blue' => ['#DBEAFE', '#1E40AF'],
        'indigo' => ['#E0E7FF', '#3730A3'],
        'violet' => ['#EDE9FE', '#5B21B6'],
        'amber' => ['#FEF3C7', '#92400E'],
        'orange' => ['#FFEDD5', '#9A3412'],
        'rose' => ['#FFE4E6', '#9F1239'],
        'slate' => ['#F1F5F9', '#475569'],
    ];

    public const NOT_TAGGED = 'Untagged';

    /** Not a stored status: Reviewing once the signers have been asked (signing started, not finished yet). */
    public const SIGNING = 'signing';

    /**
     * Where a request stands, for the pill: the workflow value, 'signing' while the signatures are being
     * collected, or a paper form's 'pending' / 'completed'. Uses approvals_count / loaded approvals when present.
     */
    public static function state(\App\Models\StudentDocumentWorkspace $ws): string
    {
        $status = $ws->workflow_status;
        if (! $status) {
            return $ws->isCompleted() ? 'completed' : 'pending';
        }
        if ($status === TransactionStatus::UnderReview) {
            $signers = match (true) {
                isset($ws->approvals_count) => (int) $ws->approvals_count > 0,
                $ws->relationLoaded('approvals') => $ws->approvals->isNotEmpty(),
                (bool) $ws->getKey() => $ws->approvals()->exists(),
                default => false,
            };
            if ($signers) {
                return self::SIGNING;
            }
        }

        return $status->value;
    }

    /** Clearance status => [hue, icon]. */
    public const CLEARANCE = [
        'Cleared' => ['emerald', 'bx-check'],
        'Pending' => ['amber', 'bx-time-five'],
        'Uncleared' => ['rose', 'bx-x'],
        self::NOT_TAGGED => ['slate', 'bx-minus'],
    ];

    /** Appointment status (the `status` column) => [word, hue]. */
    public const APPOINTMENT = [
        'pending' => ['Pending', 'amber'],
        'approved' => ['Approved', 'blue'],
        'attended' => ['Attended', 'emerald'],
        'missed' => ['Missed', 'rose'],
        'rejected' => ['Rejected', 'rose'],
        'retracted' => ['Cancelled', 'slate'],
    ];

    /**
     * A request's status word and hue. Takes the enum, its raw value, or a paper form's legacy
     * `status` column ('pending' / 'completed'); anything unknown reads as Pending.
     *
     * @return array{label: string, tone: string}
     */
    public static function transaction(TransactionStatus|string|null $state): array
    {
        if ($state === self::SIGNING) {
            return ['label' => 'Signing', 'tone' => 'violet'];
        }
        $status = $state instanceof TransactionStatus ? $state : TransactionStatus::tryFrom((string) $state);

        if ($status) {
            return ['label' => $status->label(), 'tone' => $status->tone()];
        }

        return (string) $state === 'completed'
            ? ['label' => 'Completed', 'tone' => 'emerald']
            : ['label' => 'Pending', 'tone' => 'amber'];
    }

    /** A paper form (no workflow) is Pending until it is completed. */
    public static function paper(bool $completed): array
    {
        return self::transaction($completed ? 'completed' : 'pending');
    }

    /**
     * A clearance status word, hue and icon. "Untagged", "Not Tagged", "none", null and '' all read as Untagged.
     *
     * @return array{label: string, tone: string, icon: string}
     */
    public static function clearance(?string $status): array
    {
        $label = self::clearanceLabel($status);
        [$tone, $icon] = self::CLEARANCE[$label];

        return ['label' => $label, 'tone' => $tone, 'icon' => $icon];
    }

    public static function clearanceLabel(?string $status): string
    {
        $key = Str::headline((string) $status);

        return match ($key) {
            'Cleared', 'Pending', 'Uncleared' => $key,
            default => self::NOT_TAGGED,
        };
    }

    /**
     * An appointment's status word and hue; an unknown value keeps its own word, in slate.
     *
     * @return array{label: string, tone: string}
     */
    public static function appointment(?string $status): array
    {
        [$label, $tone] = self::APPOINTMENT[(string) $status] ?? [Str::headline((string) $status) ?: 'Pending', 'slate'];

        return ['label' => $label, 'tone' => $tone];
    }

    /** The pill's classes for a hue. */
    public static function classes(string $tone): string
    {
        return self::BADGE . ' ' . (self::TONES[$tone] ?? self::TONES['slate']);
    }

    /** Print colours [tint, text] for a status word as Reports shows it (requests, clearance or appointments). */
    public static function printColours(?string $label): array
    {
        $label = (string) $label;
        $tone = match (true) {
            isset(self::CLEARANCE[$label]) => self::CLEARANCE[$label][0],
            $label === 'Signing' => 'violet',
            default => collect(TransactionStatus::cases())->first(fn ($s) => $s->label() === $label)?->tone()
                ?? collect(self::APPOINTMENT)->first(fn ($a) => $a[0] === $label)[1] ?? 'slate',
        };

        return self::PRINT[$tone] ?? self::PRINT['slate'];
    }
}
