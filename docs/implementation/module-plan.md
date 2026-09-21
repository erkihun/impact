# Module implementation plan

| Phase | Module | Current state | Exit evidence |
|---|---|---|---|
| 0 | Document inventory and discrepancy control | Complete | 379 IDs inventoried; discrepancies recorded |
| 1 | Laravel foundation, UUIDs, locale, security middleware | Implemented | migration, route, header and locale tests |
| 2 | Identity, RBAC, immutable audit | Implemented; certification pending | user and role Actions, session revocation, TOTP/recovery, direct-route tests |
| 3 | Content workflow and versioned content | Implemented core; broader content types partial | immutable edit, conflict, signed preview, review/publish and rollback-as-revision are tested |
| 4 | Services, industries, experts, case studies, insights | Partial | public read paths exist; admin lifecycle and relation management required |
| 5 | Engagement submissions and consent | Implemented core | intake, idempotency, consent, triage/assignment/history and queued acknowledgement tested; attachments/export remain |
| 6 | Events, vacancies and applications | Implemented core | capacity/closure, secure CV intake, HR workflow/history/download and acknowledgement tested; admin event/vacancy authoring/export remain |
| 7 | Media and file security | Implemented local adapter path | MIME signature, quarantine, ClamAV boundary, variants, promotion and signed delivery tested; production S3/ClamAV integration pending |
| 8 | Search, SEO, redirects and analytics | Implemented database projection | parent-aware visibility, reconciliation, ranking, privacy-safe query logs, sitemap/robots/redirect tests; external provider and reports remain |
| 9 | Operations and API | Partial | publication/search/privacy/retention schedules, readiness, worker/Nginx templates and API resources exist; monitoring/export API breadth remains |
| 10 | Certification | Blocked by target environment | PHP 8.4 CI execution, Redis/S3/scanner/search integration, WCAG, load, restore and security assessment evidence |

Every phase must update the requirements matrix and add evidence before `Partial` becomes `Implemented` or `Verified`.
