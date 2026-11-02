<?php

namespace App\Support;

class MarkdownLite
{
    public static function toHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = self::normalizeInlineSteps($text);
        $text = self::normalizeLabeledLines($text);
        $text = self::normalizeLongParagraphs($text);
        $text = preg_replace_callback('/\[[^\]\n]+\]\([^)]+\)|\S{30,}/u', function ($m) {
            if (str_starts_with($m[0], '[')) {
                return $m[0];
            }

            return implode("\u{200B}", mb_str_split($m[0], 30));
        }, $text);
        $safe = e($text);

        // Bold first (so single-asterisk italics below doesn't eat into **pairs**)
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $safe);
        $safe = preg_replace('/`([^`]+)`/', '<code>$1</code>', $safe);
        $safe = preg_replace_callback('/\[([^\]\n]+)\]\(([^)\s]+)\)/', static function ($match) {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $isInternal = str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\');
            $isExternal = preg_match('#^https://[^\s]+$#i', $url) === 1;

            if (! $isInternal && ! $isExternal) {
                return $match[1];
            }

            $attributes = $isInternal ? ' wire:navigate' : ' target="_blank" rel="noopener noreferrer"';

            return '<a href="' . e($url) . '"' . $attributes . '>' . $match[1] . '</a>';
        }, $safe);

        $lines = explode("\n", $safe);
        $html = [];
        $paragraphBuffer = [];
        $specialBlock = null;
        $listType = null;
        $closeList = static function () use (&$listType, &$html): void {
            if ($listType !== null) {
                $html[] = '</' . $listType . '>';
                $listType = null;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line); // drops any indentation the model adds

            if (preg_match('/^:::(center|credits)$/', $trimmed, $blockMatch)) {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                $specialBlock = $blockMatch[1];
                $html[] = '<div class="chatbot-' . $specialBlock . '">';
                continue;
            }

            if ($trimmed === ':::' && $specialBlock !== null) {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                $html[] = '</div>';
                $specialBlock = null;
                continue;
            }

            if ($specialBlock !== null) {
                if ($trimmed === '') {
                    if ($specialBlock === 'center') {
                        $html[] = '<div class="chatbot-centered-gap"></div>';
                    }
                    continue;
                }

                if ($specialBlock === 'center' && preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $headingMatch)) {
                    $html[] = '<div class="chatbot-centered-heading"><strong>' . $headingMatch[1] . '</strong></div>';
                    continue;
                }

                $html[] = '<div class="chatbot-' . ($specialBlock === 'center' ? 'centered-line' : 'credit-line') . '">' . $trimmed . '</div>';
                continue;
            }

            if ($trimmed === '') {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                continue;
            }

            if (preg_match('/^[-*_]{3,}$/', $trimmed)) {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                $html[] = '<hr class="chatbot-divider">';
                continue;
            }

            // Optional heading support (##, ###)
            if (preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                $html[] = '<h3 class="chatbot-heading">' . $m[1] . '</h3>';
                continue;
            }

            // Bullet item: - item, * item, • item
            if (preg_match('/^[-*\x{2022}]\s+(.*)$/u', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                if ($listType !== 'ul') {
                    $closeList();
                    $html[] = '<ul class="chatbot-list">';
                    $listType = 'ul';
                }
                $html[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Numbers (1. / 1)), letters (a. / A.), roman numerals (ii. / IV.)
            if (preg_match('/^(\d+|[a-zA-Z]|[ivxlcdmIVXLCDM]{2,6})[.)]\s+(.*)$/u', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                if ($listType !== 'ol') {
                    $closeList();
                    $html[] = '<ol class="chatbot-list chatbot-list-numbered">';
                    $listType = 'ol';
                }
                $html[] = '<li>' . $m[2] . '</li>';
                continue;
            }

            if (preg_match('/^&gt;\s?(.*)$/', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                $closeList();
                $html[] = '<blockquote class="chatbot-callout">' . $m[1] . '</blockquote>';
                continue;
            }

            // Plain text line: buffer into the current paragraph
            $closeList();
            $paragraphBuffer[] = $trimmed;
        }

        self::flushParagraph($paragraphBuffer, $html);
        $closeList();
        if ($specialBlock !== null) {
            $html[] = '</div>';
        }

        return implode('', $html);
    }

    protected static function normalizeInlineSteps(string $text): string
    {
        $text = preg_replace('/(?<!\w)step\s+([1-9])[:.)]\s*/i', '$1. ', $text) ?? $text;
        $markers = preg_match_all('/(?:^|[ \t])(?:[1-9][.)]\s+|step\s+[1-9][:.]\s+)/im', $text);

        if ($markers < 2) {
            return $text;
        }

        return preg_replace('/[ \t]+(?=(?:[1-9][.)]\s+|step\s+[1-9][:.]\s+))/i', "\n", $text) ?? $text;
    }

    protected static function normalizeLabeledLines(string $text): string
    {
        $lines = explode("\n", $text);
        $isLabel = static fn (string $line): bool => preg_match(
            "/^\s*([[:upper:]][[:alnum:] &\/()'-]{1,28}):\s+(.+)$/u",
            $line,
        ) === 1;

        for ($start = 0; $start < count($lines);) {
            if (! $isLabel($lines[$start])) {
                $start++;
                continue;
            }

            $end = $start;
            while ($end < count($lines) && $isLabel($lines[$end])) {
                $end++;
            }

            if ($end - $start >= 2) {
                for ($index = $start; $index < $end; $index++) {
                    preg_match(
                        "/^\s*([[:upper:]][[:alnum:] &\/()'-]{1,28}):\s+(.+)$/u",
                        $lines[$index],
                        $match,
                    );
                    $lines[$index] = '- **' . $match[1] . ':** ' . $match[2];
                }
            }

            $start = $end;
        }

        return implode("\n", $lines);
    }

    protected static function normalizeLongParagraphs(string $text): string
    {
        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        $normalized = [];

        foreach ($paragraphs as $paragraph) {
            if (mb_strlen($paragraph) < 260
                || preg_match('/^\s*(?:#{1,6}\s|[-*]\s|\d+[.)]\s|>)/m', $paragraph)) {
                $normalized[] = $paragraph;
                continue;
            }

            $sentences = self::splitSentences(trim($paragraph));
            if (count($sentences) < 2) {
                $normalized[] = $paragraph;
                continue;
            }

            $normalized[] = array_shift($sentences);
            if (count($sentences) === 1) {
                $normalized[] = $sentences[0];
            } else {
                $normalized[] = implode("\n", array_map(fn ($sentence) => '- ' . $sentence, $sentences));
            }
        }

        return implode("\n\n", $normalized);
    }

    protected static function splitSentences(string $text): array
    {
        $protected = [];
        $text = preg_replace_callback('/\b(?:Mr|Mrs|Ms|Dr|Prof)\.|\b[A-Z]\./u', function ($match) use (&$protected) {
            $token = 'CHATDOT' . count($protected) . 'TOKEN';
            $protected[$token] = $match[0];

            return $token;
        }, $text);

        $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9\[])/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];

        return array_map(
            fn ($sentence) => strtr(trim($sentence), $protected),
            $sentences,
        );
    }

    protected static function item(string $marker, string $content): string
    {
        return '<div class="chatbot-item">'
            . '<span class="chatbot-marker">' . $marker . '</span>'
            . '<span class="chatbot-text">' . $content . '</span>'
            . '</div>';
    }

    protected static function flushParagraph(array &$buffer, array &$html): void
    {
        if (! empty($buffer)) {
            $html[] = '<p>' . implode(' ', $buffer) . '</p>';
            $buffer = [];
        }
    }
}
