# Structured data

JSON-LD is built by `App\Services\Seo\StructuredDataBuilder` from approved settings and published, visible fields, and emitted as one `@graph` per page. Editors cannot enter JSON-LD or scripts. `StructuredDataValidator` (used by `seo:structured-data-validate` and `seo:audit`) checks required properties and rejects forbidden claims.

## Graph per page type

| Page | Nodes |
| --- | --- |
| Every public page | `Organization` (`#organization`), `WebSite` (`#website`), a page node (`#webpage`) linked by `isPartOf`/`publisher` |
| Pages below the homepage | `BreadcrumbList` (`#breadcrumb`), matching the visible breadcrumb; every item uses the canonical host |
| Listings | `CollectionPage` + `ItemList` of the items on the current page |
| Service | `Service` (`provider` = organization; `areaServed` only from editor-selected geographic relevance) |
| Expert | `ProfilePage` with `mainEntity` → `Person` (`jobTitle`, `worksFor`, `knowsAbout` from assigned services, `knowsLanguage`, `image` only for approved public photos) |
| Insight | `Article`; `NewsArticle` when the insight type is `news`; `Report` for `report`/`publication`. Author and publisher = the organization (content has no named byline). |
| Case study | `Article` with `articleSection: Case study` |
| Event | `Event` with local-time `startDate`/`endDate`, attendance mode from the format, `Place` for physical venues and `VirtualLocation` = the public event page for online/hybrid (the private meeting link is never exposed). Cancelled events are 410 and carry no markup. |
| Vacancy | `JobPosting` only while applications are open (`acceptsApplications()`), with `validThrough`, `employmentType`, `identifier`, `hiringOrganization`, `jobLocation` |
| About / contact / form pages | `AboutPage` / `ContactPage` |
| Search | `SearchResultsPage` (page is noindex) |
| Any page with a published FAQ section | `FAQPage` built from the visible question/answer items only |

## Organization data sources

All from Settings Center via `SeoSettings::organization()`: `site.name`, `site.legal_name`, `site.abbreviation`, `site.description`, `site.email`, `site.phone`, `site.postal_address` (else `site.address`), `site.default_country`, `branding.logo_url`, `seo.organization_type`, and `sameAs` from `seo.social_linkedin_url`, `seo.social_x_url`, `seo.social_facebook_url`, `seo.social_youtube_url`. Nothing is hard-coded.

## Never emitted

`aggregateRating`, `review`, `award`, testimonials as reviews, fabricated authors, `SearchAction` (Google retired the sitelinks search box), URLs under `/en/` or `/am/`, empty properties, or markup for content that is not visible on the page. The homepage testimonial quotes are presentational and are deliberately not marked up.

## Validation rules

| Check | Severity |
| --- | --- |
| Invalid JSON, wrong `@context`, missing `@type` | Blocking |
| Missing required property for the type (e.g. `Event.startDate`, `JobPosting.validThrough`, `Article.datePublished`) | Blocking |
| `aggregateRating`, `review` or `award` present | Blocking |
| Breadcrumb positions not consecutive or names missing | Blocking |
| `JobPosting` past its closing date | Blocking |
| Retired language URL in `url`/`item` | Blocking |
| Duplicate `@id` | Warning |
| Indexable page without structured data | Warning |

External validation (Google Rich Results Test, Schema.org validator) should be run against production URLs after launch; it is not automated here.
