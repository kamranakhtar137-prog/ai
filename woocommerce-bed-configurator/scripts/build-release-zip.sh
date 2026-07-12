#!/usr/bin/env bash
# Build a small release ZIP (Media Library mode — no bundled variation PNGs).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$(cd "$ROOT/.." && pwd)/woocommerce-bed-configurator.zip"

cd "$(dirname "$ROOT")"

rm -f "$OUT"

zip -9 -r "$OUT" "$(basename "$ROOT")" \
	-x "*.git*" \
	-x "*scripts/*" \
	-x "*demo-images/layers/base/*" \
	-x "*demo-images/layers/headboard/*" \
	-x "*demo-images/layers/storage/*" \
	-x "*demo-images/layers/legs/*" \
	-x "*demo-images/layers/base-*" \
	-x "*demo-images/layers/headboard-*" \
	-x "*demo-images/happybeds-cache/*" \
	-x "*demo-images/happybeds-layers/*"

echo "Created $OUT ($(du -h "$OUT" | cut -f1))"
