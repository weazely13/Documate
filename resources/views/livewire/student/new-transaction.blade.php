@if($selectedTemplate)
    <div
        x-data="documentWorkspace({
            initialValues: @js($formValues),
            fields: @js($fields),
            systemValues: @js($this->systemPreviewValues),
            initialSavedMessage: @js($savedMessage),
            initialLastSavedAt: @js($lastSavedAt),
        })"
        class="space-y-4"
    >
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-h-[40px]">
                <template x-if="savedMessage">
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-2 text-sm text-[#2A57B4]" x-text="savedMessage"></div>
                </template>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    @click="saveWorkspace()"
                    class="rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#24499A]"
                >
                    Save workspace
                </button>

                <button
                    type="button"
                    @click="savePdf()"
                    :disabled="pdfLoading"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                >
                    <span x-show="!pdfLoading">Save as PDF</span>
                    <span x-show="pdfLoading">Generating…</span>
                </button>

                <a
                    href="{{ route('student.new-transaction') }}"
                    wire:navigate
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Back to documents
                </a>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[360px,minmax(0,1fr)]">
            <aside class="space-y-5">
                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Document details</p>
                            <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $selectedTemplate['name'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Workspace #{{ $selectedWorkspaceId }}<span x-show="lastSavedAt" x-text="lastSavedAt ? ' - Updated ' + lastSavedAt : ''"></span>
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                            {{ $selectedTemplate['document_size'] }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Orientation</div>
                            <div class="mt-1 font-medium text-slate-900">{{ ucfirst($selectedTemplate['orientation']) }}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Fields</div>
                            <div class="mt-1 font-medium text-slate-900">{{ count($fields) }}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-500">PDF</div>
                            <div class="mt-1 font-medium text-slate-900">{{ $pdfUrl ? 'Saved' : 'Draft' }}</div>
                        </div>
                    </div>

                    @if($pdfUrl)
                        <a
                            href="{{ $pdfUrl }}"
                            target="_blank"
                            class="mt-4 inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Open saved PDF
                        </a>
                    @endif
                </section>

                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Form inputs</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Every template field is listed below. System values stay locked.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        @forelse($fields as $field)
                            <div class="space-y-2">
                                <div class="flex min-w-0 items-center justify-between gap-3">
                                    <label class="block min-w-0 flex-1 truncate text-sm font-medium text-slate-700" title="{{ $field['name'] ?: $field['label'] }}">
                                        {{ $field['name'] ?: $field['label'] }}
                                    </label>
                                    @if($field['required'])
                                        <span class="rounded-full bg-rose-500/15 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-300">Required</span>
                                    @elseif($field['source_type'] === 'system')
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">System</span>
                                    @endif
                                </div>

                                @php
                                    $disabledField = $field['source_type'] === 'system' || ($field['type'] === 'date' && $field['date_mode'] === 'current');
                                @endphp

                                @if($field['type'] === 'paragraph')
                                    <textarea
                                        x-model="values['{{ $field['name'] }}']"
                                        rows="{{ max((int) ($field['max_lines'] ?? 4), 3) }}"
                                        maxlength="{{ $field['max_length'] ?: '' }}"
                                        placeholder="{{ $field['placeholder'] ?: 'Enter ' . strtolower($field['label']) }}"
                                        @disabled($disabledField)
                                        class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition {{ $disabledField ? 'border-slate-200 bg-slate-50 text-slate-500' : 'border-slate-300 bg-white text-slate-900 focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100' }}"
                                    ></textarea>
                                @else
                                    <input
                                        x-model="values['{{ $field['name'] }}']"
                                        type="{{ $disabledField && $field['type'] === 'date' ? 'text' : ($field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text')) }}"
                                        maxlength="{{ $field['type'] === 'text' && $field['max_length'] ? $field['max_length'] : '' }}"
                                        placeholder="{{ $field['type'] === 'date' ? '' : ($field['placeholder'] ?: 'Enter ' . strtolower($field['label'])) }}"
                                        @disabled($disabledField)
                                        class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition {{ $disabledField ? 'border-slate-200 bg-slate-50 text-slate-500' : 'border-slate-300 bg-white text-slate-900 focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100' }}"
                                    >
                                @endif

                                @error('formValues.' . $field['name'])
                                    <p class="text-sm text-rose-500">{{ $message }}</p>
                                @enderror

                                <p class="text-xs text-slate-400">
                                    {{ strtoupper($field['type']) }} / {{ strtoupper($field['alignment']) }} / {{ $field['font_size'] }}px
                                </p>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                                This template does not contain any fields yet.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Instructions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse($instructions as $instruction)
                            <div class="flex gap-3 rounded-xl bg-slate-50 p-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#2A57B4] text-sm font-semibold text-white">
                                    {{ $instruction['step_number'] }}
                                </div>
                                <p class="text-sm leading-6 text-slate-600">{{ $instruction['description'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No extra instructions were added for this form.</p>
                        @endforelse
                    </div>
                </section>
            </aside>

            <section class="min-h-[75vh] bg-white">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Document preview</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            The page below keeps the saved paper size, exact field positions, and text styling from the template editor.
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-[0.16em] text-slate-500">
                        {{ $selectedTemplate['document_size'] }} / {{ ucfirst($selectedTemplate['orientation']) }}
                    </div>
                </div>

                <div class="overflow-auto bg-white p-0">
                    <div
                        class="relative mx-auto overflow-hidden bg-white"
                        style="width: {{ $selectedTemplate['canvas']['width'] }}px; height: {{ $selectedTemplate['canvas']['height'] }}px;"
                    >
                        <img
                            src="{{ $selectedTemplate['image_url'] }}"
                            alt="{{ $selectedTemplate['name'] }}"
                            class="absolute inset-0 w-full h-full pointer-events-none select-none"
                        >

                        @foreach($fields as $field)
                            <div
                                class="absolute"
                                x-bind:style="fieldBoxStyle(@js($field))"
                            >
                                <div
                                    class="w-full px-2 py-1 pointer-events-none"
                                    x-bind:style="fieldTextStyle(@js($field))"
                                    x-text="displayValue(@js($field))"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        function documentWorkspace(config) {
            return {
                values: config.initialValues || {},
                fields: config.fields || [],
                systemValues: config.systemValues || {},
                savedMessage: config.initialSavedMessage || null,
                lastSavedAt: config.initialLastSavedAt || null,
                pdfLoading: false,
                init() {
                    window.addEventListener('pdf-ready', (event) => {
                        const detail = event.detail?.[0] || event.detail || {};
                        this.pdfLoading = false;
                        this.savedMessage = detail.message || 'PDF generated.';
                        this.lastSavedAt = detail.savedAt || this.lastSavedAt;

                        if (detail.url) {
                            setTimeout(() => window.open(detail.url, '_blank'), 100);
                        }
                    });

                    window.addEventListener('pdf-error', (event) => {
                        console.log('pdf-error raw detail:', event.detail); // debug
                        const detail = event.detail || {};
                        this.pdfLoading = false;
                        alert('PDF Error: ' + (detail.message || 'Unknown error'));
                    });

                    this.$wire.on('validation-error', () => {
                        this.pdfLoading = false;
                    });
                    this.fields.forEach((field) => {
                        if (field.source_type === 'system' || (field.type === 'date' && field.date_mode === 'current')) {
                            this.values[field.name] = this.displayValue(field);
                        }
                    });

                    window.addEventListener('workspace-saved', (event) => {
                        const detail = event.detail?.[0] || event.detail || {};
                        this.savedMessage = detail.message || 'Workspace saved.';
                        this.lastSavedAt = detail.savedAt || this.lastSavedAt;
                    });
                    window.addEventListener('livewire:dispatched', (event) => {
                        console.log('livewire dispatched:', event.detail);
                    });

                },
                saveWorkspace() {
                    this.$wire.call('saveWorkspace', this.values);
                },
                savePdf() {
                    if (this.pdfLoading) return;
                    this.pdfLoading = true;
                    this.$wire.call('savePdf', this.values)
                        .then(() => {
                            // pdfLoading will be reset by the event handler
                            // but set a fallback timeout in case the event never fires
                            setTimeout(() => { this.pdfLoading = false; }, 8000);
                        })
                        .catch(() => {
                            this.pdfLoading = false;
                        });
                },
                displayValue(field) {
                    if (field.source_type === 'system') {
                        return this.systemValues[field.name] || '';
                    }

                    if (field.type === 'date' && field.date_mode === 'current') {
                        const now = new Date();
                        return `${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}/${now.getFullYear()}`;
                    }

                    const raw = this.values[field.name];
                    if (raw === null || raw === undefined || raw === '') {
                        return field.placeholder || '';
                    }

                    if (field.type === 'date') {
                        const parsed = new Date(raw);
                        if (!Number.isNaN(parsed.getTime())) {
                            return `${String(parsed.getMonth() + 1).padStart(2, '0')}/${String(parsed.getDate()).padStart(2, '0')}/${parsed.getFullYear()}`;
                        }
                    }

                    return raw;
                },
                fieldTextStyle(field) {
                    const value = this.displayValue(field);
                    const fontSize = this.fitFontSize(field, value);
                    const lineHeight = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const maxHeight = maxLines > 0 ? (maxLines * fontSize * lineHeight) + 8 : null;
                    const isParagraph = field.type === 'paragraph';

                    return `
                        font-family: '${field.font_family}', sans-serif;
                        font-size: ${fontSize}px;
                        font-weight: ${field.font_weight};
                        color: ${field.text_color};
                        text-align: ${field.alignment};
                        line-height: ${lineHeight};
                        letter-spacing: ${field.letter_spacing}px;
                        display: block;
                        width: 100%;
                        overflow: hidden;
                        overflow-wrap: ${isParagraph ? 'break-word' : 'normal'};
                        word-break: ${isParagraph ? 'break-word' : 'normal'};
                        white-space: ${isParagraph ? 'pre-wrap' : 'nowrap'};
                        text-overflow: ${isParagraph ? 'clip' : 'ellipsis'};
                        max-height: ${maxHeight ? `${maxHeight}px` : 'none'};
                    `;
                },
                fieldBoxStyle(field) {
                    const baseHeight = parseFloat(field.height || 0);
                    const fontSize = this.fitFontSize(field, this.displayValue(field));
                    const lineHeight = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const computedHeight = maxLines > 0
                        ? Math.max(baseHeight, (maxLines * fontSize * lineHeight) + 8)
                        : baseHeight;

                    return `
                        left: ${field.x}px;
                        top: ${field.y}px;
                        width: ${field.width}px;
                        min-height: ${computedHeight}px;
                        height: ${computedHeight}px;
                        box-sizing: border-box;
                        border: 1px solid transparent;
                        background: transparent;
                        overflow: hidden;
                    `;
                },
                fitFontSize(field, value) {
                    const baseSize = parseFloat(field.font_size || 12);
                    const minSize = 6;

                    if (field.type === 'paragraph') {
                        return baseSize;
                    }

                    const width = Math.max((field.width || 0) - 16, 10);
                    const lineHeightRatio = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const heightFromLines = maxLines > 0 ? (maxLines * baseSize * lineHeightRatio) : 0;
                    const height = Math.max(Math.max((field.height || 0) - 8, heightFromLines), 10);
                    const text = `${value || ''}`.trim();

                    if (!text) {
                        return baseSize;
                    }

                    for (let size = baseSize; size >= minSize; size -= 0.5) {
                        if (this.textFits(field, text, width, height, size)) {
                            return size;
                        }
                    }

                    return minSize;
                },
                textFits(field, text, width, height, fontSize) {
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    const weight = field.font_weight || 'normal';
                    const family = field.font_family || 'Arial';
                    const lineHeightRatio = parseFloat(field.line_height || 1.3);
                    const letterSpacing = parseFloat(field.letter_spacing || 0);
                    const maxLines = parseInt(field.max_lines || 0, 10);

                    context.font = `${weight} ${fontSize}px ${family}`;

                    const paragraphs = text.split(/\r\n|\r|\n/);
                    const lines = [];

                    for (const paragraph of paragraphs) {
                        const words = paragraph.split(/\s+/).filter(Boolean);

                        if (!words.length) {
                            lines.push('');
                            continue;
                        }

                        let current = '';
                        for (const word of words) {
                            const candidate = current ? `${current} ${word}` : word;
                            const candidateWidth = context.measureText(candidate).width + (candidate.length * letterSpacing);

                            if (candidateWidth <= width || !current) {
                                current = candidate;
                                continue;
                            }

                            lines.push(current);
                            current = word;
                        }

                        if (current) {
                            lines.push(current);
                        }
                    }

                    const totalHeight = lines.length * fontSize * lineHeightRatio;
                    if (totalHeight > height) {
                        return false;
                    }

                    if (maxLines > 0 && lines.length > maxLines) {
                        return false;
                    }

                    return lines.every((line) => {
                        const measured = context.measureText(line).width + (line.length * letterSpacing);
                        return measured <= width;
                    });
                }
            };
        }
    </script>
@else
    <div x-data="{ view: 'grid' }" class="mx-auto max-w-6xl space-y-8">
        <section class="space-y-5">
            <div>
                <h2 class="text-[2rem] font-semibold tracking-tight text-slate-900">Transactions Summary</h2>
            </div>

            <div class="grid gap-4 xl:grid-cols-[repeat(3,150px),minmax(0,1fr)]">
                <div class="rounded-[16px] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-center text-6xl font-semibold leading-none text-[#2A57B4]">{{ $this->dashboardStats['pending_transactions'] }}</div>
                    <p class="mt-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Pending</p>
                </div>

                <div class="rounded-[16px] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-center text-6xl font-semibold leading-none text-[#2A57B4]">{{ $this->dashboardStats['completed_transactions'] }}</div>
                    <p class="mt-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Completed</p>
                </div>

                <div class="rounded-[16px] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-center text-6xl font-semibold leading-none text-[#2A57B4]">{{ $this->dashboardStats['upcoming_appointments'] }}</div>
                    <p class="mt-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Upcoming Appointments</p>
                </div>

                <div class="rounded-[16px] bg-[#2A57B4] px-5 py-4 text-white shadow-[0_16px_34px_rgba(42,87,180,0.22)]">
                    <p class="text-xs font-medium text-blue-100">Recent Transaction :</p>
                    @if($this->dashboardStats['recent_transaction'])
                        <div class="mt-2 truncate text-[2rem] font-semibold leading-tight">
                            {{ $this->dashboardStats['recent_transaction']['name'] }}
                        </div>
                        <p class="mt-1 text-sm text-blue-50">{{ $this->dashboardStats['recent_transaction']['status'] }}</p>
                        <p class="mt-1 text-xs text-blue-100">{{ $this->dashboardStats['recent_transaction']['updated_at'] }}</p>
                    @else
                        <div class="mt-3 text-2xl font-semibold leading-tight">No recent transaction</div>
                        <p class="mt-1 text-sm text-blue-100">Start a document request below.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="space-y-6">
            <div class="text-center">
                <h2 class="text-[2.05rem] font-semibold tracking-tight text-slate-900">Start a New Document Transaction</h2>
                <p class="mt-2 text-sm text-slate-500">Select a document type and download your pre-filled form</p>
            </div>

            <div class="mx-auto max-w-4xl">
                <label class="relative block">
                    <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input
                        wire:model.live.debounce.250ms="search"
                        type="text"
                        placeholder="Search"
                        class="w-full rounded-full border border-transparent bg-[#F2F2F2] py-4 pl-14 pr-5 text-sm text-slate-900 outline-none transition focus:border-[#2A57B4] focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                </label>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="view = 'grid'"
                        class="inline-flex items-center gap-2 text-sm text-slate-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="4" width="18" height="4"></rect>
                            <rect x="3" y="10" width="18" height="4"></rect>
                            <rect x="3" y="16" width="18" height="4"></rect>
                        </svg>
                        <span class="font-medium">Grid View</span>
                    </button>

                    <button
                        type="button"
                        @click="view = 'list'"
                        class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M8 6h13"></path>
                            <path d="M8 12h13"></path>
                            <path d="M8 18h13"></path>
                            <circle cx="4" cy="6" r="1"></circle>
                            <circle cx="4" cy="12" r="1"></circle>
                            <circle cx="4" cy="18" r="1"></circle>
                        </svg>
                        <span>List View</span>
                    </button>
                </div>

                <p class="text-sm text-slate-500">{{ count($templates) }} active document{{ count($templates) === 1 ? '' : 's' }}</p>
            </div>

            <div class="mt-2" :class="view === 'grid' ? 'grid gap-8 md:grid-cols-2 xl:grid-cols-3' : 'space-y-4'">
                @forelse($templates as $template)
                    <a
                        href="{{ route('student.new-transaction', ['template' => $template['template_id']]) }}"
                        wire:navigate
                        class="group overflow-hidden rounded-[10px] border border-slate-300 bg-white text-left shadow-sm transition hover:-translate-y-1 hover:border-[#2A57B4] hover:shadow-[0_14px_30px_rgba(42,87,180,0.12)]"
                        :class="view === 'list' ? 'flex w-full items-stretch' : 'block'"
                    >
                        <div :class="view === 'list' ? 'w-64 shrink-0 border-r border-slate-300 bg-white' : 'border-b border-slate-300 bg-white'">
                            @if($template['preview_url'])
                                <img
                                    src="{{ $template['preview_url'] }}"
                                    alt="{{ $template['name'] }}"
                                    class="h-72 w-full object-contain bg-white transition duration-300 group-hover:scale-[1.01]"
                                >
                            @else
                                <div class="flex h-72 items-center justify-center bg-white text-sm font-medium text-slate-400">
                                    No preview available
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col border-t border-slate-300 p-4" :class="view === 'list' ? 'border-t-0' : ''">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-slate-900">{{ $template['name'] }}</h3>
                                    <p class="mt-1 truncate text-xs text-slate-400">
                                        {{ $template['document_size'] }} - {{ ucfirst($template['orientation']) }}
                                    </p>
                                </div>

                                <span class="rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#2A57B4]">
                                    Public
                                </span>
                            </div>

                            <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">{{ $template['description'] }}</p>

                            <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ number_format($template['access_count']) }} accessed</span>
                                <span>Last Updated -</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-[10px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-600">
                        No active documents matched your search.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endif
