# Responsive Landing Page

A fully responsive landing page implementing the approved layout specifications across all breakpoints.

## Breakpoints

| Breakpoint | Range | Side Padding | Section Spacing | Max Content Width |
|------------|-------|--------------|-----------------|-------------------|
| Mobile | 375–767px | 16–20px | 32–40px | 100% |
| Tablet | 768–1199px | 24px | 40–48px | 100% |
| Laptop | 1200–1439px | 32px | 48–56px | 1200px |
| Desktop | 1440px+ | 40–48px | 56–64px | 1400–1440px |

## Sections

- **Hero** — Text above image on tablet/mobile; side-by-side on laptop+. Stacked full-width buttons on mobile.
- **Our Brands** — Horizontal slider with 2–3 cards (mobile), 3–4 cards (tablet), full row (desktop).
- **Existing Products** — Slider showing ~1.2–1.5 cards (mobile); 2-column grid (tablet); 3-column grid (desktop).
- **Licensing Categories** — 2×4 grid (mobile); 4-per-row (tablet+).
- **Information Cards** — Vertical stack (mobile/tablet); 3-column row (desktop).
- **Contact Form** — Single column (mobile); 2-column fields (tablet+).
- **CTA Banner** — Characters below text (mobile); side-by-side (tablet+).
- **Footer** — Accordion layout (mobile); column layout (desktop). Newsletter displayed first.

## File Structure

```
landing-page/
├── index.html
├── css/
│   ├── tokens.css       # Design tokens and breakpoint variables
│   ├── base.css         # Reset and typography
│   ├── components.css   # Reusable UI components
│   └── sections.css     # Section-specific styles
└── js/
    └── main.js          # Sliders, accordion, equal-height cards
```

## Preview

Open `landing-page/index.html` in a browser, or serve locally:

```bash
cd landing-page
python3 -m http.server 8080
```

Then visit `http://localhost:8080`.

## Global Consistency

- Equal card heights within each section (enforced via CSS grid/flex and JS equalization)
- Uniform spacing via CSS custom properties
- Consistent typography, button sizing, and border radius
- Images vertically centered within containers
