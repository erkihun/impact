# Security control matrix

| Control | Implementation | Evidence | Residual work |
|---|---|---|---|
| TLS enforcement | `EnforceHttps`, explicit trusted hosts/proxy CIDRs, secure production template, HSTS on secure responses | `SecurityConfigurationTest` | production edge/TLS/proxy verification |
| Browser hardening | CSP, frame denial, nosniff, referrer/permission/cross-origin policies, private-route no-store/noindex | `AddSecurityHeaders`, `SecurityConfigurationTest` | CSP reporting and edge verification |
| Authentication | session auth, adaptive hashes, verification, progressive throttle/temporary lock, absolute/inactivity limits and session version | auth + identity tests | target Redis session integration and alert routing |
| MFA | privileged-role gate, TOTP enrollment/challenge and hashed one-use recovery codes | `MfaEnforcementTest` | operational recovery procedure and target-runtime review |
| Authorization | permission catalog, role mappings, middleware, policies and final Action checks | identity/application/media tests | remaining content-type administration matrices |
| Auditability | append-only audit/security models with correlation IDs and hashes | immutability and submission tests | DB-level immutability/WORM export/retention |
| Sensitive data | encrypted phone, descriptions, meeting URLs and MFA secret | Eloquent encrypted casts | KMS/key rotation and encrypted backups |
| Consent | current-policy append-only consent Action, double opt-in and unsubscribe evidence | `ConsentGatingTest`, `DoubleOptInTest` | full visitor preference UI and provider acceptance |
| Input controls | Form Requests, enum rules, honeypot and coordinated throttles | public-form tests | external abuse-provider decision |
| File safety | signature MIME detection, quarantine, ClamAV adapter, image processing, controlled promotion and signed delivery | `MediaLifecycleTest` | live S3 and ClamAV acceptance evidence |
| Privacy retention | bounded dry-run/execute, explicit approver permission, legal holds, anonymization, media deletion and cleanup | `RetentionProcessingTest`, `PrivacyCleanupCommandTest` | approved real policy, backup disposal and production execution evidence |

Open controls are deployment blockers where the SRS marks them mandatory.
