#!/usr/bin/env python3
"""Generate separate compositable bed layer images."""

from pathlib import Path
from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parent.parent / "demo-images"
SIZE = (800, 600)

SIZES = {
    "small-single": (290, 510),
    "single": (270, 530),
    "small-double": (210, 590),
    "double": (180, 620),
    "king": (150, 650),
    "super-king": (120, 680),
}

COLORS = {
    "beige-velvet": (210, 190, 165),
    "black-velvet": (35, 35, 40),
    "graphite-velvet": (95, 95, 100),
    "cream-cotton": (245, 240, 225),
    "midnight-blue-cotton": (30, 45, 90),
}

DEPTH_HEIGHT = {
    "6-inch": 70,
    "10-inch": 90,
    "14-inch": 110,
}


def save(img: Image.Image, path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG")


def swatch(color: tuple, label: str, path: Path) -> None:
    img = Image.new("RGB", (120, 120), color)
    draw = ImageDraw.Draw(img)
    draw.rectangle((4, 4, 116, 116), outline=(43, 46, 82), width=2)
    draw.text((10, 48), label[:8], fill=(255, 255, 255) if sum(color) < 380 else (43, 46, 82))
    save(img, path)


def transparent() -> Image.Image:
    return Image.new("RGBA", SIZE, (0, 0, 0, 0))


def draw_shadow(draw: ImageDraw.ImageDraw, left: int, right: int) -> None:
    cx = (left + right) // 2
    draw.ellipse((left - 20, 500, right + 20, 560), fill=(0, 0, 0, 55))


def draw_legs(draw: ImageDraw.ImageDraw, left: int, right: int, y_top: int) -> None:
    leg_w = 22
    for x in (left + 40, right - 40 - leg_w):
        draw.rounded_rectangle((x, y_top, x + leg_w, y_top + 70), radius=4, fill=(90, 90, 95, 255))


def draw_base(draw: ImageDraw.ImageDraw, left: int, right: int, color: tuple, height: int, drawers: int = 0) -> int:
    y_bottom = 470
    y_top = y_bottom - height
    draw.rounded_rectangle((left, y_top, right, y_bottom), radius=16, fill=color + (255,))
    if drawers >= 2:
        gap = (right - left) // 4
        for i, x in enumerate((left + gap, right - gap - gap)):
            draw.rounded_rectangle((x, y_top + 18, x + gap - 10, y_bottom - 12), radius=6, outline=(255, 255, 255, 160), width=2)
    if drawers >= 4:
        mid = (left + right) // 2
        w = (right - left) // 5
        for x in (left + w, mid - w // 2, right - 2 * w):
            draw.rounded_rectangle((x, y_top + 18, x + w - 8, y_bottom - 12), radius=6, outline=(255, 255, 255, 160), width=2)
    return y_top


def draw_headboard_shape(draw: ImageDraw.ImageDraw, left: int, right: int, color: tuple, shape: str, base_top: int) -> None:
    hb_bottom = base_top
    hb_top = max(80, hb_bottom - 190)

    if shape == "none":
        return

    if shape == "dudley":
        draw.rounded_rectangle((left + 10, hb_top + 30, right - 10, hb_bottom), radius=28, fill=color + (255,))
        draw.pieslice((left + 30, hb_top - 10, right - 30, hb_top + 80), 180, 0, fill=color + (255,))
    elif shape == "victor":
        draw.rectangle((left + 6, hb_top, right - 6, hb_bottom), fill=color + (255,))
        for x in range(left + 40, right - 20, 55):
            draw.ellipse((x, hb_top + 20, x + 24, hb_top + 44), fill=(255, 255, 255, 90))
    else:  # cornell
        draw.rounded_rectangle((left, hb_top, right, hb_bottom), radius=20, fill=color + (255,))
        draw.line((left + 50, hb_top + 35, right - 50, hb_top + 35), fill=(255, 255, 255, 110), width=3)
        draw.line((left + 50, hb_top + 80, right - 50, hb_top + 80), fill=(255, 255, 255, 110), width=3)


def main() -> None:
    # Swatches
    for name, (l, r) in SIZES.items():
        swatch((200, 200, 205), name.replace("-", " "), ROOT / "swatches" / "size" / f"{name}.png")
    for name, rgb in COLORS.items():
        swatch(rgb, name.split("-")[0], ROOT / "swatches" / "colour" / f"{name}.png")
    for depth in DEPTH_HEIGHT:
        swatch((180, 180, 190), depth, ROOT / "swatches" / "depth" / f"{depth}.png")
    for opt in ("no-drawers", "ottoman", "2-drawers", "4-drawers", "end-drawer"):
        swatch((170, 170, 180), opt.replace("-", " "), ROOT / "swatches" / "storage" / f"{opt}.png")
    for hb in ("cornell-plain", "cornell-lined", "cornell-buttoned", "dudley-plain", "victor-plain", "no-headboard"):
        swatch((190, 185, 175), hb.split("-")[0], ROOT / "swatches" / "headboard" / f"{hb}.png")

    # Shadow layer
    shadow = transparent()
    d = ImageDraw.Draw(shadow)
    draw_shadow(d, 120, 680)
    save(shadow, ROOT / "layers" / "shadow-only.png")
    save(transparent(), ROOT / "layers" / "transparent.png")

    # Legs layer (generic, scales visually with bed)
    for size, (left, right) in SIZES.items():
        legs = transparent()
        d = ImageDraw.Draw(legs)
        draw_legs(d, left, right, 400)
        save(legs, ROOT / "layers" / "legs" / f"{size}.png")

    # Base layers per size / colour / depth / drawers
    for size, (left, right) in SIZES.items():
        for colour, rgb in COLORS.items():
            for depth, height in DEPTH_HEIGHT.items():
                base = transparent()
                d = ImageDraw.Draw(base)
                draw_base(d, left, right, rgb, height, 0)
                save(base, ROOT / "layers" / "base" / size / colour / f"{depth}.png")

                base_drawers = transparent()
                d = ImageDraw.Draw(base_drawers)
                draw_base(d, left, right, rgb, height, 2)
                save(base_drawers, ROOT / "layers" / "base" / size / colour / f"{depth}-drawers.png")

    # Headboard layers per shape / colour / size
    shapes = ("cornell", "dudley", "victor")
    for size, (left, right) in SIZES.items():
        base_top = 470 - DEPTH_HEIGHT["14-inch"]
        for shape in shapes:
            for colour, rgb in COLORS.items():
                hb = transparent()
                d = ImageDraw.Draw(hb)
                draw_headboard_shape(d, left, right, rgb, shape, base_top)
                save(hb, ROOT / "layers" / "headboard" / size / shape / f"{colour}.png")

    # Legacy flat paths for backwards compatibility
    left, right = SIZES["small-double"]
    rgb = COLORS["beige-velvet"]
    base = transparent()
    d = ImageDraw.Draw(base)
    draw_base(d, left, right, rgb, DEPTH_HEIGHT["14-inch"], 0)
    save(base, ROOT / "layers" / "base-beige.png")

    base2 = transparent()
    d = ImageDraw.Draw(base2)
    draw_base(d, left, right, rgb, DEPTH_HEIGHT["14-inch"], 2)
    save(base2, ROOT / "layers" / "base-beige-2drawers.png")

    base_blk = transparent()
    d = ImageDraw.Draw(base_blk)
    draw_base(d, left, right, COLORS["black-velvet"], DEPTH_HEIGHT["14-inch"], 0)
    save(base_blk, ROOT / "layers" / "base-black.png")

    hb = transparent()
    d = ImageDraw.Draw(hb)
    draw_headboard_shape(d, left, right, rgb, "cornell", 470 - DEPTH_HEIGHT["14-inch"])
    save(hb, ROOT / "layers" / "headboard-cornell.png")
    save(transparent(), ROOT / "layers" / "headboard-none.png")

    print(f"Generated compositable layers in {ROOT}")


if __name__ == "__main__":
    main()
