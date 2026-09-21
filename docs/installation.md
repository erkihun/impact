# Installation

## Runtime

PHP 8.4 with `curl`, `dom`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `redis` or Predis, and `zip`; Composer 2; Node 20+; MySQL 8.4; Redis 7.

```powershell
Copy-Item .env.example .env
composer install --no-interaction
php artisan key:generate
npm ci
php artisan migrate --seed
npm run build
php artisan storage:link
php artisan optimize
```

Never use development seed credentials in a shared or production environment. Production deployment must run the migration without `--seed` unless explicitly using approved production seed classes.
