# UI/UX current-state audit

Date: 2026-07-26

Authority order: approved SRS, SDD, LLD, UI/UX Design Specification v1.0, then current source. Classification reflects the source before the Executive Editorial continuation changes.

## Baseline

- Stack: Laravel 12.64, Blade, Tailwind CSS, Alpine CSP build and Vite.
- Screen templates: 59 Blade views, including 10 public templates, 17 administration templates, 8 authentication templates, profile views, Breeze components and one custom error page.
- Existing strengths: server-rendered content, localized strings, semantic public landmarks, signed previews/downloads, permission-aware administration links, native form controls and a restrained public color direction.
- Central defect: the public shell has an early editorial treatment, but the authenticated shell, authentication pages and shared Breeze components remain visually generic. The page-specific information architecture and interaction states required by the approved UI/UX specification are incomplete.

## Shared foundations

| Surface | Classification | Evidence | Required correction |
|---|---|---|---|
| Tailwind tokens | Functional but visually non-compliant | custom `impact`, `sand` and `sun` scales exist | map the approved navy, teal, knowledge blue, gold, ink, muted, quiet, danger and derived semantic states centrally |
| Typography | Functional but visually non-compliant | Inter plus Georgia; public headings are editorial | use approved Inter/Arial and Noto Sans Ethiopic stacks, controlled public/admin scales and Amharic line-height rules |
| Public layout | Partially implemented | skip link, header, navigation, search, locale link, main and footer | accessible click/keyboard mega menus, full mobile sheet, focus management, current-page state, breadcrumbs, equivalent-page locale switching and persistent privacy controls |
| Admin layout | Functional but visually non-compliant | Breeze top navigation with permission checks | compact permission-aware sidebar, top context bar, mobile drawer, workflow-oriented content region and responsive table behavior |
| Authentication layout | Functional but visually non-compliant | default centered Breeze card and logo | branded single-purpose shell, clear security context, accessible form states and recovery paths |
| Shared form controls | Partially implemented | reusable Breeze inputs/buttons plus public CSS classes | semantic variants, help/error relationships, error summary, required-field explanation, loading/disabled states and consistent 44 px targets |
| Feedback components | Partially implemented | isolated success/error boxes | reusable alert, persistent status, empty/no-results, loading, warning and destructive-confirmation treatments |
| Overlays | Partially implemented | Breeze modal/dropdown; public `<details>` mobile menu | accessible titles, focus trap/restoration, Escape behavior, scroll control and explicit consequences |
| Localization foundation | Partially implemented | English and 382-key Amharic JSON catalog | equivalent-page switching, complete auth/admin/error copy, long-string reflow and locale-aware presentation audit |

## Public route and screen classification

| Route or screen | Classification | Current evidence | Principal gap |
|---|---|---|---|
| `/`, `/{locale}` home | Partially implemented | editorial hero, services, approach and CTA | required credibility, impact, industries, experts and insight sequence absent; fallback claims and decorative metric are not database evidence |
| `/{locale}/about` | Partially implemented | purpose/promise/standard narrative | history, leadership, methodology, credentials, partners, locations, ethics, quality and sustainability structures absent |
| Services index | Partially implemented | paginated generic cards | introduction, categories/search/filter/reset, result count and service-specific empty state absent |
| Service detail | Partially implemented | generic field renderer and CTA | challenges, phases, deliverables, outcomes, related evidence/experts/insights and selected-service CTA context incomplete |
| Industries index/detail | Partially implemented | generic collection/detail templates | sector-specific filters, relationships, contextual evidence and tailored CTA incomplete |
| Experts index/profile | Partially implemented | generic collection/detail templates | portrait treatment, result count, filters/chips, credentials/languages/relationships and controlled inquiry context incomplete |
| Case studies index/detail | Partially implemented | generic collection/detail templates | outcome-first card, approved metric context, confidentiality-aware client treatment and full narrative/relationship sequence incomplete |
| Insights index/detail | Partially implemented | generic collection/detail templates | knowledge filters, metadata, reading layout, report/download states, citations and related content incomplete |
| Events index/detail | Partially implemented | generic index plus event registration form | explicit lifecycle/capacity/accessibility states, agenda/speakers and localized date presentation incomplete |
| Careers index/vacancy | Partially implemented | generic vacancy collection and application form | recruitment narrative/process, stronger expired state, full vacancy sections and upload state feedback incomplete |
| Search | Partially implemented | query, result count, pagination and empty state | content-type/filter controls, active chips and richer recovery paths absent |
| Consultation | Partially implemented | secure single-page localized form | approved five-step need/organization/project/review/confirmation journey and focused error recovery absent |
| RFP | Partially implemented | secure localized multipart intake | approved step/review flow, selected-file metadata and client-visible security-processing states incomplete |
| Contact/partnership/media | Partially implemented | differentiated type selector and privacy cue | user-need route cards, office alternatives and inquiry-specific confirmation treatment incomplete |
| Newsletter | Partially implemented | labeled footer form, required consent and double opt-in backend | preference-center route and richer inline feedback absent |
| Legal/accessibility pages | Missing | no dedicated views/routes in current route inventory | privacy, terms, cookies, accessibility statement and barrier-reporting presentation depend on approved content and routes |
| 403/404/419/429/500/503 | Missing | only custom 409 exists | branded localized recovery pages and safe incident/retry guidance required |

