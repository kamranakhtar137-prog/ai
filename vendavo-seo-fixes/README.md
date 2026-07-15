# Vendavo SEO Fixes

WordPress plugin for post-launch SEO remediation on [vendavo.com](https://www.vendavo.com/).

## Installation

1. Copy the `vendavo-seo-fixes` folder to `wp-content/mu-plugins/vendavo-seo-fixes/` **or** `wp-content/plugins/vendavo-seo-fixes/`.
2. If installed as a regular plugin, activate **Vendavo SEO Fixes** in WP Admin.
3. Replace `data/redirects.csv` with the client's final Menerva redirect audit CSV (optional if redirects are managed in the Redirection plugin instead).
4. Remove the legacy hardcoded Organization schema snippet from the theme/Elementor custom code area if it is still present (the plugin also strips it from rendered HTML as a safeguard).
5. Clear WP Engine, Cloudflare, and Yoast sitemap caches after deployment.

## What this plugin fixes

| Issue | Implementation |
| --- | --- |
| Organization schema staging URLs | Removes staging JSON-LD from rendered HTML and sanitizes Yoast Organization URLs dynamically |
| FAQPage / SoftwareApplication schema | **Dynamic** — generated at runtime from page title, SEO meta description, and Elementor FAQ tab content |
| Paginated canonical tags | Forces absolute canonical URLs with `?page=N` via Yoast/core filters; removes duplicate relative canonical tags |
| Infinite pagination | Removes `vfp-loadmore` links on the last page; 301 redirects invalid `?page=` URLs |
| XML sitemap cleanup | Excludes author, category, board, leadership, and utility URLs from Yoast sitemaps |
| Uppercase URLs | 301 redirects mixed/uppercase paths to lowercase |
| Redirect audit | Optional CSV-driven redirects (see `data/redirects.csv`) |

## Redirect audit workflow

The bundled `data/redirects.csv` contains sample rows from the Menerva audit preview. **Replace this file with the client's final CSV before relying on plugin-managed redirects.**

If the client prefers the Redirection plugin:

1. Review the final CSV against existing Redirection rules.
2. Import only net-new or changed rules.
3. Leave `data/redirects.csv` empty (header only) to avoid duplicate redirect handling.

See `docs/REDIRECT-REVIEW.md` for review notes.

## Sitemap exclusions

See `docs/SITEMAP-REMOVED.md` for the list of URLs/types removed from Yoast XML sitemaps.

## Dynamic schema behavior

- **SoftwareApplication** name comes from the page title
- **SoftwareApplication** description comes from Yoast/Rank Math meta description, excerpt, or first content paragraph
- **FAQPage** questions/answers are parsed from Elementor `_elementor_data` (accordion/tabs) with rendered HTML fallback
- **Organization** URLs are forced to `home_url()` and staging domains are stripped from Yoast schema
- No static JSON files need to be maintained — update page content and schema updates automatically

### Enable schema on additional pages later

```php
add_filter( 'vendavo_seo_schema_enabled_for_post', function( $enabled, $post ) {
    if ( is_page( 'my-new-faq-page', $post ) ) {
        return true;
    }
    return $enabled;
}, 10, 2 );
```

## Paginated archives configured

- `/insights/blog/` — 9 posts per page
- `/insights/whitepapers/` — 9 per page
- `/insights/videos/` — 9 per page
- `/glossary/` — 18 per page

Adjust `includes/class-pagination.php` if Menerva/JetEngine listing settings change.
