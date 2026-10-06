<!-- markdownlint-disable MD013 -->
# SEO route matrix (current)

Canonical host: `SEO_CANONICAL_URL` (fallback `APP_URL`), HTTPS in production. "Robots" is the production directive; outside production every response is `noindex, nofollow` with a matching `X-Robots-Tag` header, and robots.txt disallows everything.

## Public pages

| Route | Indexable | Canonical | Title | Description | Structured data | Sitemap | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `/` | Yes | `/` | `seo.home_title` (verbatim) | `seo.home_description` | Organization, WebSite, WebPage | pages | Verification tags; hero preload |
| `/about` | Yes | `/about` | About Impact Consulting | Page summary | AboutPage, BreadcrumbList | pages | |
| `/services` | Yes | `/services` (`?page=N` for N > 1) | Consulting services \| Impact Consulting (– Page N) | Static listing description | CollectionPage, BreadcrumbList, ItemList | pages (page 1) | Unknown query keys → `noindex, follow` |
| `/services/{slug}` | Yes | itself | `{Name} consulting \| Impact Consulting` | SEO override → summary → problem → approach → default | WebPage, BreadcrumbList, Service | services | Older slugs 301; archived 410 |
| `/industries`, `/industries/{slug}` | Yes | itself | `{Name} consulting \| …` | summary → overview → challenges | CollectionPage / WebPage + BreadcrumbList | pages / industries | Related services, experts, case studies |
| `/experts`, `/experts/{slug}` | Yes | itself | `{Name} \| {Title}` when ≤ 48 chars, else name; suffix added once | biography → title | ProfilePage, Person (+knowsAbout) | pages / experts | Photo as WebP srcset |
| `/case-studies`, `/case-studies/{slug}` | Yes | itself | Case title (suffix dropped if it would exceed 65) | outcomes → challenge → approach | Article (articleSection Case study) | pages / case-studies | Confidential cases never public |
| `/insights`, `/insights/{slug}` | Yes | itself | Title | excerpt → body | Article, NewsArticle (type news) or Report (type report/publication) | pages / insights | Expired insights 410 |
| `/events`, `/events/{slug}` | Yes | itself | Event title | description | Event (no private meeting link) | pages / events | Cancelled 410; completed stay public |
| `/careers`, `/careers/{slug}` | Yes | itself | `{Position} – Careers \| …` | description → requirements | JobPosting while applications are open | pages / careers (open roles only) | Status closed/archived → 410; past closing date → page stays, JobPosting removed, left out of the sitemap |
| `/consultation`, `/request-for-proposal`, `/contact` | Yes | path only | Page title | Page description | ContactPage | pages (if the form is enabled) | Any query (e.g. `?service_id=`) → `noindex, follow` |
| `/privacy`, `/terms`, `/cookies`, `/accessibility` | Yes | itself | Page title | Page description | WebPage, BreadcrumbList | pages | |
| `/search` | No (`noindex, follow`) | `/search` | Search \| Impact Consulting | Static | SearchResultsPage | Never | Setting `seo.search_results_noindex` |
| `/status` | No (`noindex, follow`) | `/status` | Maintenance title | Maintenance message | — | Never | |

## Infrastructure and legacy

| Route | Behavior |
| --- | --- |
| `/sitemap.xml` | Sitemap index of non-empty child sitemaps; `X-Robots-Tag: noindex`; 404 if disabled |
| `/sitemaps/{pages,services,industries,experts,case-studies,insights,events,careers}.xml` | URL sets; 404 for unknown or empty segments (including `/sitemaps/en.xml`, `/sitemaps/am.xml`) |
| `/robots.txt` | Production: allow, disallow private paths, `Sitemap:` line. Elsewhere: `Disallow: /` |
| `/en`, `/en/{path}` | Single-hop 301 to the unprefixed URL (or straight to a managed redirect's final target); 404 when no such page exists |
| `/am`, `/am/{path}` | Stored legacy decision, else 301 to the equivalent English page when one exists, else 410 Gone |
| Trailing slash (`/services/`) | 301 to the slashless URL (query preserved) |
| Upper case (`/Services/X`) | 301 to the lowercase URL when that URL serves a page |
| Any other unmatched path | Managed redirect (301/302/307/308) or 410; otherwise 404 |

## Private (never indexed)

| Route | Signals |
| --- | --- |
| `/admin`, `/admin/*` (incl. signed content and composition previews) | `X-Robots-Tag: noindex, nofollow, noarchive`, meta robots `noindex`, authentication + permissions |
| `/login`, `/mfa`, `/forgot-password`, `/reset-password/*`, `/invitations/*`, `/verify-email`, `/confirm-password`, `/dashboard`, `/profile` | `X-Robots-Tag: noindex, nofollow`, meta robots `noindex, nofollow`, robots.txt disallow (crawl budget only) |
| `/restricted-media/*`, `/application-files/*`, `/submission-files/*` | Signed, authenticated, `X-Robots-Tag` |
| `/newsletter/confirm/*`, `/newsletter/unsubscribe/*` | Signed, `X-Robots-Tag`, robots.txt disallow |
| All POST endpoints | `X-Robots-Tag: noindex, nofollow` |
