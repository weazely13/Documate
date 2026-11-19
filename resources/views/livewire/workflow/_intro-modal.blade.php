{{-- "Before you start": a popup that opens with the form. Reopen it with $dispatch('open-intro').
     Needs: $items (plain-text lines), $storageKey (remembers "don't show again" per form).
     Optional: $title, $lead, $note (amber notice), $startLabel, $steps ([[title, text], …]: numbered, in order). --}}
<div x-data="{
        open: false, opener: null, skip: false,
        key: @js('documate.intro.' . $storageKey),
        init() {
            try { this.skip = localStorage.getItem(this.key) === '1'; } catch (e) {}
            if (! this.skip) this.$nextTick(() => this.show());
        },
        show() { this.opener = document.activeElement; this.open = true; this.$nextTick(() => this.$refs.start?.focus()); },
        close() {
            this.open = false;
            try { this.skip ? localStorage.setItem(this.key, '1') : localStorage.removeItem(this.key); } catch (e) {}
            this.$nextTick(() => this.opener && this.opener !== document.body && this.opener.focus());
        }
     }"
     @open-intro.window="show()" @tour-started.window="open = false">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             class="fixed inset-0 z-[86] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-6"
             role="dialog" aria-modal="true" aria-labelledby="intro-title" aria-describedby="intro-lead"
             @keydown.escape.window="if (open) close()">
            <div class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
                 @click.outside="close()"
                 @keydown.tab="
                    const f = [...$el.querySelectorAll('button, a[href], input')].filter(e => e.offsetParent !== null);
                    if (! f.length) return;
                    if ($event.shiftKey && document.activeElement === f[0]) { $event.preventDefault(); f[f.length - 1].focus(); }
                    else if (! $event.shiftKey && document.activeElement === f[f.length - 1]) { $event.preventDefault(); f[0].focus(); }">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]" aria-hidden="true">
                            <i class="bx bx-info-circle text-xl"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 id="intro-title" class="text-base font-semibold tracking-[-0.01em] text-slate-900">{{ $title ?? 'Before you start' }}</h2>
                            <p id="intro-lead" class="mt-0.5 text-sm text-slate-500">{{ $lead ?? 'A quick checklist so you have everything ready.' }}</p>
                        </div>
                    </div>
                    <button type="button" @click="close()"
                            class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30"
                            aria-label="Close before you start">
                        <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                    </button>
                </div>

                @if (! empty($steps))
                    {{-- How the transaction goes, start to finish, numbered in order --}}
                    <div class="px-5 pt-4">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">How it goes</h3>
                        <ol class="mt-3 space-y-3">
                            @foreach ($steps as $i => [$stepTitle, $stepText])
                                <li class="flex gap-3">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-xs font-semibold tabular-nums text-white" aria-hidden="true">{{ $i + 1 }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $stepTitle }}</p>
                                        <p class="mt-0.5 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $stepText }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if (! empty($steps) && ! empty($items))
                    <h3 class="px-5 pt-5 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Have ready</h3>
                @endif
                <ul class="space-y-2.5 px-5 py-4 {{ empty($items) ? 'hidden' : '' }}">
                    @foreach ($items as $item)
                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-slate-700">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#2A57B4]" aria-hidden="true"></span>
                            {{-- A "*" in the text is the required-field marker: shown red, read out as "an asterisk". --}}
                            <span>{!! str_replace('*', '<span class="font-semibold text-rose-600" aria-hidden="true">*</span><span class="sr-only">an asterisk</span>', e($item)) !!}</span>
                        </li>
                    @endforeach
                </ul>

                @if (! empty($note))
                    <p class="mx-5 mb-4 rounded-lg bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">{{ $note }}</p>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" x-model="skip" class="h-4 w-4 accent-[#2A57B4]">
                        Don't show this again for this form
                    </label>
                    <button type="button" x-ref="start" @click="close()"
                            class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-9 sm:py-1.5 [@media(pointer:coarse)]:min-h-11">
                        {{ $startLabel ?? 'Start filling in' }} <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
