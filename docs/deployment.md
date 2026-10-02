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

## React and server-side rendering

Every active public, authentication, profile and administration screen uses React
through Inertia. Blade remains the initial HTML document and the source for email
templates. Historical page templates are retained as references and are no longer
rendered by the controllers.

Install Node.js 22 or newer. Run `npm ci` and `npm run build` during artifact
creation; this builds both `public/build` and `bootstrap/ssr`. Include both
directories in the release. The SSR process also needs the locked runtime
dependencies from `node_modules` (install with `npm ci --omit=dev` on the release
if dependencies are not packaged). Do not deploy `public/hot`.

Set `INERTIA_SSR_ENABLED=true` and
`INERTIA_SSR_URL=http://127.0.0.1:13714`. Install the example
`deploy/supervisor/impact-ssr.conf`, adjusting the Node executable and release
path, then restart `impact-ssr` after switching releases. The rendering service
binds to localhost; it does not need a public proxy route. On Plesk, run the same
`node bootstrap/ssr/ssr.js` command under a supervised background service, separate
from the PHP website.

Verify with `php artisan inertia:check-ssr` and inspect the initial HTML for a
localized public page: its heading and content must be present with JavaScript
disabled. Authentication and administration routes deliberately use client-side
rendering so private workspace props are not sent to Node. If the renderer is
unavailable, public pages fall back to client rendering after a bounded timeout;
monitor renderer health because this fallback loses pre-rendered SEO content.

For local development with the existing Vite 6 setup, run `npm run build`,
`npm run ssr`, and `npm run dev` in separate terminals. Vite forwards Inertia's
development rendering requests to the local SSR worker. Rebuild and restart the
SSR worker after changing React components to keep its output aligned with the
client. Set `INERTIA_SSR_ENABLED=false` locally when working with client HMR alone.
The manual rendering setup follows the
[Inertia SSR documentation](https://inertiajs.com/docs/v3/advanced/server-side-rendering).

Production sign-off requires TLS/proxy validation, secrets from a managed store, MySQL/Redis/S3/search/scanner connectivity, queue supervision, monitoring and backup restore evidence. The complete open list is maintained in `implementation/production-readiness-blockers.md`.
