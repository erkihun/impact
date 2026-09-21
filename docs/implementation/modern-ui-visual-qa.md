# Modern UI visual QA

Date: 2026-07-28

## Method

Real rendered pages, not source review. `scripts/ui-shots.mjs` drives the Chromium already cached by
Playwright over CDP, adding no npm dependency. It signs in through the actual login and MFA challenge for
admin routes, captures each route at four widths, and measures `documentElement.scrollWidth` against
`clientWidth` to detect horizontal overflow.

- 30 routes, 4 viewports (390 / 768 / 1280 / 1920), English and Amharic.
- 120 screenshots per sweep; before and after sets retained.
- Evidence: `output/ui-shots/before*/`, `output/ui-shots/final-*/`, each with `findings.json`.

Reproduce:

```
php artisan serve --port=8123
node scripts/ui-shots.mjs http://127.0.0.1:8123 output/ui-shots/final-public
AUTH_EMAIL=… AUTH_PASSWORD=… AUTH_TOTP_CMD="php scratchpad/totp.php" \
  node scripts/ui-shots.mjs http://127.0.0.1:8123 output/ui-shots/final-admin routes.json
```

## Overflow: before and after

| Route | Viewport | Before | After |
|---|---|---|---|
| `/admin/engagement` | 390 px | **106 px overflow** | **0** |
| `/admin/users` | 390 px | **86 px overflow** | **0** |
| All other routes | all | 0 | 0 |

Final sweep: **0 horizontal overflow failures across 120 screenshots.** Every route has exactly one `<h1>`.

## Route results

| Route | Previous problem | New treatment | Mobile | Tablet | Desktop | EN | AM |
|---|---|---|---|---|---|---|---|
| `/admin` | Four KPI tiles then dead space; counts only | Attention queues carrying real records, recent work, permission-aware quick actions | pass | pass | pass | pass | pass |
| `/admin/engagement` | Raw table, 106 px overflow, free-text filters | Record list, select filters, status badges, result count | pass | pass | pass | pass | pass |
| `/admin/users` | 86 px overflow; invite form competing with list | Record list; invitation collapsed into a disclosure | pass | pass | pass | pass | pass |
| `/admin/audit-events` | No filters; raw UUID actors; bare heading | Action/from/to filters, resolved actor names, readable dates | pass | pass | pass | pass | pass |
| `/admin/settings` | 13 ungrouped fields in one card | Seven labelled sections with explanations | pass | pass | pass | pass | pass |
| `/admin/roles` | Permission codes as a comma list | Business-language permission descriptions | pass | pass | pass | pass | pass |
| `/admin/roles/{id}/edit` | Codes led, starter styling | Description leads, code secondary, grouped sections | pass | pass | pass | pass | pass |
| `/admin/users/{id}/edit` | One flat form | Identity / account state / roles sections | pass | pass | pass | pass | pass |
| `/admin/media` | No dimensions, size or alt-text state | Size, dimensions, alt-text status, scan and processing badges | pass | pass | pass | pass | pass |
| `/admin/applications` | Raw table, plain status text | Record list with badges and record actions | pass | pass | pass | pass | pass |
| `/admin/applications/{id}` | Starter styling, PII unmarked | Restricted-data notice, workflow rail, grouped detail | pass | pass | pass | pass | pass |
| `/admin/engagement/{id}` | Starter styling, dense blocks | Structured detail, attachment scan state, timeline | pass | pass | pass | pass | pass |
| `/profile` | Breeze scaffold, "Are you sure?" | Design-system panels; destructive dialog names the account and consequence | pass | pass | pass | pass | pass |
| Public routes (20) | Already conformant | Unchanged except CSP binding fixes | pass | pass | pass | pass | pass |

## Behavioural verification

Checked by driving the real page, because these are the failures a screenshot cannot show:

| Behaviour | Result |
|---|---|
| Services mega menu opens on click | `aria-expanded="true"`, panel `display:block` |
| Consultation stepper advances 1 → 2 | step 1 `none`, step 2 `block` |
| Consent banner renders and `Manage choices` opens the preference dialog | pass |
| Browser console during public flow | 0 errors, 0 warnings |

These four were the CSP-binding regressions. Before the fix the mega menu did not open at all.

## Not verified

Honest limits of this pass:

- **Imagery.** The database holds 0 media assets, so image crops, `srcset` selection and portrait ratios could
  not be exercised. The specification forbids unapproved stock imagery in production, so none was invented.
- **Content volume.** 3 services, 3 industries, 1 expert, 1 case study, 1 insight. Pagination, dense filter
  results and long-list behaviour are structurally implemented but not observed under realistic volume.
- **Assistive technology.** Automated checks plus keyboard and structural review only. The specification is
  explicit that this is not sufficient evidence of WCAG conformance; an independent screen-reader assessment
  remains an open acceptance dependency.
- **Browser matrix.** Chromium only. Firefox, WebKit, physical devices, forced-colors mode and 400 % zoom
  are untested here.
- **Amharic quality.** Rendering, glyph shaping and reflow verified. Translation *wording* for the 137 new
  admin strings is a working translation pending professional native review.
