# Homepage Third-Party Scripts — Live Inspection Findings & Fix Plan

**Update:** the original version of this doc assumed Crazy Egg, Microsoft
Clarity, ClickCease, and the Facebook Pixel were raw hardcoded `<script>`
tags. I inspected the live homepage (rendered HTML + full network waterfall,
mobile UA) to verify that before writing a plan, and the actual situation is
different — and more fixable than "add these to GTM."

**Second update:** pulled the current PageSpeed Insights real-user (CrUX)
field data directly. Mobile is currently **passing** Core Web Vitals — LCP
1.6s, INP 146ms, CLS 0.03 — a big contrast with the "10.9s" figure
originally reported. See the bot-blocking finding below (§1b) for a
plausible explanation of that gap: our own automated Lighthouse run got
blocked with HTTP 429 by the site's bot protection, which could also be
happening intermittently to Google's own lab test runner and inflating
isolated lab scores without reflecting what most real visitors experience.

**Third update — likely root cause of the lab-only spike identified.** The
actual Lighthouse lab numbers for mobile were shared:

| Metric | Value |
| --- | --- |
| First Contentful Paint | **9.6s** |
| Speed Index | **9.6s** |
| Largest Contentful Paint | **20.6s** |
| Total Blocking Time | 220ms |
| Cumulative Layout Shift | 0 |

FCP and Speed Index being identical at 9.6s means **nothing paints on the
page at all for 9.6 seconds**, then LCP finishes 11 seconds after that. That
"frozen, then everything happens at once" signature is the classic shape of
a single blocking network request hanging, not a gradual slowdown from too
many scripts or images (those would show a more gradual FCP/LCP gap, not an
identical FCP/Speed Index freeze).

This points directly at **§1b/§2 below**: the geo-blocking app's
`/tools/_?_t=page` request carries `blocking="render"` — the browser is
explicitly told not to paint anything until it resolves. Under Lighthouse's
test conditions (a fresh, uncached, automated session — the same profile
that got our own Lighthouse run blocked with a 429), this check likely
takes several seconds to resolve (bot/IP verification, retries, or a
challenge) instead of the near-instant response a normal cached visitor
gets. That would produce exactly this pattern: paint frozen for ~9.6s while
the check resolves, then the already-slow carousel/script loading (all the
findings elsewhere in this doc) stacks on top to push LCP out to 20.6s.

This is a **hypothesis backed by strong circumstantial evidence, not yet
directly confirmed** — the real-user field data (1.6s LCP) suggests most
visitors don't hit this multi-second freeze, only certain sessions/test
conditions do. **Fastest way to confirm it:** temporarily disable the
geo-blocking app (or ask its vendor for a way to turn off `blocking="render"`
specifically) on a staging/preview, then re-run the PageSpeed Insights
mobile test. If FCP drops from 9.6s to ~1–2s, this is confirmed as the
primary cause — bigger than every other finding in this document combined,
and should be prioritized above the GTM tag delays and image lazy-loading
work.

## Solution — do these in order

### 0. Confirm and fix the render-blocking geo-check freeze (do this first — likely the single biggest win available)

**Who:** whoever manages the geo-blocking/country-restriction app, with us
or Termly/EggFlow support as needed.
**Time:** ~10 minutes to test, more to fix depending on the app's options.

1. On a staging/preview environment (or briefly on production during low
   traffic, if staging isn't available), disable the geo-blocking app or
   its render-blocking mode.
2. Re-run PageSpeed Insights (mobile) on the same URL.
3. If FCP/Speed Index drop from ~9.6s to ~1–2s, this confirms the
   render-blocking geo-check is the primary cause of the reported LCP
   spike — re-enable the app and work with its vendor on a non-blocking
   verification method (most geo-restriction apps have one; `blocking="render"`
   is an aggressive, non-default choice), or ask Shopify support whether
   **Settings → Markets** can enforce the same restriction natively without
   a render-blocking script.
4. If FCP doesn't improve, the cause is elsewhere and the GTM/image fixes
   below remain the priority.

No further theme code change is required for #1 (the plumbing already
exists live); the highest-impact fix is a GTM configuration change.

### 1. Delay 6 of the 7 tags already in GTM-57VH77Q (highest impact, lowest risk, no code)

**Who:** whoever has tagmanager.google.com access to this container.
**Time:** ~15 minutes.

In the `GTM-57VH77Q` container, open each tag below and change its trigger
to a **Custom Event** trigger with event name **`delayedAnalytics`**
(create this trigger once under Triggers → New → Custom Event — the event
itself is already being pushed to `dataLayer` live by the theme's existing
`theme-phase2-defer-third-party` snippet on first scroll/click/touch, or
after ~4s idle):

