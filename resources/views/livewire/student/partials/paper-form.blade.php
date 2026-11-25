{{-- A paper form (template), filled out right on the page: each blank on the form image is the place to type.
     On a narrow screen the page is too small to type on, so tapping a blank opens it large at the foot of the
     screen (with Back / Next through the blanks).

     Needs: $template (name, image_url, document_size, orientation, canvas), $fields, $formValues, $instructions,
            $systemValues, $savedMessage, $lastSavedAt, $titleCase (fn), $preview (bool: the admin's preview, nothing saves)
     Runs on Alpine "documentWorkspace" (resources/js/app.js). --}}
@php
    $preview = $preview ?? false;
    $btnGhost = 'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:h-11';

    // What a blank is called: the field's label, unless it is still the editor's default ("TEXT"), then its name.
    $defaultLabels = ['text', 'number', 'date', 'paragraph', 'field'];
    $systemLabels = \App\Livewire\Admin\Templates\TemplateEditor::SYSTEM_VARIABLES;
    $labelOf = fn (array $f) => match (true) {
        ($f['label'] ?? '') !== '' && ! in_array(strtolower(trim($f['label'])), $defaultLabels, true) => $f['label'],
        ($f['source_type'] ?? '') === 'system' && isset($systemLabels[$f['system_key'] ?? '']) => preg_replace('/\s*\(automatic\)/i', '', $systemLabels[$f['system_key']]),
        default => $titleCase($f['name']),
    };

    $isLocked = fn (array $f) => ($f['source_type'] ?? 'input') === 'system' || (($f['type'] ?? '') === 'date' && ($f['date_mode'] ?? '') === 'current');

    // The blanks to fill, top to bottom and left to right as they sit on the page.
    $asked = collect($fields)->reject($isLocked)
        ->sortBy([fn ($a, $b) => round(($a['y'] ?? 0) / 8) <=> round(($b['y'] ?? 0) / 8), fn ($a, $b) => ($a['x'] ?? 0) <=> ($b['x'] ?? 0)])
        ->values();
    $blanks = $asked->map(fn ($f) => [
        'name' => $f['name'],
        'label' => $labelOf($f),
        'type' => $f['type'] ?? 'text',
        'max' => $f['max_length'] ?: null,
        'required' => (bool) ($f['required'] ?? false),
        'placeholder' => $f['placeholder'] ?? '',
    ])->all();
    $title = $titleCase($template['name']);
@endphp

<div x-data="documentWorkspace({
        initialValues: @js($formValues),
        fields: @js($fields),
        systemValues: @js($systemValues),
        initialSavedMessage: @js($savedMessage),
        initialLastSavedAt: @js($lastSavedAt),
        groups: [],
        canvasWidth: {{ $template['canvas']['width'] }},
        canvasHeight: {{ $template['canvas']['height'] }},
     })"
     class="mx-auto w-full max-w-5xl space-y-3 sm:space-y-4 {{ $preview ? 'pb-24' : '' }}">

    @if ($preview)
        {{-- The admin's preview: framed and labelled over the whole screen, so it is never mistaken for the real thing --}}
        <div class="pointer-events-none fixed inset-0 z-[60] ring-[5px] ring-inset ring-[#2A57B4]/60" aria-hidden="true"></div>
        <div class="fixed inset-x-0 bottom-4 z-[61] flex justify-center px-3" role="note" aria-label="Student preview">
            <div class="flex max-w-full items-center gap-3 rounded-2xl bg-slate-900/95 py-2 pl-4 pr-2 text-white shadow-2xl ring-1 ring-white/10 backdrop-blur">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#2A57B4]"><i class="bx bx-show text-lg" aria-hidden="true"></i></span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold">Student preview</span>
                    <span class="block truncate text-xs text-white/70">Sample profile details · type to test · nothing is saved{{ ! empty($previewStatus) ? ' · ' . $previewStatus : '' }}</span>
                </span>
                @if (! empty($previewBack))
                    <a href="{{ $previewBack }}" class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-lg bg-white px-3 text-sm font-medium text-slate-900 hover:bg-slate-100">
                        <i class="bx bx-arrow-back text-base" aria-hidden="true"></i><span class="hidden sm:inline">Back to editor</span><span class="sm:hidden">Back</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold tracking-[-0.015em] text-slate-900 sm:text-xl">{{ $title }}</h1>
            <p class="text-xs text-slate-500">
                Paper form · {{ $template['document_size'] }}{{ $template['orientation'] === 'landscape' ? ' landscape' : '' }}
                <template x-if="lastSavedAt"><span class="whitespace-nowrap">&middot; Saved <span x-text="lastSavedAt"></span></span></template>
            </p>
        </div>
        <button type="button" @click="showInstructions = true" class="{{ $btnGhost }} w-9 shrink-0 !px-0 [@media(pointer:coarse)]:w-11"
                aria-label="How to fill out this form" title="How to fill out this form">
            <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
        </button>
    </div>

    <template x-if="savedMessage">
        <div role="status" class="flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">
            <i class="bx bx-check-circle text-lg" aria-hidden="true"></i><span x-text="savedMessage"></span>
        </div>
    </template>

    {{-- The form itself. Blue boxes are the blanks: type in them (or tap them on a phone). --}}
    <form @submit.prevent aria-labelledby="pf-title"
          x-data="{
            blanks: @js($blanks),
            editing: null,
            get small() { return this.previewScale < 0.72; },
            get pos() { return this.blanks.findIndex(b => b.name === this.editing); },
            get current() { return this.blanks[this.pos] || null; },
            edit(name) { this.editing = name; },
            step(d) { const i = this.pos + d; if (i >= 0 && i < this.blanks.length) this.editing = this.blanks[i].name; else this.editing = null; },
            onFocus(name, el) {
                this.setActiveField(name);
                if (this.small) { el.blur(); this.edit(name); }
            },
            // The page shrinks to the frame's width. Done here: the outer component cannot see refs inside this one.
            init() {
                const frame = this.$refs.previewFrame;
                const fit = () => {
                    const cs = getComputedStyle(frame);
                    const inner = frame.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
                    if (inner > 0) this.previewScale = Math.min(inner / this.canvasWidth, 1);
                };
                this.$nextTick(fit);
                new ResizeObserver(fit).observe(frame);
            },
          }">
        <h2 id="pf-title" class="sr-only">Fill out the form: type in each blank</h2>


        <div x-ref="previewFrame" class="overflow-hidden rounded-xl bg-[#e7e5e1] p-1.5 sm:p-3">
            <div class="mx-auto" :style="`width: ${canvasWidth * previewScale}px; height: ${canvasHeight * previewScale}px;`">
                <div class="relative origin-top-left bg-white shadow-sm"
                     :style="`width: ${canvasWidth}px; height: ${canvasHeight}px; transform: scale(${previewScale});`">
                    <img src="{{ $template['image_url'] }}" alt="{{ $title }}" class="pointer-events-none absolute inset-0 h-full w-full select-none">
                    @if ($preview)
                        <span class="pointer-events-none absolute inset-0 z-20 grid place-items-center overflow-hidden" aria-hidden="true">
                            <span class="-rotate-[30deg] select-none whitespace-nowrap text-[140px] font-semibold tracking-[0.2em] text-[#2A57B4]/[0.07]">PREVIEW</span>
                        </span>
                    @endif

                    @foreach ($fields as $f)
                        @php
                            $name = Illuminate\Support\Js::from($f['name']);
                            $max = $f['max_length'] ?: null;
                        @endphp
                        @if ($isLocked($f))
                            {{-- Printed for you (profile details, today's date) --}}
                            <div class="absolute" x-bind:style="fieldBoxStyle(@js($f))" wire:key="pf-lock-{{ $f['id'] }}" title="{{ $labelOf($f) }} (from your account)">
                                <div class="pointer-events-none w-full px-2 py-1" x-bind:style="fieldTextStyle(@js($f))" x-text="displayValue(@js($f))"></div>
                            </div>
                        @else
                            @php
                                $cls = 'block h-full w-full resize-none rounded-[2px] border-0 px-2 py-1 outline-none ring-inset transition-colors focus:outline-none focus:ring-2 focus:ring-[#2A57B4] focus:bg-white placeholder:text-[#2A57B4]/45';
                            @endphp
                            <div class="absolute" x-bind:style="fieldBoxStyle(@js($f))" wire:key="pf-f-{{ $f['id'] }}">
                                @if ($f['type'] === 'paragraph')
                                    <textarea id="pf-{{ $f['name'] }}" x-model="values[{{ $name }}]"
                                              @focus="onFocus({{ $name }}, $el)" @blur="clearActiveField()"
                                              @if ($max) maxlength="{{ $max }}" @endif
                                              placeholder="{{ $labelOf($f) }}" aria-label="{{ $labelOf($f) }}" title="{{ $labelOf($f) }}"
                                              @if ($f['required']) aria-required="true" @endif
                                              class="{{ $cls }}" x-bind:style="fieldTextStyle(@js($f)) + 'height:100%;max-height:none;'"
                                              :class="errorModal.fields[{{ $name }}] ? 'bg-rose-100/70 ring-2 ring-rose-500' : (isFilled({{ $name }}) ? 'bg-transparent ring-1 ring-[#2A57B4]/15 hover:bg-[#2A57B4]/[0.06]' : 'bg-[#2A57B4]/[0.08] ring-1 ring-[#2A57B4]/40 hover:bg-[#2A57B4]/[0.14]')"></textarea>
                                @else
                                    <input id="pf-{{ $f['name'] }}" x-model="values[{{ $name }}]"
                                           @focus="onFocus({{ $name }}, $el)" @blur="clearActiveField()"
                                           type="{{ $f['type'] === 'date' ? 'date' : 'text' }}"
                                           @if ($f['type'] === 'number') inputmode="numeric" pattern="[0-9]*" @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, ''); values[{{ $name }}] = $event.target.value" @endif
                                           @if ($max && $f['type'] !== 'date') maxlength="{{ $max }}" @endif
                                           placeholder="{{ $f['type'] === 'date' ? '' : $labelOf($f) }}" aria-label="{{ $labelOf($f) }}" title="{{ $labelOf($f) }}"
                                           @if ($f['required']) aria-required="true" @endif
                                           class="{{ $cls }}" x-bind:style="fieldTextStyle(@js($f)) + 'height:100%;'"
                                           :class="errorModal.fields[{{ $name }}] ? 'bg-rose-100/70 ring-2 ring-rose-500' : (isFilled({{ $name }}) ? 'bg-transparent ring-1 ring-[#2A57B4]/15 hover:bg-[#2A57B4]/[0.06]' : 'bg-[#2A57B4]/[0.08] ring-1 ring-[#2A57B4]/40 hover:bg-[#2A57B4]/[0.14]')">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @error('formValues')
            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
        @enderror

        {{-- Phones: the blank being filled, large, at the foot of the screen --}}
        <div x-show="editing" x-cloak x-transition.opacity
             class="fixed inset-0 z-[90] flex items-end bg-slate-900/40"
             @click.self="editing = null" @keydown.escape.window="editing = null"
             role="dialog" aria-modal="true" aria-labelledby="pf-edit-label">
            <div class="w-full rounded-t-2xl bg-white px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl" x-show="editing" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-6">
                <div class="mx-auto mb-2 h-1 w-10 rounded-full bg-slate-300" aria-hidden="true"></div>
                <div class="flex items-center justify-between gap-3">
                    <label for="pf-edit" id="pf-edit-label" class="min-w-0 truncate text-sm font-semibold text-slate-900">
                        <span x-text="current?.label"></span><span x-show="current?.required" class="text-rose-600" aria-hidden="true"> *</span>
                    </label>
                    <span class="shrink-0 text-xs tabular-nums text-slate-500" x-text="(pos + 1) + ' of ' + blanks.length"></span>
                </div>
                <template x-if="current && current.type === 'paragraph'">
                    <textarea id="pf-edit" x-model="values[current.name]" rows="4" :maxlength="current.max || null"
                              x-effect="editing; $nextTick(() => $el.focus())"
                              class="mt-2 block w-full resize-none border-0 bg-transparent px-0.5 py-0 text-base font-medium leading-7 text-slate-900 outline-none focus:ring-0 bg-[linear-gradient(to_bottom,transparent_27px,#2A57B4_27px,#2A57B4_28px)] bg-[length:100%_28px] bg-local"></textarea>
                </template>
                <template x-if="current && current.type !== 'paragraph'">
                    <input id="pf-edit" x-model="values[current.name]" :type="current.type === 'date' ? 'date' : 'text'"
                           :inputmode="current.type === 'number' ? 'numeric' : null" :maxlength="current.type !== 'date' ? (current.max || null) : null"
                           @input="if (current.type === 'number') { $event.target.value = $event.target.value.replace(/[^0-9]/g, ''); values[current.name] = $event.target.value }"
                           @keydown.enter.prevent="step(1)" enterkeyhint="next"
                           x-effect="editing; $nextTick(() => $el.focus())"
                           class="mt-2 block w-full border-0 border-b-2 border-[#2A57B4] bg-transparent px-0.5 py-2 text-lg font-medium text-slate-900 outline-none focus:ring-0">
                </template>
                <div class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-2">
                    <button type="button" @click="step(-1)" :disabled="pos <= 0"
                            class="inline-flex min-h-11 items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 disabled:opacity-40">
                        <i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> Back
                    </button>
                    <button type="button" @click="step(1)"
                            class="inline-flex min-h-11 items-center justify-center gap-1 rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white">
                        <span x-text="pos >= blanks.length - 1 ? 'Done' : 'Next'"></span> <i class="bx bx-chevron-right text-lg" aria-hidden="true" x-show="pos < blanks.length - 1"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Actions: below the form page --}}
        <div class="mt-3 py-2">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <p class="text-xs text-slate-500">
                    <span class="font-semibold tabular-nums text-slate-800" x-text="filledCount + ' of ' + totalCount"></span> filled · print, sign, bring to VPSD
                </p>
                @if ($preview)
                    <span class="text-xs text-slate-500">Preview only: buttons work for students.</span>
                @endif
            </div>
            <div class="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-2 sm:flex sm:justify-end">
                @if ($blanks)
                    <button type="button" @click="edit(blanks[0].name)" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 sm:hidden" x-show="small">
                        <i class="bx bx-edit-alt text-base" aria-hidden="true"></i><span class="sr-only">Fill out, one blank at a time</span>
                    </button>
                @endif
                <button type="button" @if ($preview) disabled @else @click="saveWorkspace()" @endif
                        class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-9">
                    <i class="bx bx-save text-base" aria-hidden="true"></i> Save draft
                </button>
                <button type="button" @if ($preview) disabled @else @click="savePdf()" :disabled="pdfLoading" @endif
                        class="col-span-2 inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:col-span-1 sm:min-h-9">
                    <i class="bx text-base" :class="pdfLoading ? 'bx-loader-alt bx-spin' : 'bxs-file-pdf'" aria-hidden="true"></i>
                    <span x-text="pdfLoading ? 'Preparing PDF…' : 'Save as PDF'">Save as PDF</span>
                </button>
            </div>
        </div>
    </form>

    {{-- How to fill out this form --}}
    <template x-teleport="body">
        <div x-show="showInstructions" x-cloak x-transition.opacity x-teleport-owner="pf-instructions"
             class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             @click.self="showInstructions = false" @keydown.escape.window="showInstructions = false"
             role="dialog" aria-modal="true" aria-labelledby="pf-ins-title">
            <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h2 id="pf-ins-title" class="text-base font-semibold text-slate-900">How to fill it out</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $title }}</p>
                    </div>
                    <button type="button" @click="showInstructions = false" class="-mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]" aria-label="Close">
                        <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                    </button>
                </div>
                <ol class="space-y-1 overflow-y-auto px-3 py-4 sm:px-4">
                    @forelse ($instructions as $i => $step)
                        <li class="flex gap-3 rounded-xl px-2 py-2.5">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-[#2A57B4] text-xs font-semibold text-[#2A57B4]">{{ $i + 1 }}</span>
                            <p class="text-sm leading-relaxed text-slate-700">{{ $step['description'] }}</p>
                        </li>
                    @empty
                        <li class="px-2 py-2.5 text-sm text-slate-500">Type in the blue boxes, save as PDF, print it and bring it to the VPSD office.</li>
                    @endforelse
                </ol>
                <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                    <button type="button" @click="showInstructions = false" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] sm:w-auto">Got it</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Something to fix before saving --}}
    <template x-teleport="body">
        <div x-show="errorModal.open" x-cloak x-transition.opacity x-teleport-owner="pf-error"
             class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             @click.self="errorModal.open = false" @keydown.escape.window="errorModal.open = false" role="alertdialog" aria-modal="true" aria-labelledby="pf-err-title">
            <div class="w-full max-w-md rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600"><i class="bx bx-error text-xl" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1">
                        <h3 id="pf-err-title" class="text-base font-semibold text-slate-900">Not saved yet</h3>
                        <p class="mt-1 text-sm text-slate-600" x-text="errorModal.message"></p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" @click="errorModal.open = false" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white hover:bg-[#24499A] sm:w-auto">Got it</button>
                </div>
            </div>
        </div>
    </template>
</div>
