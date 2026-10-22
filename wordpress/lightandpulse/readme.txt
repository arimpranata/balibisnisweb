=== Lightandpulse ===
Contributors: idabagusarimpranata
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
License: GPLv2 or later

Lightweight agency theme for Lightandpulse Digital Agency, built for Elementor and WooCommerce, with a built-in client finder and CRM.

== Description ==

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

= 1.0.0 =
* First release.
