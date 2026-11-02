@php
    $user = auth()->user();
    $role = $user?->role?->role_name;
    $isFresh = count($messages) <= 1;
@endphp
<div
    x-data="{
        share: true,
        page: '',
        toBottom() {
            this.$nextTick(() => requestAnimationFrame(() => {
                const b = this.$refs.scrollBody;
                if (b) b.scrollTop = b.scrollHeight;
            }));
        },
        screen() { return this.share && window.documateScreen ? window.documateScreen() : {}; },
        readPage() { this.page = @js($pageLabel) || (window.documateScreen ? window.documateScreen().label : ''); },
        send() {
            if (!$wire.input.trim() || $wire.isThinking) return;
            $wire.sendMessage(this.screen());
        },
        ask(text) { $wire.selectQuickReply(text, this.screen()); },
        grow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 132) + 'px'; },
    }"
    x-init="readPage(); window.addEventListener('chatbot-opened', () => { readPage(); toBottom(); $nextTick(() => $refs.input?.focus()); })"
    x-on:chat-message-sent.window="$wire.getReply()"
    x-on:chatbot-ask.window="ask($event.detail)"
    class="chatbot-shell"
>
    <header class="chatbot-header">
        <span class="chatbot-avatar" aria-hidden="true"><i class='bx bx-bot'></i></span>
        <div class="min-w-0 flex-1">
            <p class="chatbot-title">DocuMate Assistant</p>
            <p class="chatbot-subtitle">
                <template x-if="share && page"><span class="truncate">Looking at <span class="text-[#273951]" x-text="page"></span></span></template>
                <template x-if="!share || !page"><span>Your guide to DocuMate and LNU</span></template>
            </p>
        </div>
        <button type="button" @click="share = !share" class="chatbot-icon-btn" :aria-pressed="share.toString()"
                :data-tooltip="share ? 'The assistant can see this page. Click to stop sharing.' : 'Page not shared. Click to let the assistant see it.'"
                :aria-label="share ? 'Stop sharing this page with the assistant' : 'Share this page with the assistant'"
                :class="share ? '!text-[#2a57b4]' : ''">
            <i class='bx' :class="share ? 'bx-show' : 'bx-hide'"></i>
        </button>
        <button type="button" data-chatbot-close class="chatbot-icon-btn" aria-label="Close the assistant">
            <i class='bx bx-x'></i>
        </button>
    </header>

    <div class="chatbot-body" x-ref="scrollBody" role="log" aria-live="polite" aria-relevant="additions text"
         x-init="
            $watch('$wire.messages', () => toBottom());
            $watch('$wire.quickReplies', () => toBottom());
            $watch('$wire.isThinking', () => toBottom());
            toBottom();
         ">

        @foreach ($messages as $message)
            @if ($message['role'] === 'user')
                <div class="chatbot-row chatbot-row-user" wire:key="m-{{ $loop->index }}">
                    <div class="chatbot-bubble chatbot-bubble-user chatbot-msg-content">{!! \App\Support\MarkdownLite::toHtml($message['content']) !!}</div>
                </div>
            @else
                <div class="chatbot-row" wire:key="m-{{ $loop->index }}">
                    <span class="chatbot-mini-avatar" aria-hidden="true"><i class='bx bx-bot'></i></span>
                    <div class="chatbot-bubble chatbot-bubble-bot chatbot-msg-content">
                        @if ($loop->first && $isFresh && $user?->first_name)
                            <p class="chatbot-hello">Hi, {{ $user->first_name }}.</p>
                        @endif
                        {!! \App\Support\MarkdownLite::toHtml($message['content']) !!}
                    </div>
                </div>
            @endif
        @endforeach

        @if ($isFresh && ! $isThinking)
            {{-- First open: help with the page, then a few starters --}}
            <div class="chatbot-starters">
                <button type="button" x-show="share && page" @click="ask('What should I do on this page?')" class="chatbot-page-help">
                    <span class="chatbot-page-help-icon" aria-hidden="true"><i class='bx bx-current-location'></i></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="block text-[13.5px] font-medium text-[#0d253d]">Help me with this page</span>
                        <span class="block truncate text-xs text-[#66707f]" x-text="page"></span>
                    </span>
                    <i class='bx bx-chevron-right text-lg text-[#9a978f]' aria-hidden="true"></i>
                </button>

                <p class="chatbot-section-label">Try asking</p>
                <div class="chatbot-chips">
                    @foreach ($quickReplies as $reply)
                        <button type="button" @click="ask(@js($reply))" wire:key="st-{{ $loop->index }}" class="chatbot-chip">{{ $reply }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($isThinking)
            <div class="chatbot-row">
                <span class="chatbot-mini-avatar" aria-hidden="true"><i class='bx bx-bot'></i></span>
                <div class="chatbot-bubble chatbot-bubble-bot">
                    <span class="sr-only">The assistant is writing a reply</span>
                    <div class="chatbot-typing" aria-hidden="true">
                        <span class="chatbot-typing-dot"></span>
                        <span class="chatbot-typing-dot"></span>
                        <span class="chatbot-typing-dot"></span>
                    </div>
                </div>
            </div>
        @endif

        @if (! $isFresh && ! empty($quickReplies) && ! $isThinking)
            <div class="chatbot-followups">
                <p class="chatbot-section-label">Related</p>
                <div class="chatbot-chips">
                    @foreach ($quickReplies as $reply)
                        <button type="button" @click="ask(@js($reply))" wire:key="qr-{{ $loop->index }}" class="chatbot-chip">{{ $reply }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @error('input')
            <p class="chatbot-input-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <form @submit.prevent="send()" class="chatbot-composer">
        <div class="chatbot-input-box">
            <textarea x-ref="input" wire:model="input" rows="1" placeholder="Ask about this page, an office, the handbook…" aria-label="Ask DocuMate Assistant"
                      maxlength="2000" autocomplete="off" @input="grow($el)"
                      @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); $nextTick(() => grow($el)); }"
                      {{ $isThinking ? 'disabled' : '' }}></textarea>
            <button type="submit" class="chatbot-send" {{ $isThinking ? 'disabled' : '' }} aria-label="Send">
                <i class='bx bx-up-arrow-alt'></i>
            </button>
        </div>
    </form>
</div>
