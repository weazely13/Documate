<?php

namespace App\Support\Charts;

/**
 * Zero-dependency SVG chart generator.
 *
 * Pure PHP, no JS, no native extensions (no GD/Imagick required).
 * Same markup can be echoed straight into a Blade view for the
 * on-screen report AND into the dompdf export template, so the
 * charts look identical in both places.
 */
class SvgChart
{
    /** Brand palette, anchored on DocuMate blue #2A57B4 */
    public const PALETTE = [
        '#2A57B4', '#4C8DFF', '#22C55E', '#F59E0B',
        '#EF4444', '#8B5CF6', '#14B8A6', '#EC4899',
        '#64748B', '#0EA5E9',
    ];

    /**
     * Vertical bar chart.
     *
     * @param array<string> $labels
     * @param array<int|float> $values
     */
    public static function bar(array $labels, array $values, array $opts = []): string
    {
        $width  = $opts['width'] ?? 640;
        $height = $opts['height'] ?? 320;
        $count  = max(1, count($values));

        // With many categories in a fixed width, each bar's column gets too
        // narrow for its label to sit flat without colliding with its
        // neighbours — angle the labels instead of letting them overlap.
        $gap = 14;
        $roughBarW = ($width - 44 - 20 - ($gap * ($count - 1))) / $count;
        $rotateLabels = $count > 6 || $roughBarW < 46;

        $padding = ['top' => 24, 'right' => 20, 'bottom' => $rotateLabels ? 74 : 56, 'left' => 44];

        $chartW = $width - $padding['left'] - $padding['right'];
        $chartH = $height - $padding['top'] - $padding['bottom'];

        $max = max(1, max($values ?: [0]));
        $niceMax = self::niceMax($max);

        $barW = max(6, ($chartW - ($gap * ($count - 1))) / $count);
        // Shorter labels leave more breathing room once there are many bars.
        $labelLen = $rotateLabels ? ($count > 8 ? 10 : 12) : 14;

        $svg = self::openSvg($width, $height);

        // gridlines + y-axis labels (5 steps)
        $steps = 4;
        for ($i = 0; $i <= $steps; $i++) {
            $val = $niceMax * $i / $steps;
            $y = $padding['top'] + $chartH - ($chartH * $i / $steps);
            $svg .= sprintf(
                '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#E2E8F0" stroke-width="1" />',
                $padding['left'], $y, $width - $padding['right'], $y
            );
            $svg .= sprintf(
                '<text x="%d" y="%.1f" font-size="10" fill="#64748B" text-anchor="end" font-family="Arial, Helvetica, sans-serif">%s</text>',
                $padding['left'] - 8, $y + 3, self::formatNumber($val)
            );
        }

        foreach ($values as $i => $v) {
            $barH = $niceMax > 0 ? ($v / $niceMax) * $chartH : 0;
            $x = $padding['left'] + $i * ($barW + $gap);
            $y = $padding['top'] + $chartH - $barH;
            $color = self::PALETTE[$i % count(self::PALETTE)];

            $svg .= sprintf(
                '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="4" fill="%s" />',
                $x, $y, $barW, $barH, $color
            );
            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" font-size="11" fill="#0F172A" text-anchor="middle" font-weight="600" font-family="Arial, Helvetica, sans-serif">%s</text>',
                $x + $barW / 2, $y - 6, self::formatNumber($v)
            );

            $label = self::truncate($labels[$i] ?? '', $labelLen);
            $labelX = $x + $barW / 2;
            $labelY = $height - $padding['bottom'] + 18;

            if ($rotateLabels) {
                // Anchor at the bar's centre, then rotate around that point so
                // angled labels for adjacent bars fan out instead of colliding.
                $svg .= sprintf(
                    '<text x="%.1f" y="%.1f" font-size="9.5" fill="#334155" text-anchor="end" font-family="Arial, Helvetica, sans-serif" transform="rotate(-40 %.1f %.1f)">%s</text>',
                    $labelX, $labelY, $labelX, $labelY, htmlspecialchars($label)
                );
            } else {
                $svg .= sprintf(
                    '<text x="%.1f" y="%d" font-size="10" fill="#334155" text-anchor="middle" font-family="Arial, Helvetica, sans-serif">%s</text>',
                    $labelX, $labelY, htmlspecialchars($label)
                );
            }
        }

