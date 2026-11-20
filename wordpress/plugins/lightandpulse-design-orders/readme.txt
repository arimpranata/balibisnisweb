=== Lightandpulse Design Orders ===
Contributors: lightandpulse
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Design order system for posters, menus, flyers, backdrops and business cards, with a branded portal page for every client, proof approval, and direct download links with QR codes.

== Description ==

= Order form =
* Shortcode `[lightandpulse_design_order]`. A page "Order Desain" with the form is created on activation.
* Step 1, business & branding: business name, contact, WhatsApp, email, Instagram, website, optional address, logo, three brand colours, brand font, tagline, brand notes.
* Step 2, design type: Poster, Menu, Flyer, Backdrop or Business card.
* Step 3, details for that type, for example:
  * Poster: size (A4–A0, 50×70, Instagram, custom), orientation, event date/time/place, QR code and its link, finishing, quantity.
  * Menu: format (single, bi-fold, tri-fold, booklet, board, table tent, digital/QR), size, pages, categories, item list or file upload, currency, tax/service note, dietary icons, food photos, material, quantity.
  * Flyer: size (A5, A4, DL, 1/3 A4, square, story, custom), sides, folds, channels, offer, validity, terms, quantity.
  * Backdrop: width × height in metres, indoor/outdoor, material, event, date, venue, elements (sponsor logos, photo booth, step-and-repeat, names, hashtag, QR), sponsor logo uploads, scope (design / print / install).
  * Business card: one person per line (name | title | phone | email), size, sides, orientation, paper, finishing, back side, QR (website, WhatsApp, vCard), boxes per person.
  * For every type: purpose, audience, languages, headline, subheadline, body, call to action, contact details to print, style, use of brand colours, things to avoid, reference links and images, photos and assets, output files, number of concepts, printing, deadline, priority, budget, notes.
* Follow-up fields appear only when relevant (e.g. custom size only when "Custom" is chosen).
* Uploads: JPG, PNG, WebP, GIF, PDF, ZIP, TXT, CSV, DOC(X), XLS(X); size limit set in Settings.
* Spam protection without a nonce, so it works on cached pages (honeypot, timing check, rate limit).

= Client portal =
* Every client gets a page `/client/{business}/{secret-key}/` with their logo and brand colours.
* Order status with progress steps, the full brief, proofs to approve or send back with revision notes and attachments.
* Final files with a direct download link, "copy link", and a QR code for each file (scan to download), plus a ZIP of all files with its own QR.
* Brand kit (logo, colours with hex codes, font) and a QR code for the portal itself.
* Clients can place new orders from their portal with their details pre-filled.
* Not indexed, not cached, no third-party fonts or scripts.

= Admin (Design Orders menu) =
* Orders: status, type, deadline, client, editable brief, proofs and final files from the Media Library (adding them moves the status to "Waiting for approval" / "Done"), WhatsApp button with the portal link, history log, download counts.
* Clients: details, branding, logo (Featured image), portal link and QR, new secret link on demand.
* Email notifications to the team and the client.
* If the Lightandpulse theme's Leads CRM is active, every new client becomes a hot lead and each order is logged on it.

= Personal data (UU 27/2022 / UU PDP) =
* Notice in Bahasa Indonesia with the 7 elements of Pasal 21, link to the Privacy Policy, and a separate, unticked consent for promotions.
* Business cards: a required statement that every person listed agreed to have their data printed.
* Consent records (time, notice version, purposes) on every order.
* WordPress Tools → Export / Erase Personal Data support.
* Automatic anonymisation after N months without activity (Settings, default 24; 0 = off).
* Suggested Privacy Policy text in Settings → Privacy.
* Emails contain links, not personal data.

== Installation ==
1. Upload the plugin zip in Plugins → Add New → Upload Plugin, then activate.
2. Make sure Settings → Permalinks is not "Plain" (portal addresses need pretty permalinks).
3. Check Design Orders → Pengaturan: agency name, notification email, file size limit, retention, privacy policy URL.
4. Add the "Order Desain" page to your menu, or put `[lightandpulse_design_order]` on any page.

== Credits ==
QR codes are generated on your server with QR Code Generator by Kazuhiko Arase (MIT licence), in vendor/qrcode-generator.

== Changelog ==
= 1.0.0 =
* First release.
