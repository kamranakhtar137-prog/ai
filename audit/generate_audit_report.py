#!/usr/bin/env python3
"""Generate Main Street Collectibles Shopify audit Word document."""

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.shared import Inches, Pt, RGBColor
from datetime import date


def add_heading(doc, text, level=1):
    return doc.add_heading(text, level=level)


def add_bullet(doc, text, bold_prefix=None):
    p = doc.add_paragraph(style="List Bullet")
    if bold_prefix:
        run = p.add_run(bold_prefix)
        run.bold = True
        p.add_run(text)
    else:
        p.add_run(text)
    return p


def build_report():
    doc = Document()

    # Title block
    title = doc.add_heading("Main Street Collectibles", 0)
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle = doc.add_paragraph("Shopify Developer Audit & Scope Estimate")
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle.runs[0].font.size = Pt(14)
    subtitle.runs[0].font.color.rgb = RGBColor(0x55, 0x55, 0x55)

    meta = doc.add_paragraph()
    meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    meta.add_run(f"Site: https://mainstreetnewcanaan.com/\n")
    meta.add_run(f"Audit date: {date.today().strftime('%B %d, %Y')}\n")
    meta.add_run("Audit method: Live storefront review (theme, navigation, collections, products, apps, policies)")

    doc.add_paragraph()

    # Executive Summary
    add_heading(doc, "Executive Summary", 1)
    doc.add_paragraph(
        "Main Street Collectibles is a live Shopify store on the Horizon theme with 265 products, "
        "a working consultation/selling product, and Easy Appointment Booking (Servicify) installed. "
        "The storefront is functional but still reflects initial setup: pickup is not configured at checkout, "
        "navigation and collections are misaligned, catalog quality is uneven, and the selling workflow lacks "
        "a structured pre-appointment intake form."
    )
    doc.add_paragraph(
        "Estimated effort for the full scope below: 55–75 developer hours (approximately 7–10 working days), "
        "plus client review cycles. Admin access is required to complete pickup configuration, app settings, "
        "and theme changes."
    )

    # Current State
    add_heading(doc, "Current State Assessment", 1)

    add_heading(doc, "Theme", 2)
    add_bullet(doc, "Shopify Horizon theme (theme store ID 2481), default configuration with minimal customization.")
    add_bullet(doc, "Homepage features 4 products from the ONLINE STORE collection; no hero messaging or sell/pickup CTAs.")
    add_bullet(doc, "Brand assets present (logo, favicon, Instagram link). Inter font, standard color scheme.")
    add_bullet(doc, "Footer includes Facebook, Instagram, and policy links.")

    add_heading(doc, "Navigation", 2)
    add_bullet(doc, "Home → /", bold_prefix="")
    add_bullet(doc, "/products/consultation (selling consultation product — not a dedicated intake page)", bold_prefix="Sell your Cards → ")
    add_bullet(doc, "/collections/online-store (15 shippable sealed products — NOT pickup inventory)", bold_prefix="Store Pickup → ")
    add_bullet(doc, "/products/msc-gift-card (collection /collections/gift-cards is empty)", bold_prefix="Gift cards → ")
    add_bullet(doc, "External Google Maps reviews URL (leaves site)", bold_prefix="Reviews → ")
    add_bullet(doc, "/pages/hours-and-info", bold_prefix="Hours and Info → ")
    add_bullet(doc, "/pages/contact (basic Shopify contact form)", bold_prefix="Contact → ")
    add_bullet(doc, "Critical: “Store Pickup” nav points to the wrong collection. The full catalog (265 items) displays as “Store Pickup Items” at /collections/all but is not linked from nav.")

    add_heading(doc, "Collections & Catalog", 2)
    add_bullet(doc, "265 total products; 15 in ONLINE STORE (sealed/shippable); ~250 graded singles and inventory items not in ONLINE STORE.")
    add_bullet(doc, "Empty or unused collections: Baseball, Basketball, WNBA, GIFT CARDS (0 products).")
    add_bullet(doc, "66 products missing images; 231 products missing descriptions.")
    add_bullet(doc, "263 of 265 variants marked requires_shipping: true (pickup not product-level ready).")

    add_heading(doc, "Settings & Configuration", 2)
    add_bullet(doc, "Custom domain active; Shopify Payments enabled; Google Analytics / GTM installed.")
    add_bullet(doc, "Shipping policy returns 404 — not configured.")
    add_bullet(doc, "Sitemap (/sitemap.xml) returns 500 error.")
    add_bullet(doc, "Apple Pay / checkout metadata shows shippingType: \"shipping\" only — no local pickup exposed.")
    add_bullet(doc, "Store address referenced correctly in announcement bar: 102 Main Street, New Canaan CT 06840.")

    add_heading(doc, "Pickup Flow", 2)
    add_bullet(doc, "Local pickup is NOT live. Checkout is shipping-only from storefront signals.")
    add_bullet(doc, "No dedicated pickup collection handle; /collections/all titled “Store Pickup Items” but functions as the full catalog.")
    add_bullet(doc, "ONLINE STORE collection (15 items) appears intended for shipped sealed product, separate from in-store pickup inventory.")
    add_bullet(doc, "Pickup location (102 Main Street) referenced on site but not configured as a Shopify pickup point in checkout.")

    add_heading(doc, "Buying / Selling Workflow", 2)
    add_bullet(doc, "15 Minute Trading Card Consultation product exists with thorough selling copy ($0.00).")
    add_bullet(doc, "Easy Appointment Booking (Servicify / digital-appointments) installed; booking widget renders on consultation product.")
    add_bullet(doc, "App embed also loads on standard product pages — should be scoped to consultation/selling only.")
    add_bullet(doc, "No structured intake form for item details (card type, quantity, graded/raw, photos, estimated value) before appointment.")
    add_bullet(doc, "Workflow today: nav → consultation product → book appointment. No data capture for buyer inventory ahead of visit.")

    add_heading(doc, "Schema & Data Requirements (Buying Intake)", 2)
    doc.add_paragraph("Recommended intake fields for buyer workflow:")
    fields = [
        "Contact: name, email, phone",
        "Appointment preference / link to Servicify booking",
        "Collection size (single items vs. small lot vs. large collection)",
        "Card details: sport/category, player, year, set, card number",
        "Condition: raw vs. graded (with grade if applicable)",
        "Estimated quantity and approximate value range",
        "Photo upload (front/back or stack photo)",
        "Notes: rookies, autos, numbered, vintage flags",
        "Acknowledgment of buying criteria (graded 8+, ~$20+ value, etc.)",
    ]
    for f in fields:
        add_bullet(doc, f)
    doc.add_paragraph(
        "Data should flow to merchant (email notification, Shopify customer record, or Servicify appointment notes) "
        "and be visible to staff before the 15-minute consultation."
    )

    # Issues table
    add_heading(doc, "Key Issues Found", 1)
    table = doc.add_table(rows=1, cols=2)
    table.style = "Table Grid"
    hdr = table.rows[0].cells
    hdr[0].text = "Issue"
    hdr[1].text = "Severity"
    issues = [
        ("Local pickup not configured in Shopify admin/checkout", "High"),
        ("Store Pickup nav links to ONLINE STORE (wrong collection)", "High"),
        ("No buying intake form; appointment booking only", "High"),
        ("Servicify app embed on all product pages", "Medium"),
        ("66 products without images", "Medium"),
        ("231 products without descriptions", "Medium"),
        ("Shipping policy missing (404)", "Medium"),
        ("Sitemap error (500)", "Low"),
        ("Empty sport/gift card collections in catalog", "Low"),
        ("Info page is logo-only placeholder", "Low"),
        ("Gift cards nav → product; collection empty", "Low"),
    ]
    for issue, sev in issues:
        row = table.add_row().cells
        row[0].text = issue
        row[1].text = sev

    doc.add_paragraph()

    # Subtask 1
    add_heading(doc, "Subtask 1: In-Store Pickup Configuration", 1)
    doc.add_paragraph("Scope: Set up collections as shoppable for in-store pickup, configure pickup for key collections, build purchase/pickup flow, set pickup location (102 Main Street), test checkout, document for client.")

    add_heading(doc, "Work Required", 2)
    tasks1 = [
        "Enable Shopify local pickup at 102 Main Street (Settings → Locations → Pickup in store).",
        "Define fulfillment strategy: ONLINE STORE = ship; pickup-eligible inventory = local pickup only (or pickup + optional ship if needed).",
        "Create or retitle dedicated Store Pickup collection(s) by category (e.g., graded singles, Pokémon, sports).",
        "Assign ~250 pickup-eligible products to correct collections; tag products for fulfillment rules.",
        "Configure shipping/pickup profiles so checkout offers “Pickup at 102 Main Street” for eligible items.",
        "Fix navigation: Store Pickup → correct collection(s), not ONLINE STORE.",
        "Add pickup messaging on collection and product pages (ready in X hours, bring ID, etc.).",
        "End-to-end test: add pickup item → checkout → select pickup → order confirmation.",
        "Write client-facing pickup documentation (how to buy, when to collect, mixed cart rules).",
    ]
    for t in tasks1:
        add_bullet(doc, t)

    add_heading(doc, "Estimate", 2)
    est1 = doc.add_table(rows=6, cols=2)
    est1.style = "Table Grid"
    rows1 = [
        ("Admin setup (location, pickup, shipping profiles)", "4–6 hrs"),
        ("Collection structure & product assignment", "5–8 hrs"),
        ("Nav, PDP/collection UX & messaging", "2–4 hrs"),
        ("Checkout testing & edge cases", "3–4 hrs"),
        ("Client documentation", "1–2 hrs"),
        ("Subtotal", "15–24 hrs"),
    ]
    for i, (a, b) in enumerate(rows1):
        est1.rows[i].cells[0].text = a
        est1.rows[i].cells[1].text = b
        if a == "Subtotal":
            for cell in est1.rows[i].cells:
                for p in cell.paragraphs:
                    for r in p.runs:
                        r.bold = True

    add_heading(doc, "Dependencies & Risks", 2)
    add_bullet(doc, "Requires Shopify admin access and POS/location already set for 102 Main Street.")
    add_bullet(doc, "Mixed carts (ship + pickup) need a clear business rule to avoid checkout confusion.")
    add_bullet(doc, "Large catalog (265 SKUs) — bulk tagging/collection rules recommended over manual one-by-one.")

    # Subtask 2
    add_heading(doc, "Subtask 2: Store Cleanup", 1)
    doc.add_paragraph("Scope: Tidy theme, navigation, and settings from initial setup. Fix nav, clean settings, ensure storefront is presentable, fix broken elements.")

    add_heading(doc, "Work Required", 2)
    tasks2 = [
        "Reorganize navigation: correct pickup link, add Shop Online if desired, fix Reviews (on-site or labeled external).",
        "Homepage: hero section, featured collections, sell-cards CTA, store hours/location block.",
        "Remove or hide empty collections (Baseball, Basketball, WNBA, empty Gift Cards).",
        "Resolve gift card path (product vs. collection consistency).",
        "Create shipping policy; update refund policy for pickup orders if needed.",
        "Investigate and fix sitemap 500 error.",
        "Scope Servicify app embed to selling/consultation templates only.",
        "Product presentation pass: placeholder images for 66 missing images (client assets needed for final).",
        "Theme settings: announcement bar, footer, typography consistency.",
        "Full storefront QA (mobile + desktop).",
    ]
    for t in tasks2:
        add_bullet(doc, t)

    add_heading(doc, "Estimate", 2)
    est2 = doc.add_table(rows=6, cols=2)
    est2.style = "Table Grid"
    rows2 = [
        ("Navigation & information architecture", "3–5 hrs"),
        ("Homepage & theme polish", "4–6 hrs"),
        ("Collection/catalog cleanup", "3–4 hrs"),
        ("Policies, app embed fix, sitemap", "2–4 hrs"),
        ("QA & broken-element fixes", "3–5 hrs"),
        ("Subtotal", "15–24 hrs"),
    ]
    for i, (a, b) in enumerate(rows2):
        est2.rows[i].cells[0].text = a
        est2.rows[i].cells[1].text = b
        if a == "Subtotal":
            for cell in est2.rows[i].cells:
                for p in cell.paragraphs:
                    for r in p.runs:
                        r.bold = True

    add_heading(doc, "Dependencies & Risks", 2)
    add_bullet(doc, "Product photography/content for 66+ items depends on client turnaround.")
    add_bullet(doc, "Some cleanup overlaps with pickup work (nav, collections) — coordinate to avoid duplicate effort.")

    # Subtask 3
    add_heading(doc, "Subtask 3: Buying Intake Form", 1)
    doc.add_paragraph("Scope: Build customer selling intake form aligned to existing buying workflow. Integrate with Easy Appointment Booking (Servicify). Build form → collect item details → integrate appointment booking → test → client review.")

    add_heading(doc, "Work Required", 2)
    tasks3 = [
        "Confirm required fields with client (use schema list above as starting point).",
        "Build intake form (Shopify Forms, custom theme section, or form app — recommend based on photo upload needs).",
        "Create dedicated “Sell Your Cards” page with form → booking flow (form first, then schedule).",
        "Integrate with Servicify: pass intake data to appointment notes, customer metafields, or email notification.",
        "Update consultation product page to reflect two-step flow (intake + book).",
        "Configure staff notifications (email/Shopify Flow) with submitted item details.",
        "Test full journey: form submit → appointment book → merchant receives data.",
        "Client review and iteration on copy/fields.",
    ]
    for t in tasks3:
        add_bullet(doc, t)

    add_heading(doc, "Estimate", 2)
    est3 = doc.add_table(rows=6, cols=2)
    est3.style = "Table Grid"
    rows3 = [
        ("Requirements & form design", "3–4 hrs"),
        ("Form build (fields, validation, photo upload)", "5–8 hrs"),
        ("Servicify integration & data routing", "5–8 hrs"),
        ("Sell page UX & consultation product updates", "3–4 hrs"),
        ("Testing & client review cycle", "4–6 hrs"),
        ("Subtotal", "20–30 hrs"),
    ]
    for i, (a, b) in enumerate(rows3):
        est3.rows[i].cells[0].text = a
        est3.rows[i].cells[1].text = b
        if a == "Subtotal":
            for cell in est3.rows[i].cells:
                for p in cell.paragraphs:
                    for r in p.runs:
                        r.bold = True

    add_heading(doc, "Dependencies & Risks", 2)
    add_bullet(doc, "Servicify custom-field support limits may require Shopify Forms + manual staff review or Zapier/Flow middleware.")
    add_bullet(doc, "Photo uploads may need a form app (e.g., HulkApps, Powerful Form Builder) if native Shopify Forms is insufficient.")
    add_bullet(doc, "Client must approve field list and notification workflow before build is final.")

    # Full scope summary
    add_heading(doc, "Full Scope Summary", 1)
    summary = doc.add_table(rows=5, cols=3)
    summary.style = "Table Grid"
    summary.rows[0].cells[0].text = "Subtask"
    summary.rows[0].cells[1].text = "Hours (Low–High)"
    summary.rows[0].cells[2].text = "Notes"
    for cell in summary.rows[0].cells:
        for p in cell.paragraphs:
            for r in p.runs:
                r.bold = True
    data = [
        ("In-Store Pickup Configuration", "15–24 hrs", "Admin + catalog assignment heavy"),
        ("Store Cleanup", "15–24 hrs", "Partial overlap with pickup nav/collections"),
        ("Buying Intake Form", "20–30 hrs", "Servicify integration is main variable"),
        ("TOTAL (sequential)", "50–78 hrs", "~7–10 working days"),
    ]
    for i, (a, b, c) in enumerate(data, 1):
        summary.rows[i].cells[0].text = a
        summary.rows[i].cells[1].text = b
        summary.rows[i].cells[2].text = c
        if a.startswith("TOTAL"):
            for cell in summary.rows[i].cells:
                for p in cell.paragraphs:
                    for r in p.runs:
                        r.bold = True

    doc.add_paragraph()
    doc.add_paragraph(
        "Recommended approach: Run Store Cleanup and Pickup Configuration in parallel where possible (shared nav/collection work). "
        "Begin Buying Intake Form after client confirms field requirements. Add 1–2 days buffer for client review on each subtask."
    )

    add_heading(doc, "Recommended Execution Order", 1)
    order = [
        "1. Store Cleanup (nav, policies, app embed) — quick wins, improves live site immediately.",
        "2. In-Store Pickup Configuration — highest revenue impact for local customers.",
        "3. Buying Intake Form — depends on client field approval and Servicify integration testing.",
    ]
    for o in order:
        doc.add_paragraph(o, style="List Number")

    add_heading(doc, "Access Required", 1)
    for item in [
        "Shopify admin (staff or collaborator access)",
        "Theme editor access (Horizon)",
        "Easy Appointment Booking / Servicify app admin",
        "Client input on pickup vs. ship rules and intake form fields",
        "Product images/descriptions for catalog gaps (client-provided)",
    ]:
        add_bullet(doc, item)

    add_heading(doc, "Out of Scope (This Audit)", 1)
    for item in [
        "POS hardware setup",
        "Inventory migration or bulk import",
        "Custom theme development beyond Horizon settings",
        "SEO/marketing campaigns",
        "Payment method changes",
    ]:
        add_bullet(doc, item)

    doc.add_paragraph()
    p = doc.add_paragraph("— End of Report —")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER

    out = "/workspace/audit/Main_Street_Collectibles_Shopify_Audit.docx"
    doc.save(out)
    print(f"Saved: {out}")
    return out


if __name__ == "__main__":
    build_report()
