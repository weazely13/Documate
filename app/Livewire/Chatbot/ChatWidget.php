<?php

namespace App\Livewire\Chatbot;

use App\Services\ChatbotService;
use App\Support\ChatScreen;
use Livewire\Component;

class ChatWidget extends Component
{
    public const GREETING = "I'm your DocuMate guide. Ask me what to do on the page you're on, where an office or building is, what the Student Handbook says, or how your requests are going. If something isn't in DocuMate's records, I'll say so.";

    public array $messages = [];
    public array $quickReplies = [];
    public string $input = '';
    public bool $isThinking = false;
    public ?string $pageLabel = null;

    protected string $sessionKey = 'chatbot.messages';
    protected string $screenKey = 'chatbot.screen';

    public function mount(): void
    {
        abort_if(auth()->user()->account_status !== 'active', 403);
        $this->pageLabel = ChatScreen::label(request()->route()?->getName());

        // On a page about one document, name the document ("Looking at Admission Slip"). The route has already
        // checked that this user may open it.
        $ws = request()->route()?->parameter('workspace');
        if ($ws && ! $ws instanceof \App\Models\StudentDocumentWorkspace && is_numeric($ws)) {
            $ws = \App\Models\StudentDocumentWorkspace::withTrashed()->find($ws);
        }
        if ($ws instanceof \App\Models\StudentDocumentWorkspace) {
            $name = $ws->documentName() ?: $ws->template?->name;
            if ($name) {
                $this->pageLabel = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::title(str_replace('_', ' ', $name)), 48);
            }
        }

        $stored = session($this->sessionKey);

        if (is_array($stored) && !empty($stored)) {
            $this->messages = $stored;
            if ($this->refreshWelcomeGreeting()) {
                $this->persist();
            }
        } else {
            $this->messages = [[
                'role' => 'assistant',
                'content' => self::GREETING,
            ]];
            $this->persist();
        }

