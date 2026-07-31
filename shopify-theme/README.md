# Product Page PageSpeed 90+ Optimizations

Shopify theme files to improve **mobile Lighthouse Performance** on product pages (target: **90+**).

## What this fixes

Your current `product-images.liquid` blocks LCP because ~1,200 lines of inline CSS/JS render **before** the gallery image. On lab tests this contributes to:

| Issue | Fix in this pack |
| --- | --- |
| LCP 19.6s (lab) | Gallery HTML first, `preload` LCP image, right-sized `srcset` (500w mobile) |
| TBT 590ms | Lightbox/hover-zoom JS loads **on first click/hover**, not at parse time |
| Oversized images | Cap LCP srcset at 1000w; lazy slides default to 600w |
| Unused Liquid work | Removed `image_list` loop and unused `image_*` URL captures |
| Shopify XR payload | XR scripts load when 3D model is near viewport or user interacts |

## Install

### 1. Upload assets

Copy to your theme `assets/` folder:

- `assets/product-gallery-zoom.css`
- `assets/product-gallery-lightbox.js`
- `assets/product-gallery-hover-zoom.js`

### 2. Replace snippet

Replace `snippets/product-images.liquid` with `snippets/product-images.liquid` from this folder.

### 3. LCP preload in `layout/theme.liquid`

Inside `<head>`, add:

```liquid
{% if template.name == 'product' %}
  {% render 'theme-product-lcp-preload' %}
{% endif %}
```

Or paste the contents of `snippets/theme-product-lcp-preload.liquid` directly.

### 4. Defer Shopify XR in `snippets/product.liquid`

**Remove** this block at the bottom of `product.liquid`:

```liquid
<script defer src="https://cdn.shopify.com/shopifycloud/shopify-xr-js/..."></script>
<script defer src="https://cdn.shopify.com/shopifycloud/model-viewer-ui/..."></script>
```

**Replace** the whole `{% if context == 'product' and model_media.size > 0 %}` section with:

```liquid
{% assign model_media = product.media | where: 'media_type', 'model' %}
{% render 'product-xr-deferred', model_media: model_media, context: context %}
```

## How it works

### LCP-first gallery

```
1. Gallery <img> renders immediately (eager, fetchpriority=high, 500w)
2. Lightbox DOM + tiny boot script (no heavy JS)
3. Zoom CSS/JS load only when user clicks (lightbox) or hovers (desktop lens)
```

### Image sizing

| Slide | Default src | srcset max |
| --- | --- | --- |
| First (LCP) | 500w | 1000w |
| Other slides | 600w (lazy) | 1200w |
| Lightbox zoom | 2048w on open | — |

`sizes` attribute: `(max-width: 798px) calc(100vw - 32px), (max-width: 1200px) 60vw, 38vw`

### Interaction-gated assets

- **Click to zoom**: loads CSS + `product-gallery-lightbox.js`, then opens lightbox
- **Hover zoom**: loads CSS + `product-gallery-hover-zoom.js` on first `mouseenter` (desktop only)
- **3D / AR**: loads Shopify XR when model cell enters viewport (+200px) or user taps model / “View in your space”

## Verify after deploy

1. Run [PageSpeed Insights](https://pagespeed.web.dev/) on a **product URL** (not homepage).
2. Confirm LCP element is the product hero image (~500–800w on mobile).
3. Confirm Total Blocking Time drops (no large inline script parse on load).
4. Test lightbox click-to-zoom and desktop hover lens still work.
5. Test 3D model + “View in your space” on a product with a model.

## Expected gains

Product-page lab score depends on third-party scripts (GTM, analytics, apps). This pack targets **theme-controlled** bottlenecks. Typical improvements:

- **LCP**: 2–8s faster (image delivery + preload + no render-blocking gallery JS)
- **TBT**: 200–400ms lower (deferred lightbox/hover JS)
- **Payload**: ~50–80KB less HTML per product page (no inline CSS/JS)

For 90+ you may also need to defer/limit third-party tags and audit `vendors.js` — see `reports/pagespeed-before-optimization.md` on branch `cursor/pagespeed-before-report-bc61`.

## File map

```
shopify-theme/
├── assets/
│   ├── product-gallery-zoom.css
│   ├── product-gallery-lightbox.js
│   └── product-gallery-hover-zoom.js
└── snippets/
    ├── product-images.liquid          ← main change
    ├── theme-product-lcp-preload.liquid
    └── product-xr-deferred.liquid
```
