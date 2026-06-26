# WordPress / Elementor speed checklist

The shared PageSpeed report shows the main real-user issue is mobile LCP around 3 seconds and TTFB around 1.3-1.6 seconds. Desktop field data is already much healthier, so the highest-impact work is reducing mobile hero/LCP cost and server response time.

## WP Rocket settings

Enable these first, then retest after clearing all caches:

- **Cache**
  - Enable page caching.
  - Enable separate mobile cache if the mobile layout differs from desktop.
  - Enable cache preloading and sitemap-based preload.
  - Enable link preloading.
- **File optimization**
  - Minify CSS.
  - Enable Remove Unused CSS or Optimize CSS Delivery. Use only one CSS delivery strategy.
  - Minify JavaScript.
  - Load JavaScript deferred.
  - Delay JavaScript execution for non-critical third parties such as analytics, tag managers, chat widgets, forms, heatmaps, and reCAPTCHA.
  - If Elementor interactions break, exclude Elementor's required frontend scripts from delay and keep delaying third-party scripts.
- **Media**
  - Enable lazy loading for images and iframes.
  - Enable the YouTube preview image replacement for embeds.
  - Add missing image dimensions.
  - Exclude the hero/LCP image from lazy loading.
  - Preload the hero/LCP image, especially on mobile.
- **Preload**
  - Preload critical local WOFF2 font files that render above the fold.
  - Use `font-display: swap` for custom fonts.
- **Database**
  - Clean revisions, transients, and expired cache data on a schedule.
- **CDN / edge cache**
  - Use Cloudflare APO, WP Rocket Cloudflare integration, or another full-page edge cache.
  - Confirm HTML cache hits for anonymous mobile visitors.

## Elementor checks

- Turn on Elementor performance experiments that are stable for the site: Optimized DOM Output, Improved Asset Loading, Improved CSS Loading, and Inline Font Icons.
- Disable unused Elementor widgets, Font Awesome 4 support, icon libraries, sliders, animation libraries, and popup features if not used.
- Avoid sliders, video backgrounds, Lottie animations, and heavy entrance animations above the fold.
- Use a static hero image with separate desktop/mobile crops.
- Make the mobile hero image close to its rendered size, usually WebP/AVIF under 150 KB.
- Reduce custom font families and weights. Prefer 1-2 families and only the weights used above the fold.

## LCP image rules

- The image visible first on mobile must not be lazy-loaded.
- Add `fetchpriority="high"` or preload for the mobile LCP image.
- Provide explicit width/height or an aspect ratio to avoid layout shifts.
- Serve responsive `srcset` sizes from the WordPress Media Library rather than external image URLs when possible.

If the YouTube Playlist Widget in this repository is used above the fold, set its thumbnail loading mode to **Eager / high priority**. If it is lower on the page, keep it on **Auto** or **Lazy**.

## Server / TTFB target

The report's TTFB is above the ideal Core Web Vitals target. Aim for anonymous cached TTFB below 800 ms by checking:

- Full-page cache is active for mobile anonymous traffic.
- No cookies or query strings are bypassing cache on landing pages.
- PHP OPcache is enabled.
- Object cache such as Redis is enabled for dynamic requests.
- Slow plugins, Elementor queries, and external API calls are not running during cached page generation.
- Hosting/CDN origin location is close to the main audience or edge HTML caching is active.

## Retest process

1. Clear WP Rocket, CDN, page builder, and server caches.
2. Regenerate WP Rocket used CSS/critical CSS.
3. Warm the cache for the homepage and main landing pages.
4. Run PageSpeed mobile and desktop in an incognito browser.
5. Validate that the LCP element is the expected hero image and that it is not lazy-loaded.
6. Watch field data over the next Chrome UX Report window; lab scores can improve immediately, field data moves more slowly.
