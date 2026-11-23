/**
 * Fill in a transaction right on its printed page.
 *
 * The page is the real PDF layout (the component's previewDocument(), the same HTML Dompdf prints), shown in an
 * iframe scaled to the screen. The PDF templates mark each printed blank with data-f="<field>" (several fields:
 * space-separated). Tapping a blank opens that field's editor (<x-doc.pop>): beside the blank on wide screens, as a
 * sheet at the foot of the screen on phones. As you type, the page is drawn again, so what you see is what prints.
 *
 * Two iframes take turns (one shown, one loading) so redrawing never flashes. Fields the page does not print
 * stay in the list under the page.
 *
 * x-data="pdfFill({ editable: true, placed: ['subject', ...] })" on the element holding both the page
 * (<x-doc.pdf-page>) and the editors.
 */
export default function pdfFill(config = {}) {
    return {
        editable: !!config.editable,
        placed: Object.fromEntries((config.placed || []).map((n) => [n, true])),
        // field => the field that must be answered first for it to show (a tick box, a choice)
        deps: config.deps || {},
        active: null,
        order: [],
        pop: { top: 0, left: 0 },
        small: false,
        ready: false,
        busy: false,
        front: 0,
        seq: 0,
        w: 842,
        h: 1171,
        scale: 1,
        offset: 0,
        _off: [],

        init() {
            // Kept here: methods also run from scopes nested inside (a field's own x-data), where $root/$refs differ.
            this._root = this.$el;
            const mq = window.matchMedia('(max-width: 767px)');
            this.small = mq.matches;
            const onMq = () => { this.small = mq.matches; this.position(); };
            mq.addEventListener('change', onMq);

            // Any scrolling (the app's content panel scrolls, not the window) moves the open editor along with its blank.
            let raf = 0;
            const onScroll = () => { if (!this.active || raf) return; raf = requestAnimationFrame(() => { raf = 0; this.position(); }); };
            const onResize = () => { this.fit(); this.position(); };
            const onMsg = (e) => {
                const f = this.frame(this.front);
                if (f && e.source === f.contentWindow && e.data && e.data.documatePreviewHeight) {
                    this.h = Math.ceil(e.data.documatePreviewHeight);
                }
            };
            document.addEventListener('scroll', onScroll, { passive: true, capture: true });
            window.addEventListener('resize', onResize);
            window.addEventListener('message', onMsg);
            this._off = [
                () => mq.removeEventListener('change', onMq),
                () => document.removeEventListener('scroll', onScroll, { capture: true }),
                () => window.removeEventListener('resize', onResize),
                () => window.removeEventListener('message', onMsg),
            ];

            // Anything else that changes the answers on the server (a picked student, a live select, a failed
            // submit) redraws the page too. Our own preview call is skipped.
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                const off = window.Livewire.hook('commit', ({ component, commit, succeed }) => {
                    if (component.id !== this.$wire.$id) return;
                    const mine = (commit?.calls || []).some((c) => c.method === 'previewDocument');
                    if (mine) return;
                    succeed(() => this.$nextTick(() => this.refresh()));
                });
                if (typeof off === 'function') this._off.push(off);
            }

            this.$nextTick(() => {
                this.fit();
                const box = this._root.querySelector('[data-page-box]');
                if (box) new ResizeObserver(() => this.fit()).observe(box);
                this.refresh();
            });
        },

        destroy() {
            this._off.forEach((off) => off());
        },

        frame(i) {
            return this._root.querySelector(`[data-frame="${i}"]`);
        },

        /** True when the list under the page has something to show (fields the page does not print). */
        get hasExtra() {
            const box = this._root && this._root.querySelector('[data-extra]');
            if (!box) return false;
            return [...box.querySelectorAll('[data-editor]')].some((e) => !this.placed[e.dataset.editor])
                || !!box.querySelector('[data-always]');
        },

        fit() {
            const box = this._root.querySelector('[data-page-box]');
            if (!box || !box.clientWidth) return;
            this.scale = Math.min(1, box.clientWidth / this.w);
            this.offset = Math.max(0, (box.clientWidth - this.w * this.scale) / 2);
        },

        /** Draw the page again from the current answers (the shown frame stays until the new one has loaded). */
        async refresh() {
            const n = ++this.seq;
            this.busy = true;
            let html;
            try {
                html = await this.$wire.previewDocument();
            } catch (e) {
                this.busy = false;
                return;
            }
            if (n !== this.seq) return;

            const back = this.frame(1 - this.front);
            if (!back) return;
            back.onload = () => {
                if (n !== this.seq) return;
                const doc = back.contentDocument;
                if (doc) this.h = Math.ceil(doc.documentElement.scrollHeight) || this.h;
                this.decorate(back);
                this.front = 1 - this.front;
                this.ready = true;
                this.busy = false;
                this.$nextTick(() => this.position());
            };
            back.srcdoc = html;
        },

        /** Which fields have an editor here (some printed blanks belong to someone else, e.g. staff). */
        editors() {
            return new Set([...this._root.querySelectorAll('[data-editor]')].map((el) => el.dataset.editor));
        },

        errorNames() {
            try {
                return JSON.parse(this._root.querySelector('[data-errors]')?.dataset.errors || '[]');
            } catch (e) {
                return [];
            }
        },

        decorate(frame) {
            const doc = frame.contentDocument;
            if (!doc) return;
            const editors = this.editors();
            const errors = new Set(this.errorNames());
            const placed = {};
            const order = [];

            doc.querySelectorAll('[data-f]').forEach((el) => {
                const names = el.dataset.f.split(/\s+/).filter((n) => editors.has(n));
                if (!names.length || !this.editable) return;
                names.forEach((n) => {
                    placed[n] = true;
                    if (!order.includes(n)) order.push(n);
                });
                el.classList.add('dm-f');
                if (names.some((n) => errors.has(n))) el.classList.add('dm-err');
                if (names.includes(this.active)) el.classList.add('dm-on');
                el.setAttribute('role', 'button');
                el.setAttribute('tabindex', '0');
                el.setAttribute('aria-label', 'Fill in');
                const go = (e) => { e.preventDefault(); this.open(names[0]); };
                el.addEventListener('click', go);
                el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') go(e); });
            });

            if (this.editable) {
                const style = doc.createElement('style');
                style.textContent = `
                    .dm-f{cursor:pointer;background:rgba(42,87,180,.07);box-shadow:inset 0 0 0 1px rgba(42,87,180,.38);border-radius:1.5px;transition:background-color .15s,box-shadow .15s;outline:none}
                    .dm-f:hover,.dm-f:focus-visible{background:rgba(42,87,180,.15)}
                    .dm-f.dm-on{background:rgba(42,87,180,.2);box-shadow:inset 0 0 0 2px #2A57B4}
                    .dm-f.dm-err{background:rgba(225,29,72,.12);box-shadow:inset 0 0 0 1.5px #e11d48}
                    span.dm-f:empty{display:inline-block;min-width:60pt;min-height:10pt}
                    tr.dm-f td{background:rgba(42,87,180,.05)}
                `;
                doc.head.appendChild(style);
            }

            this.placed = placed;
            this.order = order;
        },

        /** The editors that can be stepped through now (hidden ones, e.g. "others" not ticked, are skipped). */
        stops() {
            const editors = this.editors();
            return this.order.filter((n) => editors.has(n) && !this.hiddenNow(n));
        },

        /** True when the field's editor is hidden by its own condition (e.g. "others" not ticked). */
        hiddenNow(name) {
            const ed = this._root.querySelector(`[data-editor="${CSS.escape(name)}"]`);
            const inner = ed && [...ed.children].find((c) => c.matches('[wire\\:key], [data-field]'));
            return !!inner && inner.style.display === 'none';
        },

        hasStep(d) {
            const list = this.stops();
            const i = list.indexOf(this.active);
            return i !== -1 && i + d >= 0 && i + d < list.length;
        },

        step(d) {
            const list = this.stops();
            const i = list.indexOf(this.active);
            const next = list[i + d];
            if (next) this.open(next);
        },

        counter(name) {
            const list = this.stops();
            const i = list.indexOf(name);
            return i === -1 ? '' : `${i + 1} of ${list.length}`;
        },

        /** Phones: walk through the blanks from the first empty one. */
        start() {
            const list = this.stops();
            if (!list.length) return;
            const doc = this.frame(this.front)?.contentDocument;
            const empty = doc && list.find((n) => {
                const el = doc.querySelector(`[data-f~="${CSS.escape(n)}"]`);
                return el && el.textContent.replace(/ /g, ' ').trim() === '';
            });
            this.open(empty || list[0]);
        },

        open(name) {
            // A blank that only counts once something else is answered (a tick box): open that first.
            if (this.hiddenNow(name) && this.deps[name] && this.editors().has(this.deps[name])) name = this.deps[name];
            this.active = name;
            this.mark();
            this.$nextTick(() => {
                const ed = this._root.querySelector(`[data-editor="${CSS.escape(name)}"]`);
                // Still closed off by a question in the list (e.g. "Are you an officer?" hides the details): go answer that first.
                let shut = false;
                for (let p = ed && ed.parentElement; p && p !== this._root; p = p.parentElement) {
                    if (p.style.display === 'none') { shut = true; break; }
                }
                if (shut) {
                    this.close();
                    const ask = this._root.querySelector('[data-extra] [data-always] input, [data-extra] [data-always] select');
                    if (ask) { ask.scrollIntoView({ block: 'center', behavior: 'smooth' }); ask.focus({ preventScroll: true }); }
                    return;
                }
                // Focus the answer: right away when the editor is already showing (phones open the keyboard only
                // inside the tap), else once it has been drawn.
                const focus = () => {
                    const field = ed && [...ed.querySelectorAll('input:not([type=hidden]):not([disabled]), select, textarea')].find((el) => el.getClientRects().length);
                    if (field) field.focus({ preventScroll: true });
                    return !!field;
                };
                this.position(true);
                if (!focus()) requestAnimationFrame(() => { this.position(true); focus(); });
            });
        },

        close() {
            this.active = null;
            this.mark();
        },

        mark() {
            const doc = this.frame(this.front)?.contentDocument;
            if (!doc) return;
            doc.querySelectorAll('.dm-on').forEach((el) => el.classList.remove('dm-on'));
            if (this.active) {
                doc.querySelectorAll(`[data-f~="${CSS.escape(this.active)}"]`).forEach((el) => el.classList.add('dm-on'));
            }
        },

        /** Where the open editor goes: under (or over) its blank on wide screens; on phones the page scrolls the blank into view. */
        position(scroll = false) {
            if (!this.active || !this.placed[this.active]) return;
            const frame = this.frame(this.front);
            const doc = frame?.contentDocument;
            const el = doc && doc.querySelector(`[data-f~="${CSS.escape(this.active)}"]`);
            const ed = this._root.querySelector(`[data-editor="${CSS.escape(this.active)}"]`);
            if (!el || !ed) return;

            const fr = frame.getBoundingClientRect();
            const r = el.getBoundingClientRect();
            const top = fr.top + r.top * this.scale;
            const bottom = fr.top + r.bottom * this.scale;
            const left = fr.left + r.left * this.scale;

            // Opening: bring the blank into view first (it then sits near the top on phones, leaving room
            // for the editor and the keyboard under it). The scroll listener moves the editor along.
            if (scroll) {
                const target = this.small ? 84 : window.innerHeight / 3;
                const offScreen = top < 72 || bottom > window.innerHeight - 72;
                if (offScreen || (this.small && Math.abs(top - target) > 48)) {
                    this.scroller().scrollBy({ top: top - target, behavior: 'smooth' });
                }
            }

            // The editor stays attached to its blank: just under it (over it when there is no room below),
            // and moves with the page as it scrolls. Phones: the full width of the screen.
            const vw = document.documentElement.clientWidth;
            const width = this.small ? vw - 16 : (ed.offsetWidth || 368);
            const height = ed.offsetHeight || 200;
            let y = bottom + 8;
            if (!this.small && y + height > window.innerHeight - 8 && top - height - 8 >= 8) y = top - height - 8;
            const x = this.small ? 8 : Math.max(12, Math.min(left, vw - width - 12));
            this.pop = { top: Math.round(y), left: Math.round(x), width: this.small ? Math.round(width) : null };
        },

        /** What scrolls the page: the app scrolls its content panel, not the window. */
        scroller() {
            for (let p = this._root.parentElement; p; p = p.parentElement) {
                const oy = getComputedStyle(p).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && p.scrollHeight > p.clientHeight) return p;
            }
            return document.scrollingElement || document.documentElement;
        },
    };
}
