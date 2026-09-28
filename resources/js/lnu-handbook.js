export function lnuHandbook() {
    return {
        // `sections` now holds ONE continuous text blob per top-level section
        // (overview / services / rules / discipline / appendix) instead of
        // 61 separate page objects. Each blob has invisible ⟦A:N⟧ markers
        // embedded at the exact spot each TOC heading actually starts —
        // formatPage() strips these and attaches id="anchor-N" to whatever
        // it renders next, so the sidebar/TOC can jump to the exact right
        // spot without the content being chopped into boxed "pages".
        //
        // IMPORTANT: N here is the *array index of the entry inside `toc`
        // below*, not the printed page number. Using the TOC's own index as
        // the anchor id guarantees every TOC entry has its own unique,
        // unambiguous jump target — even when several entries share the
        // same printed page number (e.g. two headings both printed on
        // page 8), and even when a printed page happens to begin mid-
        // paragraph rather than at a heading. The old scheme anchored on
        // page number, so entries sharing a page collided and all jumped
        // to whichever text happened to sit first on that page.
        sections: JSON.parse(document.getElementById('lnu-handbook-data').textContent),
        search: '',
        matchCount: 0,
        currentMatch: 0,
        _matchNodes: [],
        showBackToTop: false,
        sidebarOpen: false,
        activeSection: null,

        // The single TOC entry (by array index) that should be highlighted.
        // Set explicitly on click, and kept in sync by the scroll-spy while
        // the user scrolls manually.
        activeTocIndex: null,

        // Sidebar dropdown state (used if/when the sidebar switches to collapsible sections)
        openSections: {
            overview: true,
            services: false,
            rules: false,
            discipline: false,
            laws: false,
            appendix: false,
            extras: false,
        },

        // Section groupings used for the main content headers
        sectionCards: [
            { id: 'overview', name: 'Section I: Overview of LNU', short: 'Overview' },
            { id: 'services', name: 'Section II: Student Services', short: 'Services' },
            { id: 'rules', name: 'Section III: Rules & Regulations', short: 'Rules' },
            { id: 'discipline', name: 'Section IV: Conduct & Discipline', short: 'Discipline' },
            { id: 'appendix', name: 'Appendices & More', short: 'Appendices' },
        ],

        // Full nested Table of Contents, flattened with a `level` for indentation.
        // Labels below are kept in sync with the actual wording/numbering used
        // in the handbook body text itself (a handful of entries here used to
        // read differently than the body — e.g. "IV. The University Governance"
        // when the body actually says "III. The University Governance" — those
        // have been corrected). `page` is the handbook's printed page number,
        // shown only for reference; it is NOT used for navigation anymore
        // (see the `anchored` note above `sections`). `page: null` means that
        // entry isn't included in this digital edition's content data, so
        // it's shown but not clickable — its *array index* not existing as an
        // anchor in the section text is what makes it non-clickable, and the
        // template below checks `page !== null` as a simple, stable proxy for
        // "this entry has content to jump to".
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

        init() {
            window.addEventListener('scroll', () => {
                this.showBackToTop = window.scrollY > 600;
            });

            this.$nextTick(() => {
                this.setupScrollSpy();
            });
            this.$watch('search', () => {
                this.$nextTick(() => this.refreshMatches());
            });
        },

        setupScrollSpy() {
            // Watches every inline anchor element scattered through the
            // continuous flow. Anchor ids are literally "anchor-<tocIndex>",
            // so there is no lookup/collision step needed at all — the
            // index is read directly off the element id.
            const anchorEls = Array.from(document.querySelectorAll('[id^="anchor-"]'));
            if (!anchorEls.length || !('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const idx = parseInt(entry.target.id.replace('anchor-', ''), 10);
                        if (!Number.isNaN(idx)) this.activeTocIndex = idx;
                    }
                });
            }, {
                root: null,
                rootMargin: '-80px 0px -70% 0px',
                threshold: 0
            });

            anchorEls.forEach(el => observer.observe(el));
        },

        // Exactly one item highlighted, matched by its exact array index —
        // never by page number, since several entries can share a page.
        tocItemClass(item, idx) {
            const isActive = item.page !== null && idx === this.activeTocIndex;
            const base = item.level === 0
                ? 'font-bold uppercase tracking-wide text-[11px] mt-3'
                : '';

            if (isActive) {
                return base + ' border-[#d8ca06] bg-[#2A57B4]/10 text-[#2A57B4] font-semibold';
            }
            return base + ' border-transparent text-slate-600 hover:bg-slate-100 hover:text-[#2A57B4]';
        },

        // Called when a sidebar link is clicked. Scrolls to the inline anchor
        // for that TOC entry — wherever it now sits inside the continuous
        // flow — and flashes it so the jump is obvious. `idx` (the entry's
        // own position in the `toc` array) IS the anchor id, so there's no
        // page-number indirection left to go wrong.
        jumpToPage(idx, evt) {
            if (evt) evt.preventDefault();
            this.sidebarOpen = false;
            this.activeTocIndex = idx;

            this.$nextTick(() => {
                const el = document.getElementById('anchor-' + idx);
                if (!el) return;

                el.scrollIntoView({ behavior: 'smooth', block: 'center' });

                el.classList.remove('hb-flash');
                void el.offsetWidth; // restart animation if clicked again quickly
                el.classList.add('hb-flash');
                setTimeout(() => el.classList.remove('hb-flash'), 1600);
            });
        },

        // Group the flat TOC into collapsible level-0 sections (used only if the
        // sidebar markup is switched to a collapsible layout; harmless otherwise).
        tocSections() {
            const sections = [];
            let current = null;

            this.toc.forEach(item => {
                if (item.level === 0) {
                    current = {
                        ...item,
                        id: this.slugifySection(item.label),
                        children: []
                    };
                    sections.push(current);
                } else if (current) {
                    current.children.push(item);
                }
            });

            return sections;
        },

        slugifySection(label) {
            return label
                .toLowerCase()
                .replace(/section\s+[ivx]+:\s*/i, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        },

        toggleSection(id) {
            this.openSections[id] = !this.openSections[id];
        },

        // Returns one continuous block per section card, instead of the old
        // per-page grouping — this is what the template now loops over to
        // render each section as one flowing piece of content.
        groupedSections() {
            return this.sectionCards
                .map(card => ({
                    id: card.id,
                    name: card.name,
                    block: this.sections.find(s => s.section === card.id) || null
                }))
                .filter(section => section.block);
        },

        totalMatches() {
            const q = this.search.trim().toLowerCase();
            if (!q) return 0;
            return this.sections.reduce((sum, s) => {
                const matches = s.text.toLowerCase().split(q).length - 1;
                return sum + matches;
            }, 0);
        },

        refreshMatches() {
            this._matchNodes = Array.from(document.querySelectorAll('.handbook-text mark'));
            this.matchCount = this._matchNodes.length;
            this.currentMatch = this.matchCount ? 1 : 0;
            this.highlightActiveMatch();
        },

        highlightActiveMatch() {
            this._matchNodes.forEach(el => el.classList.remove('hb-mark-active'));
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

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        escapeHtml(value) {
            return value
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        },

        // Renders one continuous section blob into HTML. Same heading/list/
        // paragraph heuristics as before, but now also watches for the
        // invisible ⟦A:N⟧ marker lines embedded in the text: each marker is
        // stripped from the visible output and its TOC index (N) is
        // attached (as id="anchor-N") to the very next block-level element
        // that gets rendered — heading, list item, or paragraph. That's the
        // anchor the sidebar jumps to, even though there's no "page"
        // wrapper in the DOM, and even though several anchors can now sit
        // close together (e.g. a heading immediately followed by its own
        // first sub-heading).
        formatPage(text) {
            let safe = this.escapeHtml(text);

            if (this.search.trim()) {
                const escapedQuery = this.escapeHtml(this.search.trim()).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                safe = safe.replace(new RegExp('(' + escapedQuery + ')', 'ig'), '<mark>$1</mark>');
            }

            const applyInlineEmphasis = (str) => {
                return str.replace(/(\([^)]*(?:Art\.|Sec\.|R\.A\.|CMO|Article)[^)]*\))/g, '<em>$1</em>');
            };

            const rawLines = safe.split('\n');
            const out = [];
            let listOpen = false;
            let listDepth = null;
            let paragraphBuffer = [];
            let pendingAnchorId = null; // TOC index to attach to the next element

            const idAttr = () => {
                if (pendingAnchorId === null) return '';
                const attr = ' id="anchor-' + pendingAnchorId + '"';
                pendingAnchorId = null;
                return attr;
            };

            const flushParagraph = () => {
                if (paragraphBuffer.length) {
                    out.push('<p' + idAttr() + '>' + paragraphBuffer.join(' ') + '</p>');
                    paragraphBuffer = [];
                }
            };

            const closeList = () => {
                if (listOpen) {
                    out.push('</ul>');
                    listOpen = false;
                    listDepth = null;
                }
            };

            const openListIfNeeded = (depth, ordered) => {
                if (listOpen && listDepth !== depth) {
                    out.push('</ul>');
                    listOpen = false;
                }
                if (!listOpen) {
                    const indent = 16 + depth * 18;
                    out.push('<ul class="hb-list ' + (ordered ? 'list-decimal' : 'list-disc') + '" style="margin-left:' + indent + 'px"' + idAttr() + '>');
                    listOpen = true;
                    listDepth = depth;
                }
            };

            const anchorMarker = /^\u27e6A:(\d+)\u27e7$/;

            for (const rawLine of rawLines) {
                const line = rawLine.trim();

                const markerMatch = line.match(anchorMarker);
                if (markerMatch) {
                    // Don't render anything for the marker itself — just
                    // remember the TOC index for whatever comes next.
                    pendingAnchorId = markerMatch[1];
                    continue;
                }

                if (!line) {
                    flushParagraph();
                    closeList();
                    continue;
                }

                const bullet = line.match(/^(?:\u2022|[-\u2013\u2014])\s+(.*)$/);

                const numbered =
                    line.match(/^(\d+(?:\.\d+)+)\s+(.*)$/) ||
                    line.match(/^(\d+)\s*[.)]\s+(.*)$/) ||
                    line.match(/^(\d+(?:\.\d+)+)\s*[-\u2013\u2014]\s+(.*)$/);

                const romanHeading = line.match(/^([IVXLC]{1,6})\.\s+([A-Z][A-Z\s/&,'\u2019()-]{3,})$/);
                const sectionHeading = /^(SECTION [IVX]+:|APPENDIX [A-Z])/i.test(line);
                const allCapsHeading = /^[A-Z][A-Z\s/&,'\u2019()-]{8,}$/.test(line) && line === line.toUpperCase() && line.length < 80;

                if (bullet) {
                    flushParagraph();
                    openListIfNeeded(0, false);
                    out.push('<li' + idAttr() + '>' + applyInlineEmphasis(bullet[1]) + '</li>');
                    continue;
                }

                if (sectionHeading || allCapsHeading) {
                    flushParagraph(); closeList();
                    out.push('<div class="hb-heading hb-heading-1"' + idAttr() + '>' + line + '</div>');
                    continue;
                }

                if (romanHeading) {
                    flushParagraph(); closeList();
                    out.push('<div class="hb-heading hb-heading-2"' + idAttr() + '>' + line + '</div>');
                    continue;
                }

                if (numbered) {
                    const num = numbered[1];
                    const depth = (num.match(/\./g) || []).length;
                    const content = numbered[2];
                    const endsWithColon = content.trim().endsWith(':');
                    const looksLikeSubheading = endsWithColon || (depth <= 2 && content.length <= 50);

                    if (looksLikeSubheading) {
                        flushParagraph(); closeList();
                        const indent = 8 + depth * 20;
                        const level = Math.min(depth + 3, 5);
                        out.push(
                            '<div class="hb-heading hb-heading-' + level + '" style="padding-left:' + indent + 'px"' + idAttr() + '>' +
                            '<strong>' + num + '.</strong> ' + applyInlineEmphasis(content) +
                            '</div>'
                        );
                    } else {
                        flushParagraph();
                        openListIfNeeded(depth, true);
                        out.push('<li' + idAttr() + '><strong>' + num + '.</strong> ' + applyInlineEmphasis(content) + '</li>');
                    }
                    continue;
                }

                closeList();
                paragraphBuffer.push(applyInlineEmphasis(line));
            }

            flushParagraph();
            closeList();
            return out.join('');
        }
    };
}