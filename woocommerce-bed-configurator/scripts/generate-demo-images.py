#!/usr/bin/env python3
"""Generate demo configurator images for WooCommerce Bed Configurator plugin."""

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent / "demo-images"
SIZE = (800, 600)


def save(img: Image.Image, path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG")


def swatch(color: tuple, label: str, path: Path) -> None:
    img = Image.new("RGB", (120, 120), color)
    draw = ImageDraw.Draw(img)
    draw.rectangle((4, 4, 116, 116), outline=(43, 46, 82), width=2)
    draw.text((10, 48), label[:8], fill=(255, 255, 255) if sum(color) < 380 else (43, 46, 82))
    save(img, path)


def layer_bed(path: Path, base_color: tuple, headboard: bool = True, drawers: int = 0) -> None:
    img = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    # shadow
    draw.ellipse((120, 470, 680, 540), fill=(0, 0, 0, 40))
    # legs
    for x in (180, 620):
        draw.rectangle((x, 420, x + 24, 500), fill=(90, 90, 95, 255))
    # base
    draw.rounded_rectangle((140, 360, 660, 470), radius=18, fill=base_color + (255,))
    # drawers
    if drawers >= 2:
        draw.rectangle((160, 390, 300, 450), outline=(255, 255, 255, 180), width=2)
        draw.rectangle((500, 390, 640, 450), outline=(255, 255, 255, 180), width=2)
    if drawers >= 4:
        draw.rectangle((160, 390, 300, 450), outline=(255, 255, 255, 180), width=2)
        draw.rectangle((330, 390, 470, 450), outline=(255, 255, 255, 180), width=2)
        draw.rectangle((500, 390, 640, 450), outline=(255, 255, 255, 180), width=2)
    # headboard
    if headboard:
        draw.rounded_rectangle((140, 180, 660, 370), radius=22, fill=base_color + (255,))
        draw.line((220, 220, 580, 220), fill=(255, 255, 255, 120), width=3)
        draw.line((220, 280, 580, 280), fill=(255, 255, 255, 120), width=3)
    save(img, path)


def main() -> None:
    colors = {
        "beige-velvet": (210, 190, 165),
        "black-velvet": (35, 35, 40),
        "graphite-velvet": (95, 95, 100),
        "cream-cotton": (245, 240, 225),
        "midnight-blue-cotton": (30, 45, 90),
    }
    sizes = ["small-single", "single", "small-double", "double", "king", "super-king"]
    for name in sizes:
        swatch((200, 200, 205), name.replace("-", " "), ROOT / "swatches" / "size" / f"{name}.png")
    for name, rgb in colors.items():
        swatch(rgb, name.split("-")[0], ROOT / "swatches" / "colour" / f"{name}.png")

    for depth in ("6-inch", "10-inch", "14-inch"):
        swatch((180, 180, 190), depth, ROOT / "swatches" / "depth" / f"{depth}.png")

    storage_opts = [
        "no-drawers", "ottoman", "2-drawers", "4-drawers", "end-drawer"
    ]
    for opt in storage_opts:
        swatch((170, 170, 180), opt.replace("-", " "), ROOT / "swatches" / "storage" / f"{opt}.png")

    for hb in ("cornell-plain", "cornell-lined", "cornell-buttoned", "dudley-plain", "victor-plain", "no-headboard"):
        swatch((190, 185, 175), hb.split("-")[0], ROOT / "swatches" / "headboard" / f"{hb}.png")

    # Layer composites
    layer_bed(ROOT / "layers" / "base-beige.png", (210, 190, 165), True, 0)
    layer_bed(ROOT / "layers" / "base-beige-2drawers.png", (210, 190, 165), True, 2)
    layer_bed(ROOT / "layers" / "base-black.png", (35, 35, 40), True, 0)
    layer_bed(ROOT / "layers" / "headboard-cornell.png", (210, 190, 165), True, 0)
    layer_bed(ROOT / "layers" / "headboard-none.png", (210, 190, 165), False, 0)
    layer_bed(ROOT / "layers" / "shadow.png", (210, 190, 165), True, 0)

    # Simple shadow-only layer
    shadow = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    ImageDraw.Draw(shadow).ellipse((100, 460, 700, 550), fill=(0, 0, 0, 55))
    save(shadow, ROOT / "layers" / "shadow-only.png")

    print(f"Generated demo images in {ROOT}")


if __name__ == "__main__":
    main()
