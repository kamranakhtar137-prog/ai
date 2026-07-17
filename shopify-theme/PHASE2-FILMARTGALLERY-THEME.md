# Phase 2 — filmartgallery.com `theme.liquid` (exact edits)

Your score is still **54** because `theme.liquid` `<head>` loads too much **before** the product image paints — even after Phase 1 gallery fixes.

Scripts at bottom already use `defer` ✅. The problem is **`<head>` weight**.

---

## Fix 1 — Remove DUPLICATE LCP preload (do first)

You have **two** product preloads. Keep only `theme-product-lcp-preload`.

### REMOVE this entire block:

```liquid
{%- comment -%} LCP image preload: product page main image. imagesrcset matches what product-images.liquid renders at 400/800/1200w. {%- endcomment -%}
{%- if template contains 'product' and product.featured_media.preview_image -%}
  {%- assign lcp_img = product.featured_media.preview_image -%}
  <link rel="preload" as="image"
        href="{{ lcp_img | img_url: '800x' }}"
        imagesrcset="{{ lcp_img | img_url: '400x' }} 400w, {{ lcp_img | img_url: '800x' }} 800w, {{ lcp_img | img_url: '1200x' }} 1200w"
        imagesizes="100vw"
        fetchpriority="high">
{%- elsif template contains 'article' and article.image -%}
  ...
{%- endif -%}
```

### KEEP only (you already have this):

```liquid
{% if template.name == 'product' %}
  {% render 'theme-product-lcp-preload' %}
{% endif %}
```

**Optional:** Keep article preload only if you need it:

```liquid
{%- elsif template contains 'article' and article.image -%}
  <link rel="preload" as="image" href="{{ article.image | image_url: width: 800 }}" fetchpriority="high">
{%- endif -%}
```

---

## Fix 2 — Stop Fast Simon blocking `<head>` on product pages

### REMOVE from `<head>`:

```liquid
<script>
  {% render 'fast-simon-js' %}
</script>
```

### ADD before `</body>` (after jquery):

```liquid
{% unless template.name == 'product' %}
<script>
  {% render 'fast-simon-js' %}
</script>
{% endunless %}
<script>
(function(){
  if ({{ template.name == 'product' | json }}) {
    function loadFastSimon() {
      if (window.__fastSimonLoaded) return;
      window.__fastSimonLoaded = true;
      var s = document.createElement('script');
      s.textContent = {% render 'fast-simon-js' | json %};
      document.body.appendChild(s);
    }
    document.querySelectorAll('.search-form, [data-search], .header__search').forEach(function(el){
      el.addEventListener('focusin', loadFastSimon, { once: true });
      el.addEventListener('click', loadFastSimon, { once: true });
    });
    setTimeout(loadFastSimon, 8000);
  }
})();
</script>
```

**Simpler option** (if above breaks search): move the whole `fast-simon-js` block to **end of body** only — still helps vs head.

---

## Fix 3 — Move `fs-js` out of `<head>`

### REMOVE from `<head>`:

```liquid
{% render 'fs-js' %}
```

### ADD before `</body>``:

```liquid
{% unless template.name == 'product' %}
  {% render 'fs-js' %}
{% endunless %}
```

On product pages, load `fs-js` only when user opens search (same pattern as Fix 2).

---

## Fix 4 — Move heavy `__cvg_shopify_info` to bottom

### REMOVE from `<head>`:

```liquid
<script>
  window['__cvg_shopify_info'] = {
    product: {{ product | json }},
    variant: {{ product.selected_or_first_available_variant | json }},
    ...
  };
</script>
```

### ADD before `</body>` (product pages only):

```liquid
{% if template.name == 'product' %}
<script>
  window['__cvg_shopify_info'] = {
    product: {{ product | json }},
    variant: {{ product.selected_or_first_available_variant | json }},
    currency: {{ shop.currency | json }}
  };
</script>
{% endif %}
```

**Better:** Remove `collection.products` from head entirely — that dumps every product JSON on collection pages.

