<?php

namespace App\Services;

use App\Models\TemplateVersion;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecreatefromwebp;
use function imagecreatefromstring;
use function imagesx;
use function imagesy;
use function imageistruecolor;
use function imagepalettetotruecolor;
use function imagecreatetruecolor;
use function imagecolorallocate;
use function imagefill;
use function imagecopy;
use function imagejpeg;
use function imagedestroy;

class StudentDocumentPdfService
{
    public function generate(TemplateVersion $version, array $fields, array $resolvedValues): string
    {
        $imagePath = Storage::disk('public')->path($version->image_path);

        if (!is_file($imagePath)) {
            throw new RuntimeException('Template background image not found.');
        }

        [$pageWidth, $pageHeight] = $this->pageSizeInPoints($version);
        [$canvasWidth, $canvasHeight] = $this->canvasDimensions($version);
        [$imageBinary, $imageWidth, $imageHeight] = $this->jpegImageData($imagePath);

        $fonts = $this->collectFonts($fields);
        $objects = [];

        $addObject = function (string $body) use (&$objects): int {
            $objects[] = $body;
            return count($objects);
        };

        $catalogId = $addObject('');
        $pagesId = $addObject('');

        $fontObjectIds = [];
        foreach ($fonts as $fontKey => $baseFont) {
            $fontObjectIds[$fontKey] = $addObject("<< /Type /Font /Subtype /Type1 /BaseFont /{$baseFont} >>");
        }

        $imageId = $addObject($this->streamObject(
            "<< /Type /XObject /Subtype /Image /Width {$imageWidth} /Height {$imageHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($imageBinary) . " >>",
            $imageBinary
        ));

        $content = $this->buildContentStream(
            $fields,
            $resolvedValues,
            $pageWidth,
            $pageHeight,
            $canvasWidth,
            $canvasHeight
        );

        $contentId = $addObject(
            $this->streamObject("<< /Length " . strlen($content) . " >>", $content)
        );

        $pageResources = "<< /XObject << /Im1 {$imageId} 0 R >> /Font << ";
        foreach ($fontObjectIds as $fontKey => $fontObjectId) {
            $pageResources .= "/{$fontKey} {$fontObjectId} 0 R ";
        }
        $pageResources .= ">> >>";

        $pageId = $addObject(
            "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources {$pageResources} /Contents {$contentId} 0 R >>"
        );

        $objects[$catalogId - 1] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        $objects[$pagesId - 1] = "<< /Type /Pages /Count 1 /Kids [{$pageId} 0 R] >>";

        return $this->buildPdf($objects);
    }

