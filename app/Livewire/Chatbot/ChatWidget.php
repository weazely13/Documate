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
                'content' => 'Hello! this is DocuMate Assistant. Where should we start?',
            ]];
            $this->persist();
        }

        $this->quickReplies = $this->defaultQuickReplies();
    }

    protected function defaultQuickReplies(): array
    {
        return [
            'Could you explain this answer further?',
            'What is the next step for this matter?',
        ];
    }

    protected function followUpReplies(string $question, string $answer = ''): array
    {
        $question = mb_strtolower($question . ' ' . $answer);

        return match (true) {
            str_contains($question, 'office'), str_contains($question, 'registrar'), str_contains($question, 'cashier') => [
                'Where is this office located?',
                'What are this office\'s hours?',
                'How can I contact this office?',
            ],
            str_contains($question, 'appointment'), str_contains($question, 'schedule'), str_contains($question, 'booking') => [
                'How can I check my appointment?',
                'When are appointments available?',
                'How do I reschedule an appointment?',
            ],
            str_contains($question, 'document'), str_contains($question, 'transaction'), str_contains($question, 'request') => [
                'How can I check my document status?',
                'Which documents can I request?',
                'How can I book an appointment?',
            ],
            str_contains($question, 'handbook'), str_contains($question, 'policy'), str_contains($question, 'rule') => [
                'What other handbook policy should I explain?',
                'Could you explain this policy further?',
                'What does the handbook say about this policy?',
            ],
            default => $this->defaultQuickReplies(),
        };
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

            $reply = trim((string) ($result['reply'] ?? ''));
            $quickReplies = collect($result['quick_replies'] ?? [])
                ->filter(fn ($suggestion) => is_string($suggestion)
                    && trim($suggestion) !== ''
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
            if (count($quickReplies) < 2) {
                $quickReplies = collect($quickReplies)
                    ->merge($this->followUpReplies($latestQuestion, $reply))
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
