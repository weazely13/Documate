{{-- A paper form (template) as the student fills it in, laid out like the online forms
     (resources/views/livewire/student/transactions/form.blade.php): the form on the left, the page on the right.

     Needs: $template (name, image_url, document_size, orientation, canvas), $fields, $formValues, $instructions,
            $systemValues, $savedMessage, $lastSavedAt, $titleCase (fn), $preview (bool: the admin's preview, nothing saves)
     Runs on Alpine "documentWorkspace" (resources/js/app.js). --}}
@php
    $preview = $preview ?? false;
    $box = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border bg-white px-3.5 transition focus-within:ring-[3px]';
    $boxLocked = 'relative mt-1 flex min-w-0 items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5';
    $bare = 'w-full min-w-0 border-0 bg-transparent p-0 py-3 text-base text-slate-900 placeholder-slate-400 outline-none focus:ring-0 sm:py-2.5 sm:text-sm';
    $labelCls = 'block text-[13px] font-medium text-slate-700';
    $help = 'mt-1 text-xs leading-relaxed text-slate-500';
    $btnGhost = 'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:h-11';

    // The question shown to students: the field's label, unless it is still the editor's default ("TEXT"), then its name.
    $defaultLabels = ['text', 'number', 'date', 'paragraph', 'field'];
    $systemLabels = \App\Livewire\Admin\Templates\TemplateEditor::SYSTEM_VARIABLES;
    $labelOf = fn (array $f) => match (true) {
        ($f['label'] ?? '') !== '' && ! in_array(strtolower(trim($f['label'])), $defaultLabels, true) => $f['label'],
        ($f['source_type'] ?? '') === 'system' && isset($systemLabels[$f['system_key'] ?? '']) => preg_replace('/\s*\(automatic\)/i', '', $systemLabels[$f['system_key']]),
        default => $titleCase($f['name']),
    };

    $isLocked = fn (array $f) => ($f['source_type'] ?? 'input') === 'system' || (($f['type'] ?? '') === 'date' && ($f['date_mode'] ?? '') === 'current');
    $locked = collect($fields)->filter($isLocked)->values();
    $asked = collect($fields)->reject($isLocked)->values();
    $groups = $asked->groupBy(fn ($f) => $f['group_name'] ?: '')->sortBy(fn ($g, $k) => $k === '' ? 1 : 0);
    $icon = fn (array $f) => match ($f['type'] ?? 'text') {
        'date' => 'bx-calendar',
        'number' => 'bx-hash',
        default => null,
    };
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
     class="mx-auto w-full max-w-2xl xl:grid xl:max-w-[1440px] xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:items-start xl:gap-6 {{ $preview ? 'pb-24' : '' }}">

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

    <div class="min-w-0 space-y-5" :class="mobileTab === 'preview' ? 'hidden xl:block' : ''">
        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-x-4 sm:gap-y-2">
            <div class="min-w-0 flex-1">
                <h1 class="break-words text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $title }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ ! empty($template['description']) ? $template['description'] : 'Paper form · fill it in, save it as a PDF, then print it and bring it to the VPSD office.' }}
                    <template x-if="lastSavedAt"><span class="whitespace-nowrap">&middot; Saved <span x-text="lastSavedAt"></span></span></template>
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="showInstructions = true" class="{{ $btnGhost }} w-9 !px-0 [@media(pointer:coarse)]:w-11"
                        aria-label="How to fill out this form" title="How to fill out this form">
                    <i class="bx bx-info-circle text-xl" aria-hidden="true"></i>
                </button>
                <button type="button" @click="mobileTab = 'preview'" class="{{ $btnGhost }} px-4 xl:hidden">
                    <i class="bx bx-show text-base text-slate-500" aria-hidden="true"></i> Preview document
                </button>
            </div>
        </div>


        <template x-if="savedMessage">
            <div role="status" class="flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <i class="bx bx-check-circle text-lg" aria-hidden="true"></i><span x-text="savedMessage"></span>
            </div>
        </template>

        <form @submit.prevent class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm" aria-labelledby="pf-title">
            <h2 id="pf-title" class="sr-only">Fill in the form</h2>

            {{-- Filled in for you: profile details and today's date, locked --}}
            @if ($locked->isNotEmpty())
                <section x-data="{ open: false }" class="border-b border-slate-100 px-4 py-4 sm:px-5" aria-labelledby="pf-sec-auto">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="pf-auto"
                            class="-ml-1 inline-flex items-center gap-1.5 rounded-lg px-1 py-1 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
                        <i class="bx text-xl text-slate-400" :class="open ? 'bx-chevron-down' : 'bx-chevron-right'" aria-hidden="true"></i>
                        <span id="pf-sec-auto" class="text-sm font-semibold text-slate-900">Filled in for you</span>
                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">{{ $locked->count() }} from your account</span>
                    </button>
                    <div id="pf-auto" x-show="open" x-cloak class="mt-3">
                        <p class="{{ $help }} mt-0">Printed on the form for you. Change your details in your profile.</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($locked as $f)
                                <div wire:key="pf-lock-{{ $f['id'] }}">
                                    <label for="pf-{{ $f['name'] }}" class="{{ $labelCls }}">{{ $labelOf($f) }}</label>
                                    <div class="{{ $boxLocked }}">
                                        <i class="bx bx-lock-alt text-base text-slate-400" aria-hidden="true"></i>
                                        <input id="pf-{{ $f['name'] }}" type="text" disabled :value="displayValue(@js($f)) || '—'"
                                               class="{{ $bare }} cursor-not-allowed text-slate-700 disabled:text-slate-700">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- The questions, one section per group --}}
            @forelse ($groups as $groupName => $groupFields)
                <section class="border-b border-slate-100 px-4 py-5 last:border-b-0 sm:px-5" aria-labelledby="pf-g-{{ $loop->index }}">
                    <h3 id="pf-g-{{ $loop->index }}" class="text-sm font-semibold text-slate-900">
                        {{ $groupName !== '' ? $titleCase($groupName) : ($groups->count() > 1 ? 'Other details' : 'Your answers') }}
                    </h3>
                    @if ($loop->first)
                        <p class="{{ $help }}">Answer each item as it should appear on the printed form. The preview shows the result as you type.</p>
                    @endif

                    <div class="mt-4 space-y-4">
                        @foreach ($groupFields as $f)
                            @php
                                $name = Illuminate\Support\Js::from($f['name']);
                                $max = $f['max_length'] ?: null;
                            @endphp
                            <div wire:key="pf-f-{{ $f['id'] }}">
                                <label for="pf-{{ $f['name'] }}" class="{{ $labelCls }}">
                                    {{ $labelOf($f) }}
                                    @if ($f['required'])<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                                </label>

                                @if ($f['type'] === 'paragraph')
                                    <div class="{{ $box }} items-start"
                                         :class="errorModal.fields[{{ $name }}] ? 'border-rose-400 focus-within:border-rose-500 focus-within:ring-rose-200' : 'border-slate-300 focus-within:border-[#2A57B4] focus-within:ring-[#2A57B4]/20'">
                                        <textarea id="pf-{{ $f['name'] }}" x-model="values[{{ $name }}]" @focus="setActiveField({{ $name }})" @blur="clearActiveField()"
                                                  rows="{{ max((int) ($f['max_lines'] ?? 3), 3) }}" @if ($max) maxlength="{{ $max }}" @endif
                                                  placeholder="{{ $f['placeholder'] ?: '' }}" class="{{ $bare }} resize-y"
                                                  @if ($f['required']) aria-required="true" @endif></textarea>
                                        @if ($max)
                                            <span class="pointer-events-none absolute bottom-1.5 right-3 text-[11px] tabular-nums text-slate-500" aria-hidden="true"><span x-text="(values[{{ $name }}] || '').length"></span>/{{ $max }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="{{ $box }}"
                                         :class="errorModal.fields[{{ $name }}] ? 'border-rose-400 focus-within:border-rose-500 focus-within:ring-rose-200' : 'border-slate-300 focus-within:border-[#2A57B4] focus-within:ring-[#2A57B4]/20'">
                                        @if ($icon($f))<i class="bx {{ $icon($f) }} text-base text-slate-400" aria-hidden="true"></i>@endif
                                        <input id="pf-{{ $f['name'] }}" x-model="values[{{ $name }}]" @focus="setActiveField({{ $name }})" @blur="clearActiveField()"
                                               type="{{ $f['type'] === 'number' ? 'text' : ($f['type'] === 'date' ? 'date' : 'text') }}"
                                               @if ($f['type'] === 'number') inputmode="numeric" pattern="[0-9]*" @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, ''); values[{{ $name }}] = $event.target.value" @endif
                                               @if ($max && $f['type'] !== 'date') maxlength="{{ $max }}" @endif
                                               placeholder="{{ $f['type'] === 'date' ? '' : ($f['placeholder'] ?: '') }}" class="{{ $bare }}"
                                               @if ($f['required']) aria-required="true" @endif>
                                    </div>
                                @endif

                                @error('formValues.' . $f['name'])
                                    <p class="mt-1 flex items-start gap-1 text-xs font-medium text-rose-600"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>{{ $message }}</p>
                                @enderror
                                <template x-if="errorModal.fields[{{ $name }}]">
                                    <p class="mt-1 flex items-start gap-1 text-xs font-medium text-rose-600"><i class="bx bx-error-circle mt-px" aria-hidden="true"></i>This needs your answer.</p>
                                </template>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-500 sm:px-5">There is nothing to fill in on this form. Save it as a PDF and print it.</p>
            @endforelse

            {{-- Actions --}}
            <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-5">
                <p class="text-xs text-slate-500">
                    <span class="font-medium text-slate-700" x-text="filledCount + ' of ' + totalCount + ' answered'"></span>
                    · Print the PDF, sign where needed, and bring it to the VPSD office.
                </p>
                <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    @if ($preview)
                        <span class="text-center text-xs text-slate-500 sm:mr-auto sm:text-left">Preview only: these buttons work for students.</span>
                    @endif
                    <button type="button" @if ($preview) disabled @else @click="saveWorkspace()" @endif
                            class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-9 sm:w-auto sm:py-1.5">
                        <i class="bx bx-save text-base" aria-hidden="true"></i> Save as draft
                    </button>
                    <button type="button" @if ($preview) disabled @else @click="savePdf()" :disabled="pdfLoading" @endif
                            class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-9 sm:w-auto sm:py-1.5">
                        <i class="bx text-base" :class="pdfLoading ? 'bx-loader-alt bx-spin' : 'bxs-file-pdf'" aria-hidden="true"></i>
                        <span x-text="pdfLoading ? 'Preparing PDF…' : 'Save as PDF'">Save as PDF</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- The page, beside the form on wide screens; its own view on phones --}}
    <aside class="rounded-2xl border border-slate-200/70 bg-white p-4 xl:sticky xl:top-0 xl:block xl:max-h-[calc(100dvh-7rem)] xl:overflow-y-auto"
           :class="mobileTab === 'preview' ? '' : 'hidden xl:block'" aria-label="Document preview">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-slate-900">Preview</h2>
                <p class="text-xs text-slate-500">Updates as you type. This is how the printed page will look.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500">
                    <i class="bx bx-printer text-sm" aria-hidden="true"></i> {{ $template['document_size'] }}{{ $template['orientation'] === 'landscape' ? ' landscape' : '' }}
                </span>
                <button type="button" @click="mobileTab = 'form'" class="{{ $btnGhost }} xl:hidden"><i class="bx bx-arrow-back text-base" aria-hidden="true"></i> Form</button>
            </div>
        </div>
        <div x-ref="previewFrame" class="overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1] p-2 sm:p-3">
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
                        @php $name = Illuminate\Support\Js::from($f['name']); @endphp
                        <div class="absolute rounded-sm transition-shadow duration-150" x-bind:style="fieldBoxStyle(@js($f))"
                             :class="activeField === {{ $name }} ? 'ring-2 ring-[#2A57B4] ring-offset-2 z-10' : (errorModal.fields[{{ $name }}] ? 'ring-2 ring-rose-400 ring-offset-2 z-10' : '')">
                            <div class="pointer-events-none w-full px-2 py-1" x-bind:style="fieldTextStyle(@js($f))" x-text="displayValue(@js($f))"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </aside>

    {{-- How to fill out this form --}}
    <template x-teleport="body">
        <div x-show="showInstructions" x-cloak x-transition.opacity x-teleport-owner="pf-instructions"
             class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             @click.self="showInstructions = false" @keydown.escape.window="showInstructions = false"
             role="dialog" aria-modal="true" aria-labelledby="pf-ins-title">
            <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h2 id="pf-ins-title" class="text-base font-semibold text-slate-900">How to fill out this form</h2>
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
                        <li class="px-2 py-2.5 text-sm text-slate-500">Fill in each item, save the form as a PDF, print it and bring it to the VPSD office.</li>
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
                        <h3 id="pf-err-title" class="text-base font-semibold text-slate-900">This can't be saved yet</h3>
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
