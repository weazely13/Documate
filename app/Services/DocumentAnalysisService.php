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
        $classification = self::classifyUpload($apiKey, $documentName, $mimeType, $imageData);

            if ($classification === null) {
                return self::failure('request_exception', 'The pre-check of the uploaded photo failed.');
            }

            if (! ($classification['is_target_form'] ?? false)) {
                $type = $classification['detected_document_type'] ?? 'unrelated image';

                return [
                    'success' => true,
                    'error_code' => null,
                    'error_message' => null,
                    'matches_document' => false,
                    'detected_document_type' => $type,
                    'unrelated_reason' => "The upload shows: {$type}",
                    'readable' => false,
                    'extracted_text' => '',
                    'fields_complete' => false,
                    'missing_fields' => [],
                    'has_signature_or_stamp' => false,
                    'authenticity_notes' => '',
                    'field_matches' => [],
                    'fields_match_expected' => false,
                    'summary' => "The uploaded image is not a {$documentName}. It appears to be: {$type}.",
                    'used_reference_image' => false,
                ];
            }

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

1. Decide what Image 1 actually shows. Set "matches_document" to true ONLY if Image 1 is a photo or scan of a "{$documentName}" form, meaning the same form title, section headings and field layout as the reference. Set it to FALSE if Image 1 is any of these: a different kind of document, a screenshot of a website or app, a selfie or random photo, a blank page, or a plain screenshot with no form in it. If matches_document is false, also set readable, fields_complete, has_signature_or_stamp and fields_match_expected to false.
2. Transcribe all readable text from Image 1, preserving structure.
3. Check whether these fields/blanks appear filled in on Image 1: {$fieldsList}. List any that look blank or missing.
4. Note whether an official signature and/or stamp/seal is visible on Image 1.
5. Note anything suspicious, tampered, or inconsistent that reduces confidence in authenticity.
6. For each of the fields below, state what you actually see written/printed for it on Image 1, and whether it matches the expected value (from Image 2 if provided, otherwise from the text below). Treat minor differences in spacing, capitalization, or date formatting (e.g. "Jan 5, 2026" vs "01/05/2026") as a match. Only flag it as NOT matching if the substantive content is genuinely different (a different name, a different number, a different date, etc). If a field is blank or illegible on Image 1, mark it as not matching and say why.
Expected values:
{$expectedValuesList}
7. Write a short (2-4 sentence) plain-language summary suitable for a non-technical admin, mentioning any field mismatches if found.

Use "detected_document_type" for what Image 1 actually is (e.g. "Student Complaint Form", "website screenshot"). Fill "unrelated_reason" only when matches_document is false.

Return ONLY valid JSON, no markdown:
{
  "matches_document": true,
  "detected_document_type": "",
  "unrelated_reason": "",
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

        // Upload FIRST, so position and label agree: Image 1 = upload, Image 2 = reference
        $parts[] = ["text" => "IMAGE 1 (STUDENT UPLOAD, the only image you are judging):"];
        $parts[] = ["inlineData" => ["mimeType" => $mimeType, "data" => $imageData]];

        if ($referenceImage) {
            $parts[] = ["text" => "IMAGE 2 (REFERENCE RENDER, ground truth only, never judge it, never report it as the upload):"];
            $parts[] = ["inlineData" => ["mimeType" => $referenceImage['mime_type'], "data" => $referenceImage['data']]];
        }
        $payload = [
            "contents" => [["parts" => $parts]],
            "generationConfig" => [
                "temperature" => 0,
                "responseMimeType" => "application/json",
            ],
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

       $matches = (bool) ($data['matches_document'] ?? false);
       // Sanity check: the transcription of a real complaint form should contain the form's title words
        $transcript = strtolower($data['extracted_text'] ?? '');
        if ($matches && $transcript !== '' && ! str_contains($transcript, 'complaint') && ! str_contains($transcript, 'grievance')) {
            $matches = false;
        }

        return [
            'success' => true,
            'error_code' => null,
            'error_message' => null,
            'matches_document' => $matches,
            'detected_document_type' => $data['detected_document_type'] ?? '',
            'unrelated_reason' => $data['unrelated_reason'] ?? '',
            'readable' => $matches && (bool) ($data['readable'] ?? false),
            'extracted_text' => $data['extracted_text'] ?? '',
            'fields_complete' => $matches && (bool) ($data['fields_complete'] ?? false),
            'missing_fields' => $data['missing_fields'] ?? [],
            'has_signature_or_stamp' => $matches && (bool) ($data['has_signature_or_stamp'] ?? false),
            'authenticity_notes' => $data['authenticity_notes'] ?? '',
            'field_matches' => $data['field_matches'] ?? [],
            'fields_match_expected' => $matches && (bool) ($data['fields_match_expected'] ?? true),
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
    private static function classifyUpload(string $apiKey, string $documentName, string $mimeType, string $imageData): ?array
    {
        $prompt = <<<PROMPT
    Look at this single image. Is it a photo or scan of a paper form titled "{$documentName}"?

    Answer false if it is a screenshot of a website, app, browser, code editor or dashboard, a different document, a selfie, or any other photo.

    Return ONLY JSON:
    {"is_target_form": false, "detected_document_type": "", "visible_title_text": ""}
    PROMPT;

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['parts' => [
                            ['text' => $prompt],
                            ['inlineData' => ['mimeType' => $mimeType, 'data' => $imageData]],
                        ]]],
                        'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
                    ]
                );
        } catch (\Throwable $e) {
            Log::error('DocumentAnalysisService: classify threw', ['message' => $e->getMessage()]);
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $data = $text ? json_decode($text, true) : null;

        return is_array($data) ? $data : null;
    }
}