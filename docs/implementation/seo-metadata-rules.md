# SEO metadata rules

All rules are implemented in `App\Services\Seo\SeoMetadataBuilder` and rendered by `SeoHeadRenderer`. Templates never assemble metadata.

## Fallback order

For every field: **editor override** (`/admin/seo/pages/...`) → **content** (title, summary, excerpt, image) → **approved global default** (Settings Center). Empty values are never emitted as tags.

## Titles

| Page | Rule | Example |
| --- | --- | --- |
| Homepage | `seo.home_title`, verbatim | Impact Consulting \| Strategic Advisory & Professional Consulting |
| Service, industry | `{Name} consulting` (capital C when the name is Title Case; unchanged if it already says "consult") | Digital transformation consulting \| Impact Consulting |
| Expert | `{Name} \| {Professional title}` when that fits in 48 characters, otherwise `{Name}` | Selam Tadesse \| Impact Consulting |
| Case study, insight, event | Content title | Strategy that survives contact with reality \| Impact Consulting |
| Vacancy | `{Position} – Careers` | Senior Consultant – Careers \| Impact Consulting |
| Listings | Listing title, plus ` – Page N` for N > 1 | Consulting services – Page 2 \| Impact Consulting |
| Static pages | Page title | Privacy notice \| Impact Consulting |

The suffix (`seo.default_title_suffix`) is added **once**: not when the title already ends with it (case-sensitive, so "About Impact Consulting" stays as is while "Social impact consulting" still gets it), and not when adding it would push a title of 65 characters or less over 65. An editor's SEO title replaces the base title and still receives the suffix under the same rules.

Audit thresholds: missing title = blocking; under 15 or over 65 characters = warning; duplicate across indexable pages = warning.

## Meta descriptions

Order: SEO description → summary → excerpt → other content fields (problem statement, overview, biography, outcomes, description) → `seo.default_description`. The homepage uses `seo.home_description`. HTML is stripped, whitespace collapsed, and text over 160 characters is cut at a word boundary with "…". Paginated listings prefix "Page N.".

Audit: a page that falls back to the site-wide description, under 50 characters, or duplicated across pages = warning. Keyword density is not measured and `<meta name="keywords">` is never emitted.

## Canonical URLs

`CanonicalUrlBuilder` produces every absolute URL:

- Scheme and host from `SEO_CANONICAL_URL` (else `APP_URL`); HTTPS is forced in production.
- Lowercase path, collapsed slashes, no trailing slash except `/`.
- Query parameters dropped except those a page declares meaningful (only `page` on listings, and only when > 1). Tracking parameters (`utm_*`, `gclid`, `fbclid`, `msclkid`) never affect canonical or robots; other query keys make a listing or form page `noindex, follow`.
- Detail pages canonicalize to their resource URL even if requested with parameters.
- Overrides (`canonical_path`) must be a path on this site, the canonical host, or a host listed in `SEO_ALLOWED_CANONICAL_HOSTS` (HTTPS only). A page with a canonical override is left out of the sitemap.
- No language alternates are emitted.

## Robots

| Context | Directive |
| --- | --- |
| Published public page | `index, follow` |
| Editor override | may set `noindex, follow` or `noindex, nofollow` (never the reverse) |
| Internal search | `noindex, follow` (setting `seo.search_results_noindex`) |
| Listing or form page with filter/context query | `noindex, follow` |
| Status page | `noindex, follow` |
| Draft, preview, admin, sign-in, errors | `noindex, nofollow` (shared default head; plus `X-Robots-Tag`) |
| Any non-production environment | `noindex, nofollow` everywhere + `X-Robots-Tag` + robots.txt `Disallow: /` |

Production indexing requires `APP_ENV=production` and `SEO_INDEXING_ENABLED=true`. robots.txt is not used for confidentiality; authorization still protects private data.

## Social metadata

`og:site_name`, `og:locale` (`seo.og_locale`, `en_US` by default), `og:type` (`website`, `article` for insights and case studies, `profile` for experts), `og:title`, `og:description`, `og:url` (= canonical), `og:image` (+ `:alt`, `:width`, `:height` when known), `article:published_time`/`modified_time` for articles, and `twitter:card`/`title`/`description`/`image`/`image:alt` plus `twitter:site` when `seo.twitter_site_handle` is set.

Image order: editor social image (approved public media only) → page image (expert photo, insight image) → `branding.social_image_url` → the generated 1200×630 social card of the design image. Private or unapproved media are never used.

## Verification

`google-site-verification` and `msvalidate.01` are emitted on the homepage only, and only when the tokens are set in Settings → SEO. DNS verification is preferred.

## Editorial intent fields

Primary topic, secondary topics, target audience, search intent and geographic relevance are stored on `seo_metadata` for editors and reporting. They are not published as keywords. Geographic relevance feeds `areaServed` on Service markup only when an editor selects it; "Ethiopia" is never appended automatically.
