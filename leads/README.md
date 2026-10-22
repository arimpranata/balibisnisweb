# Lightandpulse Leads

A private lead-finder and mini CRM for **Lightandpulse Digital Agency**, a Bali agency run by Ida Bagus Arim Pranata that builds websites with WordPress, Elementor and WooCommerce.

It does three jobs:

1. **Search:** find real Bali businesses by category and area. **OpenStreetMap is the default: free, with no API key or billing.** Google Maps (Places API) is optional for review counts. Results are ranked so reachable businesses without a website come first, and businesses that only use Instagram, Wix or Booking.com are flagged.
2. **Manage:** a pipeline from New to Won/Lost, with table and drag-and-drop board views, priority, deal value in IDR, services to pitch, tags, follow-up dates, an activity timeline, and CSV import/export.
3. **Connect:** one-click WhatsApp (`wa.me`) and email outreach using Bahasa Indonesia and English templates. Templates fill in the lead's details and **website audit findings** automatically. Sending logs the message, moves the lead to *Contacted* and schedules a follow-up.

### Website audit (opportunity score 0–100)
The audit fetches a prospect's homepage and detects: no HTTPS, not mobile-friendly, the platform (WordPress version, Elementor, WooCommerce, Wix, Squarespace, Shopify, Blogger…), outdated WordPress or jQuery, a missing WhatsApp button, missing SEO basics (title, meta description, H1, OG tags, schema), no analytics, slow responses, an old copyright year, and single-language pages. Each finding maps to a service you sell. A higher score means more room to help.

## Requirements
- PHP 8.1+ with `pdo_sqlite` and `curl` (standard on cPanel hosting)
- No Composer, no build step, no MySQL

## Install
1. Upload the `leads/` folder to your hosting, ideally on a subdomain such as `crm.lightandpulse.com`, served over HTTPS.
2. Make sure `leads/data/` is writable by PHP. The SQLite database is created there automatically.
3. Open the URL. The first-run screen asks for your profile and a password.
4. *Find clients* works straight away with OpenStreetMap (free, no key). Your hosting must allow outgoing HTTPS to `overpass-api.de`, which almost all hosts do.
5. Optional: add a **Google Places API (New)** key in *Settings* to also search Google Maps with ratings and review counts. Google requires a billing account, even within its monthly free credit.

Local test: `php -S 127.0.0.1:8000 -t leads` and open http://127.0.0.1:8000.

## Security notes
- Single-user password login (hashed) with lockout after 5 failed attempts. Every form is CSRF-protected and all queries use prepared statements.
- Website audits refuse private, reserved and loopback addresses on every redirect hop, so the audit can't be used to probe your server's network.
- `data/`, `src/` and `views/` are blocked from the web by `.htaccess` (Apache/LiteSpeed). **On Nginx**, add `location ~ ^/(data|src|views)/ { deny all; }`.
- Back up `data/leads.sqlite` regularly.
