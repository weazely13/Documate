/**
 * Template editor (Admin › Templates › a template): a standard design-editor canvas over the form image.
 *
 *  Select     click · Shift/Ctrl-click to add or remove · drag on empty paper for a marquee · Ctrl+A · Esc
 *  Move       drag (snaps to the page and other fields; hold Alt to stop snapping, Shift to keep to one axis)
 *             arrows nudge 1 px, Shift+arrows 10 px
 *  Resize     the 8 handles of a single selected field
 *  Edit       Ctrl+C / Ctrl+X / Ctrl+V / Ctrl+D · Delete · Ctrl+Z / Ctrl+Shift+Z (or Ctrl+Y) · right-click menu
 *  View       Ctrl+scroll or pinch to zoom at the pointer · scroll to pan · Space-drag, middle-drag or H to pan
 *             Ctrl+0 fit · Ctrl+1 100% · Ctrl+= / Ctrl+- zoom
 *
 * State lives here; the server only stores it. Every change is saved (debounced) through
 * $wire.saveFields(fields, name), which returns real ids for fields created here ("tmp-…").
 */

const TYPES = {
    text: { label: 'Text', icon: 'bx-text', tint: 'bg-[#dcecfa] text-[#1E4090]' },
    number: { label: 'Number', icon: 'bx-hash', tint: 'bg-[#d9f3e1] text-[#1f6b3a]' },
    paragraph: { label: 'Paragraph', icon: 'bx-paragraph', tint: 'bg-[#e6e0f5] text-[#5b3fa0]' },
    date: { label: 'Date', icon: 'bx-calendar', tint: 'bg-[#fdece3] text-[#9a4521]' },
};

const LOCKED_SYSTEM_KEYS = ['current_semester', 'current_academic_year'];
const NUMBER_KEYS = ['student_number', 'contact_number', 'year_level'];
const DATE_KEYS = ['date_of_birth'];

const CLIPBOARD_KEY = 'documate.templateClipboard';
const TOUR_KEY = 'documate.editorTour.v1';

/** The guided tour: each step points at an element marked data-tour="…". */
const TOUR = [
    { target: 'canvas', title: 'This is your form', text: 'The page is the form itself. Scroll to move around, Ctrl + scroll (or pinch) to zoom, and drag on empty paper to select several fields at once.' },
    { target: 'fields', title: 'Add the blanks', text: 'Add a Text, Number, Paragraph or Date field, then drag it onto the blank line of the form. Pull the handles to resize it.' },
    { target: 'auto', title: 'Filled in for the student', text: 'Automatic fields come from the student\'s profile (name, student number, program…) or the current term. Students never type them.' },
    { target: 'layers', title: 'Layers and groups', text: 'Every field is listed here. Drag to reorder, and pick a group to select all of its fields. Right-click fields on the page to group them.' },
    { target: 'design', title: 'Field settings', text: 'With a field selected: the label students read, its type, whether it is required, its group and its text style. With nothing selected: the page and its paper size.' },
    { target: 'steps', title: 'Instructions', text: 'Steps students read before they fill in and print the form, like "Have your parent sign the bottom".' },
    { target: 'preview', title: 'Check it as a student', text: 'Preview opens the form exactly as students will see it, with sample details. Nothing is saved there.' },
    { target: 'publish', title: 'Publish when ready', text: 'Private forms are hidden from students. Switch to Public when the form is ready. Everything you change is saved as you go.' },
];
const SNAP = 6; // screen px

