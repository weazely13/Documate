---
version: alpha
name: DocuMate
description: "A calm, document-first workspace for student clearance and approvals. Notion is the base: warm paper surfaces, a soft-tinted sidebar, hairline borders, 8px rectangular buttons, 12px cards, and pastel 'database-property' tints that color-code work. Stripe supplies the details: deep-navy ink instead of black, blue-tinted soft shadows, tight negative tracking on headings, and tabular figures wherever numbers are compared. DocuMate blue (#2a57b4) is the single brand accent."

colors:
  primary: "#2a57b4"
  primary-hover: "#24499a"
  primary-press: "#1e4090"
  primary-tint: "#f0f4fb"
  primary-select: "#e2e9f7"
  on-primary: "#ffffff"
  ink: "#0d253d"
  ink-secondary: "#273951"
  ink-body: "#4a5568"
  ink-mute: "#66707f"
  ink-faint: "#9a978f"
  canvas: "#ffffff"
  ground: "#fafaf9"
  shell: "#f5f4f2"
  hover: "#ecebe7"
  hairline: "#e7e5e1"
  hairline-strong: "#d4d1cb"
  tint-sky: "#dcecfa"
  tint-lavender: "#e6e0f5"
  tint-mint: "#d9f3e1"
  tint-yellow: "#fef3c7"
  tint-rose: "#fde0ec"
  tint-peach: "#fdece3"
  success: "#059669"
  warning: "#d97706"
  danger: "#dc2626"

typography:
  display:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 40px
    fontWeight: 600
    lineHeight: 1.1
    letterSpacing: -0.025em
    fontFeature: "cv11, ss01"
  heading-lg:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 24px
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: -0.02em
  heading-md:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 18px
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: -0.012em
  card-title:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 15px
    fontWeight: 600
    lineHeight: 1.3
  body:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 14px
    fontWeight: 500
    lineHeight: 1.4
  caption:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 12px
    fontWeight: 400
    lineHeight: 1.45
  stat:
    fontFamily: "'Inter Variable', Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: 32px
    fontWeight: 600
    lineHeight: 1
    letterSpacing: -0.02em
    fontFeature: tnum
  wordmark:
    fontFamily: "'Gveret Levin', cursive"
    fontWeight: 600

rounded:
  sm: 6px
  md: 8px
  xl: 10px
  lg: 12px
  xxl: 16px
  pill: 9999px

spacing:
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 20px
  xxl: 24px
  section: 36px

components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    height: 36px
    padding: 0 16px
  button-secondary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    border: "1px solid {colors.hairline}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    height: 36px
    padding: 0 16px
  text-input:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    border: "1px solid {colors.hairline-strong}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: 10px 12px
  card:
    backgroundColor: "{colors.canvas}"
    border: "1px solid {colors.hairline}"
    rounded: "{rounded.lg}"
    padding: 20px
  card-tinted:
    backgroundColor: "{colors.tint-sky}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: 20px
  nav-link:
    textColor: "{colors.ink-secondary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 7px 10px
  nav-link-active:
    backgroundColor: "{colors.primary-select}"
    textColor: "{colors.primary-hover}"
  status-pill:
    typography: "{typography.caption}"
    rounded: "{rounded.pill}"
    padding: 2px 10px
---

## Overview

DocuMate is an Operate surface: students, officers, signatories and OSDAS staff come to move a document forward, not to be impressed. The system therefore takes Notion's calm document workspace as its base and borrows Stripe's precision for anything that is data.

**Notion base**
- Warm paper neutrals. The page ground is `{colors.ground}`, the sidebar shell `{colors.shell}`, and cards are white `{colors.canvas}` on a 1px `{colors.hairline}` border.
- The active sidebar row is a soft blue wash (`{colors.primary-select}`), not a solid fill.
- Buttons are 8px rectangles; cards are 12px.
- Pastel tints mark *kinds of work* the way Notion's database properties do (see Signature).

**Stripe details**
- Text is deep navy ink (`{colors.ink}`), never pure black. The neutral ramp runs from warm paper at the light end to Stripe navy at the dark end.
- Shadows are soft and tinted blue, `rgba(0,55,112,…)`, never grey or black halos.
- Headings carry negative tracking (−0.02em at 24px+, −0.025em on the greeting).
- Tables, stat numbers and large figures use tabular numerals, so counts line up.

**Brand**
- DocuMate blue `{colors.primary}` is the only chromatic accent for actions, links, focus and selection.
- The cursive wordmark (Gveret Levin) appears only in the logo and the greeting name.

## Colors

### How it is wired
The Tailwind config (`tailwind.config.js`) remaps the palettes so existing classes follow the system:
- `slate-*`, `gray-*` and `zinc-*` all resolve to the warm-paper → navy-ink ramp: 50 `#fafaf9` · 100 `#f5f4f2` · 200 `#e7e5e1` · 300 `#d4d1cb` · 400 `#9a978f` · 500 `#66707f` · 600 `#4a5568` · 700 `#273951` · 800 `#182c44` · 900 `#0d253d`.
- `blue-*`, `indigo-*` and `brand-*` resolve to the DocuMate blue ramp, with `#2a57b4` at 600.
- The 50/100 steps of `emerald`, `green`, `rose`, `red`, `amber`, `sky`, `violet` and `purple` are replaced by Notion's pastel tints. Their deeper steps stay Tailwind's, so status text keeps its contrast.

CSS custom properties in `resources/css/app.css` (`--dm-*`) mirror these tokens for hand-written CSS.

