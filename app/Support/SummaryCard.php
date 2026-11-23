<?php

namespace App\Support;

/**
 * The summary cards at the top of Workflow Review, Appointments, Clearance Monitoring, Feedback and
 * Audit Trail: one look everywhere. Every card is soft blue; the card that is selected (the filter in use) is
 * soft yellow. Dark text on both, a faint icon in the corner.
 */
final class SummaryCard
{
    /** Soft blue: a card, or one that can be picked but is not. */
    public const IDLE = 'bg-[#e8f0fb] border-transparent hover:brightness-[0.98]';

    /** Soft yellow: the card that is picked right now. */
    public const ACTIVE = 'bg-[#fdf3cf] border-amber-300 shadow-[0_0_0_1px_#fcd34d]';

    /** The shape every summary card shares (add IDLE or ACTIVE). */
    public const BOX = 'relative isolate flex flex-col overflow-hidden rounded-2xl border p-3.5 text-left text-slate-900 transition sm:p-4';

    /** The faint icon in the corner. */
    public const ICON = 'pointer-events-none absolute -bottom-3 -right-2 -z-10 text-6xl text-slate-900/[0.07]';

    public static function tone(bool $active): string
    {
        return $active ? self::ACTIVE : self::IDLE;
    }
}
