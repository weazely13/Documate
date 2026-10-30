<?php

namespace App\Livewire\Chatbot;

use App\Services\ChatbotService;
use Livewire\Component;

class ChatWidget extends Component
{
    public array $messages = [];
    public array $quickReplies = [];
    public string $input = '';
    public bool $isThinking = false;

    protected string $sessionKey = 'chatbot.messages';

    public function mount(): void
    {
        abort_if(auth()->user()->account_status !== 'active', 403);

        $stored = session($this->sessionKey);

        if (is_array($stored) && !empty($stored)) {
            $this->messages = $stored;
        } else {
            $this->messages = [[
                'role' => 'assistant',
                'content' => 'Hello! DocuMate Assistant at your service.',
            ]];
            $this->persist();
        }

        $this->quickReplies = $this->defaultQuickReplies();
    }

    protected function defaultQuickReplies(): array
    {
        return $this->faqQuestions(['Form Processing']);
    }

    protected function faqQuestions(array $categories = []): array
    {
        $faqs = config('faqs', []);

        return collect($categories)
            ->flatMap(fn ($category) => collect($faqs[$category] ?? [])->pluck('q'))
            ->filter(fn ($question) => is_string($question) && str_ends_with(trim($question), '?'))
            ->map(fn ($question) => trim($question))
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    protected function followUpReplies(string $question, string $answer = ''): array
    {
        $context = trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($question . ' ' . $answer)) ?? '');
        $contextTerms = collect(preg_split('/\s+/u', $context) ?: [])
            ->filter(fn ($term) => mb_strlen($term) > 2)
            ->reject(fn ($term) => in_array($term, [
                'about', 'after', 'also', 'can', 'does', 'form', 'from', 'have', 'help', 'how',
                'into', 'just', 'more', 'need', 'only', 'please', 'question', 'should', 'that',
                'their', 'them', 'then', 'this', 'what', 'when', 'where', 'which', 'with', 'your',
            ], true))
            ->unique()
            ->values();

        if ($contextTerms->isEmpty()) {
            return [];
        }

        return collect(config('faqs', []))
            ->flatMap(fn ($items) => collect($items))
            ->filter(fn ($faq) => is_string($faq['q'] ?? null) && str_ends_with(trim($faq['q']), '?'))
            ->map(function ($faq) use ($context, $contextTerms) {
                $question = trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($faq['q'])) ?? '');
                $keywords = collect($faq['keywords'] ?? [])
                    ->map(fn ($keyword) => trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower((string) $keyword)) ?? ''))
                    ->filter();
                $score = 0;

                foreach ($keywords as $keyword) {
                    if (str_contains($keyword, ' ') && str_contains($context, $keyword)) {
                        $score += 3;
                    }
                }

                foreach ($contextTerms as $term) {
                    if (preg_match('/\b' . preg_quote($term, '/') . '\b/', $question)) {
                        $score++;
                    } elseif ($keywords->contains(fn ($keyword) => preg_match('/\b' . preg_quote($term, '/') . '\b/', $keyword))) {
                        $score++;
                    }
                }

                return ['question' => trim($faq['q']), 'score' => $score];
            })
            ->filter(fn ($faq) => $faq['score'] >= 3)
            ->sortByDesc('score')
            ->pluck('question')
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    protected function withFollowUpQuestion(string $answer, string $question): string
    {
        if (preg_match('/\?\s*(?:[*_`~)\]]*)*$/u', trim($answer))) {
            return $answer;
        }

        $context = mb_strtolower($question . ' ' . $answer);
        $followUp = match (true) {
            str_contains($context, 'handbook'), str_contains($context, 'policy'), str_contains($context, 'rule')
                => 'Would you like more detail about this handbook policy?',
            str_contains($context, 'appointment'), str_contains($context, 'schedule'), str_contains($context, 'booking')
                => 'Would you like more detail about this appointment process?',
            str_contains($context, 'document'), str_contains($context, 'transaction'), str_contains($context, 'request')
                => 'Would you like more detail about this document request?',
            str_contains($context, 'office'), str_contains($context, 'registrar'), str_contains($context, 'cashier')
                => 'Would you like any other details about this office?',
            default => 'Would you like me to explain any part of this answer further?',
        };

        return rtrim($answer) . "\n\n" . $followUp;
    }

    protected function persist(): void
    {
        $this->messages = array_slice($this->messages, -30);
        session([$this->sessionKey => $this->messages]);
    }

    public function selectQuickReply(string $text): void
    {
        $this->input = $text;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        $text = trim($this->input);
        if ($text === '') {
            return;
        }

        $this->validateOnly('input', ['input' => 'string|max:2000']);

        $this->messages[] = ['role' => 'user', 'content' => $text];
        $this->input = '';
        $this->isThinking = true;
        $this->quickReplies = [];
        $this->persist();

        $this->dispatch('chat-message-sent');
    }

    public function getReply(): void
    {
        if (! $this->isThinking) {
            return;
        }

        try {
            $chatbot = app(ChatbotService::class);
            $result = $chatbot->ask($this->messages, auth()->user());

            $faqQuestions = $this->faqQuestions(array_keys(config('faqs', [])));
            $reply = trim((string) ($result['reply'] ?? ''));
            $relatedFaqQuestions = $this->followUpReplies(
                collect($this->messages)->last(fn ($message) => ($message['role'] ?? null) === 'user')['content'] ?? '',
                $reply,
            );
            $quickReplies = collect($result['quick_replies'] ?? [])
                ->filter(fn ($suggestion) => is_string($suggestion)
                    && trim($suggestion) !== ''
                    && in_array(trim($suggestion), $faqQuestions, true)
                    && in_array(trim($suggestion), $relatedFaqQuestions, true)
                    && str_ends_with(trim($suggestion), '?'))
                ->map(fn ($suggestion) => trim($suggestion))
                ->unique()
                ->take(5)
                ->values()
                ->all();

            if ($reply === '') {
                $reply = 'I could not prepare a response. Please try asking another way. What LNU or DocuMate question can I help with?';
            }

            $latestQuestion = collect($this->messages)
                ->last(fn ($message) => ($message['role'] ?? null) === 'user')['content'] ?? '';
            if (count($quickReplies) < 2 && $relatedFaqQuestions !== []) {
                $quickReplies = collect($quickReplies)
                    ->merge($relatedFaqQuestions)
                    ->unique()
                    ->take(5)
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Chat widget error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $reply = 'An unexpected error prevented a response. Please try again shortly. Would you like to ask another LNU or DocuMate question?';
            $quickReplies = $this->defaultQuickReplies();
        }

        $latestQuestion ??= collect($this->messages)
            ->last(fn ($message) => ($message['role'] ?? null) === 'user')['content'] ?? '';
        $reply = $this->withFollowUpQuestion($reply, $latestQuestion);

        $this->messages[] = ['role' => 'assistant', 'content' => $reply];
        $this->quickReplies = $quickReplies;
        $this->isThinking = false;
        $this->persist();
    }

    public function render()
    {
        return view('livewire.chatbot.chat-widget');
    }
}
