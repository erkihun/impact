# SEO current-state audit (before the English-only SEO implementation)

Audit date: 2026-10-06. Scope: every public route, the Inertia/Blade head pipeline, sitemaps, robots, redirects, search indexing, images, structured data, admin tooling and leftover multilingual code. This records the state **before** this change; the "Required fix" column is what was then implemented (see `seo-route-matrix.md` for the resulting state).

## Summary of findings

| Area | Finding | Severity |
| --- | --- | --- |
| URL structure | Every public page lived under `/{locale}` with only `en` allowed, while `/` also served the homepage. `/` and `/en` were two indexable copies of the homepage. | Blocking |
| Retired Amharic URLs | `/am/{path}` blindly redirected to `/en/{path}`. Amharic slugs (`/am/services/strategy-transformation-am`) therefore redirected to English URLs that 404, a redirect into an error. | Blocking |
| hreflang | `<link rel="alternate" hreflang="en">` and `hreflang="x-default"` were emitted on every page (Blade fallback and `PublicLayout.jsx`) for a single-language site. | Warning |
| Head pipeline | Title, description, canonical and robots were assembled twice: in `inertia.blade.php` (non-SSR) and in `PublicLayout.jsx` `<Head>` (SSR/client). The two used different inputs. | Warning |
| Titles | Built as `"{meta.title} - {suffix}"`. Several controllers already put the brand in `meta.title`, giving `Search — Impact Consulting - Impact Consulting`, `Contact — Impact Consulting - Impact Consulting`, `Request a consultation — Impact Consulting - …`, and a homepage title `Impact Consulting — Ideas into measurable change - Impact Consulting`. | Warning |
| Descriptions | `null` for home, about, consultation, contact and search, so they all shared the site default (duplicates). Detail pages used a truncated summary without HTML stripping. | Warning |
| Canonical | `url()->current()`: request host (not a configured host), no HTTPS enforcement, and query-state duplicates (`?page=1`, filters) were never consolidated. Trailing-slash URLs (`/en/services/`) also answered 200. | Blocking |
| Robots | One site-wide robots value from `seo.robots_indexing` (bound to `APP_ENV`). Search had `noindex,follow`; drafts/admin relied on authorization only; no `X-Robots-Tag` on non-HTML responses. | Warning |
| robots.txt | A static `public/robots.txt` (`User-agent: * / Disallow:`) shadows the dynamic route on any web server that serves static files first, so production would never receive the environment-aware policy. | Blocking |
| Sitemap | `/sitemap.xml` indexed `/sitemaps/en.xml` (locale segmentation). The XML was malformed (`<loc}>`). It listed version rows (duplicates when several versions were published, and archived services because parent status was ignored), omitted experts, events and careers, had no `lastmod`, and did not exclude redirect sources. | Blocking |
| Structured data | None. No Organization, WebSite, BreadcrumbList or content schema. | Warning |
| Redirects | `RedirectController` followed chains at request time (up to 5 hops) and returned 508 for loops. No validation on write, no admin UI, no slug-change redirects, external destinations rejected only at runtime. | Warning |
| Slug history | Domain resources are versioned with a unique slug per version. Every published version stayed reachable, so an old slug and the new slug both answered 200 (duplicate content). Published slug edits created no redirect. | Blocking |
| Draft exposure | `ExpertVersion::scopePubliclyVisible()` did not check `workflow_state`; a draft revision of a published expert would be served publicly. | Blocking |
| Internal search | `search_documents.url` held `/en/...` paths; generic CMS items were indexed at `/en/{slug}`, a route that does not exist (search results linking to 404). Amharic documents remained in the index. | Blocking |
| Images | The homepage hero and the background of every inner page header loaded `public/images/ethiopia-highlands.jpg`: 5596×3607, 6.4 MB. Expert photos and composition media were served as originals without `srcset` or dimensions. | Blocking (LCP) |
| Headings | `PageIntro` hid the built-in page header whenever a composition existed, even with no heading section, leaving such pages without an `<h1>`. | Warning |
| Internal linking | Detail pages linked only to their own listing. Existing `service_industry` and `expert_service` relations were not rendered. | Warning |
| Admin | No SEO centre or page SEO editor. `seo.manage` permission existed but was unused. `seo_metadata` table was locale-keyed and unused. | Warning |
| Settings | `seo.sitemap_refresh_frequency` and `content.seo_checks_required` were read-only "pending consumer". No canonical host, social profiles, verification tokens or organization schema settings. | Warning |
| Seed data | Event and vacancy slugs carried a `-en` suffix (`strategy-to-delivery-en`); Amharic ternaries remained in the seeder. | Information |

## Obsolete multilingual SEO code found

