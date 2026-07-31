# Homepage Speed Update: Third-Party Scripts

**Site:** filmartgallery.com

## Summary

Crazy Egg, Microsoft Clarity, ClickCease, and Facebook Pixel are already
running through Google Tag Manager — they're not scattered, uncontrolled
scripts. The problem: all of them fire the instant the page starts loading,
competing with your homepage content for bandwidth. Fixing this is a
settings change, not a code change.

## Findings & Fixes

| # | Finding | Fix | Who | Effort |
|---|---|---|---|---|
| 1 | Crazy Egg, Clarity, Facebook Pixel (104KB), + Google/Bing ad tags all fire at once, on page load | Delay these in Google Tag Manager until scroll/click/tap (or ~4s) | GTM account holder | ~15 min |
| 2 | ClickCease also fires at once | Keep it fast (fraud protection needs speed) — no change needed | — | — |
| 3 | 3 unidentified tracking pixels found outside GTM | Check Shopify Admin → Customer Events for a duplicate Facebook pixel | Shopify Admin | ~5 min |
| 4 | Cookie-consent script (Termly) is the single largest script on the page — bigger than the Facebook Pixel | Ask Termly if it can load without breaking consent enforcement, before we touch it | Termly support | Pending their reply |
| 5 | Country-restriction tool intentionally delays page render | Confirm with vendor if a faster mode exists | App vendor | Pending their reply |

## Bottom line

No images, design, or tracking tools are removed. This is purely about
*timing* — stopping background scripts from competing with your content on
first load. Item 1 is the biggest win and can be done today with no
development work.
