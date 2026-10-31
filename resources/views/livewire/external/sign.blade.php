@php
    $input = 'mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-[3px] focus:ring-[#2A57B4]/20';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 py-1.5 text-center leading-snug text-sm font-medium transition disabled:opacity-50 [@media(pointer:coarse)]:min-h-11';
    $primary = $btn . ' bg-[#2A57B4] text-white hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
@endphp

<div class="space-y-4">

    {{-- Where the signer is: 1 confirm email, 2 enter code, 3 review and sign. --}}
    @unless (in_array($state, ['done', 'expired', 'closed'], true))
        @php $at = match ($state) { 'email' => 1, 'code' => 2, default => 3 }; @endphp
        <nav aria-label="Progress">
            <ol class="grid grid-cols-3 gap-2">
                @foreach (['Confirm email', 'Enter code', 'Review and sign'] as $i => $stepLabel)
                    <li class="min-w-0" @if ($i + 1 === $at) aria-current="step" @endif>
                        <div class="h-1.5 rounded-full {{ $i + 1 < $at ? 'bg-emerald-500' : ($i + 1 === $at ? 'bg-[#2A57B4]' : 'bg-slate-200') }}" aria-hidden="true"></div>
                        <p class="mt-1.5 truncate text-xs {{ $i + 1 === $at ? 'font-semibold text-slate-900' : 'text-slate-500' }}">
                            {{ $i + 1 }}. {{ $stepLabel }}<span class="sr-only">{{ $i + 1 < $at ? ', done' : ($i + 1 === $at ? ', current' : '') }}</span>
                        </p>
                    </li>
                @endforeach
            </ol>
        </nav>
    @endunless

    {{-- Error summary --}}
    @if ($errors->any())
        <div role="alert" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())"
             class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 focus:outline-none">
            <p class="flex items-center gap-2 font-semibold"><i class="bx bx-error-circle text-lg" aria-hidden="true"></i> Please check the following</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-9">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== Finished / unavailable states ===== --}}
    @if ($state === 'done')
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <p class="text-lg font-semibold text-emerald-800">Thank you. Your response was recorded.</p>
            <p class="mt-1 text-sm text-emerald-700">The VPSD office has received it. You can close this page.</p>
        </section>
    @elseif ($state === 'expired')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-lg font-semibold text-slate-900">This link has expired</p>
            <p class="mt-1 text-sm text-slate-500">Ask the student to contact the VPSD office, who can send you a new one.</p>
        </section>
    @elseif ($state === 'closed')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-lg font-semibold text-slate-900">This request is no longer open</p>
            <p class="mt-1 text-sm text-slate-500">It may have been changed or cancelled by the VPSD office. Nothing more is needed from you.</p>
        </section>

    {{-- ===== Step 1: confirm email ===== --}}
    @elseif ($state === 'email')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Confirm it's you</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ match ($kind) {
                    'rating' => 'You were invited to rate a student.',
                    'review' => 'You were asked to review and sign a committee report.',
                    'consent' => 'You were asked to give your consent for a student.',
                    'form' => 'You were asked to fill in and sign a form for a student.',
                    'recommend' => 'You were asked to recommend a student for Student Assistantship.',
                    default => 'You were asked to recommend approval of a student activity.',
                } }} Type the email address that received the invitation
                (it looks like {{ $maskedEmail }}) and we will send a 6-digit code to it.
            </p>

            <label for="ext-email" class="mt-5 block text-sm font-medium text-slate-700">Your email address <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="ext-email" type="email" wire:model="email" wire:keydown.enter="sendCode" autocomplete="email" inputmode="email" class="{{ $input }}" placeholder="name@example.com"
                   aria-required="true" @error('email') aria-invalid="true" aria-describedby="ext-email-error" @enderror autofocus>
            @error('email') <p id="ext-email-error" class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="sendCode" wire:loading.attr="disabled" class="{{ $primary }}">
                    <span wire:loading.remove wire:target="sendCode">Email me a code</span>
                    <span wire:loading wire:target="sendCode">Sending…</span>
                </button>
                <button type="button" wire:click="$set('codeSent', true)" class="text-sm font-medium text-[#2A57B4] hover:underline">I already have a code</button>
            </div>
        </section>

    {{-- ===== Step 2: enter code ===== --}}
    @elseif ($state === 'code')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Enter your code</h1>
            <p class="mt-1 text-sm text-slate-500">
                If that address matches the invitation, a 6-digit code is on its way. It expires in 10 minutes.
            </p>

            <label for="ext-code" class="mt-5 block text-sm font-medium text-slate-700">6-digit code <span class="text-rose-600" aria-hidden="true">*</span></label>
            <input id="ext-code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" wire:model="code" wire:keydown.enter="verifyCode" autocomplete="one-time-code"
                   class="{{ $input }} text-center font-mono text-2xl tracking-[0.5em]" placeholder="••••••" autofocus
                   aria-required="true" aria-describedby="ext-code-help @error('code') ext-code-error @enderror" @error('code') aria-invalid="true" @enderror>
            <p id="ext-code-help" class="mt-1 text-xs text-slate-500">Numbers only. Check your spam folder if it does not arrive.</p>
            @error('code') <p id="ext-code-error" class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="verifyCode" wire:loading.attr="disabled" class="{{ $primary }}">Verify code</button>
                <button type="button" wire:click="sendCode" class="text-sm font-medium text-[#2A57B4] hover:underline">Send a new code</button>
                <button type="button" wire:click="changeEmail" class="text-sm text-slate-500 hover:underline">Use a different email</button>
            </div>
            @error('email') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
        </section>

    {{-- ===== Step 3: review, fill in and sign (same layout as the student transaction form) ===== --}}
    @else
        @php
            $box = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3.5 shadow-sm transition focus-within:border-[#2A57B4] focus-within:ring-[3px] focus-within:ring-[#2A57B4]/20';
            $boxLocked = 'relative mt-1 flex min-w-0 items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5';
            $bare = 'w-full min-w-0 border-0 bg-transparent p-0 py-3 text-base text-slate-900 placeholder-slate-500 outline-none focus:ring-0 sm:py-2.5 sm:text-sm';
            $label = 'block text-[13px] font-medium text-slate-700';
            $help = 'mt-1 text-xs leading-relaxed text-slate-500';
            $err = 'mt-1 flex items-start gap-1 text-xs font-medium text-rose-600';
            $confidential = in_array($kind, ['review', 'guardian', 'witness'], true);
            $hasResponse = in_array($kind, ['rating', 'form'], true);
            $intro = match ($kind) {
                'rating' => 'This is to inform the Office of the Vice President for Student Development that I personally know ' . $student . ($program ? ', a ' . $program . ' student of Leyte Normal University' : '') . '. Based on my honest assessment of his/her interpersonal relations with classmates and teachers, I am giving him/her the rating below.',
                'review' => 'As a member of the Discipline Committee, please review this report concerning ' . $student . ($program ? ' (' . $program . ')' : '') . ', then sign below.',
                'form' => $student . ($program ? ' (' . $program . ')' : '') . ' named you as their ' . (strtolower($signerRole) ?: 'signer') . '. Please fill in the form below, then sign.',
                'recommend' => $student . ($program ? ', a ' . $program . ' student of Leyte Normal University,' : '') . ' asks you to recommend them to the Office of the Vice President for Student Development for Student Assistantship.',
                'consent' => 'This is to inform the Office of the Vice President for Student Development that ' . $student . ', my ' . ($relationship ?: 'son/daughter/ward') . ', is a/an ' . $program . ' student of Leyte Normal University, and that I am allowing him/her to work as student assistant in your institution.',
                'guardian' => $student . ($program ? ' (' . $program . ')' : '') . ' filed the complaint below with the Office of the Vice President for Student Development. Please read it, then sign as parent/guardian.',
                'witness' => $student . ($program ? ' (' . $program . ')' : '') . ' named you as a witness to the complaint below. Please read it, then sign as a witness.',
                default => $student . ($program ? ' (' . $program . ')' : '') . ' asks for your recommending approval as adviser of ' . ($organization ?: 'their organization') . '.',
            };
        @endphp

        <div class="xl:grid xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:items-start xl:gap-6">
        <div class="min-w-0 space-y-4">

        <div x-data="{
                open: false, loading: false, html: '', w: 842, h: 1171, scale: 1, wrapEl: null, frameEl: null,
                async show() {
                    this.open = true; this.loading = true;
                    this.html = await this.$wire.previewDocument();
                    this.loading = false;
                    this.$nextTick(() => this.fit());
                },
                fit() { const cw = this.wrapEl ? this.wrapEl.clientWidth : 0; if (cw > 0) this.scale = Math.min(1, cw / this.w); },
                measure(e) {
                    if (this.frameEl && e.source === this.frameEl.contentWindow && e.data && e.data.documatePreviewHeight) {
                        this.h = Math.ceil(e.data.documatePreviewHeight);
                    }
                }
             }"
             @message.window="measure($event)" @resize.window="fit()">
            <p class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"><i class="bx bx-check-shield text-sm" aria-hidden="true"></i> Email verified</p>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-x-4 sm:gap-y-2">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $documentName }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $signerRole ? 'You are signing as ' . $signerRole . '.' : 'You were asked to sign this document.' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" @click="$dispatch('open-intro')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-[#2A57B4] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
                            aria-label="Before you sign: what to check" title="Before you sign">
                        <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="show()"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 xl:hidden [@media(pointer:coarse)]:min-h-11">
                        <i class="bx bx-show text-base text-slate-500" aria-hidden="true"></i> Preview document
                    </button>
                </div>
            </div>

            <template x-teleport="body">
                <div x-show="open" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[80] flex flex-col bg-slate-900/50 p-0 sm:p-6"
                     role="dialog" aria-modal="true" aria-labelledby="ext-preview-title"
                     @keydown.escape.window="open = false">
                    <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl" @click.outside="open = false">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                            <div class="min-w-0">
                                <h2 id="ext-preview-title" class="text-sm font-semibold text-slate-900">Document preview</h2>
                                <p class="text-xs text-slate-500">Shows your answers and name as they will print. Nothing is signed by previewing.</p>
                            </div>
                            <button type="button" @click="open = false" x-init="$watch('open', v => v && $nextTick(() => $el.focus()))"
                                    class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30"
                                    aria-label="Close preview">
                                <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto bg-[#e7e5e1]" x-init="wrapEl = $el">
                            <div x-show="loading" class="flex h-64 items-center justify-center gap-2 text-sm text-slate-600" role="status">
                                <i class="bx bx-loader-alt bx-spin text-xl" aria-hidden="true"></i> Preparing preview…
                            </div>
                            <div x-show="!loading" class="mx-auto overflow-hidden" :style="`width:${w * scale}px;height:${h * scale}px`">
                                <iframe x-init="frameEl = $el" :srcdoc="html" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                                        :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <form wire:submit="submit" novalidate wire:confirm="Submit your response? You cannot change it afterwards."
              x-data @input="$dispatch('doc-changed')" @change="$dispatch('doc-changed')" @pointerup="$dispatch('doc-changed')"
              class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm" aria-labelledby="ext-form-title">
            <h2 id="ext-form-title" class="sr-only">Review and sign</h2>

            {{-- Before you sign: a popup that opens with the form (reopen it from the info button). --}}
            @include('livewire.workflow._intro-modal', [
                'title' => 'Before you sign',
                'storageKey' => 'external.' . $kind,
                'lead' => $documentName . ($signerRole ? ' · signing as ' . $signerRole : ''),
                'items' => array_values(array_filter([
                    'Read the request details. Greyed-out fields come from the student\'s request and cannot be changed here.',
                    'The full document is shown beside the form (or under "Preview document" on smaller screens) and fills in as you answer.',
                    $hasResponse ? 'Fields marked * are required.' : null,
                    'Type your name with your title as it should be printed. A handwritten signature image is optional.',
                    'Submitting asks you to confirm. Your verified email, the time and a security seal are recorded.',
                ])),
                'note' => $confidential ? 'This is confidential. Please do not share it.' : null,
                'startLabel' => 'Review and sign',
            ])

            {{-- 1. Student information --}}
            <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="ext-sec-student">
                <h3 id="ext-sec-student" class="text-sm font-semibold text-slate-900">Student information</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="ext-student" class="{{ $label }}">Name</label>
                        <div class="{{ str_replace('items-start', 'items-center', $boxLocked) }} py-0">
                            <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                            <input id="ext-student" type="text" value="{{ $student }}" disabled class="{{ $bare }} cursor-not-allowed text-slate-700">
                        </div>
                    </div>
                    @if ($program)
                        <div>
                            <label for="ext-program" class="{{ $label }}">Program, year and section</label>
                            <div class="{{ str_replace('items-start', 'items-center', $boxLocked) }} py-0">
                                <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                                <input id="ext-program" type="text" value="{{ $program }}" disabled class="{{ $bare }} cursor-not-allowed text-slate-700">
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- 2. Request details --}}
            <section class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="ext-sec-details">
                <h3 id="ext-sec-details" class="text-sm font-semibold text-slate-900">Request details</h3>
                <p class="mt-2 rounded-lg bg-slate-50 px-3.5 py-3 text-sm leading-relaxed text-slate-700">{{ $intro }}</p>
                @if ($details)
                    <dl class="mt-3 space-y-3">
                        @foreach ($details as $d)
                            <div wire:key="detail-{{ $loop->index }}">
                                <dt class="{{ $label }}">{{ $d['label'] }}</dt>
                                <dd class="{{ $boxLocked }}">
                                    <i class="bx bx-lock-alt mt-0.5 text-base text-slate-400" aria-hidden="true"></i>
                                    <span class="min-w-0 whitespace-pre-wrap text-sm text-slate-800">{{ $d['value'] }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- 3. Your response --}}
            @if ($hasResponse)
                <section class="border-b border-slate-100 px-4 py-5 sm:px-5" aria-labelledby="ext-sec-response">
                    <h3 id="ext-sec-response" class="text-sm font-semibold text-slate-900">Your response</h3>
                    @if ($kind === 'rating')
                        <p class="{{ $help }}">Choose one rating.</p>
                        <fieldset class="mt-3" id="ext-rating" tabindex="-1" @error('rating') aria-describedby="ext-rating-error" @enderror>
                            <legend class="sr-only">Your rating <span>(required)</span></legend>
                            <div class="space-y-2" wire:key="ratings">
                                @foreach ($ratings as $n => $ratingLabel)
                                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-[#2A57B4]/20">
                                        <input type="radio" name="rating" wire:model="rating" value="{{ $n }}" class="h-4 w-4 accent-[#2A57B4]">
                                        <span class="font-medium text-slate-800">{{ $ratingLabel }}</span>
                                        <span class="ml-auto tabular-nums text-slate-500">{{ $n }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @error('rating') <p id="ext-rating-error" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                    @else
                        <p class="{{ $help }}">Score each item honestly. Your answers are sealed with your signature.</p>
                        <div class="mt-3">
                            @include('livewire.workflow._signer-fields', ['fields' => $signerFields])
                        </div>
                    @endif
                </section>
            @endif

            {{-- 4. Your signature --}}
            <section class="px-4 py-5 sm:px-5" aria-labelledby="ext-sec-sign">
                <h3 id="ext-sec-sign" class="text-sm font-semibold text-slate-900">Your signature</h3>

                <label for="ext-name" class="{{ $label }} mt-3">Your full name, as it will be printed <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                <div class="{{ $box }} {{ $errors->has('signerName') ? 'border-rose-400' : '' }}">
                    <i class="bx bx-user text-base text-slate-400" aria-hidden="true"></i>
                    <input id="ext-name" type="text" wire:model="signerName" maxlength="120" autocomplete="name" class="{{ $bare }}"
                           aria-required="true" aria-describedby="ext-name-help @error('signerName') ext-name-error @enderror" @error('signerName') aria-invalid="true" @enderror>
                </div>
                <p id="ext-name-help" class="{{ $help }}">Include your title, e.g. Dr. Maria Santos or Prof. Juan Dela Cruz. It is printed above the signature line, after "Digitally signed by".</p>
                @error('signerName') <p id="ext-name-error" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

                <label for="ext-apply" class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm text-slate-700 hover:bg-slate-50 has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50/50">
                    <input id="ext-apply" type="checkbox" wire:model.live="applySignature" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]">
                    <span><span class="font-medium text-slate-900">Add my handwritten signature image</span>
                        <span class="block text-xs text-slate-500">Optional. If you leave this off, your name, verified email and a security seal are still recorded, so you can finish with the keyboard alone.</span></span>
                </label>

                <div x-show="$wire.applySignature" x-cloak class="mt-3">
                    <p class="{{ $label }}">Draw your signature</p>
                    <p class="{{ $help }} mt-0">Use your finger, a stylus or the mouse.</p>
                    @include('partials.signature-pad', ['model' => 'signature'])
                    @error('signature') <p class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                </div>

                @error('action') <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm font-medium text-rose-700" role="alert">{{ $message }}</p> @enderror
            </section>

            {{-- Confirmation and actions --}}
            <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-5">
                <label for="ext-attest" class="flex cursor-pointer items-start gap-3 rounded-xl border {{ $errors->has('attest') ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50/70' }} px-3.5 py-3 text-sm text-slate-700">
                    <input id="ext-attest" type="checkbox" wire:model="attest" class="mt-0.5 h-4 w-4 shrink-0 accent-[#2A57B4]" aria-required="true"
                           @error('attest') aria-invalid="true" aria-describedby="ext-attest-error" @enderror>
                    <span><span class="font-semibold text-slate-900">I confirm.</span>
                        {{ match ($kind) {
                            'rating' => 'I personally know this student, and this rating is my own honest assessment.',
                            'review' => 'I am a member of the Discipline Committee. I have read this report and confirm it.',
                            'consent' => 'I am the parent/guardian of this student and I give my consent.',
                            'form' => 'I personally know this student, and what I entered above is my own honest assessment.',
                            'recommend' => 'I personally know this student and I recommend them for Student Assistantship.',
                            'guardian' => 'I am the parent/guardian of this student. I have read this complaint and I sign it.',
                            'witness' => 'I am a witness to this matter. I have read this complaint and I sign it.',
                            default => 'I am the adviser of this organization and I recommend approval of this activity.',
                        } }}
                        <span class="mt-1 block text-xs text-slate-500">Your verified email, the time and a security seal are recorded with your response, under the Data Privacy Act of 2012.</span></span>
                </label>
                @error('attest') <p id="ext-attest-error" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror

                <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                    <button type="button" wire:click="$toggle('declining')" aria-expanded="{{ $declining ? 'true' : 'false' }}" aria-controls="ext-decline-panel"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-9">
                        {{ match ($kind) { 'rating' => 'I cannot rate this student', 'review' => 'I cannot sign this report', 'consent' => 'I cannot give consent', 'form' => 'I cannot fill in this form', 'recommend' => 'I cannot recommend this student', 'guardian', 'witness' => 'I cannot sign this complaint', default => 'I cannot approve this request' } }}
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                            class="{{ $primary }} w-full min-h-11 shadow-sm sm:w-auto sm:min-h-9">
                        <i class="bx bx-pen text-base" aria-hidden="true"></i>
                        <span wire:loading.remove wire:target="submit">Sign and submit</span>
                        <span wire:loading wire:target="submit">Submitting…</span>
                    </button>
                </div>
            </div>
        </form>

        @if ($declining)
            <section id="ext-decline-panel" class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm" aria-labelledby="ext-decline-title">
                <h2 id="ext-decline-title" class="text-sm font-semibold text-slate-900">Decline this request</h2>
                <p id="ext-decline-help" class="mt-1 text-xs text-slate-500">The student will see your reason and will need to start a new request with someone else.</p>
                <label for="ext-decline" class="{{ $label }} mt-3">Your reason <span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                <div class="{{ $box }} items-start {{ $errors->has('declineReason') ? 'border-rose-400' : '' }}">
                    <textarea id="ext-decline" wire:model="declineReason" rows="3" maxlength="500" aria-required="true"
                              aria-describedby="ext-decline-help @error('declineReason') ext-decline-error @enderror" @error('declineReason') aria-invalid="true" @enderror
                              placeholder="{{ match ($kind) {
                                  'rating' => 'e.g. I do not know this student well enough to rate them.',
                                  'consent' => 'e.g. I am not this student\'s parent or guardian.',
                                  'form', 'recommend' => 'e.g. I do not know this student well enough.',
                                  'guardian' => 'e.g. I am not this student\'s parent or guardian.',
                                  'witness' => 'e.g. I did not witness this matter.',
                                  default => 'Please give your reason.',
                              } }}"
                              class="{{ $bare }} resize-y"></textarea>
                </div>
                @error('declineReason') <p id="ext-decline-error" class="{{ $err }}"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p> @enderror
                <button type="button" wire:click="decline" wire:loading.attr="disabled" wire:confirm="Decline this request? This ends it."
                        class="{{ $btn }} mt-3 bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-rose-300">Decline request</button>
            </section>
        @endif
        </div>{{-- left column --}}

        {{-- Wide screens: the whole document beside the form, refreshed from the live answers. --}}
        <aside class="hidden rounded-2xl border border-slate-200/70 bg-white p-4 xl:sticky xl:top-4 xl:block xl:max-h-[calc(100dvh-2rem)] xl:overflow-y-auto"
               aria-label="Document preview"
               x-data="{
                    html: '', busy: false, w: 842, h: 1171, scale: 1, seq: 0,
                    wide() { return window.matchMedia('(min-width: 1280px)').matches; },
                    async refresh() {
                        if (! this.wide()) return;
                        const n = ++this.seq;
                        this.busy = true;
                        const html = await this.$wire.previewDocument();
                        if (n !== this.seq) return;
                        this.html = html;
                        this.busy = false;
                    },
                    fit() { const cw = this.$refs.box ? this.$refs.box.clientWidth : 0; if (cw > 0) this.scale = Math.min(1, cw / this.w); },
                    measure(e) {
                        if (this.$refs.frame && e.source === this.$refs.frame.contentWindow && e.data && e.data.documatePreviewHeight) {
                            this.h = Math.ceil(e.data.documatePreviewHeight);
                        }
                    }
               }"
               x-init="fit(); new ResizeObserver(() => fit()).observe($refs.box); refresh()"
               @doc-changed.window.debounce.900ms="refresh()"
               @resize.window.debounce.300ms="html ? fit() : refresh()"
               @message.window="measure($event)">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Preview</h2>
                    <p class="text-xs text-slate-500">Updates as you answer. Nothing is signed by previewing.</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span x-show="busy && html" x-cloak class="inline-flex items-center gap-1 text-xs text-slate-500" role="status">
                        <i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i> Updating
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500">
                        <i class="bx bxs-file-pdf text-sm" aria-hidden="true"></i> A4 PDF
                    </span>
                </div>
            </div>
            <div x-ref="box" wire:ignore class="overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1]" :style="`height:${h * scale}px`">
                <div x-show="! html" class="flex h-64 items-center justify-center gap-2 text-sm text-slate-600" role="status">
                    <i class="bx bx-loader-alt bx-spin text-xl" aria-hidden="true"></i> Preparing preview…
                </div>
                <iframe x-ref="frame" x-show="html" :srcdoc="html" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                        :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
            </div>
        </aside>
        </div>{{-- two columns --}}
    @endif
</div>
