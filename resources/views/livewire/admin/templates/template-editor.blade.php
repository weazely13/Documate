<div class="flex flex-col h-screen overflow-hidden">

    <!-- 🔷 TOPBAR -->
    <div class="h-12 bg-white border-b flex items-center justify-between px-5">
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/favicon.png') }}" class="w-5 h-5">
            <span class="font-semibold text-sm">DOCUMATE</span>
        </div>

        <div class="flex gap-2 items-center">
            {{-- Only ONE status shown at a time --}}
            <span x-show="saveStatus === 'saving'" x-cloak
                class="text-[10px] text-gray-400 flex items-center gap-1">
                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Saving...
            </span>
            <span x-show="saveStatus === 'saved'" x-cloak class="text-[10px] text-green-500">✓ Saved</span>

            <button onclick="window.location.href='/admin/templates'"
                class="px-3 py-1 text-xs border rounded-md hover:bg-gray-100 transition">
                ← Back
            </button>
            <button
                x-data="{ status: '{{ $template->status }}' }"
                x-on:status-updated.window="status = $event.detail.status"
                class="px-3 py-1 text-xs rounded-md transition font-medium border"
                :class="status === 'active'
                    ? 'bg-green-600 text-white hover:bg-red-600 hover:border-red-600 border-green-600'
                    : 'bg-white text-gray-600 border-gray-300 hover:bg-green-50 hover:border-green-500'"
                @click="$wire.toggleStatus()"
                :title="status === 'active' ? 'Click to make Private' : 'Click to make Public'">
                <span x-text="status === 'active' ? '🌐 Public' : '🔒 Private'"></span>
            </button>
        </div>
    </div>

    <!-- 🔷 MAIN -->
    <div class="flex flex-1 overflow-hidden"
        x-data="{
            zoom: 100, panX: 0, panY: 0,
            activeTab: 'field',
            selectedField: null,
            selectedFieldData: null,
            selectedFieldState: { x: 0, y: 0, width: 0, height: 0 },
            selectedConstraints: { maxLength: null, maxLines: null, required: false, dateMode: 'current' },
            saveStatus: '',
            autoSaveTimeout: null,
            previewTick: 0,
            typographyTick: 0,
            constraintTick: 0,
            imagePreview: window.imageState.preview || @js($version->image_path ? '/storage/'.$version->image_path : ''),

            init() {
                window.Livewire.on('imageUpdated', (url) => {
                    let finalUrl = url + '?' + Date.now();
                    this.imagePreview = finalUrl;
                    window.imageState.preview = finalUrl;
                });
                window.Livewire.on('imageUploaded', () => {
                    location.reload();
                });
                window.Livewire.on('autoSaved', () => {
                    this.saveStatus = 'saved';
                    setTimeout(() => {
                        if (this.saveStatus === 'saved') this.saveStatus = '';
                    }, 2000);
                });
                // Handle field added without re-render
                window.Livewire.on('fieldAdded', ({ field }) => {
                    $wire.fields.push(field);
                });

                // Handle field deleted without re-render
                window.Livewire.on('fieldDeleted', ({ id }) => {
                    let root = Alpine.closestData(document.querySelector('[data-canvas]'));
                    if (!root) return;
                    let wire = root.$wire;
                    let idx = wire.fields.findIndex(f => String(f.id) === String(id));
                    if (idx !== -1) wire.fields.splice(idx, 1);
                });

            },
            setConstraint(key, value) {
                let id = this.selectedFieldData?.id;
                if (!id) return;
                if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                window.fieldConstraints[id][key] = value;
                this.selectedConstraints[key] = value;  // ← triggers Alpine reactivity
                this.previewTick++;
                this.constraintTick++;
                this.triggerSave();
            },

            get selectedSourceType() {
                return this.selectedFieldData?.source_type ?? 'input';
            },

            getTypo() {
                let id = this.selectedFieldData?.id;
                if (!id) return defaultTypography();
                return window.typographyState[id] || defaultTypography();
            },
            setTypo(key, value) {
                let id = this.selectedFieldData?.id;
                if (!id) return;
                if (!window.typographyState[id]) window.typographyState[id] = defaultTypography();
                window.typographyState[id][key] = value;
                this.typographyTick++;
                this.triggerSave();
            },
            triggerSave() {

                let wire = this.$wire;

                let mergedFields = wire.fields.map(f => {
                    let meta = window.fieldMeta[f.id] || {};
                    return { ...f, ...meta };
                });

                // Deep-copy all mutable state at this moment
                let snapshotFields      = JSON.parse(JSON.stringify(mergedFields));
                let snapshotPositions   = JSON.parse(JSON.stringify(window.fieldState));
                let snapshotTypography  = JSON.parse(JSON.stringify(window.typographyState));
                let snapshotConstraints = JSON.parse(JSON.stringify(window.fieldConstraints));
                let snapshotName        = wire.templateName;

                clearTimeout(this.autoSaveTimeout);
                this.saveStatus = 'saving';

                // The timeout only fires the call — it uses the snapshot, not live state
                this.autoSaveTimeout = setTimeout(() => {
                    wire.call(
                        'saveFields',
                        snapshotPositions,
                        snapshotFields,
                        snapshotName,
                        snapshotTypography,
                        snapshotConstraints
                    );
                }, 800);
            },
            scheduleAutosave() {
                this.triggerSave();
            },
            handleImageUpload(event) {
                let file = event.target.files[0];
                if (!file) return;
                this.saveStatus = 'saving';
                this.$wire.upload('image', file);
                this.$refs.imageInput.value = null;
            },
            syncFieldToList(id) {
                let idx = $wire.fields.findIndex(f => String(f.id) === String(id));
                if (idx !== -1 && this.selectedFieldData) {
                    $wire.fields.splice(idx, 1, { ...$wire.fields[idx], ...this.selectedFieldData });
                }
            },
        }">

        <!-- 🟦 LEFT PANEL -->
        <div class="w-80 bg-white border-r px-4 py-4 overflow-y-auto text-xs">

            <div>
                <!-- UPLOAD (CARD) -->
                <div class="border rounded-lg p-4 text-center mb-3 shadow-sm">
                    <div class="h-40 border-2 border-dashed flex flex-col items-center justify-center text-gray-500 text-xs rounded-md cursor-pointer"
                        @click="$refs.imageInput.click()">
                        <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16l4-4a3 3 0 014 0l4 4m-4-4l1-1a3 3 0 014 0l2 2M3 16h18"/>
                        </svg>
                        Click to upload
                        <span class="text-[10px] mt-2 text-gray-400">JPG, PNG supported</span>
                    </div>
                    <button class="w-full mt-2 text-xs bg-gray-200 py-1 rounded"
                        @click="$refs.imageInput.click()">
                        Replace Image
                    </button>
                    <p class="text-[11px] text-yellow-600 mt-2 flex items-center justify-center gap-1">
                        ⚠ Use 300 DPI resolution for best quality
                    </p>
                </div>
                <input type="file" accept="image/*" class="hidden" x-ref="imageInput"
                    @change="handleImageUpload">

                <!-- VIEW -->
                <label class="font-semibold text-xs">V I E W</label>
                <div class="mt-2 space-y-2">
                    <div>
                        <label class="text-[11px] text-gray-500">Zoom</label>
                        <input type="range" min="50" max="150" x-model="zoom" class="w-full">
                    </div>
                    <div>
                        <label class="text-[11px] text-gray-500">Horizontal</label>
                        <input type="range" min="-800" max="800" x-model="panX" class="w-full">
                    </div>
                    <div>
                        <label class="text-[11px] text-gray-500">Vertical</label>
                        <input type="range" min="-800" max="800" x-model="panY" class="w-full">
                    </div>
                </div>

                <hr class="my-4">

                <!-- TEMPLATE NAME -->
                <label class="font-semibold text-xs">Template Name</label>
                <input type="text"
                    class="w-full border rounded-md px-2 py-1 mt-2"
                    placeholder="Template name"
                    x-model="$wire.templateName"
                    @input="scheduleAutosave()">

                <hr class="my-4">

                <!-- FIELD POSITION -->
                <label class="font-semibold text-xs">Field Position</label>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div>
                        <label class="text-[11px] text-gray-500">X</label>
                        <input type="number"
                            class="w-full border px-2 py-1 rounded"
                            x-model="selectedFieldState.x"
                            @input="
                                let id = selectedFieldData.id;
                                let value = +selectedFieldState.x;
                                window.fieldState[id].x = value;
                                if(window.fieldInstances[id]) window.fieldInstances[id].localX = value;
                                scheduleAutosave();
                            ">
                    </div>
                    <div>
                        <label class="text-[11px] text-gray-500">Y</label>
                        <input type="number" class="w-full border px-2 py-1 rounded"
                            x-model="selectedFieldState.y"
                            @input="
                                let id = selectedFieldData.id;
                                let value = +$event.target.value;
                                window.fieldState[id].y = value;
                                if(window.fieldInstances[id]) window.fieldInstances[id].localY = value;
                                scheduleAutosave();
                            ">
                    </div>
                </div>

                <!-- FIELD SIZE -->
                <label class="font-semibold text-xs mt-4 block">Field Size</label>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div>
                        <label class="text-[11px] text-gray-500">Width</label>
                        <input type="number" class="w-full border px-2 py-1 rounded"
                            x-model="selectedFieldState.width"
                            @input="
                                let id = selectedFieldData.id;
                                let value = +$event.target.value;
                                window.fieldState[id].width = value;
                                if(window.fieldInstances[id]) window.fieldInstances[id].localW = value;
                                scheduleAutosave();
                            ">
                    </div>
                    <div>
                        <label class="text-[11px] text-gray-500">Height</label>
                        <input type="number" class="w-full border px-2 py-1 rounded"
                            x-model="selectedFieldState.height"
                            @input="
                                let id = selectedFieldData.id;
                                let value = +$event.target.value;
                                window.fieldState[id].height = value;
                                if(window.fieldInstances[id]) window.fieldInstances[id].localH = value;
                                scheduleAutosave();
                            ">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- TOOLBOX -->
            <label class="font-semibold text-xs">T O O L B O X</label>
            <div class="grid grid-cols-2 gap-3 mt-2 mb-6 text-[11px] text-center">
                <div class="border p-3 cursor-pointer hover:bg-blue-50 rounded"
                     @click="$wire.addField('text')">
                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor">
                        <path d="M4 6h16M8 6v12"/>
                    </svg>
                    Text
                </div>
                <div class="border p-3 cursor-pointer hover:bg-blue-50 rounded"
                    @click="$wire.addField('number')">
                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor">
                        <path d="M5 10h14M5 14h14"/>
                    </svg>
                    Number
                </div>
                <div class="border p-3 cursor-pointer hover:bg-blue-50 rounded"
                    @click="$wire.addField('paragraph')">
                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor">
                        <path d="M4 6h16M4 10h12M4 14h16"/>
                    </svg>
                    Paragraph
                </div>
                <div class="border p-3 cursor-pointer hover:bg-blue-50 rounded"
                    @click="$wire.addField('date')">
                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor">
                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                        <path d="M16 3v4M8 3v4"/>
                    </svg>
                    Date
                </div>
            </div>

        </div>


        <!-- 🟨 CANVAS -->
        <div class="relative flex-1 bg-gray-100 flex items-center justify-center overflow-hidden" data-canvas 
        @field-drag-end.window="scheduleAutosave()">
            <div class="max-w-[900px] max-h-[90%]">
                <div
                    :style="'transform: translate(' + panX + 'px,' + panY + 'px) scale(' + (zoom/100) + ')'"
                    style="transform-origin:center;">

                    <div class="relative bg-white shadow-md" data-editor-canvas
                        style="
                            width: {{ $canvasDimensions['width'] }}px;
                            height: {{ $canvasDimensions['height'] }}px;
                        ">

                        <img x-show="imagePreview"
                            :src="imagePreview"
                            class="absolute inset-0 w-full h-full pointer-events-none">

                        <div class="absolute inset-0 z-10">
                            <template x-for="field in $wire.fields" :key="field.id">
                                <div
                                    x-data="fieldInteraction({ index: field.id, field: field })"
                                    x-init="
                                        localX = field.x ?? 100;
                                        localY = field.y ?? 100;
                                        localW = field.width ?? 150;
                                        localH = field.height ?? 50;
                                        constraintTick: 0,
                                        field.required,
                                        window.fieldState[field.id] = { x: localX, y: localY, width: localW, height: localH };
                                        window.fieldInstances[field.id] = $data;
                                        if (!window.typographyState[field.id]) {
                                            window.typographyState[field.id] = {
                                                fontFamily: field.font_family || 'Arial',
                                                fontWeight: field.font_weight === 'bold' ? 'bold' : 'normal',
                                                fontStyle: field.font_weight === 'italic' ? 'italic' : 'normal',
                                                fontSize: field.font_size || 12,
                                                color: field.text_color || '#000000',
                                                textAlign: field.alignment || 'left',
                                                lineHeight: field.line_height || 1.4,
                                                letterSpacing: field.letter_spacing || 0,
                                            };
                                        }
                                        if (!window.fieldConstraints[field.id]) {
                                            window.fieldConstraints[field.id] = {
                                                maxLength: field.max_length  || null,   // ← was: null
                                                maxLines:  field.max_lines   || null,   // ← was: null
                                                required:  field.required    || false,
                                                dateMode:  field.date_mode   || 'current',
                                            };
                                        }
                                            if (field.placeholder !== null && field.placeholder !== undefined && field.placeholder !== '') {
                                                window.previewValues[field.id] = field.placeholder;
                                            }
                                    "
                                    @mousedown="if(!$event.target.closest('[data-resize]')) startDrag($event)"
                                    @click.stop="
                                        $wire.selectedFieldId = field.id;
                                        selectedField = field.type;
                                        selectedFieldData = field;
                                        selectedConstraints = { ...(window.fieldConstraints[field.id] || defaultConstraints()) };
                                        selectedFieldState = window.fieldState[field.id] = window.fieldState[field.id] || {x: field.x, y: field.y, width: field.width, height: field.height};
                                        selectedFieldData.source_type = selectedFieldData.source_type ?? 'input';
                                        selectedFieldData.name = selectedFieldData.name ?? ('field_' + field.id);
                                        selectedFieldData.system_key = selectedFieldData.system_key ?? null;
                                        selectedSourceType = selectedFieldData.source_type;
                                    "
                                    class="absolute bg-transparent text-xs border cursor-move select-none"
                                    :class="{
                                        'border-blue-500 shadow-sm': $wire.selectedFieldId === field.id,
                                        'border-gray-300': $wire.selectedFieldId !== field.id,
                                        'z-50': dragging || resizing
                                    }"
                                    :style="`
                                        left: ${localX}px;
                                        top: ${localY}px;
                                        width: ${localW}px;
                                        min-height: ${localH}px;
                                        height: auto;
                                    `">

                                    <!-- TYPE BADGE + VARIABLE NAME -->
                                    <div class="absolute -top-5 left-0 flex items-center gap-1 pointer-events-none">
                                        <div class="text-[9px] font-semibold px-1 rounded-sm leading-4"
                                            :class="{
                                                'bg-blue-100 text-blue-700': field.type === 'text',
                                                'bg-purple-100 text-purple-700': field.type === 'paragraph',
                                                'bg-green-100 text-green-700': field.type === 'number',
                                                'bg-orange-100 text-orange-700': field.type === 'date',
                                            }"
                                            x-text="field.type">
                                        </div>
                                        <div class="text-[9px] font-semibold px-1 rounded-sm leading-4 bg-gray-100 text-gray-500"
                                           x-text="'@{{' + (field.source_type === 'system' ? (field.system_key || field.type) : (field.name || field.type)) + '}}'">
                                        </div>
            
                                        <!-- Required badge -->
                                        <div x-show="field.required"
                                            class="text-[9px] font-semibold px-1 rounded-sm leading-4 bg-red-100 text-red-600">
                                            required
                                        </div>
                                    </div>

                                    <!-- FIELD CONTENT -->
                                    <div
                                        class="w-full px-2 py-1 pointer-events-none break-words whitespace-pre-wrap"
                                        :style="(() => {
                                            let t = (typographyTick >= 0 || constraintTick >= 0) && (window.typographyState[field.id] || defaultTypography());
                                            let c = window.fieldConstraints[field.id] || defaultConstraints();
                                            let lineH = parseFloat(t.lineHeight) || 1.4;
                                            let fs = parseFloat(t.fontSize) || 12;
                                            let computedH = c.maxLines ? (c.maxLines * fs * lineH + 8) + 'px' : null;
                                            return `
                                                font-family: ${t.fontFamily};
                                                font-weight: ${t.fontWeight};
                                                font-style: ${t.fontStyle};
                                                font-size: ${t.fontSize}px;
                                                color: ${t.color};
                                                text-align: ${t.textAlign};
                                                line-height: ${t.lineHeight};
                                                letter-spacing: ${t.letterSpacing}px;
                                                min-height: ${computedH || '100%'};
                                                max-height: ${computedH || 'none'};
                                                overflow: hidden;
                                                width: 100%;
                                                overflow-wrap: break-word;
                                                word-break: break-word;
                                            `;
                                        })()"
                                        x-text="(() => {
                                            previewTick;
                                            let c = window.fieldConstraints[field.id] || {};

                                            if (field.type === 'date') {
                                                if (c.dateMode === 'current') {
                                                    return new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'});
                                                } else {
                                                    return window.previewValues[field.id] || field.placeholder || 'MM/DD/YYYY';
                                                }
                                            }

                                            let val = window.previewValues[field.id];
                                            if (!val && field.placeholder) val = field.placeholder;

                                            if (val) {
                                                // Enforce maxLines on the displayed value
                                                if (c.maxLines) {
                                                    let lines = val.split('\n');
                                                    if (lines.length > c.maxLines) val = lines.slice(0, c.maxLines).join('\n');
                                                }
                                                return val;
                                            }

                                            let name = field.name || field.system_key || field.type;
                                            let hint = [];
                                            if (c.maxLength) hint.push('max ' + c.maxLength + ' chars');
                                            if (c.maxLines)  hint.push('max ' + c.maxLines  + ' lines');
                                            return hint.length ? name + ' (' + hint.join(', ') + ')' : name;
                                        })()">
                                    </div>

                                    <!-- RESIZE HANDLES -->
                                    <div data-resize class="absolute w-3 h-3 bg-blue-600 -top-1 -left-1 cursor-nw-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'nw')"></div>
                                    <div data-resize class="absolute w-3 h-3 bg-blue-600 -top-1 -right-1 cursor-ne-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'ne')"></div>
                                    <div data-resize class="absolute w-3 h-3 bg-blue-600 -bottom-1 -left-1 cursor-sw-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'sw')"></div>
                                    <div data-resize class="absolute w-3 h-3 bg-blue-600 -bottom-1 -right-1 cursor-se-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'se')"></div>
                                    <div data-resize class="absolute h-1.5 w-full top-0 left-0 cursor-n-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'n')"></div>
                                    <div data-resize class="absolute h-1.5 w-full bottom-0 left-0 cursor-s-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 's')"></div>
                                    <div data-resize class="absolute w-1.5 h-full left-0 top-0 cursor-w-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'w')"></div>
                                    <div data-resize class="absolute w-1.5 h-full right-0 top-0 cursor-e-resize z-10"
                                        @mousedown.stop.prevent="startResize($event, 'e')"></div>

                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🟩 RIGHT PANEL -->
        <div class="w-80 bg-white border-l flex flex-col text-xs">

            <!-- TABS -->
            <div class="flex border-b">
                <button class="flex-1 py-2"
                    :class="activeTab==='field' ? 'border-b-2 border-blue-600 font-semibold' : ''"
                    @click="activeTab='field'">
                    Field Settings
                </button>
                <button class="flex-1 py-2"
                    :class="activeTab==='instruction' ? 'border-b-2 border-blue-600 font-semibold' : ''"
                    @click="activeTab='instruction'">
                    Document Instructions
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-3">

                <!-- FIELD SETTINGS TAB -->
                <div x-show="activeTab==='field'">

                    <!-- Field header card -->
                    <div class="border rounded-md p-3 mb-3 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <template x-if="selectedField === 'text'">
                                <div class="w-9 h-9 rounded-md bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg">T</div>
                            </template>
                            <template x-if="selectedField === 'number'">
                                <div class="w-9 h-9 rounded-md bg-green-100 flex items-center justify-center text-green-700 font-bold text-lg">#</div>
                            </template>
                            <template x-if="selectedField === 'paragraph'">
                                <div class="w-9 h-9 rounded-md bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-lg">¶</div>
                            </template>
                            <template x-if="selectedField === 'date'">
                                <div class="w-9 h-9 rounded-md bg-orange-100 flex items-center justify-center text-orange-700 text-lg">📅</div>
                            </template>
                            <template x-if="!selectedField">
                                <div class="w-9 h-9 rounded-md bg-gray-100 flex items-center justify-center text-gray-400 text-lg">?</div>
                            </template>
                            <div>
                                <div class="font-semibold text-xs capitalize"
                                    x-text="selectedField ? selectedField + ' Field' : 'No Field Selected'">
                                </div>
                                <div class="text-gray-400 text-[10px] truncate max-w-[140px]"
                                    x-text="selectedFieldData
                                        ? (selectedFieldData.source_type === 'system'
                                            ? '@{' + (selectedFieldData.system_key || '...') + '}'
                                            : '@{' + (selectedFieldData.name || '...') + '}')
                                        : '—'">
                                </div>
                            </div>
                        </div>

                        <!-- DELETE BUTTON -->
                        <button class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-md transition text-lg"
                            x-show="selectedFieldData"
                            @click="
                                let id = selectedFieldData.id;
                                $wire.fields = $wire.fields.filter(f => String(f.id) !== String(id));
                                $wire.deleteField(id);
                                selectedFieldData = null;
                                selectedField = null;
                                "
                                >🗑</button>
                    </div>

                    <hr class="my-3">

                    <!-- Empty state -->
                    <div x-show="!selectedFieldData" class="text-gray-400 text-center py-8">
                        Click a field on the canvas to edit it.
                    </div>

                    <!-- All settings (only when a field is selected) -->
                    <div x-show="selectedFieldData">

                        {{-- ── REQUIRED TOGGLE ── --}}
                        <div class="flex items-center justify-between mb-3">
                            <label class="font-semibold text-xs">Required</label>
                            <button
                                @click="
                                    let id = selectedFieldData?.id;
                                    if (!id) return;
                                    if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                                    window.fieldConstraints[id].required = !window.fieldConstraints[id].required;
                                    if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                    window.fieldMeta[id].required = window.fieldConstraints[id].required;
                                    {{-- // Force Alpine reactivity with splice instead of index assignment --}}
                                    let idx = $wire.fields.findIndex(f => f.id === id);
                                    if (idx !== -1) {
                                        let updated = { ...$wire.fields[idx], required: window.fieldConstraints[id].required };
                                        $wire.fields.splice(idx, 1, updated);
                                    }
                                    selectedFieldData.required = window.fieldConstraints[id].required;
                                    selectedConstraints.required = window.fieldConstraints[id].required;
                                    constraintTick++;
                                    scheduleAutosave();
                                "
                                :class="selectedFieldData?.required
                                    ? 'bg-red-500 text-white border-red-500'
                                    : 'bg-white text-gray-500 border-gray-300'"
                                class="px-3 py-1 text-[11px] border rounded-full transition font-medium">
                                <span x-text="selectedFieldData?.required ? '★ Required' : '☆ Optional'"></span>
                            </button>
                        </div>

                        <hr class="my-3">

                        {{-- ── DATA SOURCE ── --}}
                        <label class="font-semibold text-xs">Data Source</label>

                        {{-- Paragraph: system not available --}}
                        <div x-show="selectedFieldData?.type === 'paragraph'"
                            class="mt-1 text-[10px] text-gray-400 italic">
                            System source is not available for paragraph fields.
                        </div>

                        {{-- Data source radios — always show for non-paragraph --}}
                        <div x-show="selectedFieldData?.type !== 'paragraph'" class="mt-1 flex gap-3">
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" value="system"
                                    :checked="selectedFieldData?.source_type === 'system'"
                                    @change="
                                        let id = selectedFieldData.id;
                                        selectedFieldData.source_type = 'system';
                                        selectedFieldData.name = null;
                                        selectedFieldData.placeholder = null;

                                        {{-- // Reset constraints --}}
                                        selectedConstraints = defaultConstraints();
                                        {{-- // Reset preview value --}}
                                        window.previewValues[id] = '';

                                        {{-- // Sync to wire fields --}}
                                        let idx = $wire.fields.findIndex(f => f.id === id);
                                        if (idx !== -1) {
                                            $wire.fields.splice(idx, 1, { ...$wire.fields[idx], placeholder: null });
                                        }

                                        let validKeys = window.getValidSystemKeys(selectedFieldData.type);
                                        let firstKey = validKeys[0]?.key ?? null;
                                        selectedFieldData.system_key = firstKey;
                                        selectedFieldData.name = firstKey;
                                        if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                        window.fieldMeta[id].source_type = 'system';
                                        window.fieldMeta[id].system_key  = firstKey;
                                        window.fieldMeta[id].name        = firstKey;
                                        window.fieldMeta[id].placeholder = null;

                                        constraintTick++;
                                        previewTick++;
                                        syncFieldToList(id);
                                        scheduleAutosave();
                                    "
                                    >
                                System
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" value="input"
                                    :checked="selectedFieldData?.source_type === 'input'"
                                    @change="
                                        let id = selectedFieldData.id;
                                        selectedFieldData.source_type = 'input';
                                        selectedFieldData.system_key  = null;
                                        if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                        window.fieldMeta[id].source_type = 'input';
                                        window.fieldMeta[id].system_key  = null;
                                        syncFieldToList(id);   
                                        scheduleAutosave();
                                        $nextTick(() => { if($refs.variableInput) $refs.variableInput.focus(); });
                                    ">
                                User Input
                            </label>
                        </div>

                        {{-- System variable select --}}
                        <div x-show="selectedFieldData?.source_type === 'system' && selectedFieldData?.type !== 'paragraph'" class="mt-2">
                            <label class="text-[11px] text-gray-500">System Variable</label>
                            <select class="w-full border rounded-md px-2 py-1 mt-1"
                                :value="selectedFieldData?.system_key"
                                @change="
                                    let id = selectedFieldData.id;
                                    selectedFieldData.system_key = $event.target.value;
                                    selectedFieldData.name = $event.target.value;
                                    if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                    window.fieldMeta[id].system_key = $event.target.value;
                                    window.fieldMeta[id].name = $event.target.value;
                                    syncFieldToList(id);
                                    scheduleAutosave();
                                ">
                                <template x-for="opt in window.getValidSystemKeys(selectedFieldData?.type)" :key="opt.key">
                                    <option :value="opt.key" :selected="opt.key === selectedFieldData?.system_key" x-text="opt.label"></option>
                                </template>
                            </select>
                        </div>

                        {{-- User input variable name --}}
                        <div x-show="selectedFieldData?.source_type === 'input'" class="mt-2">
                            <label class="text-[11px] text-gray-500">Variable Name</label>
                            <input class="w-full border rounded-md px-2 py-1 mt-1"
                                x-ref="variableInput"
                                placeholder="@{{variable}}"
                                :value="selectedFieldData?.name ?? ''"
                                @input="
                                    let id = selectedFieldData?.id;
                                    if (!id) return;
                                    if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                    window.fieldMeta[id].name = $event.target.value;
                                    selectedFieldData.name = $event.target.value;
                                    syncFieldToList(id);
                                    scheduleAutosave();
                                ">
                        </div>

                        <!-- CONSTRAINTS: disabled when system source -->
                        <div x-show="selectedFieldData?.source_type === 'system'"
                            class="mt-2 text-[10px] text-gray-400 italic">
                            Constraints are managed by the system variable.
                        </div>

                        {{-- ── DATE MODE (only when source is user input) ── --}}
                        <div x-show="selectedFieldData?.type === 'date' && selectedFieldData?.source_type === 'input'" class="mt-3">
                            <hr class="mb-3">
                            <label class="font-semibold text-xs">Date Mode</label>
                            <div class="mt-1 space-y-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio"
                                        name="dateMode"
                                        value="current"
                                        :checked="selectedConstraints.dateMode === 'current'"
                                        @change="
                                            let id = selectedFieldData?.id; if (!id) return;
                                            if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                                            setConstraint('dateMode', 'current'); previewTick++; scheduleAutosave();
                                        ">
                                    <span class="text-[11px]">Current date (auto-filled when student opens)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio"
                                        name="dateMode"
                                        value="user"
                                        :checked="selectedConstraints.dateMode === 'user'"
                                        @change="
                                            let id = selectedFieldData?.id; if (!id) return;
                                            if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                                            setConstraint('dateMode', 'user'); previewTick++; scheduleAutosave();
                                        ">
                                    <span class="text-[11px]">User input (student picks a date)</span>
                                </label>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- ── TYPOGRAPHY ── --}}
                        <label class="font-semibold text-xs">Typography</label>

                        <div class="mt-2 space-y-2">
                            {{-- FONT FAMILY --}}
                            <div>
                                <label class="text-[11px] text-gray-500">Font</label>
                                <select class="w-full border rounded-md px-2 py-1 mt-1"
                                    :value="typographyTick >= 0 ? getTypo().fontFamily : 'Arial'"
                                    @change="setTypo('fontFamily', $event.target.value)">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Courier New">Courier New</option>
                                </select>
                            </div>

                            {{-- WEIGHT + SIZE --}}
                            <div class="flex gap-2">
                                <div class="w-1/2">
                                    <label class="text-[11px] text-gray-500">Weight</label>
                                    <select class="w-full border rounded-md px-2 py-1 mt-1"
                                        :value="typographyTick >= 0 ? (getTypo().fontWeight === 'bold' ? 'bold' : (getTypo().fontStyle === 'italic' ? 'italic' : 'normal')) : 'normal'"
                                        @change="
                                            let v = $event.target.value;
                                            setTypo('fontWeight', v === 'bold' ? 'bold' : (v === 'thin' ? '100' : 'normal'));
                                            setTypo('fontStyle', v === 'italic' ? 'italic' : 'normal');
                                        ">
                                        <option value="thin">Thin</option>
                                        <option value="normal">Regular</option>
                                        <option value="bold">Bold</option>
                                        <option value="italic">Italic</option>
                                    </select>
                                </div>
                                <div class="w-1/2">
                                    <label class="text-[11px] text-gray-500">Size (px)</label>
                                    <input type="number" min="6" max="128"
                                        class="w-full border rounded-md px-2 py-1 mt-1"
                                        :value="typographyTick >= 0 ? getTypo().fontSize : 12"
                                        @input="setTypo('fontSize', Math.min(128, Math.max(6, +$event.target.value)))">
                                </div>
                            </div>

                            {{-- ALIGNMENT --}}
                            <div>
                                <label class="text-[11px] text-gray-500 block">Alignment</label>
                                <div class="flex gap-1 mt-1">
                                    <button class="border p-1.5 rounded flex-1"
                                        :class="typographyTick >= 0 && getTypo().textAlign === 'left' ? 'bg-blue-50 border-blue-400' : ''"
                                        @click="setTypo('textAlign', 'left')">
                                        <svg class="w-4 h-4 mx-auto" stroke="currentColor" fill="none">
                                            <path d="M3 6h12M3 10h8M3 14h12"/>
                                        </svg>
                                    </button>
                                    <button class="border p-1.5 rounded flex-1"
                                        :class="typographyTick >= 0 && getTypo().textAlign === 'center' ? 'bg-blue-50 border-blue-400' : ''"
                                        @click="setTypo('textAlign', 'center')">
                                        <svg class="w-4 h-4 mx-auto" stroke="currentColor" fill="none">
                                            <path d="M3 6h12M5 10h8M3 14h12"/>
                                        </svg>
                                    </button>
                                    <button class="border p-1.5 rounded flex-1"
                                        :class="typographyTick >= 0 && getTypo().textAlign === 'right' ? 'bg-blue-50 border-blue-400' : ''"
                                        @click="setTypo('textAlign', 'right')">
                                        <svg class="w-4 h-4 mx-auto" stroke="currentColor" fill="none">
                                            <path d="M3 6h12M7 10h8M3 14h12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- COLOR --}}
                            <div>
                                <label class="text-[11px] text-gray-500 block">Color</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="color"
                                        class="w-8 h-8 border rounded cursor-pointer"
                                        :value="getTypo().color"
                                        @input="setTypo('color', $event.target.value)">
                                    <input type="text"
                                        class="border px-2 py-1 text-xs flex-1 rounded"
                                        :value="typographyTick >= 0 ? getTypo().color : '#000000'"
                                        @input="setTypo('color', $event.target.value)">
                                </div>
                            </div>

                            {{-- LINE HEIGHT + LETTER SPACING --}}
                            <div class="flex gap-2">
                                <div class="w-1/2">
                                    <label class="text-[11px] text-gray-500">Line Height</label>
                                    <input type="number" min="0.5" max="5" step="0.1"
                                        class="w-full border px-2 py-1 text-xs rounded mt-1"
                                        :value="typographyTick >= 0 ? getTypo().lineHeight : 1.4"
                                        @input="setTypo('lineHeight', +$event.target.value)">
                                </div>
                                <div class="w-1/2">
                                    <label class="text-[11px] text-gray-500">Letter Spacing</label>
                                    <input type="number" min="-5" max="20" step="0.5"
                                        class="w-full border px-2 py-1 text-xs rounded mt-1"
                                        :value="typographyTick >= 0 ? getTypo().letterSpacing : 0"
                                        @input="setTypo('letterSpacing', +$event.target.value)">
                                </div>
                            </div>
                        </div>

                        {{-- WRAPPER: greyed overlay when system source --}}
                        <div :class="selectedFieldData?.source_type === 'system' ? 'opacity-40 pointer-events-none select-none' : ''">

                            {{-- ── CONSTRAINTS (hidden for date fields) ── --}}
                            <div x-show="selectedFieldData?.type !== 'date'" class="mt-3 space-y-2">
                                <label class="font-semibold text-xs">Constraints</label>
                                <div class="flex gap-2 mt-1">
                                    <div class="w-1/2" x-show="selectedFieldData?.type !== 'paragraph'">
                                        <label class="text-[11px] text-gray-500">Max Length</label>
                                        <input type="number" min="1"
                                            class="w-full border px-2 py-1 rounded mt-1 text-xs"
                                            :value="selectedConstraints.maxLength || ''"
                                            @keydown="['e','E','+','-','.'].includes($event.key) && $event.preventDefault()"
                                            @input="
                                                let id = selectedFieldData?.id;
                                                if (id) {
                                                    if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                                                    setConstraint('maxLength', $event.target.value ? +$event.target.value : null);
                                                    if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                                    window.fieldMeta[id].max_length = window.fieldConstraints[id].maxLength;
                                                    previewTick++;
                                                    constraintTick++;
                                                    scheduleAutosave();
                                                }
                                            "
                                            placeholder="None">
                                    </div>
                                    <div class="w-1/2" x-show="selectedFieldData?.type === 'paragraph'">
                                        <label class="text-[11px] text-gray-500">Max Lines</label>
                                        <input type="number" min="1"
                                            class="w-full border px-2 py-1 rounded mt-1 text-xs"
                                            :value="selectedConstraints.maxLines || ''"
                                            @keydown="['e','E','+','-','.'].includes($event.key) && $event.preventDefault()"
                                            @input="
                                                let id = selectedFieldData?.id;
                                                if (id) {
                                                    if (!window.fieldConstraints[id]) window.fieldConstraints[id] = defaultConstraints();
                                                    setConstraint('maxLines', $event.target.value ? +$event.target.value : null);
                                                    if (!window.fieldMeta[id]) window.fieldMeta[id] = {};
                                                    window.fieldMeta[id].max_lines = window.fieldConstraints[id].maxLines;
                                                    previewTick++;
                                                    constraintTick++;
                                                    scheduleAutosave();
                                                }
                                            "
                                            placeholder="None">
                                    </div>
                                </div>
                            </div>
                            <hr class="my-3">

                            {{-- ── PREVIEW MODE ── --}}
                            <label class="font-semibold text-xs block">Preview Mode</label>
                            <p class="text-[10px] text-gray-400 mb-1">Test how the field will look with sample input.</p>

                        

                            {{-- DATE preview --}}
                            <div x-show="selectedFieldData?.type === 'date'">
                                {{-- Current date mode: always inactive/readonly --}}
                                <div 
                                    x-show="selectedConstraints.dateMode !== 'user'" 
                                    class="w-full border rounded-md mt-1 px-2 py-1 text-xs bg-gray-100 text-gray-400 italic cursor-not-allowed"
                                    x-text="new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'})">
                                </div>
                                {{-- User input mode: active date picker --}}
                                <input 
                                    x-show="selectedConstraints.dateMode === 'user'"
                                    type="date"
                                    class="w-full border rounded-md mt-1 px-2 py-1 text-xs"
                                    :value="selectedFieldData ? (window.rawDateValues?.[selectedFieldData.id] || '') : ''"
                                    @input="
                                        let id = selectedFieldData?.id;
                                        if (id) {
                                            if (!window.rawDateValues) window.rawDateValues = {};
                                            window.rawDateValues[id] = $event.target.value;
                                            let formatted = new Date($event.target.value + 'T00:00:00').toLocaleDateString('en-US', {
                                                year:'numeric', month:'long', day:'numeric'
                                            });
                                            window.previewValues[id] = formatted;
                                            selectedFieldData.placeholder = formatted;
                                            let idx = $wire.fields.findIndex(f => String(f.id) === String(id));
                                            if (idx !== -1) {
                                                $wire.fields.splice(idx, 1, { ...$wire.fields[idx], placeholder: formatted });
                                            }
                                            scheduleAutosave();
                                            previewTick++;
                                        }
                                    ">
                            </div>
                      

                            {{-- NUMBER: type=text with numeric-only enforcement (typing, no spinner) --}}
                            <input x-show="selectedFieldData?.type === 'number'"
                                type="text"
                                inputmode="numeric"
                                class="w-full border rounded-md mt-1 px-2 py-1 text-xs"
                                :placeholder="'@{{' + (selectedFieldData?.name || '...') + '}}'"
                                :maxlength="selectedConstraints.maxLength || false"
                                :value="selectedFieldData ? (window.previewValues[selectedFieldData.id] || '') : ''"
                                @input="
                                    let id = selectedFieldData?.id;
                                    if (!id) return;
                                    let val = $event.target.value.replace(/[^0-9.\-]/g, '');
                                    $event.target.value = val;
                                    window.previewValues[id] = val;
                                    selectedFieldData.placeholder = val;   // ← add this line
                                    let idx = $wire.fields.findIndex(f => String(f.id) === String(id));
                                    if (idx !== -1) $wire.fields.splice(idx, 1, { ...$wire.fields[idx], placeholder: val });
                                    scheduleAutosave();
                                    previewTick++;
                                ">

                            {{-- TEXT / PARAGRAPH --}}
                            <textarea
                                x-show="selectedFieldData?.type !== 'date' && selectedFieldData?.type !== 'number'"
                                class="w-full border rounded-md mt-1 text-xs px-2 py-1 resize-none"
                                :placeholder="'@{{' + (selectedFieldData?.name || selectedFieldData?.system_key || '...') + '}}'"
                                :maxlength="selectedConstraints.maxLength || false"
                                :rows="Math.max(3, selectedConstraints.maxLines || 3)"
                                @input="
                                        let id = selectedFieldData?.id;
                                        if (!id) return;
                                        let c = window.fieldConstraints[id] || {};
                                        let val = $event.target.value;
                                        if (c.maxLines) {
                                            // Count explicit newlines
                                            let lines = val.split('\n');
                                            if (lines.length > c.maxLines) {
                                                val = lines.slice(0, c.maxLines).join('\n');
                                                $event.target.value = val;
                                            }
                                            // Also clamp by textarea scrollHeight vs max-height
                                            if ($event.target.scrollHeight > $event.target.offsetHeight + 2) {
                                                // Remove last char until it fits
                                                while ($event.target.scrollHeight > $event.target.offsetHeight + 2 && val.length > 0) {
                                                    val = val.slice(0, -1);
                                                }
                                                $event.target.value = val;
                                            }
                                        }
                                        window.previewValues[id] = val;
                                        selectedFieldData.placeholder = val;
                                        let idx = $wire.fields.findIndex(f => String(f.id) === String(id));
                                        if (idx !== -1) {
                                            $wire.fields.splice(idx, 1, { ...$wire.fields[idx], placeholder: val });
                                        }
                                        scheduleAutosave();
                                        previewTick++;
                                    "
                                :value="selectedFieldData ? (window.previewValues[selectedFieldData.id] || '') : ''"
                            ></textarea>

                            {{-- Character / line counter --}}
                            <div x-show="selectedFieldData?.type !== 'date'"
                                class="text-[10px] text-gray-400 text-right mt-0.5">
                                <span x-text="(() => {
                                    let id = selectedFieldData?.id;
                                    let c = selectedConstraints;
                                    let val = window.previewValues[id] || '';
                                    let parts = [];
                                    if (c.maxLength) parts.push(val.length + ' / ' + c.maxLength + ' chars');
                                    if (c.maxLines)  parts.push(val.split('\n').length + ' / ' + c.maxLines + ' lines');
                                    return parts.join('  ');
                                })()"></span>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- DOCUMENT INSTRUCTIONS TAB -->
                <div x-show="activeTab==='instruction'" x-data="{
                        steps: [],
                        newStep: '',
                        draggingId: null,

                        init() {
                            this.steps = $wire.instructions ?? [];

                            window.Livewire.on('instructionAdded', ({ step }) => {
                                this.steps.push(step);
                            });

                            window.Livewire.on('instructionDeleted', ({ id }) => {
                                this.steps = this.steps.filter(s => s.instruction_id != id);
                            });

                            window.Livewire.on('instructionsReordered', () => {
                                // already reordered locally via splice, nothing needed
                            });
                        },

                        startDrag(index) { this.draggingId = index; },

                        dropAt(index) {
                            if (this.draggingId === null || this.draggingId === index) return;
                            const moved = this.steps.splice(this.draggingId, 1)[0];
                            this.steps.splice(index, 0, moved);
                            this.draggingId = null;
                            const ordered = this.steps.map(s => s.instruction_id);
                            $wire.reorderInstructions(ordered);
                        },

                        deleteStep(id) {
                            this.steps = this.steps.filter(s => s.instruction_id !== id);
                            $wire.deleteInstruction(id);
                        },

                        addStep() {
                            if (!this.newStep.trim()) return;
                            $wire.addInstruction(this.newStep.trim());
                            this.newStep = '';
                        }
                    }" class="flex flex-col h-full justify-between">

                    <div class="flex-1 overflow-y-auto">
                        <textarea x-model="newStep"
                            class="w-full border rounded-md h-20 mb-2 text-xs p-2"
                            placeholder="Type the next step instruction..."></textarea>

                        <button @click="addStep()"
                            class="text-xs bg-blue-600 text-white px-3 py-1 rounded-md mb-4 float-right">
                            + Add Step
                        </button>
                        <!-- Steps List -->
                        <div class="mt-8 space-y-2 text-xs">
                            <template x-for="(s, index) in steps" :key="s.instruction_id">
                                <div class="flex items-center gap-2 bg-white p-2 border rounded shadow-sm cursor-default"
                                    draggable="true"
                                    @dragstart="startDrag(index)"
                                    @dragover.prevent
                                    @drop="dropAt(index)">
                                    
                                    <span class="cursor-move text-gray-400 text-lg">⋮⋮</span>
                                    <div class="flex-1 flex justify-between items-center gap-1">
                                        <div class="flex-1 truncate">
                                            <strong class="font-semibold text-gray-600" x-text="'Step ' + (index + 1) + ':'"></strong>
                                            <span x-text="s.description" class="ml-1"></span>
                                        </div>
                                        <button @click="deleteStep(s.instruction_id)"
                                            class="text-red-500 text-lg hover:scale-110 transition">🗑</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    {{-- At the bottom of template-editor.blade.php, before the last </div> --}}
    <script>
        window.getValidSystemKeys = function(fieldType) {
            const all = @json($systemVariables);
            const numberKeys = ['student_number', 'contact_number', 'year_level'];
            const dateKeys   = ['date_of_birth'];
            const excludeFromText = [...numberKeys, ...dateKeys];
            let allowed = [];
            if (fieldType === 'date') {
                allowed = dateKeys;
            } else if (fieldType === 'number') {
                allowed = numberKeys;
            } else {
                allowed = Object.keys(all).filter(k => !excludeFromText.includes(k));
            }
            return allowed.filter(k => all[k]).map(k => ({ key: k, label: all[k] }));
        };
    </script>
</div>