<?php

namespace App\Services\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pre-checks a submitted workflow transaction for the VPSD office, the way AppointmentReviewAgent
 * checks appointments: it sends the document's answers to Gemini and stores a verdict, a short
 * checklist and suggested wording on the row (ai_flag, ai_findings, ai_reviewed_at).
 *
 * It only advises. Nothing is approved, returned or rejected without a person pressing the button.
 */
class TransactionReviewAgent
{
    /** The checklist the model answers, in display order. */
    public const CHECKS = [
        'completeness' => 'Required answers filled in',
        'clarity' => 'Answers are clear and real',
        'consistency' => 'Dates and details add up',
        'identity' => 'Names match the student',
        'appropriateness' => 'Appropriate for the school',
        'signatories' => 'Right people to sign',
    ];

    public const RECOMMENDATIONS = [
        'send_for_signing' => 'Send for signing',
        'request_changes' => 'Request changes',
        'reject' => 'Reject',
    ];

    /** Field types that hold files or drawings, not answers to read. */
    private const SKIP_TYPES = ['signature', 'file', 'image', 'photo'];

    protected string $model;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->model = config('services.gemini.review_model', 'gemini-2.5-flash');
        $this->apiKey = config('services.gemini.review_key');
    }

    public function review(StudentDocumentWorkspace $ws): void
    {
        if (empty($this->apiKey)) {
            Log::warning('TransactionReviewAgent: GEMINI_REVIEW_API_KEY is not set, skipping review.');
            $this->store($ws, 'review_failed', ['summary' => 'The AI check is not set up (missing API key).']);

            return;
        }

        $ws->loadMissing('user.program');
        $document = DocumentRegistry::get($ws->document_type);

        $payload = [
            'system_instruction' => ['parts' => [['text' => $this->systemPrompt()]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => json_encode($this->snapshot($ws, $document), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]],
            ]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.2,
                'maxOutputTokens' => 8192,
            ],
        ];

        $text = $this->call($payload);
        $this->store($ws, null, $this->normalize($this->parseJson($text)) + ['answers_hash' => self::answersHash($ws)]);
    }

    /** Marks the check as failed (used once the queue gives up). */
    public function markFailed(StudentDocumentWorkspace $ws): void
    {
        $this->store($ws, 'review_failed', ['summary' => 'The AI check could not reach the service. Try again in a moment.']);
    }

    /** Fingerprint of the answers, so the page can tell when a check is out of date. */
    public static function answersHash(StudentDocumentWorkspace $ws): string
    {
        return hash('sha256', json_encode($ws->field_values ?? []));
    }

    /* ------------------------------------------------------------------ */

    private function store(StudentDocumentWorkspace $ws, ?string $flag, array $findings): void
    {
        // Written without touching updated_at or the workflow fields: an advisory note, not an edit.
        StudentDocumentWorkspace::whereKey($ws->getKey())->update([
            'ai_flag' => $flag ?? (($findings['status'] ?? null) === 'flagged' ? 'flagged' : 'clean'),
            'ai_findings' => json_encode($findings),
            'ai_reviewed_at' => now(),
        ]);
    }

    private function call(array $payload): string
    {
        $models = array_values(array_unique(array_filter([
            $this->model,
            config('services.gemini.review_fallback_model'),
        ])));

        $lastError = null;

        foreach ($models as $model) {
            try {
                $response = Http::timeout(30)
                    ->retry([2000, 5000], 0, fn ($e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && in_array($e->response->status(), [429, 500, 503], true)))
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}", $payload);

                $text = $response->successful() ? $response->json('candidates.0.content.parts.0.text') : null;
                if ($text) {
                    return $text;
                }

                $lastError = new \RuntimeException("Gemini {$model} returned " . $response->status());
            } catch (\Throwable $e) {
                $lastError = $e;
            }

            Log::warning('TransactionReviewAgent model failed', ['model' => $model, 'error' => $lastError->getMessage()]);
        }

        throw $lastError ?? new \RuntimeException('No Gemini model responded.');
    }

    /** What the model sees: the form as the office would read it, plus the student's account. */
    private function snapshot(StudentDocumentWorkspace $ws, WorkflowDocument $document): array
    {
        $values = $ws->field_values ?? [];
        $user = $ws->user;

        $answers = [];
        foreach ($document->fields() as $name => $f) {
            if (! empty($f['hidden']) || in_array($f['type'] ?? 'text', self::SKIP_TYPES, true) || Str::endsWith($name, ['_path', '_hash'])) {
                continue;
            }

            $answers[] = [
                'field' => $f['label'] ?? $name,
                'required' => (bool) ($f['required'] ?? false),
                'value' => $this->plain($document, $f, $values[$name] ?? null),
            ];
        }

        $signers = collect($document->chain($values))->map(function ($step) use ($document, $values) {
            if (($step['type'] ?? 'internal') !== 'external') {
                return ['step' => $step['label'], 'signs_as' => 'office signatory (' . ($step['role'] ?? '') . ')'];
            }

            return [
                'step' => $step['label'],
                'signs_as' => 'person named by the student',
                'name' => $document->personName($values, $step['name_field']),
                'email' => trim((string) ($values[$step['email_field']] ?? '')),
            ];
        })->all();

        return [
            'today' => now()->toDateString(),
            'document' => [
                'name' => $document->name(),
                'purpose' => $document->description(),
                'form_code' => method_exists($document, 'formCode') ? $document->formCode() : null,
            ],
            'student_account' => [
                'name' => $user?->full_name,
                'email' => $user?->email,
                'student_number' => $user?->student_number,
                'program' => $user?->program?->name,
                'year_level' => $user?->year_level,
            ],
            // What the form prints from the account (the student cannot type over these).
            'printed_account_details' => collect($document->systemFields())
                ->mapWithKeys(fn ($label, $key) => [$label => (string) ($values[$key] ?? '')])
                ->all(),
            'submitted_at' => $ws->submitted_at?->toDateTimeString(),
            'answers' => $answers,
            'signers' => $signers,
        ];
    }

    private function plain(WorkflowDocument $document, array $f, mixed $v): string
    {
        if (is_array($v)) {
            // Lists of students, rows of slots, ...: keep the readable bits.
            return collect($v)->map(fn ($item) => is_array($item)
                ? collect($item)->filter(fn ($x) => is_scalar($x) && $x !== '')->implode(', ')
                : (string) $item)->filter()->implode('; ');
        }

        try {
            return $document->formatValue($f, $v);
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    /** Keeps the model's answer to the shape the page expects, whatever it sent back. */
    private function normalize(array $r): array
    {
        $risk = max(0, min(100, (int) ($r['risk'] ?? $r['incorrect_probability'] ?? 0)));

        $recommendation = array_key_exists($r['recommendation'] ?? '', self::RECOMMENDATIONS)
            ? $r['recommendation']
            : ($risk >= 70 ? 'reject' : ($risk >= 35 ? 'request_changes' : 'send_for_signing'));

        $checks = [];
        foreach (self::CHECKS as $key => $label) {
            $c = collect($r['checks'] ?? [])->firstWhere('key', $key) ?? [];
            $checks[] = [
                'key' => $key,
                'label' => $label,
                'result' => in_array($c['result'] ?? null, ['pass', 'warn', 'fail'], true) ? $c['result'] : 'pass',
                'note' => Str::limit(trim((string) ($c['note'] ?? '')), 160),
            ];
        }

        $issues = collect($r['issues'] ?? [])->filter(fn ($i) => is_array($i) && ! empty($i['issue']))
            ->take(8)
            ->map(fn ($i) => [
                'field' => Str::limit(trim((string) ($i['field'] ?? 'General')), 60),
                'issue' => Str::limit(trim((string) $i['issue']), 200),
                'severity' => in_array($i['severity'] ?? null, ['low', 'medium', 'high'], true) ? $i['severity'] : 'low',
            ])->values()->all();

        $flagged = $recommendation !== 'send_for_signing'
            || collect($checks)->contains('result', 'fail')
            || collect($issues)->contains('severity', 'high');

        return [
            'status' => $flagged ? 'flagged' : 'clean',
            'recommendation' => $recommendation,
            'risk' => $risk,
            'summary' => Str::limit(trim((string) ($r['summary'] ?? '')), 240),
            'checks' => $checks,
            'issues' => $issues,
            'message' => $recommendation === 'send_for_signing' ? null : Str::limit(trim((string) ($r['message'] ?? '')), 600),
        ];
    }

    private function systemPrompt(): string
    {
        $checks = collect(self::CHECKS)->map(fn ($label, $key) => "- {$key}: {$label}")->implode("\n");

        return <<<PROMPT
        You pre-check student transactions for the Office of the Vice President for Student Development (VPSD)
        of a Philippine university, so the office can decide quickly. You only advise; a person decides.

        You get the document type and its purpose, today's date, the student's account details, every answer
        on the form (with whether it is required), and who will sign.

        Check each of these and give a result of pass, warn or fail with a short note:
        {$checks}

        What to look for:
        - completeness: required answers blank, "N/A", "none" or obviously placeholder where a real answer is needed.
        - clarity: keyboard mashing, gibberish, test text ("asdf", "test", "sample"), answers that do not answer the question.
        - consistency: dates in the wrong order or the wrong tense for the document (an absence that has not happened yet,
          an activity that ends before it starts), impossible times, numbers or IDs in the wrong format, answers that
          contradict each other or the document's purpose.
        - identity: names on the form that should be the student's but are not, or the student naming themself where
          someone else must sign.
        - appropriateness: offensive, discriminatory, unsafe or plainly inappropriate content for a school, or a request
          that does not fit what this document is for.
        - signatories: a signer's email that is the student's own, an empty or malformed email, a signer name that is
          obviously not a person, or a signer who is clearly the wrong kind of person for the step.

        Be fair and practical. Do not flag minor typos, informal wording, or Filipino/English mixing. Do not invent
        rules the form does not state. If you cannot tell, use "warn", not "fail".

        Recommendation:
        - "send_for_signing": nothing a person needs to fix.
        - "request_changes": the student can fix it (missing, unclear or inconsistent answers, wrong signer details).
        - "reject": the request itself is not acceptable (inappropriate, clearly fake, or not what this document is for).

        "risk" is 0-100: how likely the office would not accept the transaction as it is.

        "message" is only for request_changes or reject: two or three plain sentences addressed to the student
        ("Please ..."), saying exactly what to fix or why it cannot be accepted. No greeting, no sign-off.

        Respond with ONLY a JSON object in this exact shape:
        {
          "recommendation": "send_for_signing" | "request_changes" | "reject",
          "risk": 0-100,
          "summary": "one short sentence for the office",
          "checks": [ { "key": "completeness", "result": "pass" | "warn" | "fail", "note": "under 20 words" } ],
          "issues": [ { "field": "field label", "issue": "under 20 words", "severity": "low" | "medium" | "high" } ],
          "message": "text for the student, or null"
        }
        Give one entry in "checks" for every key listed above. "issues" is empty when nothing needs fixing.
        PROMPT;
    }

    private function parseJson(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        throw new \RuntimeException('AI response was not valid JSON: ' . Str::limit($text, 300));
    }
}
