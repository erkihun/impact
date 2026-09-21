# Public UI Current-State Audit

## Scope

This audit covers the localized public experience defined by the approved SRS, SDD, LLD, and UI/UX design specification. It includes the global shell, all routed public screens, empty and error states, public forms, consent, search, responsive behavior, and English/Amharic rendering.

## Baseline findings

The starting implementation already had a credible Executive Editorial direction on the home page: restrained navy, teal, blue, and gold; strong information hierarchy; useful editorial numbering; a working mega menu and mobile sheet; and substantive public content. It was not a blank-slate redesign.

The main gaps were:

- settings were resolved directly inside the public Blade layout and consent component;
- search used an older visual language and lacked the shared page-header, result, and recovery patterns;
- About, event, and vacancy pages used older shell and card treatments;
- native file inputs did not provide a coherent secure-upload presentation;
- repeated search/result patterns were not shared components;
- the settings/status work introduced untranslated literal labels;
- the narrow vacancy form overflowed after the initial secure-upload treatment;
- no current route-by-width, keyboard-behavior, or visual evidence pack existed.

## Implemented corrections

- Public settings now pass through `PublicUiSettings::viewData()` and a view composer. Public Blade no longer queries `EffectiveSettings`, `PublicUiSettings`, or the privacy config directly.
- Search now uses shared search form, result count, search result, page header, breadcrumbs, pagination, and recovery actions.
- About is an editorial institutional-commitment narrative instead of a generic three-card row.
- Event and vacancy screens use the shared page-header system, localized date output, editorial forms, clear unavailable states, and recovery links.
- CV and RFP attachment inputs use a shared secure file-upload component.
- The file input is responsive down to 375 px without horizontal overflow.
- Missing Amharic translations were completed, and Abyssinica SIL remains the self-hosted Ethiopic typeface.

## Current assessment

The routed public UI is visually coherent and behaviorally intact. The remaining gaps are content and product dependencies rather than disconnected page styling:

- there is no dedicated report/download repository route or report-specific backend model;
- partnership intake is represented by the existing typed contact route, not a separate application workflow;
- some Amharic collections have no published records, so those sections correctly render their current data/empty state;
- address, contact, and organization identity values remain whatever administrators configure in settings.

