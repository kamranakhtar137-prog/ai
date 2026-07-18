#!/usr/bin/env python3
"""Apply responsive breakpoint tokens to Eggs Time home-v2.css."""

from pathlib import Path

SOURCE = Path(__file__).parent / "css" / "home-v2-source.css"
OUTPUT = Path(__file__).parent / "css" / "home-v2.css"

OLD_BREAKPOINTS = """@media screen and (min-width: 768px) {
    .et-home {
        --et-home-section-gap: 72px;
    }
}

@media screen and (min-width: 1200px) {
    .et-home {
        --et-home-section-gap: 80px;
    }
}"""

NEW_BREAKPOINTS = """@media screen and (min-width: 768px) {
    .et-home {
        --et-home-section-x: 24px;
        --et-home-section-gap: 44px;
        --et-home-section-padding-y: 44px;
    }
}

@media screen and (min-width: 1200px) {
    .et-home {
        --et-home-section-x: 32px;
        --et-home-section-gap: 52px;
        --et-home-section-padding-y: 52px;
    }
}

@media screen and (min-width: 1440px) {
    .et-home {
        --et-home-max: 1420px;
        --et-home-section-x: 44px;
        --et-home-section-gap: 60px;
        --et-home-section-padding-y: 60px;
    }
}"""

UNIFIED_SECTION_RULES = """
/* ----- Responsive breakpoint tokens (10 homepage sections) ----- */
.main--home-v2 .et-home__hero.et-home__hero--split-video .et-home__hero-inner,
.main--home-v2 .et-home__bg-band--playful .et-home__characters .et-home__section-inner,
.main--home-v2 .et-home__bg-band--playful .et-home__best-sellers .et-home__section-inner,
.main--home-v2 #et-home-stories .et-home__section-inner,
.main--home-v2 #et-home-youtube .et-home__section-inner,
.main--home-v2 #et-home-products .et-home__section-inner,
.main--home-v2 .et-home__fun-egg .et-home__section-inner,
.main--home-v2 .et-home__parent-benefits .et-home__section-inner,
.main--home-v2 #et-home-social .et-home__section-inner,
.main--home-v2 .et-home__distributor .et-home__section-inner,
.main--home-v2 .et-home__cta .et-home__cta-inner {
    padding-left: var(--et-home-section-x);
    padding-right: var(--et-home-section-x);
    padding-top: var(--et-home-section-padding-y);
    padding-bottom: var(--et-home-section-padding-y);
}

"""

FINAL_CASCADE_MARKER = "/* ----- Tight heading junctions (final cascade wins) ----- */"


def update_et_home_root(css: str) -> str:
    css = css.replace(
        "    --et-home-section-x: 20px;",
        "    --et-home-section-x: 18px;",
        1,
    )
    css = css.replace(
        "    --et-home-section-gap: 64px;",
        "    --et-home-section-gap: 36px;",
        1,
    )
    insert_after = "    --et-home-section-spacing: var(--et-home-section-top-spacing);\n"
    padding_y_line = "    --et-home-section-padding-y: 36px;\n"
    if padding_y_line.strip() not in css:
        css = css.replace(insert_after, insert_after + padding_y_line, 1)
    return css


def replace_global_padding(css: str) -> str:
    replacements = [
        ("padding-top: 100px !important", "padding-top: var(--et-home-section-padding-y) !important"),
        ("padding-bottom: 100px !important", "padding-bottom: var(--et-home-section-padding-y) !important"),
        ("padding-top: 100px", "padding-top: var(--et-home-section-padding-y)"),
        ("padding-bottom: 100px", "padding-bottom: var(--et-home-section-padding-y)"),
    ]
    for old, new in replacements:
        css = css.replace(old, new)
    return css


def update_mobile_et_home_vars(css: str) -> str:
    css = css.replace(
        "        --et-home-section-x: 16px;",
        "        --et-home-section-x: 18px;",
    )
    return css


def replace_mobile_section_padding(css: str) -> str:
    marker = FINAL_CASCADE_MARKER
    idx = css.find(marker)
    if idx == -1:
        raise ValueError(f"Missing marker: {marker}")

    before = css[:idx]
    after = css[idx:]

    after = after.replace(
        "        padding-top: 18px !important;",
        "        padding-top: var(--et-home-section-padding-y) !important;",
    )
    after = after.replace(
        "        padding-bottom: 18px !important;",
        "        padding-bottom: var(--et-home-section-padding-y) !important;",
    )

    return before + after


def main() -> None:
    css = SOURCE.read_text(encoding="utf-8-sig", errors="replace")

    css = css.replace("Eggs Time \u00e2\u20ac\u201d", "Eggs Time \u2014")
    css = css.replace("Eggs Time \u00e2\u20ac\u2014", "Eggs Time \u2014")

    css = update_et_home_root(css)

    if OLD_BREAKPOINTS not in css:
        raise ValueError("Expected old breakpoint block not found")
    css = css.replace(OLD_BREAKPOINTS, NEW_BREAKPOINTS)

    css = replace_global_padding(css)
    css = update_mobile_et_home_vars(css)

    if FINAL_CASCADE_MARKER not in css:
        raise ValueError(f"Missing marker: {FINAL_CASCADE_MARKER}")
    css = css.replace(FINAL_CASCADE_MARKER, UNIFIED_SECTION_RULES + FINAL_CASCADE_MARKER, 1)

    css = replace_mobile_section_padding(css)

    OUTPUT.write_text(css, encoding="utf-8")
    line_count = len(css.splitlines())
    print(f"Wrote {OUTPUT} ({line_count} lines)")


if __name__ == "__main__":
    main()
