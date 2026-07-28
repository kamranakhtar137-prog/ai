# Product Page SEO Template (Shopify)

This module implements the Main Street Collectibles product-page SEO template as
reusable Shopify Liquid code, so the title tag, meta description, and H1 are
generated automatically for every product instead of being typed in by hand.

| Element | Template | Example length |
|---|---|---|
| URL | `/products/[handle]` | — |
| Title tag | `[Year] [Set] [Card Name] [Grade/Edition] \| Main Street Collectibles` | ~67 chars |
| Meta description | `Buy the [Card Name], graded [condition/grade] with [key attribute]. In stock at Main Street Collectibles, New Canaan CT. [Price if available].` | ~142 chars |
| H1 | `[Year] [Set] [Card Name] [Grade/Edition]` | — |

This repo doesn't currently contain a Shopify theme, so this is delivered as a
drop-in snippet plus the exact edits to make in your theme (Dawn or any
Liquid-based theme uses the same structure).

## 1. Add the metafield definitions

In the Shopify Admin, go to **Settings → Custom data → Products → Add definition**
and create these (namespace `custom`, all "Single line text"):

| Key | Purpose | Required |
|---|---|---|
| `custom.year` | Card year, e.g. `2003` | Optional |
| `custom.set` | Card set/brand, e.g. `Topps Chrome` | Optional |
| `custom.grade` | Grade/Edition, e.g. `PSA 10`, `Raw / Ungraded` | Optional |
| `custom.condition` | Condition if different from grade | Optional (falls back to `custom.grade`) |
| `custom.key_attribute` | Standout feature, e.g. `Rookie Card`, `Autographed`, `Serial-Numbered /99` | Optional |

`[Card Name]` is always pulled from the product title, so no extra metafield is
needed for it. Any field left blank is simply omitted from the generated text
(no dangling separators or "graded ." artifacts).

You can bulk-fill these via **Products → Export/Import** (metafields are
included as columns in the CSV) once the definitions exist.

## 2. Add the snippet

Copy [`snippets/collectibles-product-seo.liquid`](./snippets/collectibles-product-seo.liquid)
into your theme's `snippets/` directory (via the Shopify Theme Editor's code
editor, or `shopify theme push` with the Shopify CLI).

## 3. Wire it into `layout/theme.liquid`

Find the existing `<title>` and meta description tags near the top of
`layout/theme.liquid` (in Dawn, this is inside `snippets/meta-tags.liquid`,
included from `theme.liquid`) and override them for the product template:

```liquid
{%- if template.name == 'product' and product -%}
  {%- render 'collectibles-product-seo', product: product -%}
  <title>{{ collectibles_seo_title }}</title>
  <meta name="description" content="{{ collectibles_seo_description | escape }}">
{%- else -%}
  <title>{{ page_title }}</title>
  {%- if page_description -%}
    <meta name="description" content="{{ page_description | escape }}">
  {%- endif -%}
{%- endif -%}
```

This leaves every other page type (collections, blog, home, etc.) using
Shopify's normal SEO fields, and only product pages use the generated
template.

## 4. Wire it into the product H1

In `sections/main-product.liquid` (or wherever the product title is rendered,
typically `<h1 class="product__title">{{ product.title }}</h1>`), replace the
title output with:

```liquid
{%- render 'collectibles-product-seo', product: product -%}
<h1 class="product__title">{{ collectibles_seo_h1 }}</h1>
```

Keep whatever class/heading tag your theme already uses — only the text
content needs to change.

## Notes on behavior

- **Availability**: rather than hard-coding "In stock", the meta description
  says "In stock at Main Street Collectibles, New Canaan CT." when
  `product.available` is true, and "Currently unavailable..." otherwise, so
  search engines never see a stale "in stock" claim for sold-out cards.
- **Price**: only appended when the product has a single fixed price
  (`product.price_varies == false`); for products with variant price ranges,
  the price is omitted rather than showing a potentially misleading single
  number.
- **Truncation**: the title is capped at 70 characters and the description at
  160 characters, matching standard search-engine display limits, in case a
  long card name/set pushes the generated string past the ideal length.
- **Manual overrides**: if a specific product needs custom SEO copy, editing
  its "Search engine listing" fields in the Shopify Admin still works — just
  wrap the override logic above with a check like
  `{%- if product.metafields.custom.seo_override -%}` if you want a manual
  escape hatch per product.
