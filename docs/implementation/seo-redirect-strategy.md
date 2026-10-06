# Redirect strategy

## Principles

- One hop. Visitors and crawlers always receive the final destination; chains are flattened when written and can be flattened again with `seo:redirects-validate --fix`.
- Local destinations only. External, protocol-relative and non-HTTP targets are rejected.
- No loops, no self-redirects, no duplicate sources.
- A redirect never hides a page that is currently published (the page would win anyway).
- Protected paths (`/admin`, sign-in, `/sitemap*`, `/robots.txt`, `/api`, `/storage`, `/build`, health checks) cannot be redirected.
- 410 Gone for content that is deliberately removed with no equivalent. Nothing is sent to the homepage as a catch-all.
- Redirects are disabled, not deleted, so history and hit counts are preserved.

## Kinds of redirect

| Origin | Created by | Status |
| --- | --- | --- |
| Manual | SEO centre → Redirects (`SaveRedirectAction`), reason required, audited | 301, 308, 302, 307 or 410 |
| Published slug change | `PublicResourceObserver` when the slug of the current public version changes (SEO editor, expert editor, or a newly published version with a new slug) | 301 from every previously public path to the current one |
| Deleted published content | `PublicResourceObserver` when the last public version is deleted | 410 |
| Legacy language URL | Migration `2026_10_06_090100` | 301 to the equivalent English page, or 410 |

Draft slug edits never touch the live URL: a draft version's slug does not resolve and creates no redirect until it is published.

Resolution order for a request:

1. A route that serves a page wins (detail routes check the resource state: current → 200; older slug → 301 to the current slug; managed redirect → its destination; archived/cancelled/closed/expired → 410 when `seo.archived_content_status = gone`, otherwise 404).
2. Unmatched paths go to `RedirectController`: managed redirect or 410 record, then case normalization (`/Services` → `/services` when that page exists), else 404.
3. `/en/...` and `/am/...` go to `LegacyLocaleController` (below).
4. Any GET with a trailing slash is 301'd to the slashless form first (`NormalizeTrailingSlash`), keeping the query string.

Query strings are preserved on redirects. Hits and last-hit time are recorded.

## Legacy language URLs

### `/en/...` (former English prefix)

| Old URL | Decision |
| --- | --- |
| `/en` | 301 → `/` |
| `/en/{page}` where `/{page}` serves a page | 301 → `/{page}` (query kept) |
| `/en/{type}/{slug}` | 301 → current URL of that resource (follows slug history in the same hop); 410 if retired; 404 if it never existed |
| `/en/{path}` covered by a managed redirect for `/{path}` | 301 straight to that redirect's final destination |
| anything else | 404 |

### `/am/...` (retired Amharic)

| Old URL | Decision |
| --- | --- |
| Stored decision (`origin = legacy_locale`) | As stored (materialized by the migration from the Amharic rows that existed) |
| `/am` and `/am/{page}` for static pages (about, services listing, contact, legal …) | 301 → the English page (same page, same purpose) |
| `/am/{type}/{slug}` where the slug resolves to a public English resource | 301 → that resource |
| `/am/{type}/{slug}` with no public English equivalent | 410 |
| any other `/am/...` | 410 |

The migration decides per stored Amharic row: services, industries, experts, case studies and insights redirect when the same resource (shared parent id) has a published English version and a published parent; events and vacancies (stored without a shared identity) redirect only when an English record matches every scheduling field exactly (start, end, format, timezone / type, opening, closing, location); otherwise the URL is 410.

### Map produced on the development database (2026-10-06)

| Old URL | Status | New URL |
| --- | --- | --- |
| `/am/services/strategy-transformation-am` | 301 | `/services/strategy-transformation` |
| `/am/services/institutional-strengthening-am` | 301 | `/services/institutional-strengthening` |
| `/am/services/research-learning-am` | 301 | `/services/research-learning` |
| `/am/industries/public-institutions-am` | 301 | `/industries/public-institutions` |
| `/am/industries/social-impact-am` | 301 | `/industries/social-impact` |
| `/am/industries/responsible-business-am` | 301 | `/industries/responsible-business` |
| `/am/events/strategy-to-delivery-am` | 301 | `/events/strategy-to-delivery-en` (that database still had the old English slug) |
| `/am/careers/senior-consultant-am` | 301 | `/careers/senior-consultant-en` (same) |

Production must run the migration against production data; the resulting map is visible in SEO centre → Redirects (origin "Legacy language URL") and should be reviewed there before launch.

## Validation

`seo:redirects-validate` (exit 1 on blocking) and the Redirects section of the SEO centre report loops, chains, unsafe targets, invalid statuses, redirects shadowing live pages, destinations that no longer serve a page, unnormalized sources and temporary redirects that may be stale.
