<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A short, plain-language summary of a student's own activity, written by Gemini with the same
 * key and model as the enrollment-slip verification (services.gemini.eslip_*).
 *
 * Only figures, form names and statuses are sent: never names, student numbers or email addresses.
 * Results are cached per student and per set of facts, so the API is called only when something changed.
 */
class ActivitySummaryService
{
    private const CACHE_HOURS = 6;

    /**
     * @return array{headline: string, summary: string, next_steps: list<string>, generated_at: string}
     *
     * @throws GeminiQuotaExceededException when the API's quota or rate limit is hit
     * @throws \RuntimeException when the summary could not be produced
     */
    public function summarize(int $userId, array $facts, bool $fresh = false): array
    {
        $key = $this->cacheKey($userId, $facts);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addHours(self::CACHE_HOURS), fn () => $this->ask($facts));
    }

    public function cached(int $userId, array $facts): ?array
    {
        return Cache::get($this->cacheKey($userId, $facts));
    }

    private function cacheKey(int $userId, array $facts): string
    {
        return 'activity-summary:' . $userId . ':' . md5(json_encode($facts));
    }

    private function ask(array $facts): array
    {
        $apiKey = config('services.gemini.eslip_key');
        $model = config('services.gemini.eslip_model');

        if (blank($apiKey) || blank($model)) {
            throw new \RuntimeException('The AI summary is not set up (services.gemini.eslip_key / eslip_model).');
        }

        $prompt = <<<PROMPT
        You write a short activity summary for a university student using DocuMate, the paperless forms
        system of the Office of the Vice President for Student Development (VPSD) at Leyte Normal University.

        RULES
        - Use ONLY the facts in the JSON below. Never invent documents, dates, people or numbers.
        - Speak to the student as "you". Plain, warm, specific English. No markdown, no emoji.
        - Lead with what matters most: anything that needs the student, then progress, then the trend.
        - Mention real form names and counts from the facts where they help.
        - If there is little or no activity, say so kindly and suggest starting a form from Home.

        RETURN ONLY JSON:
        {
          "headline": "one sentence, at most 12 words",
          "summary": "two or three sentences",
          "next_steps": ["up to 3 short actions the student can take, most urgent first; empty list if none"]
        }

        FACTS
        PROMPT;

        $response = Http::withoutVerifying()
            ->timeout(45)
            ->withHeaders(['Content-Type' => 'application/json', 'x-goog-api-key' => $apiKey])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [['parts' => [['text' => $prompt . "\n" . json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]]]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'responseMimeType' => 'application/json',
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ]);

        if ($response->status() === 429 || $response->json('error.status') === 'RESOURCE_EXHAUSTED') {
            Log::warning('ActivitySummary: quota/rate limit hit');
            throw new GeminiQuotaExceededException('Gemini quota exceeded.');
        }

        if (! $response->successful()) {
            Log::error('ActivitySummary: request failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('The AI summary service did not respond.');
        }

        $text = collect($response->json('candidates.0.content.parts') ?? [])
            ->first(fn ($p) => isset($p['text']) && empty($p['thought']))['text'] ?? '';
        $data = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $text)), true);

        if (! is_array($data) || blank($data['summary'] ?? null)) {
            Log::error('ActivitySummary: unreadable response', ['text' => mb_substr($text, 0, 500)]);
            throw new \RuntimeException('The AI summary came back unreadable.');
        }

        return [
            'headline' => mb_substr(trim((string) ($data['headline'] ?? '')), 0, 120),
            'summary' => mb_substr(trim((string) $data['summary']), 0, 700),
            'next_steps' => collect($data['next_steps'] ?? [])->filter(fn ($s) => is_string($s) && trim($s) !== '')
                ->map(fn ($s) => mb_substr(trim($s), 0, 160))->take(3)->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
