# SEO architecture

The public website is English-only. Every public resource has exactly one indexable URL, and all SEO output is produced on the server by one set of services. Blade and React templates only print what those services resolve.

## Request flow

```
Controller ──► SeoMetadataBuilder ──► SeoMetadata (DTO) ──► SeoHeadRenderer ──► props.seo.head (escaped HTML strings)
   │                │                                                                │
   │                ├─ SeoSettings (Settings Center values, read once per request)   ├─ inertia.blade.php prints them (first load, no SSR)
   │                ├─ CanonicalUrlBuilder (configured host, HTTPS, normalized path)  ├─ SSR prints them through the Inertia head directive
   │                ├─ RobotsDirectiveBuilder (intended vs. effective)               └─ client head manager swaps them on navigation
   │                ├─ StructuredDataBuilder (typed JSON-LD @graph)                      (createInertiaApp({ serverHead }))
   │                └─ SeoMetadata model (editor overrides)
   └─ PublicPage::render() adds visible-FAQ markup from the published composition
```

Each head element carries a stable `data-inertia` key, so the same element is never printed twice (verified for SSR and non-SSR responses). Non-public Inertia responses (admin, sign-in, errors, previews) receive a shared default `seo.head` that is only `noindex, nofollow`.

## Components

| Component | Responsibility |
| --- | --- |
| `App\Enums\Seo\PublicResourceType` | The single map between a content family, its model, route names, sitemap segment, title field and labels. |
| `App\Enums\Seo\RobotsDirective` | `index, follow`, `noindex, follow`, `noindex, nofollow`; parses stored values. |
| `App\Enums\Seo\SeoIssueSeverity`, `RedirectOrigin` | Blocking / Warning / Information; manual, slug change, legacy language URL, deleted content. |
| `App\Data\Seo\SeoMetadata`, `SeoImage`, `SeoIssue`, `SeoValidationResult`, `SitemapEntry`, `RedirectResolution` | Typed values passed between services. `SeoValidationResult` reports "SEO ready / warnings / blocking issues", never a score. |
| `App\Services\Seo\SeoSettings` | Approved resolver for every SEO setting (scoped binding: one database read per key per request or job). |
| `CanonicalUrlBuilder` | All absolute URLs: configured scheme+host (`SEO_CANONICAL_URL`, falling back to `APP_URL`, HTTPS forced in production), lowercase path, no trailing slash, only declared query keys (pagination), safe overrides only. |
| `PublicUrlGenerator` | Public path and canonical URL of a resource; used by pages, search, sitemaps, internal links and structured data. |
| `SeoMetadataBuilder` | Title, description, canonical, robots, social image and structured data for one response, applying override → content → default fallbacks. |
| `RobotsDirectiveBuilder` | Intended directive (what production would send) and effective directive (always `noindex, nofollow` outside production). Overrides can only make a page stricter. |
| `StructuredDataBuilder`, `StructuredDataValidator` | Typed schema.org nodes from settings and visible content; validation of required properties and forbidden claims. |
| `SeoHeadRenderer` | Escaped `<head>` elements; no empty tags. |
| `MediaImagePresenter`, `ResponsiveImages`, `StaticImageOptimizer` | Responsive WebP derivatives for approved media; AVIF/WebP derivatives and a social card for design images (`php artisan seo:images-optimize`). |
| `App\Queries\Seo\PublicResourceQuery` | What is public now: current version per resource, listings without superseded versions, slug resolution (`current` / `moved` / `gone` / `missing`). |
| `App\Support\Inertia\PublicResourcePage` | Detail-route responder: 200, 301 to the current slug, managed redirect, 410 for retired content, or 404. |
| `RedirectResolver`, `App\Actions\Seo\SaveRedirectAction`, `RedirectAuditor` | Single-hop resolution; governed writes (local targets, no loops, chain flattening, no redirects over live pages); integrity checks. |
| `LegacyLocaleController`, `RedirectController` | Retired `/en/...` and `/am/...` URLs; fallback redirects, 410 and case normalization. |
| `NormalizeTrailingSlash`, `AddRobotsHeader` middleware | One URL form; `X-Robots-Tag` for non-production, private areas and non-GET responses. |
| `SitemapBuilder`, `App\Jobs\Seo\GenerateSitemapsJob` | Segmented English sitemaps, generated under a cache lock and written atomically. |
| `InternalLinkService` | Contextual related links from service/industry and expert/service assignments and curated `content_relations`. |
| `PublicResourceObserver`, `PublicResourceParentObserver`, `PublicationSeoSync` | Publication integration: slug-change redirects, 410 on deletion, then sitemap, search and CDN refresh after commit. |
| `ContentSeoValidator` | Pre-publication checks (title, description, slug, heading, canonical, robots, images, orphan risk). |
| `PageInspector`, `SeoAuditService`, `LinkChecker` | Render URLs through the HTTP kernel as a crawler would (state-isolated when called inside an admin request), audit, and link checks. |
| `App\Contracts\CdnPurger` (`LogCdnPurger`) | CDN purge hook. No CDN is configured; affected URLs are logged until a provider adapter is bound. |

