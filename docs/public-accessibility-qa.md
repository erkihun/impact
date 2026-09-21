# Public Accessibility QA

## Implemented controls

- skip link targets the focusable main content;
- semantic header, navigation, main, footer, article, section, aside, and form landmarks;
- one page-level H1 per tested route;
- visible focus styles use the shared focus token;
- desktop menu and mobile dialog support Escape and focus restoration;
- mobile navigation and consent dialogs trap focus;
- consent offers equal manage, reject, and accept actions;
- validation errors use `aria-invalid`, field messages, and a shared error summary;
- status and submission feedback use live-region semantics;
- images use meaningful alternative text or intentional empty alt for decoration;
- reduced-motion settings and `prefers-reduced-motion` are respected;
- touch targets are at least 44 px in shared controls;
- Amharic typography and line height are explicitly supported.

## Browser checks

Twelve representative route archetypes were scanned for:

- duplicate IDs;
- visible links/buttons without accessible names;
- visible form controls without labels.

Final result: **12/12 routes passed with no findings**.

The full responsive matrix also checked H1 count, main landmark, image alt presence, language, and overflow across 208 route/viewport combinations.

## Limitation

No Axe, Pa11y, or Lighthouse accessibility dependency is configured in this repository, so this is not represented as a third-party WCAG certification. Manual assistive-technology testing remains a release activity for formal WCAG 2.2 AA sign-off.

