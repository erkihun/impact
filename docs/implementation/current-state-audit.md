# Current state audit

Date: 2026-07-26

Authority order: SRS, SDD, LLD, then current source. The audit evaluates working behavior and evidence, not filename presence.

## Repository baseline

- Laravel 12.64 with PHP 8.4 declared, Blade, Tailwind, CSP-compatible Alpine, Scout, Predis, Pest, Pint and Larastan.
- 20 application/framework migrations run cleanly on MySQL.
- 90 non-vendor routes are registered.
- 101 tests and 472 assertions pass through both `php artisan test` and Pest.
- 331/331 first-party PHP files declare strict types; the source scan found no empty model guard lists, `env()` outside configuration, debug calls, TODO/FIXME markers, route action closures or literal passwords.
- Local PHP is 8.2.12, so a normal `composer install` correctly refuses the PHP 8.4 application. Target-runtime certification remains external.

## Module classification

| # | Module | Classification | Verified evidence | Defect or remaining work |
|---:|---|---|---|---|
| 1 | Public home page | Partially implemented | English/Amharic rendered browser path | Sections and evidence are seeded/source-defined rather than fully CMS-configurable |
| 2 | Navigation and footer | Partially implemented | responsive desktop/mobile navigation | menu hierarchy, legal links and ordering are not administratively managed |
| 3 | Corporate pages | Partially implemented | localized About page | managed history, mission, governance and policy pages incomplete |
| 4 | Leadership | Missing | none | structured profiles and administration required |
| 5 | Credentials and certifications | Missing | none | expiry rules and publication override required |
| 6 | Partners | Missing | none | profiles, logos, authorization and links required |
| 7 | Offices and locations | Partially implemented | `offices` schema/model | public/admin pages and map fallback absent |
| 8 | Services | Partially implemented | public list/detail and localized versions | admin CRUD, relationships, archive/restore and full workflow absent |
| 9 | Industries | Partially implemented | public list/detail and localized versions | admin CRUD and relationship governance absent |
| 10 | Experts | Partially implemented | public list/detail and authorization metadata | documented verification workflow and admin CRUD absent |
| 11 | Case studies | Partially implemented | public list/detail and consent metadata | legal/client review workflow and administration absent |
| 12 | Testimonials | Missing | none | consent, attribution and administration required |
| 13 | Insights and publications | Partially implemented | public list/detail | admin lifecycle, types, authors and revision controls incomplete |
| 14 | Reports and downloads | Missing | media foundation only | managed reports and download evidence required |
| 15 | News and press releases | Partially implemented | insight type can represent records | dedicated administration and filtering absent |
| 16 | Events and webinars | Partially implemented | public list/detail and capacity enforcement | administration, reminders and lifecycle scheduling absent |
| 17 | Event registrations | Implemented but defective | atomic registration, consent, acknowledgement tests | admin list/export and audited private access absent |
| 18 | Careers | Partially implemented | public vacancy list/detail and closure rules | vacancy administration and scheduling absent |
| 19 | Consultant opportunities | Partially implemented | vacancy type supports consultant records | dedicated filtering/content administration absent |
| 20 | Applications | Partially implemented | secure intake, HR list/detail, 409 state conflicts, history, audited signed download and retention anonymization/file revocation | approved export remains |
| 21 | Consultation requests | Complete and verified | validation, consent, reference, audit, acknowledgement | target mail/queue evidence external |
| 22 | RFP submissions | Partially implemented | dedicated bilingual multipart form, typed intake, bounded MIME/size/count validation, UUIDv7 quarantine keys, initial history, reviewer policy and audited signed clean-file delivery | type-specific administration, live scanner/storage acceptance and response-template lifecycle remain |
| 23 | Partnership inquiries | Partially implemented | engagement type/schema supports it | dedicated public path/form absent |
| 24 | General contacts | Partially implemented | dedicated bilingual general/partnership/media form plus typed contact intake | office directory/map and inquiry-specific operations remain |
| 25 | Newsletter subscriptions | Partially implemented | consent-gated request, hashed single-use confirmation, localized queued notification, signed confirmation and idempotent unsubscribe | subscriber administration/export and campaign lifecycle are absent; downstream campaign/bounce provider acceptance remains external |
| 26 | Search | Partially implemented | parent-aware published projection, ranking, private-query hashing and hourly reconciliation/stale deletion | external provider failover and advanced filters remain |
| 27 | Media library | Blocked by external dependency | transactional quarantine metadata, signature detection, UUIDv7-only object keys, scan adapter, variants, dedicated approval, cross-module private-file isolation and audited signed delivery | live S3/ClamAV acceptance is required before the production path can be verified |
| 28 | Multilingual content | Partially implemented | locale routes, 382-key Amharic UI catalog, hreflang and rendered English/Amharic RFP/contact browser evidence | translation readiness and content publication completeness controls absent |
| 29 | CMS workflow | Partially implemented | immutable revisions, optimistic 409 conflicts, signed/no-store preview, independent revision-author approval, persisted schedules, idempotent queued publish/unpublish, archive and rollback-as-new-revision | automatic redirect creation for expired URLs, recycle-bin UI and catalog-specific authoring remain |
| 30 | SEO management | Partially implemented | canonical, hreflang, robots, sitemaps and redirects | per-record administration, structured data breadth and redirect-chain prevention incomplete |
| 31 | Analytics consent | Partially implemented | version-locked append-only decisions through Form Request/DTO/Action/audit; withdrawal decisions supported | visitor preference UI and live analytics adapter remain |
| 32 | Users | Partially implemented | invitation/acceptance lifecycle, administration, MFA scope, status/session invalidation and protected self-deletion | operational directory/SSO integration remains external |
| 33 | Roles | Partially implemented | role update, delegated role/permission ceilings and session invalidation | catalog-specific policy matrix remains incomplete |
| 34 | Permissions | Partially implemented | centralized catalog and route middleware | several catalog permissions unused and operation-specific permissions absent |
| 35 | System settings | Partially implemented | typed catalog, validation and audit | secrets/integration configuration intentionally external |
| 36 | Audit logs | Partially implemented | append-only model, admin list, sensitive download, consent, invitation, workflow and retention events | export and immutable external/WORM copy absent |
| 37 | Notifications | Partially implemented | three localized queued acknowledgements | template administration, delivery outcomes and reminders absent |
| 38 | Queues | Partially implemented | named media/search/notification jobs and Supervisor config | all required jobs, failure hooks and live Redis evidence absent |
| 39 | Scheduler | Blocked by external dependency | due publication, hourly search reconciliation, privacy cleanup and retention inspection; singleton locks configured | target scheduler leadership/monitoring evidence is required |
| 40 | Retention processing | Blocked by external dependency | bounded dry-run/execute modes, explicit authorized approval, run evidence, legal-hold exclusion, application/engagement/registration anonymization and file deletion jobs | approved policy/legal-hold procedure and backup-disposal evidence are required |
| 41 | Administration dashboard | Partially implemented | permission-aware navigation and KPI cards | queues, recent activity, health and review panels incomplete |
| 42 | Operational health | Partially implemented | `/up`, truthful `/ready`, deployment templates | liveness/readiness split, worker/scheduler/backup indicators and alerting incomplete |

