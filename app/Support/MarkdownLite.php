<?php

namespace App\Support;

class MarkdownLite
{
    public static function toHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
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

        foreach ($lines as $line) {
            $trimmed = trim($line); // drops any indentation the model adds

            if ($trimmed === '') {
                self::flushParagraph($paragraphBuffer, $html);
                continue;
            }

            // Optional heading support (##, ###)
            if (preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                $html[] = '<p class="chatbot-heading"><strong>' . $m[1] . '</strong></p>';
                continue;
            }

            // Bullet item: - item, * item, • item
            if (preg_match('/^[-*\x{2022}]\s+(.*)$/u', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                $html[] = self::item('&bull;', $m[1]);
                continue;
            }

            // Numbers (1. / 1)), letters (a. / A.), roman numerals (ii. / IV.)
            if (preg_match('/^(\d+|[a-zA-Z]|[ivxlcdmIVXLCDM]{2,6})[.)]\s+(.*)$/u', $trimmed, $m)) {
                self::flushParagraph($paragraphBuffer, $html);
                $html[] = self::item($m[1] . '.', $m[2]);
                continue;
            }

            // Plain text line: buffer into the current paragraph
            $paragraphBuffer[] = $trimmed;
        }

        self::flushParagraph($paragraphBuffer, $html);

        return implode('', $html);
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