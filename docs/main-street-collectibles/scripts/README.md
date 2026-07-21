# Main Street Collectibles — pickup scripts

## `verify-pickup-checkout.py`

Read-only smoke test against the live storefront:

- Adds a known in-stock variant to the cart (default: WNBA Mega Box from the ONLINE STORE collection).
- Opens checkout and inspects embedded configuration for delivery methods and pickup points.

```bash
python3 docs/main-street-collectibles/scripts/verify-pickup-checkout.py
```

Optional environment variables:

| Variable | Default | Purpose |
| --- | --- | --- |
| `MSC_STORE_URL` | `https://mainstreetnewcanaan.com` | Storefront base URL |
| `MSC_TEST_VARIANT_ID` | `46678279749822` | Variant to add for checkout probe |

Exit code `0` when pickup locations appear present; `1` when checkout is misconfigured (e.g. pickup-only with zero locations).

## `enable-local-pickup.graphql`

GraphQL Admin API mutation template. Run in **Shopify Admin → Settings → Apps and sales channels → Develop apps** (or your existing custom app) with scopes for shipping / delivery settings.

Set:

- `SHOPIFY_STORE=0ugbe0-t1.myshopify.com`
- `SHOPIFY_ADMIN_ACCESS_TOKEN=shpat_...`
- Replace `LOCATION_ID` in the variables file with your 102 Main Street location GID.

Example with Shopify CLI (if installed):

```bash
shopify app execute graphql --query @enable-local-pickup.graphql --variables @enable-local-pickup.variables.json
```
