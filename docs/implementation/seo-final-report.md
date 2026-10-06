# SEO implementation final report

Date: 2026-10-06. Scope: English-only technical, on-page and structured-data SEO for the public website, plus SEO administration, auditing and publication integration.

## Results

| Item | Result |
| --- | --- |
| Public routes audited | 30 indexable URLs on seeded data (16 static/listing pages, 14 detail pages) plus search, status, sitemaps, robots, legacy and private routes — see `seo-current-state-audit.md`, `seo-route-matrix.md` |
| Canonical routes | One unprefixed URL per resource; canonical host from `SEO_CANONICAL_URL`, HTTPS in production |
| Duplicate URLs removed | `/` vs `/en`; every `/en/...` page; trailing-slash and upper-case variants; old version slugs; `?page=1`; filter/context query variants (now `noindex, follow` with clean canonical) |
| `/am` routes | Static pages 301 to English; detail URLs 301 to the English equivalent or 410; unknown 410. 8 decisions materialized from the development data (6 services/industries, 1 event, 1 vacancy, all 301) |
| `/en` routes | Migrated: single-hop 301 to unprefixed URLs |
| hreflang | Removed (`hreflang="en"`, `x-default`); audit blocks if it reappears |
| Locale sitemap logic | Removed; English sitemap index with 8 type segments |
| Titles fixed | All public pages: repeated brand suffix removed (search, contact, consultation, RFP, home), patterned service/industry/expert/vacancy titles, pagination titles. 0 duplicate or out-of-range titles on seeded data |
| Descriptions fixed | Home, about, consultation, RFP, contact, search, events and careers now have their own; detail pages fall back through content fields. 0 fallback or duplicate descriptions on seeded data |
| Canonicals | Implemented by `CanonicalUrlBuilder` on every public page; safe override only |
| Sitemap files | `sitemap.xml` + pages (16), services (3), industries (3), experts (4), case-studies (1), insights (1), events (1), careers (1) = 30 URLs, validated |
| Redirects validated / chains removed | Validation on write and via `seo:redirects-validate`; no chains or loops on seeded data |
| Broken links | 217 links checked (navigation, footer, content, related, CTAs, redirect targets, media): 0 broken |
| Structured data types | Organization, WebSite, WebPage, AboutPage, ContactPage, CollectionPage, ProfilePage, SearchResultsPage, BreadcrumbList, ItemList, Service, Person, Article, NewsArticle, Report, Event, JobPosting, FAQPage. Valid on all 30 pages |
| Missing alt text | Expert photos and composition media fall back to meaningful alt or are marked decorative; media library images without alt are reported |
| Oversized images | 6.4 MB 5596×3607 JPEG hero (homepage LCP and every page header background) replaced by AVIF/WebP derivatives: 32–328 KB AVIF, 45–500 KB WebP, 1200×630 social card 266 KB; source moved out of `public/`. Approved media served as 480/960/1600 WebP with srcset and dimensions instead of originals |
| Orphan pages | All 14 seeded detail pages now have contextual links (service/industry and expert/service assignments plus curated relations); 0 orphans |
| Headings | Pages whose composition had no heading section no longer lose their `<h1>`; audit confirms exactly one `<h1>` on all 30 pages |
| Defects fixed beyond SEO tags | Draft expert revisions were publicly visible; malformed sitemap XML; static `public/robots.txt` shadowing the route; search results linking to non-existent `/en/{slug}` URLs; archived services listed in the sitemap; slug rules allowed uppercase/underscores (now normalized, reserved words rejected) |

## Core Web Vitals

Not measured in a lab or field run (a local Lighthouse run did not complete). Changes that protect them: LCP image reduced from 6.4 MB to ≤ 328 KB (AVIF) with responsive `srcset`, `fetchpriority="high"`, eager loading and a `<link rel="preload">`; page-header background reduced from 6.4 MB to 117–241 KB; intrinsic `width`/`height` on hero, expert and composition images (CLS); below-the-fold images lazy and async-decoded; metadata in server HTML in both SSR and non-SSR modes; no new front-end libraries. TTFB note: `EffectiveSettings` resolves each setting with a database query; `SeoSettings` caches per request, but the rest of the app still pays that cost.

