# Admin UI Visual QA

## Browser Evidence

Command:

```powershell
node scripts/ui-shots.mjs http://127.0.0.1:8123 output/admin-ui-shots tmp/admin-ui-routes.json
```

Result:

- 44 screenshots captured across 11 routes and 4 viewport widths.
- Horizontal overflow failures: 0.
- Desktop routes without exactly one H1: 0.
- Evidence directory: `output/admin-ui-shots`
- Findings file: `output/admin-ui-shots/findings.json`

## Authenticated Admin Limitation

The screenshot helper remained on `/login` after attempting local QA-admin login. Local database inspection showed the `admin@impact.test` password no longer matched the previously documented QA password. I did not reset the local account password for visual QA.

Authenticated admin rendering was therefore covered by feature tests, not live browser screenshots.

Related local account evidence: `Hash::check('Impact-QA-Only-2026!', admin@impact.test)` returned `false` on 2026-07-28.

## Screen Notes

| Route | Previous problem | Updated components | Result |
| --- | --- | --- | --- |
| `/admin` | Broken collapse bindings, custom KPI markup | `x-admin.kpi-card`, `x-admin.quick-action-card`, fixed shell controller | Feature-rendered admin shell passes |
| `/admin/content` | Custom header/filter/list pattern | `x-admin.page-header`, `x-admin.filter-bar`, `x-admin.result-summary`, `x-admin.content-summary-card` | Source and feature test pass |
| `/admin/content/create` | Starter-like form container | `x-admin.page-header`, `admin-panel`, error summary | Blade cache pass |
| `/admin/content/{content}` | Custom detail panels | `x-admin.page-header`, `admin-panel` | Blade cache pass |
| `/admin/content/{content}/edit` | Custom editor panel/header | `x-admin.page-header`, `admin-panel` | Blade cache pass |
| Admin list routes | Mostly centralized already | Existing `x-admin.record-list`, `x-admin.filter-bar` retained | Blade cache pass |
| Auth routes | Shared auth shell already present | Existing auth shell retained | Browser pass confirms no overflow on login |

## Remaining Issue

Rerun authenticated browser screenshots after the local admin password is reset or confirmed.
