<?php

namespace App\Support\Charts;

/**
 * The Reports page's trend chart, drawn for the PDF download as SVG (dompdf only renders SVG
 * loaded as an image). Takes the same $trend the page draws — days, weeks, months or years,
 * one series per line — plus the page's comparison, so the printout matches the screen:
 * a period comparison as dashed lines (or paler bars), a metric comparison on two scales.
 */
final class ReportPdfChart
{
    private const W = 700;
    private const H = 250;
    private const PAD_L = 46;
    private const PAD_R = 16;
    private const PAD_T = 12;
    private const PAD_B = 34;
    private const FONT = 'font-family="Arial, Helvetica, sans-serif"';

    /** @param array{unit: string, buckets: array, series: array, axes?: string} $trend */
    public static function render(array $trend, ?array $comparison, string $type = 'line'): string
    {
        $series = $trend['series'];
        $n = count($trend['buckets']);
        $dual = ($trend['axes'] ?? null) === 'dual' && count($series) === 2;
        $cmp = $comparison['series'] ?? [];

        $all = collect($series)->flatMap(fn ($s) => $s[3])->concat(collect($cmp)->flatten())->filter();
        $top = self::niceTop((int) $all->max());
        $tops = array_map(fn ($s) => $dual ? self::niceTop((int) max($s[3] ?: [0])) : $top, $series);

        $plotW = self::W - self::PAD_L - ($dual ? 46 : self::PAD_R);
        $plotH = self::H - self::PAD_T - self::PAD_B;
        $y = fn ($v, $si) => self::PAD_T + $plotH - ($v / max(1, $tops[$si])) * $plotH;

        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', self::W, self::H, self::W, self::H);

        // gridlines and the scale(s)
        foreach ([0, 0.25, 0.5, 0.75, 1] as $f) {
            $gy = self::PAD_T + $plotH - $f * $plotH;
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="%s" stroke-width="1"/>', self::PAD_L, $gy, self::PAD_L + $plotW, $gy, $f === 0 ? '#94A3B8' : '#E2E8F0');
            $svg .= sprintf('<text x="%d" y="%.1f" font-size="14" fill="%s" text-anchor="end" %s>%s</text>', self::PAD_L - 6, $gy + 3.5, $dual ? $series[0][2] : '#64748B', self::FONT, (int) round($tops[0] * $f));
            if ($dual) {
                $svg .= sprintf('<text x="%d" y="%.1f" font-size="14" fill="%s" %s>%s</text>', self::PAD_L + $plotW + 6, $gy + 3.5, $series[1][2], self::FONT, (int) round($tops[1] * $f));
            }
        }

        if ($type === 'bar') {
            $slot = $plotW / max(1, $n);
            $per = count($series) * ($cmp ? 2 : 1);
            $barW = $slot * 0.72 / max(1, $per);
            foreach ($series as $si => [$key, , $colour, $values]) {
                foreach ($values as $i => $v) {
                    $x0 = self::PAD_L + $i * $slot + $slot * 0.14 + $si * ($cmp ? 2 : 1) * $barW;
                    if ($v > 0) {
                        $svg .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s"/>', $x0, $y($v, $si), $barW * 0.9, self::PAD_T + $plotH - $y($v, $si), $colour);
                    }
                    $c = $cmp[$key][$i] ?? null;
                    if ($c) {
                        $svg .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s" fill-opacity="0.35"/>', $x0 + $barW, $y($c, $si), $barW * 0.9, self::PAD_T + $plotH - $y($c, $si), $colour);
                    }
                }
            }
            $xAt = fn ($i) => self::PAD_L + ($i + 0.5) * $slot;
        } else {
            $xAt = fn ($i) => self::PAD_L + ($n <= 1 ? $plotW / 2 : $i * $plotW / ($n - 1));
            foreach ($series as $si => [$key, , $colour, $values]) {
                // the comparison first, dashed, so the period on screen sits on top of it
                if (isset($cmp[$key])) {
                    $svg .= self::dashed($cmp[$key], $xAt, fn ($v) => $y($v, $si), $colour);
                }
                $pts = [];
                foreach ($values as $i => $v) {
                    $pts[] = sprintf('%.1f,%.1f', $xAt($i), $y($v, $si));
                }
                if (! $dual && count($pts) > 1) {
                    $svg .= sprintf('<polygon points="%s %.1f,%.1f %.1f,%.1f" fill="%s" fill-opacity="0.10"/>', implode(' ', $pts), $xAt($n - 1), self::PAD_T + $plotH, $xAt(0), self::PAD_T + $plotH, $colour);
                }
                $svg .= sprintf('<polyline points="%s" fill="none" stroke="%s" stroke-width="2.2" stroke-linejoin="round"/>', implode(' ', $pts), $colour);
            }
        }

        // date labels: about seven, the last one always shown
        $every = max(1, (int) ceil($n / 7));
        foreach ($trend['buckets'] as $i => $b) {
            if (($i % $every === 0 && $n - 1 - $i >= $every / 2) || $i === $n - 1) {
                $anchor = $type === 'line' && $n > 1 ? ($i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle')) : 'middle';
                $svg .= sprintf('<text x="%.1f" y="%d" font-size="14" fill="#475569" text-anchor="%s" %s>%s</text>', $xAt($i), self::H - 9, $anchor, self::FONT, e($b['label']));
            }
        }

        return $svg . '</svg>';
    }

    /** A dashed line drawn as short segments, which every SVG renderer draws the same way. */
    private static function dashed(array $values, callable $x, callable $y, string $colour): string
    {
        $out = '';
        $prev = null;
        foreach ($values as $i => $v) {
            if ($v === null) {
                $prev = null;
                continue;
            }
            $pt = [$x($i), $y($v)];
            if ($prev) {
                $len = hypot($pt[0] - $prev[0], $pt[1] - $prev[1]);
                for ($d = 0; $d < $len; $d += 9) {
                    $t0 = $d / max(1, $len);
                    $t1 = min(1, ($d + 5) / max(1, $len));
                    $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-opacity="0.6" stroke-width="2"/>',
                        $prev[0] + ($pt[0] - $prev[0]) * $t0, $prev[1] + ($pt[1] - $prev[1]) * $t0,
                        $prev[0] + ($pt[0] - $prev[0]) * $t1, $prev[1] + ($pt[1] - $prev[1]) * $t1, $colour);
                }
            }
            $prev = $pt;
        }

        return $out;
    }

    /** A round top for the scale (1, 2, 2.5, 5 or 10 steps) so the gridlines read easily. */
    private static function niceTop(int $peak): int
    {
        $peak = max(1, $peak);
        $raw = $peak / 4;
        $mag = 10 ** floor(log10(max(1, $raw)));
        $step = 10 * $mag;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            $s = $m * $mag;
            if ($s >= $raw && floor($s) == $s) {
                $step = $s;
                break;
            }
        }
        $step = max(1, (int) $step);

        return $step * (int) ceil($peak / $step);
    }
}
