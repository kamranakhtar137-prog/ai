#!/usr/bin/env python3
"""Smoke-test Main Street Collectibles checkout for in-store pickup."""

from __future__ import annotations

import json
import os
import re
import sys
import urllib.error
import urllib.request
from http.cookiejar import CookieJar

STORE = os.environ.get("MSC_STORE_URL", "https://mainstreetnewcanaan.com").rstrip("/")
DEFAULT_VARIANT = int(os.environ.get("MSC_TEST_VARIANT_ID", "46678279749822"))


def build_opener() -> urllib.request.OpenerDirector:
    jar = CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def main() -> int:
    print(f"Store: {STORE}")
    print(f"Test variant: {DEFAULT_VARIANT}")

    opener = build_opener()

    add_url = f"{STORE}/cart/add.js"
    payload = json.dumps({"id": DEFAULT_VARIANT, "quantity": 1}).encode()
    req = urllib.request.Request(
        add_url,
        data=payload,
        method="POST",
        headers={"Content-Type": "application/json"},
    )
    try:
        with opener.open(req, timeout=60) as resp:
            if resp.status != 200:
                print(f"FAIL: cart/add.js status {resp.status}")
                return 1
    except urllib.error.HTTPError as exc:
        print(f"FAIL: cart/add.js HTTP {exc.code}: {exc.read().decode()[:500]}")
        return 1

    print("OK: Added test line to cart")

    checkout_url = f"{STORE}/checkout?skip_shop_pay=true"
    try:
        with opener.open(checkout_url, timeout=60) as resp:
            html = resp.read().decode("utf-8", errors="replace")
            final_url = resp.geturl()
            status = resp.status
    except urllib.error.HTTPError as exc:
        print(f"FAIL: checkout HTTP {exc.code}")
        return 1

    print(f"Checkout HTTP {status} — {final_url}")

    methods_match = re.search(r'enabledDeliveryMethods&quot;:\[([^\]]*)\]', html)
    methods: list[str] = []
    if methods_match:
        raw = methods_match.group(1).replace("&quot;", '"')
        methods = json.loads(f"[{raw}]")

    pickup_empty = 'pickupPoint&quot;:[]' in html or '"pickupPoint":[]' in html
    pickup_has_data = bool(re.search(r'pickupPoint&quot;:\[\{', html))

    print(f"enabledDeliveryMethods: {methods or '(not found)'}")
    if pickup_has_data:
        print("pickupPoint: at least one location present")
    elif pickup_empty:
        print("pickupPoint: EMPTY (no pickup locations exposed at checkout)")
    else:
        print("pickupPoint: could not determine from HTML")

    if "PICK_UP" in methods and pickup_empty:
        print(
            "\nRESULT: MISCONFIGURED — checkout allows pickup only but lists zero pickup locations."
        )
        print(
            "Action: complete admin setup in "
            "docs/main-street-collectibles/in-store-pickup-configuration.md §3"
        )
        return 1

    if "PICK_UP" in methods and pickup_has_data:
        print("\nRESULT: PASS — pickup delivery with at least one location.")
        return 0

    if "SHIPPING" in methods and not pickup_has_data:
        print("\nRESULT: WARN — shipping enabled; verify pickup if dual fulfillment is required.")
        return 0

    print("\nRESULT: REVIEW — unexpected checkout delivery configuration.")
    return 1


if __name__ == "__main__":
    sys.exit(main())
