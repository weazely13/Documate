<?php

namespace App\Support\Charts;

/**
 * The Reports page's left-hand chart, shared by the page and the PDF so both draw it alike:
 * a pie of requests by status (forms) or of cleared students by organization / one
 * organization's statuses (organizations), and the cleared-by-year-level columns (clearance).
 */
final class ReportPie
{
    /** Request states and clearance statuses, in the hues of the status badges. */
    public const STATUS = [
        'pending' => '#fcd34d', 'submitted' => '#a5b4fc', 'under_review' => '#6366f1', 'action_required' => '#f59e0b',
        'approved' => '#2A57B4', 'completed' => '#10b981', 'rejected' => '#f43f5e', 'voided' => '#fda4af',
        'Cleared' => '#10b981', 'Pending' => '#fbbf24', 'Uncleared' => '#f43f5e', 'Not tagged' => '#cbd5e1',
    ];

    /** Organizations take these in a fixed order (the categorical palette). */
    public const PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6'];

    private const GREY = '#94a3b8';

    /**
     * The slices of a sideChart() pie, each with its colour, share (%) and SVG path on a
     * 200 × 200 box ('circle' for a whole pie, null for an empty slice).
     *
     * @return array<int, array{label: string, value: int, of: int, pick: ?string, key: ?string, colour: string, share: int, path: ?string}>
     */
    public static function slices(array $chart): array
    {
        $total = max(0, (int) $chart['total']);
        $hue = 0;
        $angle = -M_PI / 2;
        $point = fn (float $a) => round(100 + 96 * cos($a), 2) . ' ' . round(100 + 96 * sin($a), 2);

        return array_map(function (array $sl) use ($total, &$hue, &$angle, $point) {
            $key = $sl['key'] ?? null;
            $sl['colour'] = $key !== null
                ? (self::STATUS[$key] ?? self::GREY)
                : ($sl['label'] === 'No organization' || str_starts_with($sl['label'], 'Other (') ? self::GREY : self::PALETTE[$hue++ % count(self::PALETTE)]);

            $sweep = $total ? $sl['value'] / $total * 2 * M_PI : 0;
            $sl['path'] = $sweep <= 0 ? null : ($sweep >= 2 * M_PI - 0.0001 ? 'circle'
                : 'M100 100 L' . $point($angle) . ' A96 96 0 ' . ($sweep > M_PI ? 1 : 0) . ' 1 ' . $point($angle + $sweep) . ' Z');
            $sl['share'] = $total ? (int) round($sl['value'] / $total * 100) : 0;
            $angle += $sweep;

            return $sl;
        }, $chart['slices']);
    }

    /** The pie as a standalone SVG, for the PDF. */
    public static function svg(array $slices): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">';
        foreach ($slices as $sl) {
            $svg .= match ($sl['path']) {
                null => '',
                'circle' => '<circle cx="100" cy="100" r="96" fill="' . $sl['colour'] . '"/>',
                default => '<path d="' . $sl['path'] . '" fill="' . $sl['colour'] . '" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/>',
            };
        }

        return $svg . '</svg>';
    }

    /** The cleared-by-year-level columns as a standalone SVG, for the PDF. */
    public static function columns(array $rows): string
    {
        $w = 700;
        $h = 170;
        $font = 'font-family="Arial, Helvetica, sans-serif"';
        $plotTop = 22;
        $plotBottom = $h - 34;
        $slot = $w / max(1, count($rows));
        $barW = min(70, $slot * 0.5);

        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', $w, $h, $w, $h);
        $svg .= sprintf('<line x1="0" y1="%d" x2="%d" y2="%d" stroke="#94A3B8" stroke-width="1"/>', $plotBottom, $w, $plotBottom);
        foreach (array_values($rows) as $i => $y) {
            $share = $y['total'] ? $y['cleared'] / $y['total'] : 0;
            $x = $i * $slot + ($slot - $barW) / 2;
            $cx = $i * $slot + $slot / 2;
            $barH = ($plotBottom - $plotTop) * $share;
            $svg .= sprintf('<rect x="%.1f" y="%d" width="%.1f" height="%d" fill="#F1F5F9"/>', $x, $plotTop, $barW, $plotBottom - $plotTop);
            if ($barH > 0) {
                $svg .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="#10b981"/>', $x, $plotBottom - $barH, $barW, $barH);
            }
            $svg .= sprintf('<text x="%.1f" y="%d" font-size="15" font-weight="bold" fill="#0F172A" text-anchor="middle" %s>%d%%</text>', $cx, $plotTop - 6, $font, round($share * 100));
            $svg .= sprintf('<text x="%.1f" y="%d" font-size="14" fill="#334155" text-anchor="middle" %s>%s</text>', $cx, $plotBottom + 16, $font, htmlspecialchars($y['label']));
            $svg .= sprintf('<text x="%.1f" y="%d" font-size="13" fill="#64748B" text-anchor="middle" %s>%d / %d</text>', $cx, $plotBottom + 31, $font, $y['cleared'], $y['total']);
        }

        return $svg . '</svg>';
    }
}
