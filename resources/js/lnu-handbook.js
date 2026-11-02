/**
 * LNU Student Handbook reader (Student › Handbook).
 *
 * The text (resources/data/lnu-handbook.json) is the handbook as extracted from its PDF: one blob per section,
 * with lines broken mid-sentence, headings in capitals, numbered rules (1.1, 4.1.4.1), bullets, column tables
 * and invisible ⟦A:N⟧ markers where table-of-contents entry N starts. formatPage() reads it in two passes:
 *
 *   1. blocks: lines are joined into headings, numbered items, lettered items, bullets, tables and paragraphs;
 *   2. html:   each block gets its typography (headings with icons, number badges with hanging indents,
 *              bold terms before "–", tables, the hymn as verse, the committee as a list of names).
 *
 * Search highlighting is applied to the text of the finished HTML only, so it never breaks the layout.
 */

// Words kept in capitals when a heading in capitals is turned into title case.
const KEEP_CAPS = new Set(['LNU', 'CHED', 'CMO', 'SDC', 'ID', 'IT', 'MIS', 'SIS', 'TOR', 'CAV', 'NSTP', 'ROTC', 'LGU', 'SSC',
    'USG', 'PE', 'RA', 'VPSD', 'VPAS', 'OSA', 'PWD', 'TES', 'BOR', 'HEI', 'HEIS', 'GAD', 'OSAS', 'DOH', 'CWTS', 'LTS', 'GPA',
    'INC', 'DRP', 'NA', 'MD', 'II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'XI', 'XII', 'XIII', 'PSA', 'NSO', 'LTO', 'UNO']);
const SMALL_WORDS = new Set(['and', 'or', 'of', 'the', 'to', 'in', 'for', 'on', 'a', 'an', 'with', 'by', 'at', 'as', 'be', 'is', 'from', 'their']);

function titleCase(str) {
    let first = true;
    return str.replace(/[A-Za-z’'.\-]+|[^A-Za-z’'.\-]+/g, (tok) => {
        if (!/[A-Za-z]/.test(tok)) return tok;
        const bare = tok.replace(/[^A-Za-z]/g, '').toUpperCase();
        let out;
        if (KEEP_CAPS.has(bare)) out = tok.toUpperCase();
        else if (!first && SMALL_WORDS.has(tok.toLowerCase())) out = tok.toLowerCase();
        else out = tok.charAt(0).toUpperCase() + tok.slice(1).toLowerCase();
        first = false;
        return out;
    });
}

// A heading's icon, from the words in it (first match wins).
const HEADING_ICONS = [
    [/mandate|history/i, 'bx-building'], [/vision|mission|objective|core value/i, 'bx-target-lock'],
    [/organizational structure/i, 'bx-sitemap'], [/governance and organization|student governance/i, 'bx-group'],
    [/governance/i, 'bx-buildings'], [/admission|registration/i, 'bx-id-card'], [/information technology/i, 'bx-laptop'],
    [/guidance|counsel/i, 'bx-conversation'], [/scholarship|grant/i, 'bx-award'], [/housing|dormitor/i, 'bx-building-house'],
    [/medical|dental|health/i, 'bx-plus-medical'], [/food/i, 'bx-restaurant'], [/library/i, 'bx-library'],
    [/museum/i, 'bx-landscape'], [/culture|arts/i, 'bx-palette'], [/sports/i, 'bx-football'], [/publication/i, 'bx-news'],
    [/academic policies/i, 'bx-book-open'], [/non-academic/i, 'bx-clipboard'], [/rights/i, 'bx-shield-quarter'],
    [/duties|responsibilit/i, 'bx-task'], [/rules on behavior/i, 'bx-user-check'], [/discipline|offense/i, 'bx-error'],
    [/due process/i, 'bx-file-find'], [/disciplinary committee|composition/i, 'bx-group'], [/complaint/i, 'bx-message-error'],
    [/drug/i, 'bx-capsule'], [/hymn/i, 'bx-music'], [/campus life/i, 'bx-happy-heart-eyes'], [/committee/i, 'bx-group'],
    [/revision|amendment/i, 'bx-edit'], [/appendix/i, 'bx-bookmark'],
];
const iconFor = (text) => (HEADING_ICONS.find(([re]) => re.test(text)) || [null, 'bx-bookmark'])[1];

// Offense levels get their own colour.
const SEVERITY = [
    [/very serious/i, 'hb-sev hb-sev-very'],
    [/serious/i, 'hb-sev hb-sev-serious'],
    [/light/i, 'hb-sev hb-sev-light'],
];

export function lnuHandbook() {
    return {
        sections: JSON.parse(document.getElementById('lnu-handbook-data').textContent),
        search: '',
        matchCount: 0,
        currentMatch: 0,
        _matchNodes: [],
        showBackToTop: false,
        sidebarOpen: false,
        activeTocIndex: null,
        tocFilter: '',
        openGroups: {},
        progress: 0,

        // The parts of the handbook, as shown at the head of each part.
        sectionCards: [
            { id: 'overview', name: 'Overview of LNU', number: 'Section I', icon: 'bx-building', tint: 'hb-tint-sky', blurb: 'The university’s mandate and history, its vision, mission and core values, and how it is governed.' },
            { id: 'services', name: 'Student Services', number: 'Section II', icon: 'bx-donate-heart', tint: 'hb-tint-mint', blurb: 'What the university offers you: records, guidance, scholarships, housing, health, the library, sports and student government.' },
            { id: 'rules', name: 'Rules and Regulations', number: 'Section III', icon: 'bx-book-open', tint: 'hb-tint-lavender', blurb: 'Admission, academic load, attendance, grading, honors, uniforms, IDs, activities and student organizations.' },
            { id: 'discipline', name: 'Student Conduct and Discipline', number: 'Section IV', icon: 'bx-shield-quarter', tint: 'hb-tint-rose', blurb: 'Your rights and duties, the rules on behavior, offenses and their sanctions, due process and complaints.' },
            { id: 'appendix', name: 'Appendices and More', number: 'Appendix', icon: 'bx-bookmarks', tint: 'hb-tint-yellow', blurb: 'Five ways to enjoy campus life, the LNU Hymn, the handbook committee and revisions.' },
        ],

        // Icons for the contents groups (level-0 entries).
        groupIcons: [
            [/committee/i, 'bx-group'], [/section i:/i, 'bx-building'], [/section ii:/i, 'bx-donate-heart'],
            [/section iii:/i, 'bx-book-open'], [/section iv:/i, 'bx-shield-quarter'], [/section v:/i, 'bx-library'],
            [/appendix a/i, 'bx-spreadsheet'], [/appendix b/i, 'bx-first-aid'], [/appendix c/i, 'bx-happy-heart-eyes'],
            [/commitment/i, 'bx-pen'], [/hymn/i, 'bx-music'], [/revision/i, 'bx-edit'],
        ],

        toc: [
            { level: 0, label: 'Committee on Student Handbook', page: 66 },
            { level: 0, label: 'Section I: Overview of LNU', page: 7 },
            { level: 1, label: 'I. LNU Mandate and Brief History', page: 8 },
            { level: 1, label: 'II. Vision, Mission, Objectives and Core Values of the University', page: 8 },
            { level: 1, label: 'III. The University Governance', page: 9 },
            { level: 1, label: 'VI. The University Organizational Structure', page: 10 },
            { level: 0, label: 'Section II: Student Services', page: 11 },
            { level: 1, label: 'I. Registration and Document Services', page: 12 },
            { level: 1, label: 'II. Information Technology Services', page: 13 },
            { level: 1, label: 'III. Guidance and Counselling Services', page: 13 },
            { level: 1, label: 'IV. Scholarships / Grants', page: 15 },
            { level: 2, label: '1. Policies on Granting of Scholarships', page: 15 },
            { level: 2, label: '2. List of Available Scholarships', page: 15 },
            { level: 3, label: '2.1 LNU Student Incentives', page: 15 },
            { level: 3, label: '2.2 Government Funded Scholarships', page: 16 },
            { level: 3, label: '2.3 Tertiary Education Scholarship (TES)', page: 16 },
            { level: 3, label: '2.4 Privately Funded Scholarships', page: 16 },
            { level: 3, label: '2.5 Student Assistantship Program', page: 16 },
            { level: 1, label: 'V. Student Housing / Dormitory', page: 17 },
            { level: 1, label: 'VI. Medical and Dental Services', page: 18 },
            { level: 1, label: 'VII. Food Services', page: 19 },
            { level: 1, label: 'VIII. Library Services', page: 20 },
            { level: 1, label: 'IX. University Museum', page: 21 },
            { level: 1, label: 'X. Culture and Arts Development', page: 21 },
            { level: 1, label: 'XI. Sports Development', page: 21 },
            { level: 1, label: 'XII. School Publication', page: 22 },
            { level: 1, label: 'XIII. Student Governance and Organization', page: 22 },
            { level: 0, label: 'Section III: Rules and Regulations', page: 25 },
            { level: 1, label: 'I. Academic Policies', page: 26 },
            { level: 2, label: '1. Admission', page: 26 },
            { level: 3, label: '1.1 Admission Requirements', page: 26 },
            { level: 3, label: '1.2 Screening and Enrollment Schedule', page: 30 },
            { level: 3, label: '1.3 Late Enrolment', page: 30 },
            { level: 3, label: '1.4 Official Dropping and Changing of Subjects', page: 30 },
            { level: 3, label: '1.5 Policies on Transferees, Shifters, Returnees and Cross-Enrolees', page: 31 },
            { level: 3, label: '1.6 Foreign Students', page: 33 },
            { level: 3, label: '1.7 Retention Requirements', page: 34 },
            { level: 2, label: '2. Academic Loads', page: 36 },
            { level: 2, label: '3. Class Attendance', page: 36 },
            { level: 2, label: '4. Grading System', page: 37 },
            { level: 3, label: '4.1 Numerical Rating and Qualitative Description', page: 37 },
            { level: 3, label: '4.2 Bases of Grading', page: 38 },
            { level: 3, label: '4.3 Mandatory Policies for Course Requirements', page: 38 },
            { level: 4, label: '4.3.1 Tests', page: 38 },
            { level: 4, label: '4.3.2 Term Papers and Projects', page: 38 },
            { level: 2, label: '5. Leave of Absence', page: 39 },
            { level: 2, label: '6. Request for Transfer', page: 39 },
            { level: 2, label: '7. Internship Programs', page: 39 },
            { level: 3, label: '7.1 General Internship Guidelines', page: 39 },
            { level: 2, label: '8. Residence Requirements', page: 40 },
            { level: 2, label: '9. Graduation Requirements', page: 40 },
            { level: 2, label: '10. Academic Honors and Awards', page: 40 },
            { level: 3, label: '10.1 Latin Honors', page: 40 },
            { level: 3, label: '10.2 Other Awards', page: 41 },
            { level: 4, label: '10.2.1 Academic Proficiency per College', page: 41 },
            { level: 4, label: '10.2.2 Non-Academic Awards', page: 43 },
            { level: 2, label: '11. Other Academic Policies', page: 43 },
            { level: 3, label: '11.1 Parents’/Guardians’ Appointment to See their Son/Daughter/Ward During Class Hours', page: 43 },
            { level: 3, label: '11.2 Student Clearance', page: 43 },
            { level: 1, label: 'II. Non-Academic Directives', page: 44 },
            { level: 2, label: '1. School Uniform', page: 44 },
            { level: 2, label: '2. School ID', page: 45 },
            { level: 2, label: '3. Co-Curricular and Non-Curricular Activities', page: 45 },
            { level: 3, label: '3.1 Curricular Activities', page: 45 },
            { level: 4, label: '3.1.1 Local Off-Campus Curricular Activities', page: 45 },
            { level: 4, label: '3.1.2 Policies on Field Trips and Student Travels', page: 46 },
            { level: 3, label: '3.2 Non-Curricular Activities', page: 47 },
            { level: 3, label: '3.3 Non-curricular Activities / Projects Involving the Entire Institution or College', page: 48 },
            { level: 3, label: '3.4 Minor In-campus Activities of Student Organizations', page: 48 },
            { level: 2, label: '4. Accreditation of and Granting of Privileges to Student Organizations', page: 49 },
            { level: 2, label: '5. Term-end and Year-end Report', page: 50 },
            { level: 2, label: '6. Posting of Announcements', page: 50 },
            { level: 2, label: '7. Fund Raising Projects', page: 51 },
            { level: 2, label: '8. Policies on all Social Events and Parties', page: 51 },
            { level: 2, label: '9. Ban Period', page: 51 },
            { level: 2, label: '10. Policies on Suspension of Classes', page: 51 },
            { level: 0, label: 'Section IV: Student Conduct and Discipline', page: 52 },
            { level: 1, label: 'I. Rights of Students', page: 53 },
            { level: 1, label: 'II. Duties and Responsibilities of Students', page: 53 },
            { level: 1, label: 'III. General Rules on Behavior', page: 54 },
            { level: 1, label: 'IV. Specific Rules on Behavior', page: 54 },
            { level: 1, label: 'V. Policies on Discipline', page: 56 },
            { level: 2, label: '1. Classification of Offenses and Corresponding Sanctions', page: 56 },
            { level: 3, label: '1.1 Light', page: 56 },
            { level: 3, label: '1.2 Serious', page: 57 },
            { level: 3, label: '1.3 Very Serious', page: 58 },
            { level: 3, label: '1.4 Sanctions on Erring Graduating Students', page: 59 },
            { level: 1, label: 'VI. Administrative Due Process', page: 59 },
            { level: 1, label: 'VII. Composition of the Student Disciplinary Committee (SDC) for Serious and Very Serious Offenses', page: 61 },
            { level: 1, label: 'VIII. Students’ Complaints', page: 61 },
            { level: 2, label: '1. Complaints Involving Alleged Abuse of Children', page: 61 },
            { level: 2, label: '2. Complaints Involving Discrimination and Sexual Harassment', page: 61 },
            { level: 2, label: '3. Confidentiality', page: 62 },
            { level: 2, label: '4. Anonymous Complaints', page: 62 },
            { level: 1, label: 'IX. How to Treat Students Confirmed to Be Using a Dangerous Drug', page: 62 },
            { level: 0, label: 'Section V: Student-Related Laws, CHED Memorandum Orders and Implementing Guidelines', page: null },
            { level: 1, label: '1. Republic Act No. 8049 (The Anti-Hazing Law)', page: null },
            { level: 1, label: '2. CHED Statement on Fraternities', page: null },
            { level: 1, label: '3. Republic Act No. 7079 (The Campus Journalism Act of 1991)', page: null },
            { level: 1, label: '4. The Education Act of 1982', page: null },
            { level: 1, label: '5. Republic Act No. 9163 (National Service Training Program Act)', page: null },
            { level: 1, label: '6. Republic Act No. 9262 (Anti-Violence Against Women and Children Act)', page: null },
            { level: 1, label: '7. Republic Act No. 7877 (Anti-Sexual Harassment Act of 1995)', page: null },
            { level: 1, label: '8. Republic Act No. 7610 (Special Protection of Children Against Abuse, Exploitation and Discrimination Act)', page: null },
            { level: 1, label: '9. Republic Act No. 8792 (Electronic Commerce Act of 2000)', page: null },
            { level: 1, label: '10. Republic Act No. 9165 (Comprehensive Dangerous Drugs Act of 2002)', page: null },
            { level: 1, label: '11. Dangerous Drugs Board Regulation No. 3, s. of 2009', page: null },
            { level: 1, label: '12. CMO No. 19, s. 2003 – Conduct of Random Drug Testing for Tertiary Students', page: null },
            { level: 1, label: '13. Republic Act 9211 (Tobacco Regulation Act of 2003)', page: null },
            { level: 1, label: '14. Republic Act No. 7271 (Magna Carta for Disabled Persons)', page: null },
            { level: 1, label: '15. Republic Act No. 9003 (Ecological Solid Waste Management Act of 2000)', page: null },
            { level: 1, label: '16. Republic Act No. 10931 (Universal Access to Quality Tertiary Education Act)', page: null },
            { level: 1, label: '17. Republic Act No. 6847 (Philippine Sports Commission Act)', page: null },
            { level: 1, label: '18. Republic Act No. 9512 (Environmental Awareness and Education Act of 2008)', page: null },
            { level: 1, label: '19. Republic Act No. 10112 (Philippine Disaster Risk Reduction and Management Act)', page: null },
            { level: 1, label: '20. Republic Act No. 10175 (Cybercrime Prevention Act of 2012)', page: null },
            { level: 1, label: '21. CMO No. 63, s. 2017 (Students’ Off-Campus Activity)', page: null },
            { level: 1, label: '22. CMO No. 26, s. 2015 (International Educational Trip Guidelines)', page: null },
            { level: 1, label: '23. CMO No. 8, s. 2021 (Flexible Delivery Guidelines during COVID-19)', page: null },
            { level: 1, label: '24. CMO No. 3, s. 2022 (Guidelines on Gender-Based Sexual Harassment in HEIs)', page: null },
            { level: 0, label: 'Appendix A: Curriculum Program', page: null },
            { level: 1, label: '1. College of Education', page: null },
            { level: 1, label: '2. College of Arts and Sciences', page: null },
            { level: 1, label: '3. College of Management and Entrepreneurship', page: null },
            { level: 0, label: 'Appendix B: What to Do in Case of Emergency', page: null },
            { level: 0, label: 'Appendix C: Five Ways to Enjoy Campus Life', page: 64 },
            { level: 0, label: 'Student Commitment Form', page: null },
            { level: 0, label: 'LNU Hymn', page: 65 },
            { level: 0, label: 'Revisions and Amendments', page: 67 },
        ],

        /** The app scrolls inside its own panel, not the window: find what actually scrolls. */
        scroller() {
            if (this._scroller !== undefined) return this._scroller;
            let el = this.$el.parentElement;
            while (el && el !== document.body) {
                const oy = getComputedStyle(el).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && el.scrollHeight > el.clientHeight + 4) return (this._scroller = el);
                el = el.parentElement;
            }
            return (this._scroller = null);
        },

        init() {
            const onScroll = () => {
                const el = this.scroller();
                const top = el ? el.scrollTop : window.scrollY;
                const max = el ? el.scrollHeight - el.clientHeight : document.documentElement.scrollHeight - window.innerHeight;
                this.showBackToTop = top > 600;
                this.progress = max > 0 ? Math.min(100, Math.round((top / max) * 100)) : 0;
                if (!this._spyQueued) {
                    this._spyQueued = true;
                    requestAnimationFrame(() => { this._spyQueued = false; this.spy(); });
                }
            };
            this.$nextTick(() => {
                const el = this.scroller();
                (el || window).addEventListener('scroll', onScroll, { passive: true });
            });
            this.$nextTick(() => {
                this.setupScrollSpy();
                onScroll();
            });
            this.$watch('search', () => this.$nextTick(() => { this._anchors = null; this.refreshMatches(); }));
            // The group holding what you are reading opens by itself.
            this.$watch('activeTocIndex', (idx) => {
                const g = this.groupOf(idx);
                if (g !== null) this.openGroups[g] = true;
            });
            // The first part starts open.
            const first = this.tocGroups().find((g) => g.available);
            if (first) this.openGroups[first.idx] = true;
        },

        /** The topic being read: the last topic heading above the top of the reading area. */
        spy() {
            const anchors = this._anchors || (this._anchors = Array.from(document.querySelectorAll('.handbook-text [id^="anchor-"]')));
            const line = (this.scroller()?.getBoundingClientRect().top ?? 0) + 140;
            let found = null;
            for (const el of anchors) {
                if (el.getBoundingClientRect().top <= line) found = el; else break;
            }
            const idx = found ? parseInt(found.id.replace('anchor-', ''), 10) : null;
            if (idx !== null && !Number.isNaN(idx) && idx !== this.activeTocIndex) this.activeTocIndex = idx;
        },

        setupScrollSpy() {
            const anchorEls = Array.from(document.querySelectorAll('[id^="anchor-"]'));
            if (!anchorEls.length || !('IntersectionObserver' in window)) return;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const idx = parseInt(entry.target.id.replace('anchor-', ''), 10);
                        if (!Number.isNaN(idx)) this.activeTocIndex = idx;
                    }
                });
            }, { root: null, rootMargin: '-90px 0px -70% 0px', threshold: 0 });
            anchorEls.forEach((el) => observer.observe(el));
        },

        // ── the contents sidebar ──────────────────────────────────────────────────────────────
        /** Level-0 entries as groups with their entries; a group is available when it is in this digital edition. */
        tocGroups() {
            if (this._groups) return this._groups;
            const groups = [];
            let current = null;
            this.toc.forEach((item, idx) => {
                if (item.level === 0) {
                    const label = item.label.replace(/^Section [IVX]+:\s*/i, '');
                    const number = (item.label.match(/^(Section [IVX]+)/i) || [])[1] || null;
                    current = {
                        idx, label, number, page: item.page,
                        icon: (this.groupIcons.find(([re]) => re.test(item.label)) || [null, 'bx-bookmark'])[1],
                        children: [],
                    };
                    groups.push(current);
                } else if (current) {
                    current.children.push({ ...item, idx });
                }
            });
            groups.forEach((g) => { g.available = g.page !== null || g.children.some((c) => c.page !== null); });
            this._groups = groups;
            return groups;
        },
        groupOf(idx) {
            if (idx === null || idx === undefined) return null;
            const g = this.tocGroups().find((x) => x.idx === idx || x.children.some((c) => c.idx === idx));
            return g ? g.idx : null;
        },
        isGroupOpen(g) { return this.tocFilter.trim() !== '' || !!this.openGroups[g.idx]; },
        toggleGroup(g) { this.openGroups[g.idx] = !this.openGroups[g.idx]; },
        visibleChildren(g) {
            const q = this.tocFilter.trim().toLowerCase();
            return q ? g.children.filter((c) => c.label.toLowerCase().includes(q)) : g.children;
        },
        groupMatches(g) {
            const q = this.tocFilter.trim().toLowerCase();
            return !q || g.label.toLowerCase().includes(q) || this.visibleChildren(g).length > 0;
        },
        groupIsActive(g) { return this.groupOf(this.activeTocIndex) === g.idx; },
        /** Where an entry's text starts in the label after its number ("2.1 LNU Student Incentives"). */
        splitLabel(label) {
            const m = label.match(/^((?:[IVXLC]+|\d+(?:\.\d+)*)\.?)\s+(.*)$/);
            return m ? { num: m[1].replace(/\.$/, ''), text: m[2] } : { num: '', text: label };
        },

        jumpToPage(idx, evt) {
            if (evt) evt.preventDefault();
            this.sidebarOpen = false;
            this.activeTocIndex = idx;
            this.$nextTick(() => {
                const el = document.getElementById('anchor-' + idx);
                if (!el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                el.classList.remove('hb-flash');
                void el.offsetWidth;
                el.classList.add('hb-flash');
                setTimeout(() => el.classList.remove('hb-flash'), 1600);
            });
        },
        jumpToSection(id) {
            const el = document.getElementById('section-' + id);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.sidebarOpen = false;
        },

        groupedSections() {
            return this.sectionCards
                .map((card) => ({ ...card, block: this.sections.find((s) => s.section === card.id) || null }))
                .filter((section) => section.block);
        },
        topicCount(id) {
            const block = this.sections.find((s) => s.section === id);
            return block ? (block.text.match(/\u27e6A:\d+\u27e7/g) || []).length : 0;
        },

        // ── search ────────────────────────────────────────────────────────────────────────────
        refreshMatches() {
            this._matchNodes = Array.from(document.querySelectorAll('.handbook-text mark'));
            this.matchCount = this._matchNodes.length;
            this.currentMatch = this.matchCount ? 1 : 0;
            this.highlightActiveMatch();
        },
        highlightActiveMatch() {
            this._matchNodes.forEach((el) => el.classList.remove('hb-mark-active'));
            if (!this.matchCount) return;
            const el = this._matchNodes[this.currentMatch - 1];
            if (!el) return;
            el.classList.add('hb-mark-active');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        nextMatch() {
            if (!this.matchCount) return;
            this.currentMatch = this.currentMatch >= this.matchCount ? 1 : this.currentMatch + 1;
            this.highlightActiveMatch();
        },
        prevMatch() {
            if (!this.matchCount) return;
            this.currentMatch = this.currentMatch <= 1 ? this.matchCount : this.currentMatch - 1;
            this.highlightActiveMatch();
        },
        scrollToTop() { (this.scroller() || window).scrollTo({ top: 0, behavior: 'smooth' }); },

        escapeHtml(value) {
            return String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
        },
        /** Marks the search term in the text of finished HTML (never inside tags). */
        highlight(html) {
            const q = this.search.trim();
            if (!q) return html;
            const re = new RegExp('(' + this.escapeHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
            return html.split(/(<[^>]+>)/).map((part) => (part.startsWith('<') ? part : part.replace(re, '<mark>$1</mark>'))).join('');
        },

        // ── the formatter ─────────────────────────────────────────────────────────────────────
        /** Pass 1: the section's lines as blocks. */
        toBlocks(text) {
            const lines = text.split('\n');
            const blocks = [];
            let cur = null;
            let anchor = null;
            let blank = false;
            let prevLine = '';
            let keepLines = false;   // the hymn and the committee: every line is its own

            const classify = (line) => {
                let m;
                if (/^(SECTION [IVX]+:|APPENDIX [A-Z]\b)/i.test(line)) return { type: 'section', text: line };
                if ((m = line.match(/^([IVXLC]{1,6})\.\s+(.{3,})$/)) && !/[.;,]$/.test(m[2])) return { type: 'roman', num: m[1], text: m[2] };
                if (/\S\s{3,}\S/.test(line) && line.split(/\s{3,}/).length >= 2 && line.length < 140) return { type: 'row', cells: line.split(/\s{3,}/) };
                if ((m = line.match(/^(\d{1,2}(?:\.\d{1,2})+)\.?\s+(.+)$/)) || (m = line.match(/^(\d{1,2})[.)]\s+(.+)$/))) {
                    return { type: 'num', num: m[1], depth: (m[1].match(/\./g) || []).length, text: m[2] };
                }
                if ((m = line.match(/^([A-Ha-h])[.)]\s+(.+)$/))) return { type: 'letter', num: m[1], text: m[2] };
                if ((m = line.match(/^[\u2022\u25aa\u25cf\uf0b7\uf0a7*]\s*(.+)$/)) || (m = line.match(/^[-\u2013\u2014]\s+(.+)$/))) return { type: 'bullet', text: m[1] };
                if (line === line.toUpperCase() && /[A-Z]{3}/.test(line) && line.length <= 90 && !/^\d/.test(line)) return { type: 'caps', text: line };
                return { type: 'text', text: line };
            };
            const ends = (s) => /[.:;!?)]$/.test(String(s ?? '').trim());
            const isCapsText = (s) => s === s.toUpperCase() && /[A-Z]{3}/.test(s) && s.length < 80;
            const looksLikeTitle = (line) => {
                const words = line.split(/\s+/);
                const caps = words.filter((w) => /^[A-Z0-9“"(]/.test(w)).length;
                return words.length <= 8 && line.length <= 60 && !ends(line) && !/,$/.test(line) && caps >= Math.ceil(words.length * 0.6);
            };

            for (const raw of lines) {
                const line = raw.trim();
                const marker = line.match(/^\u27e6A:(\d+)\u27e7$/);
                if (marker) { anchor = marker[1]; cur = null; continue; }
                if (!line) { blank = true; continue; }

                let c = classify(line);
                if (['caps', 'roman', 'section'].includes(c.type)) keepLines = /hymn/i.test(line) || (/committee/i.test(line) && !/disciplinary/i.test(line));
                else if (keepLines && c.type !== 'num') {
                    blocks.push({ type: 'text', text: line, anchor });
                    anchor = null; cur = null; blank = false; prevLine = line;
                    continue;
                }
                const lower = /^[a-z(]/.test(line);
                // "…removes a / 4.0 or completes…": a number inside an unfinished sentence is not an item
                if (c.type === 'num' && cur && cur.type === 'text' && !blank && !ends(cur.text)) c = { type: 'text', text: line };

                // A table keeps taking rows; a line without columns continues the last cell
                if (cur && cur.type === 'table') {
                    if (c.type === 'row') { cur.rows.push(c.cells); prevLine = line; blank = false; continue; }
                    if (!blank && c.type === 'text' && line.length < 60) { const r = cur.rows[cur.rows.length - 1]; r[r.length - 1] += ' ' + line; prevLine = line; continue; }
                }
                // Continue the block being built?
                if (cur && c.type === 'text' && anchor === null) {
                    const open = ['num', 'letter', 'bullet', 'text'].includes(cur.type);
                    const wrapsHeading = ['section', 'roman'].includes(cur.type) && !blank && line.length < 50 && !ends(line);
                    // a blank line ends a paragraph only when the sentence before it is finished
                    const paraBreak = cur.type === 'text' && ((blank && ends(cur.text)) || (prevLine.length < 60 && ends(prevLine) && !lower));
                    const titleLine = !lower && looksLikeTitle(line) && (blank || ends(prevLine));
                    // "1. VISION" / "4.1 Registration": a heading-like item ends at its line
                    const headItem = cur.type === 'num' && !lower && (isCapsText(cur.text) || looksLikeTitle(cur.text));
                    const carries = !blank || (!ends(cur.text) && (lower || cur.type === 'text'));
                    if (wrapsHeading || (open && !headItem && !paraBreak && !titleLine && carries)) {
                        cur.text += ' ' + line;
                        prevLine = line;
                        blank = false;
                        continue;
                    }
                }

                const block = { ...c, anchor };
                if (block.type === 'row') { block.type = 'table'; block.rows = [c.cells]; }
                if (block.type === 'text' && looksLikeTitle(line) && (blank || !cur || ends(prevLine) || ['caps', 'roman', 'title'].includes(cur?.type))) block.type = 'title';
                blocks.push(block);
                cur = block;
                anchor = null;
                blank = false;
                prevLine = line;
            }
            if (anchor !== null) blocks.push({ type: 'anchor', anchor });
            return blocks;
        },

        /** Inline emphasis: citations in italics, "Note:" and definition terms in bold. */
        inline(str) {
            return str
                .replace(/(\([^)]*(?:Art\.|Sec\.|Section|R\.A\.|RA|CMO|Article|LNU Code)[^)]*\))/g, '<em>$1</em>')
                .replace(/^(Note|NOTE|Provided|Important)(:)/, '<strong>$1$2</strong>')
                .replace(/(Republic Act No\.\s*\d+|R\.A\.\s*\d+|Presidential Decree No\.\s*\d+|CMO No\.\s*\d+,?\s*s\.?\s*\d{4})/g, '<span class="hb-law">$1</span>');
        },
        /** "Term – what it means" and "1st Offense  Reprimand": the term in bold. */
        withTerm(str) {
            let m;
            if ((m = str.match(/^(\d(?:st|nd|rd|th) Offense)\s+(.+)$/i))) return `<strong class="hb-term">${m[1]}</strong> <span class="hb-dash">·</span> ${this.inline(m[2])}`;
            if ((m = str.match(/^([^–—:]{2,60}?)\s+[–—]\s+(.+)$/))) return `<strong class="hb-term">${m[1]}</strong> <span class="hb-dash">–</span> ${this.inline(m[2])}`;
            if ((m = str.match(/^([A-Z][^:.;]{1,40}):\s+(.+)$/))) return `<strong class="hb-term">${m[1]}:</strong> ${this.inline(m[2])}`;
            return this.inline(str);
        },

        /** Pass 2: blocks to HTML. */
        formatPage(text) {
            const blocks = this.toBlocks(this.escapeHtml(text));
            const out = [];
            let depth = 0;          // indentation of what follows a numbered heading or item
            let mode = null;        // 'verse' after the hymn's title, 'people' after the committee's
            let callout = false;    // the paragraph after VISION / MISSION
            let bullets = [];

            const id = (b) => (b.anchor !== null && b.anchor !== undefined ? ` id="anchor-${b.anchor}"` : '');
            const indent = (d) => `--d:${Math.min(d, 4)}`;
            const flushBullets = () => {
                if (!bullets.length) return;
                out.push(`<ul class="hb-bullets" style="${indent(depth)}">${bullets.join('')}</ul>`);
                bullets = [];
            };

            for (let i = 0; i < blocks.length; i++) {
                const b = blocks[i];
                if (b.type !== 'bullet') flushBullets();

                if (mode === 'verse' && ['text', 'title', 'caps'].includes(b.type)) {
                    // the hymn: keep every line; credits as small labels
                    const meta = b.text.match(/^(Music|Lyrics):\s*(.+)$/);
                    if (meta) { out.push(`<p class="hb-credit"${id(b)}><span>${meta[1]}</span> ${meta[2]}</p>`); continue; }
                    if (/^Leyte Normal University\.?$/.test(b.text)) { mode = null; continue; }
                    out.push(`<p class="hb-verse"${id(b)}>${b.text}</p>`);
                    continue;
                }
                if (mode === 'people' && ['text', 'title'].includes(b.type)) {
                    const [name, ...cred] = b.text.split(/,\s*/);
                    out.push(`<div class="hb-person"${id(b)}><span class="hb-person-icon"><i class="bx bx-user"></i></span><span><strong>${name}</strong>${cred.length ? `<span class="hb-person-cred">${cred.join(', ')}</span>` : ''}</span></div>`);
                    continue;
                }
                if (!['text', 'title'].includes(b.type) || b.type === 'caps') mode = null;

                switch (b.type) {
                    case 'anchor':
                        out.push(`<span class="hb-anchor"${id(b)}></span>`);
                        break;
                    case 'section':
                        // the part's own header is drawn above; keep only the jump target
                        out.push(`<span class="hb-anchor"${id(b)}></span>`);
                        depth = 0;
                        break;
                    case 'roman':
                    case 'caps': {
                        const t = titleCase(b.text);
                        const sev = (SEVERITY.find(([re]) => re.test(b.text) && /offense/i.test(b.text)) || [null, ''])[1];
                        out.push(`<h3 class="hb-h2"${id(b)}><span class="hb-h2-icon"><i class="bx ${iconFor(b.text)}"></i></span><span>${sev ? `<span class="${sev}" aria-hidden="true"></span>` : ''}${b.num ? `<span class="hb-roman">${b.num}.</span> ` : ''}${t}</span></h3>`);
                        depth = 0;
                        callout = false;
                        if (/hymn/i.test(b.text)) mode = 'verse';
                        if (/committee/i.test(b.text) && !/disciplinary/i.test(b.text)) mode = 'people';
                        break;
                    }
                    case 'title':
                        out.push(`<h4 class="hb-h4"${id(b)}>${b.text}</h4>`);
                        depth = 0;
                        break;
                    case 'num': {
                        const txt = b.text.trim();
                        const capsHead = txt === txt.toUpperCase() && /[A-Z]{3}/.test(txt) && txt.length < 80;
                        const shortHead = b.depth <= 1 && txt.length <= 60 && !/[.;,]$/.test(txt) && txt.split(/\s+/).length <= 9;
                        if (capsHead || shortHead) {
                            const label = capsHead ? titleCase(txt) : txt;
                            const sev = (SEVERITY.find(([re]) => re.test(txt) && /offense/i.test(txt)) || [null, ''])[1];
                            out.push(`<h4 class="hb-h3" style="${indent(b.depth)}"${id(b)}><span class="hb-num">${b.num}</span><span>${sev ? `<span class="${sev}" aria-hidden="true"></span>` : ''}${label}</span></h4>`);
                            depth = b.depth + 1;
                            callout = /^(vision|mission)$/i.test(txt);
                        } else {
                            const lead = (/:$/.test(txt) || (txt.length <= 40 && !/[.;,]$/.test(txt) && !/\s[–—]\s|Offense/i.test(txt))) ? ' hb-item-lead' : '';
                            out.push(`<div class="hb-item${lead}" style="${indent(b.depth)}"${id(b)}><span class="hb-num">${b.num}</span><div>${this.withTerm(txt)}</div></div>`);
                            depth = b.depth + 1;
                        }
                        break;
                    }
                    case 'letter':
                        out.push(`<div class="hb-item" style="${indent(depth)}"${id(b)}><span class="hb-num hb-num-letter">${b.num}</span><div>${this.withTerm(b.text)}</div></div>`);
                        break;
                    case 'bullet':
                        bullets.push(`<li${id(b)}>${this.withTerm(b.text)}</li>`);
                        break;
                    case 'table': {
                        // as many columns as the header; extra cells join the last one
                        const cols = b.rows[0].length;
                        const fit = (r) => (r.length > cols ? [...r.slice(0, cols - 1), r.slice(cols - 1).join(' ')] : r);
                        const [head, ...rows] = b.rows.map(fit);
                        const cell = (c, tag) => `<${tag}>${this.inline(c)}</${tag}>`;
                        const pad = (r) => r.concat(Array(cols - r.length).fill(''));
                        out.push(`<div class="hb-table-wrap" style="${indent(depth)}"${id(b)}><table><thead><tr>${pad(head).map((c) => cell(c, 'th')).join('')}</tr></thead><tbody>${rows.map((r) => `<tr>${pad(r).map((c) => cell(c, 'td')).join('')}</tr>`).join('')}</tbody></table></div>`);
                        break;
                    }
                    default:
                        if (callout) {
                            out.push(`<blockquote class="hb-callout" style="${indent(Math.max(depth - 1, 0))}"${id(b)}>${this.inline(b.text)}</blockquote>`);
                            callout = false;
                        } else {
                            out.push(`<p style="${indent(depth)}"${id(b)}>${this.inline(b.text)}</p>`);
                        }
                }
            }
            flushBullets();
            return this.highlight(out.join(''));
        },
    };
}
