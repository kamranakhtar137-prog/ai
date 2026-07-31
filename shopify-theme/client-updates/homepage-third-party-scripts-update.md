# Homepage Speed Update: Third-Party Scripts

**Site:** filmartgallery.com

## Headline finding

We now have a likely explanation for the 10–20 second speed spike: your
mobile test shows the page frozen (nothing appears on screen at all) for
**9.6 seconds**, then finishes loading 11 seconds after that. That pattern
points to one specific culprit — your country-restriction tool, which is
currently set up to hold the entire page from appearing until it finishes
checking the visitor's location. Under certain test conditions, that check
appears to take several seconds instead of being instant.

**This is the #1 priority — likely bigger than everything else combined.**

## Findings & Fixes

| # | Finding | Fix | Who | Effort |
|---|---|---|---|---|
| 0 | **Country-restriction tool holds the whole page from appearing while it checks the visitor's location — likely the main cause of the reported slowdown** | Test with it briefly disabled to confirm, then ask the vendor for a non-blocking check method | App vendor | ~10 min to confirm |
| 1 | Crazy Egg, Clarity, Facebook Pixel (104KB), + Google/Bing ad tags all fire at once, on page load | Delay these in Google Tag Manager until scroll/click/tap (or ~4s) | GTM account holder | ~15 min |
| 2 | ClickCease also fires at once | Keep it fast (fraud protection needs speed) — no change needed | — | — |
| 3 | 3 unidentified tracking pixels found outside GTM | Check Shopify Admin → Customer Events for a duplicate Facebook pixel | Shopify Admin | ~5 min |
| 4 | Cookie-consent script (Termly) is the single largest script on the page — bigger than the Facebook Pixel | Ask Termly if it can load without breaking consent enforcement, before we touch it | Termly support | Pending their reply |

## Bottom line

No images, design, or tracking tools are removed. This is purely about
*timing* — stopping one blocking check and background scripts from
competing with your content on first load. Item 0 is the fastest way to
confirm the biggest lever; item 1 is the biggest win that needs no
development work at all.
