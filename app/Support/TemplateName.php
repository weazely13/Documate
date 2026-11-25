<?php

namespace App\Support;

use Illuminate\Support\Str;

/** A paper template's name for display: "affidavit_of_loss_form" and "AFFIDAVIT OF LOSS FORM" both read "Affidavit of Loss Form". */
final class TemplateName
{
    /** Words that stay lowercase inside a title (never the first word). */
    private const SMALL = ['a', 'an', 'and', 'as', 'at', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'with'];

    public static function display(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $words = explode(' ', Str::title(str_replace('_', ' ', trim($name))));
        foreach ($words as $i => $w) {
            if ($i > 0 && in_array(Str::lower($w), self::SMALL, true)) {
                $words[$i] = Str::lower($w);
            }
        }

        return implode(' ', $words);
    }
}