    private function buildContentStream(
        array $fields,
        array $resolvedValues,
        float $pageWidth,
        float $pageHeight,
        float $canvasWidth,
        float $canvasHeight
    ): string {
        $lines = [
            'q',
            sprintf('%.3F 0 0 %.3F 0 0 cm', $pageWidth, $pageHeight),
            '/Im1 Do',
            'Q',
        ];

        foreach ($fields as $field) {
            $value = trim((string) ($resolvedValues[$field['id']] ?? ''));
            if ($value === '') {
                continue;
            }

            $fontKey = $this->fontResourceKey($field);
            $baseFontSize = $this->scaleX((float) ($field['font_size'] ?? 12), $pageWidth, $canvasWidth);
            $baseLineHeight = max($baseFontSize * (float) ($field['line_height'] ?? 1.3), $baseFontSize * 1.1);
            $x = $this->scaleX((float) $field['x'], $pageWidth, $canvasWidth);
            $top = $this->scaleY((float) $field['y'], $pageHeight, $canvasHeight);
            $width = max($this->scaleX((float) $field['width'], $pageWidth, $canvasWidth), 10);
            $paddingX = $this->scaleX(8, $pageWidth, $canvasWidth);
            $paddingY = $this->scaleY(4, $pageHeight, $canvasHeight);
            $height = $this->effectiveFieldHeight(
                $field,
                max($this->scaleY((float) $field['height'], $pageHeight, $canvasHeight), 10),
                $baseLineHeight
            );
            $contentWidth = max($width - ($paddingX * 2), 4);
            $fitWidth = $contentWidth * 0.95;
            $contentHeight = max($height - ($paddingY * 2), 4);

            [$fontSize, $wrappedLines] = $this->fitFieldContent(
                $field,
                $value,
                $fitWidth,
                $contentHeight,
                $pageWidth,
                $canvasWidth,
                $baseFontSize
            );

            $lineHeight = max($fontSize * (float) ($field['line_height'] ?? 1.3), $fontSize * 1.1);
            $clipBottom = $pageHeight - $top - $height;

            $lines[] = 'q';
            $lines[] = sprintf('%.3F %.3F %.3F %.3F re W n', $x + $paddingX, $clipBottom + $paddingY, $contentWidth, $contentHeight);

            foreach ($wrappedLines as $index => $textLine) {

                $baselineOffset = $fontSize * 0.12;
                $y = $pageHeight - $top - $fontSize - $paddingY - $baselineOffset - ($index * $lineHeight);
                if ($y < 0) {
                    continue;
                }

                $textWidth = $this->estimateTextWidth(
                    $textLine,
                    $fontSize,
                    (float) ($field['letter_spacing'] ?? 0),
                    $field
                );
                $alignment = strtolower((string) ($field['alignment'] ?? 'left'));
                $drawX = match ($alignment) {
                    'center' => $x + $paddingX + max(($fitWidth - $textWidth) / 2, 0),
                    'right'  => $x + $paddingX + max($fitWidth - $textWidth, 0),
                    default  => $x + $paddingX,
                };

                [$r, $g, $b] = $this->hexToRgb((string) ($field['text_color'] ?? '#000000'));

                $lines[] = 'BT';
                $lines[] = sprintf('/%s %.3F Tf', $fontKey, $fontSize);
                $lines[] = sprintf('%.3F %.3F %.3F rg', $r, $g, $b);
                $lines[] = sprintf('1 0 0 1 %.3F %.3F Tm', $drawX, $y);
                $lines[] = '(' . $this->escapePdfString($textLine) . ') Tj';
                $lines[] = 'ET';
            }

            $lines[] = 'Q';
        }

        return implode("\n", $lines) . "\n";
    }

    private function effectiveFieldHeight(array $field, float $baseHeight, float $baseLineHeight): float
    {
        if (($field['type'] ?? 'text') !== 'paragraph') {
            return $baseHeight;
        }

        $maxLines = !empty($field['max_lines']) ? max((int) $field['max_lines'], 1) : 1;
        $heightFromLines = ($maxLines * $baseLineHeight) + 8;

        return max($baseHeight, $heightFromLines);
    }

    private function fitFieldContent(
        array $field,
        string $value,
        float $contentWidth,
        float $contentHeight,
        float $pageWidth,
        float $canvasWidth,
        ?float $baseFontSize = null
    ): array {
        $baseFontSize ??= $this->scaleX((float) ($field['font_size'] ?? 12), $pageWidth, $canvasWidth);
        $minFontSize = max($baseFontSize * 0.45, 4);
        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'paragraph') {
            $maxLines = $this->maxLinesForField($field, $baseFontSize, $contentHeight);

            return [
                $baseFontSize,
                $this->wrapText(
                    $value,
                    $contentWidth,
                    $baseFontSize,
                    $maxLines,
                    (float) ($field['letter_spacing'] ?? 0)
                ),
            ];
        }

        for ($fontSize = $baseFontSize; $fontSize >= $minFontSize; $fontSize -= 0.5) {
            $maxLines = $this->maxLinesForField($field, $fontSize, $contentHeight);
            $lines = $this->wrapText(
                $value,
                $contentWidth,
                $fontSize,
                $maxLines,
                (float) ($field['letter_spacing'] ?? 0)
            );

            $lineHeight = max($fontSize * (float) ($field['line_height'] ?? 1.3), $fontSize * 1.1);
            $totalHeight = count($lines) * $lineHeight;
            $fitsWidth = collect($lines)->every(fn ($line) => $this->estimateTextWidth(
                $line,
                $fontSize,
                (float) ($field['letter_spacing'] ?? 0),
                $field
            ) <= $contentWidth);

            if ($type !== 'paragraph' && count($lines) > 1) {
                continue;
            }

            if ($totalHeight <= $contentHeight && $fitsWidth) {
                return [$fontSize, $lines];
            }
        }

