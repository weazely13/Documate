@php
    $field = 'w-full rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';
    $label = 'mb-1.5 block text-sm font-medium text-slate-700';
    $isIssue = $type === 'issue';
    $ratingWords = [1 => 'Poor', 2 => 'Fair', 3 => 'Okay', 4 => 'Good', 5 => 'Excellent'];
@endphp

<div class="mx-auto max-w-6xl">

    {{-- Banner --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#2A57B4] via-[#24499A] to-[#1d3c80] px-5 py-6 text-white shadow-sm sm:px-7 sm:py-7">
        <i class="bx bx-message-square-dots pointer-events-none absolute -right-4 -top-6 text-[9rem] text-white/10" aria-hidden="true"></i>
        <i class="bx bx-bug pointer-events-none absolute bottom-[-2.5rem] right-32 hidden text-[6rem] text-white/5 sm:block" aria-hidden="true"></i>
        <p class="text-xs font-semibold uppercase tracking-widest text-blue-100">OVPSD · Help desk</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Feedback & Issues</h1>
        <p class="mt-1.5 max-w-xl text-sm text-blue-100">Tell the office what's working, what isn't, or report a problem with DocuMate. Replies appear on this page and in your notifications.</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1"><i class="bx bx-bell"></i>Replies come to your notifications</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/90 px-3 py-1 text-amber-950"><i class="bx bxs-star"></i>Your rating helps us improve</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] lg:items-start">

        {{-- ================= FORM ================= --}}
        <form wire:submit="submit" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="h-1 {{ $isIssue ? 'bg-gradient-to-r from-rose-500 to-amber-400' : 'bg-gradient-to-r from-[#2A57B4] to-sky-400' }}" aria-hidden="true"></div>

            {{-- Type switch --}}
            <div class="border-b border-slate-100 p-4 sm:p-5">
                <div role="radiogroup" aria-label="What would you like to do?" class="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['feedback' => ['Share feedback', 'bx-message-square-dots'], 'issue' => ['Report an issue', 'bx-error-circle']] as $key => [$text, $icon])
                        <button type="button" role="radio" aria-checked="{{ $type === $key ? 'true' : 'false' }}"
                                wire:click="$set('type', '{{ $key }}')"
                                class="flex items-center justify-center gap-2 rounded-md px-3 py-2.5 text-sm font-semibold transition sm:py-2
                                    {{ $type === $key ? ($key === 'issue' ? 'bg-rose-600 text-white shadow-sm' : 'bg-[#2A57B4] text-white shadow-sm') : ($key === 'issue' ? 'text-slate-500 hover:bg-rose-50 hover:text-rose-700' : 'text-slate-500 hover:bg-blue-50 hover:text-[#2A57B4]') }}">
                            <i class="bx {{ $icon }} text-lg"></i>{{ $text }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5 p-4 sm:p-5">

                {{-- Rating (feedback) --}}
                @unless ($isIssue)
                    <div x-data="{ hover: 0 }">
                        <span class="{{ $label }}">How was your experience with DocuMate?</span>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1" @mouseleave="hover = 0" role="radiogroup" aria-label="Rating">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="setRating({{ $i }})" @mouseenter="hover = {{ $i }}"
                                            role="radio" aria-checked="{{ $rating === $i ? 'true' : 'false' }}" aria-label="{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}"
                                            class="rounded p-0.5 transition hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                                        <i class="bx text-3xl"
                                           :class="(hover ? hover >= {{ $i }} : {{ (int) $rating }} >= {{ $i }}) ? 'bxs-star text-amber-400' : 'bx-star text-slate-300'"></i>
                                    </button>
                                @endfor
                            </div>
                            @if ($rating)
                                <span class="text-sm font-medium text-slate-600">{{ $ratingWords[$rating] }}</span>
                            @endif
                        </div>
                        @error('rating') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endunless

                {{-- Topic --}}
                <fieldset>
                    <legend class="{{ $label }}">{{ $isIssue ? 'What is the problem about?' : 'What is your feedback about?' }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $key => $text)
                            <label wire:key="cat-{{ $type }}-{{ $key }}"
                                   class="cursor-pointer rounded-full border px-3.5 py-2 text-sm font-medium transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2A57B4]
                                       {{ $category === $key
                                           ? ($isIssue ? 'border-rose-600 bg-rose-600 text-white shadow-sm' : 'border-[#2A57B4] bg-[#2A57B4] text-white shadow-sm')
                                           : ($isIssue ? 'border-rose-200 bg-rose-50/60 text-rose-700 hover:border-rose-400' : 'border-blue-200 bg-blue-50/60 text-[#2A57B4] hover:border-[#2A57B4]') }}">
                                <input type="radio" wire:model.live="category" value="{{ $key }}" class="sr-only">
                                {{ $text }}
                            </label>
                        @endforeach
                    </div>
                    @error('category') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </fieldset>

                {{-- Severity (issue) --}}
                @if ($isIssue)
                    <fieldset>
                        <legend class="{{ $label }}">How much does it affect you?</legend>
                        <div class="grid gap-2 sm:grid-cols-3">
                            @foreach ($severities as $key => $text)
                                @php [$head, $tail] = array_pad(array_map('trim', explode(':', $text, 2)), 2, ''); @endphp
                                <label wire:key="sev-{{ $key }}"
                                       class="flex cursor-pointer flex-col rounded-lg border p-3 transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2A57B4]
                                           {{ $severity === $key
                                               ? ['low' => 'border-slate-500 bg-slate-100 ring-1 ring-slate-500', 'medium' => 'border-amber-500 bg-amber-50 ring-1 ring-amber-500', 'high' => 'border-rose-500 bg-rose-50 ring-1 ring-rose-500'][$key]
                                               : ['low' => 'border-slate-200 hover:border-slate-400', 'medium' => 'border-amber-200 hover:border-amber-400', 'high' => 'border-rose-200 hover:border-rose-400'][$key] }}">
                                    <input type="radio" wire:model.live="severity" value="{{ $key }}" class="sr-only">
                                    <span class="flex items-center gap-2 text-sm font-semibold {{ ['low' => 'text-slate-700', 'medium' => 'text-amber-700', 'high' => 'text-rose-700'][$key] }}">
                                        <span class="h-2 w-2 rounded-full {{ ['low' => 'bg-slate-400', 'medium' => 'bg-amber-500', 'high' => 'bg-rose-500'][$key] }}"></span>
                                        {{ $head }}
                                    </span>
                                    <span class="mt-0.5 text-xs text-slate-500">{{ ucfirst($tail) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('severity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

                {{-- Subject --}}
                <div>
                    <label for="fb-subject" class="{{ $label }}">{{ $isIssue ? 'Short summary of the problem' : 'Subject' }}</label>
                    <input id="fb-subject" type="text" wire:model="subject" maxlength="150" class="{{ $field }}"
                           placeholder="{{ $isIssue ? 'e.g. Upload button does nothing on the clearance form' : 'e.g. Booking an appointment was quick' }}">
                    @error('subject') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Message --}}
                <div x-data="{ count: @js(mb_strlen($message)) }">
                    <label for="fb-message" class="{{ $label }}">{{ $isIssue ? 'What happened?' : 'Your feedback' }}</label>
                    <textarea id="fb-message" rows="5" wire:model="message" maxlength="3000" @input="count = $event.target.value.length"
                              class="{{ $field }}"
                              placeholder="{{ $isIssue ? 'What were you trying to do, what did you expect, and what happened instead?' : 'What did you like, and what could be better?' }}"></textarea>
                    <div class="mt-1 flex justify-between gap-3">
                        @error('message') <p class="text-sm text-rose-600">{{ $message }}</p> @else <span></span> @enderror
                        <span class="shrink-0 text-xs tabular-nums text-slate-400"><span x-text="count"></span>/3000</span>
                    </div>
                </div>

                {{-- Page (issue) --}}
                @if ($isIssue)
                    <div>
                        <label for="fb-page" class="{{ $label }}">Where did it happen? <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="fb-page" type="text" wire:model="pageUrl" maxlength="500" class="{{ $field }}" placeholder="Page name or link">
                        <p class="mt-1 text-xs text-slate-500">Filled in with the page you came from. Change it if the problem was somewhere else.</p>
                    </div>
                @endif

                {{-- Screenshot --}}
                <div>
                    <span class="{{ $label }}">{{ $isIssue ? 'Screenshot' : 'Image' }} <span class="font-normal text-slate-400">(optional)</span></span>
                    @if ($attachment && ! $errors->has('attachment'))
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-2">
                            <img src="{{ $attachment->temporaryUrl() }}" alt="Attachment preview" class="h-16 w-16 rounded-md object-cover">
                            <span class="min-w-0 flex-1 truncate text-sm text-slate-600">{{ $attachment->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('attachment', null)" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-rose-600">Remove</button>
                        </div>
                    @else
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition hover:border-[#2A57B4] hover:bg-blue-50/40 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#2A57B4]">
                            <input type="file" wire:model="attachment" accept="image/png,image/jpeg,image/webp" class="sr-only">
                            <i class="bx bx-image-add text-3xl text-slate-400"></i>
                            <span class="text-sm font-medium text-[#2A57B4]" wire:loading.remove wire:target="attachment">Choose an image</span>
                            <span class="text-sm text-slate-500" wire:loading wire:target="attachment">Uploading…</span>
                            <span class="text-xs text-slate-400">PNG, JPG or WEBP, up to 5 MB</span>
                        </label>
                    @endif
                    @error('attachment') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p class="text-xs text-slate-500">Your name and account are attached so the office can follow up.</p>
                <button type="submit" wire:loading.attr="disabled" wire:target="submit,attachment"
                        class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition disabled:opacity-60 {{ $isIssue ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#2A57B4] hover:bg-[#24499A]' }}">
                    <i class="bx bx-send" wire:loading.remove wire:target="submit"></i>
                    <i class="bx bx-loader-alt animate-spin" wire:loading wire:target="submit"></i>
                    {{ $isIssue ? 'Send report' : 'Send feedback' }}
                </button>
            </div>
        </form>

        {{-- ================= HISTORY ================= --}}
        <section aria-labelledby="fb-history" class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                <h2 id="fb-history" class="flex items-center gap-2 text-sm font-semibold text-slate-900"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]"><i class="bx bx-history"></i></span>Your submissions</h2>
                <span class="text-xs text-slate-500">{{ $reports->count() }} {{ \Illuminate\Support\Str::plural('item', $reports->count()) }}</span>
            </div>

            @forelse ($reports as $r)
                <div wire:key="mine-{{ $r->id }}" class="border-b border-l-4 border-b-slate-100 last:border-b-0 {{ ['new' => 'border-l-[#2A57B4]', 'in_progress' => 'border-l-amber-400', 'resolved' => 'border-l-emerald-500'][$r->status] ?? 'border-l-slate-300' }}">
                    <button type="button" wire:click="toggle({{ $r->id }})" aria-expanded="{{ $openId === $r->id ? 'true' : 'false' }}"
                            class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition hover:bg-slate-50 sm:px-5">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $r->type === 'issue' ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-[#2A57B4]' }}">
                            <i class="bx {{ $r->type === 'issue' ? 'bx-error-circle' : 'bx-message-square-dots' }} text-lg"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $r->subject }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $r->reference() }} · {{ $r->created_at->format('M j, Y') }}</span>
                        </span>
                        <span class="flex shrink-0 flex-col items-end gap-1">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ \App\Models\FeedbackReport::statusBadge($r->status) }}">{{ $r->statusLabel() }}</span>
                            @if ($r->admin_reply)
                                <span class="flex items-center gap-1 text-[11px] font-medium text-[#2A57B4]"><i class="bx bx-reply"></i>Reply</span>
                            @endif
                        </span>
                    </button>

                    @if ($openId === $r->id)
                        <div class="space-y-3 bg-slate-50/70 px-4 pb-4 pt-1 text-sm sm:px-5">
                            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-xs">
                                <dt class="text-slate-400">Type</dt><dd class="text-slate-700">{{ $r->typeLabel() }} · {{ $r->categoryLabel() }}</dd>
                                @if ($r->rating)
                                    <dt class="text-slate-400">Rating</dt>
                                    <dd class="flex items-center gap-0.5 text-amber-400" aria-label="{{ $r->rating }} out of 5">
                                        @for ($i = 1; $i <= 5; $i++)<i class="bx {{ $i <= $r->rating ? 'bxs-star' : 'bx-star text-slate-300' }}"></i>@endfor
                                    </dd>
                                @endif
                                @if ($r->severity)
                                    <dt class="text-slate-400">Impact</dt><dd class="text-slate-700">{{ $r->severityLabel() }}</dd>
                                @endif
                            </dl>
                            <p class="whitespace-pre-line text-slate-700">{{ $r->message }}</p>
                            @if ($r->attachment_path)
                                <a href="{{ route('feedback.attachment', $r) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium text-[#2A57B4] hover:underline"><i class="bx bx-image"></i>View attached image</a>
                            @endif

                            @if ($r->admin_reply)
                                <div class="rounded-lg border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-3">
                                    <p class="flex items-center gap-1.5 text-xs font-semibold text-[#2A57B4]">
                                        <i class="bx bx-buildings"></i>OVPSD office
                                        @if ($r->replied_at)<span class="font-normal text-slate-400">· {{ $r->replied_at->diffForHumans() }}</span>@endif
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-slate-700">{{ $r->admin_reply }}</p>
                                </div>
                            @else
                                <p class="text-xs text-slate-500">No reply yet. You'll get a notification when the office responds.</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <i class="bx bx-message-square-dots text-4xl text-slate-300"></i>
                    <p class="mt-2 text-sm font-medium text-slate-700">Nothing sent yet</p>
                    <p class="mt-1 text-xs text-slate-500">Feedback and reports you send will show up here with the office's replies.</p>
                </div>
            @endforelse
        </section>
    </div>
</div>
