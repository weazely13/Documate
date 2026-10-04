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
                'content' => "Hi! I'm the DocuMate Assistant. Where should we begin?",
            ]];
            $this->persist();
        }

        $this->quickReplies = $this->defaultQuickReplies();
    }

    protected function defaultQuickReplies(): array
    {
        return [
            'Start a new transaction',
            'Check my appointment',
            'Check document status',
            'Find an office',
            'Ask about the handbook',
        ];
    }

    protected function followUpReplies(string $question, string $answer = ''): array
    {
        $question = mb_strtolower($question . ' ' . $answer);

        return match (true) {
            str_contains($question, 'office'), str_contains($question, 'registrar'), str_contains($question, 'cashier') => [
                'Find the Registrar\'s Office',
                'Find the Cashier\'s Office',
                'Who handles scholarships?',
            ],
            str_contains($question, 'appointment'), str_contains($question, 'schedule'), str_contains($question, 'booking') => [
                'Check my appointment',
                'Check appointment availability',
                'How do I reschedule an appointment?',
            ],
            str_contains($question, 'document'), str_contains($question, 'transaction'), str_contains($question, 'request') => [
                'Check document status',
                'What documents can I request?',
                'How do I book an appointment?',
            ],
            str_contains($question, 'handbook'), str_contains($question, 'policy'), str_contains($question, 'rule') => [
                'Ask another handbook question',
                'Find an office',
                'How do I request a document?',
            ],
            default => $this->defaultQuickReplies(),
        };
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
                ->filter(fn ($suggestion) => is_string($suggestion) && trim($suggestion) !== '')
                ->map(fn ($suggestion) => trim($suggestion))
                ->unique()
                ->take(5)
                ->values()
                ->all();

            if ($reply === '') {
                $reply = 'I could not prepare a response. Please try asking another way.';
            }

            if ($quickReplies === []) {
                $latestQuestion = collect($this->messages)
                    ->last(fn ($message) => ($message['role'] ?? null) === 'user')['content'] ?? '';
                $quickReplies = $this->followUpReplies($latestQuestion, $reply);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Chat widget error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $reply = "Sorry, something went wrong on my end. Please try again.";
            $quickReplies = $this->defaultQuickReplies();
        }

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