---

## Fix 5 — Remove jQuery preload from `<head>`

### REMOVE:

```liquid
{{ 'jquery.min.js' | asset_url | preload_tag: as: 'script' }}
```

jQuery already loads with `defer` at bottom. Preloading it competes with LCP image bandwidth.

---

## Fix 6 — Disable InstantClick on product pages

### WRAP existing InstantClick block:

```liquid
{% unless settings.performance == 'sport' or template contains 'customer' or template.name == 'product' %}
  ... instantclick scripts ...
{% endunless %}
```

InstantClick re-fetches scripts on navigation and hurts Lighthouse product lab score.

---

## Fix 7 — Move Termly sync script to bottom

Cut the large `<script>` block (Termly `syncTermlyToShopify` / polling) from `<head>`.

Paste it **before `</body>`** (after Termly loader script you already have at bottom).

Same code — just not in `<head>`.

---

## Fix 8 — Move canonical/modal script to bottom

Cut the `runCanonicalAndModal` `<script>` from `<head>` → paste before `</body>`.

---

## Fix 9 — Defer the huge inline `<style>` block

Your `<style>` block in `<head>` is **~8KB+** of CSS (iwish, footer, media queries). It blocks render.

**Best fix:**
1. Create asset `custom-head-overrides.css` in Assets
2. Paste the CSS from `<style>...</style>` into that file
3. Replace inline block with:

```liquid
<link rel="preload" href="{{ 'custom-head-overrides.css' | asset_url }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>{{ 'custom-head-overrides.css' | asset_url | stylesheet_tag }}</noscript>
```

**Quick fix (no new file):** move `<style>...</style>` to **end of `<body>`** — not ideal but improves FCP.

---

## Fix 10 — Add Phase 2 snippets before `</body>`

After `custom.min.js`, add:

```liquid
{% render 'theme-phase2-defer-third-party' %}
{% if template.name == 'product' %}
  {% render 'theme-phase2-lazy-sections' %}
{% endif %}
```

**Downloads:**
- [theme-phase2-defer-third-party.liquid](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-defer-third-party.liquid)
- [theme-phase2-lazy-sections.liquid](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-lazy-sections.liquid)

---

## Fix 11 — Hardcoded CSS URLs (minor)

These use `filmartgallery.com` domain — use relative or Shopify CDN if they break:

```liquid
<link rel="preload" href="https://filmartgallery.com/cdn/shopifycloud/portable-wallets/...
```

Change to:

```liquid
<link rel="preload" href="https://cdn.shopify.com/shopifycloud/portable-wallets/latest/accelerated-checkout-backwards-compat.css" as="style" onload="this.rel='stylesheet'">
```

---

## Priority order (do today)

| Priority | Fix | Impact |
|:---:|---|---|
| 1 | Remove duplicate LCP preload | High LCP |
| 2 | Fast Simon out of `<head>` | High TBT |
| 3 | `fs-js` out of `<head>` | Medium TBT |
| 4 | `__cvg_shopify_info` to bottom | High payload |
| 5 | Remove jquery preload | Medium LCP |
| 6 | InstantClick off on product | Medium TBT |
| 7 | Move Termly + modal scripts to bottom | Medium TBT |
| 8 | Inline `<style>` → external/async | High FCP |
| 9 | Phase 2 snippets | Medium |

---

## What you already did right ✅

- `vendors.js`, `app.js`, `jquery` → `defer` at bottom
- GTM script commented out in head
- Fancybox removed
- Owl carousel only on index/collection
- Termly loader at bottom with idle callback
- Main `styles.aio.min.css` preloaded async
- iWish + checkout CSS deferred

---

## Expected score after these edits

| Stage | Score |
|---|---|
| Now (Phase 1 only) | ~54 |
| Fixes 1–6 | ~65–75 |
| All fixes 1–10 | ~75–85 |
| + GTM delayed in Tag Manager + vendors audit | ~85–92 |
