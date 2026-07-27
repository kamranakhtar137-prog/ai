# Main Street Collectibles — On-Page SEO Fix Tracker

Live-site verification of the on-page SEO plan for `mainstreetnewcanaan.com`, checked directly
against the site's rendered HTML (title tag, meta description, H1, H2) rather than just the
planned copy.

## Files

- `MSC_SEO_Audit_Tracker.xlsx` — Executive Summary dashboard + full planned-vs-live tracker for
  all 13 pages/templates in the on-page SEO plan (Home, Shop/Catalog, Baseball, TCG/Pokémon,
  Basketball/WNBA, Football/NFL, Hockey/NHL, Merchandise, Contact, Hours & Info, About,
  Gift Cards, Product page template), color-coded by status, with a prioritized fix list.
- `MSC_SEO_Fix_Report.docx` — Narrative write-up of the same findings for the 5 pages verified
  live so far (Home, Shop/Catalog, Baseball, Contact, Hours & Info), with a numbered priority
  fix list.
- `generate_excel_tracker.py` / `generate_doc_report.py` — scripts used to regenerate the
  two files above (openpyxl / python-docx).

## Regenerating

```bash
pip install openpyxl python-docx
python3 generate_excel_tracker.py
python3 generate_doc_report.py
```

## Key findings (as of 2026-07-27)

- Home, Shop/Catalog, Contact, and Hours & Info have correct meta titles live.
- Shop/Catalog's meta description has a text-duplication bug ("...ba**Browse o**seball...").
- Home's meta description is 226 characters (target ~155–160) due to extra trailing text.
- Contact has no `<h1>` tag anywhere on the page.
- Hours & Info's live H1 ("Hours and Info") doesn't match the planned H1 ("Store Hours & Location").
- The Baseball collection (`/collections/baseball`) currently 404s publicly — it has no products
  and "Show in online store" is disabled.
- None of the 5 verified pages have their planned H2 content sections built yet — each renders
  only default theme chrome (cart drawer, filter drawer, newsletter signup, search).
- 8 of 13 planned pages/templates have not yet been audited against the live site.
