# Phase 2 — Product Page 54 → 90+ Performance

Phase 1 fixed the **gallery**. Score still ~54 because Lighthouse is now penalizing:

| Bottleneck | Typical impact |
| --- | --- |
| `vendors.js` (~118KB, ~82KB unused) | TBT +400ms |
| Google Tag Manager + Analytics | TBT +280ms, ~130KB unused |
| Termly cookie banner | TBT +200ms |
| App scripts (Fast Simon, Mailchimp, iWish, Clarity, Facebook) | TBT + payload |
| Render-blocking CSS (theme + app extensions) | FCP/LCP delay |
| Below-fold sections (complementary products, recommendations) | Network + JS |

**Target after Phase 2:** Performance **80–92** on mobile lab (90+ needs most third-party delayed).

---

## Step 1 — Defer theme JavaScript in `layout/theme.liquid`

**Where:** Edit code → **Layout** → `theme.liquid`

**Find** script tags like:

```liquid
<script src="{{ 'vendors.js' | asset_url }}"></script>
<script src="{{ 'app.js' | asset_url }}"></script>
```

**Change to** (add `defer`):

```liquid
<script src="{{ 'vendors.js' | asset_url }}" defer></script>
<script src="{{ 'app.js' | asset_url }}" defer></script>
```

Add `defer` to **every theme script** except Shopify requires in `content_for_header`.

**Also:** Move all `<script src="...">` tags to **just before `</body>`** if any are in `<head>`.

| Download |
| --- |
| N/A — edit existing `theme.liquid` |

---

## Step 2 — Add third-party delay snippet

**Where:** Edit code → **Snippets** → Add snippet `theme-phase2-defer-third-party`

| Download |
| --- |
| [theme-phase2-defer-third-party.liquid](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-defer-third-party.liquid) |

**In `theme.liquid`**, add **before `</body>`** (after main content):

```liquid
{% render 'theme-phase2-defer-third-party' %}
```

**What it does:** Delays non-essential analytics/marketing scripts until first scroll, click, or touch (saves ~300–500ms TBT).

---

## Step 3 — Defer non-critical CSS

**Where:** Edit code → **Snippets** → Add snippet `theme-phase2-defer-styles`

| Download |
| --- |
| [theme-phase2-defer-styles.liquid](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-defer-styles.liquid) |

**In `theme.liquid` `<head>`**, add **after** your main stylesheet link:

```liquid
{% render 'theme-phase2-defer-styles' %}
```

**What it does:** Loads app extension CSS (iWish, checkout compat, etc.) without blocking first paint.

---

## Step 4 — Product-only: skip heavy homepage JS

**Where:** Edit code → **Layout** → `theme.liquid`

Wrap **homepage-only** scripts in a condition:

```liquid
{% unless template.name == 'product' %}
  <script src="{{ 'homepage-slider.js' | asset_url }}" defer></script>
{% endunless %}
```

Check your theme for scripts only used on index/collection — exclude them on product pages.

---

## Step 5 — Lazy-load complementary products

**Where:** Edit code → **Snippets** → `complementary-products.liquid` (if exists)

At the **top** of the file, wrap content:

```liquid
<div class="complementary-products-lazy" data-lazy-section="complementary">
  <!-- existing complementary products markup -->
</div>
```

**Where:** Edit code → **Snippets** → Add `theme-phase2-lazy-sections`

| Download |
| --- |
| [theme-phase2-lazy-sections.liquid](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-lazy-sections.liquid) |

**In `theme.liquid`** before `</body>`:

```liquid
{% if template.name == 'product' %}
  {% render 'theme-phase2-lazy-sections' %}
{% endif %}
```

---

## Step 6 — App embeds (Shopify Admin, not code)

**Where:** Online Store → **Themes** → **Customize** → **Theme settings** → **App embeds**

| App | Action |
| --- | --- |
| Fast Simon search | Disable on product pages OR defer in app settings |
| Mailchimp | Disable or defer |
| Microsoft Clarity | Disable or load after consent |
| Facebook Pixel | Move to GTM with delay trigger |
| iWish wishlist | Keep but CSS deferred in Step 3 |
| Termly | Use async mode in Termly dashboard |

**Where:** **Settings → Customer events / Google & YouTube / Facebook** — avoid duplicate tracking (GTM + direct install).

---

## Step 7 — GTM: delay tags (GTM dashboard)

**Where:** [tagmanager.google.com](https://tagmanager.google.com)

For each tag (GA4, Facebook, Clarity, etc.):

1. Open tag → **Advanced Settings** → **Tag firing priority**
2. Add trigger: **Page View – Delayed**
   - Condition: `Page Path` contains `/products/`
   - Or use custom event `delayedAnalytics` (fired by Step 2 snippet)

Or create trigger **"First scroll OR 4 seconds"** for product pages only.

---

## Step 8 — Reduce `vendors.js` (biggest theme file)

**Where:** Edit code → **Assets** → `vendors.js`

This file is ~118KB with ~82KB unused on product pages. Options:

1. **Theme support** — ask theme developer for a slim product-page bundle
2. **Audit** — search `vendors.js` for: `fancybox`, `masonry`, `instagram`, `plyr` — if unused on product page, split bundle
3. **Quick win** — ensure `defer` is set (Step 1)

If you use a build process, create `vendors-product.js` with only: jQuery (if needed), gallery/slider, variant picker.

---

## Step 9 — Preconnect only what matters

**In `theme.liquid` `<head>`**, keep only:

```liquid
<link rel="preconnect" href="https://cdn.shopify.com" crossorigin>
<link rel="preconnect" href="https://fonts.shopifycdn.com" crossorigin>
```

**Remove** unused preconnects to Google Analytics, Facebook, Clarity (they load later now).

---

## Step 10 — Test again

1. Hard refresh product page in incognito
2. Run PageSpeed on product URL (mobile)
3. Check Lighthouse **Diagnostics** tab for remaining top offenders

| Metric | Target |
| --- | --- |
| Performance | 85–92+ |
| LCP | < 2.5s |
| TBT | < 200ms |
| CLS | < 0.1 |

---

## Phase 2 file checklist

| Step | File | Action | Download |
|:---:|---|---|---|
| 1 | `layout/theme.liquid` | Add `defer` to JS | — |
| 2 | `snippets/theme-phase2-defer-third-party.liquid` | Create + render | [raw](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-defer-third-party.liquid) |
| 3 | `snippets/theme-phase2-defer-styles.liquid` | Create + render | [raw](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-defer-styles.liquid) |
| 5 | `snippets/theme-phase2-lazy-sections.liquid` | Create + render on product | [raw](https://raw.githubusercontent.com/kamranakhtar137-prog/ai/cursor/product-page-pagespeed-90-fcfb/shopify-theme/snippets/theme-phase2-lazy-sections.liquid) |
| 6 | App embeds | Disable/defer in admin | — |
| 7 | GTM | Delay tags | — |
| 8 | `assets/vendors.js` | Audit/defer | — |

---

## Why Phase 1 alone = 54

Phase 1 fixed **your gallery code**. The remaining score is mostly **Shopify apps + analytics + vendors.js** — not the product snippet. Phase 2 targets those.

**Realistic expectation:**
- Steps 1–3 + 6–7 alone → often **70–80**
- All steps + vendors audit → **85–92**
