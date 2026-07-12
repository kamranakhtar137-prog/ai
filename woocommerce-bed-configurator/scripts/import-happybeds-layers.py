#!/usr/bin/env python3
"""Import Happy Beds layer files extracted via extract-happybeds-layers.js."""

from __future__ import annotations

import json
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
TARGET = ROOT / "demo-images" / "happybeds-layers"
MANIFEST = TARGET / "manifest.json"


def flatten_name(relative_path: str) -> str:
    return relative_path.replace("/", "__")


def restore_path(flat_name: str) -> Path:
    return TARGET / flat_name.replace("__", "/")


def import_downloads(download_dir: Path) -> None:
    TARGET.mkdir(parents=True, exist_ok=True)
    count = 0
    for flat in download_dir.glob("*.png"):
        dest = restore_path(flat.stem + ".png")
        if flat.suffix == ".jpg" or flat.name.endswith(".jpg"):
            dest = restore_path(flat.stem + ".jpg")
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(flat, dest)
        count += 1
    print(f"Imported {count} files into {TARGET}")


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python3 import-happybeds-layers.py /path/to/downloads")
        print("  python3 import-happybeds-layers.py /path/to/happybeds-manifest.json")
        sys.exit(1)

    source = Path(sys.argv[1]).expanduser().resolve()

    if source.is_dir():
        import_downloads(source)
        manifest_src = source / "happybeds-manifest.json"
        if manifest_src.exists():
            shutil.copy2(manifest_src, MANIFEST)
        return

    if source.name == "happybeds-manifest.json":
        shutil.copy2(source, MANIFEST)
        print(f"Copied manifest to {MANIFEST}")
        return

    if source.suffix == ".json":
        shutil.copy2(source, MANIFEST)
        parent = source.parent
        for rel in json.loads(source.read_text()).get("files", {}):
            flat = parent / flatten_name(rel)
            if flat.exists():
                dest = restore_path(Path(rel).name if "/" not in rel else rel)
                if rel.count("/"):
                    dest = TARGET / rel
                dest.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(flat, dest)
        print(f"Imported manifest + files from {source.parent}")
        return

    print(f"Unknown source: {source}")
    sys.exit(1)


if __name__ == "__main__":
    main()
