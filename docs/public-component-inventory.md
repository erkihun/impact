# Public Component Inventory

## Global and navigation

| Component or equivalent | Implementation | States/notes |
|---|---|---|
| public shell | `resources/views/layouts/public.blade.php` | metadata, status banner, header, main, footer, consent |
| mega menu | public layout + `publicNavigation` | click, outside click, Escape, focus restoration |
| mobile navigation | public layout + `publicNavigation` | dialog semantics, focus trap, Escape, body scroll lock |
| breadcrumbs | `components/ui/breadcrumbs.blade.php` | current item, settings-controlled visibility |
| page header | `components/ui/page-header.blade.php` | standard, knowledge, paper variants |

## Editorial content

| Component | Purpose |
|---|---|
| `editorial-number` | ordered evidence, collection, and process markers |
| `insight-marker` | small editorial category marker |
| `impact-line` | restrained brand divider |
| `impact-index` | compact indexed content structure |
| `page-header` | shared title, description, metadata, actions |
| `legal-page` | legal and policy page structure |

## Search and discovery

| Component | Purpose |
|---|---|
| `search-form` | global or collection search, compact and full variants |
| `result-count` | live result count and active query summary |
| `search-result` | typed, numbered result row |
| `empty-state` | no-data/no-result explanation and recovery actions |

## Forms and feedback

| Component or equivalent | Purpose |
|---|---|
| `file-upload` | secure CV/RFP attachment selection with responsive browser control |
| `error-summary` | linked validation summary |
| `.form-label`, `.form-input`, `.form-help`, `.field-error` | common field, help, invalid and error states |
| multi-step form behavior | consultation and RFP progress, validation, review, previous/next |
| status badge/surfaces | success, warning, danger, information, neutral |

## Privacy and overlays

| Component | Purpose |
|---|---|
| `consent-banner` | equal first-layer choices and detailed preference dialog |
| mobile sheet | accessible navigation overlay |
| error page | shared 404 and recovery treatment |

## Reuse rule

New public screens should assemble these components and semantic classes. They should not introduce a page-local color system, duplicated header/footer markup, direct settings lookup, or a new search/form state pattern.

