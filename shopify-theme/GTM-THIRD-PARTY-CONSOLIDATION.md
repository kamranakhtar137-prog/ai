# Homepage Third-Party Scripts — Live Inspection Findings & Fix Plan

**Update:** the original version of this doc assumed Crazy Egg, Microsoft
Clarity, ClickCease, and the Facebook Pixel were raw hardcoded `<script>`
tags. I inspected the live homepage (rendered HTML + full network waterfall,
mobile UA) to verify that before writing a plan, and the actual situation is
different — and more fixable than "add these to GTM."

## What's actually happening

**GTM is already active and already fires all four tools** — just not from
the snippet you'd expect. In `theme.liquid` the classic GTM loader
(`GTM-57VH77Q`) is commented out. But the site has the **Elevar**
app installed ("Elevar Conversion Tracking" — a Shopify app, App Embed
Block), which injects its own GTM loader for the **same container ID**
(`GTM-57VH77Q`, confirmed via Elevar's own `config.js`: `"allow_gtm": true`).
So GTM loads via Elevar's app block, not the theme file, and Crazy Egg,
Clarity, ClickCease, and the Facebook Pixel are already configured as **tags
inside that GTM-57VH77Q container** — they're not separate hand-pasted
scripts outside of it.

Captured network waterfall (mobile, cold load), all four fire within
~300ms of each other, immediately after `gtm.js` itself loads, with **no
delay/trigger strategy applied to any of them**:

| Time | Request | Compressed size |
| --- | --- | --- |
| — | `googletagmanager.com/gtm.js?id=GTM-57VH77Q` | — |
| +~0.2s | `connect.facebook.net/en_US/fbevents.js` | **~104KB** (matches your PageSpeed number exactly) |
| +~0.2s | `www.clarity.ms/tag/...` → `scripts.clarity.ms/.../clarity.js` | ~25KB |
| +~0.2s | `www.clickcease.com/monitor/stat.js` | ~44KB |
| +~0.2s | `script.crazyegg.com/pages/scripts/...js` (+ a second "commontransformations" file) | ~8KB + more |
| +~0.3s | `bat.bing.com/bat.js` (Bing/Microsoft Ads UET — also in GTM, not one you named but same issue) | ~15KB |

Also firing from the same container, all at once: Google Ads conversion
tracking (`AW-823233215`), GA4 (`G-SF2H4TEH22`), and a Google Ads
`doubleclick.net` collect ping.

**So the fix isn't "move these into GTM" — it's "GTM is already the right
place, but every tag in it fires on the same default trigger as soon as GTM
loads."** That's the actual bug.

## The real fix: delay the tags inside GTM (not more code)

This part happens in the GTM UI (tagmanager.google.com), which needs
container access I don't have. Steps for whoever has access:

1. Open the `GTM-57VH77Q` container → **Tags**.
2. For **Crazy Egg**, **Microsoft Clarity**, and **Facebook Pixel** tags:
   - Change the trigger from whatever "all pages" / initialization trigger
     it's currently using to a **Custom Event** trigger.
   - Create that trigger once: **Triggers → New → Custom Event** → Event
     name `delayedAnalytics`.
   - This event is already available to fire it from: `theme.liquid`
     already renders `{% render 'theme-phase2-defer-third-party' %}` near
     the end of `<body>`, which pushes a `delayedAnalytics` event to
     `dataLayer` on first scroll/click/touch/keydown, or after a ~4s idle
     fallback. **The plumbing exists in the theme already — GTM's tags just
     aren't wired to wait for it.** This is the single highest-leverage,
     lowest-risk fix available right now.
3. For **ClickCease**: leave it on a fast trigger (e.g. **Window Loaded**,
   not the same `delayedAnalytics` gate as the others). It needs to observe
   clicks quickly to flag invalid ones on paid Google/Meta traffic — gating
   it behind "wait for scroll or 4s idle" risks missing fast-bouncing
   fraudulent sessions.
4. Do the same for Bing UET, GA4, and Google Ads conversion tags if they
   don't need to fire before paint (they generally don't for a homepage
   view — only cart/checkout/purchase events need to be reliable, and
   those fire later in the session anyway, not on initial homepage load).
5. Use GTM **Preview** mode to confirm each retagged tag now fires on
   `delayedAnalytics` (or Window Loaded for ClickCease) and not on initial
   page load.

## Check for duplicate Facebook tracking before/after

