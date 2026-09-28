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
                'content' => "Hi! I'm the DocuMate Assistant. What would you like help with?",
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

    protected function persist(): void
    {
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
            $chatbot = app(\App\Services\ChatbotService::class);
            $result = $chatbot->ask($this->messages, auth()->user());

            $reply = $result['reply'];
            $quickReplies = $result['quick_replies'] ?? [];
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