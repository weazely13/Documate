<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends an appointment's purpose text + attached document field values to
 * the Gemini API and asks it to flag anything that looks wrong: garbled
 * or placeholder text, missing/invalid required fields, or content that
 * doesn't match the document being requested.
 *
 * Result is written straight onto the Appointment row (ai_flag, ai_findings,
 * ai_reviewed_at) — see the accompanying migration.
 */
class AppointmentReviewAgent
{
    protected string $model;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->model = config('services.gemini.review_model', 'gemini-2.5-flash');
        $this->apiKey = config('services.gemini.review_key');
    }

    public function review(Appointment $appointment): void
    {
        if (empty($this->apiKey)) {
            Log::warning('AppointmentReviewAgent: GEMINI_API_KEY is not set, skipping review.');
            $appointment->update([
                'ai_flag' => 'review_failed',
                'ai_findings' => ['summary' => 'AI review is not configured (missing API key).'],
                'ai_reviewed_at' => now(),
            ]);
            return;
        }

        $appointment->loadMissing('user.program', 'workspace.template');

        try {
            $payload = [
                'system_instruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => json_encode($this->buildSnapshot($appointment), JSON_PRETTY_PRINT)]],
                ]],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.2,
                    'maxOutputTokens' => 8192,
                ],
            ];

            $models = array_values(array_unique(array_filter([
                $this->model,
                config('services.gemini.review_fallback_model'),
            ])));

            $response = null;
            $lastError = null;

            foreach ($models as $model) {
                try {
                    $response = Http::timeout(30)
                        ->retry([2000, 5000, 10000], 0, function ($exception) {
                            return $exception instanceof \Illuminate\Http\Client\ConnectionException
                                || ($exception instanceof \Illuminate\Http\Client\RequestException
                                    && in_array($exception->response->status(), [429, 500, 503], true));
                        })
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}", $payload);
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                    Log::warning('AppointmentReviewAgent model failed', ['model' => $model, 'error' => $e->getMessage()]);
                    
                }
            }

            if (! $response) {
                throw $lastError ?? new \RuntimeException('No Gemini model responded.');
            }

            if (!$response->successful()) {
                throw new \RuntimeException('Gemini API error ' . $response->status() . ': ' . $response->body());
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (!$text) {
                throw new \RuntimeException('No text content returned by the model.');
            }

            $result = $this->parseJson($text);

            $probability = (int) ($result['incorrect_probability'] ?? 0);
            $probability = max(0, min(100, $probability));

            $recommendation = in_array($result['recommendation'] ?? null, ['approve', 'reject'], true)
                ? $result['recommendation']
                : ($probability >= 50 ? 'reject' : 'approve');

            $validCategories = ['missing_fields', 'identity_mismatch', 'garbled_text', 'purpose_mismatch', 'invalid_format', 'other'];
            $reasonCategory = $recommendation === 'reject'
                ? (in_array($result['reason_category'] ?? null, $validCategories, true) ? $result['reason_category'] : 'other')
                : null;

            $result['incorrect_probability'] = $probability;
            $result['recommendation'] = $recommendation;
            $result['reason_category'] = $reasonCategory;

            // Does the purpose fit the attached document (or, without one, the office's transactions)?
            $relevance = is_array($result['relevance'] ?? null) ? $result['relevance'] : [];
            $result['relevance'] = [
                'relevant' => (bool) ($relevance['relevant'] ?? true),
                'note' => mb_substr(trim((string) ($relevance['note'] ?? '')), 0, 200),
            ];
            $appointment->update([
                'ai_flag' => ($result['status'] ?? null) === 'flagged' ? 'flagged' : 'clean',
                'ai_findings' => $result,
                'ai_reviewed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AppointmentReviewAgent failed', [
                'appointment_id' => $appointment->appointment_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** Called by the job once its retries are used up. */
    public function markFailed(Appointment $appointment): void
    {
        $appointment->update([
            'ai_flag' => 'review_failed',
            'ai_findings' => ['summary' => 'The AI review service was unavailable. Please try again.'],
            'ai_reviewed_at' => now(),
        ]);
    }
        

    /**
     * What the model sees: the purpose, the attached document read the way the office reads it
     * (name, what it is for, where it stands, its answers), the student, and the transactions
     * the office handles, so it can judge whether the purpose fits.
     */
    protected function buildSnapshot(Appointment $appointment): array
    {
        $workspace = $appointment->workspace;
        $doc = $workspace?->document_type ? \App\Workflow\Documents\DocumentRegistry::find($workspace->document_type) : null;

        $answers = null;
        if ($workspace && $doc) {
            $values = $workspace->field_values ?? [];
            $answers = [];
            foreach ($doc->fields() as $name => $f) {
                if (! empty($f['hidden']) || in_array($f['type'] ?? 'text', ['signature', 'file', 'image', 'photo'], true)) {
                    continue;
                }
                $v = $values[$name] ?? null;
                try {
                    $answers[$f['label'] ?? $name] = is_array($v) ? json_encode($v) : $doc->formatValue($f, $v);
                } catch (\Throwable) {
                    $answers[$f['label'] ?? $name] = is_scalar($v) ? (string) $v : json_encode($v);
                }
            }
        } elseif ($workspace) {
            $answers = $workspace->field_values ?? null;
        }

        $handled = \App\Support\AppointmentPurposes::labels();

        return [
            'purpose_of_visit' => $appointment->purpose,
            'attached_document' => $workspace ? [
                'name' => $workspace->documentName(),
                'what_it_is_for' => $doc?->description() ?? 'A paper form handled at the office.',
                'where_it_stands' => $workspace->workflow_status?->label() ?? $workspace->status,
                'answers' => $answers,
            ] : null,
            'transactions_the_office_handles' => $handled,
            'student' => [
                'name' => $appointment->user->full_name,
                'student_number' => $appointment->user->student_number,
                'program' => $appointment->user->program?->name,
                'year_level' => $appointment->user->year_level,
            ],
        ];
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
    You are a document-and-form quality checker for a university registrar's appointment system.
    You will be given the purpose-of-transaction text a student typed, and — if the appointment
    has a document attached — the field values they filled into a document template.

    You also get the list of transactions the office handles.

    RELEVANCE (always check this):
    - With a document attached: does the purpose of the visit fit that document and what it is for?
      A purpose about one transaction with a different document attached (for example an off-campus
      activity permit as the purpose and an Admission Slip attached) is a purpose_mismatch.
      A general purpose such as an inquiry is fine with any document.
    - With no document attached: is the purpose something the office handles (one of the listed
      transactions, or a sensible inquiry about records or requirements)? A purpose that names one of
      the listed transactions is relevant. Do NOT flag the absence of a document by itself.
    Report it in "relevance". When it is not relevant, also list it in "issues" and use reason_category
    "purpose_mismatch" if you recommend rejecting.

    Check for:
    - Garbled, nonsensical, or placeholder text (e.g. "asdf", keyboard mashing, "N/A" where a real answer is required)
    - Missing or invalid required fields (blank required fields, a name field containing numbers, an invalid date, etc.)
    - Spelling/grammar severe enough to be unclear or unprofessional — do not nitpick minor typos
    - Mismatches between the stated purpose and the document template being requested
    - Identity mismatches (e.g. the submitting student's name doesn't match a required signatory field)

    Respond with ONLY a JSON object in this exact shape:
    {
    "status": "clean" | "flagged",
    "recommendation": "approve" | "reject",
    "reason_category": "missing_fields" | "identity_mismatch" | "garbled_text" | "purpose_mismatch" | "invalid_format" | "other" | null,
    "incorrect_probability": 0-100,
    "summary": "one short sentence overview",
    "relevance": { "relevant": true | false, "note": "one short sentence on how the purpose fits the document" },
    "issues": [
        { "field": "which field or section", "issue": "one short sentence, under 20 words", "severity": "low" | "medium" | "high" }
    ]
    }

    reason_category definitions (pick the ONE that best explains why this should be rejected; null if recommendation is "approve"):
    - missing_fields: required fields are blank or clearly incomplete
    - identity_mismatch: names/signatories don't match who is submitting or who should be involved
    - garbled_text: nonsensical, keyboard-mashed, or placeholder text in place of real answers
    - purpose_mismatch: the stated purpose doesn't match the document template being requested
    - invalid_format: dates, numbers, or IDs are present but malformed/invalid
    - other: a real issue exists but doesn't fit the categories above

    Set "recommendation" to "reject" whenever incorrect_probability >= 50, otherwise "approve".
    If everything looks fine, return "status": "clean", "recommendation": "approve", "reason_category": null, "incorrect_probability" near 0, and an empty issues array.
    PROMPT;
    }

    protected function parseJson(string $text): array
    {
        $decoded = json_decode(trim($text), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Likely truncated mid-response — try to close it off and recover what we can
        $repaired = $this->attemptJsonRepair($text);
        if ($repaired !== null) {
            return $repaired;
        }

        throw new \RuntimeException('AI response was not valid JSON (possibly truncated): ' . substr($text, 0, 500));
    }

    protected function attemptJsonRepair(string $text): ?array
    {
        // Truncate to the last complete object in the issues array, then close the JSON manually
        $text = rtrim($text);
        $lastCompleteObject = strrpos($text, '},');
        if ($lastCompleteObject === false) {
            return null;
        }

        $repaired = substr($text, 0, $lastCompleteObject + 1) . ']}';
        $decoded = json_decode($repaired, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
    }
}