        $fallbackFontSize = $minFontSize;
        $fallbackMaxLines = $this->maxLinesForField($field, $fallbackFontSize, $contentHeight);

        return [
            $fallbackFontSize,
            $this->wrapText(
                $value,
                $contentWidth,
                $fallbackFontSize,
                $fallbackMaxLines,
                (float) ($field['letter_spacing'] ?? 0)
            ),
        ];
    }

    private function maxLinesForField(array $field, float $fontSize, float $contentHeight): int
    {
        if (($field['type'] ?? 'text') !== 'paragraph') {
            return 1;
        }

        $lineHeight = max($fontSize * (float) ($field['line_height'] ?? 1.3), $fontSize * 1.1);
        $maxLines = max((int) floor($contentHeight / max($lineHeight, 1)), 1);

        if (!empty($field['max_lines'])) {
            $maxLines = min($maxLines, (int) $field['max_lines']);
        }

        return max($maxLines, 1);
    }

    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $objectBody) {
            $offsets[] = strlen($pdf);
            $objectNumber = $index + 1;
            $pdf .= "{$objectNumber} 0 obj\n{$objectBody}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function streamObject(string $dictionary, string $stream): string
    {
        return "{$dictionary}\nstream\n{$stream}\nendstream";
    }

    private function jpegImageData(string $path): array
    {
        $raw = file_get_contents($path);

        if (!$raw) {
            throw new RuntimeException('Could not read image file.');
        }

        // Get dimensions without re-encoding through GD
        $size = @getimagesize($path);

        if (!$size || $size[2] !== IMAGETYPE_JPEG) {
            throw new RuntimeException('Template image is not a valid JPEG.');
        }

        $width  = $size[0];
        $height = $size[1];

        // Use raw JPEG bytes directly — no GD re-encoding needed
        return [$raw, $width, $height];
    }

    private function collectFonts(array $fields): array
    {
        $fonts = [];

        foreach ($fields as $field) {
            $fonts[$this->fontResourceKey($field)] = $this->baseFontName($field);
        }

        return $fonts ?: ['HelveticaRegular' => 'Helvetica'];
    }

    private function fontResourceKey(array $field): string
    {
        $family = strtolower((string) ($field['font_family'] ?? 'helvetica'));
        $weight = strtolower((string) ($field['font_weight'] ?? 'normal'));

        $familyKey = str_contains($family, 'times') ? 'Times'
            : (str_contains($family, 'courier') ? 'Courier' : 'Helvetica');

        return $weight === 'bold' ? $familyKey . 'Bold' : $familyKey . 'Regular';
    }

    private function baseFontName(array $field): string
    {
        $family = strtolower((string) ($field['font_family'] ?? 'helvetica'));
        $weight = strtolower((string) ($field['font_weight'] ?? 'normal'));

        if (str_contains($family, 'times')) {
            return $weight === 'bold' ? 'Times-Bold' : 'Times-Roman';
        }

        if (str_contains($family, 'courier')) {
            return $weight === 'bold' ? 'Courier-Bold' : 'Courier';
        }

        return $weight === 'bold' ? 'Helvetica-Bold' : 'Helvetica';
    }

    private function wrapText(
        string $value,
        float $width,
        float $fontSize,
        int $maxLines,
        float $letterSpacing = 0
    ): array
    {
        if ($maxLines <= 1) {
            return [$value];
        }

        $paragraphs = preg_split("/\r\n|\r|\n/", $value) ?: [$value];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph)) ?: [];

            if ($words === [] || $words === ['']) {
                $lines[] = '';
                continue;
            }

            $current = '';

            foreach ($words as $word) {
                if ($this->estimateTextWidth($word, $fontSize, $letterSpacing) > $width) {
                    if ($current !== '') {
                        $lines[] = $current;
                        $current = '';

                        if (count($lines) >= $maxLines) {
                            return array_slice($lines, 0, $maxLines);
                        }
                    }

                    $segments = $this->splitLongToken($word, $width, $fontSize, $letterSpacing);

                    foreach ($segments as $segmentIndex => $segment) {
                        $isLastSegment = $segmentIndex === array_key_last($segments);

                        if ($isLastSegment) {
                            $current = $segment;
                            continue;
                        }

                        $lines[] = $segment;
                        if (count($lines) >= $maxLines) {
                            return array_slice($lines, 0, $maxLines);
                        }
                    }

                    continue;
                }

                $candidate = $current === '' ? $word : "{$current} {$word}";

                if ($this->estimateTextWidth($candidate, $fontSize, $letterSpacing, null) <= $width || $current === '') {
                    $current = $candidate;
                    continue;
                }

                $lines[] = $current;
                $current = $word;

                if (count($lines) >= $maxLines) {
                    return array_slice($lines, 0, $maxLines);
                }
            }

            if ($current !== '') {
                $lines[] = $current;
            }

            if (count($lines) >= $maxLines) {
                return array_slice($lines, 0, $maxLines);
            }
        }

        return array_slice($lines, 0, $maxLines);
    }

    private function splitLongToken(string $token, float $width, float $fontSize, float $letterSpacing): array
    {
        $segments = [];
        $current = '';
        $characters = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($characters as $character) {
            $candidate = $current . $character;

            if ($current !== '' && $this->estimateTextWidth($candidate, $fontSize, $letterSpacing) > $width) {
                $segments[] = $current;
                $current = $character;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $segments[] = $current;
        }

        return $segments ?: [$token];
    }

    private function estimateTextWidth(
        string $value,
        float $fontSize,
        float $letterSpacing = 0,
        ?array $field = null
    ): float
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $width = 0.0;

        foreach ($characters as $character) {
            $width += $fontSize * $this->characterWidthFactor($character, $field);
        }

        return $width + (max(count($characters) - 1, 0) * $letterSpacing);
    }

    private function characterWidthFactor(string $character, ?array $field = null): float
    {
        if ($character === ' ') {
            return 0.28;
        }

        if (preg_match('/[ilI1\.,:;\'`\|]/u', $character)) {
            return 0.24;
        }

        if (preg_match('/[MW@#%&QGOD]/u', $character)) {
            return 0.82;
        }

        if (preg_match('/[A-Z]/u', $character)) {
            return 0.62;
        }

        if (preg_match('/[0-9]/u', $character)) {
            return 0.56;
        }

        $family = strtolower((string) ($field['font_family'] ?? 'helvetica'));

        if (str_contains($family, 'courier')) {
            return 0.60;
        }

        if (str_contains($family, 'times')) {
            return 0.44;
        }

        return 0.49;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }

        $hex = str_pad(substr($hex, 0, 6), 6, '0');

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private function escapePdfString(string $value): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ' '],
            $value
        );
    }

    private function pageSizeInPoints(TemplateVersion $version): array
    {
        $sizes = [
            'A4' => [595.28, 841.89],
            'A3' => [841.89, 1190.55],
            'Letter' => [612.00, 792.00],
            'Legal' => [612.00, 1008.00],
        ];

        if ($version->document_size === 'Custom' && $version->custom_width && $version->custom_height) {
            $width = $version->custom_width * 28.3465;
            $height = $version->custom_height * 28.3465;
        } else {
            [$width, $height] = $sizes[$version->document_size] ?? $sizes['A4'];
        }

        return ($version->orientation ?? 'portrait') === 'landscape'
            ? [$height, $width]
            : [$width, $height];
    }

    private function canvasDimensions(TemplateVersion $version): array
    {
        $sizes = [
            'A4' => [794, 1123],
            'A3' => [1123, 1587],
            'Letter' => [816, 1056],
            'Legal' => [816, 1344],
        ];

        if ($version->document_size === 'Custom' && $version->custom_width && $version->custom_height) {
            $width = round($version->custom_width * 37.8);
            $height = round($version->custom_height * 37.8);
        } else {
            [$width, $height] = $sizes[$version->document_size] ?? $sizes['A4'];
        }

        return ($version->orientation ?? 'portrait') === 'landscape'
            ? [$height, $width]
            : [$width, $height];
    }

    private function scaleX(float $value, float $pageWidth, float $canvasWidth): float
    {
        return $canvasWidth > 0 ? ($value / $canvasWidth) * $pageWidth : $value;
    }

    private function scaleY(float $value, float $pageHeight, float $canvasHeight): float
    {
        return $canvasHeight > 0 ? ($value / $canvasHeight) * $pageHeight : $value;
    }
}
