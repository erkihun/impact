# English-only SEO migration

Approved direction (DD-008): the public website is English-only and served without a language prefix. This document records what was removed and how old URLs are handled.

## Removed

| Area | Removed | Replacement |
| --- | --- | --- |
| Amharic routes | `/am/{path}` closure that blindly redirected to `/en/{path}` | `LegacyLocaleController`: stored decision, 301 to the English equivalent, or 410 |
| English prefix | `Route::prefix('{locale}')` group and the `localized-home` route; the `locale` parameter on ~220 `route()` calls | Unprefixed routes; `/en/...` answered with a single-hop 301 |
| Locale logic in the shell | `site.locale` props, `site.seo.defaultLocaleUrl`, `document.documentElement.lang` updates | `<html lang="en">` in the root view |
| hreflang | `hreflang="en"` and `x-default` links in `inertia.blade.php` and `PublicLayout.jsx` | None (not needed for one language) |
| Localized sitemaps | `/sitemaps/{locale}.xml` driven by `localization.enabled_locales` | `/sitemaps/{segment}.xml` by content type |
| Locale SEO storage | Locale-keyed, unused `seo_metadata` table | Subject-keyed `seo_metadata` (no locale) |
| Search URLs | `/{locale}/...` search document URLs; indexing of every locale | English-only documents with URLs from `PublicUrlGenerator` |
| Seed data | `-en` slug suffixes and Amharic titles for events and vacancies | Plain English slugs |

Kept: the `locale` columns on content and other tables (all `en`), the `SetLocale` middleware, `localization.*` settings and the `locales` table. They are generic infrastructure with no SEO output; removing them would be an unrelated schema change.

## Legacy URL decisions

See `seo-redirect-strategy.md` for the rules and the map produced from the development database. Summary:

- `/en` → `/`; `/en/{page}` → `/{page}`; detail URLs go straight to the resource's current URL in one hop; non-existent paths 404.
- `/am/...` static pages → the English page; Amharic detail URLs → the same resource's public English page when one exists; everything else → 410 Gone. Never a homepage catch-all.

## Data and index cleanup (migration `2026_10_06_090100_migrate_legacy_locale_urls_to_english_only`)

1. Existing redirects referencing `/en/...` are rewritten to unprefixed paths.
2. Every stored non-English detail URL gets an explicit 301 or 410 row (`origin = legacy_locale`).
3. Non-English search documents are deleted; `/en/...` document URLs are rewritten.
4. Navigation rows lose the `locale` route parameter and `localized-home` becomes `home`.
5. Navigation caches for English locations are cleared.

## Post-deployment checklist

- Run `php artisan migrate --force`, then `php artisan seo:sitemap-generate`.
- Review the legacy decisions in SEO centre → Redirects.
- Run `php artisan optimize:clear` (or `cache:clear`) so no cached navigation, settings or old sitemap survives.
- In Search Console and Bing: submit `/sitemap.xml`, remove any previously submitted `/sitemaps/en.xml`, and use URL inspection on a sample of `/en/...` and `/am/...` URLs to confirm 301/410.
- Expect `/en/...` and `/am/...` URLs to drop out of the index over several weeks as crawlers process the redirects and 410s.