## Data

| Table | Purpose |
| --- | --- |
| `seo_metadata` | Editor overrides keyed by `subject_type` (`page` or a resource type) and `subject_key` (page key or stable resource id): SEO title/description, robots, canonical path, sitemap inclusion, social title/description/image, primary/secondary topics, audience, intent, geographic relevance. |
| `redirects` | Source path, local destination or `NULL` for 410, status, origin, subject, reason, creator, hits, last hit. Disabled rather than deleted. |
| `content_relations` | Curated contextual links between public resources (by stable id). |
| `seo_link_checks` | Broken-link findings with source, link text, status, severity, first detected, last checked and resolution. |
| `seo_audit_runs` | Persisted audit results for the admin SEO centre. |

## Admin

`/admin/seo` (permission `seo.manage`) has Overview, Pages, Metadata, Sitemap, Redirects, Structured data, Broken links, Indexing, Content quality, Social preview, SEO settings and Audit sections. `/admin/seo/pages/{type}/{key}` is the page SEO editor with search-result and social previews, live validation, slug change (with automatic 301), indexing, canonical, social card, editorial intent fields and curated related content. Global values are edited in Settings Center → SEO and Social Sharing.

## Commands and schedule

| Command | Purpose | Schedule |
| --- | --- | --- |
| `seo:audit [--format=table|json] [--strict] [--persist]` | Full audit; `--strict` exits 1 on blocking defects. | Daily 04:30 with `--persist` |
| `seo:sitemap-generate` | Rebuild sitemap files. | Per setting (hourly/daily/weekly), plus after every publication change |
| `seo:sitemap-validate` | XML validity and that every URL is 200, indexable and self-canonical. | CI / release |
| `seo:redirects-validate [--fix]` | Loops, chains, unsafe/dead targets; `--fix` flattens chains. | CI / release |
| `seo:structured-data-validate` | Render and validate JSON-LD on every indexable page. | CI / release |
| `seo:links-check [--external]` | Internal (and bounded external) link check. | Weekly Monday 04:00 |
| `seo:images-optimize` | Regenerate design-image derivatives and the manifest. | When a source image changes |

Queue workers must listen on `search` and `default` (`GenerateSitemapsJob`, `ReconcileSearchIndexJob`). Both are unique while queued, so a burst of edits produces one rebuild.

## Extending to another language later

Nothing locale-specific is active. To add a language: reintroduce a locale segment in `PublicUrlGenerator`/routes, key `seo_metadata` by locale, add per-locale sitemap entries with `xhtml:link` alternates, and emit `hreflang` in `SeoHeadRenderer`. The data model already stores `locale` on content rows.
