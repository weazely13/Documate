<?php

namespace App\Support;

class NameMatcher
{
    /**
     * Lowercase, strip anything but letters/spaces, collapse whitespace,
     * split into tokens, and drop single-letter tokens — these are almost
     * always middle initials ("Juan D." -> ["juan"]).
     */
    public static function tokens(?string $value): array
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));

        $tokens = array_filter(explode(' ', $value));

        return array_values(array_filter($tokens, fn ($t) => mb_strlen($t) > 1));
    }

    /**
     * Tolerant name comparison: every token on the shorter side must appear
     * on the longer side, regardless of order. This lets:
     *  - "Dela Cruz" match "Dela Cruz" or "Cruz Dela"
     *  - "Juan D." match "Juan" (middle initial dropped by the filter above)
     *  - "Maria Clara" match "Maria" (OCR captured a second given name)
     */
    public static function match(?string $a, ?string $b): bool
    {
        $tokensA = self::tokens($a);
        $tokensB = self::tokens($b);

        if (empty($tokensA) || empty($tokensB)) {
            return false;
        }

        [$shorter, $longer] = count($tokensA) <= count($tokensB)
            ? [$tokensA, $tokensB]
            : [$tokensB, $tokensA];

        foreach ($shorter as $token) {
            if (!in_array($token, $longer, true)) {
                return false;
            }
        }

        return true;
    }
}