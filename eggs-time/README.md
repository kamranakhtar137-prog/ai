# Eggs Time — Home v2 Responsive CSS

Breakpoint-based side padding and section spacing for the Eggs Time homepage (`home-v2.css`).

## Breakpoint tokens

| Breakpoint | Media query | Side padding (`--et-home-section-x`) | Section spacing (`--et-home-section-padding-y`) | Max width (`--et-home-max`) |
|------------|-------------|--------------------------------------|-----------------------------------------------|-----------------------------|
| Mobile | default (0–767px) | 18px (spec: 16–20px) | 36px (spec: 32–40px) | 1650px |
| Tablet | `min-width: 768px` | 24px (spec: 24px) | 44px (spec: 40–48px) | 1650px |
| Laptop | `min-width: 1200px` | 32px (spec: 32px) | 52px (spec: 48–56px) | 1650px |
| Desktop | `min-width: 1440px` | 44px (spec: 40–48px) | 60px | 1420px (spec: 1400–1440px) |

`--et-home-section-gap` matches `--et-home-section-padding-y` at each breakpoint.  
`--et-home-section-top-spacing` is `calc(var(--et-home-section-gap) / 2)` for shared `.et-home__section-inner` rhythm.

## 10 homepage sections

| # | Section | Selector |
|---|---------|----------|
| 1 | Hero (split video) | `.et-home__hero.et-home__hero--split-video .et-home__hero-inner` |
| 2 | Playful band (Egg World + Best Sellers) | `.et-home__bg-band--playful` → characters + best-sellers inners |
| 3 | Popular Stories | `#et-home-stories .et-home__section-inner` |
| 4 | Watch & Learn | `#et-home-youtube .et-home__section-inner` |
| 5 | Collect, Play & Learn | `#et-home-products .et-home__section-inner` |
| 6 | Fun in Every Egg | `.et-home__fun-egg .et-home__section-inner` |
| 7 | Why Parents Love | `.et-home__parent-benefits .et-home__section-inner` |
| 8 | Social Community | `#et-home-social .et-home__section-inner` |
| 9 | Distributor Partnerships | `.et-home__distributor .et-home__section-inner` |
| 10 | Partner CTA | `.et-home__cta .et-home__cta-inner` |

## Files

- `css/home-v2.css` — production stylesheet (drop-in replacement)
- `css/home-v2-source.css` — unmodified source snapshot
- `apply_breakpoints.py` — reproducible transform script

## Regenerate

```bash
cd eggs-time
python3 apply_breakpoints.py
```
