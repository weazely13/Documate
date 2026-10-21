<div>
    @if($selectedTemplate)
        @php
            $groupedFields = collect($fields)->groupBy(fn($f) => $f['group_name'] ?: 'Other');
            $groupNames = $groupedFields->keys()->values();
            $iconBtn = 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 active:scale-[0.97] sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5';
        @endphp

        <div
            x-data="documentWorkspace({
                initialValues: @js($formValues),
                fields: @js($fields),
                systemValues: @js($this->systemPreviewValues),
                initialSavedMessage: @js($savedMessage),
                initialLastSavedAt: @js($lastSavedAt),
                groups: @js($groupNames),
                canvasWidth: {{ $selectedTemplate['canvas']['width'] }},
                canvasHeight: {{ $selectedTemplate['canvas']['height'] }},
            })"
            class="mx-auto w-full max-w-6xl space-y-4"
        >
            {{-- Top action bar: icon-only on mobile, labels from sm up --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <button type="button" @click="showInstructions = true" title="How to Fill out This Form" class="{{ $iconBtn }}">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path stroke-linecap="round" d="M12 11v5"></path>
                            <path stroke-linecap="round" d="M12 8h.01"></path>
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline">Instructions</span>
                    </button>

                    <template x-if="savedMessage">
                        <div class="inline-flex min-w-0 items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 sm:px-4 sm:text-sm">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.5 4.5L19 7" />
                            </svg>
                            <span x-text="savedMessage" class="truncate"></span>
                        </div>
                    </template>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <a href="{{ route('student.new-transaction') }}" wire:navigate title="Back to Documents" class="{{ $iconBtn }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline">Back to Documents</span>
                    </a>

                    <button type="button" @click="savePdf()" :disabled="pdfLoading" title="Save as PDF" class="{{ $iconBtn }} disabled:opacity-50">
                        <svg x-show="!pdfLoading" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6" />
                        </svg>
                        <svg x-show="pdfLoading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline" x-text="pdfLoading ? 'Generating…' : 'Save as PDF'"></span>
                    </button>

                    <button type="button" @click="saveWorkspace()" title="Save Workspace"
                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#2A57B4] px-4 text-sm font-medium text-white transition hover:bg-[#24499A] active:scale-[0.97] sm:h-auto sm:py-2.5">
                        <svg class="h-4 w-4 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8M7 3v5h8" />
                        </svg>
                        <span>Save<span class="hidden sm:inline"> Workspace</span></span>
                    </button>
                </div>
            </div>

            {{-- Mobile only: segmented switch between form and preview --}}
            <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 xl:hidden" role="tablist">
                <button type="button" role="tab" @click="mobileTab = 'form'" :aria-selected="mobileTab === 'form'"
                    class="rounded-lg py-2 text-sm font-semibold transition"
                    :class="mobileTab === 'form' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500'">
                    Form Inputs
                </button>
                <button type="button" role="tab" @click="mobileTab = 'preview'" :aria-selected="mobileTab === 'preview'"
                    class="rounded-lg py-2 text-sm font-semibold transition"
                    :class="mobileTab === 'preview' ? 'bg-white text-[#2A57B4] shadow-sm' : 'text-slate-500'">
                    Document Preview
                </button>
            </div>

            <div class="grid gap-5 xl:grid-cols-[360px,minmax(0,1fr)]">
                <aside class="space-y-4 sm:space-y-5" :class="mobileTab === 'form' ? '' : 'hidden xl:block'">

                    {{-- Document details --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Document Details</p>
                                <h2 class="mt-1 text-base font-semibold leading-snug text-slate-900 sm:text-lg">{{ $this->formatTitle($selectedTemplate['name']) }}</h2>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                    Last Updated {{ $selectedTemplate['updated_at'] ?? 'Recently' }}
                                </p>
                            </div>

                            <div class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-600">
                                {{ $selectedTemplate['document_size'] }}
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Orientation</div>
                                <div class="mt-1 truncate text-sm font-semibold text-slate-900">{{ ucfirst($selectedTemplate['orientation']) }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Fields</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ count($fields) }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Filled</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900" x-text="filledCount + ' / ' + totalCount"></div>
                            </div>
                        </div>
                    </section>

                    {{-- Form inputs, grouped, paginated --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Form Inputs</p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-slate-900" x-text="groups[activeGroupIndex] ? groups[activeGroupIndex].replace(/_/g, ' ').replace(/\b\w+/g, (w, i) => i > 0 && ['to','of','the'].includes(w.toLowerCase()) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()) : 'Fields'"></h3>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500" x-show="groups.length">
                                <span x-text="activeGroupIndex + 1"></span> / <span x-text="groups.length"></span>
                            </span>
                        </div>

                        <div class="mt-4">
                            @forelse($groupedFields as $groupName => $groupFields)
                                <div x-show="activeGroupIndex === {{ $loop->index }}" x-cloak class="space-y-4">
                                    @foreach($groupFields as $field)
                                        <div class="space-y-1.5">
                                            <div class="flex min-w-0 items-center justify-between gap-3">
                                                <label class="block min-w-0 flex-1 truncate text-sm font-medium text-slate-700" title="{{ $this->formatTitle($field['name']) }}">
                                                    {{ $this->formatTitle($field['name']) }}
                                                </label>
                                                @if($field['required'])
                                                    <span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-rose-500">Required</span>
                                                @elseif($field['source_type'] === 'system')
                                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">System</span>
                                                @endif
                                            </div>

                                            @php
                                                $disabledField = $field['source_type'] === 'system' || ($field['type'] === 'date' && $field['date_mode'] === 'current');
                                                $formattedTitlePlaceholder = 'Enter ' . $this->formatTitle($field['label'] ?? $field['name']);
                                            @endphp

                                            {{-- text-base on mobile prevents iOS from zooming the page on focus --}}
                                            @if($field['type'] === 'paragraph')
                                                <textarea
                                                    x-model="values[{{ Illuminate\Support\Js::from($field['name']) }}]"
                                                    @focus="setActiveField({{ Illuminate\Support\Js::from($field['name']) }})"
                                                    @blur="clearActiveField()"
                                                    rows="{{ max((int) ($field['max_lines'] ?? 4), 3) }}"
                                                    maxlength="{{ $field['max_length'] ?: '' }}"
                                                    placeholder="{{ $field['placeholder'] ?: $formattedTitlePlaceholder }}"
                                                    @disabled($disabledField)
                                                    class="w-full rounded-xl border px-4 py-3 text-base outline-none transition sm:text-sm"
                                                    :class="inputClasses({{ Illuminate\Support\Js::from($field['name']) }}, {{ $disabledField ? 'true' : 'false' }})"
                                                ></textarea>
                                            @else
                                                <input
                                                    x-model="values[{{ Illuminate\Support\Js::from($field['name']) }}]"
                                                    @focus="setActiveField({{ Illuminate\Support\Js::from($field['name']) }})"
                                                    @blur="clearActiveField()"
                                                    type="{{ $disabledField && $field['type'] === 'date' ? 'text' : ($field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text')) }}"
                                                    maxlength="{{ $field['type'] === 'text' && $field['max_length'] ? $field['max_length'] : '' }}"
                                                    placeholder="{{ $field['type'] === 'date' ? '' : ($field['placeholder'] ?: $formattedTitlePlaceholder) }}"
                                                    @disabled($disabledField)
                                                    class="w-full rounded-xl border px-4 py-3 text-base outline-none transition sm:text-sm"
                                                    :class="inputClasses({{ Illuminate\Support\Js::from($field['name']) }}, {{ $disabledField ? 'true' : 'false' }})"
                                                >
                                            @endif

                                            @error('formValues.' . $field['name'])
                                                <p class="text-sm text-rose-500">{{ $message }}</p>
                                            @enderror

                                            <template x-if="errorModal.fields[{{ Illuminate\Support\Js::from($field['name']) }}]">
                                                <p class="flex items-center gap-1 text-xs font-medium text-rose-500">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                        <path stroke-linecap="round" d="M12 8v5"></path>
                                                        <path stroke-linecap="round" d="M12 16h.01"></path>
                                                    </svg>
                                                    This Field Needs Your Attention.
                                                </p>
                                            </template>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                                    This Template Does not Contain Any Fields Yet.
                                </div>
                            @endforelse
                        </div>

                        @if($groupedFields->count() > 1)
                            <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                                <button type="button" @click="prevGroup()" :disabled="activeGroupIndex === 0"
                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                                    </svg>
                                    Back
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <template x-for="(g, i) in groups" :key="i">
                                        <span class="h-1.5 rounded-full transition-all" :class="activeGroupIndex === i ? 'w-5 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"></span>
                                    </template>
                                </div>

                                <button type="button" @click="nextGroup()" :disabled="activeGroupIndex === groups.length - 1"
                                    class="inline-flex items-center gap-1 rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#24499A] disabled:cursor-not-allowed disabled:opacity-40">
                                    Next
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </section>
                </aside>

                {{-- Preview --}}
                <section class="min-h-[50vh] bg-white xl:min-h-[75vh]" :class="mobileTab === 'preview' ? '' : 'hidden xl:block'">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-900">Document Preview</h3>
                            <p class="mt-1 hidden text-sm text-slate-500 sm:block">
                                The page below keeps the saved paper size, exact field positions, and text styling from the template editor.
                            </p>
                        </div>
                        <div class="shrink-0 self-start rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium uppercase tracking-wider text-slate-500 sm:self-auto">
                            {{ $selectedTemplate['document_size'] }} / {{ ucfirst($selectedTemplate['orientation']) }}
                        </div>
                    </div>

                    <div class="-mx-4 overflow-x-auto bg-white px-4 pb-4 sm:mx-0 sm:px-0 sm:pb-0">
                        <div
                            x-ref="previewFrame"
                            class="mx-auto flex max-h-[65vh] w-full items-start justify-center overflow-hidden xl:max-h-none xl:w-auto xl:justify-start xl:overflow-visible"
                            style="touch-action: pinch-zoom;"
                        >
                            <div :style="`width: ${canvasWidth * previewScale}px; height: ${canvasHeight * previewScale}px;`">
                                <div
                                    class="relative origin-top-left overflow-visible bg-white shadow-sm ring-1 ring-slate-200"
                                    :style="`width: ${canvasWidth}px; height: ${canvasHeight}px; transform: scale(${previewScale});`"
                                >
                                    <img
                                        src="{{ $selectedTemplate['image_url'] }}"
                                        alt="{{ $this->formatTitle($selectedTemplate['name']) }}"
                                        class="pointer-events-none absolute inset-0 h-full w-full select-none"
                                    >

                                    @foreach($fields as $field)
                                        <div
                                            class="absolute rounded-md transition-all duration-150"
                                            x-bind:style="fieldBoxStyle(@js($field))"
                                            :class="activeField === {{ Illuminate\Support\Js::from($field['name']) }} ? 'ring-2 ring-[#2A57B4] ring-offset-2 z-10' : (errorModal.fields[{{ Illuminate\Support\Js::from($field['name']) }}] ? 'ring-2 ring-rose-400 ring-offset-2 z-10' : '')"
                                        >
                                            <div
                                                class="pointer-events-none w-full px-2 py-1"
                                                x-bind:style="fieldTextStyle(@js($field))"
                                                x-text="displayValue(@js($field))"
                                            ></div>

                                            <template x-if="activeField === {{ Illuminate\Support\Js::from($field['name']) }}">
                                                <div class="pointer-events-none absolute top-1/2 -left-2 -translate-x-full -translate-y-1/2">
                                                    <div class="h-0 w-0 border-y-[6px] border-y-transparent border-r-[8px] border-r-[#2A57B4]"></div>
                                                </div>
                                            </template>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-xs text-slate-400 xl:hidden">Pinch to zoom in for detail</p>
                </section>
            </div>

            {{-- Instructions modal (bottom sheet on mobile) --}}
            <template x-teleport="body">
                <div
                    x-show="showInstructions"
                    x-cloak
                    x-transition.opacity
                    x-teleport-owner="instructions-modal"
                    class="fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-4"
                    style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);"
                    @click.self="showInstructions = false"
                    @keydown.escape.window="showInstructions = false"
                >
                    <div
                        x-show="showInstructions"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-2xl"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-gradient-to-br from-[#2A57B4] to-[#1E4090] px-5 py-4 text-white sm:px-6 sm:py-5">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path stroke-linecap="round" d="M12 11v5"></path>
                                        <path stroke-linecap="round" d="M12 8h.01"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold">How to Fill out This Form</h3>
                                    <p class="mt-0.5 text-xs text-blue-100">
                                        {{ count($instructions) }} Step{{ count($instructions) === 1 ? '' : 's' }} to Follow
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showInstructions = false" aria-label="Close" class="shrink-0 rounded-full p-1.5 text-blue-100 transition hover:bg-white/15 hover:text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-y-auto px-5 py-5 sm:px-6">
                            @forelse($instructions as $instruction)
                                <div class="relative flex gap-4 pb-5 last:pb-0">
                                    @if(!$loop->last)
                                        <span class="absolute left-[13px] top-7 h-full w-px bg-slate-200"></span>
                                    @endif
                                    <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-xs font-semibold text-white ring-4 ring-white">
                                        {{ $instruction['step_number'] }}
                                    </div>
                                    <p class="pt-0.5 text-sm leading-6 text-slate-600">{{ $instruction['description'] }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No Extra Instructions Were Added for This Form.</p>
                            @endforelse
                        </div>

                        <div class="flex justify-end border-t border-slate-100 px-5 py-4 sm:px-6">
                            <button type="button" @click="showInstructions = false"
                                class="w-full rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#24499A] sm:w-auto">
                                Got it, Thanks
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Error modal --}}
            <template x-teleport="body">
                <div
                    x-show="errorModal.open"
                    x-cloak
                    x-transition.opacity
                    x-teleport-owner="error-modal"
                    class="fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-4"
                    style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);"
                    @click.self="errorModal.open = false"
                    @keydown.escape.window="errorModal.open = false"
                >
                    <div
                        x-show="errorModal.open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        class="w-full max-w-md rounded-t-2xl border border-rose-100 bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-slate-900">We Couldn't Save This</h3>
                                <p class="mt-1 text-sm text-slate-600" x-text="errorModal.message"></p>
                            </div>
                        </div>
                        <div class="mt-5 flex justify-end">
                            <button type="button" @click="errorModal.open = false"
                                class="w-full rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#24499A] sm:w-auto">
                                Got it
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    @else

        {{-- ============================================================
            TEMPLATE PICKER — grid / list applies to BOTH sections
        ============================================================= --}}
        <div class="mx-auto max-w-6xl space-y-6">

            {{-- Search + view switcher --}}
            <div class="flex items-center gap-2 sm:gap-3">

                <div class="relative min-w-0 flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class='bx bx-search text-lg'></i>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="search"
                        placeholder="Search templates…"
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-9 text-base placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:py-2 sm:text-sm"
                    />
                    @if($search)
                        <button type="button" wire:click="$set('search', '')" aria-label="Clear search"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <i class='bx bx-x text-xl'></i>
                        </button>
                    @endif
                </div>

                <div class="inline-flex shrink-0 rounded-xl border border-gray-200 bg-white p-1" role="group" aria-label="View mode">
                    <button type="button" wire:click="$set('viewMode', 'grid')" title="Grid view" aria-pressed="{{ $viewMode === 'grid' ? 'true' : 'false' }}"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs transition-colors sm:h-auto sm:py-1.5 {{ $viewMode === 'grid' ? 'bg-blue-50 font-semibold text-blue-600' : 'text-gray-500 hover:text-gray-800' }}">
                        <i class='bx bx-grid-alt text-lg sm:text-base'></i>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button type="button" wire:click="$set('viewMode', 'list')" title="List view" aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs transition-colors sm:h-auto sm:py-1.5 {{ $viewMode === 'list' ? 'bg-blue-50 font-semibold text-blue-600' : 'text-gray-500 hover:text-gray-800' }}">
                        <i class='bx bx-list-ul text-lg sm:text-base'></i>
                        <span class="hidden sm:inline">List</span>
                    </button>
                </div>
            </div>

            {{-- ============ PAPERLESS TRANSACTIONS ============ --}}
            @if(count($filteredDocuments))
                <section class="space-y-3" wire:key="paperless-section">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-400">Paperless transactions</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Fill in, submit and track everything online. You only print the finished document if you need a paper copy.</p>
                    </div>

                    @if($viewMode === 'grid')

                        <div class="grid grid-cols-1 gap-3 min-[480px]:grid-cols-2 lg:grid-cols-3" wire:key="paperless-grid" wire:loading.class="opacity-60">
                            @foreach($filteredDocuments as $doc)
                                <a href="{{ route('student.transactions.new', $doc->key()) }}" wire:navigate wire:key="doc-grid-{{ $doc->key() }}"
                                    class="group flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                                    <span class="self-start rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#2A57B4]">Paperless</span>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $doc->name() }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $doc->description() }}</p>
                                    <p class="mt-auto pt-3 text-xs font-semibold text-[#2A57B4]">Start &rarr;</p>
                                </a>
                            @endforeach
                        </div>

                    @else

                        <div class="flex flex-col gap-2.5" wire:key="paperless-list" wire:loading.class="opacity-60">
                            @foreach($filteredDocuments as $doc)
                                <a href="{{ route('student.transactions.new', $doc->key()) }}" wire:navigate wire:key="doc-list-{{ $doc->key() }}"
                                    class="group flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#2A57B4]">
                                        <i class='bx bx-edit text-xl'></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-900 group-hover:text-[#2A57B4]">{{ $doc->name() }}</p>
                                            <span class="hidden shrink-0 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#2A57B4] sm:inline">Paperless</span>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $doc->description() }}</p>
                                    </div>
                                    <i class='bx bx-chevron-right shrink-0 text-xl text-slate-300 group-hover:text-[#2A57B4]'></i>
                                </a>
                            @endforeach
                        </div>

                    @endif
                </section>
            @endif

            {{-- ============ PAPER-BASED TEMPLATES ============ --}}
            <section class="space-y-3" wire:key="paper-section">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-400">Paper-based templates</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Forms you print and bring to the VPSD office.</p>
                </div>

                @if($viewMode === 'grid')

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4" wire:key="template-grid" wire:loading.class="opacity-60">
                        @forelse($filteredTemplates as $template)
                            <a
                                wire:key="grid-card-{{ $template['template_id'] }}"
                                href="{{ route('student.new-transaction', ['template' => $template['template_id']]) }}"
                                wire:navigate
                                class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition-all duration-150 hover:border-blue-400 hover:shadow-md"
                            >
                                {{-- Same aspect ratio whether or not a preview exists, so cards line up --}}
                                <div class="relative w-full shrink-0 overflow-hidden border-b border-gray-200 bg-gray-100" style="padding-top: 129%;">
                                    @if(!empty($template['preview_url']))
                                        <img src="{{ $template['preview_url'] }}" alt="{{ $this->formatTitle($template['name']) }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-top">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                            <i class='bx bx-file text-4xl'></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 p-3">
                                    <p class="truncate text-xs font-semibold text-gray-800 group-hover:text-blue-600 sm:text-sm" title="{{ Str::title(str_replace('_', ' ', $template['name'])) }}">
                                        {{ Str::title(str_replace('_', ' ', $template['name'])) }}
                                    </p>
                                    <p class="mt-0.5 truncate text-[11px] text-gray-400">
                                        {{ $template['document_size'] ?? 'Standard Document' }}
                                    </p>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full py-16 text-center text-gray-400">
                                <p class="text-sm">No templates found{{ $search ? ' matching "' . $search . '"' : '' }}.</p>
                            </div>
                        @endforelse
                    </div>

                @else

                    <div class="flex flex-col gap-2.5" wire:key="template-list" wire:loading.class="opacity-60">
                        @forelse($filteredTemplates as $template)
                            <a
                                wire:key="list-card-{{ $template['template_id'] }}"
                                href="{{ route('student.new-transaction', ['template' => $template['template_id']]) }}"
                                wire:navigate
                                class="group flex w-full items-center gap-3 rounded-xl border border-gray-200 bg-white p-3.5 transition-all duration-150 hover:border-blue-400 hover:shadow-md"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <i class='bx bx-file text-xl'></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-800 group-hover:text-blue-600">
                                        {{ Str::title(str_replace('_', ' ', $template['name'])) }}
                                    </p>
                                    <p class="mt-0.5 truncate text-[11px] text-gray-400">
                                        {{ $template['document_size'] ?? 'Standard Document' }}
                                    </p>
                                </div>

                                <i class='bx bx-chevron-right shrink-0 text-xl text-gray-300 group-hover:text-blue-600'></i>
                            </a>
                        @empty
                            <div class="py-16 text-center text-gray-400">
                                <p class="text-sm">No templates found{{ $search ? ' matching "' . $search . '"' : '' }}.</p>
                            </div>
                        @endforelse
                    </div>

                @endif
            </section>

        </div>
    @endif
</div>