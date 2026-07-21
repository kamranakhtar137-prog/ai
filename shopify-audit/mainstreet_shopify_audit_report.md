# Developer Audit — Main Street Collectibles

Store: https://mainstreetnewcanaan.com/  ·  Location: 102 Main Street, New Canaan CT 06840

---

## 1. In-Store Pickup Configuration

### Current state

- Local pickup is ALREADY live at checkout: shows "102 Main St, 1st FL LE, New Canaan CT 06840", "Usually ready in 24 hours", FREE. Tested products are pickup-only (no shipping option shown).
- Product pages do NOT use Shopify's native "Pickup availability" widget — only plain text "Available in store only".
- "Store Pickup" nav link points to the "ONLINE STORE" collection (15 products); other collections not verified for pickup / inventory-location assignment.

### Backend (admin) findings

- Shipping & delivery -> Pickup (102 Main St):
  - Location status: ON (customers can pick up orders at this location).
  - Expected pickup date: "Usually ready in 24 hours".
  - Ready-for-pickup notification: custom message set - "Bring your photo id along with confirmation email when you come to collect your order. All orders must match name and photo ID".
  - Store transfers: unavailable (requires multiple active locations).
- Locations:
  - Using 1 of 10 active locations.
  - 102 Main St - POS subscription: POS Lite - Status: Active.

### Work remaining

- Verify pickup + inventory location assigned across all key collections; make each shoppable.
- Enable native pickup-availability block on product pages (earlier visibility of pickup + ready time).
- Confirm pickup prep-time and "ready for pickup" notification email.
- End-to-end checkout test per key collection; client documentation.

![Frontend: checkout delivery showing pickup at 102 Main St (FREE, ready in 24h)](audit_checkout_delivery.png)
![Frontend: product page shows only "Available in store only"](audit_product_pickup_widget.png)
![Backend: In-store pickup for 102 Main St - status ON, ready in 24h, ready-for-pickup message](backend/backend_pickup_config.png)
![Backend: Locations - 1 of 10 active; 102 Main St (POS Lite, Active)](backend/backend_locations.png)

---

## 2. Store Cleanup

### Current state

- Theme is clean but homepage is very sparse — logo + email signup only; no hero, featured products, or collections grid.
- Navigation labeling is confusing ("Store Pickup" → "ONLINE STORE"); no "Shop All" discovery path.
- Footer is minimal — no phone/email, hours not repeated, no shipping/FAQ links.

### Backend (admin) findings

- Collections (admin) - product count and whether shown on the website:
  - ONLINE STORE - 15 products - shown on site.
  - Sports - 1,027 products (auto: title does not contain Pokemon) - NOT shown on site.
  - Pokemon - 216 products (auto: title contains Pokemon) - NOT shown on site.
  - Consulting - 1 product - shown on site.
  - Tcg CARD - 1 product - shown on site.
  - Tcg - 1 product - not shown on site.
  - GIFT CARDS - 0 products (empty) - shown on site.
  - WNBA - 0 products (empty) - shown on site.
  - Basketball - 0 products (empty) - shown on site.
  - Baseball - 0 products (empty) - shown on site.
  - Issue: the two collections that actually hold inventory (Sports, Pokemon) are hidden, while four empty collections are shown. Hide/merge the empty ones and surface Sports/Pokemon.
- Main menu items:
  - Home
  - Sell your Cards
  - Store Pickup
  - Gift cards
  - Reviews
  - Hours and Info
  - Contact
  - Trading Card Donations
  - Note: no clear "Shop / All products" entry, and "Store Pickup" points to the ONLINE STORE collection - relabel and add a proper shopping path.
- Content -> Blog posts:
  - No blog posts published.
  - Optional: publish a few posts for SEO/community, or remove blog links so the storefront stays tidy.

### Work remaining

- Build out homepage (hero, featured collections/products, CTAs).
- Hide/merge empty collections (Baseball, Basketball, WNBA, GIFT CARDS) and surface stocked ones (Sports, Pokemon); add collection images + descriptions.
- Restructure/relabel nav + add shop discovery.
- Complete footer (contact, hours, policy/FAQ links).
- Review core settings/config (checkout, notifications, SEO/meta, favicon, policies) and QA broken elements cross-device.

![Frontend: homepage is sparse (logo + email signup only)](audit_homepage_top.png)
![Frontend: collections index](audit_collections_page.png)
![Backend: Collections - stocked Sports (1,027) & Pokemon (216) hidden; empty collections shown](backend/backend_collections.png)
![Backend: Main menu items](backend/backend_main_menu.png)
![Backend: Blog posts - none published](backend/backend_blog_posts.png)

---

## 3. Buying Intake Form

### Current state

- A structured selling intake ALREADY exists inside the "15 Minute Trading Card Consultation" (Easy Appointment Booking).
- The booking currently collects:
  - Card details (required free-text): "Please provide a description of your cards and value / asking price" - guides Year, Set, Sport, Condition, Grading Company, Grade, Cert Number, price.
  - First name, Last name, Email, Phone.
  - Appointment date/time (15-minute slots).
- Separate generic Contact form (Name, Email, Phone, Comment) — not an intake form.

### Backend (admin) findings

- Installed apps (8):
  - Testimonials Master - collects/displays customer testimonials.
  - Forms - build custom forms (can power a structured selling intake form).
  - Easy Appointment Booking - runs the consultation scheduling + intake question (core of the buying workflow).
  - Koin - store credit / loyalty (role to confirm in admin).
  - Flow - Shopify automation (can route/notify on new intake submissions).
  - CardDealerPro - trading-card buying / inventory management.
  - Retail Barcode Labels - POS barcode label printing.
  - Messaging - customer messaging / inbox.
- Relevant to the intake form: Forms + Easy Appointment Booking can build it (no new app needed); CardDealerPro and Flow can route submissions into the existing buying workflow.

### Work remaining (align to buying workflow + better structure)

- Confirm requirements/workflow with client.
- Convert the single free-text field into structured inputs via Easy Appointment Booking custom questions / Forms:
  - Sport (dropdown)
  - Grading company (dropdown: PSA / BGS / SGC / CGC / Raw)
  - Grade
  - Cert number
  - Quantity
  - Condition
  - Optional card-image upload
- Ensure details are captured pre-appointment and land somewhere usable (order notes / metafields / email / CardDealerPro / Flow) — schema + validation.
- Test end-to-end (incl. edge cases) + client review.

![Frontend: existing structured intake field inside booking](audit_booking_form_details.png)
![Backend: installed apps (Forms, Easy Appointment Booking, CardDealerPro, Flow, ...)](backend/backend_installed_apps.png)

---