| Location | Item | Disposition |
| --- | --- | --- |
| `routes/web.php` | `Route::prefix('{locale}')->where(['locale' => 'en'])` group, `localized-home` route, `/am/{path}` closure, `/sitemaps/{locale}.xml` | Removed; replaced by unprefixed routes, `LegacyLocaleController`, `/sitemaps/{segment}.xml` |
| ~220 `route(..., ['locale' => ...])` calls in controllers, middleware, Blade views and the navigation service | locale route parameter | Removed (route parameter only; data columns untouched) |
| `resources/views/inertia.blade.php` | `hreflang` current + `x-default`, dynamic `<html lang>` | Removed; `<html lang="en">` |
| `resources/js/Layouts/PublicLayout.jsx` | `<Head>` with hreflang, x-default, `document.documentElement.lang = site.locale.current` | Removed |
| `HandleInertiaRequests::site()` | `site.locale` (current/alternate/alternateUrl/alternateLabel), `site.seo.defaultLocaleUrl` | Removed |
| `SitemapController::locale()` | per-locale sitemap reading `localization.enabled_locales` | Removed |
| `SearchIndexReconciler`, `SyncContentSearchDocumentJob` | `/{locale}/...` URL construction, indexing of every locale | Replaced with `PublicUrlGenerator`, English-only |
| `seo_metadata` (`seoable` + `locale` unique key) | Locale-scoped SEO table, never read | Replaced by subject-keyed table |
| `page_navigation_configurations` rows | `route_name = localized-home`, `route_parameters.locale` | Migrated; runtime also tolerates old rows |
| `search_documents` rows | `locale = am`; `url` starting `/en/` | Deleted / rewritten by migration |
| Public content seeder | `-en` slugs, Amharic titles | Removed |

Not removed (still valid infrastructure): `locale` columns on content, navigation, composition, media, user and newsletter tables (all `en`); `SetLocale` middleware (forces `en`); `localization.*` settings; the `locales` table. Removing these would be a schema change with no SEO benefit.

## Public route audit (before)

| Route | Indexable? | Canonical | Title source | Description source | Structured data | In sitemap | Robots | Redirect behavior | Defect | Required fix |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `/` | Yes | request URL | `meta.title` + suffix (brand twice) | site default | none | No | site value | — | Duplicate of `/en`; double brand | Single homepage at `/`, configured title |
| `/en` | Yes | request URL | same | site default | none | Yes | site value | — | Duplicate of `/` | 301 → `/` |
| `/en/about` | Yes | request URL | "About Impact Consulting - Impact Consulting" | site default | none | Yes | site value | — | `/en` prefix, suffix repeated, default description | `/about`, own description, AboutPage |
| `/en/services` | Yes | request URL | collection title | static text | none | Yes | site value | — | `?page=1` duplicate; no ItemList | `/services`, CollectionPage + ItemList, pagination rules |
| `/en/services/{slug}` | Yes | request URL | name | summary (160) | none | Yes (dupes, archived) | site value | old version slugs 200 | Duplicate slugs, no Service schema | `/services/{slug}`, moved slugs 301, Service schema |
| `/en/industries`, `/{slug}` | Yes | request URL | title/name | static / summary | none | Yes | site value | — | as services | `/industries…`, related services |
| `/en/experts`, `/{slug}` | Yes | request URL | title/name | static / title | none | No | site value | — | Draft revision exposure; not in sitemap; 4 per page | Workflow check, Person/ProfilePage, sitemap |
| `/en/case-studies`, `/{slug}` | Yes | request URL | title | static / null | none | Yes | site value | — | No description on detail | Outcome description, Article |
| `/en/insights`, `/{slug}` | Yes | request URL | title | static / excerpt | none | Yes | site value | — | Expired insights resolved | Article/NewsArticle/Report, expiry |
| `/en/events`, `/{slug}` | Yes | request URL | title | none / description | none | No | site value | — | Not in sitemap; no Event schema | Event schema, sitemap |
| `/en/careers`, `/{slug}` | Yes | request URL | title | none / description | none | No | site value | — | Closed vacancies indexable | JobPosting while open, sitemap |
| `/en/search` | No | request URL | "Search — Impact Consulting" + suffix | default | none | No | `noindex,follow` | — | Double brand | Keep noindex, query-free canonical |
| `/en/consultation`, `/en/request-for-proposal`, `/en/contact` | Yes | request URL | brand twice | default / one static | none | No | site value | — | Context query (`?service_id=`) indexable | Query → noindex, ContactPage |
| `/en/privacy`, `/terms`, `/cookies`, `/accessibility` | Yes | request URL | page title | page description | none | No | site value | — | Not in sitemap | Sitemap, WebPage |
| `/status` | Yes | request URL | maintenance title | default | none | No | site value | — | Operational page indexable | noindex, follow |
| `/am/{path}` | — | — | — | — | — | No | — | 301 → `/en/{path}` | Redirects into 404 | Per-URL 301 or 410 |
| `/sitemap.xml`, `/sitemaps/en.xml` | — | — | — | — | — | — | — | — | Malformed XML, locale split | Segmented English sitemaps |
| `/robots.txt` | — | — | — | — | — | — | — | — | Shadowed by static file | Delete static file; environment policy |
| fallback | — | — | — | — | — | — | — | DB redirect, 508 on loop | No normalization | Single-hop resolver, case normalization |
| `/admin/*`, `/login`, `/mfa`, `/dashboard` | No | — | workspace | — | — | No | meta + header (admin) | auth | `/login` header missing | Robots header middleware |
| `/restricted-media/*`, `/application-files/*`, `/submission-files/*` | No | — | — | — | — | No | header (some) | signed + auth | — | X-Robots-Tag everywhere |
