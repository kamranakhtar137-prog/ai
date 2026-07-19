#!/usr/bin/env python3
"""Patch license.css root layout tokens to match home-v2.css breakpoints."""

from pathlib import Path

SOURCE = Path(__file__).parent / "css" / "license-source.css"
OUTPUT = Path(__file__).parent / "css" / "license.css"

REPLACEMENTS = [
    ("--et-lic-shell-max: 1440px;", "--et-lic-shell-max: 1650px;"),
    ("--et-lic-shell-x: clamp(20px, 3.5vw, 48px);", "--et-lic-shell-x: 18px;"),
    (
        "--et-lic-section-x: var(--et-lic-shell-x, clamp(20px, 3.5vw, 48px));",
        "--et-lic-section-x: var(--et-lic-shell-x, 18px);",
    ),
    ("--et-lic-section-pad-y: clamp(56px, 4.5vw, 64px);", "--et-lic-section-pad-y: 36px;"),
    (
        "--et-lic-section-half: calc(var(--et-lic-section-pad-y) / 2);",
        "--et-lic-section-half: calc(var(--et-lic-section-gap) / 2);",
    ),
    (
        "--et-lic-section-gap: var(--et-lic-section-pad-y);",
        "--et-lic-section-gap: 36px;",
    ),
    ("--et-lic-max: var(--et-lic-shell-max, 1440px);", "--et-lic-max: var(--et-lic-shell-max, 1650px);"),
]

HOME_V2_BREAKPOINTS = """
/* ----- Home v2 layout rhythm (mirror home-v2.css) ----- */
@media screen and (min-width: 768px) {
    .et-license,
    .main--license {
        --et-lic-shell-x: 24px;
        --et-lic-section-x: 24px;
        --et-lic-section-gap: 44px;
        --et-lic-section-pad-y: 44px;
        --et-lic-section-half: calc(var(--et-lic-section-gap) / 2);
    }
}

@media screen and (min-width: 1200px) {
    .et-license,
    .main--license {
        --et-lic-shell-x: 32px;
        --et-lic-section-x: 32px;
        --et-lic-section-gap: 52px;
        --et-lic-section-pad-y: 52px;
        --et-lic-section-half: calc(var(--et-lic-section-gap) / 2);
    }
}

@media screen and (min-width: 1440px) {
    .et-license,
    .main--license {
        --et-lic-shell-max: 1420px;
        --et-lic-max: 1420px;
        --et-lic-shell-x: 44px;
        --et-lic-section-x: 44px;
        --et-lic-section-gap: 60px;
        --et-lic-section-pad-y: 60px;
        --et-lic-section-half: calc(var(--et-lic-section-gap) / 2);
    }
}

@media screen and (max-width: 767px) {
    .et-license,
    .main--license {
        --et-lic-shell-x: 18px;
        --et-lic-section-x: 18px;
        --et-lic-section-gap: 36px;
        --et-lic-section-pad-y: 36px;
        --et-lic-section-half: calc(var(--et-lic-section-gap) / 2);
    }
}
"""

MARKER = ".et-license__hero-wrap.et-home {"


def main() -> None:
    if not SOURCE.exists():
        print(f"Skip: add {SOURCE.name} then re-run to generate license.css")
        return

    css = SOURCE.read_text(encoding="utf-8-sig", errors="replace")

    for old, new in REPLACEMENTS:
        css = css.replace(old, new)

    if HOME_V2_BREAKPOINTS.strip() not in css:
        if MARKER in css:
            css = css.replace(MARKER, HOME_V2_BREAKPOINTS + "\n" + MARKER, 1)
        else:
            css = HOME_V2_BREAKPOINTS + "\n" + css

    OUTPUT.write_text(css, encoding="utf-8")
    print(f"Wrote {OUTPUT} ({len(css.splitlines())} lines)")


if __name__ == "__main__":
    main()
