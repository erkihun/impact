# Impact Consulting digital platform

Laravel 12 implementation of the public website, bilingual content platform and secure engagement operations described by the project SRS, SDD and LLD.

## Current delivery baseline

Implemented and verified:

- English/Amharic public routes and responsive Blade/Tailwind design system
- services, industries, experts, case studies, insights, events, careers and search read models
- consultation/contact/RFP intake with encrypted sensitive fields, consent evidence, auditable lifecycle assignment and queued acknowledgement
- UUIDv7 domain identifiers, RBAC seed catalog, append-only audit/security events
- editorial workflow state machine with guarded transitions and immediate public-search projection updates
- event capacity enforcement, restricted recruitment files and audited application state history
- quarantine, signature detection, ClamAV adapter, responsive variants, controlled promotion and signed private delivery
- user/role/settings administration, session revocation, login lockout, TOTP MFA and one-use recovery codes
- security headers, CSP-safe Alpine runtime, HTTPS enforcement, correlation IDs and throttling
- MySQL schema, deterministic development seed data, Pest foundation tests

The full 379-item requirement inventory and the remaining delivery backlog are tracked in [requirements traceability](docs/implementation/requirements-traceability.md), the [module plan](docs/implementation/module-plan.md), and [production-readiness blockers](docs/implementation/production-readiness-blockers.md). A `Partial` status is not an acceptance claim.

## Local setup

Required production baseline: PHP 8.4, Composer 2, Node 20+, MySQL 8.4, Redis 7.

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
npm ci
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Local seed administrator: `admin@impact.test`. Set `DEVELOPMENT_ADMIN_PASSWORD` before seeding; development seeders do not run in production.

## Quality gates

```powershell
php artisan test
vendor\bin\pint --test
vendor\bin\phpstan analyse
npm run build
php artisan view:cache
```

This checkout was verified locally with PHP 8.2.12 because PHP 8.4 is not installed on the workstation. `composer.json` and the PHP 8.4/MySQL 8.4/Redis CI workflow enforce the target baseline; final certification still requires a successful run in that environment and the selected production services.

## Documentation

Start with:

- [Installation](docs/installation.md)
- [Architecture](docs/architecture.md)
- [Security](docs/security.md)
- [Permissions](docs/permissions.md)
- [Content workflow](docs/content-workflow.md)
- [Testing](docs/testing.md)
- [Implementation control](docs/implementation/repository-audit.md)
