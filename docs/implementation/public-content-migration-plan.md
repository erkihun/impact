# Public content migration plan

## Completed phases

1. Inventory routes, views, domain sources, and hard-coded page headers.
2. Add typed enums, registry, persistence, DTOs, models, mutations, workflow, audit, and cache.
3. Seed approved bilingual template compositions without overwriting existing records.
4. Integrate managed headers into all public page families while preserving fixed forms and domain-query semantics.
5. Add localized navigation/footer records and administration.
6. Add strict verification and regression coverage.

## Deployment sequence

```powershell
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=PageCompositionSeeder --force
php artisan db:seed --class=NavigationConfigurationSeeder --force
php artisan public-content:verify --strict
npm ci
npm run build
php artisan optimize
```

Back up the database before deployment. Migrations are additive. The composition seeder skips any page key/locale that already exists. Published public rendering falls back to the existing approved header only when no composition exists, allowing controlled phased rollout; strict verification prevents production sign-off while coverage is missing.

## Rollback

Disable the integration by removing published composition records only after a database backup, or deploy the previous application version while retaining additive tables. Do not reverse migrations after editors have created versions; restore application code first and preserve composition/audit evidence for forward recovery.
