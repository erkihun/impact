# Performance audit

Date: 2026-07-26

## Application-level findings

| Control | Result |
|---|---|
| Pagination | Public collections, admin queues, audit, applications, engagement, users and search are paginated |
| Database indexes | Publication, workflow, status, retention, audit, search, queue and review paths have targeted indexes |
| Search reconciliation | Streamed/latest-version reconciliation with bounded chunk deletion; scheduled hourly with singleton lock |
| Retention | Bounded per-category selection, explicit limit and singleton schedule |
| Async work | media, search, publication and notifications use named queues, retries/backoff and after-commit dispatch |
| Build | Vite production build passes; CSS 78.93 kB (12.21 kB gzip), JS 107.68 kB (37.56 kB gzip) |
| Scheduled publication | Due work is selected in bounded chunks, dispatched to a named queue, uniquely keyed per schedule and guarded by row locks/idempotent terminal states |
| Caching safety | public assets are versioned; privileged/private/preview responses are no-store |

## Risks

- Database search uses SQL `LIKE`; acceptable for the local projection but not certified for the SRS peak dataset.
- Reconciliation tracks expected document keys in memory; production dataset sizing must validate worker memory.
- Redis cache/locks/queues and external search performance are not exercised locally.
- Images need production CDN transformations, cache policy and real-content weight review.

## Required target evidence

- k6/JMeter load tests for public browse/search, form bursts, admin queues and signed downloads.
- p95/p99 latency, throughput, error rate, DB query count, slow-query and lock-wait evidence.
- Lighthouse/Core Web Vitals on representative English/Amharic pages with production assets.
- Queue throughput/backlog/retry and scheduler-overlap evidence.
- CDN/origin cache behavior and large-upload processing under concurrency.

Disposition: performance-conscious controls are implemented, but no production load or Core Web Vitals claim is made.
