<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;
use App\Models\SignatoryRole;
use App\Services\Workflow\FormSupport;

/** F-SDM-015 Rev. 1 (03-31-23): Application for Student Assistantship Form. */
final class StudentAssistantshipApplication extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Requests; }

    public const WEEKLY_HOURS = 12;
    public const MAX_DAILY_HOURS = 4;
    

    /** key => [label, days the row covers] */
    public const DAYS = [
        'mon_thu' => ['Monday & Thursday', 2],
        'tue_fri' => ['Tuesday & Friday', 2],
        'wed'     => ['Wednesday', 1],
        'sat'     => ['Saturday', 1],
        'sun'     => ['Sunday', 1],
    ];
    public const WINDOWS = [
        'am' => ['07:00', '12:00'],
        'pm' => ['13:00', '20:00'],
    ];

    public function key(): string { return 'student_assistantship_application'; }
    public function name(): string { return 'Application for Student Assistantship'; }
    public function description(): string { return 'Apply as a Student Assistant (SA) by listing your vacant periods.'; }
    public function formCode(): string { return 'F-SDM-015 Rev. 1 (03-31-23)'; }

    public function systemFields(): array
    {
        return parent::systemFields() + ['contact_number' => 'Contact number'];
    }

    public function fields(): array
    {
        $fields = [];

        foreach (self::DAYS as $k => [$label]) {
            foreach (['am' => 'AM', 'pm' => 'PM'] as $h => $H) {
                foreach (['from' => 'from', 'to' => 'to'] as $p => $word) {
                    $fields["{$k}_{$h}_{$p}"] = [
                        'label' => "{$label}: {$H} {$word}", 'type' => 'time',
                        'group' => 'schedule', 'rules' => ['date_format:H:i,H:i:s'],
                    ];
                }
            }
        }

        return $fields;
    }

    /* ---------- helpers (also used by the form view and the PDF) ---------- */

    public static function minutes(?string $t): ?int
    {
        return $t && preg_match('/^(\d{1,2}):(\d{2})/', $t, $m) ? ((int) $m[1]) * 60 + (int) $m[2] : null;
    }

    public static function halfHours(array $v, string $k, string $h): float
    {
        $a = self::minutes($v["{$k}_{$h}_from"] ?? null);
        $b = self::minutes($v["{$k}_{$h}_to"] ?? null);

        return ($a !== null && $b !== null && $b > $a) ? ($b - $a) / 60 : 0.0;
    }

    public static function dayHours(array $v, string $k): float
    {
        return self::halfHours($v, $k, 'am') + self::halfHours($v, $k, 'pm');
    }

    public static function weeklyTotal(array $v): float
    {
        $sum = 0.0;
        foreach (self::DAYS as $k => [, $days]) {
            $sum += self::dayHours($v, $k) * $days;
        }

        return $sum;
    }

    public static function range(array $v, string $k, string $h): string
    {
        $a = $v["{$k}_{$h}_from"] ?? null;
        $b = $v["{$k}_{$h}_to"] ?? null;
        if (! $a || ! $b) {
            return '';
        }

        return Carbon::createFromFormat('H:i', substr($a, 0, 5))->format('g:i') . ' - '
            . Carbon::createFromFormat('H:i', substr($b, 0, 5))->format('g:i A');
    }

    public static function fmt(float $n): string
    {
        return $n > 0 ? rtrim(rtrim(number_format($n, 2), '0'), '.') : '';
    }

    public function check(array $values): ?string
    {
        foreach (self::DAYS as $k => [$label]) {
            foreach (['am' => 'AM', 'pm' => 'PM'] as $h => $H) {
                $from = $values["{$k}_{$h}_from"] ?? null;
                $to = $values["{$k}_{$h}_to"] ?? null;

                if (($from === null || $from === '') xor ($to === null || $to === '')) {
                    return "{$label} ({$H}): pick both the start and end time, or leave both empty.";
                }
                if (! $from) {
                    continue;
                }
                if (self::minutes($to) <= self::minutes($from)) {
                    return "{$label} ({$H}): the end time must be after the start time.";
                }
                [$min, $max] = self::WINDOWS[$h];
                if (self::minutes($from) < self::minutes($min) || self::minutes($to) > self::minutes($max)) {
                    return $h === 'am'
                        ? "{$label}: AM times must be between 7:00 AM and 12:00 PM."
                        : "{$label}: PM times must be between 1:00 PM and 8:00 PM.";
                }
            }
            if (self::dayHours($values, $k) > self::MAX_DAILY_HOURS) {
                return "{$label}: duty hours cannot exceed " . self::MAX_DAILY_HOURS . ' hours a day.';
            }
        }

        $total = self::weeklyTotal($values);
        if (abs($total - self::WEEKLY_HOURS) > 0.001) {
            return 'Your vacant hours add up to ' . (self::fmt($total) ?: '0') . ' hours a week. They must total exactly ' . self::WEEKLY_HOURS . '.';
        }

        return null;
    }
    /** Freeze the current VPSD's name as the addressee. No signature is involved. */
    public function beforeSigning(array $values): array
    {
        if (! empty($values['vpsd_name'])) {
            return $values;
        }

        $holder = SignatoryRole::where('key', 'vpsd')->first()?->holdersFor(null, null)->first();

        $values['vpsd_name'] = $holder ? FormSupport::systemValue($holder, 'formatted_full_name') : null;

        return $values;
    }

    public function chain(array $values = []): array { return []; }
    public function requiresStudentSignature(): bool { return true; }
    public function pdfView(): string { return 'pdf.student-assistantship-application'; }

    public function summary(array $values): string
    {
        return 'SA application · ' . self::fmt(self::weeklyTotal($values)) . ' hrs/week';
    }
}