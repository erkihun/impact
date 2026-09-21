# Unresolved dependencies

Date: 2026-07-26

| ID | Owner/dependency | Blocking evidence |
|---|---|---|
| UD-001 | PHP 8.4 CI/runtime | locked Composer install, full suite and extension inventory on the declared runtime |
| UD-002 | Redis 7 | sessions, cache, rate limits, locks, queues, failover and worker restart evidence |
| UD-003 | S3-compatible storage | private/public/quarantine bucket policy, encryption, lifecycle, signed access and deletion evidence |
| UD-004 | ClamAV | clean, malicious, timeout and unavailable-service acceptance |
| UD-005 | SMTP/delivery provider | sender identity, bilingual delivery, bounce, complaint and suppression evidence |
| UD-006 | Search platform decision | database projection capacity approval or external Scout provider integration/failover |
| UD-007 | Edge/operations | DNS, TLS, exact proxy CIDRs, trusted hosts, CDN, alerts and log aggregation |
| UD-008 | Governance owners | approved privacy/consent text, retention periods, legal-hold procedure and production approver UUID |
| UD-009 | Recovery | encrypted backup, restore sample, RTO/RPO, release rollback and incident drills |
| UD-010 | Independent acceptance | WCAG 2.2 AA, browser/device, load, privacy, DAST and penetration reports |
| UD-011 | Version-control record | Git history, ownership, approval and revision evidence for DOC-008; the supplied workspace contains no `.git` metadata |
None of these external dependencies is represented as completed by local code or tests.

## Application-controlled release blockers

These are not external dependencies and must not be waived as infrastructure acceptance:

| ID | Incomplete application scope | Required closure evidence |
|---|---|---|
| APP-001 | Catalog administration | authorized, localized CRUD/workflow coverage for services, industries, experts, case studies, insights, events, vacancies, offices and the missing public catalogs |
| APP-002 | Bounded exports and reporting | approved-purpose subscriber, registration, application and audit exports with authorization, audit records, limits and sensitive-data tests |
| APP-003 | Notification lifecycle | authorized localized template administration, placeholder validation, delivery outcomes, reminders, bounce/complaint handling and suppression |
| APP-004 | Consent preference center | visitor-readable current decisions and auditable category-specific withdrawal/update UI |
| APP-005 | Operations dashboard and health | truthful queue, scheduler, backup, scanner, search and dependency indicators with alert routing |
| APP-006 | Scheduled URL expiry | create and validate approved redirects when automated unpublication removes a previously public URL |
