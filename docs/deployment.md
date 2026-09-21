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

Reference configuration is provided under `deploy/nginx`, `deploy/php`, and `deploy/supervisor`; it must be adapted and reviewed for the target environment.

Production sign-off requires TLS/proxy validation, secrets from a managed store, MySQL/Redis/S3/search/scanner connectivity, queue supervision, monitoring and backup restore evidence. The complete open list is maintained in `implementation/production-readiness-blockers.md`.
