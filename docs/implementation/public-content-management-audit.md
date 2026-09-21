# Public content management audit

Date: 2026-07-29  
Scope: public routes, Blade templates, global navigation/footer, media, workflow, SEO, localization, and administration.

## Baseline findings

The original public experience used approved shared layouts and strong typed domain records, but page structure was selected inside controllers and substantial headings, summaries, CTA copy, and section order remained in Blade. `public/home.blade.php`, `public/collection.blade.php`, `public/detail.blade.php`, About, forms, legal pages, event, and vacancy views were the main structure owners. Navigation and footer links were declared in `layouts/public.blade.php`. There was no composition persistence or section registry.

The existing platform already supplied useful foundations: immutable content versions, workflow events, RBAC, audit recording, secure media processing, localized public catalog versions, settings, signed previews, public-form protections, and responsive design-system components.

## Implemented control boundary

- 26 page keys have published EN and AM compositions.
- Visible H1/header content is resolved from the published composition on home, About, collections, details, events, careers, engagement forms, search, and legal surfaces.
- Catalog bodies remain owned by their typed domain records; secure form schemas remain fixed in application code.
- Primary labels and footer link records are controlled through `page_navigation_configurations`.
- Page sections can use only registered types, fields, variants, presentation enums, library media, typed relations, and typed actions.
- Published compositions are read-only. Editors create a cloned draft before modification.
- Each section save creates a new immutable section version and uses optimistic locking.

## Route inventory

| Family | Route names | Page keys |
|---|---|---|
| Home/institutional | `localized-home`, `about.show` | `home`, `about` |
| Directories/details | services, industries, experts, case studies, insights | matching `.index` and `.show` keys |
| Events/careers | `events.*`, `careers.*` | matching `.index` and `.show` keys |
| Engagement | consultation, RFP, contact | `consultation`, `rfp`, `contact` |
| Legal | privacy, terms, cookies, accessibility | matching `legal.*` keys |
| Discovery/error | search, 404, 500 | `search`, `errors.404`, `errors.500` |

## Residual intentional code ownership

Security-sensitive form field schemas, consent text contracts, validation, catalog query semantics, legal-body arrays already governed by reviewed source, and workflow enforcement remain application-owned. Editors control the surrounding page introduction and composition without gaining arbitrary HTML, JavaScript, route, query, or upload-path access.
