<?php

namespace App\Support;

/**
 * Honorifics (in front of a name) and post-nominals (after it), as suggestions.
 * People may type their own when theirs is not listed, so values are only checked for length and characters.
 */
final class Honorifics
{
    public const HONORIFICS = ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.', 'Engr.', 'Atty.', 'Arch.', 'Rev.', 'Fr.', 'Sr.', 'Hon.'];

    public const POST_NOMINALS = ['PhD', 'EdD', 'DPA', 'MD', 'RN', 'LPT', 'CPA', 'MAEd', 'MAT', 'MA', 'MS', 'MBA', 'MPA', 'MIT', 'RGC', 'RPm', 'RSW', 'Jr.', 'Sr.', 'III'];

    /** Validation for a typed honorific or post-nominal: letters, dots, commas, spaces and hyphens. */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:40', "regex:/^[\\pL\\pN .,'\\-\\/()]+$/u"];
    }

    /** "Dr. Maria": a name with the honorific in front (when there is one and the name does not already start with it). */
    public static function prefix(?string $honorific, ?string $name): string
    {
        return self::decorate($honorific, $name, null);
    }

    /** The name without a leading honorific ("Dr. Maria Santos" → "Maria Santos"): for initials and matching. */
    public static function strip(?string $name): string
    {
        $name = trim((string) $name);
        $known = array_map('mb_strtolower', self::HONORIFICS);
        // Leading words that are a known honorific, or a short abbreviation ending in a dot (a typed one, e.g. "Hon.").
        while (preg_match('/^(\S+)\s+(.+)$/u', $name, $m)) {
            $word = mb_strtolower($m[1]);
            if (! in_array($word, $known, true) && ! preg_match('/^\pL{1,5}\.$/u', $m[1])) {
                break;
            }
            $name = $m[2];
        }

        return $name;
    }

    /** Two-letter initials of a person's name, ignoring a leading honorific ("Dr. Maria Santos" → "MS"). */
    public static function initials(?string $name, string $fallback = '?'): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', self::strip($name)) ?: []));
        if (! $words) {
            return $fallback;
        }
        $last = count($words) > 1 ? end($words) : '';

        return mb_strtoupper(mb_substr($words[0], 0, 1) . ($last !== '' ? mb_substr($last, 0, 1) : '')) ?: $fallback;
    }

    /** "Dr. Maria Santos, PhD": a name with its honorific and post-nominals, skipping the empty ones. */
    public static function decorate(?string $honorific, ?string $name, ?string $postNominals): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        $pre = trim((string) $honorific);
        $post = trim((string) $postNominals, " ,");

        if ($pre !== '' && ! str_starts_with(mb_strtolower($name), mb_strtolower($pre))) {
            $name = $pre . ' ' . $name;
        }
        if ($post !== '' && ! str_ends_with(mb_strtolower($name), mb_strtolower($post))) {
            $name .= ', ' . $post;
        }

        return $name;
    }
}
