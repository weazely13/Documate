<div
    x-data="{
        toBottom() {
            this.$nextTick(() => requestAnimationFrame(() => {
                const b = this.$refs.scrollBody;
                if (b) b.scrollTop = b.scrollHeight;
            }));
        }
    }"
    x-on:chat-message-sent.window="$wire.getReply()"
    class="flex flex-col h-full"
>
    <div class="chatbot-body flex-1 overflow-y-auto overflow-x-hidden space-y-3 p-4" x-ref="scrollBody" role="log" aria-live="polite" aria-relevant="additions text"
         x-init="
            $watch('$wire.messages', () => toBottom());
            $watch('$wire.quickReplies', () => toBottom());
            $watch('$wire.isThinking', () => toBottom());
            toBottom();
            new IntersectionObserver((entries) => { if (entries[0].isIntersecting) toBottom(); }).observe($refs.scrollBody);
         ">

        @foreach ($messages as $message)
            <div class="chatbot-msg-row flex items-end gap-2 {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                @if ($message['role'] !== 'user')
                    <div class="chatbot-mini-avatar"><i class='bx bx-bot'></i></div>
                @endif

                <div class="max-w-[78%] min-w-0 rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed chatbot-msg-content {{ $message['role'] === 'user' ? 'chatbot-bubble-user' : 'chatbot-bubble-bot' }}">
                    {!! \App\Support\MarkdownLite::toHtml($message['content']) !!}
                </div>
            </div>
        @endforeach

        @if ($isThinking)
            <div class="chatbot-msg-row flex items-end gap-2 justify-start">
                <div class="chatbot-mini-avatar"><i class='bx bx-bot'></i></div>
                <div class="chatbot-bubble-bot rounded-2xl">
                    <div class="chatbot-typing">
                        <span class="chatbot-typing-dot"></span>
                        <span class="chatbot-typing-dot"></span>
                        <span class="chatbot-typing-dot"></span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Selection shown as a chat message from the bot --}}
        @if (!empty($quickReplies) && !$isThinking)
            <div class="chatbot-msg-row flex items-end gap-2 justify-start">
                <div class="chatbot-mini-avatar"><i class='bx bx-bot'></i></div>

                <div class="max-w-[78%] min-w-0 rounded-2xl px-3.5 py-2.5 text-sm chatbot-bubble-bot chatbot-choice-bubble">
                    <div class="chatbot-choice-label">Choose an option:</div>
                    <div class="chatbot-choice-list">
                        @foreach ($quickReplies as $reply)
                            <button type="button"
                                    wire:click="selectQuickReply(@js($reply))"
                                    wire:key="qr-{{ $loop->index }}"
                                    class="chatbot-choice">
                                {{ $reply }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @error('input')
            <p class="chatbot-input-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <form wire:submit.prevent="sendMessage" class="chatbot-input flex items-center gap-2 p-3">
        <input type="text" wire:model="input" placeholder="Ask something..." aria-label="Ask DocuMate Assistant" maxlength="2000" autocomplete="off" class="flex-1" {{ $isThinking ? 'disabled' : '' }}>
        <button type="submit" class="chatbot-send-btn" {{ $isThinking ? 'disabled' : '' }} aria-label="Send message">
            <i class='bx bx-send'></i>
        </button>
    </form>
</div>