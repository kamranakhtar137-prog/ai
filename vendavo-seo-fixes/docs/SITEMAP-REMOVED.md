# XML Sitemap Removals

The Vendavo SEO Fixes plugin excludes the following from Yoast XML sitemaps.

## Removed post type sitemaps

| Post type | Example URL pattern | Reason |
| --- | --- | --- |
| `board-of-director` | `/board-of-director/*` | Thin profile pages; not primary SEO landing pages |
| `leadership-team` | `/leadership-team/*` | Thin profile pages; not primary SEO landing pages |

## Removed taxonomy sitemaps

| Taxonomy | Example URL pattern | Reason |
| --- | --- | --- |
| `category` | `/category/*` | Category archives are low-value/thin for this site structure |

## Removed author archives

| Type | Example URL pattern | Reason |
| --- | --- | --- |
| Author archives | `/author/*` | Author pages are not target organic landing pages |

## Removed individual pages

| Page path | URL | Reason |
| --- | --- | --- |
| `mss` | `https://www.vendavo.com/mss/` | Utility/internal page flagged in audit |

## Sitemaps retained

The following Yoast sitemaps remain enabled:

- `post-sitemap.xml`
- `page-sitemap.xml`
- `case-study-sitemap.xml`
- `data-sheet-sitemap.xml`
- `events-sitemap.xml`
- `glossary-sitemap.xml`
- `press-newsroom-sitemap.xml`
- `videos-sitemap.xml`
- `webinars-sitemap.xml`
- `whitepapers-sitemap.xml`

## After deployment

1. Regenerate Yoast sitemaps in WP Admin.
2. Purge WP Engine / Cloudflare cache.
3. Resubmit `https://www.vendavo.com/sitemap_index.xml` in Google Search Console if needed.

If Menerva's audit recommends additional exclusions, add them to `includes/class-sitemap.php` and update this document.