| Tag to find in GTM | Identifier to match it by | New trigger |
| --- | --- | --- |
| Facebook Pixel | Pixel ID `814773616537076` | Custom Event: `delayedAnalytics` |
| Microsoft Clarity | Project ID `r5hv5em0xa` | Custom Event: `delayedAnalytics` |
| Crazy Egg | account/script `0094/8571` | Custom Event: `delayedAnalytics` |
| Google Ads conversion | `AW-823233215` | Custom Event: `delayedAnalytics` |
| GA4 config | `G-SF2H4TEH22` | Custom Event: `delayedAnalytics` |
| Bing/Microsoft UET | action id `136001197` | Custom Event: `delayedAnalytics` |
| **ClickCease** | `clickcease.com/monitor/stat.js` | **Window Loaded** (not `delayedAnalytics` — needs to stay fast for fraud detection) |

After retagging, use GTM **Preview** mode on the homepage: confirm none of
the first six fire until you scroll/click/tap (or ~4s idle), and ClickCease
still fires right after window load. Then **Publish** the container version.

**Expected result:** ~250KB (Facebook Pixel + Clarity + Crazy Egg + Bing UET
combined, compressed) moves off the critical path on every homepage load,
with zero code deployment.

### 2. Rule out duplicate Facebook tracking (5 minutes, no risk)

**Who:** whoever has Shopify Admin access.

Go to **Admin → Settings → Customer events**. The homepage loads 4
independent Shopify "Web Pixel" sandbox workers outside of GTM — one is
labeled as a native Shopify app pixel, three are unlabeled numeric IDs I
can't identify from outside the site. Open each one listed there; if any is
a second Facebook/Meta pixel, remove it (or remove the GTM one) so Meta Ads
Manager isn't double-counting.

### 3. Get a professional opinion on the Termly resource-blocker before touching it (do not just add `async`)

**Who:** Termly support, or whoever manages the Termly account, before any
code change.

This script is ~152KB compressed / ~495KB uncompressed, sits as the literal
first line in `<head>` (before `<meta charset>`), and has no `defer`/
`async` — it is the single largest blocking script on the page, larger than
the Facebook Pixel. But it's also what enforces cookie-consent blocking, so
I'm not proposing a blind code edit here. Ask Termly support: *"Does the
resource-blocker script support `async` loading without breaking
`autoBlock`, given our trackers load via a separately-injected GTM
container (Elevar), not inline `type=text/plain` script tags?"* If yes,
adding `async` is a one-line change I can make immediately. If no, the
longer-term fix is migrating to **Google Consent Mode v2** (supported by
both Termly and GTM), which gates tag firing via a signal instead of
blocking script tags outright — no head-blocking script needed at all. That
migration is a larger scope, flag it as a follow-up rather than doing it
today.

### 4. Ask whether the geo-blocking app can run without `blocking="render"` (business decision)

**Who:** whoever owns the country-restriction requirement.

Two requests from this app explicitly opt into blocking first paint. That
may be a deliberate, accepted trade-off (compliance/legal), but it's worth
confirming: ask the app vendor if a non-blocking check mode exists, or
evaluate whether Shopify's native **Settings → Markets** country
restrictions could cover the same requirement without a render-blocking
third-party script at all.

---

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

### 1b. That same geo-blocking layer also appears to rate-limit automated testing tools (possible cause of volatile/spiked lab scores)

While independently trying to verify the homepage's mobile performance with
our own Lighthouse run (same tool PageSpeed Insights uses), every automated
browser-driven request to `https://filmartgallery.com/` was rejected with
**HTTP 429**, while a plain `curl` request (no JS/browser automation) to the
same URL at the same time succeeded normally. This was reproducible across
multiple attempts and cooldown periods.

This suggests the site's bot/geo-blocking layer (Cloudflare + the
geo-blocker app, see below) may be flagging automated performance-testing
tools (Lighthouse/PageSpeed Insights itself, and possibly some real Google
crawler or slow-network sessions) as bot traffic and blocking them. If
Google's own PageSpeed Insights lab runner occasionally gets challenged or
blocked the same way, that would produce an artificially inflated LCP for
that specific test run — a plausible explanation for a lab-reported spike
like "LCP 10.9s" that doesn't match the (good) real-user field data below.

**Recommended next step:** check Cloudflare's firewall/bot-management event
log (and the geo-blocking app's logs) for 429s served to Google's
Lighthouse/PageSpeed Insights user agent or IP ranges, and consider
allowlisting Google's testing infrastructure so lab scores reflect the real
page, not a bot-challenge response.

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
