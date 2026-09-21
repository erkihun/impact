# Test coverage matrix

| Area | Automated evidence | State |
|---|---|---|
| Laravel authentication/profile baseline | `tests/Feature/Auth`, `tests/Feature/ProfileTest.php` | Covered |
| Editorial workflow transitions | `ContentWorkflowTest`, `ContentVersioningTest`, `WorkflowConflictResponseTest` | immutable edits, 409 conflicts, preview and rollback covered |
| Public localization | `LocalizationTest`, literal-key scan, browser QA | English/Amharic routes, copy catalog and rendered-language coverage |
| Engagement intake | `EngagementSubmissionTest` | success, validation, consent and audit |
| Audit immutability | `AuditImmutabilityTest` | model-level update/delete prevention |
| Route protection | `RouteProtectionTest` | guest and unverified scenarios |
| Browser security headers | `SecurityHeadersTest` | public response baseline |
| Schema portability | SQLite Pest + configured MySQL migration/seed | Foundation covered |
| RBAC action matrix | `IdentityAdministrationTest` | user/role changes, self-lockout and forced route covered |
| MFA and session revocation | `MfaEnforcementTest` | enrollment, TOTP, recovery storage, gate and revocation covered |
| Media lifecycle | `MediaLifecycleTest` | variants, state guard, promotion and signed access covered |
| Events/applications | `EventRegistrationTest`, `ApplicationSubmissionTest`, `ApplicationOperationsTest` | capacity, expiry, quarantine and lifecycle covered |
| Search indexing/SEO/redirects | `SearchPrivacyAndIndexingTest`, `SearchReconciliationTest`, `SeoTest` | parent publication/consent boundary, reconciliation, ranking, query privacy and redirects covered |
| Invitations and protected account lifecycle | `IdentityInvitationTest`, `PrivilegedAccountDeletionTest` | invite/accept, registration disabled and staff self-deletion blocked |
| Consent/newsletter | `ConsentGatingTest`, `DoubleOptInTest` | current-policy consent, confirmation, signed links, idempotent unsubscribe |
| Retention/legal holds | `RetentionProcessingTest`, `PrivacyCleanupCommandTest` | dry-run, authorization, legal holds, anonymization and expired-data cleanup |
| Private file audit | `PrivateDownloadAuditTest` | signed authorized download and guest denial evidence |
| Queued acknowledgement | `QueuedAcknowledgementsTest` | all three public submission families covered |
| Browser/security smoke | `SecurityConfigurationTest` plus Playwright English/Amharic/form flows | application-level smoke passed; independent WCAG/load/DAST certification blocking |
| RFP attachment security | `RfpAttachmentSecurityTest` | bilingual pages, bounded quarantine intake, opaque keys, initial history, cross-module denial and authorized signed delivery |
| Scheduled publication lifecycle | `ScheduledPublicationLifecycleTest` | future-time validation, persisted schedule, idempotent publish/unpublish, workflow and audit evidence |
| Model/seed hardening | `MassAssignmentProtectionTest`, `DevelopmentAdminSeederSecurityTest` | generated IDs guarded, composite allow-list, explicit-only admin secret and complete seed-chain safety |

## Latest local evidence

2026-07-26:

- Pest and `php artisan test`: 101 tests passed, 472 assertions
- Pint: pass
- Larastan level 5: no errors
- Vite production build: pass
- Blade view cache: pass
- configured MySQL migrations: 20/20 ran and seeders completed without a default account
- Composer audit: no advisories
- npm audit: no vulnerabilities
- PHP strict-types scan: 331/331 first-party files
- source audit: no empty model guards, `env()` outside config, debug calls, TODO/FIXME markers, route action closures or literal passwords
- browser QA: English/Amharic RFP, English contact and Amharic home at desktop/mobile widths; no overflow or console warnings/errors

PHP 8.4 target-runtime certification and external-service integration remain outstanding.
