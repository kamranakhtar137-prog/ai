# WooCommerce Bed Configurator

A custom WooCommerce plugin that adds a **Build Your Own Bed** product configurator similar to [Happy Beds](https://www.happybeds.co.uk/build-your-own-bed). It includes layered live preview images, accordion option groups, headboard shape filters, drawer open/close toggle, dynamic pricing, and cart/order meta.

## Features

- Accordion option groups: **Size**, **Colour**, **Headboard**, **Base Depth**, **Storage**
- Layered product preview (shadow, legs, base, headboard, storage layers)
- Headboard **shape filter** + **style grid** (like the reference site)
- Real-time price updates via AJAX
- Selected options stored in cart and order line items
- Admin JSON editor for full configuration
- **Demo product** auto-created on plugin activation
- **32 demo PNG images** included in `demo-images/` for testing

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Installation

1. Download or copy the `woocommerce-bed-configurator` folder.
2. Zip it (see below) or upload the folder to `wp-content/plugins/`.
3. Activate **WooCommerce Bed Configurator** in WordPress admin.
4. Ensure WooCommerce is active.
5. Visit the demo product: **Build Your Own Bed (Demo)** (SKU: `wcbc-demo-bed`).

### Create ZIP for upload

```bash
cd /path/to/plugins
zip -r woocommerce-bed-configurator.zip woocommerce-bed-configurator \
  -x "*.git*" -x "*scripts/*"
```

## Usage

### Enable on any simple product

1. Edit a **Simple product** in WooCommerce.
2. Open the **Bed Configurator** tab.
3. Check **Enable Bed Configurator**.
4. Optionally set a base price and edit the JSON config.
5. Update the product.

The standard product gallery is hidden on configurator products; the layered preview replaces it.

### Demo images

Bundled test assets live in:

```
demo-images/
  layers/       # Composite bed preview layers
  swatches/
    size/
    colour/
    depth/
    storage/
    headboard/
```

Regenerate images with:

```bash
python3 scripts/generate-demo-images.py
```

Requires Pillow (`pip install pillow`).

## Configuration JSON schema

```json
{
  "base_price": 299.99,
  "layers": {
    "shadow": "https://example.com/shadow.png",
    "base": "https://example.com/base.png",
    "headboard": "https://example.com/headboard.png"
  },
  "defaults": {
    "size": "small-double",
    "colour": "beige-velvet",
    "headboard": "cornell-plain",
    "base_depth": "14-inch",
    "storage": "no-drawers"
  },
  "groups": [
    {
      "id": "size",
      "label": "Size",
      "icon": "size",
      "required": true,
      "options": [
        {
          "id": "small-double",
          "label": "Small Double",
          "sublabel": "4ft",
          "price": 40,
          "image": "/path/to/swatch.png",
          "layers": { "size": "4ft" },
          "badge": ""
        }
      ]
    }
  ]
}
```

### Headboard filters

Add `filter_type` and `filters` to a group, and set `layers.shape` on each option:

```json
{
  "id": "headboard",
  "filter_type": "shape",
  "filters": [
    { "id": "cornell", "label": "Cornell", "filter": "cornell" }
  ],
  "options": [
    {
      "id": "cornell-plain",
      "layers": { "headboard": "...png", "shape": "cornell" }
    }
  ]
}
```

## Cart & orders

Selections are saved as cart item meta and copied to order line items:

- `_wcbc_selections` — raw option IDs
- `_wcbc_labels` — human-readable labels
- `Bed configuration` — JSON summary for admins

Prices are calculated server-side on add-to-cart and in AJAX responses.

## File structure

```
woocommerce-bed-configurator/
├── woocommerce-bed-configurator.php
├── includes/
│   ├── class-wcbc-config.php
│   ├── class-wcbc-loader.php
│   ├── class-wcbc-admin.php
│   ├── class-wcbc-frontend.php
│   ├── class-wcbc-cart.php
│   ├── class-wcbc-ajax.php
│   └── class-wcbc-demo.php
├── templates/
│   └── configurator.php
├── assets/
│   ├── css/configurator.css
│   ├── js/configurator.js
│   └── icons/
├── demo-images/
└── scripts/generate-demo-images.py
```

## Customization

- Override template: copy `templates/configurator.php` to your theme at `woocommerce-bed-configurator/configurator.php`
- Styles: edit `assets/css/configurator.css` or add overrides in your theme
- Replace demo image URLs in product JSON with your own CDN/media URLs

## License

GPL-2.0-or-later
