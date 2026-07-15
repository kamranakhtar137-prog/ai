# Redirect Audit Review

## Status

We reviewed the Menerva redirect audit preview shared by the client. **Please replace `data/redirects.csv` with the final attached CSV before import or deployment** so we can validate every row against live redirects.

## Current live behavior (sample rows)

| Source URL | Current behavior | Recommended action |
| --- | --- | --- |
| `/risk-proofing-profit-tactics/` | Already redirects to `/insights/webinars/` | Safe to import if target matches final CSV |
| `/ai-price-selling-solutions/` | Already redirects to `/solutions/` | Safe to import if target matches final CSV |
| `/case-study-molson-coors/` | Already redirects to `/insights/case-studies/` | Safe to import if target matches final CSV |
| `/resources/webinars/?filter=webinars` | Already redirects to `/insights/webinars/?filter=webinars` | Safe to import if target matches final CSV |
| `/resource/*` legacy paths | Mixed — some 404, some redirect | Import from final CSV; verify each destination |
| `/comparison-vendavo-vs-*` | Review individually before import | Confirm final target with Menerva |

## Recommendation before CSV upload to Redirection

1. Export the current Redirection plugin rules from WP Admin.
2. Diff the final Menerva CSV against the export.
3. Import only new/changed rules to avoid overriding intentional existing redirects.
4. Spot-check high-traffic legacy `/resource/` and `/resources/` URLs after import.

## Plugin vs Redirection plugin

This plugin can enforce redirects from `data/redirects.csv` at runtime. Use **one** redirect system for each rule to avoid duplicate/conflicting redirects:

- **Preferred for client workflow:** Redirection plugin CSV import after review
- **Fallback:** Populate `data/redirects.csv` and leave overlapping Redirection rules disabled

## Questions for client

1. Please attach the final CSV so we can complete a full row-by-row review.
2. Confirm whether any existing Redirection rules should be preserved over the audit list when conflicts exist.