        $svg .= self::closeSvg();
        return $svg;
    }

    /**
     * Donut / pie chart with a side legend.
     *
     * @param array<string> $labels
     * @param array<int|float> $values
     */
    public static function pie(array $labels, array $values, array $opts = []): string
    {
        $width  = $opts['width'] ?? 460;
        $height = $opts['height'] ?? 260;
        $donut  = $opts['donut'] ?? true;
        $count  = max(1, count($values));

        // Donut + legend geometry scales with whatever box we're actually
        // drawn into, instead of assuming the ~460px default — a narrower
        // box (e.g. a two-column layout) used to leave the legend text with
        // nowhere to go, so it got clipped by the SVG viewBox edge.
        $cx = max(60, $width * 0.28);
        $cy = $height / 2;
        $r  = max(30, min($cy - 18, $cx - 14));
        $innerR = $donut ? $r * 0.55 : 0;

        $legendX = $cx + $r + 22;
        $legendAvailableW = max(60, $width - $legendX - 10);
        // ~5.6px per character at 10px font; reserve room for " (99, 100%)".
        $legendLabelLen = max(4, (int) floor($legendAvailableW / 5.6) - 12);

        $lineHeight = max(14, min(20, ($height - 32) / $count));
        $legendY = max(16, ($height - $count * $lineHeight) / 2) + $lineHeight * 0.6;

        $total = array_sum($values) ?: 1;
        $svg = self::openSvg($width, $height);

        $angle = -90; // start at top
        foreach ($values as $i => $v) {
            $slice = ($v / $total) * 360;
            $color = self::PALETTE[$i % count(self::PALETTE)];

            if ($slice > 0) {
                $svg .= self::pieSlicePath($cx, $cy, $r, $innerR, $angle, $angle + $slice, $color);
            }
            $angle += $slice;
        }

        if ($donut) {
            $totalFontSize = max(13, min(20, $r * 0.4));
            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" font-size="%.1f" font-weight="700" fill="#0F172A" text-anchor="middle" font-family="Arial, Helvetica, sans-serif">%d</text>',
                $cx, $cy - 2, $totalFontSize, (int) $total
            );
            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" font-size="9.5" fill="#64748B" text-anchor="middle" font-family="Arial, Helvetica, sans-serif">TOTAL</text>',
                $cx, $cy + 13
            );
        }

        // legend — position and label length were computed above from $width/$height
        // so this fits inside the canvas instead of running past its right edge.
        foreach ($values as $i => $v) {
            $color = self::PALETTE[$i % count(self::PALETTE)];
            $pct = $total > 0 ? round(($v / $total) * 100) : 0;
            $y = $legendY + $i * $lineHeight;
            $svg .= sprintf('<rect x="%.1f" y="%.1f" width="10" height="10" rx="2.5" fill="%s" />', $legendX, $y - 9, $color);
            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" font-size="10" fill="#334155" font-family="Arial, Helvetica, sans-serif">%s (%s, %d%%)</text>',
                $legendX + 15, $y, htmlspecialchars(self::truncate($labels[$i] ?? '', $legendLabelLen)), self::formatNumber($v), $pct
            );
        }

        $svg .= self::closeSvg();
        return $svg;
    }

    /**
     * Simple line/trend chart (e.g. counts per month).
     *
     * @param array<string> $labels
     * @param array<int|float> $values
     */
    public static function line(array $labels, array $values, array $opts = []): string
    {
        $width   = $opts['width'] ?? 640;
        $height  = $opts['height'] ?? 260;
        $padding = ['top' => 20, 'right' => 20, 'bottom' => 40, 'left' => 44];
        $color   = $opts['color'] ?? self::PALETTE[0];

        $chartW = $width - $padding['left'] - $padding['right'];
        $chartH = $height - $padding['top'] - $padding['bottom'];

        $max = max(1, max($values ?: [0]));
        $niceMax = self::niceMax($max);
        $count = max(1, count($values) - 1);

        $svg = self::openSvg($width, $height);

        $steps = 4;
        for ($i = 0; $i <= $steps; $i++) {
            $val = $niceMax * $i / $steps;
            $y = $padding['top'] + $chartH - ($chartH * $i / $steps);
            $svg .= sprintf(
                '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#E2E8F0" stroke-width="1" />',
                $padding['left'], $y, $width - $padding['right'], $y
            );
            $svg .= sprintf(
                '<text x="%d" y="%.1f" font-size="10" fill="#64748B" text-anchor="end" font-family="Arial, Helvetica, sans-serif">%s</text>',
                $padding['left'] - 8, $y + 3, self::formatNumber($val)
            );
        }

        $points = [];
        foreach ($values as $i => $v) {
            $x = $padding['left'] + ($count > 0 ? ($i / $count) * $chartW : 0);
            $y = $padding['top'] + $chartH - ($niceMax > 0 ? ($v / $niceMax) * $chartH : 0);
            $points[] = [$x, $y];
        }

        if (count($points) > 1) {
            $path = 'M ' . implode(' L ', array_map(fn ($p) => sprintf('%.1f,%.1f', $p[0], $p[1]), $points));
            $svg .= sprintf('<path d="%s" fill="none" stroke="%s" stroke-width="2.5" />', $path, $color);

            $areaPath = $path . sprintf(' L %.1f,%.1f L %.1f,%.1f Z', end($points)[0], $padding['top'] + $chartH, $points[0][0], $padding['top'] + $chartH);
            $svg .= sprintf('<path d="%s" fill="%s" opacity="0.08" />', $areaPath, $color);
        }

        foreach ($points as $i => [$x, $y]) {
            $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="3.5" fill="%s" />', $x, $y, $color);
            if ($i % max(1, (int) ceil(count($points) / 8)) === 0 || $i === count($points) - 1) {
                $svg .= sprintf(
                    '<text x="%.1f" y="%d" font-size="9" fill="#64748B" text-anchor="middle" font-family="Arial, Helvetica, sans-serif">%s</text>',
                    $x, $height - ($padding['bottom'] - 24), htmlspecialchars($labels[$i] ?? '')
                );
            }
        }

        $svg .= self::closeSvg();
        return $svg;
    }

    private static function pieSlicePath(float $cx, float $cy, float $r, float $innerR, float $startDeg, float $endDeg, string $color): string
    {
        $start = deg2rad($startDeg);
        $end = deg2rad($endDeg);
        $largeArc = ($endDeg - $startDeg) > 180 ? 1 : 0;

        $x1 = $cx + $r * cos($start);
        $y1 = $cy + $r * sin($start);
        $x2 = $cx + $r * cos($end);
        $y2 = $cy + $r * sin($end);

        if ($innerR > 0) {
            $ix1 = $cx + $innerR * cos($end);
            $iy1 = $cy + $innerR * sin($end);
            $ix2 = $cx + $innerR * cos($start);
            $iy2 = $cy + $innerR * sin($start);

            $d = sprintf(
                'M %.2f,%.2f A %.2f,%.2f 0 %d 1 %.2f,%.2f L %.2f,%.2f A %.2f,%.2f 0 %d 0 %.2f,%.2f Z',
                $x1, $y1, $r, $r, $largeArc, $x2, $y2,
                $ix1, $iy1, $innerR, $innerR, $largeArc, $ix2, $iy2
            );
        } else {
            $d = sprintf(
                'M %.2f,%.2f L %.2f,%.2f A %.2f,%.2f 0 %d 1 %.2f,%.2f Z',
                $cx, $cy, $x1, $y1, $r, $r, $largeArc, $x2, $y2
            );
        }

        return sprintf('<path d="%s" fill="%s" />', $d, $color);
    }

    private static function openSvg(int $width, int $height): string
    {
        // dompdf does not reliably resolve a percentage width on the root <svg> —
        // it needs an explicit pixel width here (the browser-only Blade view is
        // fine with either, since browsers do resolve "100%").
        return sprintf('<svg viewBox="0 0 %d %d" width="%d" height="%d" xmlns="http://www.w3.org/2000/svg">', $width, $height, $width, $height);
    }

    private static function closeSvg(): string
    {
        return '</svg>';
    }

    private static function niceMax(float $max): float
    {
        if ($max <= 0) {
            return 1;
        }
        $magnitude = 10 ** floor(log10($max));
        $normalized = $max / $magnitude;
        $nice = match (true) {
            $normalized <= 1 => 1,
            $normalized <= 2 => 2,
            $normalized <= 5 => 5,
            default => 10,
        };
        return $nice * $magnitude;
    }

    private static function formatNumber(float $val): string
    {
        return $val == (int) $val ? (string) (int) $val : number_format($val, 1);
    }

    private static function truncate(string $text, int $len): string
    {
        return strlen($text) > $len ? substr($text, 0, $len - 1) . '…' : $text;
    }
}