The homepage also loads **four separate Shopify "Web Pixel" sandbox
workers** (Shopify's native Customer Events architecture, completely
independent of GTM):

```
web-pixel-shopify-app-pixel@0501/sandbox/worker.modern.js
web-pixel-1762001143@.../sandbox/worker.modern.js
web-pixel-1334739191@.../sandbox/worker.modern.js
web-pixel-921927927@.../sandbox/worker.modern.js
```

These run in a sandbox and I can't identify what each one tracks from
outside the site. **Check Shopify Admin → Settings → Customer events** to
see what's registered there. If any of those three numbered pixels is
*also* a Facebook/Meta pixel (separate from the one GTM fires via
`fbevents.js`), you're double-counting conversions in Meta Ads Manager
right now, independent of anything above — worth confirming either way.

## Two bigger findings from the same inspection (not on your original list)

These aren't Crazy Egg/Clarity/ClickCease/Facebook, but they showed up as
larger or more structurally significant during the same check, so flagging
them here:

### 1. Termly's "resource-blocker" script is the actual biggest blocking script on the page — bigger than the Facebook Pixel

```html
<script src="https://app.termly.io/resource-blocker/ecc1da61-dfcc-4242-a069-553ceaf9593e?autoBlock=on"></script>
```

This is the **very first line inside `<head>`** in `theme.liquid` — before
`<meta charset>`, before the viewport meta tag, before anything else. It has
no `defer`/`async`, so it's a classic parser-blocking script, and it's
**~152KB compressed / ~495KB uncompressed** (confirmed by direct request) —
larger than the Facebook Pixel. Every millisecond spent downloading and
executing it happens before the browser can even start discovering the rest
of `<head>` (including the LCP preload link, main stylesheet, etc.).

**Why I'm not just recommending `defer`/`async` here:** this script's job is
Termly's cookie-consent "auto-block" feature — it needs to run synchronously,
before the parser reaches later `<script>` tags, in order to intercept and
block those trackers until consent is given. Marking it `async` could let
the parser reach and start other tracker `<script>` tags before Termly has
finished loading and intercepting them, which would undermine the consent
gating it exists for. This needs to be validated with Termly's own
performance guidance (or their support) before changing — and/or evaluated
against switching to **Google Consent Mode v2** (configured in GTM/Termly),
which gates tag *firing* via a signal rather than blocking script tags
outright, and doesn't require a synchronous head-blocking script at all.
That's a bigger, longer-term change than a one-line edit, but worth raising
given its size.

### 2. A geo/country-blocking app is deliberately render-blocking on every page load

```html
<script src="https://cdn.shopify.com/extensions/.../geo-blocker-31/assets/runtime.js" fetchpriority="high" blocking="render" ...>
<script src="https://filmartgallery.com/tools/_?_t=page&_v=2" fetchpriority="high" blocking="render">
```

These carry the HTML `blocking="render"` attribute, which explicitly opts
into blocking first paint (this is intentional on the app's part, not a
theme bug) — almost certainly to prevent a flash of visible content in
countries the store wants to restrict before the check completes. This is
a real, measurable LCP cost, but it may be an accepted business trade-off
(compliance/legal restriction, not just marketing). Worth a conversation
with whoever owns that requirement about whether the current
render-blocking implementation is necessary, or whether the app has a
non-blocking mode.

## Also present, smaller/lower priority

Other third-party requests seen on the same load, roughly in order of
weight: Bugsnag error monitoring (`d2wy8f7a9ursnm.cloudfront.net`), Intuit
`pixel-reporting-sdk` (9 chained script files, small individually but worth
asking what installed this — not obviously related to Shopify), MailChimp
(`chimpstatic.com`), Instagram feed widget (`nfcube.com`), and Fast Simon
search autocomplete (expected — that's the site search app). None of these
were on your original list; flagging in case any are dead weight from a
long-removed integration.

## What I could not verify from outside

- The exact GTM tag/trigger names and current firing rules — needs
  tagmanager.google.com access.
- What the three unlabeled Web Pixel workers track — needs Shopify Admin →
  Settings → Customer events access.
- Whether Termly's autoBlock feature would still function correctly with
  `async` — needs testing (staging/preview) or Termly support confirmation.

If you can share GTM container access (read-only view is enough) or export
the current tag/trigger list, I can turn "delay these tags" into an exact
tag-by-tag change list rather than general guidance.