## Cross-cutting disposition

Closed in code and automated evidence:

1. Public staff registration is disabled; staff onboarding is invitation-only.
2. Role-bearing staff cannot self-delete; authorized administration must disable them.
3. Invalid workflow transitions return stable HTTP 409 responses.
4. Media quarantine metadata is written and audited inside the Action transaction.
5. Private media/application downloads are audited after authorization and signature validation.
6. Delegated administrators cannot grant roles or permissions they do not possess.
7. Parent publication/consent state is enforced by public catalog queries and search reconciliation.
8. RFP attachments use bounded validation, opaque quarantine keys, cross-module policy isolation and audited signed delivery.
9. Scheduled publication and unpublication execute idempotently and append workflow/audit evidence.
10. Models deny mass assignment of generated IDs, and development seeds no longer invent privileged credentials.

Open application-controlled work:

1. The Content workflow retains `unpublished` because the SRS requires it while the LLD enum omits it; authority and rationale remain documented.
2. Several catalog-specific admin CRUD, import/export, reporting and provider-integration surfaces remain partial.
3. This directory has no Git metadata, so SRS document-control requirement DOC-008 cannot be evidenced from the supplied workspace.

External or target-environment verification:

1. PHP 8.4, Redis, S3-compatible storage, ClamAV, external search, SMTP, monitoring and independent acceptance evidence cannot be certified locally.

This audit is updated as repairs close findings. “Complete and verified” requires backend, UI, authorization, validation, persistence, audit, localization, error handling, tests and documentation evidence.