### Roles
- **Primary** `{colors.primary}`: primary buttons, links, focus outlines, the active nav icon, progress fills for "document" work.
- **Ink** `{colors.ink}` / `{colors.ink-secondary}`: headings and emphasized text. **Body** `{colors.ink-body}`. **Muted** `{colors.ink-mute}` for helper text (≥4.5:1 on white). **Faint** `{colors.ink-faint}` for icons and disabled states only, never for running text.
- **Status**: emerald = cleared/approved/completed, amber = pending/waiting, rose/red = rejected/overdue/error, blue = in review, violet = in signing.

## Typography

- **Inter Variable** is self-hosted through `@fontsource-variable/inter`, imported in `resources/js/app.js`. It is Notion-Sans' open counterpart, set with `cv11` + `ss01` (Stripe's stylistic-set habit).
- Weights: 400 body, 500 labels and buttons, 600 headings and figures. Avoid 700+, which reads as shouting at these sizes.
- Tabular numerals apply automatically to tables and to `text-2xl` and larger; add `tabular-nums` elsewhere when numbers stack.
- Selection is brand tint `#c0d0ee`, the caret is brand blue, and scrollbars are thin `{colors.hairline-strong}` thumbs.

## Layout

- The shell is a 264px sidebar (68px collapsed) on `{colors.shell}`, a 60px white topbar with a hairline bottom border, and content on `{colors.ground}` padded 16px on mobile and 24px from md up.
- Dashboards cap at `max-w-7xl`, with 36px between sections and 16px between cards.
- Container queries (`cq-*`) size cards by the space the sidebar leaves, not the viewport.

## Elevation & Depth

| Level | Token | Use |
|---|---|---|
| 0 | none | Most cards: the hairline border carries the edge |
| 1 | `shadow-sm` · `0 1px 2px rgba(13,37,61,.06)` | Buttons, inputs, resting cards |
| 2 | `shadow-md` | Raised cards, calendar |
| 3 | `shadow-lg` · `0 8px 24px rgba(0,55,112,.08)` | Popovers, tooltips, floating chat button |
| 4 | `shadow-xl` / `2xl` | Modals and drawers |

## Shapes

`rounded` 4px (tags) · `rounded-md` 6px (nav rows) · `rounded-lg` 8px (**buttons, inputs**) · `rounded-xl` 10px · `rounded-2xl` 12px (**cards**) · `rounded-3xl` 16px (large panels) · `rounded-full` (status pills, avatars, badges only).

## Components

- **Buttons**: `<x-primary-button>`, `<x-secondary-button>` and `<x-danger-button>` are 36px tall, 8px radius, 14px/500 sentence-case labels, with a focus ring only on `:focus-visible`. No uppercase, no letter-spaced labels, no scale-on-hover.
- **Inputs**: `<x-text-input>` and `.input` use a white field, `{colors.hairline-strong}` border, 8px radius, and a brand border plus 20% brand ring on focus.
- **Sidebar**: rows are 6px radius on hover `{colors.hover}`; the active row is `{colors.primary-select}` with brand-deep text and a brand icon. Sections are separated by a solid hairline. Count badges are peach `#fdece3` with rust text.
- **Cards**: white, hairline border, 12px radius, 20px padding, titled in 15px/600 ink.
- **Status pills**: full radius, tint background, deep text of the same hue, 1px ring of the 200 step.

## Signature: pastel work folders

The admin dashboard's headline figures sit in **folders**, a nod to the documents DocuMate routes, painted in Notion's database-property tints instead of saturated gradients. Each folder's color names a kind of work:

| Folder | Front / back | Accent |
|---|---|---|
| Requests to review | sky `#dcecfa` / `#bcd5f2` | brand blue |
| Waiting for signatures | lavender `#e6e0f5` / `#d3c9ef` | violet-600 |
| Students cleared | mint `#d9f3e1` / `#b6e3c5` | emerald-600 |
| Appointment requests | yellow `#fef3c7` / `#f3df93` | amber |

Text on every tint is navy ink, so the folders read as one family. Reuse these tint/accent pairs wherever the same kind of work appears, for example the morning (sky) and afternoon (lavender) session cards.

The **greeting header** keeps its time-of-day sky, softened to pale washes and faint mountains. It is DocuMate's quiet equivalent of Stripe's atmospheric header band.

## Do's and Don'ts

### Do
- Use slate/gray utilities and trust the remap. Reach for `brand-*` or `#2a57b4` only for actions and selection.
- Put data in white cards; use a pastel tint only when the color names a kind of work or a status.
- Keep buttons rectangular (8px) and labels in sentence case.
- Use tabular numerals for anything compared or counted.

### Don't
- Don't use saturated gradient fills on cards or buttons; color fields are flat tints.
- Don't use pure black text or grey/black glow shadows.
- Don't use pill-shaped buttons; pills are for status and badges.
- Don't use font weight 700+ for headings, or uppercase tracked labels.
- Don't touch `resources/views/pdf/` and `resources/views/exports/` with this system. Official documents keep their own print styling.

## Responsive Behavior

- Below 1024px the sidebar becomes an overlay drawer; below 768px the topbar drops the long office name and the verify label.
- Dashboard folders go 2 × 2 on phones (figures only) and 4 across from `cq-lg`.
- Touch targets are at least 44px on coarse pointers.
- Motion uses ease-out (`cubic-bezier(0.22, 1, 0.36, 1)`); no bounce or elastic easing. `prefers-reduced-motion` disables decorative motion.

## Sources

Derived from the Notion and Stripe DESIGN.md files in [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md), adapted to DocuMate's brand and its use as a working tool rather than a marketing page.
