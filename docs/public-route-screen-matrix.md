# Public Route and Screen Matrix

All localized routes use the shared public layout and support `en` and `am` where published data exists.

| Surface | Route | Screen/state coverage |
|---|---|---|
| home | `/{locale}` | evidence-led hero, capability counts, services, case study, industries, expert, insight, CTA |
| about | `/{locale}/about` | institutional narrative and commitments |
| services | `/{locale}/services` | collection, query, count, empty, pagination |
| service detail | `/{locale}/services/{slug}` | challenge, approach, deliverables, outcomes, consultation context |
| industries | `/{locale}/industries` | collection, query, count, empty, pagination |
| industry detail | `/{locale}/industries/{slug}` | context, challenges, related action |
| experts | `/{locale}/experts` | collection, query, count, empty, pagination |
| expert detail | `/{locale}/experts/{slug}` | biography, credentials, metadata |
| case studies | `/{locale}/case-studies` | collection, count, empty, pagination |
| case study detail | `/{locale}/case-studies/{slug}` | challenge, approach, outcomes |
| insights | `/{locale}/insights` | knowledge collection, count, empty, pagination |
| insight detail | `/{locale}/insights/{slug}` | article body, metadata, actions |
| events | `/{locale}/events` | collection and no-record state |
| event detail | `/{locale}/events/{slug}` | metadata, venue, open registration, closed/unavailable |
| careers | `/{locale}/careers` | vacancy collection and no-record state |
| vacancy detail | `/{locale}/careers/{slug}` | opportunity, requirements, open application, closed/unavailable, secure CV |
| search | `/{locale}/search` | initial, results, no-results, query summary, pagination |
| consultation | `/{locale}/consultation` | four-step request, errors, review, submission feedback |
| RFP | `/{locale}/request-for-proposal` | four-step brief, secure multiple upload, errors, review |
| contact/partnership/media | `/{locale}/contact` | typed inquiry, validation, feedback |
| newsletter | footer form | consent, duplicate prevention, server feedback |
| privacy | `/{locale}/privacy` | shared legal structure |
| cookie notice | `/{locale}/cookies` | shared legal structure and privacy choices |
| terms | `/{locale}/terms` | shared legal structure |
| accessibility | `/{locale}/accessibility` | shared legal structure |
| system status | `/status` | settings-aware availability and maintenance state |
| not found | fallback | 404 status, search/services/insights recovery |
| machine discovery | `/sitemap.xml`, `/sitemaps/{locale}.xml`, `/robots.txt` | existing controller behavior preserved |

## Backend/content dependencies

- No dedicated report/download route or report-specific backend object exists. Current published knowledge is represented by insights.
- Partnership uses the existing typed contact submission. A separate partnership application would require an approved backend workflow.
- Empty Amharic sections reflect the publication state of Amharic records; the UI does not fabricate content.

