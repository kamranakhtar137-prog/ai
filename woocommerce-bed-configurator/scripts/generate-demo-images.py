#!/usr/bin/env python3
"""Generate compositable bed layer images (1000×750) for all 23 colours."""

from __future__ import annotations

import json
import random
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parent.parent / "demo-images"
SCRIPT_DIR = Path(__file__).resolve().parent
SIZE = (1000, 750)

SIZES = {
    "small-single": (360, 640),
    "single": (338, 662),
    "small-double": (262, 738),
    "double": (225, 775),
    "king": (188, 812),
    "super-king": (150, 850),
}

STORAGE_TYPES = ("no-drawers", "ottoman", "2-drawers", "4-drawers", "end-drawer")
HEADBOARD_VARIANTS = ("plain", "lined", "buttoned")
HEADBOARD_SHAPES = ("cornell", "dudley", "victor")

DEPTH_HEIGHT = {
    "6-inch": 88,
    "10-inch": 112,
    "14-inch": 138,
}

BASE_BOTTOM = 588


def load_colours() -> dict[str, tuple[int, int, int]]:
    data = json.loads((SCRIPT_DIR / "colour-rgb.json").read_text(encoding="utf-8"))
    return {slug: tuple(rgb) for slug, rgb in data.items()}


COLORS = load_colours()


def save(img: Image.Image, path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG", optimize=True)


def swatch(color: tuple, label: str, path: Path) -> None:
    img = Image.new("RGB", (120, 120), color)
    draw = ImageDraw.Draw(img)
    draw.rectangle((4, 4, 116, 116), outline=(43, 46, 82), width=2)
    draw.text((10, 48), label[:8], fill=(255, 255, 255) if sum(color) < 380 else (43, 46, 82))
    save(img, path)


def transparent() -> Image.Image:
    return Image.new("RGBA", SIZE, (0, 0, 0, 0))


def is_velvet(slug: str) -> bool:
    return slug.endswith("-velvet")


def fabric_texture(size: tuple[int, int], rgb: tuple[int, int, int], velvet: bool = True) -> Image.Image:
    random.seed(sum(rgb) + size[0] * size[1])
    tile = Image.new("RGBA", (64, 64), rgb + (255,))
    px = tile.load()
    for y in range(64):
        for x in range(64):
            n = random.randint(-18, 18) if velvet else random.randint(-10, 10)
            px[x, y] = (
                max(0, min(255, rgb[0] + n)),
                max(0, min(255, rgb[1] + n - 2)),
                max(0, min(255, rgb[2] + n - 4)),
                255,
            )
    if velvet:
        tile = tile.filter(ImageFilter.GaussianBlur(radius=0.6))
    tex = Image.new("RGBA", size, rgb + (255,))
    for y in range(0, size[1], 64):
        for x in range(0, size[0], 64):
            tex.paste(tile, (x, y))
    return tex.crop((0, 0, size[0], size[1]))


def paste_fabric(canvas: Image.Image, box: tuple[int, int, int, int], rgb: tuple[int, int, int], slug: str = "", radius: int = 18) -> None:
    x1, y1, x2, y2 = box
    w, h = x2 - x1, y2 - y1
    tex = fabric_texture((w, h), rgb, velvet=is_velvet(slug) if slug else True)
    mask = Image.new("L", (w, h), 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, w, h), radius=radius, fill=255)
    canvas.paste(tex, (x1, y1), mask)


def draw_shadow() -> Image.Image:
    shadow = transparent()
    layer = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse((90, 620, 910, 700), fill=(0, 0, 0, 70))
    layer = layer.filter(ImageFilter.GaussianBlur(radius=12))
    shadow.alpha_composite(layer)
    return shadow


def draw_legs(left: int, right: int, y_top: int) -> Image.Image:
    img = transparent()
    d = ImageDraw.Draw(img)
    leg_w, leg_h = 32, 96
    for x in (left + 44, right - 44 - leg_w):
        d.rounded_rectangle((x, y_top, x + leg_w, y_top + leg_h), radius=6, fill=(58, 54, 50, 255))
        d.rounded_rectangle((x + 4, y_top + 4, x + leg_w - 4, y_top + leg_h - 8), radius=5, fill=(82, 76, 70, 255))
        d.line((x + 8, y_top + 12, x + leg_w - 8, y_top + 12), fill=(48, 44, 40, 180), width=2)
    return img


