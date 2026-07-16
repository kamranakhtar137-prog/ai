# Before Optimization PageSpeed Report - filmartgallery.com

**Tested URL:** https://filmartgallery.com/  
**Source report:** https://pagespeed.web.dev/analysis/https-filmartgallery-com/ant750wfib?form_factor=mobile  
**Report generated from PageSpeed snapshot:** Jun 18, 2026, 11:17:30 AM / Lighthouse fetch time `2026-06-18T15:17:34.302Z`  
**Device/profile:** Mobile, Lighthouse 13.4.0, simulated throttling, 412 x 823 mobile viewport  
**Purpose:** Baseline report before page-speed optimization work begins.

## Executive Summary

The site passes mobile Core Web Vitals in real-user Chrome UX Report data, but the Lighthouse mobile lab run shows a low **Performance score of 53/100**. The main lab bottleneck is **Largest Contentful Paint (LCP) at 19.6 s**, supported by a high **Time to Interactive of 21.6 s**, **Total Blocking Time of 590 ms**, large network payloads, unused JavaScript/CSS, oversized product images, and third-party script overhead.

Accessibility, Best Practices, and SEO are better than Performance but still have baseline issues to track:

- **Accessibility:** 80/100
- **Best Practices:** 96/100
- **SEO:** 85/100

## Mobile Field Data Baseline - Real Users

Field data is from the latest 28-day Chrome UX Report period shown in PageSpeed Insights.

### This URL

| Metric | 75th Percentile | Status | Distribution |
| --- | ---: | --- | --- |
| Largest Contentful Paint (LCP) | 1.2 s | Good | 91% good / 5% needs improvement / 4% poor |
| Interaction to Next Paint (INP) | 140 ms | Good | 89% good / 10% needs improvement / 1% poor |
| Cumulative Layout Shift (CLS) | 0.03 | Good | 95% good / 3% needs improvement / 2% poor |
| First Contentful Paint (FCP) | 0.8 s | Good | 96% good / 3% needs improvement / 1% poor |
| Time to First Byte (TTFB) | 0.4 s | Good | 92% good / 7% needs improvement / 2% poor |

**Core Web Vitals assessment:** Passed

### Origin

| Metric | 75th Percentile | Status | Distribution |
| --- | ---: | --- | --- |
| Largest Contentful Paint (LCP) | 1.4 s | Good | 92% good / 6% needs improvement / 3% poor |
| Interaction to Next Paint (INP) | 110 ms | Good | 92% good / 6% needs improvement / 2% poor |
| Cumulative Layout Shift (CLS) | 0 | Good | 97% good / 1% needs improvement / 2% poor |
| First Contentful Paint (FCP) | 0.6 s | Good | 97% good / 2% needs improvement / 1% poor |
| Time to First Byte (TTFB) | 0.5 s | Good | 90% good / 8% needs improvement / 2% poor |

**Core Web Vitals assessment:** Passed

## Mobile Lighthouse Lab Baseline

| Category | Score |
| --- | ---: |
| Performance | 53/100 |
| Accessibility | 80/100 |
| Best Practices | 96/100 |
| SEO | 85/100 |

### Lab Performance Metrics

| Metric | Result | Lighthouse Score | Baseline Assessment |
| --- | ---: | ---: | --- |
| First Contentful Paint | 2.4 s | 0.71 | Needs improvement |
| Largest Contentful Paint | 19.6 s | 0.00 | Critical issue |
| Total Blocking Time | 590 ms | 0.50 | Needs improvement |
| Cumulative Layout Shift | 0.054 | 0.98 | Good |
| Speed Index | 5.0 s | 0.63 | Needs improvement |
| Time to Interactive | 21.6 s | 0.01 | Critical issue |
| Max Potential First Input Delay | 300 ms | 0.35 | Needs improvement |
| Initial Server Response Time | 10 ms | 1.00 | Good |

## Key Performance Findings

### 1. Lab LCP is the biggest issue

- Lighthouse LCP: **19.6 s**
- Lighthouse Performance score: **53/100**
- PageSpeed image delivery insight estimates **241 KiB** of image savings and **1.8 s LCP savings**.
- Top oversized image candidates:
  - `Lolita-Vintage-Movie-Poster-Original...800x.jpg`: 134,438 bytes total, 124,779 bytes estimated wasted. Displayed around 214 x 311 but delivered as 800 x 1160.
  - `Peeping-Tom-Vintage-Movie-Poster-Original...800x.jpg`: 67,102 bytes total, 62,280 bytes estimated wasted. Displayed around 214 x 162 but delivered as 800 x 604.
  - `Back-to-the-Future-Vintage-Movie-Poster-Original...800x.jpg`: 64,652 bytes total, 60,007 bytes estimated wasted. Displayed around 214 x 149 but delivered as 800 x 556.

### 2. JavaScript is delaying interactivity

- Time to Interactive: **21.6 s**
- Total Blocking Time: **590 ms**
- Reduce JavaScript execution time: **2.3 s**
- Minimize main-thread work: **5.3 s**
- Main-thread contributors:
  - Script evaluation: 2.61 s
  - Other: 986 ms
  - Style/layout: 617 ms
  - Script parsing/compilation: 606 ms
  - Garbage collection: 285 ms

Top JavaScript execution contributors:

