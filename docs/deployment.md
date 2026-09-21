# Deployment

Deploy immutable artifacts built on PHP 8.4. Required order:

1. backup and verify restore point;
2. enable maintenance only when a migration is not backward-compatible;
3. install locked dependencies and assets;
4. run `php artisan migrate --force`;
5. run `php artisan optimize`;
6. restart workers;
7. check `/ready`, public localized pages and authenticated admin access;
8. retain the previous artifact for rollback.

For Plesk, select the project directory containing `artisan` and `composer.json`
as the Composer application directory, and its `public` subdirectory as the web
document root. Deploy the complete project, including `app/Foundation/Application.php`
and `bootstrap/app.php`. The application declares its fixed `App\` namespace
explicitly so Artisan does not depend on Composer metadata to infer it. Keep the
`autoload.psr-4` mappings in `composer.json` intact; Composer still needs them to
load application classes.

Install production dependencies with
`composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`
using the site's configured PHP binary. Then run `php artisan package:discover`
with that same PHP version to verify application startup before proceeding.

Reference configuration is provided under `deploy/nginx`, `deploy/php`, and `deploy/supervisor`; it must be adapted and reviewed for the target environment.

Production sign-off requires TLS/proxy validation, secrets from a managed store, MySQL/Redis/S3/search/scanner connectivity, queue supervision, monitoring and backup restore evidence. The complete open list is maintained in `implementation/production-readiness-blockers.md`.
