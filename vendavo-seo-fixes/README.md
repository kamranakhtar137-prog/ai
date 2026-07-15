# Vendavo Dynamic Schema

**One file. Fully dynamic. No static JSON to maintain.**

## Direct download

**https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/vendavo-seo-fixes-a1c0/vendavo-seo-fixes/vendavo-dynamic-schema.php**

## Install (30 seconds)

1. Download `vendavo-dynamic-schema.php`
2. Upload to: `wp-content/mu-plugins/vendavo-dynamic-schema.php`
3. Delete the old hardcoded Organization schema snippet (the one with `vendavostg.wpenginepowered.com`)
4. Clear WP Engine + Cloudflare cache

No activation needed — mu-plugins load automatically.

## What it does dynamically

| Schema | Generated from |
|---|---|
| **SoftwareApplication** | Page title + Yoast meta description |
| **FAQPage** | Elementor FAQ tabs/accordion content (`_elementor_data`) |
| **Organization fix** | Strips staging JSON-LD; sanitizes Yoast Organization to `home_url()` |

## Pages covered automatically

- `/platform/`
- `/platform/pricing/`
- `/platform/quoting-and-agreements/`
- `/platform/rebates/`
- `/platform/ai-and-intelligence/`
- `/platform/analytics/`
- `/platform/integrations-and-security/`

Edit page content in Elementor → schema updates automatically.

## Enable on more pages later

```php
add_filter( 'vendavo_seo_schema_enabled_for_post', function( $enabled, $post ) {
    if ( is_page( 'my-page-slug', $post ) ) {
        return true;
    }
    return $enabled;
}, 10, 2 );
```

## Verify after install

View source on `/platform/pricing/` — look for:

```html
<script type="application/ld+json" class="vendavo-dynamic-schema">
```

## Static files

Old static snapshots are in `reference/` folder — **do not use for production**.
