# Public content test matrix

| Risk | Automated evidence |
|---|---|
| Registry/type drift | every enum type has a definition and existing renderer |
| Arbitrary fields/variants | validation rejects unsupported payloads |
| Immutable edits | update creates a second section version |
| Lost associations | mutator clones relations, media, and actions unless explicitly replaced |
| Concurrent edits | stale lock returns HTTP 409 with stable code |
| RBAC/direct URL | unprivileged user receives 403 |
| Published mutation | mutator accepts only draft/changes-requested |
| Draft lineage | published composition clones to v2 with `based_on_id` |
| Bilingual coverage | 52 published EN/AM compositions |
| Route/layout rendering | EN and AM home responses include managed section output |
| Admin workspace | section library, outline, and controls render |
| Media safety | strict verifier checks public/clean/ready and alt text |
| Action safety | strict verifier checks route existence and HTTPS |
| Navigation | strict verifier requires primary and three footer locations per locale |
| Accessibility structure | strict verifier requires exactly one H1-producing section |
| Existing workflows | public submissions, SEO, route protection, and admin UI regression suites |

Required commands:

```powershell
vendor/bin/pint --test
php artisan view:cache
php artisan public-content:verify --strict
php artisan test
npm run build
```
