<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\GeminiQuotaExceededException;
use Illuminate\Support\Facades\Log;

class GeminiOCRService
{
    public static function extractText($filePath)
    {
        try {
            $apiKey = config('services.gemini.eslip_key');
            $model  = config('services.gemini.eslip_model');

            if (blank($apiKey)) {
                Log::error('GeminiOCR: services.gemini.key is empty. Check .env and run php artisan config:clear');
                return null;
            }

            $fullPath = Storage::disk('local')->path($filePath);

            if (!file_exists($fullPath)) {
                return null;
            }

            $mimeType = mime_content_type($fullPath);
            $imageData = base64_encode(file_get_contents($fullPath));

            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => "Extract structured data from this student enrollment slip.

            IMPORTANT:
            - Extract EXACT values from the image
            - DO NOT guess missing fields
            - If not found, return null
            - Combine multi-line values if needed

            FIELDS TO EXTRACT:
            - student_number
            - first_name
            - last_name
            - full_name (the complete name exactly as printed on the slip, in original order, including any middle initial)
            - date
            - semester
            - academic_year
            - college
            - course
            - year
            - section
            - officially_enrolled (true if a stamp/text like 'OFFICIALLY ENROLLED' is present, otherwise false)
            - stamp_text (the exact, verbatim text printed inside or immediately around the enrollment stamp — e.g. 'OFFICIALLY ENROLLED', registrar remarks, seal wording. Transcribe exactly as shown, null if no stamp found)
            - stamp_registrar (the name of the office/registrar/signatory on the stamp, if legible, otherwise null)
            - stamp_date (the date printed or handwritten on the stamp, if legible, otherwise null)
            - processed_by (printed name of the registrar staff/officer who processed the enrollment, usually near the signature line or stamp; null if not found)
            - has_signature (true ONLY if a handwritten or digital signature mark is visibly present on the processor's signature line; a printed name alone does NOT count; otherwise false)
            - processed_date (date the enrollment was processed, as printed/handwritten; null if not found)
            - processed_time (time the enrollment was processed, e.g. '10:32 AM'; null if not found)

            RETURN ONLY VALID JSON:

            {
            \"student_number\": \"\",
            \"first_name\": \"\",
            \"last_name\": \"\",
            \"full_name\": \"\",
            \"date\": \"\",
            \"semester\": \"\",
            \"academic_year\": \"\",
            \"college\": \"\",
            \"course\": \"\",
            \"year\": \"\",
            \"section\": \"\",
            \"officially_enrolled\": true,
            \"stamp_text\": \"\",
            \"stamp_registrar\": \"\",
            \"stamp_date\": \"\",
            \"processed_by\": \"\",
            \"has_signature\": false,
            \"processed_date\": \"\",
            \"processed_time\": \"\"
            }"
                            ],
                            [
                                "inline_data" => [
                                    "mime_type" => $mimeType,
                                    "data" => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $payload['generationConfig'] = [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
                'thinkingConfig' => ['thinkingBudget' => 0],
            ];

            $response = Http::withoutVerifying()
                ->timeout(60)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    $payload
                );

            if ($response->status() === 429 || $response->json('error.status') === 'RESOURCE_EXHAUSTED') {
                Log::warning('GeminiOCR: quota/rate limit hit', ['body' => $response->body()]);
                throw new GeminiQuotaExceededException('Gemini quota exceeded.');
            }

            if (!$response->successful()) {
                Log::error('GeminiOCR: request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $json = $response->json();

            $text = collect($json['candidates'][0]['content']['parts'] ?? [])
                ->first(fn ($p) => isset($p['text']) && empty($p['thought']))['text'] ?? null;

            if (!$text) {
                Log::error('GeminiOCR: no text in response', ['response' => $json]);
                return null;
            }

            // Extract JSON
            preg_match('/\{.*\}/s', $text, $matches);

            if (!isset($matches[0])) {
                return null;
            }

            $data = json_decode($matches[0], true);

            if (!is_array($data)) {
                return null;
            }

            return [
                'student_number' => $data['student_number'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'full_name' => $data['full_name'] ?? null,
                'enrollment_date' => $data['date'] ?? null,
                'semester' => $data['semester'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'college' => $data['college'] ?? null,
                'course' => $data['course'] ?? null,
                'year' => $data['year'] ?? null,
                'section' => $data['section'] ?? null,
                'officially_enrolled' => $data['officially_enrolled'] ?? false,
                'stamp_text' => $data['stamp_text'] ?? null,
                'stamp_registrar' => $data['stamp_registrar'] ?? null,
                'stamp_date' => $data['stamp_date'] ?? null,
                'processed_by'   => $data['processed_by'] ?? null,
                'has_signature'  => $data['has_signature'] ?? false,
                'processed_date' => $data['processed_date'] ?? null,
                'processed_time' => $data['processed_time'] ?? null,
            ];

        } catch (GeminiQuotaExceededException $e) {
            throw $e; // let the component show the quota notice
        } catch (\Throwable $e) {
            Log::error('GeminiOCR exception: ' . $e->getMessage());
            return null;
        }
    }
}