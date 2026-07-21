# In-Store Pickup Audit — Main Street Collectibles

| | |
| --- | --- |
| **Client / store** | Main Street Collectibles |
| **URL** | https://mainstreetnewcanaan.com/ |
| **Shopify domain** | `0ugbe0-t1.myshopify.com` |
| **Intended pickup location** | 102 Main Street, New Canaan, CT 06840 |
| **Audit date** | July 21, 2026 |
| **Method** | Public storefront APIs, live checkout session inspection, automated smoke test |
| **Related** | [Configuration guide](./in-store-pickup-configuration.md) |

---

## Executive summary

Main Street Collectibles is configured for **pickup-only checkout** (no shipping countries). **In-store pickup is not operational:** checkout exposes **zero pickup locations**, so customers cannot select **102 Main Street** to complete an order.

Sport and gift-card collections are largely **empty** on the storefront; only the **ONLINE STORE** collection (15 products) and **Consulting** / **TCG** (1 product each) have visible catalog depth. Inventory and location pickup settings in Shopify Admin must be completed before marketing “order online, pick up in store.”

**Overall readiness:** **Not ready for launch** (blocking: pickup location + inventory at location).

---

## Scope

- Checkout delivery and pickup location availability  
- Published collections vs. shoppable catalog for pickup  
- Store metadata and customer-facing address messaging  
- Smoke test: add-to-cart → checkout  

**Out of scope:** Shopify Admin settings not visible without staff access, theme code review, POS, tax, payment provider configuration beyond what checkout exposes.

---

## Findings

### Critical

| ID | Finding | Evidence | Risk |
| --- | --- | --- | --- |
| C-1 | **No pickup location at checkout** | Checkout session data: `pickupPoint: []`; smoke test `verify-pickup-checkout.py` exits with MISCONFIGURED | Customers cannot finish pickup orders; abandoned checkouts |
| C-2 | **Pickup-only checkout without pickup enabled** | `enabledDeliveryMethods: ["PICK_UP"]`; `ships_to_countries` empty in `/meta.json` | Only fulfillment path offered is broken |

### High

| ID | Finding | Evidence | Risk |
| --- | --- | --- | --- |
| H-1 | **Key collections have no products** | Storefront API `products_count`: Baseball 0, Basketball 0, WNBA 0, GIFT CARDS 0 | Browse paths do not match shoppable pickup catalog |
| H-2 | **Pickup requires location inventory** | Shopify pickup rules (not verifiable per-SKU without Admin); catalog has 319 published products, 163 available variants in first 250-product sample | Even after C-1 fix, SKUs with zero stock at 102 Main Street will fail at checkout |

### Medium

| ID | Finding | Evidence | Risk |
| --- | --- | --- | --- |
| M-1 | **Weak product taxonomy for collection automation** | Sample of 250 products: mostly empty `product_type`; dominant tag `MZ` (16) | Hard to maintain sport/category collections without tagging cleanup |
| M-2 | **“In store only” copy on some PDPs** | Example: ONLINE STORE product body “Available in store only” | Confusing if web checkout is promoted before pickup works |
| M-3 | **Checkout contact email** | Checkout embed: `Team@jtmmgm.com` | Customer service routing may not match Main Street branding |

### Low / informational

| ID | Finding | Evidence |
| --- | --- | --- |
| L-1 | Theme shows correct street address | Header link: 102 Main Street New Canaan CT 06840 |
| L-2 | Cart / add to cart functional | AJAX `cart/add.js` succeeds for test variant `46678279749822` ($60 WNBA Mega Box) |
| L-3 | Shop Pay installments enabled | `/meta.json` |

---

## Collection inventory (storefront API)

| Collection | Handle | Products | Notes |
| --- | --- | ---: | --- |
| ONLINE STORE | `online-store` | 15 | Primary candidate for pickup merchandising |
| Consulting | `consulting` | 1 | Selling consultation online — confirm pickup intent |
| Tcg CARD | `tcg-card` | 1 | Under-populated vs. store positioning |
| Baseball | `baseball` | 0 | Empty |
| Basketball | `basketball` | 0 | Empty |
| WNBA | `wnba` | 0 | Empty |
| GIFT CARDS | `gift-cards` | 0 | Empty |

**Store totals:** 7 published collections, **319** published products (`/meta.json`).

---

## Checkout smoke test results

**Command:** `python3 docs/main-street-collectibles/scripts/verify-pickup-checkout.py`

| Step | Result |
| --- | --- |
| Add test variant to cart | Pass |
| Load checkout | Pass (HTTP 200) |
| Delivery methods | `PICK_UP` only |
| Pickup locations | **Fail** — empty |
| **Automated verdict** | **MISCONFIGURED** |

---

## Recommendations (priority order)

1. **Admin — enable pickup at 102 Main Street**  
   Settings → Shipping and delivery → Pickup in store → activate location; set ready time and pickup instructions.

2. **Admin — location + inventory**  
   Settings → Locations: location fulfills online orders. Assign tracked inventory at 102 Main Street for all SKUs sold online.

3. **Re-run smoke test** until `verify-pickup-checkout.py` reports **PASS**.

4. **Collections** — tag products and use automated collection rules; expand **ONLINE STORE** first; populate Baseball / Basketball / WNBA / TCG.

5. **Optional** — add US shipping zone if ship + pickup is desired; update store contact email if needed.

6. **QA** — complete test order (Bogus Gateway or refund): ready for pickup → customer notification → picked up.

Detailed steps: [in-store-pickup-configuration.md](./in-store-pickup-configuration.md).

---

## Sign-off

| Role | Name | Date | Signature |
| --- | --- | --- | --- |
| Auditor | | July 21, 2026 | |
| Client acknowledgment | | | |
| Remediation owner | | | |

---

*This audit reflects the live storefront at the audit date. Re-audit after Admin changes.*
