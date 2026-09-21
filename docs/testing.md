# Testing

```powershell
php artisan test
vendor\bin\pint --test
vendor\bin\phpstan analyse --memory-limit=1G
npm run build
php artisan view:cache
php artisan route:list --except-vendor
```

Pest uses in-memory SQLite. Schema changes also require `php artisan migrate:fresh --seed` on MySQL. Release certification additionally runs PHP 8.4, Redis/S3/Meilisearch/scanner integration, accessibility, performance, browser, security and backup/restore tests.

Record exact results in `implementation/test-coverage-matrix.md`; do not infer coverage from file count.
