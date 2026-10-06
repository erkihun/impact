# SEO test matrix

All tests are Pest feature tests in `tests/Feature/Seo` (96 tests, 691 assertions) unless noted. Run with `php artisan test --filter=Seo` or `vendor/bin/pest tests/Feature/Seo`. Production directives are tested by switching the environment to `production` (`asProduction()` in `tests/Pest.php`) and requesting HTTPS URLs.

| Requirement | Test | File |
| --- | --- | --- |
| Title, description, canonical, robots, Open Graph, Twitter in server HTML; no empty tags; no meta keywords | renders a unique title, description, canonical, robots and social metadata in the server HTML | MetadataTest |
| Homepage title verbatim; suffix once; case-sensitive brand match | uses the configured homepage title verbatim and adds the brand suffix exactly once elsewhere | MetadataTest |
| Override → content → default fallback | applies the fallback order | MetadataTest |
| Long titles keep meaning | keeps long titles readable by dropping the suffix | MetadataTest |
| Verification tags only when configured, homepage only | emits search engine verification tags only when configured | MetadataTest |
| Metadata for client-side visits | keeps metadata for client-side visits in the Inertia payload | MetadataTest |
| `<html lang="en">`, no hreflang / x-default / alternate locale | declares English on every public page without language alternates (7 pages) | SingleLanguageTest |
| No Amharic navigation or locale props | has no Amharic navigation, language switch or locale props | SingleLanguageTest |
| No Amharic or per-language sitemap | serves no Amharic or per-language sitemap | SingleLanguageTest |
| Legacy Amharic URLs: 301 to equivalent, 410 otherwise, never homepage catch-all | answers retired Amharic URLs with a single-hop 301 … otherwise 410 | SingleLanguageTest |
| `/en/...` duplicates 301 in one hop | has exactly one indexable URL per resource | CanonicalUrlTest |
| `/en` + managed redirect resolves to final destination | resolves a retired /en URL straight to the final destination | CanonicalUrlTest |
| Trailing slash and case normalization | normalizes trailing slashes and letter case | CanonicalUrlTest |
| Query duplicates: tracking stripped, filters noindex | strips tracking parameters … noindexes arbitrary filter combinations | CanonicalUrlTest |
| Pagination canonical; 404 beyond last page | gives real paginated listing pages their own canonical | CanonicalUrlTest |
| Older published slug → 301 | permanently redirects an older published version slug | CanonicalUrlTest |
| Draft slug does not change live URL | keeps the live URL when a draft revision proposes a different slug | CanonicalUrlTest |
| Valid sitemap index and child sitemaps | publishes an English-only sitemap index | SitemapTest |
| Published included; draft, archived, noindex, redirected, 410 and closed excluded | includes only published, indexable, non-redirecting resources | SitemapTest |
| Atomic regeneration on publish, removal on archive, 410 | writes sitemap files atomically … drops them when archived | SitemapTest |
| robots.txt non-production / production | serves a robots.txt that blocks everything outside production; production robots.txt | SitemapTest |
| Previous slug → 301 with origin and subject | creates a permanent redirect when a published slug changes | RedirectGovernanceTest |
| Slug history stays single-hop | keeps slug history single-hop after repeated slug changes | RedirectGovernanceTest |
| Deleted content → 410 | records 410 Gone when the last public version is deleted | RedirectGovernanceTest |
| Loop, self, external, protocol-relative, live page, admin rejected | rejects loops, self-redirects, external targets … (6 cases) | RedirectGovernanceTest |
| Chain flattened on write (both orders) | flattens chains …; stores a new redirect to a chained destination as its final hop | RedirectGovernanceTest |
| Detect/flatten chains and loops written outside the action | detects and flattens chains and loops | RedirectGovernanceTest |
| 410 records, query preserved, hit counting | serves 410 for gone records and counts hits | RedirectGovernanceTest |
| Organization / WebSite / WebPage from settings | describes the organization and website from configured settings | StructuredDataTest |
| Service, Person, Article, case-study Article, Event, JobPosting + BreadcrumbList, validator passes | emits typed, valid JSON-LD for each content type (6 cases) | StructuredDataTest |
| ProfilePage; private meeting link never exposed | marks expert pages as profile pages … | StructuredDataTest |
| NewsArticle / Report only for those insight types | uses NewsArticle and Report only for insights of that kind | StructuredDataTest |
| JobPosting removed after closing | removes JobPosting markup once applications close | StructuredDataTest |
| Forbidden ratings/awards, legacy URLs rejected | rejects fabricated ratings, awards and retired-language URLs | StructuredDataTest |
| FAQPage only for visible FAQ | adds FAQPage only for question and answer content that is visibly published | StructuredDataTest |
| Hero: AVIF/WebP srcset, dimensions, eager, preload | serves the hero as responsive AVIF/WebP derivatives | ImageSeoTest |
| Source image private; derivative budgets | keeps the high-resolution source out of the public directory | ImageSeoTest |
| Media: WebP srcset, dimensions, alt, not original | delivers public media as WebP derivatives | ImageSeoTest |
| Alt fallback; private media excluded | falls back to a meaningful alt text and never exposes private media | ImageSeoTest |
| Social image from approved media | uses an approved public image for the social card | ImageSeoTest |
| Published indexable; search noindex, follow | makes published pages indexable and internal search noindex, follow | IndexingTest |
| Drafts 404, unpublished 404, archived 410 | never serves drafts, unpublished or archived resources | IndexingTest |
| Non-production noindex + header | noindexes everything outside production | IndexingTest |
| Admin, sign-in, preview noindex | keeps admin, sign-in, previews and private downloads out of the index | IndexingTest |
| No private content in sitemap or markup | never lists private or confidential content | IndexingTest |
| Publish/move/archive updates search, sitemap, CDN | updates internal search, the sitemap and the CDN | PublicationWorkflowTest |
| SEO validation blocks publication | blocks publishing CMS content that has SEO blocking issues | PublicationWorkflowTest |
| Slug normalization; reserved slugs | normalizes slugs …; rejects reserved slugs | PublicationWorkflowTest |
| Contextual internal links; unpublished never linked | links related published content contextually | PublicationWorkflowTest |
| `seo:audit --strict` passes on a clean site / fails on blocking | passes the strict audit …; fails the strict audit on blocking defects | SeoAuditCommandTest |
| Duplicate/fallback/orphan findings, no score | flags duplicate titles, fallback descriptions and orphan pages | SeoAuditCommandTest |
| Sitemap/structured-data/links/redirect commands | validates the sitemap, structured data and links from the command line | SeoAuditCommandTest |
| SEO centre sections and permission | shows every SEO centre section …; denies … without seo.manage | AdminSeoCenterTest |
| Admin audit keeps the admin session | runs an audit from the admin without disturbing the administrator session | AdminSeoCenterTest |
| Page editor: previews, validation, slug change 301, overrides, relations, areaServed | edits page SEO with previews and validation | AdminSeoCenterTest |
| Unsafe canonical and unpublished relations rejected | refuses unsafe canonical overrides and links to unpublished content | AdminSeoCenterTest |
| Redirect admin: validation, disable-not-delete | manages redirects with validation … | AdminSeoCenterTest |

Updated existing tests: `InertiaPublicPagesTest` (unprefixed routes, `props.seo`), `InertiaPublicShellTest`, `UiUxExperienceTest`, `LocalizationTest` (no locale props, `/en` 301), `SearchPrivacyAndIndexingTest` (no search documents for URL-less CMS items), and URL updates from `/en/...` to `/...` across the suite. The superseded `tests/Feature/Feature/Public/SeoTest.php` (locale sitemap, 508 loops) was removed.
