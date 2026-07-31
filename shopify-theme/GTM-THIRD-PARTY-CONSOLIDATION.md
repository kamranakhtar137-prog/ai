# Consolidate Homepage Third-Party Scripts into GTM

You counted ~15 separate scripts loading on the homepage, including Crazy
Egg, Microsoft Clarity, ClickCease, and a 104KB Facebook Pixel — none
installed as Shopify apps (so they're hand-pasted `<script>` tags in
`theme.liquid`, most likely in `<head>`). This is a real, high-value fix,
and it's the same class of problem already flagged for the product page in
`PHASE2.md` ("GTM + Analytics" → TBT +280ms) — the homepage almost certainly
has the same or worse issue since it also carries the carousel weight.

## Important expectation to set correctly

**Moving scripts into GTM does not shrink them.** The Facebook Pixel is
still ~104KB whether it's pasted directly or fired from GTM. What actually
improves mobile performance is:

1. **One script tag instead of many.** Every hardcoded `<script src="...">`
   pointing at a different third-party domain costs its own DNS lookup +
   TCP + TLS handshake before a single byte downloads. Consolidating to one
   `gtm.js` (already warm/cached across millions of sites) removes most of
   that connection overhead.
2. **Control over *when* each tag fires.** GTM lets you trigger each tag
   independently — most of these don't need to run before the page has
   painted anything. Delaying them off the critical path is what actually
   moves LCP/TBT, not the byte count going down.

So: this is a **timing and connection-count fix**, not a payload-size fix.
Frame it that way with the client so the win (LCP/TBT) is understood
correctly, separate from the image work.

## Per-script recommendation

| Script | Move to GTM? | Trigger | Why |
| --- | --- | --- | --- |
| **Crazy Egg** (heatmaps/recordings) | Yes | Delayed (`delayedAnalytics` custom event, or "Window Loaded") | Doesn't need to run before first paint — it's recording behavior, not blocking anything user-facing. |
| **Microsoft Clarity** (heatmaps/recordings) | Yes | Delayed (`delayedAnalytics` custom event, or "Window Loaded") | Same as Crazy Egg. Also has an official template in the GTM Community Template Gallery. |
| **Facebook Pixel** (104KB) | Yes | Delayed (`delayedAnalytics` custom event) — **but see duplicate-tracking check below first** | Biggest single win by weight. Official "Facebook Pixel" GTM template exists, or wrap the existing pixel snippet in a Custom HTML tag. |
| **ClickCease** (click-fraud protection) | Yes, but **don't use the same long delay** | "Window Loaded" (fires as soon as the page has finished loading, not gated on scroll/click/4s idle) | ClickCease needs to observe clicks quickly to flag invalid ones on paid Google/Meta traffic. Gating it behind the same "wait for scroll or 4s" trigger as the heatmap tools risks missing fast-bouncing fraudulent sessions. Still centralize it in GTM for management, just give it an earlier trigger than the others. |
| The other ~11 scripts | Audit individually | — | Anything else non-Shopify-checkout-critical (chat widgets, reviews widgets, upsell popups, etc.) should go through the same delayed-trigger treatment as Crazy Egg/Clarity unless it has a similar "must react fast" requirement like ClickCease. |

## Before you touch code: check for duplicate tracking

Before moving the Facebook Pixel, check **Shopify Admin → Settings →
Customer events** (and **Settings → Facebook & Instagram** if that sales
channel is installed). If a Facebook/Meta pixel is *also* registered there,
you already have it firing through Shopify's own pixel manager — adding a
second one via GTM would double-count purchases/add-to-carts in Meta Ads
Manager. Pick one source of truth (GTM, since you're consolidating) and
remove the other.

## Implementation

### 1. Confirm/re-enable GTM itself

`PHASE2-FILMARTGALLERY-THEME.md` notes the GTM script was previously found
**commented out** in `<head>`. Confirm whether that's still the case and
whether you have an active GTM container ID before doing anything else —
everything below assumes GTM is actually loading.

`snippets/theme-gtm-loader.liquid` (this folder) is the standard async GTM
boot snippet — drop your real container ID in and render it near the top of
`<head>` in `theme.liquid`. Add the matching `<noscript>` iframe fallback
right after the opening `<body>` tag (Google's standard install — keep it
even though most visitors have JS enabled).

### 2. Remove the hardcoded script tags

In `theme.liquid` (and anywhere else they're pasted — checkout `.liquid`
files, `layout/password.liquid`, etc.), delete the raw `<script>` tags for
Crazy Egg, Clarity, ClickCease, and Facebook Pixel. If you want to keep a
manual audit trail, comment them out for one deploy cycle instead of
deleting outright, then remove for good once GTM is confirmed firing all
four in GA4/Meta Events Manager/Clarity dashboard/ClickCease dashboard.

### 3. Add the delay trigger

Render `snippets/theme-defer-third-party.liquid` (this folder) right before
`</body>` in `theme.liquid`. It pushes a `delayedAnalytics` event to
`dataLayer` on first scroll/click/touch/keydown, or after ~4s of idle time
if the visitor never interacts — so tags wired to it still fire for
non-interacting visitors, just not on the critical rendering path.

### 4. Build the tags in GTM (tagmanager.google.com — no code)

For **Crazy Egg**, **Microsoft Clarity**, and **Facebook Pixel**:

1. **Tags → New** → paste the existing script as a Custom HTML tag (or use
   the official template for Clarity/Facebook Pixel from the Template
   Gallery).
2. **Triggering** → **New** → Trigger type: **Custom Event** → Event name:
   `delayedAnalytics`.
3. Save, then use **Preview** mode to confirm the tag fires after
   scroll/click (or after ~4s idle) and not on initial load.

For **ClickCease**:

1. Same Custom HTML tag setup.
2. **Triggering** → **Window Loaded** (built-in trigger — fires once the
   full page, including images, has loaded; still off the LCP-critical
   path, but doesn't wait for user interaction like the others).

### 5. Clean up preconnects

If `theme.liquid` has `<link rel="preconnect">` hints pointing at Crazy Egg,
Clarity, ClickCease, or Facebook domains, remove them — those connections
now happen later (after the delay trigger), so preconnecting at page load
just wastes a connection that may go unused for several seconds.

## Verify

1. GTM **Preview** mode: confirm each of the four tags fires, and on the
   trigger you expect (delayed vs. window-loaded).
2. Network tab: the four separate script domains should no longer appear in
   the first ~2–3s of the waterfall; `gtm.js` should be the only new
   third-party connection early on.
3. Confirm each tool is still receiving data in its own dashboard (Meta
   Events Manager test events, Clarity live sessions, Crazy Egg recordings,
   ClickCease dashboard) after the change.
4. Re-run PageSpeed Insights (mobile) on the homepage — expect a TBT and
   FCP/LCP improvement on top of the carousel fix, since fewer render/
   parser-blocking script tags compete for the main thread and bandwidth
   during initial load.

## What I need from you to produce an exact code diff

I don't have access to your live `theme.liquid` or its current `<head>`/
`</body>` script blocks in this repository. To turn this plan into an exact
before/after diff (rather than general guidance), share:

- The current GTM container ID (or confirmation there isn't an active one).
- The current hardcoded `<script>` blocks for Crazy Egg, Clarity,
  ClickCease, and Facebook Pixel (and ideally the other ~11 scripts) as they
  appear in `theme.liquid` today.

With those I can produce the same kind of "remove this / add this" fix list
as `PHASE2-FILMARTGALLERY-THEME.md`, specific to the homepage.
