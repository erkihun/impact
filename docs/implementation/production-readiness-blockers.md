# Production readiness blockers

This file distinguishes implemented code from evidence that can only be produced in the target environment. The platform must not be described as production-ready until every mandatory item is closed or formally waived.

## Internal delivery backlog

- complete catalog administration and relationship management for services, industries, experts, case studies, insights, events, and vacancies;
- add catalog-specific authoring/relationship administration and recycle-bin restoration;
- add event/vacancy authoring, application export, inquiry-specific operations, and approved bulk exports;
- add a full visitor-facing consent preference center (the consent API and newsletter confirmation/unsubscribe lifecycle are implemented);
- implement analytics reports, integration outbox delivery, and the remaining versioned API/export surface;
- implement immutable audit export and an approved production retention execution record (the legal-hold-aware engine is implemented);
- complete independent Amharic editorial review across all administration and validation surfaces.

## Target-environment acceptance

- execute the complete CI pipeline on PHP 8.4, MySQL 8.4, and Redis 7;
- validate Redis sessions, cache, locks, rate limits, queues, and scheduler overlap prevention;
- validate S3-compatible quarantine/public/private buckets, policies, encryption, lifecycle, and signed access;
- validate clean and malicious-file paths against the production ClamAV service;
- select and verify the production Scout search engine, rebuild process, and deletion propagation;
- verify SMTP deliverability, bounce handling, complaint handling, and approved sender identity;
- validate TLS, trusted proxies, secure cookies, DNS, CDN/cache rules, and security headers at the edge;
- load approved brand, editorial, contact, legal, privacy, retention, and consent-policy content;
- complete independent WCAG 2.2 AA, supported-browser, responsive, performance/Core Web Vitals, load, SAST/DAST, penetration, and privacy reviews;
- complete backup restoration, rollback, incident response, alerting, and failed-job replay drills.
