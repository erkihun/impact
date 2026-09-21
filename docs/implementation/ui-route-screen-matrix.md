# UI route and screen matrix

Date: 2026-07-26

Route inventory: 93 framework and application routes. The matrix lists user-facing screen families; action, download, health, sitemap and API routes are noted only where they affect a visible state.

## Public localized screens

| Route family | View/controller | Delivered UI state | Remaining dependency |
|---|---|---|---|
| `/`, `/{locale}` | home | Executive Editorial hero, services, evidence, industries, experts, insight, final CTA; absent data sections are omitted | credibility/partner strip requires approved data/model |
| `/{locale}/about` | about | editorial narrative and shared shell | approved history, offices, credentials and governance content are not present in schema |
| services index/detail | collection/detail | keyword search, result count, empty state, pagination; challenges, approach, deliverables, benefits and contextual consultation | taxonomy/faceted filters and relationships require backend fields |
| industries index/detail | collection/detail | keyword search, context/challenges and contextual inquiry | related service/evidence joins not exposed by current model |
| experts index/detail | collection/detail | keyword search, title, qualifications, credentials, languages and inquiry CTA | approved portrait/media relationship absent |
| case studies index/detail | collection/detail | evidence-first cards and challenge/approach/outcome narrative | only approved stored outcomes are displayed; no fabricated metric |
| insights index/detail | collection/detail | knowledge collection and readable body | citation/download/author relations are not present |
| events index/detail | collection/event | lifecycle-aware registration, truthful closed state, validation and confirmation wording | agenda, speakers and capacity UI require backend data |
| careers index/detail | collection/vacancy | vacancy state, secure file guidance, application summary/errors and closed state | recruitment-process content and richer job fields absent |
| search | search | query, results, count, pagination and recovery state | content-type facets require search backend support |
| consultation | consultation | Need → Organization → Project → Review; server confirmation remains authoritative fifth state | none for current validated fields |
| request for proposal | RFP | same task sequence, file constraints/security processing, review and authoritative confirmation | client-visible post-submit scan status has no public status route |
| contact | contact | inquiry type, secure form, error summary, duplicate-submit protection | office alternatives require approved office content |
| newsletter | footer + confirmation actions | labeled consent, double opt-in and unsubscribe backend | preference center route not implemented |

## Identity and profile screens

| Route family | Delivered UI state |
|---|---|
| login | branded secure-access page, clear hierarchy, recovery path, duplicate-submit guard |
| invitation acceptance | invitation-only activation with single-use context |
| forgot/reset/confirm password | single-purpose branded pages with consistent controls |
| email verification | branded action and resend/logout paths |
| MFA enrollment/challenge/recovery | explicit enrollment, secret/recovery guidance and focused verification |
| dashboard/profile | protected workspace orientation and profile/security tasks |
| registration template | styled for completeness but no open registration route is exposed |

## Administration screens

| Route family | Delivered UI state | Authorization source |
|---|---|---|
| `/admin` | real permission-scoped attention counts and quick actions | controller permission checks |
| content index/create/show/edit | compact shell, state/type filters, immutable version/workflow actions and preview | `content.*` permissions |
| engagement index/show | queue filters, secure attachment state, history and allowed transitions | `engagement.*` permissions |
| applications index/show | filter, restricted files, retention and allowed transitions | `applications.*` permissions |
| media | upload queue with truthful scan/processing/approval/download states | `media.*` permissions |
| users | invitation, filters, roles and account state | `users.*` permissions |
| roles | grouped permissions and delegated management | `roles.*` permissions |
| audit events | read-only event table | `audit.view` |
| settings | typed configuration form | `settings.*` permissions |

The navigation is a view of backend authorization, not an authorization control by itself. Direct route middleware/policies remain authoritative.

## Error and system states

| Status | View | Recovery behavior |
|---|---|---|
| 403 | `errors/403` | permission explanation, home/contact |
| 404 | `errors/404` | unpublished/outdated address guidance |
| 409 | `errors/409` | current-state conflict without technical leakage |
| 419 | `errors/419` | expired secure session and re-entry guidance |
| 429 | `errors/429` | wait/retry guidance |
| 500 | `errors/500` | safe retry/contact text; no stack details |
| 503 | `errors/503` | maintenance/recovery message |

## Not implemented because no approved route exists

Dedicated privacy, terms, cookies, accessibility statement, office/location, partner/credential, report library, notification center, public account area and client RFP-status screens remain route/schema/content dependencies. They are not represented by dead navigation or invented data.
