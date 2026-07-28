# Product Page SEO Template (Shopify Horizon theme)

This module implements the Main Street Collectibles product-page SEO template as
reusable Shopify Liquid code, so the title tag, meta description, and H1 are
generated automatically for every product instead of being typed in by hand.

| Element | Template | Example length |
|---|---|---|
| URL | `/products/[handle]` | — |
| Title tag | `[Year] [Set] [Card Name] [Grade/Edition] \| Main Street Collectibles` | ~67 chars |
| Meta description | `Buy the [Card Name], graded [condition/grade] with [key attribute]. In stock at Main Street Collectibles, New Canaan CT. [Price if available].` | ~142 chars |
| H1 | `[Year] [Set] [Card Name] [Grade/Edition]` | — |

This repo doesn't contain a live theme, so this is delivered as a drop-in
snippet plus the exact edits to make in **Horizon** (Shopify's current
flagship theme). Horizon is architecturally different from older themes like
Dawn — product pages are built from JSON templates (`templates/product.json`)
made of theme blocks, rather than a single static `sections/main-product.liquid`
file — so the integration steps below are Horizon-specific.

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

In the Shopify Admin, go to **Online Store → Themes → your Horizon theme →
Edit code**, and add a new snippet file
[`snippets/collectibles-product-seo.liquid`](./snippets/collectibles-product-seo.liquid)
(copy its contents in). It's plain Liquid and works unchanged in Horizon —
it only reads the global `product` object and product metafields.

## 3. Wire it into the title tag & meta description

Horizon centralizes all `<title>`, Open Graph, and Twitter Card tags in
`snippets/meta-tags.liquid`, which builds everything off the `page_title` and
`page_description` globals. Open that file and add this block right before
the existing `{%- liquid ... %}` block near the top (the one that assigns
`og_title`/`og_description`):

```liquid
{%- if request.page_type == 'product' and product -%}
  {%- render 'collectibles-product-seo', product: product -%}
  {%- assign page_title = collectibles_seo_title -%}
  {%- assign page_description = collectibles_seo_description -%}
{%- endif -%}
```

Because `page_title`/`page_description` are what feed `og:title`, `og:description`,
`twitter:title`, `twitter:description`, `<title>`, and `<meta name="description">`
further down in the same file, this one insertion updates all of them
consistently for product pages, while every other page type (collections,
blog, home, etc.) keeps using Shopify's normal SEO fields untouched.

## 4. Wire it into the product H1

Horizon doesn't hardcode the product title in a Liquid file — it's a **theme
block** inside `templates/product.json` (by default a "Text" block bound to
`{{ closest.product.title }}`, styled with the `h3` type preset). That default
text block renders as a styled `<div>`, not a real `<h1>` element — this is a
known Horizon characteristic (see the [community thread](https://community.shopify.com/t/horizon-theme-h1-customization/588410/31)
that already flags this for the built-in title block).

To get a real semantic `<h1>` that also follows the SEO template, replace
that block with a **Custom Liquid** block:

1. In **Edit code**, open `templates/product.json`.
2. Find the block named `text_xpVMaM` (or whichever block has
   `"name": "t:names.title"` under the `product-details` → `header` group) —
   it looks like:

   ```json
   "text_xpVMaM": {
     "type": "text",
     "name": "t:names.title",
     "settings": {
       "text": " {{ closest.product.title }} ",
       "type_preset": "h3",
       ...
     },
     "blocks": {}
   }
   ```

3. Replace its `"type"` and `"settings"` so it renders a real `<h1>` via the
   snippet instead:

   ```json
   "text_xpVMaM": {
     "type": "custom-liquid",
     "name": "t:names.custom_liquid",
     "settings": {
       "custom_liquid": "{% render 'collectibles-product-seo', product: product %}<h1 class=\"h3\">{{ collectibles_seo_h1 }}</h1>"
     },
     "blocks": {}
   }
   ```

   The `class="h3"` keeps the same visual size Horizon already uses for the
   title (Horizon's CSS applies typographic scale via `.h1`–`.h6` classes
   independent of the actual HTML tag), while the element itself is now a
   genuine `<h1>`.

Alternatively, you can make the same edit visually: in **Customize**, select
the product template, delete/hide the default "Title" block, add a **Custom
Liquid** block in its place inside the same group, and paste the
`custom_liquid` value from step 3 into its settings panel.

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
  its "Search engine listing" fields in the Shopify Admin still works —
  `page_title`/`page_description` are only reassigned in step 3 for the
  product page type, so a manual per-product escape hatch can be added there
  if needed (e.g. skip the override when a `custom.seo_override` metafield
  is set).

## Using an older theme instead (e.g. Dawn)

If you're on a classic section-based theme instead of Horizon, the same
snippet still works, but wiring differs slightly:

- Title/meta description: add the same override block from step 3 into
  `layout/theme.liquid` (or wherever `<title>`/meta description are output),
  guarded by `{%- if template.name == 'product' and product -%}`.
- H1: in `sections/main-product.liquid`, replace
  `<h1 class="product__title">{{ product.title }}</h1>` with:

  ```liquid
  {%- render 'collectibles-product-seo', product: product -%}
  <h1 class="product__title">{{ collectibles_seo_h1 }}</h1>
  ```
