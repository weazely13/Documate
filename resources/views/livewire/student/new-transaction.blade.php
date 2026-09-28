<div>
    @if($selectedTemplate)
        @php
            $groupedFields = collect($fields)->groupBy(fn($f) => $f['group_name'] ?: 'Other');
            $groupNames = $groupedFields->keys()->values();
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
            {{-- Top Action Bar: Instructions, Back, Save PDF, Save — Icon-Only + Compact on Mobile, Full Labels from sm: Up --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-2">
                    <button
                        type="button"
                        @click="showInstructions = true"
                        title="How to Fill out This Form"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 active:scale-[0.97] sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path stroke-linecap="round" d="M12 11v5"></path>
                            <path stroke-linecap="round" d="M12 8h.01"></path>
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline">Instructions</span>
                    </button>

                    <template x-if="savedMessage">
                        <div class="inline-flex min-w-0 items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-700 sm:px-4 sm:py-2 sm:text-sm">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.5 4.5L19 7" />
                            </svg>
                            <span x-text="savedMessage" class="truncate"></span>
                        </div>
                    </template>
                </div>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-3">
                    <a
                        href="{{ route('student.new-transaction') }}"
                        wire:navigate
                        title="Back to Documents"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 active:scale-[0.97] sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline">Back to Documents</span>
                    </a>

                    <button
                        type="button"
                        @click="savePdf()"
                        :disabled="pdfLoading"
                        title="Save as PDF"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50 active:scale-[0.97] disabled:opacity-50 sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-2.5"
                    >
                        <svg x-show="!pdfLoading" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6" />
                        </svg>
                        <svg x-show="pdfLoading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline" x-text="pdfLoading ? 'Generating…' : 'Save as PDF'"></span>
                    </button>

                    <button
                        type="button"
                        @click="saveWorkspace()"
                        title="Save Workspace"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-[#2A57B4] px-3 text-sm font-medium text-white transition hover:bg-[#24499A] active:scale-[0.97] sm:h-auto sm:px-4 sm:py-2.5"
                    >
                        <svg class="h-4 w-4 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-8H7v8M7 3v5h8" />
                        </svg>
                        <span>Save<span class="hidden sm:inline"> Workspace</span></span>
                    </button>
                </div>
            </div>

            {{-- Mobile-Only: Swipe Between Form Inputs and Document Preview --}}
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-2 xl:hidden">
                <button
                    type="button"
                    @click="mobileTab = 'form'"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
                    aria-label="Show Form Inputs"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                    </svg>
                </button>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700" x-text="mobileTab === 'form' ? 'Form Inputs' : 'Document Preview'"></span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 rounded-full transition-all" :class="mobileTab === 'form' ? 'w-5 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"></span>
                        <span class="h-1.5 rounded-full transition-all" :class="mobileTab === 'preview' ? 'w-5 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"></span>
                    </span>
                </div>

                <button
                    type="button"
                    @click="mobileTab = 'preview'"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
                    aria-label="Show Document Preview"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-5 xl:grid-cols-[360px,minmax(0,1fr)]">
                <aside class="space-y-4 sm:space-y-5" :class="mobileTab === 'form' ? '' : 'hidden xl:block'">
                    <section class="rounded-[14px] border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400 sm:text-[11px]">Document Details</p>
                               <h2>{{ $this->formatTitle($selectedTemplate['name']) }}</h2>
                                <p class="mt-1.5 text-xs text-slate-500 sm:mt-2 sm:text-sm">
                                    Last Updated {{ $selectedTemplate['updated_at'] ?? 'Recently' }}
                                </p>
                            </div>

                            <div class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-600 sm:px-3 sm:py-2 sm:text-[11px]">
                                {{ $selectedTemplate['document_size'] }}
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2 text-sm sm:mt-4 sm:gap-3">
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2.5 sm:px-3 sm:py-3">
                                <div class="text-[10px] uppercase tracking-[0.16em] text-slate-500 sm:text-[11px]">Orientation</div>
                                <div class="mt-1 truncate text-sm font-medium text-slate-900">{{ ucfirst($selectedTemplate['orientation']) }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2.5 sm:px-3 sm:py-3">
                                <div class="text-[10px] uppercase tracking-[0.16em] text-slate-500 sm:text-[11px]">Fields</div>
                                <div class="mt-1 text-sm font-medium text-slate-900">{{ count($fields) }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2.5 sm:px-3 sm:py-3">
                                <div class="text-[10px] uppercase tracking-[0.16em] text-slate-500 sm:text-[11px]">Filled</div>
                                <div class="mt-1 text-sm font-medium text-slate-900" x-text="filledCount + ' / ' + totalCount"></div>
                            </div>
                        </div>
                    </section>

                    {{-- Form Inputs, Grouped, Paginated by Back/Next --}}
                    <section class="rounded-[14px] border border-slate-200 bg-white p-3.5 shadow-sm sm:p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400 sm:text-[11px]">Form Inputs</p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-slate-900 sm:text-xl" x-text="groups[activeGroupIndex] ? groups[activeGroupIndex].replace(/_/g, ' ').replace(/\b\w+/g, (w, i) => i > 0 && ['to','of','the'].includes(w.toLowerCase()) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()) : 'Fields'"></h3>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500" x-show="groups.length">
                                <span x-text="activeGroupIndex + 1"></span> / <span x-text="groups.length"></span>
                            </span>
                        </div>

                        <div class="mt-4">
                            @forelse($groupedFields as $groupName => $groupFields)
                                <div x-show="activeGroupIndex === {{ $loop->index }}" x-cloak class="space-y-4">
                                    @foreach($groupFields as $field)
                                        <div class="space-y-2">
                                            <div class="flex min-w-0 items-center justify-between gap-3">
                                                <label class="block min-w-0 flex-1 truncate text-sm font-medium text-slate-700" title="{{ $this->formatTitle($field['name']) }}">
                                                    {{ $this->formatTitle($field['name']) }}
                                                </label>
                                                @if($field['required'])
                                                    <span class="shrink-0 rounded-full bg-rose-50 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-500">Required</span>
                                                @elseif($field['source_type'] === 'system')
                                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">System</span>
                                                @endif
                                            </div>

                                            @php
                                                $disabledField = $field['source_type'] === 'system' || ($field['type'] === 'date' && $field['date_mode'] === 'current');
                                                $formattedTitlePlaceholder = 'Enter ' . $this->formatTitle($field['label'] ?? $field['name']);
                                            @endphp

                                            @if($field['type'] === 'paragraph')
                                                <textarea
                                                    x-model="values[{{ Illuminate\Support\Js::from($field['name']) }}]"
                                                    @focus="setActiveField({{ Illuminate\Support\Js::from($field['name']) }})"
                                                    @blur="clearActiveField()"
                                                    rows="{{ max((int) ($field['max_lines'] ?? 4), 3) }}"
                                                    maxlength="{{ $field['max_length'] ?: '' }}"
                                                    placeholder="{{ $field['placeholder'] ?: $formattedTitlePlaceholder }}"
                                                    @disabled($disabledField)
                                                    class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition"
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
                                                    class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition"
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
                                <button
                                    type="button"
                                    @click="prevGroup()"
                                    :disabled="activeGroupIndex === 0"
                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                                    </svg>
                                    Back
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <template x-for="(g, i) in groups" :key="i">
                                        <span
                                            class="h-1.5 rounded-full transition-all"
                                            :class="activeGroupIndex === i ? 'w-5 bg-[#2A57B4]' : 'w-1.5 bg-slate-200'"
                                        ></span>
                                    </template>
                                </div>

                                <button
                                    type="button"
                                    @click="nextGroup()"
                                    :disabled="activeGroupIndex === groups.length - 1"
                                    class="inline-flex items-center gap-1 rounded-lg bg-[#2A57B4] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#24499A] disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Next
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </section>
                </aside>

                <section class="min-h-[50vh] bg-white xl:min-h-[75vh]" :class="mobileTab === 'preview' ? '' : 'hidden xl:block'">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-900">Document Preview</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                The page below keeps the saved paper size, exact field positions, and text styling from the template editor.
                            </p>
                        </div>
                        <div class="shrink-0 self-start rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-[0.16em] text-slate-500 sm:self-auto">
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
                                        class="absolute inset-0 w-full h-full pointer-events-none select-none"
                                    >

                                    @foreach($fields as $field)
                                        <div
                                            class="absolute rounded-md transition-all duration-150"
                                            x-bind:style="fieldBoxStyle(@js($field))"
                                            :class="activeField === {{ Illuminate\Support\Js::from($field['name']) }} ? 'ring-2 ring-[#2A57B4] ring-offset-2 z-10' : (errorModal.fields[{{ Illuminate\Support\Js::from($field['name']) }}] ? 'ring-2 ring-rose-400 ring-offset-2 z-10' : '')"
                                        >
                                            <div
                                                class="w-full px-2 py-1 pointer-events-none"
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
                    <p class="mt-2 text-center text-xs text-slate-400 xl:hidden">Pinch to Zoom in for Detail</p>
                </section>
            </div>

            {{-- Instructions Modal --}}
            <template x-teleport="body">
                <div
                    x-show="showInstructions"
                    x-cloak
                    x-transition.opacity
                    x-teleport-owner="instructions-modal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                    style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);"
                    @click.self="showInstructions = false"
                    @keydown.escape.window="showInstructions = false"
                >
                    <div
                        x-show="showInstructions"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-gradient-to-br from-[#2A57B4] to-[#1E4090] px-6 py-5 text-white">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                            <button type="button" @click="showInstructions = false" class="shrink-0 rounded-full p-1.5 text-blue-100 transition hover:bg-white/15 hover:text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-y-auto px-6 py-5">
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

                        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                            <button
                                type="button"
                                @click="showInstructions = false"
                                class="rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#24499A]"
                            >
                                Got it, Thanks
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Error Modal --}}
            <template x-teleport="body">
                <div
                    x-show="errorModal.open"
                    x-cloak
                    x-transition.opacity
                    x-teleport-owner="error-modal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                    style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);"
                    @click.self="errorModal.open = false"
                    @keydown.escape.window="errorModal.open = false"
                >
                    <div
                        x-show="errorModal.open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="w-full max-w-md rounded-2xl border border-rose-100 bg-white p-6 shadow-2xl"
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
                            <button
                                type="button"
                                @click="errorModal.open = false"
                                class="rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#24499A]"
                            >
                                Got it
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @else
        <div class="mx-auto max-w-6xl space-y-8">
            <div class="space-y-6">
                <!-- Header Controls: Search Input & View Switcher (Single Line on Mobile) -->
                <div class="flex flex-row items-center justify-between gap-2 sm:gap-3">
                    
                    <!-- Search Field -->
                    <div class="relative flex-1 min-w-0">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 sm:pl-3 text-gray-400">
                            <i class='bx bx-search text-base sm:text-lg'></i>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Search Templates..."
                            class="w-full rounded-xl border border-gray-200 bg-white py-1.5 sm:py-2 pl-8 sm:pl-9 pr-7 sm:pr-8 text-xs sm:text-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        @if($search)
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600"
                            >
                                <i class='bx bx-x text-base sm:text-lg'></i>
                            </button>
                        @endif
                    </div>

                    <!-- View Mode Switcher -->
                    <div class="inline-flex shrink-0 rounded-xl border border-gray-200 bg-white p-0.5 sm:p-1">
                        <button
                            type="button"
                            wire:click="$set('viewMode', 'grid')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2 sm:px-3 py-1 sm:py-1.5 text-xs transition-colors {{ $viewMode === 'grid' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-800' }}"
                            title="Grid View"
                        >
                            <i class='bx bx-grid-alt text-base'></i>
                            <span class="hidden sm:inline">Grid</span>
                        </button>
                        <button
                            type="button"
                            wire:click="$set('viewMode', 'list')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2 sm:px-3 py-1.5 text-xs transition-colors {{ $viewMode === 'list' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-800' }}"
                            title="List View"
                        >
                            <i class='bx bx-list-ul text-base'></i>
                            <span class="hidden sm:inline">List</span>
                        </button>
                    </div>

                </div>

                <!-- Template Container -->
                <div wire:key="view-mode-container">
                    @if($viewMode === 'grid')
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6" wire:key="template-grid" wire:loading.class="opacity-60">
                            @forelse($filteredTemplates as $template)
                                <a
                                    wire:key="grid-card-{{ $template['template_id'] }}"
                                    href="{{ route('student.new-transaction', ['template' => $template['template_id']]) }}"
                                    wire:navigate
                                    class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white cursor-pointer transition-all duration-150 hover:border-blue-400 hover:shadow-md"
                                >
                                    @if(!empty($template['preview_url']))
                                        <div class="relative w-full shrink-0 overflow-hidden border-b border-gray-200 bg-gray-100" style="padding-top: 129%;">
                                            <img src="{{ $template['preview_url'] }}" alt="{{ $this->formatTitle($template['name']) }}" class="absolute inset-0 h-full w-full object-cover object-top">
                                        </div>
                                    @else
                                        <div class="flex h-32 w-full items-center justify-center bg-gray-50 text-gray-300">
                                            <i class='bx bx-file text-3xl'></i>
                                        </div>
                                    @endif

                                    <div class="flex flex-1 flex-col justify-between p-3">
                                        <div>
                                            <p class="truncate text-xs sm:text-sm font-semibold text-gray-800 group-hover:text-blue-600" title="{{ Str::title(str_replace('_', ' ', $template['name'])) }}">
                                                {{ Str::title(str_replace('_', ' ', $template['name'])) }}
                                            </p>
                                            <p class="mt-0.5 truncate text-[11px] text-gray-400">
                                                {{ $template['document_size'] ?? 'Standard Document' }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="col-span-full py-16 text-center text-gray-400">
                                    <p class="text-sm">No Templates Found Matching "{{ $search }}".</p>
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="flex flex-col space-y-3" wire:key="template-list" wire:loading.class="opacity-60">
                            @forelse($filteredTemplates as $template)
                                <a
                                    wire:key="list-card-{{ $template['template_id'] }}"
                                    href="{{ route('student.new-transaction', ['template' => $template['template_id']]) }}"
                                    wire:navigate
                                    class="group flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white p-3.5 cursor-pointer transition-all duration-150 hover:border-blue-400 hover:shadow-md"
                                >
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                            <i class='bx bx-file text-xl'></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs sm:text-sm font-semibold text-gray-800 group-hover:text-blue-600">
                                                {{ Str::title(str_replace('_', ' ', $template['name'])) }}
                                            </p>
                                            <p class="mt-0.5 truncate text-[11px] text-gray-400">
                                                {{ $template['document_size'] ?? 'Standard Document' }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="py-16 text-center text-gray-400">
                                    <p class="text-sm">No Templates Found Matching "{{ $search }}".</p>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>