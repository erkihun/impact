# Release readiness

Date: 2026-07-26

Decision: **Not approved for production yet.**

## Passing application gates

- 20/20 MySQL migrations and all seeders pass without creating a default user; both continuation migrations also pass rollback/reapply.
- 90 non-vendor routes register.
- 101 tests / 472 assertions pass through both required runners.
- PHPStan level 5 reports no errors.
- Pint passes.
- Vite production build passes.
- Composer validation passes; Composer and npm audits report no vulnerabilities.
- Queue failure table is empty locally.
- Publication, search reconciliation, privacy cleanup and retention inspection schedules register.
- Playwright English/Amharic RFP, English contact and Amharic home desktop/mobile flows pass with no overflow or console warnings/errors.
- All 379 SRS IDs are represented in the implementation traceability matrix; only 17 are conservatively marked Verified.

## Release-blocking gates

1. Run the locked application on PHP 8.4; the current workstation is PHP 8.2.12 and correctly fails `composer install`.
2. Validate the production Redis, storage, scanner, email, search and edge integrations.
3. Supply approved real privacy, retention, legal-hold, contact, brand and editorial content.
4. Complete the application-controlled release blockers listed separately in `unresolved-dependencies.md`.
5. Produce independent accessibility, load, privacy and security reports.
6. Demonstrate backup restore, rollback, monitoring, incident and queue-replay procedures.
7. Restore or supply authoritative version-control history and approval metadata; the provided working directory has no `.git` repository.

## Production command sequence

```powershell
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan about
php artisan schedule:list
php artisan queue:failed
php artisan impact:search:reconcile
php artisan impact:retention:inspect
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --test
```

Destructive retention must not be scheduled until `RETENTION_EXECUTION_ENABLED=true`, `RETENTION_APPROVED_BY` identifies an active authorized privacy approver, and the policy/legal review is recorded.
