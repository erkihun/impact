# Modern UI current-state audit

Date: 2026-07-28

Method: the running application was inspected, not only the source. A headless Chromium harness
(`scripts/ui-shots.mjs`) captured 120 screenshots across 30 routes at 390 / 768 / 1280 / 1920 px, in English
and Amharic, signed in as a privileged operator through the real login and MFA challenge. Evidence is under
`output/ui-shots/before/` and `output/ui-shots/before-admin/`, with machine-readable results in each
`findings.json`.

## Headline finding

The brief's premise — that the interface is uniformly boring and generic — is **half correct, and the correct
half is the important one.**

The public website is already a deliberate, distinctive editorial design. The administration panel is not.
**Two different design systems are live in the same application at the same time**, and that is the actual
cause of the inconsistency the brief describes.

| Area | State | Evidence |
|---|---|---|
| Public site | Designed. Editorial hierarchy, asymmetric layouts, numbered sections, restrained palette. | `before/home-desktop.png` |
| Admin (redesigned subset) | Designed. Eyebrow, title, description, primary action, filter rail, contextual panel. | `before-admin/admin-content-desktop.png` |
| Admin (legacy subset) | Generic Laravel starter kit. Bare heading, raw table, `text-gray-*`, tiny text-link actions. | `before-admin/admin-users-desktop.png`, `admin-audit-desktop.png`, `admin-settings-desktop.png` |

Measured split: **20 Blade views contain 112 occurrences of the legacy `gray-*` palette**, every one of them in
`admin/` or `profile/`. No public view uses it. This is the single highest-value defect in the application.

## Confirmed defects

### D1 — Two parallel design systems (Critical)

`admin/engagement/index`, `admin/audit/index`, `admin/roles/index`, `admin/media/index`,
`admin/settings/edit`, `admin/users/index`, and all four `profile/` views are styled with Tailwind's default
gray ramp and starter-kit structure (`text-gray-800`, `bg-white shadow-sm`, `max-w-7xl`), while
`admin/content/index` and `admin/dashboard` use the project's design system. Adjacent screens in the same
sidebar look like different products.

### D2 — Admin tables overflow on mobile (High)

Measured horizontal overflow with no mobile record pattern:

| Route | Viewport | Overflow |
|---|---|---|
| `/admin/engagement` | 390 px | 106 px |
| `/admin/users` | 390 px | 86 px |

The specification requires a structured record view on small screens rather than a compressed table
(UIUX-04-05). `before-admin/admin-engagement-mobile.png` shows columns truncated off-screen.

### D3 — Inconsistent page headers (High)

`admin/content/index` renders eyebrow + title + description + primary action. `admin/users/index`,
`admin/audit/index` and `admin/settings/edit` render a bare `<h1>` in a thin bar with no description, no
context and no primary action. There is no shared admin page-header component, so every view reinvents it.

### D4 — Audit view has no filters (High)

The specification requires audit views filterable by actor, action, object, date and result (UIUX-08-13). The
current view is an unfiltered table. It also prints raw UUID actor IDs and correlation IDs as the only actor
identity, which is unreadable for an operator.

### D5 — Settings is one ungrouped form (Medium)

Thirteen unrelated fields — organization identity, contact details, branding, SEO, locale, analytics and
privacy version — are stacked in a single white card with no grouping. The specification requires settings to
group related controls and identify changes that need deployment (section 10.9).

### D6 — Tiny text-link row actions (Medium)

`Manage`, `Approve` and `Download` are rendered as bare underlined text links inside table cells, below the
44 px touch target the specification requires (UIUX-04-03) and inconsistent with the button system used
elsewhere.

### D7 — Users list mixes creation with browsing (Medium)

`admin/users/index` places a full invitation form above the user table, so the page has two competing primary
purposes and no clear dominant action (UIUX-01-02).

### D8 — Dashboard is not operational (Medium)

Four KPI tiles and a quick-action card, then a large empty region below the fold. The specification requires
the dashboard to prioritise work needing attention — drafts awaiting review, expiring content, failed jobs,
recent work — rather than decorative counts (UIUX-08-02).

### D9 — Media queue lacks required metadata (Medium)

The media library shows file, scan, processing and created date. The specification additionally requires
dimensions, file size, alternative-text decisions, rights/source and usage references (UIUX-08-09), and warns
before deleting an asset in use.

### D10 — Legacy shell components (Low)

`components/nav-link.blade.php` and `components/responsive-nav-link.blade.php` remain starter-kit artifacts
using `gray-*`, retained from the original Breeze scaffold.

## What is already correct and must not be regressed

Verified across 120 screenshots:

- Zero horizontal overflow on **all 20 public routes** at every tested width, in both locales.
- Exactly one `<h1>` on every route tested, desktop and mobile.
- Amharic renders with `lang="am"`, correct Ethiopic glyphs and no clipping.
- The approved token palette is already defined in `tailwind.config.js` and `resources/css/app.css`.
- Public header, mega menu, mobile sheet, breadcrumbs, consent banner and legal pages are spec-conformant.
- Consultation and RFP flows implement the required Need → Organization → Project → Review → Confirmation
  sequence with duplicate-submit protection.

A full public-site "redesign" is therefore **not** the highest-value work, and rebuilding it would risk
regressing verified accessibility and responsive behaviour. The work is concentrated where the evidence points.

## Content constraint

The database holds 3 services, 3 industries, 1 expert, 1 case study, 1 insight and **0 media assets**. Several
brief requirements — hero imagery, expert portraits, case-study imagery, industry visuals, image crops,
responsive `srcset` — cannot be visually verified without approved assets. The specification prohibits
placeholder copy and unapproved stock imagery in production (UIUX-11-08), so these are recorded as asset
dependencies rather than satisfied by inventing content.

## Work plan derived from this audit

1. Extend the design system with the admin primitives that are missing (page header, data table, record card,
   filter bar, row actions, section grouping, KPI, empty/loading states).
2. Rebuild the 20 legacy views on those primitives, removing all 112 `gray-*` occurrences.
3. Fix the two measured mobile overflows with a real record-card pattern.
4. Add audit filters, group settings, split user invitation from the user list, make the dashboard operational.
5. Re-run the harness and compare before/after evidence.
