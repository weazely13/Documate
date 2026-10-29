---
target: admin dashboard
total_score: 22
max_score: 40
na_heuristics: 
p0_count: 1
p1_count: 3
target_identity: "file:C:\\Users\\Admin\\Desktop\\documate\\resources\\views\\livewire\\admin\\dashboard.blade.php"
target_fingerprint: "sha256:b64325c96ae89081ae376a22519aa13cb14108e15fa1d6ab606001e728c8e3ed"
target_path: "C:\\Users\\Admin\\Desktop\\documate\\resources\\views\\livewire\\admin\\dashboard.blade.php"
timestamp: 2026-10-28T09-42-48Z
slug: resources-views-livewire-admin-dashboard-blade-php
closed: true
---
# Critique: Admin dashboard

Method: dual-agent (A: design review, B: detector + headless-Edge probes)

## Design Health Score: 22/40 (Acceptable)
| # | Heuristic | Score | Key issue |
|---|---|---|---|
| 1 | Visibility of System Status | 3 | 60s poll + live serving; no updated stamp; "1 min" durations are artifacts |
| 2 | Match System / Real World | 3 | Strong plain summaries; jargon: Board, Monitoring, tagged, Not set 0.8%, serving – · 0/1 |
| 3 | User Control and Freedom | 3 | Read-only; calendar nav; picked day/month not in URL |
| 4 | Consistency and Standards | 1 | Orange = Completed vs In progress; late = 5 vs 7 days; calendar vs school days |
| 5 | Error Prevention | 2 | Waiting-strip counts overlap silently |
| 6 | Recognition Rather Than Recall | 2 | Per-chart colour legends; hover-only values |
| 7 | Flexibility and Efficiency | 1 | Fixed 30 days; no filters/shortcuts/reminder action |
| 8 | Aesthetic and Minimalist Design | 2 | 3-4 lines per stat tile; summaries repeat tiles |
| 9 | Error Recovery | 2 | Unassigned-roles warning has no fix link |
| 10 | Help and Documentation | 3 | Good About panels, hidden behind small pill |

## Design specificity
Copy authored for DocuMate; structure is stock SaaS (5 identical panel templates). Detector: 1 finding, border-accent-on-rounded at dashboard.blade.php:569 — false positive (stacked-bar gap).

## Priority issues
- [P0] Waiting strip double-counts: overdue() spans office+signing ("40 overdue" includes "13 late"); to_review includes the 12 ready. Fix: to_review - ready; overdue - signing_late; reconcile with open. (/impeccable harden)
- [P1] Durations floor to "1 min": turnaround diffInHours/24 -> 0 -> "1 min". Fix: minutes, "Under 1 hr", "–" when <3 data points. (/impeccable harden)
- [P1] Colour/threshold meanings flip between charts; late 5 vs 7 days; calendar vs school days. (/impeccable colorize)
- [P1] 26 small-text nodes <3:1 (gray-300 1.47:1, gray-400 2.54:1, badges 3.2:1); invisible focus on navy strip; no focus styles on pills/calendar. (/impeccable audit, polish)
- [P2] items-stretch dead space (~100px Transactions, ~160px Appointments); About vs nav links look alike; docs chart labels clipped on phone. (/impeccable layout)

## Persona red flags
- Alex: fixed range, hero above triage, wrong overdue count, duplicate signer rows, Show-more state not remembered.
- Sam: invisible focus on navy; colour/title-only facts; small grey text; poll unannounced; h2/h3 sibling mismatch.
- Non-technical staff: numbers don't add up; signature steps vs requests unexplained; jargon; no fix link; help hidden.

## Minor
"1 are still open"; appointments "this month" follows viewed month, approvals count is all-time; no-semester clearance copy; org list cut at 6; empty-state wording drift; uneven Waiting-to-sign tile; legend repeats tiles; strip label truncation on phone.

## Questions
- One prioritised "Do next" list instead of four equal sections?
- Does "Where requests come from" belong on Reports?
- Group "Waiting the longest" by signer with a reminder action?
