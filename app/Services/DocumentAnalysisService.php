<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentAnalysisService
{
    /**
     * @param array<int, array{label: string, expected_value: ?string}> $expectedFields
     * @param array{mime_type: string, data: string}|null $referenceImage Base64 image of the
     *        expected filled document (e.g. rendered from the generated PDF), used so the model
     *        can visually cross-check field values instead of only reasoning about a text list.
     */
    public static function analyze(
        string $filePath,
        string $documentName,
        array $expectedFields = [],
        ?array $referenceImage = null
    ): array {
        $fullPath = Storage::disk('public')->path($filePath);

        if (!file_exists($fullPath)) {
            return self::failure('file_missing', "Stored file not found on disk at path: {$filePath}");
        }

        $apiKey = config('services.gemini.document_ocr_key');
        if (!$apiKey) {
            return self::failure('missing_api_key', 'Gemini API key is not configured (services.gemini.document_ocr_key).');
        }

        $mimeType = mime_content_type($fullPath);
        $imageData = base64_encode(file_get_contents($fullPath));

        $fieldsWithValues = array_filter($expectedFields, fn ($f) => filled($f['expected_value'] ?? null));

        $fieldsList = $expectedFields
            ? implode(', ', array_column($expectedFields, 'label'))
            : 'no specific fields provided';

        $expectedValuesList = $fieldsWithValues
            ? collect($fieldsWithValues)
                ->map(fn ($f) => "- {$f['label']}: \"{$f['expected_value']}\"")
                ->implode("\n")
            : 'None — no expected values were provided to compare against.';

        $referenceInstructions = $referenceImage
            ? <<<TXT
You have been given TWO images:
- Image 1: a physical photo the student uploaded as proof the document was processed at the office.
- Image 2: a reference render of exactly what the document should contain — this shows the correct, expected value for every field, generated from what the student actually entered digitally.

Use Image 2 as ground truth for what each field's value should read. Compare it directly against what you see written/printed in Image 1 for the corresponding field. This is a much stronger signal than reasoning from a text list alone, so prioritize the visual comparison between the two images over the text list below.
TXT
            : "You have been given ONE image: a physical photo the student uploaded as proof the document was processed at the office. No reference render was available, so rely on the expected text values listed below.";

        $prompt = <<<PROMPT
You are reviewing a photo submitted by a student as proof they had a "{$documentName}" physically processed at the office.

{$referenceInstructions}

1. Determine whether Image 1 is actually a photo of a "{$documentName}" (or a clearly related processed copy) — not an unrelated photo, screenshot, or random image.
2. Transcribe all readable text from Image 1, preserving structure.
3. Check whether these fields/blanks appear filled in on Image 1: {$fieldsList}. List any that look blank or missing.
4. Note whether an official signature and/or stamp/seal is visible on Image 1.
5. Note anything suspicious, tampered, or inconsistent that reduces confidence in authenticity.
6. For each of the fields below, state what you actually see written/printed for it on Image 1, and whether it matches the expected value (from Image 2 if provided, otherwise from the text below). Treat minor differences in spacing, capitalization, or date formatting (e.g. "Jan 5, 2026" vs "01/05/2026") as a match. Only flag it as NOT matching if the substantive content is genuinely different (a different name, a different number, a different date, etc). If a field is blank or illegible on Image 1, mark it as not matching and say why.
Expected values:
{$expectedValuesList}
7. Write a short (2-4 sentence) plain-language summary suitable for a non-technical admin, mentioning any field mismatches if found.

Return ONLY valid JSON, no markdown:
{
  "matches_document": true,
  "readable": true,
  "extracted_text": "",
  "fields_complete": true,
  "missing_fields": [],
  "has_signature_or_stamp": true,
  "authenticity_notes": "",
  "field_matches": [
    {"label": "", "expected_value": "", "found_value": "", "matches": true}
  ],
  "fields_match_expected": true,
  "summary": ""
}
PROMPT;

        $parts = [["text" => $prompt]];

        if ($referenceImage) {
            $parts[] = ["inlineData" => ["mimeType" => $referenceImage['mime_type'], "data" => $referenceImage['data']]];
        }

        $parts[] = ["inlineData" => ["mimeType" => $mimeType, "data" => $imageData]];

        $payload = [
            "contents" => [[
                "parts" => $parts,
            ]],
        ];

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}",
                    $payload
                );
        } catch (\Throwable $e) {
            Log::error('DocumentAnalysisService: request threw', ['message' => $e->getMessage()]);
            return self::failure('request_exception', 'The request to the review service failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            Log::error('DocumentAnalysisService: non-2xx response', ['status' => $status, 'body' => $body]);
            return self::failure(
                'api_error_' . $status,
                "The review service returned an error (HTTP {$status})."
            );
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            $finishReason = $json['candidates'][0]['finishReason'] ?? null;
            $promptBlocked = $json['promptFeedback']['blockReason'] ?? null;

            Log::error('DocumentAnalysisService: empty response text', [
                'finish_reason' => $finishReason,
                'block_reason' => $promptBlocked,
                'raw' => $json,
            ]);

            if ($promptBlocked) {
                return self::failure('content_blocked', "The image was blocked by the review service's safety filters ({$promptBlocked}).");
            }

            return self::failure('empty_response', 'The review service returned an empty response' . ($finishReason ? " (reason: {$finishReason})." : '.'));
        }

        preg_match('/\{.*\}/s', $text, $matches);
        if (!isset($matches[0])) {
            Log::error('DocumentAnalysisService: no JSON found in response text', ['text' => $text]);
            return self::failure('unparseable_response', 'The review service response did not contain valid JSON.');
        }

        $data = json_decode($matches[0], true);
        if (!is_array($data)) {
            Log::error('DocumentAnalysisService: json_decode failed', [
                'json_error' => json_last_error_msg(),
                'raw_match' => $matches[0],
            ]);
            return self::failure('invalid_json', 'The review service response could not be parsed: ' . json_last_error_msg());
        }

        return [
            'success' => true,
            'error_code' => null,
            'error_message' => null,
            'matches_document' => (bool) ($data['matches_document'] ?? false),
            'readable' => (bool) ($data['readable'] ?? false),
            'extracted_text' => $data['extracted_text'] ?? '',
            'fields_complete' => (bool) ($data['fields_complete'] ?? false),
            'missing_fields' => $data['missing_fields'] ?? [],
            'has_signature_or_stamp' => (bool) ($data['has_signature_or_stamp'] ?? false),
            'authenticity_notes' => $data['authenticity_notes'] ?? '',
            'field_matches' => $data['field_matches'] ?? [],
            'fields_match_expected' => (bool) ($data['fields_match_expected'] ?? true),
            'summary' => $data['summary'] ?? '',
            'used_reference_image' => $referenceImage !== null,
        ];
    }

    private static function failure(string $code, string $message): array
    {
        return [
            'success' => false,
            'error_code' => $code,
            'error_message' => $message,
        ];
    }
}