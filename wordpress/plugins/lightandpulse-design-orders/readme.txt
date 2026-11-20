=== Lightandpulse Design Orders ===
Contributors: lightandpulse
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.3.1
License: GPLv2 or later

Design order system for posters, menus, flyers, backdrops and business cards, with a branded portal page for every client, proof approval, and direct download links with QR codes.

== Description ==

= Order form =
* Shortcode `[lightandpulse_design_order]`. A page "Order Desain" with the form is created on activation.
* Step 1, business & branding: business name, contact, WhatsApp, office phone, email, Instagram, website, optional address, logo, three brand colours, brand font, tagline, the standard contact block printed on designs, brand notes.
* Step 2, design type (shaped by real hotel and restaurant work): Poster, Flyer, Menu, Banner & signage, Backdrop, Business card, Greeting card, TV & social media, Room directory.
* Step 3, details for that type, for example:
  * Poster & flyer: several sizes at once (A5–A2, DL, standing frame 67×92, Instagram, TV, custom), orientation, sides/folds (flyer), finishing (poster), where it is used, quantity; events table (name, date, time, venue, price, description), inclusions, event set menu (course, dish, description), price basis (++ / net / free), kids or group terms.
  * Menu: kind (food, beverage, wine & spirits, set, happy hour, kids, in-room dining, spa), format, size (A4, A5, 18×29.7, 16×30, 15×30, DL, custom), pages, material; items table (category, name, ingredients, price, second price e.g. bottle, label); currency, price style (thousands or full), tax note and percentage, photos.
  * Banner & signage: table of pieces (X-banner, roll-up, standing frame, banner, trolley/booth or door sticker, signage; placement, width, height, quantity), material, indoor/outdoor, scope.
  * Backdrop: size in metres, material, scope, event, venue, date, main text, elements, names, hashtag, sponsors table.
  * Business card: people table (name, title, phone, email), shared info, size, sides, orientation, paper, finishing, QR, boxes.
  * Greeting card: occasion (Christmas, New Year, Nyepi, Galungan, Eid, Chinese New Year, 17 August…), formats (printed card, Instagram, WhatsApp, e-card), greeting, message, sign-off and signature.
  * TV & social media: formats (TV landscape/portrait, Instagram post/story, Facebook, web banner, email header, WhatsApp), screen, still or animated, slides table (title, text, price, seconds).
  * Room directory: format, pages, sections, facilities & opening hours table, phone extensions table, Wi-Fi, existing texts.
  * Shared where it fits: outlet/venue and outlet or partner logos, promo items table (category, name, description, price, promo price), validity and terms, headline/body/call to action, contact block (standard, custom or none) and QR target, reference images, assets, notes.
  * File fields take many files, picked in several goes or dragged in, with previews and a remove button; the deadline is set by the team in wp-admin.
* Follow-up fields appear only when relevant (e.g. custom size only when "Custom" is chosen).
* Uploads: JPG, PNG, WebP, GIF, PDF, ZIP, TXT, CSV, DOC(X), XLS(X); size limit set in Settings.
* Spam protection without a nonce, so it works on cached pages (honeypot, timing check, rate limit).

= Client portal =
* Every client gets a page `/client/{business}/{secret-key}/` with their logo and brand colours.
* Order status with progress steps, the full brief, proofs to approve or send back with revision notes and attachments.
* Final files with a direct download link, "copy link", and a QR code for each file (scan to download), plus a ZIP of all files with its own QR.
* Floating buttons open side panels: Brand kit (logo on light and dark, colours with hex codes, font, standard contact block, and a form to update business & branding) and Link & QR for the portal itself.
* Clients place new orders from their portal without re-entering business details, or press "Duplikat" on an earlier order to start from its brief and files.
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
= 1.3.1 =
* Removed target audience and design language fields.

= 1.3.0 =
* Portal: Brand kit (with business & branding editing) and Link & QR move into floating buttons that open side panels; orders from the portal no longer repeat step 1.
* "Duplikat" on every order starts a new order from that brief, reusing its files unless new ones are uploaded.
* Removed style, colour/logo choice, previous design, things to avoid, reference links, print quantities and menu material from the form.
* Light (e.g. white) logos get a dark background automatically.

= 1.2.0 =
* New design types: Banner & signage, Greeting card, TV & social media, Room directory.
* Posters and flyers: several sizes per order, events table, inclusions, event set menu, price basis.
* Menus: menu kind, hotel menu sizes, second price column, tax percentage.
* Shared: outlet/venue and partner logos, promo items table, standard contact block from branding, QR target.

= 1.1.0 =
* Per-type tables (menu items, people, line-up, products, sponsors) instead of free text.
* Multi-file drop zones that keep adding files, with previews and limits checked before sending.
* Removed the files/schedule/budget section from the form; the deadline stays in wp-admin.
* Cleaner form and portal design; the portal background no longer takes the client's second colour.

= 1.0.0 =
* First release.
