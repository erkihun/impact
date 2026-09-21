# UI component inventory

Date: 2026-07-26

Authority: approved UI/UX Design Specification v1.0 and the implemented Laravel 12 application.

## Design foundations

| Foundation | Implementation | Contract |
|---|---|---|
| Core color system | `tailwind.config.js`, `resources/css/app.css` | Navy `#17324D`, teal `#2D7A78`, knowledge blue `#2D6C99`, gold `#C99A2E`, ink `#1F2933`, muted `#5D6A74`, quiet `#F2F4F6`, danger `#C0392B`, white |
| Typography | Tailwind font families and locale selector in `app.css` | Inter/Arial for Latin; Noto Sans Ethiopic first for Amharic; compact administration hierarchy |
| Spacing and layout | `.content-container`, `.site-shell`, `.reading-width`, `.admin-workspace` | responsive 4/8/12-column behavior, readable line length, no fixed content height |
| Focus and motion | global `:focus-visible`, `prefers-reduced-motion` | visible knowledge-blue focus, motion reduction respected |
| Elevation and radius | `shadow-editorial`, `shadow-overlay`, shared `rounded-xl` | restrained editorial surfaces; no decorative glassmorphism |

## Layout components

| Component | Source | States and behavior |
|---|---|---|
| Public shell | `layouts/public.blade.php` | skip link, utility bar, sticky header, landmarks, status region, equivalent-locale link, footer |
| Desktop mega menu | public shell + `publicNavigation` | click/keyboard opening, one menu at a time, Escape close, outside click, trigger focus restoration |
| Mobile navigation sheet | public shell + `publicNavigation` | dialog semantics, explicit close, body scroll lock, focus trap/restoration, search/language/consultation first |
| Admin shell | `layouts/app.blade.php`, `layouts/navigation.blade.php` | noindex, fixed compact sidebar, mobile dialog drawer, permission-filtered links, profile/public-site routes |
| Authentication shell | `layouts/guest.blade.php` | branded split layout, single-purpose card, recovery/security context |
| Public page header | `components/ui/page-header.blade.php` | eyebrow, single H1, optional description and actions |
| Breadcrumbs | `components/ui/breadcrumbs.blade.php` | named navigation, linked ancestors, current-page state |

## Controls and feedback

| Component | Source | Implemented states |
|---|---|---|
| Primary, secondary and danger buttons | Blade button components + semantic CSS classes | default, hover, focus, disabled; minimum 44 px target |
| Text input and label | `text-input`, `input-label`, `input-error` | default, focus, invalid, disabled, help/error associations at page level |
| Error summary | `components/ui/error-summary.blade.php` | assertive summary, field anchors, document-load focus |
| Status badge | `components/ui/status-badge.blade.php` | neutral, information, success, warning, danger |
| Empty state | `components/ui/empty-state.blade.php` | title, explanation, optional recovery action |
| Modal | `components/modal.blade.php`, Alpine `modal` | dialog naming, Escape, overlay close where allowed, focus trap/restoration, body lock |
| Dropdown | `components/dropdown.blade.php`, Alpine `dropdown` | trigger state, outside/Escape close, keyboard-compatible native links/buttons |
| Duplicate-submit guard | `form[data-prevent-duplicate]` | disables the submitter after a valid submit event |
| Multi-step form | Alpine `multiStepForm` | native validity before forward movement, previous-step editing, heading focus, live review, no forward-step bypass |

## Content patterns

| Pattern | Screens |
|---|---|
| Editorial hero and evidence-led sections | home |
| Searchable collection with count, cards, empty state and pagination | services, industries, experts, case studies, insights |
| Contextual detail with at-a-glance sidebar and inquiry context | service, industry, expert, case study, insight |
| Secure task flow | consultation and RFP |
| Lifecycle-aware intake | event registration and vacancy application |
| Permission-scoped attention dashboard | administration dashboard |
| Safe recovery page | 403, 404, 409, 419, 429, 500, 503 |

## Known component gaps

- No date picker, tabs, toast stack, data visualization, rich-text editor, media cropper or sortable data grid is invented because the current backend and route inventory do not require or support those controls.
- CMS forms still use several page-local field/table compositions. They inherit the semantic admin shell and control tokens but have not all been decomposed into standalone Blade components.
- Loading skeletons are not shown for server-rendered navigation because navigation completes before HTML is rendered; submitting forms use the duplicate-submit state.

## Admin primitives (added 2026-07-28)

Located in `resources/views/components/admin/`. These make admin page identity and list structure a single
definition rather than a per-view reinvention.

| Component | Props | Notes |
|---|---|---|
| `page-header` | `eyebrow`, `title`, `description`, `action` slot | Used by every admin screen |
| `filter-bar` | `legend`, `hasActiveFilters`, `clearUrl`, `action`, `method` | Real GET form; works without JavaScript |
| `result-summary` | `paginator` or `total`, `context` | `role="status"` so filtering announces its result |
| `record-list` | `columns`, `label` | Table ≥1024 px, stacked cards below |
| `cell` | `label`, `primary`, `numeric` | `label` becomes the stacked-view field label |
| `row-actions` | `label` | Shared button system, 44 px targets |
| `section` | `title`, `description`, `id` | Groups related form fields |

Retired: `components/nav-link.blade.php`, `components/responsive-nav-link.blade.php` (unused Breeze scaffold).

Fixed for the `@alpinejs/csp` build: `components/modal.blade.php`, `components/dropdown.blade.php`,
`layouts/public.blade.php`, `layouts/navigation.blade.php`, `public/consultation.blade.php`,
`public/rfp.blade.php`. All directives now bind to bare property/method names.
