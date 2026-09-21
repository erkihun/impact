# Remarkable UI accessibility review

Date: 2026-07-26

## Implemented controls

- Skip links target the public, authentication, and admin main regions.
- Public and admin navigation expose named landmarks.
- Mobile menus use `role="dialog"`, `aria-modal`, labelled controls, focus trapping, Escape close, and trigger focus restoration.
- Every reviewed page has one `h1`.
- Form controls retain programmatic labels, required state, help text, invalid state, and error association.
- Multi-step forms preserve focusable step headings and validation-gated progression.
- Status is communicated in text and not color alone.
- Decorative artwork uses `alt=""`; meaningful image requirements remain data-driven.
- Reduced-motion CSS disables non-essential transitions and smooth scrolling.
- Focus-visible outlines and 44px minimum targets remain global.

## Browser checks

| Check | Result |
| --- | --- |
| Homepage images without `alt` | 0 |
| Homepage nameless links | 0 |
| Homepage nameless buttons | 0 |
| Consultation unlabeled fields | 0 |
| Admin content unlabeled fields | 0 |
| Mobile menu focus on open | Close button received focus |
| Escape behavior | Dialog closed |
| Focus restoration | Returned to Open menu trigger |
| Browser console | 0 warnings, 0 errors |

## Automated coverage

- `tests/Feature/UiUxExperienceTest.php`
- `tests/Feature/RemarkableUiExperienceTest.php`
- Existing public form, security-header, localization, route-protection, and workflow feature tests

## Tool limitation

An axe-core CLI run was attempted with axe-core 4.12.1. It could not start because the transient ChromeDriver was version 151 while the installed Chrome browser was version 150. This is an environment driver mismatch, not an axe result. No claim of a clean axe scan is made. The semantic DOM checks, keyboard interaction checks, rendered visual review, and application accessibility regression tests completed successfully.

## Follow-up before production

Run axe or an equivalent WCAG scanner in CI with a browser/driver pair pinned to the same version, then complete a manual screen-reader pass with representative English and Amharic flows.