        // Starters only for a new chat; a conversation carried over from another page continues as it was
        $this->quickReplies = count($this->messages) <= 1 ? $this->defaultQuickReplies() : [];
    }

    protected function refreshWelcomeGreeting(): bool
    {
        if (($this->messages[0]['role'] ?? null) !== 'assistant') {
            return false;
        }

        $previousGreetings = [
            'Hello. How can I assist you with Leyte Normal University or the DocuMate system today?',
            'Hello! How can I help with LNU or DocuMate?',
            'Hello. DocuMate Assistant at your service. How may I assist you?',
            'Hello. DocuMate assistant at your service. How may I be at your service?',
        ];

        if (! in_array(trim((string) ($this->messages[0]['content'] ?? '')), $previousGreetings, true)) {
            return false;
        }

        $this->messages[0]['content'] = self::GREETING;

        return true;
    }

    /** Starters for an empty chat, by role. */
    protected function defaultQuickReplies(): array
    {
        $role = auth()->user()?->role?->role_name;

        return match ($role) {
            'Admin' => [
                'What needs my attention today?',
                'Which transactions are overdue?',
                'How do I make a new paper form template?',
                'Where is the VPSD office?',
            ],
            'Signatory' => [
                'How do I sign a document?',
                'How do I decline a document?',
                'Where is the VPSD office?',
                'What does the handbook say about student discipline?',
            ],
            'Officer' => [
                'How do I tag a student as cleared?',
                'What is the status of my documents?',
                'Where is the Office of Student Affairs?',
                'What does the handbook say about student organizations?',
            ],
            default => [
                'What documents can I request?',
                'How do I book an appointment?',
                "Where is the Registrar's Office?",
                'What does the handbook say about the school uniform?',
            ],
        };
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
        $context = mb_strtolower($question . ' ' . $answer);

        if (str_contains($context, 'office') || str_contains($context, 'registrar') || str_contains($context, 'cashier')) {
            return [
                'Find the Registrar\'s Office',
                'Find the Cashier\'s Office',
                'Who handles scholarships?',
            ];
        }

        $normalize = fn (string $value) => trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($value)) ?? '');
        $answerText = $normalize($answer);
        $questionText = $normalize($question);
        $stopWords = [
            'about', 'after', 'also', 'can', 'does', 'for', 'form', 'from', 'have', 'help', 'how',
            'into', 'just', 'more', 'need', 'only', 'please', 'question', 'should', 'that', 'their',
            'them', 'then', 'this', 'what', 'when', 'where', 'which', 'with', 'your', 'documate',
            'student', 'students', 'lnu', 'office', 'offices', 'document', 'documents', 'request',
            'requests', 'transaction', 'transactions', 'system', 'university',
        ];
        $answerTerms = collect(preg_split('/\s+/u', $answerText) ?: [])
            ->filter(fn ($term) => mb_strlen($term) > 2)
            ->reject(fn ($term) => in_array($term, $stopWords, true))
            ->unique()
            ->values();

        if ($answerTerms->isEmpty()) {
            return [];
        }

        return collect(config('faqs', []))
            ->flatMap(fn ($items) => collect($items))
            ->filter(fn ($faq) => is_string($faq['q'] ?? null) && str_ends_with(trim($faq['q']), '?'))
            ->map(function ($faq) use ($answerText, $questionText, $answerTerms, $stopWords) {
                $faqQuestion = trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($faq['q'])) ?? '');
                if ($faqQuestion === $questionText || ($questionText !== '' && str_contains($faqQuestion, $questionText))) {
                    return ['question' => trim($faq['q']), 'score' => 0];
                }

                $keywords = collect($faq['keywords'] ?? [])
                    ->map(fn ($keyword) => trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower((string) $keyword)) ?? ''))
                    ->filter();
                $score = 0;

                foreach ($keywords as $keyword) {
                    if (str_contains($keyword, ' ') && str_contains($answerText, $keyword)) {
                        $score += 2;
                    }
                }

                $faqTerms = collect(preg_split('/\s+/u', $faqQuestion . ' ' . $keywords->implode(' ')) ?: [])
                    ->filter(fn ($term) => mb_strlen($term) > 2)
                    ->reject(fn ($term) => in_array($term, $stopWords, true))
                    ->unique();
                $score += $answerTerms->intersect($faqTerms)->count();

                return ['question' => trim($faq['q']), 'score' => $score];
            })
            ->filter(fn ($faq) => $faq['score'] >= 2)
            ->sortByDesc('score')
            ->pluck('question')
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    protected function withFollowUpQuestion(string $answer, string $question): string
    {
        if (preg_match('/\?\s*(?:[*_`~)\]]*)*$/u', trim($answer))
            || preg_match('/^#{1,6}\s+/m', trim($answer))
            || preg_match('/^(?:[-*•]|\d+[.)])\s+/m', trim($answer))) {
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

    public function selectQuickReply(string $text, array $screen = []): void
    {
        $this->input = $text;
        $this->sendMessage($screen);
    }

    /** Start over: clears the conversation. */
    public function newChat(): void
    {
        session()->forget([$this->sessionKey, $this->screenKey]);
        $this->messages = [[
            'role' => 'assistant',
            'content' => self::GREETING,
        ]];
        $this->persist();
        $this->quickReplies = $this->defaultQuickReplies();
        $this->isThinking = false;
        $this->input = '';
    }

    /** @param array $screen what the browser sees: path, title, headings, notices, visible text */
    public function sendMessage(array $screen = []): void
    {
        session([$this->screenKey => collect($screen)->only(['path', 'title', 'headings', 'alerts', 'text'])->all()]);

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
            $screen = ChatScreen::describe((array) session($this->screenKey, []), auth()->user());
            $result = $chatbot->ask($this->messages, auth()->user(), $screen);

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
            // The FAQ is written for students: staff don't get student questions as suggestions
            if (in_array(auth()->user()?->role?->role_name, ['Admin', 'Signatory'], true)) {
                $quickReplies = [];
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
