@php
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
    $btn = 'rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50';
    $primary = $btn . ' bg-[#2A57B4] text-white hover:bg-[#24499A]';
@endphp

<div class="space-y-4">

    {{-- ===== Finished / unavailable states ===== --}}
    @if ($state === 'done')
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <p class="text-lg font-bold text-emerald-800">Thank you. Your response was recorded.</p>
            <p class="mt-1 text-sm text-emerald-700">The VPSD office has received it. You can close this page.</p>
        </section>
    @elseif ($state === 'expired')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-lg font-bold text-slate-900">This link has expired</p>
            <p class="mt-1 text-sm text-slate-500">Ask the student to contact the VPSD office, who can send you a new one.</p>
        </section>
    @elseif ($state === 'closed')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-lg font-bold text-slate-900">This request is no longer open</p>
            <p class="mt-1 text-sm text-slate-500">It may have been changed or cancelled by the VPSD office. Nothing more is needed from you.</p>
        </section>

    {{-- ===== Step 1: confirm email ===== --}}
    @elseif ($state === 'email')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Confirm it's you</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ match ($kind) {
                    'rating' => 'You were invited to rate a student.',
                    'review' => 'You were asked to review and sign a committee report.',
                    default => 'You were asked to recommend approval of a student activity.',
                } }} Type the email address that received the invitation
                (it looks like {{ $maskedEmail }}) and we will send a 6-digit code to it.
            </p>

            <label class="mt-5 block text-sm font-medium text-slate-700">Your email address</label>
            <input type="email" wire:model="email" wire:keydown.enter="sendCode" autocomplete="email" class="{{ $input }}" placeholder="name@example.com">
            @error('email') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="sendCode" wire:loading.attr="disabled" class="{{ $primary }}">
                    <span wire:loading.remove wire:target="sendCode">Send code</span>
                    <span wire:loading wire:target="sendCode">Sending…</span>
                </button>
                <button type="button" wire:click="$set('codeSent', true)" class="text-sm font-medium text-[#2A57B4] hover:underline">I already have a code</button>
            </div>
        </section>

    {{-- ===== Step 2: enter code ===== --}}
    @elseif ($state === 'code')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Enter your code</h1>
            <p class="mt-1 text-sm text-slate-500">
                If that address matches the invitation, a 6-digit code is on its way. It expires in 10 minutes.
            </p>

            <label class="mt-5 block text-sm font-medium text-slate-700">6-digit code</label>
            <input type="text" inputmode="numeric" maxlength="6" wire:model="code" wire:keydown.enter="verifyCode" autocomplete="one-time-code"
                   class="{{ $input }} text-center font-mono text-2xl tracking-[0.5em]" placeholder="••••••">
            @error('code') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="verifyCode" wire:loading.attr="disabled" class="{{ $primary }}">Verify</button>
                <button type="button" wire:click="sendCode" class="text-sm font-medium text-[#2A57B4] hover:underline">Send a new code</button>
                <button type="button" wire:click="changeEmail" class="text-sm text-slate-500 hover:underline">Use a different email</button>
            </div>
            @error('email') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </section>

    {{-- ===== Step 3: rate and sign ===== --}}
    @else
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-600">Email verified</p>

            @if ($kind === 'rating')
                <h1 class="mt-1 text-xl font-bold text-slate-900">Model Student Faculty/Peer Rating</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    This is to inform the Office of the Vice President for Student Development that I personally know
                    <strong class="text-slate-900">{{ $student }}</strong>@if ($program), a <strong class="text-slate-900">{{ $program }}</strong> student of Leyte Normal University @endif.
                    Based on my honest assessment of his/her interpersonal relations with classmates and teachers, I am giving him/her a rating of:
                </p>

                <div class="mt-4 space-y-2" wire:key="ratings">
                    @foreach ($ratings as $n => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                            <input type="radio" wire:model="rating" value="{{ $n }}" class="h-4 w-4 accent-[#2A57B4]">
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                            <span class="ml-auto text-slate-400">({{ $n }})</span>
                        </label>
                    @endforeach
                </div>
                @error('rating') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            @elseif ($kind === 'review')
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $documentName }}</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    As a member of the Discipline Committee, please review this report concerning
                    <strong class="text-slate-900">{{ $student }}</strong>@if ($program) ({{ $program }})@endif,
                    then sign below. This report is confidential. Please do not share it.
                </p>
                <dl class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                    @foreach ($details as $d)
                        <div wire:key="detail-{{ $loop->index }}">
                            <dt class="text-xs font-medium text-slate-400">{{ $d['label'] }}</dt>
                            <dd class="whitespace-pre-wrap text-slate-800">{{ $d['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $documentName }}</h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    <strong class="text-slate-900">{{ $student }}</strong>@if ($program) ({{ $program }})@endif
                    asks for your recommending approval as adviser of <strong class="text-slate-900">{{ $organization }}</strong>.
                </p>
                <dl class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                    @foreach ($details as $d)
                        <div wire:key="detail-{{ $loop->index }}">
                            <dt class="text-xs font-medium text-slate-400">{{ $d['label'] }}</dt>
                            <dd class="whitespace-pre-wrap text-slate-800">{{ $d['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            <label class="mt-5 flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" wire:model="attest" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                <span>{{ match ($kind) {
                    'rating' => 'I personally know this student, and this rating is my own honest assessment.',
                    'review' => 'I am a member of the Discipline Committee. I have read this report and confirm it.',
                    default => 'I am the adviser of this organization and I recommend approval of this activity.',
                } }}</span>
            </label>
            @error('attest') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <label class="mt-5 block text-sm font-medium text-slate-700">Your full name</label>
            <input type="text" wire:model="signerName" maxlength="120" class="{{ $input }}">
            @error('signerName') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <label class="mt-5 block text-sm font-medium text-slate-700">Your signature</label>
            <p class="text-xs text-slate-400">Draw with your finger, stylus or mouse.</p>

            <div wire:ignore class="mt-2"
                 x-data="{
                    drawing: false, has: false, ctx: null,
                    init() { const c = this.$refs.c; this.ctx = c.getContext('2d'); this.ctx.lineWidth = 3; this.ctx.lineCap = 'round'; this.ctx.lineJoin = 'round'; this.ctx.strokeStyle = '#111'; },
                    pos(e) { const c = this.$refs.c, r = c.getBoundingClientRect(); return [(e.clientX - r.left) * c.width / r.width, (e.clientY - r.top) * c.height / r.height]; },
                    start(e) { this.drawing = true; const [x, y] = this.pos(e); this.ctx.beginPath(); this.ctx.moveTo(x, y); this.ctx.lineTo(x + 0.1, y + 0.1); this.ctx.stroke(); },
                    move(e) { if (!this.drawing) return; const [x, y] = this.pos(e); this.ctx.lineTo(x, y); this.ctx.stroke(); this.has = true; },
                    end() { if (!this.drawing) return; this.drawing = false; if (this.has) { $wire.$set('signature', this.$refs.c.toDataURL('image/png'), false); } },
                    clear() { this.ctx.clearRect(0, 0, 600, 200); this.has = false; $wire.$set('signature', '', false); }
                 }">
                <canvas x-ref="c" width="600" height="200"
                        class="w-full touch-none rounded-xl border border-dashed border-slate-300 bg-white"
                        @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)"
                        @pointerup="end()" @pointerleave="end()"></canvas>
                <button type="button" @click="clear()" class="mt-1.5 text-xs font-medium text-slate-500 hover:text-rose-600">Clear signature</button>
            </div>
            @error('signature') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            @error('action') <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm font-medium text-rose-700">{{ $message }}</p> @enderror

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        wire:confirm="Submit your response? You cannot change it afterwards."
                        class="{{ $primary }}">
                    <span wire:loading.remove wire:target="submit">Sign and submit</span>
                    <span wire:loading wire:target="submit">Submitting…</span>
                </button>
                <button type="button" wire:click="$toggle('declining')" class="text-sm text-slate-500 hover:underline">{{ match ($kind) { 'rating' => 'I cannot rate this student', 'review' => 'I cannot sign this report', default => 'I cannot approve this request' } }}</button>
            </div>
        </section>

        @if ($declining)
            <section class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Decline this request</h2>
                <p class="mt-1 text-xs text-slate-500">The student will see your reason and will need to start a new request with someone else.</p>
                <textarea wire:model="declineReason" rows="3" maxlength="500" placeholder="e.g. I do not know this student well enough to rate them."
                          class="{{ $input }}"></textarea>
                @error('declineReason') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                <button type="button" wire:click="decline" wire:loading.attr="disabled"
                        wire:confirm="Decline this request? This ends it."
                        class="{{ $btn }} mt-3 bg-rose-600 text-white hover:bg-rose-700">Decline request</button>
            </section>
        @endif
    @endif
</div>