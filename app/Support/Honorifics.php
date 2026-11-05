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