let tmpCounter = 0;
const tmpId = () => `tmp-${Date.now().toString(36)}-${(tmpCounter++).toString(36)}`;
const clone = (v) => JSON.parse(JSON.stringify(v));
const isTyping = (el) => el && (['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName) || el.isContentEditable);

function blankField(type) {
    return {
        id: tmpId(),
        type,
        text: TYPES[type]?.label ?? 'Field',
        name: 'field_' + Math.random().toString(36).slice(2, 8),
        source_type: 'input',
        system_key: null,
        group_name: null,
        x: 100, y: 100, width: type === 'paragraph' ? 320 : 180, height: type === 'paragraph' ? 80 : 28,
        font_family: 'Arial', font_weight: 'normal', font_size: 12, text_color: '#000000',
        alignment: 'left', line_height: 1.4, letter_spacing: 0, text_case: 'none',
        max_length: null, max_lines: null, required: false, date_mode: 'current', placeholder: null,
    };
}

const DEFAULT_LABELS = ['text', 'number', 'date', 'paragraph', 'field'];
const headline = (str) => String(str || '').replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim()
    .replace(/\w\S*/g, (w, i) => (i > 0 && ['to', 'of', 'the', 'and', 'or'].includes(w.toLowerCase()) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()));

let measureCtx = null;
function textWidth(text, f, size) {
    measureCtx = measureCtx || document.createElement('canvas').getContext('2d');
    const italic = f.font_weight === 'italic';
    measureCtx.font = `${italic ? 'italic ' : ''}${italic ? 'normal' : f.font_weight || 'normal'} ${size}px '${f.font_family || 'Arial'}'`;
    return measureCtx.measureText(text).width + text.length * (parseFloat(f.letter_spacing) || 0);
}

function sentenceCase(str) {
    if (!str) return str;
    return String(str).toLowerCase().replace(/(^\s*\w|[.!?]\s+\w)/g, (m) => m.toUpperCase());
}

export default (config) => ({
    // ── data ────────────────────────────────────────────────────────────────
    fields: [],
    sel: [],
    page: { width: config.page.width, height: config.page.height },
    paper: config.paper,
    imageUrl: config.imageUrl,
    name: config.name,
    description: config.description || '',
    status: config.status,
    systemVariables: config.systemVariables,
    systemPreview: config.systemPreview,

    zoom: 1, panX: 0, panY: 0,
    tool: 'select',
    spaceDown: false,
    gesture: null,          // the pointer action in progress
    marquee: null,          // { x, y, w, h } in page units
    guides: { x: [], y: [] },
    menu: null,             // right-click menu { x, y }
    groupDialog: null,      // { name } while naming a group
    mobilePanel: null,      // phones: 'layers' | 'design' | 'steps' | 'add'
    pointers: {},           // touch points, for pinch-zoom
    leftTab: 'layers',
    layerGroup: '',         // '' every field · '__none' not in a group · a group name
    layerDrag: null,        // ids being dragged in the layers list
    layerOver: null,        // { id, after } where they would land
    rightTab: 'design',
    layerFilter: '',

    past: [], future: [], lastRecordKey: null, lastRecordAt: 0,
    saveState: 'saved',     // saved | dirty | saving | error
    saveTimer: null, saving: false, saveAgain: false,
    pasteCount: 0,
    toast: null,

    // Instructions for students (the Instructions tab)
    steps: (config.instructions || []).map((x) => ({ ...x })),
    stepDraft: '',
    stepBusy: false,
    stepDrag: null,
    stepOver: null,
    stepEditing: null,      // the step being edited (its id)

    // The guided tour
    tourSteps: TOUR,
    tour: null,             // index of the step on screen, or null
    tourRect: null,         // where the highlighted part is

    // PDF / image replacement
    pdf: null, pdfPages: [], pdfBusy: false, pdfError: '',

    // ── setup ───────────────────────────────────────────────────────────────
    init() {
        this.fields = (config.fields || []).map((f) => ({ ...blankField(f.type || 'text'), ...f, id: String(f.id) }));
        this.$nextTick(() => this.fit());

        this._keydown = (e) => this.onKey(e);
        this._keyup = (e) => { if (e.code === 'Space') this.spaceDown = false; };
        this._move = (e) => this.onPointerMove(e);
        this._up = (e) => this.onPointerUp(e);
        this._cancel = (e) => { delete this.pointers[e.pointerId]; this.onPointerUp(e); };
        window.addEventListener('pointercancel', this._cancel);
        this._beforeUnload = (e) => {
            if (this.saveState !== 'saved') { e.preventDefault(); e.returnValue = ''; }
        };
        window.addEventListener('keydown', this._keydown);
        window.addEventListener('keyup', this._keyup);
        window.addEventListener('pointermove', this._move);
        window.addEventListener('pointerup', this._up);
        window.addEventListener('beforeunload', this._beforeUnload);
        new ResizeObserver(() => { if (!this._fittedOnce) this.fit(); }).observe(this.$refs.viewport);
        this._tourResize = () => this.placeTour();
        window.addEventListener('resize', this._tourResize);
        // First visit: show the tour once
        try { if (!localStorage.getItem(TOUR_KEY)) setTimeout(() => this.startTour(), 700); } catch (_) { /* storage blocked */ }

        Livewire.on('imageUploaded', ({ url }) => {
            this.imageUrl = url;
            this.notify('Background replaced');
        });
        Livewire.on('paperUpdated', ({ page, paper }) => {
            this.page = page;
            this.paper = paper;
            this.$nextTick(() => this.fit());
        });
    },

    destroy() {
        window.removeEventListener('keydown', this._keydown);
        window.removeEventListener('keyup', this._keyup);
        window.removeEventListener('pointermove', this._move);
        window.removeEventListener('pointerup', this._up);
        window.removeEventListener('pointercancel', this._cancel);
        window.removeEventListener('beforeunload', this._beforeUnload);
        window.removeEventListener('resize', this._tourResize);
    },

    // ── lookups ─────────────────────────────────────────────────────────────
    types: TYPES,
    byId(id) { return this.fields.find((f) => f.id === id); },
    isSel(id) { return this.sel.includes(id); },
    get selected() { return this.fields.filter((f) => this.sel.includes(f.id)); },
    get one() { return this.sel.length === 1 ? this.byId(this.sel[0]) : null; },
    /** Front-most first, like any design tool; filtered by the search and the group dropdown. */
    get layers() {
        const q = this.layerFilter.trim().toLowerCase();
        return [...this.fields].reverse().filter((f) => {
            if (this.layerGroup === '__none' && f.group_name) return false;
            if (this.layerGroup && this.layerGroup !== '__none' && f.group_name !== this.layerGroup) return false;
            return !q
                || (f.text || '').toLowerCase().includes(q)
                || this.varName(f).toLowerCase().includes(q)
                || (f.group_name || '').toLowerCase().includes(q);
        });
    },
    groupCount(name) { return this.fields.filter((f) => (name === '__none' ? !f.group_name : f.group_name === name)).length; },
    /** The group dropdown: show only that group's layers and select them. */
    pickGroup(name) {
        this.layerGroup = name;
        if (name) this.sel = this.fields.filter((f) => (name === '__none' ? !f.group_name : f.group_name === name)).map((f) => f.id);
    },

    // ── layers: drag to reorder (the list is front-most first) ──
    layerDragStart(e, id) {
        if (!this.isSel(id)) this.sel = [id];
        this.layerDrag = this.fields.filter((f) => this.sel.includes(f.id)).map((f) => f.id);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', id);
    },
    layerDragOver(e, id) {
        if (!this.layerDrag) return;
        const r = e.currentTarget.getBoundingClientRect();
        this.layerOver = this.layerDrag.includes(id) ? null : { id, after: e.clientY > r.top + r.height / 2 };
    },
    layerDrop() {
        const over = this.layerOver, moving = this.layerDrag;
        this.layerDrag = null;
        this.layerOver = null;
        if (!over || !moving?.length) return;
        this.record();
        const picked = this.fields.filter((f) => moving.includes(f.id));
        const rest = this.fields.filter((f) => !moving.includes(f.id));
        const at = rest.findIndex((f) => f.id === over.id);
        // above the target in the list = in front of it = later in the array
        const index = over.after ? at : at + 1;
        rest.splice(index, 0, ...picked);
        this.fields = rest;
        this.changed();
    },
    layerDragEnd() { this.layerDrag = null; this.layerOver = null; },
    moveLayer(dir) {
        if (!this.sel.length) return;
        this.record();
        const arr = [...this.fields];
        const idxs = arr.map((f, i) => (this.sel.includes(f.id) ? i : -1)).filter((i) => i >= 0);
        const order = dir === 'up' ? [...idxs].reverse() : idxs; // up = towards the front
        for (const i of order) {
            const j = dir === 'up' ? i + 1 : i - 1;
            if (j < 0 || j >= arr.length || this.sel.includes(arr[j].id)) continue;
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        this.fields = arr;
        this.changed();
    },
    get groups() { return [...new Set(this.fields.map((f) => f.group_name).filter(Boolean))]; },
    varName(f) { return (f.source_type === 'system' ? f.system_key : f.name) || f.type; },
    systemLabel(key) { return String(this.systemVariables?.[key] || headline(key)).replace(/\s*\(automatic\)/i, ''); },
    /** What a person calls this field: its label, or (while it still has the editor's default) its profile detail or name. */
    displayLabel(f) {
        const label = (f.text || '').trim();
        if (label && !DEFAULT_LABELS.includes(label.toLowerCase())) return label;
        return f.source_type === 'system' ? this.systemLabel(f.system_key) : headline(f.name || f.type);
    },
    isLocked(f) { return f && f.source_type === 'system' && LOCKED_SYSTEM_KEYS.includes(f.system_key); },

    systemOptions(type) {
        const all = this.systemVariables || {};
        let keys;
        if (type === 'date') keys = DATE_KEYS;
        else if (type === 'number') keys = NUMBER_KEYS;
        else keys = Object.keys(all).filter((k) => !NUMBER_KEYS.includes(k) && !DATE_KEYS.includes(k));
        return keys.filter((k) => all[k]).map((k) => ({ key: k, label: all[k] }));
    },

    bbox(list = this.selected) {
        if (!list.length) return null;
        const x1 = Math.min(...list.map((f) => f.x));
        const y1 = Math.min(...list.map((f) => f.y));
        const x2 = Math.max(...list.map((f) => f.x + f.width));
        const y2 = Math.max(...list.map((f) => f.y + f.height));
        return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
    },

    /** One value for the selection, or '' when the selected fields differ (shown as "Mixed"). */
    shared(key) {
        const list = this.selected;
        if (!list.length) return '';
        const first = list[0][key];
        return list.every((f) => f[key] === first) ? first : '';
    },
    mixed(key) {
        const list = this.selected;
        return list.length > 1 && !list.every((f) => f[key] === list[0][key]);
    },

    // ── what a field shows on the paper ─────────────────────────────────────
    previewText(f) {
        let out;
        if (f.source_type === 'system' && LOCKED_SYSTEM_KEYS.includes(f.system_key)) {
            out = this.systemPreview?.[f.system_key] ?? f.system_key;
        } else if (f.source_type === 'system') {
            out = this.systemLabel(f.system_key); // e.g. "Student number", shown faint until a student fills it
        } else if (f.type === 'date' && f.date_mode === 'current') {
            out = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        } else if (f.placeholder) {
            out = f.placeholder;
            if (f.max_lines) out = out.split('\n').slice(0, f.max_lines).join('\n');
        } else {
            out = this.displayLabel(f);
        }
        return f.text_case === 'sentence' ? sentenceCase(out) : out;
    },
    /** The field's box on the page. */
    fieldStyle(f) {
        return `left:${f.x}px;top:${f.y}px;width:${f.width}px;height:${f.height}px;`;
    },
    /**
     * The text inside, drawn the way the student form and its PDF draw it (resources/js/app.js documentWorkspace):
     * 8 px / 4 px padding, one line that shrinks to fit for text, number and date, wrapping lines for paragraphs.
     */
    textStyle(f) {
        const italic = f.font_weight === 'italic';
        const multiline = f.type === 'paragraph';
        let size = parseFloat(f.font_size) || 12;
        if (!multiline) {
            let text = String(this.previewText(f) || '').trim();
            if (f.text_case === 'uppercase' || f.text_case === 'smallcaps') text = text.toUpperCase();
            const room = Math.max(f.width - 16, 10) * 0.98;
            while (text && size > 6 && textWidth(text, f, size) > room) size -= 0.5;
        }
        const caseCss = f.text_case === 'uppercase' ? 'text-transform:uppercase;'
            : f.text_case === 'smallcaps' ? 'text-transform:lowercase;font-variant:small-caps;' : '';
        return `font-family:'${f.font_family}',sans-serif;font-weight:${italic ? 'normal' : f.font_weight};font-style:${italic ? 'italic' : 'normal'};`
            + `font-size:${size}px;color:${f.text_color};text-align:${f.alignment};line-height:${f.line_height};letter-spacing:${f.letter_spacing}px;`
            + `white-space:${multiline ? 'pre-wrap' : 'nowrap'};overflow-wrap:${multiline ? 'break-word' : 'normal'};${caseCss}`;
    },
    hasPreviewValue(f) {
        return !!f.placeholder || (f.type === 'date' && f.date_mode === 'current') || this.isLocked(f);
    },

    // ── view: zoom and pan ──────────────────────────────────────────────────
    fit() {
        const vp = this.$refs.viewport;
        if (!vp) return;
        const r = vp.getBoundingClientRect();
        if (r.width < 50) return;
        this._fittedOnce = true;
        const z = Math.min((r.width - 96) / this.page.width, (r.height - 96) / this.page.height, 1.5);
        this.zoom = Math.max(0.1, Math.round(z * 100) / 100);
        this.panX = (r.width - this.page.width * this.zoom) / 2;
        this.panY = Math.max(48, (r.height - this.page.height * this.zoom) / 2);
    },
    zoomTo(z, cx = null, cy = null) {
        const vp = this.$refs.viewport.getBoundingClientRect();
        const px = cx ?? vp.width / 2;
        const py = cy ?? vp.height / 2;
        const nz = Math.min(4, Math.max(0.1, z));
        // keep the point under the pointer still
        this.panX = px - ((px - this.panX) / this.zoom) * nz;
        this.panY = py - ((py - this.panY) / this.zoom) * nz;
        this.zoom = nz;
    },
    zoomBy(f, cx, cy) { this.zoomTo(this.zoom * f, cx, cy); },
    /** Gentle on purpose: a wheel notch zooms about 8% and scrolls about 50 px. Trackpads stay smooth. */
    onWheel(e) {
        const vp = this.$refs.viewport.getBoundingClientRect();
        const unit = e.deltaMode === 1 ? 16 : (e.deltaMode === 2 ? vp.height : 1); // lines / pages -> px
        let dx = e.deltaX * unit, dy = e.deltaY * unit;
        if (e.ctrlKey || e.metaKey) {
            const d = Math.max(-60, Math.min(60, dy));
            this.zoomBy(Math.exp(-d * 0.0013), e.clientX - vp.left, e.clientY - vp.top);
            return;
        }
        if (e.shiftKey && !dx) { dx = dy; dy = 0; }
        const clamp = (v) => Math.max(-80, Math.min(80, v * 0.5));
        this.panX -= clamp(dx);
        this.panY -= clamp(dy);
    },
    toPage(e) {
        const r = this.$refs.page.getBoundingClientRect();
        return { x: (e.clientX - r.left) / this.zoom, y: (e.clientY - r.top) / this.zoom };
    },
    get viewportCursor() {
        if (this.gesture?.kind === 'pan') return 'cursor-grabbing';
        if (this.tool === 'hand' || this.spaceDown) return 'cursor-grab';
        return 'cursor-default';
    },

    // ── pointer: select, marquee, move, resize, pan ─────────────────────────
    onPointerDown(e) {
        this.menu = null;
        if (e.pointerType === 'touch') {
            this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
            const pts = Object.values(this.pointers);
            if (pts.length === 2) {
                // second finger: drop whatever one finger started, pinch and pan instead
                if (this.gesture?.recorded) this.changed();
                this.marquee = null;
                const vp = this.$refs.viewport.getBoundingClientRect();
                const mid = { x: (pts[0].x + pts[1].x) / 2 - vp.left, y: (pts[0].y + pts[1].y) / 2 - vp.top };
                this.gesture = { kind: 'pinch', dist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y), mid, zoom: this.zoom, panX: this.panX, panY: this.panY };
                return;
            }
        }
        if (e.button === 2) return; // context menu handles it
        const start = { x: e.clientX, y: e.clientY };

        if (e.button === 1 || this.tool === 'hand' || this.spaceDown) {
            e.preventDefault();
            this.gesture = { kind: 'pan', start, panX: this.panX, panY: this.panY };
            return;
        }
        if (e.button !== 0) return;

        const handle = e.target.closest('[data-handle]');
        const box = e.target.closest('[data-field]');
        const p = this.toPage(e);

        if (handle && this.one) {
            e.preventDefault();
            const f = this.one;
            this.gesture = { kind: 'resize', dir: handle.dataset.handle, p, orig: { x: f.x, y: f.y, w: f.width, h: f.height }, recorded: false };
            return;
        }

        if (box) {
            e.preventDefault();
            const id = box.dataset.field;
            const additive = e.shiftKey || e.ctrlKey || e.metaKey;
            if (additive) {
                this.sel = this.isSel(id) ? this.sel.filter((s) => s !== id) : [...this.sel, id];
                if (!this.isSel(id)) return;
            } else if (!this.isSel(id)) {
                this.sel = [id];
            }
            this.gesture = {
                kind: 'move', p, start,
                orig: Object.fromEntries(this.selected.map((f) => [f.id, { x: f.x, y: f.y }])),
                box: this.bbox(), recorded: false, moved: false, clickedId: id, additive,
            };
            return;
        }

        // empty paper or the desk around it: marquee
        if (!(e.shiftKey || e.ctrlKey || e.metaKey)) this.sel = [];
        this.gesture = { kind: 'marquee', p, base: [...this.sel] };
        this.marquee = { x: p.x, y: p.y, w: 0, h: 0 };
    },

    onPointerMove(e) {
        if (e.pointerType === 'touch' && this.pointers[e.pointerId]) this.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        const g = this.gesture;
        if (!g) return;

        if (g.kind === 'pinch') {
            const pts = Object.values(this.pointers);
            if (pts.length < 2) return;
            const vp = this.$refs.viewport.getBoundingClientRect();
            const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            const mid = { x: (pts[0].x + pts[1].x) / 2 - vp.left, y: (pts[0].y + pts[1].y) / 2 - vp.top };
            const nz = Math.min(4, Math.max(0.1, g.zoom * dist / g.dist));
            this.zoom = nz;
            this.panX = mid.x - ((g.mid.x - g.panX) / g.zoom) * nz;
            this.panY = mid.y - ((g.mid.y - g.panY) / g.zoom) * nz;
            return;
        }

        if (g.kind === 'pan') {
            this.panX = g.panX + (e.clientX - g.start.x);
            this.panY = g.panY + (e.clientY - g.start.y);
            return;
        }

        const p = this.toPage(e);

        if (g.kind === 'marquee') {
            const x = Math.min(p.x, g.p.x), y = Math.min(p.y, g.p.y);
            this.marquee = { x, y, w: Math.abs(p.x - g.p.x), h: Math.abs(p.y - g.p.y) };
            const m = this.marquee;
            const hit = this.fields.filter((f) => f.x < m.x + m.w && f.x + f.width > m.x && f.y < m.y + m.h && f.y + f.height > m.y).map((f) => f.id);
            this.sel = [...new Set([...g.base, ...hit])];
            return;
        }

        if (g.kind === 'move') {
            let dx = p.x - g.p.x, dy = p.y - g.p.y;
            if (!g.moved && Math.hypot(e.clientX - g.start.x, e.clientY - g.start.y) < 3) return;
            if (!g.recorded) { this.record(); g.recorded = true; }
            g.moved = true;
            if (e.shiftKey) { if (Math.abs(dx) > Math.abs(dy)) dy = 0; else dx = 0; }
            if (!e.altKey) [dx, dy] = this.snapMove(g.box, dx, dy);
            else this.guides = { x: [], y: [] };
            for (const f of this.selected) {
                const o = g.orig[f.id];
                if (!o) continue;
                f.x = Math.round(o.x + dx);
                f.y = Math.round(o.y + dy);
            }
            return;
        }

        if (g.kind === 'resize') {
            const f = this.one;
            if (!f) return;
            if (!g.recorded) { this.record(); g.recorded = true; }
            const dx = p.x - g.p.x, dy = p.y - g.p.y, o = g.orig;
            let { x, y, w, h } = o;
            if (g.dir.includes('e')) w = o.w + dx;
            if (g.dir.includes('s')) h = o.h + dy;
            if (g.dir.includes('w')) { w = o.w - dx; x = o.x + dx; }
            if (g.dir.includes('n')) { h = o.h - dy; y = o.y + dy; }
            if (w < 16) { if (g.dir.includes('w')) x -= 16 - w; w = 16; }
            if (h < 12) { if (g.dir.includes('n')) y -= 12 - h; h = 12; }
            Object.assign(f, { x: Math.round(x), y: Math.round(y), width: Math.round(w), height: Math.round(h) });
        }
    },

    onPointerUp(e) {
        if (e && e.pointerType === 'touch') delete this.pointers[e.pointerId];
        const g = this.gesture;
        if (!g) return;
        if (g.kind === 'pinch') {
            if (Object.keys(this.pointers).length === 0) this.gesture = null;
            return;
        }
        this.gesture = null;
        this.marquee = null;
        this.guides = { x: [], y: [] };
        // a plain click on one of several selected fields selects just that one
        if (g.kind === 'move' && !g.moved && !g.additive && this.sel.length > 1) this.sel = [g.clickedId];
        if ((g.kind === 'move' && g.moved) || (g.kind === 'resize' && g.recorded)) this.changed();
    },

    /** Snap the moving box's edges and centre to the page and the other fields. */
    snapMove(box, dx, dy) {
        const tol = SNAP / this.zoom;
        const others = this.fields.filter((f) => !this.sel.includes(f.id));
        const xs = [0, this.page.width / 2, this.page.width, ...others.flatMap((f) => [f.x, f.x + f.width / 2, f.x + f.width])];
        const ys = [0, this.page.height / 2, this.page.height, ...others.flatMap((f) => [f.y, f.y + f.height / 2, f.y + f.height])];
        const best = (edges, targets) => {
            let hit = null;
            for (const e of edges) for (const t of targets) {
                const d = t - e;
                if (Math.abs(d) <= tol && (!hit || Math.abs(d) < Math.abs(hit.d))) hit = { d, t };
            }
            return hit;
        };
        const nx = box.x + dx, ny = box.y + dy;
        const sx = best([nx, nx + box.w / 2, nx + box.w], xs);
        const sy = best([ny, ny + box.h / 2, ny + box.h], ys);
        this.guides = { x: sx ? [sx.t] : [], y: sy ? [sy.t] : [] };
        return [dx + (sx ? sx.d : 0), dy + (sy ? sy.d : 0)];
    },

    onContextMenu(e) {
        const box = e.target.closest('[data-field]');
        if (box && !this.isSel(box.dataset.field)) this.sel = [box.dataset.field];
        const r = this.$refs.viewport.getBoundingClientRect();
        this.menu = { x: e.clientX - r.left, y: e.clientY - r.top, at: this.toPage(e) };
    },

    // ── keyboard ────────────────────────────────────────────────────────────
    onKey(e) {
        if (this.tour !== null) {
            if (e.key === 'Escape') { e.preventDefault(); this.endTour(); }
            else if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); this.tourNext(); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); this.tourBack(); }
            return;
        }
        if (isTyping(document.activeElement)) return;
        const mod = e.ctrlKey || e.metaKey;
        const k = e.key.toLowerCase();

        if (e.code === 'Space') { this.spaceDown = true; e.preventDefault(); return; }
        if (mod && k === 'z') { e.preventDefault(); e.shiftKey ? this.redo() : this.undo(); return; }
        if (mod && k === 'y') { e.preventDefault(); this.redo(); return; }
        if (mod && k === 'a') { e.preventDefault(); this.sel = this.fields.map((f) => f.id); return; }
        if (mod && k === 'c') { e.preventDefault(); this.copy(); return; }
        if (mod && k === 'x') { e.preventDefault(); this.cut(); return; }
        if (mod && k === 'v') { e.preventDefault(); this.paste(); return; }
        if (mod && k === 'd') { e.preventDefault(); this.duplicate(); return; }
        if (mod && k === 'g') { e.preventDefault(); e.shiftKey ? this.ungroup() : this.openGroupDialog(); return; }
        if (mod && k === '0') { e.preventDefault(); this.fit(); return; }
        if (mod && k === '1') { e.preventDefault(); this.zoomTo(1); return; }
        if (mod && (k === '=' || k === '+')) { e.preventDefault(); this.zoomBy(1.15); return; }
        if (mod && k === '-') { e.preventDefault(); this.zoomBy(1 / 1.15); return; }
        if (mod) return;

        if (k === 'delete' || k === 'backspace') { e.preventDefault(); this.remove(); return; }
        if (k === 'escape') { this.sel = []; this.menu = null; return; }
        if (k === 'v') { this.tool = 'select'; return; }
        if (k === 'h') { this.tool = 'hand'; return; }
        const arrows = { arrowleft: [-1, 0], arrowright: [1, 0], arrowup: [0, -1], arrowdown: [0, 1] };
        if (arrows[k] && !this.sel.length) {
            // nothing selected: the arrows scroll the page (left and right scroll sideways)
            e.preventDefault();
            const step = e.shiftKey ? 160 : 40;
            this.panX -= arrows[k][0] * step;
            this.panY -= arrows[k][1] * step;
            return;
        }
        if (arrows[k] && this.sel.length) {
            e.preventDefault();
            const step = e.shiftKey ? 10 : 1;
            this.record('nudge');
            for (const f of this.selected) { f.x += arrows[k][0] * step; f.y += arrows[k][1] * step; }
            this.changed();
        }
    },

    // ── history ─────────────────────────────────────────────────────────────
    /** Remember the state before a change. Repeated changes with the same key within a second count as one. */
    record(key = null) {
        const now = Date.now();
        if (key && key === this.lastRecordKey && now - this.lastRecordAt < 1000) { this.lastRecordAt = now; return; }
        this.past.push(JSON.stringify(this.fields));
        if (this.past.length > 100) this.past.shift();
        this.future = [];
        this.lastRecordKey = key;
        this.lastRecordAt = now;
    },
    undo() {
        if (!this.past.length) return;
        this.future.push(JSON.stringify(this.fields));
        this.fields = JSON.parse(this.past.pop());
        this.sel = this.sel.filter((id) => this.byId(id));
        this.lastRecordKey = null;
        this.changed();
    },
    redo() {
        if (!this.future.length) return;
        this.past.push(JSON.stringify(this.fields));
        this.fields = JSON.parse(this.future.pop());
        this.sel = this.sel.filter((id) => this.byId(id));
        this.lastRecordKey = null;
        this.changed();
    },

    // ── editing ─────────────────────────────────────────────────────────────
    /** Change a property on every selected field. */
    set(key, value, recordKey = null) {
        if (!this.sel.length) return;
        // A number field's sample is digits only
        if (key === 'placeholder' && typeof value === 'string') {
            const nums = this.selected.every((f) => f.type === 'number');
            if (nums) value = value.replace(/[^0-9]/g, '') || null;
        }
        this.record(recordKey ?? `set:${key}:${this.sel.join(',')}`);
        for (const f of this.selected) {
            if (this.isLocked(f) && ['source_type', 'system_key', 'name', 'type'].includes(key)) continue;
            f[key] = value;
        }
        this.changed();
    },
    setNumber(key, raw, min = null, max = null) {
        if (raw === '' || raw === null) return;
        let v = Number(raw);
        if (Number.isNaN(v)) return;
        if (min !== null) v = Math.max(min, v);
        if (max !== null) v = Math.min(max, v);
        this.set(key, v);
    },
    setType(type) {
        this.record();
        for (const f of this.selected) {
            if (this.isLocked(f)) continue;
            f.type = type;
            if (f.source_type === 'system' && !this.systemOptions(type).some((o) => o.key === f.system_key)) {
                f.source_type = 'input';
                f.system_key = null;
                f.name = this.uniqueName('field');
            }
        }
        this.changed();
    },
    setSource(source) {
        const f = this.one;
        if (!f || this.isLocked(f)) return;
        this.record();
        if (source === 'system') {
            const first = this.systemOptions(f.type)[0]?.key ?? null;
            if (!first) { this.notify('No system value fits this field type'); this.past.pop(); return; }
            Object.assign(f, { source_type: 'system', system_key: first, name: first, placeholder: null });
        } else {
            Object.assign(f, { source_type: 'input', system_key: null, name: this.uniqueName(f.text || 'field') });
        }
        this.changed();
    },
    setSystemKey(key) {
        const f = this.one;
        if (!f) return;
        this.record();
        Object.assign(f, { system_key: key, name: key });
        this.changed();
    },
    toggleRequired() {
        const all = this.selected.every((f) => f.required);
        this.set('required', !all, 'required');
    },

    uniqueName(base) {
        const slug = String(base || 'field').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'field';
        const taken = new Set(this.fields.map((f) => f.name));
        if (!taken.has(slug)) return slug;
        let i = 2;
        while (taken.has(`${slug}_${i}`)) i++;
        return `${slug}_${i}`;
    },

    /** Add a field at the centre of what is on screen (or at a point on the page). */
    /** The Automatic menu: every profile detail, grouped, plus the current term. */
    get automaticGroups() {
        const keys = Object.keys(this.systemVariables || {});
        const pick = (list) => list.filter((k) => keys.includes(k)).map((k) => ({ key: k, label: this.systemLabel(k) }));
        const term = ['current_semester', 'current_academic_year'];
        const name = ['full_name', 'formatted_full_name', 'first_name', 'middle_name', 'last_name', 'suffix'];
        const school = ['student_number', 'college', 'program', 'program_year_section', 'year_level', 'section', 'organization', 'academic_status'];
        const rest = keys.filter((k) => ![...term, ...name, ...school].includes(k));
        return [
            { title: 'Name', items: pick(name) },
            { title: 'School', items: pick(school) },
            { title: 'Personal and contact', items: pick(rest) },
            { title: 'This term', items: pick(term) },
        ].filter((g) => g.items.length);
    },
    add(kind, at = null) {
        const legacy = { current_semester: 'current_semester', current_school_year: 'current_academic_year' };
        const sysKey = kind.startsWith('sys:') ? kind.slice(4) : legacy[kind];
        const sysType = DATE_KEYS.includes(sysKey) ? 'date' : (NUMBER_KEYS.includes(sysKey) ? 'number' : 'text');
        const f = blankField(sysKey ? sysType : kind);
        if (sysKey) {
            Object.assign(f, { source_type: 'system', system_key: sysKey, name: sysKey, text: this.systemLabel(sysKey) });
        } else {
            f.name = this.uniqueName(TYPES[kind].label);
        }
        const c = at ?? this.viewCenter();
        f.x = Math.round(Math.max(0, Math.min(this.page.width - f.width, c.x - f.width / 2)));
        f.y = Math.round(Math.max(0, Math.min(this.page.height - f.height, c.y - f.height / 2)));
        this.record();
        this.fields.push(f);
        this.sel = [f.id];
        this.tool = 'select';
        this.changed();
    },
    viewCenter() {
        const r = this.$refs.viewport.getBoundingClientRect();
        return { x: (r.width / 2 - this.panX) / this.zoom, y: (r.height / 2 - this.panY) / this.zoom };
    },

    remove() {
        if (!this.sel.length) return;
        this.record();
        const n = this.sel.length;
        this.fields = this.fields.filter((f) => !this.sel.includes(f.id));
        this.sel = [];
        this.changed();
        this.notify(`${n} ${n === 1 ? 'field' : 'fields'} deleted · Ctrl+Z to undo`);
    },

    copy() {
        if (!this.sel.length) return;
        const items = clone(this.selected);
        try { localStorage.setItem(CLIPBOARD_KEY, JSON.stringify({ items, at: Date.now() })); } catch (_) { /* storage full or blocked */ }
        this._clipboard = items;
        this.pasteCount = 0;
        this.notify(`Copied ${items.length} ${items.length === 1 ? 'field' : 'fields'}`);
    },
    cut() { this.copy(); this.remove(); },
    clipboardItems() {
        try {
            const saved = JSON.parse(localStorage.getItem(CLIPBOARD_KEY) || 'null');
            if (saved?.items?.length) return saved.items; // works across templates and tabs
        } catch (_) { /* ignore */ }
        return this._clipboard || [];
    },
    /** Paste below and to the right of the copies (each paste steps further), or at a point. */
    paste(at = null) {
        const items = this.clipboardItems();
        if (!items.length) return;
        this.record();
        this.pasteCount++;
        const box = this.bbox(items);
        const off = at ? { x: at.x - box.x, y: at.y - box.y } : { x: 16 * this.pasteCount, y: 16 * this.pasteCount };
        const added = items.map((src) => this.copyOf(src, off));
        this.fields.push(...added);
        this.sel = added.map((f) => f.id);
        this.changed();
    },
    duplicate() {
        if (!this.sel.length) return;
        this.record();
        const added = this.selected.map((src) => this.copyOf(src, { x: 16, y: 16 }));
        this.fields.push(...added);
        this.sel = added.map((f) => f.id);
        this.changed();
    },
    copyOf(src, off) {
        const f = { ...clone(src), id: tmpId(), x: Math.round(src.x + off.x), y: Math.round(src.y + off.y) };
        if (f.source_type === 'input') f.name = this.uniqueName(src.name || f.text);
        return f;
    },

    // ── arrange ─────────────────────────────────────────────────────────────
    align(edge) {
        const list = this.selected;
        if (!list.length) return;
        // one field aligns to the page; several align to their shared box
        const b = list.length > 1 ? this.bbox() : { x: 0, y: 0, w: this.page.width, h: this.page.height };
        this.record();
        for (const f of list) {
            if (edge === 'left') f.x = b.x;
            if (edge === 'center') f.x = Math.round(b.x + (b.w - f.width) / 2);
            if (edge === 'right') f.x = b.x + b.w - f.width;
            if (edge === 'top') f.y = b.y;
            if (edge === 'middle') f.y = Math.round(b.y + (b.h - f.height) / 2);
            if (edge === 'bottom') f.y = b.y + b.h - f.height;
        }
        this.changed();
    },
    distribute(axis) {
        const list = [...this.selected];
        if (list.length < 3) return;
        this.record();
        const [pos, size] = axis === 'x' ? ['x', 'width'] : ['y', 'height'];
        list.sort((a, b) => a[pos] - b[pos]);
        const first = list[0], last = list[list.length - 1];
        const total = list.reduce((s, f) => s + f[size], 0);
        const gap = (last[pos] + last[size] - first[pos] - total) / (list.length - 1);
        let cur = first[pos];
        for (const f of list) { f[pos] = Math.round(cur); cur += f[size] + gap; }
        this.changed();
    },
    matchSize(dim) {
        const list = this.selected;
        if (list.length < 2) return;
        this.record();
        const key = dim === 'w' ? 'width' : 'height';
        const v = list[0][key]; // the first one picked
        for (const f of list) f[key] = v;
        this.changed();
    },
    order(where) {
        if (!this.sel.length) return;
        this.record();
        const picked = this.selected;
        const rest = this.fields.filter((f) => !this.sel.includes(f.id));
        this.fields = where === 'front' ? [...rest, ...picked] : [...picked, ...rest];
        this.changed();
    },
    /** Right-click › Group: put the selected fields in one group (asked together on the student's form). */
    openGroupDialog() {
        if (!this.sel.length) return;
        this.groupDialog = { name: this.shared('group_name') || '' };
        this.menu = null;
        this.$nextTick(() => document.getElementById('group-name')?.focus());
    },
    applyGroup() {
        const name = (this.groupDialog?.name || '').trim();
        if (!name) return;
        this.set('group_name', name, 'group');
        const n = this.sel.length;
        this.groupDialog = null;
        this.notify(`${n} ${n === 1 ? 'field' : 'fields'} grouped as "${name}"`);
    },
    ungroup() {
        this.set('group_name', null, 'group');
        this.menu = null;
    },
    selectGroup(name) {
        this.sel = this.fields.filter((f) => f.group_name === name).map((f) => f.id);
    },

    // ── saving ──────────────────────────────────────────────────────────────
    changed() {
        this.saveState = 'dirty';
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => this.save(), 700);
    },
    async save() {
        if (this.saving) { this.saveAgain = true; return; }
        this.saving = true;
        this.saveAgain = false;
        this.saveState = 'saving';
        try {
            const ids = await this.$wire.saveFields(clone(this.fields), this.name);
            this.adoptIds(ids || {});
            this.saveState = this.saveAgain ? 'dirty' : 'saved';
        } catch (err) {
            console.error(err);
            this.saveState = 'error';
        } finally {
            this.saving = false;
            if (this.saveAgain) this.save();
        }
    },
    /** Swap "tmp-…" ids for the ids the server gave, everywhere they appear. */
    adoptIds(map) {
        const pairs = Object.entries(map);
        if (!pairs.length) return;
        for (const f of this.fields) if (map[f.id]) f.id = String(map[f.id]);
        this.sel = this.sel.map((id) => (map[id] ? String(map[id]) : id));
        const swap = (s) => pairs.reduce((acc, [t, real]) => acc.split(`"id":"${t}"`).join(`"id":"${real}"`), s);
        this.past = this.past.map(swap);
        this.future = this.future.map(swap);
    },
    saveDescription(value) {
        this.description = value;
        clearTimeout(this._descTimer);
        this._descTimer = setTimeout(() => this.$wire.saveDescription(this.description), 500);
    },
    rename(value) {
        this.name = value;
        this.changed();
    },
    /** The student's view of this form, in a new tab, after saving what is on screen. */
    async openPreview(url) {
        const tab = window.open('', '_blank');
        clearTimeout(this.saveTimer);
        if (this.saveState !== 'saved') await this.save();
        if (tab) tab.location.href = url; else window.location.href = url;
    },
    async toggleStatus() {
        this.status = await this.$wire.toggleStatus();
        this.notify(this.status === 'active' ? 'Public: students can use this form' : 'Private: hidden from students');
    },

    // ── paper and background ────────────────────────────────────────────────
    setPaper(size, orientation, w = null, h = null) {
        this.$wire.setPaper(size, orientation, w, h);
    },
    async pickBackground(e) {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;
        if (file.type === 'application/pdf' || /\.pdf$/i.test(file.name)) {
            await this.openPdf(file);
        } else {
            // The paper follows the picture's shape
            const paper = await window.DocumatePaper.fromImage(file);
            if (paper && (paper.size !== this.paper.size || paper.orientation !== this.paper.orientation)) {
                this.setPaper(paper.size, paper.orientation, paper.widthCm, paper.heightCm);
                this.notify(`Page size set to ${paper.size === 'Custom' ? `${paper.widthCm} × ${paper.heightCm} cm` : paper.size}, ${paper.orientation}`);
            }
            this.uploadBackground(file);
        }
    },
    uploadBackground(file) {
        this.notify('Uploading…');
        this.$wire.upload('image', file, () => {}, () => this.notify('Upload failed. Use an image under 2 MB.'));
    },
    async openPdf(file) {
        this.pdfError = '';
        this.pdfBusy = true;
        try {
            this.pdf?.destroy();
            this.pdf = await window.DocumatePdf.open(file);
            this.pdfPages = [];
            for (let n = 1; n <= Math.min(this.pdf.numPages, 24); n++) {
                this.pdfPages.push({ n, src: await this.pdf.thumbnail(n, 140) });
            }
            if (this.pdf.numPages === 1) await this.usePdfPage(1);
        } catch (err) {
            console.error(err);
            this.pdfError = 'This PDF could not be read.';
        } finally {
            this.pdfBusy = false;
        }
    },
    async usePdfPage(n) {
        this.pdfBusy = true;
        try {
            const paper = await this.pdf.paper(n);
            const file = await this.pdf.toImageFile(n);
            const same = paper.size === this.paper.size && paper.orientation === this.paper.orientation;
            if (!same) this.setPaper(paper.size, paper.orientation, paper.widthCm, paper.heightCm);
            this.uploadBackground(file);
            this.closePdf();
            if (!same) this.notify(`Page size set to ${paper.size === 'Custom' ? `${paper.widthCm} × ${paper.heightCm} cm` : paper.size}, ${paper.orientation}`);
        } catch (err) {
            this.pdfError = err.message || 'This page could not be converted.';
        } finally {
            this.pdfBusy = false;
        }
    },
    closePdf() {
        this.pdf?.destroy();
        this.pdf = null;
        this.pdfPages = [];
    },

    // ── instructions ────────────────────────────────────────────────────────
    async addStep() {
        const text = this.stepDraft.trim();
        if (!text || this.stepBusy) return;
        this.stepBusy = true;
        try {
            const step = await this.$wire.addInstruction(text);
            if (step) this.steps.push(step);
            this.stepDraft = '';
        } catch (err) {
            console.error(err);
            this.notify('That step was not saved. Try again.');
        } finally {
            this.stepBusy = false;
        }
    },
    editStep(step) {
        this.stepEditing = step.instruction_id;
        this.$nextTick(() => {
            const el = document.getElementById('step-edit-' + step.instruction_id);
            if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }
        });
    },
    saveStep(step, text) {
        this.stepEditing = null;
        text = (text || '').trim();
        if (!text || text === step.description) return false;
        step.description = text;
        this.$wire.updateInstruction(step.instruction_id, text);
        this.notify('Step saved');
        return true;
    },
    removeStep(step) {
        this.steps = this.steps.filter((x) => x.instruction_id !== step.instruction_id);
        this.$wire.deleteInstruction(step.instruction_id);
    },
    moveStep(i, dir) {
        const j = i + dir;
        if (j < 0 || j >= this.steps.length) return;
        const list = [...this.steps];
        [list[i], list[j]] = [list[j], list[i]];
        this.steps = list;
        this.$wire.reorderInstructions(this.steps.map((x) => x.instruction_id));
    },
    stepDrop(i) {
        const from = this.stepDrag;
        this.stepDrag = null;
        this.stepOver = null;
        if (from === null || from === i) return;
        const list = [...this.steps];
        const [moved] = list.splice(from, 1);
        list.splice(i, 0, moved);
        this.steps = list;
        this.$wire.reorderInstructions(this.steps.map((x) => x.instruction_id));
    },

    // ── guided tour ─────────────────────────────────────────────────────────
    startTour() {
        this.menu = null;
        this.mobilePanel = null;
        this.tour = 0;
        this.$nextTick(() => this.placeTour());
    },
    tourNext() {
        if (this.tour === null) return;
        if (this.tour >= this.tourSteps.length - 1) { this.endTour(); return; }
        this.tour++;
        this.$nextTick(() => this.placeTour());
    },
    tourBack() {
        if (!this.tour) return;
        this.tour--;
        this.$nextTick(() => this.placeTour());
    },
    endTour() {
        this.tour = null;
        this.tourRect = null;
        try { localStorage.setItem(TOUR_KEY, '1'); } catch (_) { /* ignore */ }
    },
    /** Measure the highlighted part; hidden parts (on phones) get a centred card instead of a spotlight. */
    placeTour() {
        if (this.tour === null) return;
        const step = this.tourSteps[this.tour];
        if (step.target === 'steps' && this.rightTab !== 'steps') this.rightTab = 'design';
        const el = [...document.querySelectorAll(`[data-tour="${step.target}"]`)].find((x) => x.getBoundingClientRect().width > 0);
        const r = el?.getBoundingClientRect();
        this.tourRect = r ? { x: r.left, y: r.top, w: r.width, h: r.height } : null;
    },
    /** The card sits beside the highlighted part, kept inside the screen. */
    get tourCardStyle() {
        const W = Math.min(320, window.innerWidth - 24), H = 190, pad = 12, r = this.tourRect;
        if (!r) return `left:${(window.innerWidth - W) / 2}px;top:${Math.max(pad, (window.innerHeight - H) / 2)}px;width:${W}px`;
        let left, top;
        if (r.y + r.h + H + pad < window.innerHeight) { top = r.y + r.h + pad; left = r.x; }
        else if (r.y - H - pad > 0) { top = r.y - H - pad; left = r.x; }
        else if (r.x + r.w + W + pad < window.innerWidth) { left = r.x + r.w + pad; top = r.y; }
        else { left = r.x - W - pad; top = r.y; }
        left = Math.max(pad, Math.min(left, window.innerWidth - W - pad));
        top = Math.max(pad, Math.min(top, window.innerHeight - H - pad));
        return `left:${left}px;top:${top}px;width:${W}px`;
    },

    notify(text) {
        this.toast = text;
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => { this.toast = null; }, 2600);
    },
});