## Administration route and screen classification

| Screen | Classification | Current evidence | Principal gap |
|---|---|---|---|
| Dashboard | Functional but visually non-compliant | four real role-neutral metrics | attention hierarchy, recent work/quick actions and permission-scoped truthful system status incomplete |
| Content list | Functional but visually non-compliant | state/type filters, table and pagination | compact filter toolbar, count, responsive records, selected/current states and clearer workflow badges |
| Content create/edit | Partially implemented | structured version fields and server validation | status/SEO/accessibility context, error summary, unsaved-state feedback and production-like responsive preview controls |
| Content detail/workflow | Partially implemented | current state, allowed backend transitions, schedule, history and rollback | clearer workflow panel/timeline, timezone and public-impact explanations, translation/SEO/accessibility status |
| Media queue | Partially implemented | secure upload, scan/processing states and approval action | preview/metadata grid, alt/rights decisions, usage references and responsive record treatment |
| Engagement inbox/detail | Partially implemented | queue, detail, attachments, assignment/status history | differentiated intake queues, compact filters, audit/internal-note hierarchy and responsive detail treatment |
| Applications list/detail | Partially implemented | status filtering, restricted files and transition history | responsive record cards, clearer privacy/retention cues and safe export state |
| Users | Functional but visually non-compliant | invitation, search/status filter and management | clearer separation of invite/list tasks, MFA/security state presentation and responsive records |
| Roles/permissions | Functional but visually non-compliant | grouped permission controls and delegated backend limits | business-language risk hierarchy, high-risk warnings and confirmation context |
| Audit | Functional but visually non-compliant | read-only paginated table | actor/action/object/date filters, semantic event presentation and mobile pattern |
| Settings | Functional but visually non-compliant | typed settings and backend validation | grouped consequences, deployment/restart cues and high-impact confirmation |
| Profile | Functional but visually non-compliant | Breeze profile/password/delete forms | align with compact security settings language and explicit destructive consequences |

## Authentication classification

| Screen | Classification | Principal gap |
|---|---|---|
| Login | Functional but visually non-compliant | default starter-kit identity, hierarchy and control styling |
| MFA and recovery | Partially implemented | branded explanation, accessible verification guidance and clearer recovery hierarchy |
| Invitation acceptance | Partially implemented | brand/security context and error/expiration presentation |
| Password reset/confirm/forgot | Functional but visually non-compliant | branded shell, concise microcopy and consistent feedback |
| Email verification | Functional but visually non-compliant | brand hierarchy and session/action context |
| Registration template | Broken/obsolete presentation | route is intentionally unavailable; template must not imply open staff registration |

## Cross-cutting high-priority defects

1. Approved semantic colors are not represented as the authoritative token names and several templates still use generic gray/indigo utilities.
2. Public navigation depends on a compact desktop list and a small dropdown; it has no approved mega-menu or full mobile-sheet behavior.
3. The alternate-language header/footer destinations fall back to the locale homepage instead of retaining the current equivalent route.
4. Homepage fallback service copy and the decorative `360°` claim are not approved database evidence.
5. Long public collection/detail types share generic templates that cannot communicate the required page-specific hierarchy and relationships.
6. Public form error summaries are not consistently focused or linked to invalid fields.
7. Administration remains a generic horizontal Breeze interface and tables rely primarily on overflow at narrow widths.
8. Only one custom error page exists.
9. Empty states exist on several lists, but loading, permission-denied, long-content, missing-image and destructive-confirmation states are inconsistent.
10. Browser-level focus restoration, menu trapping, keyboard operation, reduced motion, 200% zoom and bilingual viewport evidence must be produced after implementation.

This audit is a starting-state record, not a completion claim. The implementation and visual-QA reports supersede classifications only where source and browser evidence are recorded.
