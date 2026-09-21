# UI/UX visual QA

Date: 2026-07-26

Method: Chromium through Playwright CLI against `http://127.0.0.1:8000` with non-production public fixtures. Screenshots are stored under `output/playwright/`.

## Sampled evidence

| Screen | Viewports/locales | Result |
|---|---|---|
| Home | 1440×1000, 1280×900, 1024×900, 768×900, 390×844 English; 1440×1000 and 390×844 Amharic | Pass: coherent hierarchy, no clipping or horizontal overflow |
| Desktop mega menu | 1440 English | Pass: click opens, `aria-expanded=true`; Escape closes and restores Services trigger focus |
| Mobile public navigation | 390 English | Pass: named dialog, close control focused, body locked, backward focus wraps to final Contact link, Escape restores opener and body |
| Consultation | 390 English | Pass: required native validation blocks progression; valid challenge advances to Organization and focuses the step heading |
| RFP | 390 English | Pass: all four step names remain in the accessibility tree; zero overflow; security-processing copy visible |
| Login and MFA | 390 English | Pass: focused task hierarchy, enrollment and recovery-code screens |
| Admin dashboard | 1440 and 390 English | Pass: compact desktop sidebar, mobile stacking, real permission-scoped counts, zero overflow |
| Admin mobile drawer | 390 English | Pass: named dialog, permission-aware links, Escape close and opener focus restoration |

## Visual review findings

- Public direction is restrained Executive Editorial: high-contrast navy fields, teal action, gold used as a small accent, generous whitespace and evidence-led section sequencing.
- The hero remains readable without relying on photography or fabricated client marks.
- Cards use consistent radius, border and editorial shadow. Content is not truncated to arbitrary heights.
- Desktop at 1024 px is compact but remains usable without collision or horizontal scrolling.
- Tablet switches to the mobile navigation below the desktop breakpoint and maintains hierarchy.
- Mobile CTA groups stack, card grids collapse, tables are contained by their page-level overflow treatment, and footer controls fit the viewport.
- The administration surface is intentionally denser than the public site and preserves clear task/action separation.
- Empty content is omitted or presented as an explicit empty state; it is not replaced with invented metrics, logos, testimonials or approvals.

## Browser evidence

Representative artifacts:

- `home-en-1440.png`
- `home-en-1280.png`
- `home-en-1024.png`
- `home-en-768.png`
- `home-en-390.png`
- `home-am-1440.png`
- `home-am-390.png`
- `rfp-en-390.png`
- `login-390.png`
- `admin-dashboard-1440.png`
- `admin-dashboard-390.png`

Browser console on the sampled English/Amharic public flows: 0 errors and 0 warnings.

## Outstanding visual acceptance

- Real production images, long-form documents, unusually long editorial titles and missing-image records require a content-populated staging review.
- Safari/WebKit, Firefox, forced colors, 400% zoom and physical touch-device checks were not executed in this local Chromium pass.
- An independent design-owner sign-off against every page in the source specification is still an external acceptance gate.
