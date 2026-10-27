// ---- Dashboard insight charts (admin and student dashboards) ----
// Plain SVG/HTML driven by Alpine; the numbers come from App\Support\DashboardInsights.

/** Round a maximum up to 2 × a 1/2/5 step, so the mid gridline is a whole number too. */
function niceMax(value) {
    const half = Math.max(1, value) / 2;
    const magnitude = 10 ** Math.floor(Math.log10(half));
    const step = [1, 2, 5, 10].map((m) => m * magnitude).find((s) => s >= half);
    return Math.max(2, step * 2);
}

document.addEventListener('alpine:init', () => {
    // Stat cards that pick which metric the line chart below them shows.
    window.Alpine.data('insightTrend', (metrics = []) => ({
        metrics,
        selected: 0,
        hover: null,

        get current() {
            return this.metrics[this.selected] ?? { series: [] };
        },
        get series() {
            return this.current.series;
        },
        get max() {
            return niceMax(Math.max(0, ...this.series.map((p) => p.v)));
        },
        get ticks() {
            return [this.max, this.max / 2, 0];
        },
        // Both axes in percent of the plot box (the SVG uses a 0–100 viewBox).
        x(i) {
            const n = this.series.length;
            return n > 1 ? (i / (n - 1)) * 100 : 50;
        },
        y(v) {
            return 100 - (v / this.max) * 100;
        },
        get path() {
            return this.series.map((p, i) => `${i ? 'L' : 'M'}${this.x(i).toFixed(2)},${this.y(p.v).toFixed(2)}`).join(' ');
        },
        // The line closed down to the baseline, for the soft fill under it.
        get area() {
            const n = this.series.length;
            return n ? `${this.path} L${this.x(n - 1).toFixed(2)},100 L${this.x(0).toFixed(2)},100 Z` : '';
        },
        get xLabels() {
            const s = this.series;
            return s.length ? [s[0].d, s[Math.floor((s.length - 1) / 2)].d, s[s.length - 1].d] : [];
        },
        get point() {
            return this.hover === null ? null : this.series[this.hover];
        },

        select(i) {
            this.selected = i;
            this.hover = null;
        },
        track(event) {
            const box = event.currentTarget.getBoundingClientRect();
            const n = this.series.length;
            const i = Math.round(((event.clientX - box.left) / box.width) * (n - 1));
            this.hover = Math.min(n - 1, Math.max(0, i));
        },
        deltaText(m) {
            if (m.delta === null) return m.previous === 0 && m.total > 0 ? 'New this period' : 'No earlier data';
            return `${m.delta > 0 ? '+' : ''}${m.delta}% vs. previous`;
        },
        deltaClass(m) {
            if (m.delta === null || m.delta === 0) return 'text-gray-500';
            return m.delta > 0 ? 'text-emerald-700' : 'text-rose-700';
        },
    }));

    // Weekday chips over a bar per three-hour window.
    window.Alpine.data('insightActiveTimes', (data) => {
        const totals = data.days.map((d) => d.bars.reduce((a, b) => a + b, 0));

        return {
            data,
            day: totals.indexOf(Math.max(...totals)),
            hover: null,

            get bars() {
                return this.data.days[this.day].bars;
            },
            get max() {
                return Math.max(1, ...this.bars);
            },
            height(v) {
                return v ? Math.max(4, (v / this.max) * 100) : 0;
            },
        };
    });
});
