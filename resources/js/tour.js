/**
 * A guided tour: a spotlight on one part of the page at a time, with a card that explains it.
 * Used by <x-dm.tour> (resources/views/components/dm/tour.blade.php), the same look as the template editor's tour.
 *
 * steps: [{ target, title, text, ... }]
 *   target   a data-tour="…" name, a CSS selector (starting with # . or [), or null for a centred card.
 *            Steps whose part isn't on the page are left out (checked again when the step is reached).
 *   unless   a target: the step is left out when that part is on screen (e.g. the menu button when the sidebar shows).
 *   tab      'overview' | 'explore': the page switches to that tab first (it listens for the 'tour-page' window event).
 *   preview  'first' opens a sample form's preview first; null closes it.
 *   sidebar  true: the part is in the sidebar, so on phones the sidebar slides open for it.
 *   type     text the tour types into the target, as a person would (the field is cleared when the step is left).
 *   click    true: Next clicks the target for the user (e.g. the Explore button), then moves on.
 *   follow   true (with click): the click opens another page, and that page's tour carries on there.
 *   cta      { label, event } shows a button on that step that ends the tour and dispatches the window event.
 * Starts on its own the first time (auto), when the previous page handed over (follow), and on a 'start-tour' window event.
 */
const FOLLOW_KEY = 'documate.tour.follow';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export default (key, steps = [], auto = true) => ({
    allSteps: steps,
    steps: [],
    i: null,
    rect: null,
    ready: false,      // the card shows only once it is placed, so it never stops in the middle of the screen first
    busy: false,
    typed: null,       // { el, value } of a field the tour typed into
    openedSidebar: false,

    init() {
        this._place = () => this.place();
        window.addEventListener('resize', this._place);
        window.addEventListener('scroll', this._place, true);
        let followed = false;
        try {
            const at = +sessionStorage.getItem(FOLLOW_KEY);
            if (at && Date.now() - at < 20000) { followed = true; sessionStorage.removeItem(FOLLOW_KEY); }
        } catch (_) { /* storage blocked */ }
        if (followed) setTimeout(() => this.start(), 700);
        else if (auto) {
            try { if (!localStorage.getItem(key)) setTimeout(() => this.start(), 900); } catch (_) { /* storage blocked */ }
        }
    },
    destroy() {
        window.removeEventListener('resize', this._place);
        window.removeEventListener('scroll', this._place, true);
        this.cleanup();
    },

    sel(target) { return /^[#.[]/.test(target) ? target : `[data-tour="${target}"]`; },
    find(target) {
        if (!target) return null;
        return [...document.querySelectorAll(this.sel(target))].find((el) => {
            const r = el.getBoundingClientRect();
            return r.width > 0 && r.height > 0 && r.right > 0 && r.left < window.innerWidth && getComputedStyle(el).visibility !== 'hidden';
        }) || null;
    },
    exists(target) { return !target || !!document.querySelector(this.sel(target)); },

    // ── the sidebar on phones ────────────────────────────────────────────
    sidebarHidden() { const s = document.getElementById('sidebar'); return !!s && !this.find('#sidebar'); },
    setSidebar(open) {
        const s = document.getElementById('sidebar'), o = document.getElementById('sidebarOverlay');
        if (!s) return;
        s.classList.toggle('mobile-open', open);
        o?.classList.toggle('active', open);
    },

    start() {
        if (this.busy) return;
        // Back to the page's first view, then leave out the parts that are not there
        const staged = this.allSteps.some((s) => 'tab' in s || 'preview' in s);
        if (staged) window.dispatchEvent(new CustomEvent('tour-page', { detail: { tab: 'overview', preview: null } }));
        setTimeout(() => {
            const later = (s) => s.tab === 'explore' || (s.preview !== undefined && s.preview !== null);
            this.steps = this.allSteps.filter((s) => {
                if (s.unless && this.find(s.unless)) return false;
                if (s.sidebar) return this.exists(s.target);            // may be hidden until the sidebar opens
                return later(s) || !s.target || this.find(s.target);
            });
            if (!this.steps.length) return;
            window.dispatchEvent(new CustomEvent('tour-started'));
            this.go(0, 1);
        }, staged ? 300 : 0);
    },

    async go(n, dir = 1) {
        const step = this.steps[n];
        if (!step) { this.end(); return; }
        this.busy = true;
        this.ready = false;
        this.i = n;
        this.untype();

        // Set the page up for this step
        let wait = 0;
        if ('tab' in step || 'preview' in step) {
            window.dispatchEvent(new CustomEvent('tour-page', { detail: { tab: step.tab, preview: step.preview } }));
            wait = 450;
        }
        if (step.sidebar && this.sidebarHidden()) { this.setSidebar(true); this.openedSidebar = true; wait = Math.max(wait, 320); }
        else if (!step.sidebar && this.openedSidebar) { this.setSidebar(false); this.openedSidebar = false; wait = Math.max(wait, 250); }
        if (wait) await sleep(wait);
        if (this.i !== n) return;

        const el = this.find(step.target);
        if (step.target && !el) {
            // Not on this page after all: skip it in the direction the user is going
            this.steps.splice(n, 1);
            const to = dir > 0 ? n : n - 1;
            this.busy = false;
            return to < 0 || to >= this.steps.length ? this.end() : this.go(to, dir);
        }
        if (el && !step.sidebar) el.scrollIntoView({ block: 'center', inline: 'nearest' });
        await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
        if (this.i !== n) return;
        this.place();
        this.ready = true;
        this.busy = false;
        if (step.type && el) this.typeInto(el, step.type, n);
    },

    // ── typing, like a person ────────────────────────────────────────────
    field(el) { return el.matches('input, textarea') ? el : el.querySelector('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=date]), textarea'); },
    async typeInto(el, text, n) {
        const f = this.field(el);
        if (!f) return;
        this.typed = { el: f, value: f.value };
        f.focus({ preventScroll: true });
        f.value = '';
        for (const ch of text) {
            if (this.i !== n) return;
            f.value += ch;
            f.dispatchEvent(new Event('input', { bubbles: true }));
            await sleep(70);
        }
    },
    untype() {
        if (!this.typed) return;
        const { el, value } = this.typed;
        this.typed = null;
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.blur();
    },
    cleanup() {
        this.untype();
        if (this.openedSidebar) { this.setSidebar(false); this.openedSidebar = false; }
    },

    async next() {
        if (this.i === null || this.busy) return;
        const step = this.steps[this.i];
        if (step.click) {
            const el = this.find(step.target);
            if (step.follow) {
                // The click opens another page: its tour carries on from there
                try { sessionStorage.setItem(FOLLOW_KEY, String(Date.now())); localStorage.setItem(key, '1'); } catch (_) { /* ignore */ }
                this.cleanup();
                this.i = null;
                this.rect = null;
                el?.click();
                return;
            }
            el?.click();
            await sleep(150);
        }
        this.i >= this.steps.length - 1 ? this.end() : this.go(this.i + 1, 1);
    },
    back() { if (this.i && !this.busy) this.go(this.i - 1, -1); },
    end() {
        const was = this.i !== null;
        this.cleanup();
        this.i = null;
        this.rect = null;
        this.busy = false;
        try { localStorage.setItem(key, '1'); } catch (_) { /* ignore */ }
        if (was) window.dispatchEvent(new CustomEvent('tour-ended'));
    },
    cta() {
        const event = this.steps[this.i]?.cta?.event;
        this.end();
        if (event) setTimeout(() => window.dispatchEvent(new CustomEvent(event)), 50);
    },
    key(e) {
        if (this.i === null) return;
        if (e.key === 'Escape') { e.preventDefault(); this.end(); }
        else if (e.key === 'ArrowRight' && !this.typed) { e.preventDefault(); this.next(); }
        else if (e.key === 'ArrowLeft' && !this.typed) { e.preventDefault(); this.back(); }
    },

    place() {
        if (this.i === null) return;
        const el = this.find(this.steps[this.i].target);
        const r = el?.getBoundingClientRect();
        // A part taller than the screen: light up only what is visible of it
        this.rect = r ? {
            x: r.left, y: Math.max(r.top, 8), w: r.width,
            h: Math.min(r.bottom, window.innerHeight - 8) - Math.max(r.top, 8),
        } : null;
    },
    get step() { return this.i === null ? null : this.steps[this.i]; },
    get nextLabel() {
        const s = this.step;
        if (!s) return 'Next';
        if (s.click) return s.clickLabel || 'Next';
        return this.i === this.steps.length - 1 ? 'Done' : 'Next';
    },
    get spot() {
        const r = this.rect;
        return r
            ? `left:${r.x - 6}px;top:${r.y - 6}px;width:${r.w + 12}px;height:${r.h + 12}px;box-shadow:0 0 0 9999px rgba(15,23,42,.55);outline:2px solid rgba(255,255,255,.9)`
            : 'inset:0;background:rgba(15,23,42,.55)';
    },
    /** The card sits beside the highlighted part, kept inside the screen. */
    get card() {
        const W = Math.min(330, window.innerWidth - 24), H = 220, pad = 12, r = this.rect;
        if (!r) return `left:${(window.innerWidth - W) / 2}px;top:${Math.max(pad, (window.innerHeight - H) / 2)}px;width:${W}px`;
        let left, top;
        if (r.y + r.h + H + pad < window.innerHeight) { top = r.y + r.h + pad; left = r.x; }
        else if (r.y - H - pad > 0) { top = r.y - H - pad; left = r.x; }
        else if (r.x + r.w + W + pad < window.innerWidth) { left = r.x + r.w + pad; top = r.y; }
        else if (r.x - W - pad > 0) { left = r.x - W - pad; top = r.y; }
        else { left = (window.innerWidth - W) / 2; top = window.innerHeight - H - pad; }
        left = Math.max(pad, Math.min(left, window.innerWidth - W - pad));
        top = Math.max(pad, Math.min(top, window.innerHeight - H - pad));
        return `left:${left}px;top:${top}px;width:${W}px`;
    },
});
