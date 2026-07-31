# Homepage Collection Slider — LCP Fix

Fixes the "Collection Slider" homepage section (`class: homepage-slider1`) so
mobile LCP stops being driven by the carousel. Reported: **LCP 10.9s**, up
549% over 30 days on ~380k sessions, and the homepage requesting ~101 images
on load.

## What was wrong

- **Every slider on the page eagerly loaded its first 4 images** (`loading="eager"`),
  regardless of whether that slider was above or below the fold. If the
  homepage has several of these sliders stacked (Latest Acquisitions, By Era,
  By Country, etc.), that's dozens of eager image requests competing for
  bandwidth with the actual LCP image on a mobile connection.
- Only the very first image had `fetchpriority="high"` — the browser had no
  `<link rel="preload">` hint, so on a slow connection it can still start
  late (CSS/JS still has to be parsed before the image is discovered by the
  main HTML parser).
- The `sizes` attribute was a flat `(max-width: 768px) 33vw, 25vw` guess. The
  layout actually renders each poster in a **fixed `max-height: 400px` box**
  (width derived from the poster's own aspect ratio), which doesn't change
  with viewport width. On wide desktop screens `25vw` could suggest a box
  up to ~480px wide when the real box is ~290px, so the browser was
  downloading a larger `srcset` candidate than it needed to.

## What changed (`sections/homepage-collection-slider.liquid`)

1. **Preload only the true first slide, once.** Added a `preload_first_image`
   checkbox setting. Turn it on for the *one* slider instance that is highest
   on the page (the actual LCP candidate) — it emits a single
   `<link rel="preload" as="image" imagesrcset="..." imagesizes="..." fetchpriority="high">`
   at the very top of that section's markup, using the exact same URL/srcset
   as the real `<img>`, so there's no duplicate/wasted request. Leave the
   setting off on every other slider on the page.
2. **Lazy-load everything else.** Removed the "eager for the first 4 slides"
   branch entirely. Now only the single priority slide (when the setting
   above is on) is eager + `fetchpriority="high"`. Every other slide, on
   every slider, uses the existing SVG-placeholder + `data-src`/`data-srcset`
   lazysizes pattern plus native `loading="lazy"`.
3. **Right-sized `sizes`.** `sizes` is now computed per image from its real
   aspect ratio against the fixed 400px-tall box (`width = aspect × 400px`),
   e.g. `sizes="292px"` instead of a viewport-percentage guess. The browser
   still always picks a `srcset` candidate that's full resolution for that
   box (so displayed image quality is unchanged/no upscaling, no added
   compression) — it just stops over-fetching a bigger file than the slot
   needs, especially on desktop.
4. **No new image transforms.** All existing `img_url` breakpoints
   (`200x…1200x`) are untouched, so there is no compression/quality change —
   only *which* of those already-existing sizes gets requested, and *when*,
   changed.

## Install

Replace `sections/homepage-slider1.liquid` (or whatever it's named in your
theme) with `sections/homepage-collection-slider.liquid` from this folder.
No new assets/snippets are required — `flickity.min.css` /
`flickity.pkgd.min.js` references are unchanged.

### After deploying

In the Theme Editor, on the homepage, open the **first** Collection Slider
section that appears above the fold and turn on **"Preload first image
(LCP)"**. Leave it off on every other Collection Slider section on the page.

## Verify

1. Run PageSpeed Insights / Lighthouse (mobile) on the homepage.
2. Confirm the LCP element is the first slide of the top slider, and that a
   matching `Link: <...>; rel=preload` (or a `<link rel=preload>` in the
   HTML) shows up in the network waterfall before it.
3. Confirm only ~1 poster image loads before scroll on mobile (Network tab,
   throttled to Slow 4G) — the rest should stay as tiny inline SVG
   placeholders until you scroll/drag the carousel.
4. Confirm no visible quality/sharpness change on any poster image at any
   breakpoint — the `img_url` widths requested didn't change, only the
   `sizes` hint and the load timing did.
