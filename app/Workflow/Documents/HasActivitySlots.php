<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;

trait HasActivitySlots
{
    private const MAX_SLOTS = 10;

    protected function slotsField(): array
    {
        return [
            'activity_slots' => [
                'label' => 'Date and time of the Activity/Event', 'type' => 'slots', 'required' => true,
                'max_slots' => self::MAX_SLOTS,
                'help' => 'Add every date the activity takes place, each with its own time.',
            ],
        ];
    }

    /** @return array<int, array{date: string, from: string, to: ?string}> sorted; falls back to the old single date/time */
    public static function slotsFrom(array $v): array
    {
        $raw = is_array($v['activity_slots'] ?? null) ? $v['activity_slots'] : [];

        if (! $raw && ! empty($v['activity_date'])) {
            $raw = [['date' => $v['activity_date'], 'from' => $v['activity_time'] ?? '', 'to' => null]];
        }

        $slots = [];
        foreach ($raw as $s) {
            $date = trim((string) ($s['date'] ?? ''));
            $from = substr(trim((string) ($s['from'] ?? '')), 0, 5);
            $to = substr(trim((string) ($s['to'] ?? '')), 0, 5);

            if ($date === '' && $from === '') {
                continue;
            }
            $slots[] = ['date' => $date, 'from' => $from, 'to' => $to !== '' ? $to : null];
        }

        usort($slots, fn ($a, $b) => [$a['date'], $a['from']] <=> [$b['date'], $b['from']]);

        return $slots;
    }

    /** "October 25, 2026, 9:00 AM to 12:00 PM" for each slot. */
    public static function slotLines(array $v): array
    {
        $t = fn ($x) => $x ? Carbon::createFromFormat('H:i', $x)->format('g:i A') : '';

        return array_map(function ($s) use ($t) {
            $date = $s['date'] !== '' ? Carbon::parse($s['date'])->format('F j, Y') : '';
            $time = $s['from'] !== '' ? $t($s['from']) . ($s['to'] ? ' to ' . $t($s['to']) : '') : '';

            return trim($date . ($time !== '' ? ", {$time}" : ''), ', ');
        }, self::slotsFrom($v));
    }

    protected function checkSlots(array $values): ?string
    {
        $slots = self::slotsFrom($values);
        if (! $slots) {
            return 'Add at least one date and time for the activity.';
        }

        $seen = [];
        foreach ($slots as $i => $s) {
            $n = $i + 1;
            if ($s['date'] === '' || ! strtotime($s['date'])) {
                return "Date {$n}: choose a date.";
            }
            if (Carbon::parse($s['date'])->lt(today())) {
                return "Date {$n} cannot be in the past.";
            }
            if ($s['from'] === '') {
                return "Date {$n}: choose a start time.";
            }
            if ($s['to'] && $s['to'] <= $s['from']) {
                return "Date {$n}: the end time must be after the start time.";
            }
            if (isset($seen[$s['date'] . $s['from']])) {
                return "Date {$n} is listed twice.";
            }
            $seen[$s['date'] . $s['from']] = true;
        }

        return null;
    }

    protected function slotsSummary(array $values): ?string
    {
        $slots = self::slotsFrom($values);
        if (! $slots || $slots[0]['date'] === '') {
            return null;
        }

        return Carbon::parse($slots[0]['date'])->format('M j, Y') . (count($slots) > 1 ? ' +' . (count($slots) - 1) . ' more' : '');
    }
}