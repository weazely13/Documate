<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI insights for the Reports page. Uses the same Gemini key and models as Document Uploads
 * (services.gemini.document_ocr_*). Only aggregated figures are sent — never student names.
 */
class ReportInsightsService
{
    /**
     * @param array $context The report's figures (see Reports::insightContext())
     * @param array<int, array{role: string, text: string}> $history Earlier turns, oldest first
     * @return array{ok: bool, text: string}
     */
    public function ask(string $question, array $context, array $history = []): array
    {
        $apiKey = config('services.gemini.document_ocr_key');
        if (! $apiKey) {
            return ['ok' => false, 'text' => 'AI insights are not set up yet: the Gemini key for Document Uploads (GEMINI_DOCUMENT_OCR_KEY) is missing.'];
        }

        $system = <<<PROMPT
        You are DocuMate's report analyst for a university student-affairs office. Admins ask about the
        report they are looking at: transaction forms students filed, students by organization, and
        semester clearance. Answer ONLY from the JSON figures given. Never invent numbers; if the data
        cannot answer, say what is missing. Be brief and concrete: start with a one-sentence answer,
        then at most 5 bullet points or one small Markdown table (e.g. Metric | This period | Previous | Change).
        Bold the key figures. Suggest one practical next step when useful. No preamble, no headings above level 3.
        PROMPT;

        $contents = [];
        foreach (array_slice($history, -8) as $turn) {
            $contents[] = ['role' => $turn['role'] === 'user' ? 'user' : 'model', 'parts' => [['text' => $turn['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => "Report data (JSON):\n" . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\nQuestion: " . $question]]];

        $text = $this->generate($apiKey, $system, $contents, ['temperature' => 0.3, 'maxOutputTokens' => 1200]);

        return $text !== null
            ? ['ok' => true, 'text' => $text]
            : ['ok' => false, 'text' => 'The AI could not answer right now. Please try again in a moment.'];
    }

    /**
     * The words of the Reports PDF: conclusion, context, one detail per key area, and actions.
     * The results and priorities in $assessment are decided by ReportAssessment's rules; the AI
     * only explains them. Without a key, or when the call fails, plain sentences built from the
     * same figures are returned instead (ok = false), so the PDF is never left without words.
     *
     * @param array $context aggregated report figures (Reports::insightContext()); no student names
     * @param array $assessment ReportAssessment::for() rows
     * @return array{ok: bool, conclusion: string[], context: string[], details: array<string, string>, actions: string[]}
     */
    public function draftReport(array $context, array $assessment): array
    {
        $fallback = $this->fallbackDraft($context, $assessment);
        $apiKey = config('services.gemini.document_ocr_key');
        if (! $apiKey) {
            return $fallback;
        }

        $areas = array_map(fn ($a) => array_intersect_key($a, array_flip(['key', 'area', 'objective', 'valueText', 'result', 'priority', 'basis'])), $assessment);
        $keys = implode(', ', array_column($assessment, 'key'));

        $system = <<<'PROMPT'
        You write the wording of an official activity report for the Office of the Vice President for Student
        Development (OVPSD) of Leyte Normal University. The reader is a university administrator, not a data analyst.
        Rules:
        - Use ONLY the figures given. Never invent numbers, names, causes or dates.
        - Each key area already has a result ("ok" or "attention") and a priority ("High", "Medium" or none) set by
          fixed rules. Never change, soften or contradict them; explain them.
        - Plain, formal English. One sentence per bullet, at most 30 words. No markdown, no emojis.
        - Be balanced: say what is going well as well as what needs work.
        Return JSON only, with exactly these keys:
        {"conclusion": [3 to 5 strings: overall picture, what is going well, where to improve, what it means for the office],
         "context": [3 or 4 strings: size of the period, what it covered, what changed against the comparison, why certain areas stand out],
         "details": {"<area key>": "one sentence explaining that area's result"},
         "actions": [2 to 4 strings: practical next steps, one per area needing attention, High priority first; if none need attention, one step to keep things on track]}
        PROMPT;

        $text = $this->generate($apiKey, $system, [[
            'role' => 'user',
            'parts' => [['text' => "Report figures (JSON):\n" . json_encode($context, JSON_UNESCAPED_UNICODE)
                . "\n\nKey areas with their fixed results (JSON):\n" . json_encode($areas, JSON_UNESCAPED_UNICODE)
                . "\n\nWrite one detail for each of these area keys: {$keys}."]],
        ]], ['temperature' => 0.2, 'maxOutputTokens' => 8192, 'responseMimeType' => 'application/json']);

        $data = $text !== null ? $this->parseJson($text) : null;
        if (! is_array($data)) {
            if ($text !== null) {
                Log::warning('ReportInsightsService: draft was not valid JSON', ['length' => strlen($text), 'start' => substr($text, 0, 200), 'end' => substr($text, -120)]);
            }
            return $fallback;
        }

        $lines = fn ($v, int $max) => array_values(array_slice(array_filter(array_map(fn ($x) => is_string($x) ? trim($x) : '', (array) $v)), 0, $max));
        $details = [];
        foreach ($assessment as $a) {
            $d = $data['details'][$a['key']] ?? null;
            $details[$a['key']] = is_string($d) && trim($d) !== '' ? trim($d) : $fallback['details'][$a['key']];
        }

        return [
            'ok' => true,
            'conclusion' => $lines($data['conclusion'] ?? [], 5) ?: $fallback['conclusion'],
            'context' => $lines($data['context'] ?? [], 4) ?: $fallback['context'],
            'details' => $details,
            'actions' => $lines($data['actions'] ?? [], 4) ?: $fallback['actions'],
        ];
    }

    /** The JSON object in a reply, even when it comes wrapped in a code fence or with text around it. */
    private function parseJson(string $text): ?array
    {
        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }

    /** Plain sentences from the same figures, for when the AI is not available. */
    public function fallbackDraft(array $context, array $assessment): array
    {
        $attention = array_values(array_filter($assessment, fn ($a) => $a['result'] === 'attention'));
        usort($attention, fn ($a, $b) => ($a['priority'] === 'High' ? 0 : 1) <=> ($b['priority'] === 'High' ? 0 : 1));
        $ok = array_values(array_filter($assessment, fn ($a) => $a['result'] === 'ok' && $a['priority'] === null && $a['value'] !== null));
        $date = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j, Y');
        $period = $date($context['period']['from']) . ' to ' . $date($context['period']['to']);
        $report = $context['report'];

        $conclusion = array_values(array_filter([
            $attention
                ? count($attention) . ' of ' . count($assessment) . ' key areas of the ' . strtolower($report) . ' report need attention for ' . $period . '.'
                : 'Every key area of the ' . strtolower($report) . ' report is on track for ' . $period . '.',
            $ok ? 'On track: ' . implode(', ', array_map(fn ($a) => strtolower($a['area']) . ' (' . $a['valueText'] . ')', $ok)) . '.' : null,
            $attention ? 'Needs attention: ' . implode(', ', array_map(fn ($a) => strtolower($a['area']) . ' (' . $a['valueText'] . ', ' . $a['priority'] . ' priority)', $attention)) . '.' : null,
        ]));

        $t = $context['forms_this_period'];
        $contextLines = array_values(array_filter([
            $report === 'Clearance'
                ? ($context['active_students'] . ' active students were reviewed for ' . ($context['semester'] ?? 'the semester') . '.')
                : ($t['requests'] . ' requests were filed by ' . $t['students_who_filed'] . ' students in the period.'),
            $context['compared_with'] ? 'Figures are compared with ' . $date($context['compared_with']['from']) . ' to ' . $date($context['compared_with']['to']) . '.' : null,
            ! empty($context['by_form'][0]) && $report !== 'Clearance' ? 'The most requested form was ' . $context['by_form'][0]['form'] . ' (' . $context['by_form'][0]['requests'] . ' requests).' : null,
        ]));

        $details = [];
        foreach ($assessment as $a) {
            $details[$a['key']] = $a['basis'];
        }

        $actions = $attention
            ? array_map(fn ($a) => 'Follow up on ' . strtolower($a['area']) . ' (' . $a['priority'] . ' priority): ' . $a['basis'], array_slice($attention, 0, 4))
            : ['Keep the current pace and check this report again next period.'];

        return ['ok' => false, 'conclusion' => $conclusion, 'context' => $contextLines, 'details' => $details, 'actions' => $actions];
    }

    /** One Gemini call with the Document Uploads key and model fallback. Null when nothing came back. */
    private function generate(string $apiKey, string $system, array $contents, array $config): ?string
    {
        $models = array_values(array_unique(array_filter([
            config('services.gemini.document_ocr_model', 'gemini-3.6-flash'),
            config('services.gemini.document_ocr_fallback_model'),
        ])));

        foreach ($models as $model) {
            try {
                $response = Http::timeout(60)
                    ->retry([1500, 4000], 0, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException
                        || ($e instanceof \Illuminate\Http\Client\RequestException && in_array($e->response->status(), [429, 500, 503], true)), throw: false)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                        'systemInstruction' => ['parts' => [['text' => $system]]],
                        'contents' => $contents,
                        'generationConfig' => $config,
                    ]);

                $text = $response->successful() ? trim((string) $response->json('candidates.0.content.parts.0.text')) : '';
                if ($text !== '') {
                    return $text;
                }

                Log::warning('ReportInsightsService: Gemini call failed', ['model' => $model, 'status' => $response->status(), 'body' => substr($response->body(), 0, 300)]);
            } catch (\Throwable $e) {
                Log::warning('ReportInsightsService: Gemini call threw', ['model' => $model, 'message' => $e->getMessage()]);
            }
        }

        return null;
    }
}
