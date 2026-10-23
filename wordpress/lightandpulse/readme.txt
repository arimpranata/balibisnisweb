=== Lightandpulse ===
Contributors: lightandpulse
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
License: GPLv2 or later

The lightandpulse.com website theme: built for Elementor and WooCommerce, with a built-in client finder and CRM.

== Description ==

= One-click website setup =
Appearance → Lightandpulse setup creates the whole lightandpulse.com site:
* Pages: Home, Services, Packages, About, Insights (blog) and Contact, built as real Elementor pages you can edit.
* Main menu (with a "Start a project" button) and footer menu.
* Site title and tagline, homepage and blog page, clean permalinks.
* Elementor Site Settings: brand colours, Switzer + JetBrains Mono fonts, 1200px content width.
* Pages keep their design even when Elementor is switched off.
* The starter copy has no client names, testimonials, prices or statistics. Add yours in Elementor.

= Theme =
* Clean, minimal base styles, so Elementor's Site Settings (global colours, fonts, buttons) stay in charge.
* Elementor Pro Theme Builder: header, footer, single and archive locations are all registered.
* Free Elementor: the "Elementor Canvas" and "Elementor Full Width" page templates work out of the box.
* WooCommerce support (product gallery zoom, lightbox and slider).
* Customizer → Lightandpulse: accent colour and a floating WhatsApp button.

= Leads (wp-admin → Leads) =
* Find clients: search real Bali businesses on OpenStreetMap (free, no API key) or Google Maps (optional API key).
* Website audit: scores how much a prospect's website could use your help (HTTPS, mobile, outdated WordPress, Wix, WhatsApp button, SEO basics, speed).
* Pipeline: table and drag-and-drop board, priority, deal value (IDR), services, follow-up dates, activity log.
* Connect: WhatsApp and email templates in Bahasa Indonesia and English, filled with each lead's details and audit findings.
* Import and export CSV.
* Follow-ups due appear as a badge on the Leads menu and in a WordPress dashboard widget.

= Website lead form =
* Elementor widget "Lightandpulse Lead Form", or the shortcode [lightandpulse_lead_form].
* Enquiries arrive in Leads as hot leads, with an email notification.
* Spam protection (honeypot, timing check, rate limit) that works with LiteSpeed and other page caches.
* Shortcode options: [lightandpulse_lead_form button="Get a quote" services="no" success="Thanks!"]

== Installation ==

1. In wp-admin go to Appearance → Themes → Add New → Upload Theme and choose lightandpulse.zip.
2. Activate the theme. The Leads tables are created automatically and administrators get access.
3. Go to Leads → Settings and check your name, agency, WhatsApp number and email.
4. Install Elementor (free) from Plugins → Add New if it isn't installed yet.

== Data ==

Leads are stored in their own database tables (wp_lp_leads, wp_lp_activities, wp_lp_templates). Switching to another theme hides the Leads menu but never deletes your data; switching back brings everything back.

== Changelog ==

= 1.2.0 =
* New quiet, editorial dark design: near-black page divided by hairline rules, square corners, monospace labels, Switzer type with an italic serif accent word, single coral accent, ASCII pulse art in the hero.
* Fonts: Switzer via the Fontshare API (its licence does not allow bundling), JetBrains Mono and Instrument Serif via Google Fonts.
* Elementor Site Settings updated to the new palette and fonts. Run Appearance → Lightandpulse setup again with "Overwrite" ticked to apply the new homepage layout.

= 1.1.0 =
* New visual design: night-navy and sunset-orange palette, animated hero, glass header, dark footer.
* One-click website setup with Elementor-editable pages, menus and Site Settings.
* Name changed to Lightandpulse; message templates now speak as the agency.
* Website form enquiries with a website address are audited automatically.

= 1.0.0 =
* First release.
