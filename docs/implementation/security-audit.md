# Security audit

Date: 2026-07-26

Scope: application source, routes, middleware, authorization, state changes, files, sessions, privacy jobs and local runtime evidence. This is an internal engineering audit, not a penetration-test certificate.

## Verified controls

| Area | Result | Evidence |
|---|---|---|
| Staff onboarding | Pass | public registration disabled; hashed, expiring, single-use invitation acceptance |
| Authentication | Pass locally | email verification, throttling/lockout, absolute and inactivity session limits, session-version revocation |
| MFA | Pass locally | every role-bearing staff account is MFA-scoped; TOTP and one-use hashed recovery codes |
| Authorization | Pass for implemented routes | permission middleware, object policies and Action rechecks; delegated role/permission ceiling |
| Workflow integrity | Pass | invalid transitions return 409; optimistic content conflict returns `CONTENT_VERSION_CONFLICT` |
| Sensitive files | Pass locally | quarantine, signature checks, scanner boundary, processing states, dedicated approval, signed downloads and audit |
| Privacy | Pass for implemented flows | append-only consent, double opt-in, unsubscribe, retention approval, legal holds, anonymization and cleanup |
| Browser boundary | Pass locally | CSP, frame denial, nosniff, referrer, permissions, cross-origin, HSTS on HTTPS, no-store/noindex for privileged paths |
| Host/proxy boundary | Configured | trusted host allowlist and explicit proxy CIDR configuration; no wildcard proxy default |
| Audit | Pass at model/application layer | append-only audit/security events, correlation IDs, before/after hashes and sensitive-download evidence |
| Mass assignment | Pass locally | generated IDs are guarded by the base model; composite slug inputs use an explicit allow-list |
| Seed credentials | Pass locally | no default privileged password; a development administrator requires an explicit valid email and 16+ character secret |
| RFP attachments | Pass locally | bounded request validation, UUIDv7-only quarantine paths, scanner-state gate, parent-policy authorization, signed delivery and audit |
| Editorial separation | Pass locally | both content owner and active revision author are prevented from self-approval |

## Closed high-risk findings

- Replaced open staff registration with invitation acceptance.
- Blocked role-bearing account self-deletion.
- Revoked identifying application files during anonymization.
- Added audit evidence for authorized private downloads.
- Moved media metadata persistence into the upload Action transaction.
- Prevented parent-state/consent bypass on public catalog and search paths.
- Defaulted queues to after-commit dispatch.
- Removed identifying upload basenames from quarantine and promoted storage keys.
- Isolated application/RFP attachments from the generic media administration and download paths.
- Implemented idempotent scheduled publication/unpublication with locked state checks and audit evidence.

## Residual risks and external gates

- Production Redis session/cache/queue behavior, S3 bucket policy, ClamAV, SMTP and edge proxy behavior are unverified.
- Database append-only models are not a substitute for a WORM/external audit archive.
- CSP reporting and production asset/provider allowlists need edge validation.
- Key management, rotation, encrypted backups, restore, incident response and failed-job replay need operational evidence.
- Composer and npm dependency audits are clean and PHPStan is clean; independent SAST/DAST and penetration testing have not been performed.

Disposition: application-level security gates pass; production security certification remains blocked by the external items above.
