# Verification findings

Date: 2026-07-26

## Baseline command evidence

| Command | Result | Finding |
|---|---|---|
| `composer install --no-interaction` | Blocked | Local PHP 8.2.12 does not satisfy root requirement `^8.4`; do not weaken the target baseline |
| `npm install` | Pass | 167 packages audited; zero vulnerabilities |
| `php artisan optimize:clear` | Pass | all generated Laravel caches cleared |
| `php artisan migrate:fresh --seed` | Pass | 20/20 migrations and all seeders completed on MySQL without inventing a user |
| `php artisan migrate:rollback --step=2` then `migrate` | Pass | both continuation migrations reverse and reapply cleanly on MySQL |
| `php artisan route:list --except-vendor` | Pass | 90 routes |
| `php artisan about` | Pass | Laravel 12.64; local database-backed cache, queue and session |
| `php artisan test` | Pass | 101 tests, 472 assertions |
| `vendor/bin/pest` | Pass | 101 tests, 472 assertions |
| `vendor/bin/pint --test` | Pass | no formatting defects |
| `vendor/bin/phpstan analyse` | Pass | no level-5 static-analysis errors |
| `npm run build` | Pass | production Vite bundle generated |
| `composer audit --locked` | Pass | no advisories |
| `npm audit --audit-level=moderate` | Pass | zero vulnerabilities |
| `php artisan queue:failed` | Pass | no local failed jobs |
| `php artisan schedule:list` | Pass | publication, hourly search reconciliation, privacy cleanup and retention inspection registered |
| Playwright browser QA | Pass | English/Amharic RFP, English contact and Amharic home at 1440px/390px; mobile menu, no horizontal overflow, zero console warnings/errors |
| required `config:show` commands | Pass with environment gap | local database drivers; secure production example added, live Redis/S3/provider configuration unverified |

## Application-controlled findings

| ID | Severity | Finding | Status |
|---|---|---|---|
| VF-001 | High | Open self-registration bypasses the required invite/activate staff lifecycle | Closed and tested |
| VF-002 | High | Privileged or last administrator can self-delete through the profile route | Closed and tested |
| VF-003 | High | Application and engagement invalid transitions return 422 rather than controlled 409 | Closed and tested |
| VF-004 | High | Application anonymization leaves restricted uploaded files and identifying filenames | Closed and tested |
| VF-005 | Medium | Private file downloads lack audit evidence | Closed and tested |
| VF-006 | Medium | Media metadata write occurs outside the upload Action transaction | Closed and statically verified |
| VF-007 | Medium | Scheduler, reconciliation and retention execution paths are absent | Closed for core paths; live scheduler evidence external |
| VF-008 | Medium | Role management lacks a delegated permission ceiling | Closed and tested |
| VF-009 | Medium | Queue after-commit and production driver guidance are unsafe/incomplete | Code default closed; live deployment configuration required |
| VF-010 | Medium | Administration/public module and accessibility coverage is incomplete | Partially closed; catalog CRUD and independent WCAG remain |
| VF-011 | High | RFP attachments had no secure intake, lifecycle or object-scoped download path | Closed and tested |
| VF-012 | High | Development seed configuration exposed an implicit privileged credential and the seed chain assumed the account existed | Closed and tested; credentials are explicit-only and fixture seeding safely skips |
| VF-013 | High | Scheduled content was recorded but not fully executed through idempotent publish/unpublish transitions | Closed and tested |
| VF-014 | High | A current revision author could approve through ownership drift | Closed and tested |
| VF-015 | Medium | Generated model identities were mass assignable through an empty base guard list | Closed and architecture-tested |
| VF-016 | Medium | The implementation traceability table did not expose all required SDD/LLD, route, class, table, policy, test and defect dimensions | Closed for all 379 SRS IDs; conservative Partial/Planned statuses retained |

## External findings

| ID | Dependency | Required evidence |
|---|---|---|
| VE-001 | PHP 8.4 | locked install, full CI, tests and static analysis |
| VE-002 | Redis 7 | cache/session/queue/locks, failover and worker evidence |
| VE-003 | S3-compatible storage and ClamAV | bucket policy/lifecycle plus clean/malicious acceptance |
| VE-004 | Search and SMTP providers | indexing/failover and delivery/bounce evidence |
| VE-005 | Production edge and operations | TLS/proxies/DNS/CDN, monitoring, backup restore and incident drills |
| VE-006 | Independent acceptance | WCAG 2.2 AA, browser, load, privacy, DAST and penetration reports |
| VE-007 | Version-control authority | repository history, ownership, approval and revision evidence; the supplied directory has no `.git` metadata |
