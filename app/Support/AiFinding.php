<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * The AI findings words, the same everywhere (Workflow Review, Appointments, the side panels, the filters):
 * Unchecked (gray), Flagged (yellow), Revision (orange), Cleared (green).
 *
 * A check that is still running or could not finish has not checked the document yet, so it reads Unchecked
 * (with a spinner or a warning icon beside the word).
 */
final class AiFinding
{
    public const NOTE = 'AI findings are initial review subject to administrator verification.';

    /** key => [word, meaning, StatusStyle tone], in the legend's order. */
    public const STATES = [
        'unchecked' => ['Unchecked', 'Document has not yet been checked.', 'slate'],
        'flagged' => ['Flagged', 'Potential issues detected.', 'yellow'],
        'revision' => ['Revision', 'Requires corrections before proceeding.', 'orange'],
        'cleared' => ['Cleared', 'No issues detected.', 'green'],
    ];

    /** The recommendations that mean the student has to correct something first. */
    public const REVISION_RECOMMENDATIONS = ['request_changes', 'reject'];

    /**
     * Which of the four a record is. $fits is false when an appointment's purpose does not match the document
     * picked: the student has to correct it, so it is Revision.
     */
    public static function key(?string $flag, ?array $findings = null, bool $fits = true): string
    {
        if ($flag === null || in_array($flag, ['pending', 'review_failed'], true)) {
            return 'unchecked';
        }
        if (in_array($findings['recommendation'] ?? null, self::REVISION_RECOMMENDATIONS, true) || ! $fits) {
            return 'revision';
        }

        return $flag === 'flagged' ? 'flagged' : 'cleared';
    }

    public static function word(string $key): string
    {
        return self::STATES[$key][0] ?? self::STATES['unchecked'][0];
    }

    /** Pill classes (tint, text, ring) for a key. */
    public static function pill(string $key): string
    {
        return StatusStyle::TONES[self::STATES[$key][2] ?? 'slate'];
    }

    public static function dot(string $key): string
    {
        return StatusStyle::DOTS[self::STATES[$key][2] ?? 'slate'];
    }

    /**
     * The pill for a record: [word, classes, icon or null, hover text or null].
     * The icon shows a check that is running (spinner) or could not finish (warning).
     */
    public static function badge(?string $flag, ?array $findings = null, bool $fits = true): array
    {
        $key = self::key($flag, $findings, $fits);

        [$icon, $title] = match ($flag) {
            'pending' => ['bx-loader-alt bx-spin', 'The check is running.'],
            'review_failed' => ['bx-error-circle', 'The check could not finish. Run it again.'],
            default => [null, $findings['summary'] ?? null],
        };

        return [self::word($key), self::pill($key), $icon, $title ?: null];
    }

    /** For <x-admin.legend-tip>: [dot, word, '', meaning] per state. */
    public static function legend(): array
    {
        return collect(self::STATES)
            ->map(fn ($s, $key) => [self::dot($key), $s[0], '', $s[1]])
            ->values()->all();
    }

    /** Filter options: key => word. */
    public static function filters(): array
    {
        return collect(self::STATES)->map(fn ($s) => $s[0])->all();
    }

    /**
     * Narrows a query to the records whose pill reads $key, exactly as key() decides.
     * For appointments, $misfit and $fits add "the purpose does not / does fit the document".
     */
    public static function where(Builder $q, string $key, ?Closure $misfit = null, ?Closure $fits = null): Builder
    {
        $revise = self::REVISION_RECOMMENDATIONS;
        $checked = fn ($w) => $w->whereIn('ai_flag', ['clean', 'flagged']);
        $noRevision = function ($w) use ($revise, $fits) {
            $w->where(fn ($x) => $x->whereNull('ai_findings->recommendation')->orWhereNotIn('ai_findings->recommendation', $revise));

            return $fits ? $fits($w) : $w;
        };

        return match ($key) {
            'unchecked' => $q->where(fn ($w) => $w->whereNull('ai_flag')->orWhereIn('ai_flag', ['pending', 'review_failed'])),
            'revision' => $checked($q)->where(function ($w) use ($revise, $misfit) {
                $w->whereIn('ai_findings->recommendation', $revise);
                if ($misfit) {
                    $w->orWhere(fn ($y) => $misfit($y));
                }
            }),
            'flagged' => $noRevision($q->where('ai_flag', 'flagged')),
            'cleared' => $noRevision($q->where('ai_flag', 'clean')),
            default => $q,
        };
    }
}
