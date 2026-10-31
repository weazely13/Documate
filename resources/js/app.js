import '@fontsource-variable/inter';
import './bootstrap';
import { lnuHandbook } from './lnu-handbook';
import './insights';

document.addEventListener('livewire:navigating', () => {
    document.querySelectorAll('body > [x-teleport-owner]').forEach((el) => el.remove());
});

document.addEventListener('alpine:init', () => {
    if (typeof window.toSentenceCase !== 'function') {
        window.toSentenceCase = function (str) {
            if (!str) return str;
            return String(str).toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, c => c.toUpperCase());
        };
    }

    Alpine.data('documentPreviewEngine', (fields = [], initialValues = [], systemValues = {}) => ({
        fields: fields,
        values: initialValues,
        systemValues: systemValues || {},
        scale: 1,
        ro: null,

        displayValue(field) {
            let value;
            if (field.source_type === 'system') {
                value = this.systemValues[field.name] || '';
            } else if (field.type === 'date' && field.date_mode === 'current') {
                const now = new Date();
                value = `${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}/${now.getFullYear()}`;
            } else {
                const raw = this.values[field.name];
                if (raw === null || raw === undefined || raw === '') return '';
                if (field.type === 'date') {
                    const parsed = new Date(raw);
                    value = !Number.isNaN(parsed.getTime())
                        ? `${String(parsed.getMonth() + 1).padStart(2, '0')}/${String(parsed.getDate()).padStart(2, '0')}/${parsed.getFullYear()}`
                        : raw;
                } else {
                    value = raw;
                }
            }
            if (field.text_case === 'sentence' && value) {
                value = window.toSentenceCase(value);
            }
            return value;
        },

        fieldTextStyle(field) {
            const value = this.displayValue(field);
            const fontSize = this.fitFontSize(field, value);
            const lineHeight = parseFloat(field.line_height || 1.3);
            const maxLines = parseInt(field.max_lines || 0, 10);
            const maxHeight = maxLines > 0 ? (maxLines * fontSize * lineHeight) + 8 : null;
            const isParagraph = field.type === 'paragraph';

            let caseCss = '';
            if (field.text_case === 'uppercase') caseCss = 'text-transform: uppercase;';
            else if (field.text_case === 'smallcaps') caseCss = 'text-transform: lowercase; font-variant: small-caps;';

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
                text-overflow: clip;
                max-height: ${maxHeight ? `${maxHeight}px` : 'none'};
                ${caseCss}
            `;
        },

        isMultiline(field) {
            // System values (name, program, etc.) are always single-line and must shrink
            return field.type === 'paragraph' && field.source_type !== 'system';
        },
        fitFontSize(field, value) {
            const baseSize = parseFloat(field.font_size || 12);
            const minSize = 6;
            if (this.isMultiline(field)) return baseSize;

            const width = Math.max((field.width || 0) - 16, 10) * 0.98;

            let text = `${value || ''}`.trim();
            if (field.text_case === 'uppercase' || field.text_case === 'smallcaps') {
                text = text.toUpperCase();
            }
            if (!text) return baseSize;

            for (let size = baseSize; size >= minSize; size -= 0.5) {
                if (this.singleLineFits(field, text, width, size)) return size;
            }
            return minSize;
        },
        singleLineFits(field, text, width, fontSize) {
            if (!window._measureCtx) {
                window._measureCtx = document.createElement('canvas').getContext('2d');
            }
            const ctx = window._measureCtx;
            const style  = field.font_style === 'italic' ? 'italic ' : '';
            const weight = field.font_weight || 'normal';
            const family = field.font_family || 'Arial';
            ctx.font = `${style}${weight} ${fontSize}px '${family}'`;
            const letterSpacing = parseFloat(field.letter_spacing || 0);
            return ctx.measureText(text).width + (text.length * letterSpacing) <= width;
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
                if (!words.length) { lines.push(''); continue; }

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
                if (current) lines.push(current);
            }

            const totalHeight = lines.length * fontSize * lineHeightRatio;
            if (totalHeight > height) return false;
            if (maxLines > 0 && lines.length > maxLines) return false;

            return lines.every((line) => {
                const measured = context.measureText(line).width + (line.length * letterSpacing);
                return measured <= width;
            });
        },

        fit() {
            const wrapper = this.$refs.scaleWrapper;
            if (!wrapper || !wrapper.parentElement) return;
            const available = wrapper.parentElement.clientWidth;
            if (available > 0) {
                this.scale = available / 794;
            } else {
                requestAnimationFrame(() => this.fit());
            }
        },

        initFit() {
            this.$nextTick(() => {
                this.fit();
                if (this.$refs.scaleWrapper?.parentElement && !this.ro) {
                    this.ro = new ResizeObserver(() => this.fit());
                    this.ro.observe(this.$refs.scaleWrapper.parentElement);
                }
            });
        }
    }));

    // ---- Student "New Transaction" workspace (form + live preview + PDF) ----
    // Used by resources/views/livewire/student/new-transaction.blade.php.
    // Registered here (not as an inline <script> in the Blade view) so Vite
    // actually parses this as JavaScript at build time — a stray escaping
    // issue in a Blade-templated inline <script> only ever surfaces as a
    // runtime SyntaxError in the browser, which is exactly what was breaking
    // Alpine/Livewire hydration for the whole page after the first re-render.
    Alpine.data('documentWorkspace', (config = {}) => ({
        values: config.initialValues || {},
        fields: config.fields || [],
        systemValues: config.systemValues || {},
        savedMessage: config.initialSavedMessage || null,
        lastSavedAt: config.initialLastSavedAt || null,
        pdfLoading: false,
        groups: config.groups || [],
        activeGroupIndex: 0,
        activeField: null,
        mobileTab: 'form',
        canvasWidth: config.canvasWidth || 1,
        canvasHeight: config.canvasHeight || 1,
        previewScale: 1,
        showInstructions: false,
        errorModal: { open: false, message: '', fields: {} },

        // keep references so we can remove them later
        _onResize: null,
        _onPdfReady: null,
        _onPdfError: null,
        _onValidationError: null,
        _onWorkspaceSaved: null,

        fitPreview() {
            if (window.innerWidth >= 1280) { this.previewScale = 1; return; }
            const frame = this.$refs.previewFrame;
            if (!frame || !frame.clientWidth || !frame.clientHeight) return;
            const scaleToFitWidth = frame.clientWidth / this.canvasWidth;
            const scaleToFitHeight = frame.clientHeight / this.canvasHeight;
            this.previewScale = Math.min(scaleToFitWidth, scaleToFitHeight, 1);
        },


        get activeGroupName() {
            return this.groups[this.activeGroupIndex] || null;
        },
        get totalCount() {
            return this.fields.filter(f => this.isStudentInput(f)).length;
        },
        get filledCount() {
            return this.fields.filter(f => this.isStudentInput(f) && this.isFilled(f.name)).length;
        },
        isStudentInput(field) {
            if (field.source_type !== 'input') return false;
            if (field.type === 'date' && field.date_mode === 'current') return false;
            return true;
        },
        isFilled(name) {
            const v = this.values[name];
            return v !== null && v !== undefined && String(v).trim() !== '';
        },

        init() {
            this.$nextTick(() => this.fitPreview());

            this._onResize = () => this.fitPreview();
            window.addEventListener('resize', this._onResize);

            this.$watch('mobileTab', () => this.$nextTick(() => this.fitPreview()));

            this._onPdfReady = (event) => {
                const detail = event.detail?.[0] || event.detail || {};
                this.pdfLoading = false;
                this.savedMessage = detail.message || 'PDF generated.';
                this.lastSavedAt = detail.savedAt || this.lastSavedAt;
                if (detail.url) setTimeout(() => window.open(detail.url, '_blank'), 100);
            };
            window.addEventListener('pdf-ready', this._onPdfReady);

            this._onPdfError = (event) => {
                const detail = event.detail?.[0] || event.detail || {};
                this.pdfLoading = false;
                this.errorModal.fields = {};
                this.errorModal.message = detail.message || 'Something went wrong while generating the PDF.';
                this.errorModal.open = true;
            };
            window.addEventListener('pdf-error', this._onPdfError);

            this._onValidationError = (event) => {
                const detail = event.detail?.[0] || event.detail || {};
                this.pdfLoading = false;
                const fields = {};
                (detail.fields || []).forEach((name) => { fields[name] = true; });
                this.errorModal.fields = fields;
                this.errorModal.message = detail.message || 'Please review the highlighted fields before continuing.';
                this.errorModal.open = true;
                this.jumpToFieldGroup(Object.keys(fields)[0]);
                this.mobileTab = 'form';
            };
            window.addEventListener('validation-error', this._onValidationError);

            this._onWorkspaceSaved = (event) => {
                const detail = event.detail?.[0] || event.detail || {};
                this.savedMessage = detail.message || 'Workspace saved.';
                this.lastSavedAt = detail.savedAt || this.lastSavedAt;
            };
            window.addEventListener('workspace-saved', this._onWorkspaceSaved);

            this.fields.forEach((field) => {
                if (field.source_type === 'system' || (field.type === 'date' && field.date_mode === 'current')) {
                    this.values[field.name] = this.displayValue(field);
                }
            });
        },

        // Alpine calls this automatically when the x-data element is removed
        // from the DOM — including on a wire:navigate swap. This is what stops
        // these closures from firing against a component that no longer exists.
       destroy() {
            window.removeEventListener('resize', this._onResize);
            window.removeEventListener('pdf-ready', this._onPdfReady);
            window.removeEventListener('pdf-error', this._onPdfError);
            window.removeEventListener('validation-error', this._onValidationError);
            window.removeEventListener('workspace-saved', this._onWorkspaceSaved);

            document.querySelectorAll('body > [x-teleport-owner]').forEach((el) => el.remove());
        },

        setActiveField(name) {
            this.activeField = name;
        },
        clearActiveField() {
            this.activeField = null;
        },

        nextGroup() {
            if (this.activeGroupIndex < this.groups.length - 1) {
                this.activeGroupIndex++;
            }
        },
        prevGroup() {
            if (this.activeGroupIndex > 0) {
                this.activeGroupIndex--;
            }
        },
        jumpToFieldGroup(name) {
            if (!name) return;
            const field = this.fields.find((f) => f.name === name);
            if (!field) return;
            const groupName = field.group_name || 'Other';
            const idx = this.groups.indexOf(groupName);
            if (idx !== -1) this.activeGroupIndex = idx;
        },

        inputClasses(name, disabled) {
            if (disabled) {
                return 'border-slate-200 bg-slate-50 text-slate-500';
            }
            if (this.errorModal.fields[name]) {
                return 'border-rose-400 bg-rose-50/40 text-slate-900 focus:border-rose-500 focus:ring-4 focus:ring-rose-100';
            }
            if (this.isFilled(name)) {
                return 'border-emerald-300 bg-emerald-50/40 text-slate-900 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
            }
            return 'border-slate-300 bg-white text-slate-900 focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
        },

        saveWorkspace() {
            this.$wire.call('saveWorkspace', this.values);
        },
        savePdf() {
            if (this.pdfLoading) return;
            this.pdfLoading = true;
            this.mobileTab = 'preview';
            this.$wire.call('savePdf', this.values)
                .then(() => {
                    setTimeout(() => {
                        if (this.$el.isConnected) this.pdfLoading = false;
                    }, 8000);
                })
                .catch(() => {
                    if (this.$el.isConnected) this.pdfLoading = false;
                });
        },

        displayValue(field) {
            let value;
            if (field.source_type === 'system') {
                value = this.systemValues[field.name] || '';
            } else if (field.type === 'date' && field.date_mode === 'current') {
                const now = new Date();
                value = `${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}/${now.getFullYear()}`;
            } else {
                const raw = this.values[field.name];
                if (raw === null || raw === undefined || raw === '') {
                    return ''; // no placeholder text, ever — applies to paragraph & number too
                }
                if (field.type === 'date') {
                    const parsed = new Date(raw);
                    value = !Number.isNaN(parsed.getTime())
                        ? `${String(parsed.getMonth() + 1).padStart(2, '0')}/${String(parsed.getDate()).padStart(2, '0')}/${parsed.getFullYear()}`
                        : raw;
                } else {
                    value = raw;
                }
            }

            if (field.text_case === 'sentence' && value) {
                value = window.toSentenceCase(value);
            }
            return value;
        },
        isMultiline(field) {
            return field.type === 'paragraph' && field.source_type !== 'system';
        },

        fitFontSize(field, value) {
            const baseSize = parseFloat(field.font_size || 12);
            const minSize = 6;
            if (this.isMultiline(field)) return baseSize;

            const width = Math.max((field.width || 0) - 16, 10) * 0.98;

            let text = `${value || ''}`.trim();
            if (field.text_case === 'uppercase' || field.text_case === 'smallcaps') {
                text = text.toUpperCase();
            }
            if (!text) return baseSize;

            for (let size = baseSize; size >= minSize; size -= 0.5) {
                if (this.singleLineFits(field, text, width, size)) return size;
            }
            return minSize;
        },

        singleLineFits(field, text, width, fontSize) {
            if (!window._measureCtx) {
                window._measureCtx = document.createElement('canvas').getContext('2d');
            }
            const ctx = window._measureCtx;
            const style  = field.font_style === 'italic' ? 'italic ' : '';
            const weight = field.font_weight || 'normal';
            const family = field.font_family || 'Arial';
            ctx.font = `${style}${weight} ${fontSize}px '${family}'`;
            const letterSpacing = parseFloat(field.letter_spacing || 0);
            return ctx.measureText(text).width + (text.length * letterSpacing) <= width;
        },

        fieldTextStyle(field) {
            const value = this.displayValue(field);
            const fontSize = this.fitFontSize(field, value);
            const lineHeight = parseFloat(field.line_height || 1.3);
            const multiline = this.isMultiline(field);
            const maxLines = multiline ? parseInt(field.max_lines || 0, 10) : 0;
            const maxHeight = maxLines > 0 ? (maxLines * fontSize * lineHeight) + 8 : null;

            let caseCss = '';
            if (field.text_case === 'uppercase') caseCss = 'text-transform: uppercase;';
            else if (field.text_case === 'smallcaps') caseCss = 'text-transform: lowercase; font-variant: small-caps;';

            return `
                font-family: '${field.font_family}', sans-serif;
                font-size: ${fontSize}px;
                font-weight: ${field.font_weight};
                font-style: ${field.font_style || 'normal'};
                color: ${field.text_color};
                text-align: ${field.alignment};
                line-height: ${lineHeight};
                letter-spacing: ${field.letter_spacing}px;
                display: block;
                width: 100%;
                overflow: hidden;
                overflow-wrap: ${multiline ? 'break-word' : 'normal'};
                word-break: ${multiline ? 'break-word' : 'normal'};
                white-space: ${multiline ? 'pre-wrap' : 'nowrap'};
                text-overflow: clip;
                max-height: ${maxHeight ? `${maxHeight}px` : 'none'};
                ${caseCss}
            `;
        },

        fieldBoxStyle(field) {
            const baseHeight = parseFloat(field.height || 0);
            const fontSize = this.fitFontSize(field, this.displayValue(field));
            const lineHeight = parseFloat(field.line_height || 1.3);
            const maxLines = this.isMultiline(field) ? parseInt(field.max_lines || 0, 10) : 0;
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
                background: transparent;
                overflow: visible;
            `;
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
    }));
    Alpine.data('lnuHandbook', lnuHandbook);
});
// ---- Logbook analytics charts (used by the Logbooks tab under Transactions) ----
// Attached to `window` explicitly because Vite bundles this as an ES module —
// plain top-level function declarations in a module are NOT automatically
// global the way they are in a classic <script> tag.
window.logbookCharts = function (initial) {
    // Chart.js instances live in this closure-scoped plain object — NOT on
    // `this` / the returned x-data object. Alpine wraps every property of
    // x-data in a reactive Proxy (via @vue/reactivity's `reactive()`), and
    // Chart.js instances are full of circular, interlinked getters
    // (scale <-> chart <-> controller). Once that graph gets proxied,
    // accessing it re-enters the proxy recursively with no cycle guard,
    // which is exactly the "Maximum call stack size exceeded" in `toRaw`
    // above. Keeping `charts` out of the reactive scope entirely avoids it.
    const charts = {};

    return {
        data: initial,
        chartOrder: ['purpose', 'monthly', 'program'],

        init() {
            charts.purposeChart = this.buildBarChart(this.$refs.purposeChart, this.data.purpose, '#2A57B4');
            charts.monthlyChart = this.buildLineChart(this.$refs.monthlyChart, this.data.monthly);
            charts.programChart = this.buildBarChart(this.$refs.programChart, this.data.program, '#10b981');
            requestAnimationFrame(() => {
                Object.values(charts).forEach((chart) => chart && chart.resize());
            });
            this.$wire.on('logbook-charts-refresh', () => this.refresh());
        },

        meta(chart) {
            return this.data[chart + 'Meta'] || { rangeLabel: 'Lifetime', totalEntries: 0 };
        },

        handleChartSwitch() {
            this.$nextTick(() => {
                Object.values(charts).forEach((chart) => {
                    if (chart) {
                        chart.resize();
                        chart.update('none');
                    }
                });
            });
        },

        refresh() {
            this.$wire.getChartData().then((fresh) => {
                this.data = fresh;
                this.$nextTick(() => {
                    this.updateChart(charts.purposeChart, fresh.purpose);
                    this.updateChart(charts.monthlyChart, fresh.monthly);
                    this.updateChart(charts.programChart, fresh.program);
                });
            });
        },

        buildBarChart(canvas, dataObj, color) {
            // Still worth keeping: guards against the canvas-reuse case if
            // this ever fires twice for the same DOM node.
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            return new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: Object.keys(dataObj),
                    datasets: [{ label: 'Requests', data: Object.values(dataObj), backgroundColor: color, borderRadius: 6 }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },

        buildLineChart(canvas, dataObj) {
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            return new Chart(canvas, {
                type: 'line',
                data: {
                    labels: Object.keys(dataObj),
                    datasets: [{
                        label: 'Requests',
                        data: Object.values(dataObj),
                        borderColor: '#2A57B4',
                        backgroundColor: 'rgba(42, 87, 180, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },

        updateChart(chart, dataObj) {
            if (!chart) return;
            chart.data.labels = Object.keys(dataObj);
            chart.data.datasets[0].data = Object.values(dataObj);
            chart.resize();
            chart.update('none');
        },

        download(chartKey, filename) {
            const chart = charts[chartKey];
            if (!chart) return;
            const link = document.createElement('a');
            link.href = chart.toBase64Image('image/png', 1);
            link.download = filename + '.png';
            link.click();
        },
    };
};

//--pinch/pan component--//
Alpine.data('pinchZoomPreview', (config = {}) => ({
    scale: 1, baseScale: 1, translateX: 0, translateY: 0,
    minScale: config.minScale ?? 0.5,
    maxScale: config.maxScale ?? 4,
    _pointers: {}, _startDist: 0, _startScale: 1, _panStart: null,

    init() {
        this.$nextTick(() => this.fitToContainer());
        this._onResize = () => this.fitToContainer();
        window.addEventListener('resize', this._onResize);
        window.addEventListener('orientationchange', this._onResize);
    },
    destroy() {
        window.removeEventListener('resize', this._onResize);
        window.removeEventListener('orientationchange', this._onResize);
    },
    fitToContainer() {
        const frame = this.$refs.zoomFrame;
        if (!frame) return;
        const availW = frame.clientWidth;
        const availH = frame.clientHeight;
        const contentW = config.contentWidth || 1;
        const contentH = config.contentHeight || 1;

        if (availW > 0 && availH > 0) {
            this.baseScale = Math.min(availW / contentW, availH / contentH, 1);
            this.scale = this.baseScale;
            this.translateX = 0;
            this.translateY = 0;
        } else {
            // Container not laid out yet (e.g. right after a wire:navigate
            // swap) — retry next frame instead of leaving scale stuck at 1.
            requestAnimationFrame(() => this.fitToContainer());
        }
    },
    reset() {
        this.scale = this.baseScale;
        this.translateX = 0;
        this.translateY = 0;
    },
    zoomBy(delta) {
        this.scale = Math.min(this.maxScale, Math.max(this.minScale, this.scale + delta));
    },
    onPointerDown(e) {
        this._pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        const pts = Object.values(this._pointers);
        if (pts.length === 2) {
            this._startDist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            this._startScale = this.scale;
        } else if (pts.length === 1) {
            this._panStart = { x: e.clientX - this.translateX, y: e.clientY - this.translateY };
        }
    },
    onPointerMove(e) {
        if (!(e.pointerId in this._pointers)) return;
        this._pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        const pts = Object.values(this._pointers);

        if (pts.length === 2 && this._startDist > 0) {
            const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            this.scale = Math.min(this.maxScale, Math.max(this.minScale, this._startScale * (dist / this._startDist)));
        } else if (pts.length === 1 && this._panStart) {
            this.translateX = e.clientX - this._panStart.x;
            this.translateY = e.clientY - this._panStart.y;
        }
    },
    onPointerUp(e) {
        delete this._pointers[e.pointerId];
        if (Object.keys(this._pointers).length < 2) this._startDist = 0;
        if (Object.keys(this._pointers).length === 0) this._panStart = null;
    },
}));