| Resource | Scripting | Parse/compile |
| --- | ---: | ---: |
| `/cdn/shop/t/128/assets/vendors.js` | 408 ms | 45 ms |
| Homepage document | 132 ms | 26 ms |
| Termly resource blocker | 151 ms | 51 ms |
| Google Tag Manager | 146 ms | 70 ms |
| Google Analytics gtag | 137 ms | 72 ms |
| Shopify WPM script | 92 ms | 29 ms |

### 3. Unused JavaScript and CSS are significant

| Audit | Estimated Savings | Metric Impact |
| --- | ---: | --- |
| Reduce unused JavaScript | 465 KiB | ~2.1 s LCP savings |
| Reduce unused CSS | 57 KiB | ~300 ms LCP / ~450 ms FCP savings |
| Minify CSS | 13 KiB | ~150 ms FCP savings |
| Minify JavaScript | 3 KiB | Low direct savings |
| Legacy JavaScript | 29 KiB | ~200 ms LCP savings |

Top unused JavaScript candidates:

| Resource | Transfer Size | Estimated Unused |
| --- | ---: | ---: |
| `/cdn/shop/t/128/assets/vendors.js` | 118,264 bytes | 81,580 bytes |
| Google Analytics gtag | 171,633 bytes | 66,369 bytes |
| Google Tag Manager | 169,983 bytes | 64,932 bytes |

### 4. Network payload is heavy

- Total network payload: **4,037 KiB**
- Largest payload examples:
  - Shopify checkout hydrate script: 228,201 bytes
  - `Hawaii-Vintage-Movie-Poster-Original_800x.jpg`: 183,456 bytes
  - Google Analytics gtag: 172,322 bytes

### 5. Cache lifetime opportunities

- Use efficient cache lifetimes: **349 KiB** estimated savings
- Estimated LCP impact: **1.75 s**
- Notable resources with cache-efficiency issues:
  - Termly resource blocker
  - Facebook Pixel scripts
  - Shopify XR / model viewer scripts
  - Microsoft Clarity
  - Fast Simon autocomplete
  - Mailchimp connected-site script

### 6. Render-blocking requests still exist

- Render-blocking requests estimated savings: **150 ms**
- Examples:
  - Shopify accelerated checkout compatibility CSS
  - iWish extension CSS

### 7. Layout stability is acceptable in lab but still has image-related shifts

- Lighthouse CLS: **0.054**
- PageSpeed found **3 layout shifts**.
- Shift cause includes media elements lacking explicit rendered size/reserved space in product/image sections.

## Accessibility Baseline - 80/100

Failed or notable accessibility audits:

| Audit | Finding |
| --- | --- |
| Dialog accessible name | Wishlist `role="alertdialog"` does not have an accessible name. |
| Required ARIA children | Product slider/gallery ARIA roles are missing required child roles. |
| Button name | Newsletter subscribe button lacks an accessible name. |
| Heading order | Heading hierarchy is not sequential. |
| Link name | 73 links do not have discernible names, including product/collection links. |
| Touch target size | Search icon target does not have sufficient size or spacing. |
| Main landmark | Document does not have a `<main>` landmark. |

## Best Practices Baseline - 96/100

Failed or notable best-practice audit:

- **Browser errors were logged to the console:** 11 console/network errors were detected.
  - Several are `ERR_BLOCKED_BY_CLIENT`.
  - Some resources returned `400 Bad Request`.

## SEO Baseline - 85/100

Failed SEO audits:

| Audit | Finding |
| --- | --- |
| Links do not have descriptive text | 3 links found. |
| Links are not crawlable | 2 instances on `a.cart_content__continue-shopping`; anchor appears without a crawlable `href`. |

## Priority Optimization Backlog

1. **Fix LCP image delivery on mobile**
   - Ensure homepage product/hero images use responsive `srcset` sizes that match mobile display dimensions.
   - Compress and serve appropriately sized image variants.
   - Confirm LCP candidate receives priority only when it is truly above the fold.

2. **Reduce and defer JavaScript**
   - Audit `vendors.js` and remove/defer unused modules.
   - Defer non-critical third-party scripts until after first interaction or consent where possible.
   - Review GTM, gtag, Facebook Pixel, Clarity, Termly, Fast Simon, Mailchimp, and Shopify app scripts for necessity and load timing.

3. **Reduce unused CSS**
   - Split critical CSS from non-critical theme CSS.
   - Remove unused CSS from `styles.aio.min.css` or load below-the-fold styles later.

4. **Improve caching for third-party and app resources**
   - Where configurable, set longer cache lifetimes for static assets.
   - For third-party scripts that cannot be cached better, reduce their use or load them conditionally.

5. **Address render-blocking CSS**
   - Inline minimal critical CSS.
   - Defer non-critical Shopify/app extension CSS.

6. **Fix accessibility and SEO markup issues while optimizing**
   - Add a main landmark.
   - Add accessible names to icon/product links and newsletter button.
   - Correct ARIA slider/gallery structure.
   - Add crawlable `href` values for continue-shopping links.

## Baseline Success Criteria for After Optimization

Use this report as the comparison point after changes. Target improvements should include:

- Performance score above 80 on mobile Lighthouse.
- Lab LCP reduced from 19.6 s to under 2.5 s where possible.
- TBT reduced below 200 ms.
- TTI materially reduced from 21.6 s.
- Total network payload reduced from 4,037 KiB.
- Unused JavaScript/CSS savings reduced substantially.
- Accessibility score improved from 80 toward 95+.
- SEO score improved from 85 toward 100.
