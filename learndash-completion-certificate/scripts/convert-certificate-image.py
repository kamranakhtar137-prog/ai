#!/usr/bin/env python3
"""
Convert Certificate.png to LearnDash-ready certificate.jpg.

Requirements:
  - Source file: assets/source/Certificate.png
  - Output: assets/certificate.jpg
  - Dimensions: 2550 x 3300 px (A4 portrait @ 300 dpi)
  - Max file size: 1 MB
  - Format: JPEG (LearnDash does not accept PNG backgrounds)
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    print("Pillow is required. Install with: pip install Pillow", file=sys.stderr)
    sys.exit(1)

TARGET_WIDTH = 2550
TARGET_HEIGHT = 3300
TARGET_DPI = (300, 300)
MAX_BYTES = 1_000_000


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Convert certificate PNG to LearnDash JPG.")
    parser.add_argument(
        "--input",
        default="assets/source/Certificate.png",
        help="Path to the source PNG file.",
    )
    parser.add_argument(
        "--output",
        default="assets/certificate.jpg",
        help="Path to the output JPG file.",
    )
    parser.add_argument(
        "--placeholder",
        action="store_true",
        help="Generate a placeholder split layout when no PNG is available.",
    )
    return parser.parse_args()


def create_placeholder() -> Image.Image:
    image = Image.new("RGB", (TARGET_WIDTH, TARGET_HEIGHT), "#111111")
    pixels = image.load()
    split_x = int(TARGET_WIDTH * 0.58)

    for x in range(split_x):
        shade = 180 - int((x / max(split_x - 1, 1)) * 120)
        for y in range(TARGET_HEIGHT):
            noise = ((x * 13 + y * 7) % 31) - 15
            value = max(0, min(255, shade + noise))
            pixels[x, y] = (value, value, value)

    return image


def load_source_image(input_path: Path, use_placeholder: bool) -> Image.Image:
    if input_path.exists():
        with Image.open(input_path) as source:
            return source.convert("RGB")

    if use_placeholder:
        print(f"Source not found at {input_path}; generating placeholder.")
        return create_placeholder()

    raise FileNotFoundError(
        f"Source image not found: {input_path}\n"
        "Place Certificate.png in assets/source/ or pass --placeholder."
    )


def save_under_size_limit(image: Image.Image, output_path: Path) -> None:
    resized = image.resize((TARGET_WIDTH, TARGET_HEIGHT), Image.Resampling.LANCZOS)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    quality = 92
    while quality >= 50:
        resized.save(
            output_path,
            format="JPEG",
            quality=quality,
            optimize=True,
            progressive=True,
            dpi=TARGET_DPI,
        )
        size = output_path.stat().st_size
        if size <= MAX_BYTES:
            print(f"Saved {output_path} ({size:,} bytes, quality={quality}).")
            return
        quality -= 4

    raise RuntimeError(
        f"Could not reduce {output_path} below 1 MB. "
        "Try simplifying the source image or lowering resolution."
    )


def main() -> int:
    args = parse_args()
    plugin_dir = Path(__file__).resolve().parents[1]
    input_path = (plugin_dir / args.input).resolve()
    output_path = (plugin_dir / args.output).resolve()

    image = load_source_image(input_path, args.placeholder)
    save_under_size_limit(image, output_path)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