## Commands executed

| Command | Result |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `php artisan migrate:fresh --seed` | OK (also ran the new migrations against the previous local data first: 8 legacy decisions created) |
| `php artisan seo:audit --strict` | Exit 0 — SEO ready: 0 blocking, 0 warnings, 2 information |
| `php artisan seo:sitemap-generate` / `seo:sitemap-validate` | 30 URLs; valid, all 200, indexable, self-canonical |
| `php artisan seo:redirects-validate` | All single-hop, local, loop-free |
| `php artisan seo:structured-data-validate` | Valid on 30 pages |
| `php artisan seo:links-check` | 217 checked, 0 broken |
| `php artisan route:list` | OK |
| `php artisan test` / `vendor/bin/pest` | 295 passed, 2,720 assertions (96 SEO tests) |
| `vendor/bin/phpstan analyse` | No errors (also fixed 6 pre-existing errors in untouched files) |
| `vendor/bin/pint --test` | Passes for every file changed in this work; **fails repo-wide on 46 untouched files** (10 CRLF-only from the Windows checkout, 36 pre-existing style issues) |
| `npm run build` | OK (client + SSR) |
| `config:cache`, `route:cache`, `view:cache`, `event:cache` | All OK; site and audit verified with caches, then cleared for development |

Generated HTML was inspected for the homepage, service, expert, event and vacancy pages in non-SSR and SSR modes: each head element appears exactly once.

## Google Search Console and Bing Webmaster Tools

Dependencies that need production access (no values were fabricated):

1. Verify the domain property by DNS TXT (preferred) or set `seo.google_site_verification` / `seo.bing_site_verification` in Settings → SEO.
2. Submit `https://<canonical-host>/sitemap.xml`; remove any old `/sitemaps/en.xml` submission.
3. URL inspection: homepage, one page per content type, and sample `/en/...` (expect 301) and `/am/...` (expect 301/410) URLs.
4. Monitor Pages/Coverage for "Duplicate without user-selected canonical", "Alternate page with proper canonical", soft 404s and crawl errors; monitor the Core Web Vitals report (field data needs traffic); check Security issues and Manual actions; watch redirect hit counts and 410s in SEO centre.
5. Run Google's Rich Results Test on Service, Event, JobPosting and Article pages.

## Remaining defects and risks

- Core Web Vitals are not measured; run Lighthouse/PageSpeed against staging and watch field data after launch.
- Repo-wide Pint fails on 46 files not touched here; a separate formatting commit would fix it.
- Heading checks cover the `<h1>` only; deeper H2/H3 order is not machine-audited.
- CDN purge is log-only until a CDN adapter is bound to `CdnPurger`.
- The hero image's licence is unconfirmed (DD-015).
- Services, industries, case studies, insights, events and vacancies have no admin content editors other than the SEO editor (slug, metadata, relations); expert profiles do.
- Organization address is published as free text (`streetAddress`); structured locality/postcode fields would improve it.
- Homepage testimonial quotes are placeholder copy (not marked up; content decision).
- Legacy event/vacancy equivalence relies on exact schedule matches; review the map on production data.
- Sitemap freshness after edits depends on a running queue worker (`search`, `default`); files are generated live only when missing.

## Evidence-based readiness

**90%.** All functional completion criteria are met and verified by tests and the strict audit (English-only output, one canonical URL, legacy handling, unique metadata, valid sitemaps, robots, exclusions, structured data, slug redirects, loop/chain prevention, image optimization, internal links, headings, admin and settings integration, publication sync, tests, static analysis, build). Not 100% because Core Web Vitals and external rich-result validation are unverified, repo-wide Pint still fails on untouched files, and Search Console/Bing setup needs production access.