def draw_base(left: int, right: int, rgb: tuple[int, int, int], slug: str, height: int, drawers: int = 0) -> Image.Image:
    img = transparent()
    y_bottom = BASE_BOTTOM
    y_top = y_bottom - height
    paste_fabric(img, (left, y_top, right, y_bottom), rgb, slug, radius=20)
    d = ImageDraw.Draw(img)
    d.rounded_rectangle((left, y_top, right, y_bottom), radius=20, outline=(255, 255, 255, 55), width=2)
    d.line((left + 18, y_top + 14, right - 18, y_top + 14), fill=(255, 255, 255, 40), width=2)

    if drawers >= 2:
        gap = (right - left) // 4
        for x in (left + gap, right - gap - gap):
            d.rounded_rectangle((x, y_top + 22, x + gap - 12, y_bottom - 16), radius=8, outline=(255, 255, 255, 130), width=2)
            d.arc((x + 14, y_bottom - 34, x + gap - 26, y_bottom - 10), 0, 180, fill=(255, 255, 255, 90), width=2)
    if drawers >= 4:
        mid = (left + right) // 2
        w = (right - left) // 5
        for x in (left + w, mid - w // 2, right - 2 * w):
            d.rounded_rectangle((x, y_top + 22, x + w - 10, y_bottom - 16), radius=8, outline=(255, 255, 255, 130), width=2)
    return img


def draw_headboard(left: int, right: int, rgb: tuple[int, int, int], slug: str, shape: str, variant: str, base_top: int) -> Image.Image:
    img = transparent()
    hb_bottom = base_top
    hb_top = max(95, hb_bottom - 235)

    if shape == "none":
        return img

    if shape == "dudley":
        paste_fabric(img, (left + 12, hb_top + 36, right - 12, hb_bottom), rgb, slug, radius=32)
        d = ImageDraw.Draw(img)
        d.pieslice((left + 36, hb_top - 12, right - 36, hb_top + 98), 180, 0, fill=rgb + (255,))
    elif shape == "victor":
        paste_fabric(img, (left + 8, hb_top, right - 8, hb_bottom), rgb, slug, radius=8)
        d = ImageDraw.Draw(img)
        for x in range(left + 48, right - 24, 68):
            d.ellipse((x, hb_top + 24, x + 28, hb_top + 52), fill=(255, 255, 255, 75))
    else:
        paste_fabric(img, (left, hb_top, right, hb_bottom), rgb, slug, radius=22)
        d = ImageDraw.Draw(img)
        if variant in ("lined", "buttoned", "plain"):
            d.line((left + 58, hb_top + 42, right - 58, hb_top + 42), fill=(255, 255, 255, 95), width=3)
            d.line((left + 58, hb_top + 96, right - 58, hb_top + 96), fill=(255, 255, 255, 95), width=3)
        if variant == "buttoned":
            for y in range(hb_top + 58, hb_bottom - 36, 48):
                for x in range(left + 80, right - 40, 72):
                    d.ellipse((x, y, x + 16, y + 16), fill=(255, 255, 255, 70), outline=(255, 255, 255, 120))

    return img


def drawer_boxes(left: int, right: int, storage: str) -> list[tuple[int, int, int, int]]:
    width = right - left
    y_top = BASE_BOTTOM - DEPTH_HEIGHT["14-inch"] + 22
    y_bottom = BASE_BOTTOM - 16
    if storage == "end-drawer":
        w = width // 3
        return [(right - w - 18, y_top, right - 18, y_bottom)]
    if storage == "2-drawers":
        gap = width // 4
        return [
            (left + gap, y_top, left + 2 * gap - 12, y_bottom),
            (right - 2 * gap, y_top, right - gap - 12, y_bottom),
        ]
    if storage == "4-drawers":
        w = width // 5
        mid = (left + right) // 2
        return [
            (left + w, y_top, left + 2 * w - 10, y_bottom),
            (mid - w // 2, y_top, mid + w // 2 - 10, y_bottom),
            (right - 2 * w, y_top, right - w - 10, y_bottom),
        ]
    if storage == "ottoman":
        return [(left + 24, y_top, right - 24, y_bottom - 8)]
    return []


def draw_storage_back(left: int, right: int, rgb: tuple[int, int, int], slug: str, storage: str, height: int) -> Image.Image:
    if storage in ("no-drawers",):
        return transparent()
    img = transparent()
    y_bottom = BASE_BOTTOM - height + 8
    y_top = y_bottom - 42
    paste_fabric(img, (left + 8, y_top, right - 8, y_bottom), tuple(max(0, c - 28) for c in rgb), slug, radius=14)
    return img


def draw_storage_slot(left: int, right: int, rgb: tuple[int, int, int], slug: str, storage: str, slot: int, height: int) -> Image.Image:
    boxes = drawer_boxes(left, right, storage)
    if not boxes or slot > len(boxes):
        return transparent()
    img = transparent()
    box = boxes[slot - 1]
    paste_fabric(img, box, rgb, slug, radius=10)
    d = ImageDraw.Draw(img)
    x1, y1, x2, y2 = box
    d.rounded_rectangle(box, radius=10, outline=(255, 255, 255, 120), width=2)
    if storage != "ottoman":
        d.arc((x1 + 12, y2 - 28, x2 - 12, y2 - 4), 0, 180, fill=(255, 255, 255, 90), width=2)
    else:
        d.line((x1 + 24, y1 + 18, x2 - 24, y1 + 18), fill=(255, 255, 255, 70), width=2)
    return img


def main() -> None:
    for name in SIZES:
        swatch((200, 200, 205), name.replace("-", " "), ROOT / "swatches" / "size" / f"{name}.png")
    for name, rgb in COLORS.items():
        swatch(rgb, name.split("-")[0], ROOT / "swatches" / "colour" / f"{name}.png")
    for depth in DEPTH_HEIGHT:
        swatch((180, 180, 190), depth, ROOT / "swatches" / "depth" / f"{depth}.png")
    for opt in STORAGE_TYPES:
        swatch((170, 170, 180), opt.replace("-", " "), ROOT / "swatches" / "storage" / f"{opt}.png")
    for hb in ("cornell-plain", "cornell-lined", "cornell-buttoned", "dudley-plain", "victor-plain", "no-headboard"):
        swatch((190, 185, 175), hb.split("-")[0], ROOT / "swatches" / "headboard" / f"{hb}.png")

    save(draw_shadow(), ROOT / "layers" / "shadow-only.png")
    save(transparent(), ROOT / "layers" / "transparent.png")

    for size, (left, right) in SIZES.items():
        save(draw_legs(left, right, BASE_BOTTOM + 2), ROOT / "layers" / "legs" / f"{size}.png")

    for size, (left, right) in SIZES.items():
        for colour, rgb in COLORS.items():
            for depth, height in DEPTH_HEIGHT.items():
                save(draw_base(left, right, rgb, colour, height, 0), ROOT / "layers" / "base" / size / colour / f"{depth}.png")
                save(draw_base(left, right, rgb, colour, height, 2), ROOT / "layers" / "base" / size / colour / f"{depth}-drawers.png")

    for size, (left, right) in SIZES.items():
        base_top = BASE_BOTTOM - DEPTH_HEIGHT["14-inch"]
        for shape in HEADBOARD_SHAPES:
            for variant in HEADBOARD_VARIANTS:
                if shape != "cornell" and variant != "plain":
                    continue
                for colour, rgb in COLORS.items():
                    path = ROOT / "layers" / "headboard" / size / shape / variant / f"{colour}.png"
                    save(draw_headboard(left, right, rgb, colour, shape, variant, base_top), path)

    for storage in STORAGE_TYPES:
        for size, (left, right) in SIZES.items():
            for colour, rgb in COLORS.items():
                for depth, height in DEPTH_HEIGHT.items():
                    layer_dir = ROOT / "layers" / "storage"
                    save(draw_storage_back(left, right, rgb, colour, storage, height), layer_dir / "storage_back" / storage / size / colour / f"{depth}.png")
                    for slot in (1, 2, 3):
                        save(draw_storage_slot(left, right, rgb, colour, storage, slot, height), layer_dir / f"storage_{slot}" / storage / size / colour / f"{depth}.png")

    print(f"Generated {len(COLORS)} colour layers in {ROOT}")


if __name__ == "__main__":
    main()
