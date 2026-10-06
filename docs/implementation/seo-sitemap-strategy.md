# XML sitemap strategy

English-only. There is no per-language sitemap and no `xhtml:link` alternates.

## Files

| URL | Contents |
| --- | --- |
| `/sitemap.xml` | Sitemap index listing each non-empty child sitemap with its latest `lastmod` |
| `/sitemaps/pages.xml` | Homepage, about, listings, form pages whose feature is enabled, legal pages |
| `/sitemaps/services.xml`, `industries.xml`, `experts.xml`, `case-studies.xml`, `insights.xml`, `events.xml`, `careers.xml` | Current public detail URLs of each family |

Each `<url>` has `<loc>` (canonical, absolute) and `<lastmod>` when a real modification time exists (listings use the newest item). `changefreq` and `priority` are omitted because search engines ignore them.

## Inclusion rules

A URL is listed only when all are true:

- The resource's current version is public (`PublicResourceQuery::current()`): published version, published parent, consent/authorization present, not expired.
- Vacancies are open for applications.
- No editor override sets noindex, opts out of the sitemap, or sets a canonical elsewhere.
- The path is not the source of an enabled redirect (301/302/410).
- It is an English, unprefixed, query-free URL.

Never listed: drafts, previews, admin, search, filtered or paginated query URLs, private files, `/en/...` or `/am/...` URLs, redirecting or 410 URLs.

## Generation

- `SitemapBuilder::generate()` writes every file under `storage/app/seo/sitemaps` (disk `SEO_SITEMAP_DISK`, default `local`), each to a temporary file then renamed over the old one, so readers never see a partial file. A cache lock (`seo:sitemap-generate`, 120 s) prevents concurrent generation; a worker that cannot get the lock skips (the running one produces the same output). Empty segments are deleted.
- `GenerateSitemapsJob` (unique while queued) runs after every publication change through `PublicationSeoSync`: publish, unpublish, archive, slug change, deletion, parent status change, SEO override change and redirect change.
- The scheduler rebuilds per `seo.sitemap_refresh_frequency` as a safety net.
- `SitemapController` serves the stored files and generates live when a file is missing or was generated for another host, so the sitemap is correct even before the first job runs.

## Validation

`php artisan seo:sitemap-validate` checks XML well-formedness, root element and namespace, the 50,000 URL / 50 MB limits, and renders every listed URL to confirm 200, indexable and self-canonical. `seo:audit` repeats the URL checks and rejects legacy-language, private or query URLs.

## Search engine submission

Submit `https://<canonical-host>/sitemap.xml` in Google Search Console and Bing Webmaster Tools; it is also referenced from production robots.txt.
