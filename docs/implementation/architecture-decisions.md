# Architecture decisions

## ADR-001: Server-rendered public and administration surfaces

Use Blade, Tailwind and minimal Alpine-compatible JavaScript. This follows the LLD, reduces public runtime complexity and supports progressive enhancement.

## ADR-002: Actions own business transactions

Controllers validate and translate HTTP concerns; Actions own state transitions, row locks, audit evidence and transactional changes.

## ADR-003: UUIDv7 domain identity

All domain models generate time-ordered UUIDv7 identifiers. Canonical UUID strings are the current storage format pending explicit approval of a binary conversion strategy (DD-005).

## ADR-004: Append-only application audit

Audit and security event models reject update/delete operations. Production assurance additionally requires DB privilege separation or immutable export.

## ADR-005: Translation records for managed content

Managed entities use stable parent records and locale/version records. Interface copy uses Laravel locale catalogs. Publication is scoped by locale.

## ADR-006: Private-first file lifecycle

Uploaded files are never publicly trusted on receipt. The media schema models quarantine, scan status, variants and visibility; production delivery waits for the scanner/storage integration phase.

## ADR-007: Conservative traceability

Requirement status is evidence-based. Code presence alone is `Partial`; `Verified` requires an acceptance test or operational proof on the target environment.
