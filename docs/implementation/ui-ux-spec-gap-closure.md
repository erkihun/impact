# UI/UX specification gap closure

Date: 2026-07-28

Authority: `docs/impact_Consulting_Organization_Website_UI_UX_Design_Specification.docx` v1.0.

This note records the end-to-end UI/UX pass carried out against the approved specification, on top of the
baseline described in `ui-ux-final-report.md`. It covers only what changed in this pass and what remains open.

## Starting position

The existing implementation already satisfied most of the visual system: the approved semantic colour tokens,
Inter / Noto Sans Ethiopic stacks, spacing and elevation tokens, focus ring, accessible mega menu and mobile
sheet, breadcrumbs, multi-step consultation and RFP flows, and branded error and authentication surfaces.

Audit against Appendix B (Page Inventory) and Appendix C (Requirement Register) identified four unmet
requirements that were not blocked by missing data or content.

## Delivered in this pass

| Requirement | Specification reference | Change |
|---|---|---|
| Consent interface | 15.2, UIUX-10-08, UIUX-10-09 | Consent banner and preference centre added. The `ConsentController` and `/consent` endpoint existed but had no interface, so no consent was ever collected. |
| Privacy, cookie and terms pages | Appendix B, UIUX-06-12, UIUX-10-12 | Privacy notice, cookie notice and terms of use published at `{locale}/privacy`, `{locale}/cookies`, `{locale}/terms`. |
| Accessibility statement | 8.10, 14.3, Appendix B | Accessibility statement published at `{locale}/accessibility`, including known limitations and a barrier-reporting route. |
| Error recovery routes | 14.4, UIUX-06-13 | Error pages now offer search, services, insights and contact rather than only home and contact. |
| Footer utility and legal access | 8.1, UIUX-10-12 | Footer rebuilt with full sitemap groups, semantic `nav` landmarks, and persistent privacy, cookie, terms, accessibility and privacy-choices links. |

### Consent interface detail

- Nothing optional is stored before a decision is recorded; only necessary storage is set on arrival.
- The first layer offers Accept optional, Reject optional and Manage choices with equal visual weight, using
  the same button treatment for each, as required by the prohibition on manipulative design.
- The preference centre groups purposes (necessary, preferences, analytics, marketing), explains each in plain
  language, traps focus, closes on Escape and is reachable again at any time from the footer.
- The recorded decision is bound to the current `impact.privacy.policy_version`. A stored decision against a
  superseded version is discarded and the user is asked again. The endpoint rejects a stale version with 422.
- Implemented against the `@alpinejs/csp` build: directives resolve to registered data properties and methods
  only, with no inline expressions, so the interface remains compatible with the deployed CSP.

## Verification

| Gate | Result |
|---|---|
| Vite production build | pass |
| Pint on changed files | pass |
| PHPStan | pass, no errors |
| Full test suite | 124 passed, 1 failed (pre-existing, see below) |
| UI/UX regression suite | 19 passed, 94 assertions |
| Amharic key coverage test | pass, 830 keys |
| New routes rendered (en and am) | 200 for privacy, cookies, terms, accessibility |
| Consent endpoint contract | 200 with current policy version, 422 with a superseded version, record persisted |
| 404 recovery routes | search, services, insights and contact confirmed rendered |

Seven regression tests were added to `tests/Feature/UiUxExperienceTest.php` so these requirements stay enforced.

## Known issues not introduced by this pass

- `MediaLifecycleTest` fails locally because the `imagick` PHP extension is not installed on this workstation
  (only `gd` is present). It touches no file changed in this pass and requires the extension in CI and production.
- `database/seeders/DevelopmentAdminSeeder.php` has outstanding Pint style findings that predate this pass and
  were left unchanged to keep the diff scoped.

## Open dependencies

The following specification items remain unmet because they depend on data, routes or approved content that do
not yet exist, rather than on interface work:

- Leadership, methodology, office detail and dedicated newsletter pages from Appendix B.
- Credibility strip content: client and partner logos, certifications and quantified reach, all of which the
  specification permits only with recorded approval.
- Event agenda, speaker and capacity fields; insight citation, author and download relationships.
- A public post-submission status route for RFP file processing.

The Amharic legal and accessibility copy added here is a working translation and requires professional native
review before production sign-off, consistent with the specification's requirement that English and Amharic be
equal content experiences.

Independent WCAG 2.2 AA assessment with assistive technology remains an open acceptance dependency. The
specification is explicit that automated checks alone are not sufficient evidence of conformance.
