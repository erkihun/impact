# Modern design system

Date: 2026-07-28

One design system now covers the public website and the administration panel. The public site expresses it at
editorial density; the admin panel expresses it at operational density. They share tokens, typography, focus
behaviour, status language and component structure.

## Token source of truth

Tokens are declared once and consumed everywhere. No Blade template contains an arbitrary hex value.

| Layer | File | Contains |
|---|---|---|
| Palette scales | `tailwind.config.js` | `brand`, `action`, `knowledge`, `gold`, `ink`, `muted`, `quiet`, `danger` scales derived from the approved core colours |
| Semantic roles | `resources/css/app.css` `:root` | surface, border, text, state, overlay, skeleton, focus-ring custom properties |
| Component classes | `resources/css/app.css` `@layer components` | buttons, forms, cards, navigation, admin shell, record list |

Approved core values, unchanged from the UI/UX specification: navy `#17324D`, teal `#2D7A78`, knowledge blue
`#2D6C99`, gold `#C99A2E`, ink `#1F2933`, muted `#5D6A74`, quiet surface `#F2F4F6`, danger `#C0392B`.

A regression test asserts these appear in `tailwind.config.js`, and a second test fails the build if any view
reintroduces Tailwind's default `gray-*` ramp.

## Typography

`Inter` for Latin, `Noto Sans Ethiopic` for Amharic, applied through one `font-sans` stack so a locale switch
never changes layout ownership. Amharic overrides line height and disables negative letter-spacing, because
Ethiopic glyphs need more vertical room and are damaged by tight tracking.

Public headings use `heading-1` / `heading-2` / `heading-3`. Admin headings use the same family at reduced
size, which is what makes the two surfaces read as one product at different densities.

## Density

This is the deliberate difference between the two surfaces.

| Dimension | Public | Admin |
|---|---|---|
| Section rhythm | 64–96 px | 24–32 px |
| Body size | 16–18 px | 14–15 px |
| Container | `content-container`, max 90rem | `admin-workspace`, max 100rem |
| Card padding | 24–32 px | 20–24 px |
| Emphasis | Editorial whitespace and rules | Information density and scanability |

## Admin component library

Added in this pass, in `resources/views/components/admin/`:

| Component | Purpose |
|---|---|
| `page-header` | Eyebrow, H1, description, optional primary action. Every admin screen uses it, so page identity is structurally identical everywhere. |
| `filter-bar` | Real `GET` form. Renders `Apply filters`, and `Clear filters` only when a filter is active. Works without JavaScript. |
| `result-summary` | Result count with `role="status"`, so filtering always reports its outcome. |
| `record-list` | Responsive list. See below. |
| `cell` | A record cell. `data-label` supplies the field label in stacked view. |
| `row-actions` | Action group using the shared button system, not bare text links. |
| `section` | Groups related fields with a heading and explanation. Used to break long forms apart. |

### The record list

One markup path, two presentations, no duplicated templates:

- **≥1024 px** — a real `<table>` with `<th scope="col">` headers.
- **<1024 px** — `thead` hides, each `<tr>` becomes a bordered card, and each `<td>` prints its column name
  from `data-label` via a CSS `::before`.

This is what removed the measured horizontal overflow on `/admin/engagement` (106 px) and `/admin/users`
(86 px) without shrinking type or forcing a scroll container.

## Alpine and the CSP build

The project ships `@alpinejs/csp`, which **evaluates only bare property and method names**. Inline expressions
never run. This was verified against the build itself: `x-on:click="inc()"` does not fire, while
`x-on:click="inc"` does.

Every directive in the codebase now follows the contract:

- `x-on:click="methodName"` — no parentheses, no arguments.
- Arguments travel on data attributes (`data-menu`, `data-step-target`, `data-modal-name`) and are read from
  `event.currentTarget` or `this.$el`.
- Conditions are exposed as getters (`servicesExpanded`, `onStep2`, `canGoBack`) rather than written inline.

A regression test scans every view for inline expressions and fails if one is reintroduced. This closed real
latent defects — the mega menus, the profile delete dialog and the multi-step form controls had bindings that
silently never executed.

## Motion and accessibility

Transitions stay within the specification's 120–260 ms range and are removed under `prefers-reduced-motion`,
which is handled globally in `app.css` rather than per component. Focus rings use one token and are never
removed. Status is always carried by text plus colour, never colour alone.
