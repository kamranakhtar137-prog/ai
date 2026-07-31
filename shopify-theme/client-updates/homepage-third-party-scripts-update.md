# Homepage Speed Update: Third-Party Tracking Scripts

**Prepared for:** Main Street Collectibles / Film/Art Gallery
**Site:** filmartgallery.com
**Subject:** Findings and recommended fixes for third-party scripts slowing the homepage

---

## Summary

You asked whether Crazy Egg, Microsoft Clarity, ClickCease, and the
Facebook Pixel (104KB) could be consolidated into Google Tag Manager (GTM)
to speed up the homepage, since none of them appear as installed Shopify
apps.

We checked the live homepage directly (not just the theme code) to confirm
exactly what's happening. Here's the short version:

- **Good news: these four tools are already running through Google Tag
  Manager.** They aren't separate, disorganized scripts — they're already
  centralized in one GTM container.
- **The actual problem: none of them are told to wait.** All four (plus a
  few more tools riding along in the same container) fire within a fraction
  of a second of each other, the instant the page starts loading — at
  exactly the moment your homepage image is trying to load and paint. They
  are actively competing with your content for the visitor's connection.
- **This is fixable without touching your website's code.** It's a
  configuration change inside Google Tag Manager's dashboard — tell each
  tool to wait until the visitor scrolls, taps, or after a few seconds,
  instead of firing instantly.
- Along the way, we also found two additional things worth your attention
  that are separate from your original question — one of them is currently
  the single largest script slowing down your homepage, bigger than the
  Facebook Pixel.

Nothing described in this update changes what your visitors see, and no
image, layout, or design quality is affected. This is entirely about *when*
background tracking scripts are allowed to load, not removing any of them.

---

## What we found

### 1. Crazy Egg, Microsoft Clarity, ClickCease, and Facebook Pixel

These are already inside your Google Tag Manager container. The real issue
is timing: all four load at essentially the same instant as the page
starts, with no delay applied. The Facebook Pixel alone confirmed at
**104KB** — matching the number you saw in your speed report exactly.

**Recommended fix:** Configure Google Tag Manager so that:
- Crazy Egg, Microsoft Clarity, and the Facebook Pixel wait until the
  visitor interacts with the page (scrolls, taps, or clicks), or a few
  seconds have passed — whichever comes first. These tools don't need to
  run before the page has even finished appearing; delaying them a few
  seconds has no effect on the data they collect.
- ClickCease keeps loading quickly, right after the page finishes loading —
  it's a fraud-detection tool for your ad spend, and it needs to react
  fast, so we recommend *not* delaying it the same way.

This is a settings change inside your Tag Manager account — no code
deployment needed, and it can typically be done and verified within the
same day.

### 2. A possible duplicate tracking issue (worth a quick check)

Separately from Tag Manager, your homepage also loads four independent
tracking "pixels" through Shopify's own built-in tracking system. One is
clearly a standard Shopify pixel; the other three aren't identified from
outside the site. **We recommend a quick look at Shopify Admin → Settings →
Customer events** to confirm none of those three is a second Facebook
pixel — if it is, you may currently be double-counting sales/conversions in
Meta Ads Manager, which would make your ad performance numbers look
different than they actually are.

### 3. Your cookie-consent script is currently your single biggest homepage script — bigger than the Facebook Pixel

This wasn't part of your original question, but it showed up as the
largest individual contributor to slow loading during our check. Your
cookie-consent tool (Termly) loads a script that is **larger than the
Facebook Pixel** and is positioned to load before literally anything else
on the page, without any of the "wait a moment" treatment recommended
above.

We are **not** recommending an immediate change here, because this
particular script's job is to enforce cookie-consent rules — changing how
it loads without confirming it's safe first could risk your compliance
setup. **Recommended next step:** ask Termly's support team whether their
script can be loaded in a less disruptive way without breaking its
consent-enforcement feature. If they confirm it's safe, this is a very
quick fix and one of the biggest single wins available on the page.

### 4. A country-restriction tool is intentionally holding up the page

Your site currently uses a tool that blocks the page from displaying until
it verifies a visitor's country — this is very likely an intentional
setup (a legal/compliance requirement for who can purchase from you), and
we want to flag that as a known, deliberate trade-off rather than a
mistake. **Recommended next step:** a quick conversation with whoever set
this up (or the app's support team) to confirm whether it can run in a
faster, less disruptive way while still meeting the same requirement.

---

## What this means for your performance goals

None of the fixes above require changing your design, your images, or
removing any tracking tool you currently rely on for marketing or fraud
protection. They're entirely about **timing** — making sure background
tools don't compete with your actual homepage content for the visitor's
first few seconds on a slow mobile connection.

Combined with the image-loading fixes already in progress on the carousel
sliders, addressing item #1 above (the GTM tag timing) is the next
highest-impact, lowest-risk item — it requires no code deployment and can
be done directly in your Google Tag Manager account.

---

## Recommended order of action

| Priority | Action | Who | Effort | Risk |
| :---: | --- | --- | --- | --- |
| 1 | Delay Crazy Egg, Clarity, Facebook Pixel, and a few related ad-tracking tags inside Google Tag Manager | Tag Manager account holder | ~15 minutes | None — configuration only |
| 2 | Confirm no duplicate Facebook tracking in Shopify's Customer Events settings | Shopify Admin | ~5 minutes | None — read-only check |
| 3 | Ask Termly if their consent script can load without disrupting consent enforcement | Termly support | Their response time | None until we act on their answer |
| 4 | Confirm whether the country-restriction tool can run without holding up the page | App vendor / whoever owns the requirement | Their response time | Business/legal decision, not technical |

We're ready to make any resulting code change (for item 3, once Termly
confirms) as soon as we hear back.
