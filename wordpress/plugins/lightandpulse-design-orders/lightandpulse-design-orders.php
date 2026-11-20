<?php
/**
 * Plugin Name:       Lightandpulse Design Orders
 * Plugin URI:        https://lightandpulse.com
 * Description:       Design order system for posters, menus, flyers, backdrops and business cards. Clients fill in a detailed brief, get their own branded portal page, approve proofs and download final files by direct link or QR code.
 * Version:           1.3.1
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Lightandpulse
 * Author URI:        https://lightandpulse.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lightandpulse-design-orders
 */
defined('ABSPATH') || exit;

define('LPDO_VERSION', '1.3.1');
define('LPDO_FILE', __FILE__);
define('LPDO_DIR', __DIR__);
define('LPDO_URL', plugin_dir_url(__FILE__));
/** Bump when the privacy notice text changes; stored with every consent record (UU PDP Pasal 24). */
define('LPDO_NOTICE_VERSION', '2026-11-20');

require LPDO_DIR . '/vendor/qrcode-generator/qrcode.php';
require LPDO_DIR . '/includes/schema.php';
require LPDO_DIR . '/includes/core.php';
require LPDO_DIR . '/includes/qr.php';
require LPDO_DIR . '/includes/form.php';
require LPDO_DIR . '/includes/portal.php';
require LPDO_DIR . '/includes/emails.php';
require LPDO_DIR . '/includes/privacy.php';
if (is_admin()) {
    require LPDO_DIR . '/includes/admin.php';
}

register_activation_hook(__FILE__, function () {
    lpdo_register_types();
    lpdo_add_rewrites();
    flush_rewrite_rules();
    if (!wp_next_scheduled('lpdo_daily')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'lpdo_daily');
    }
    // An order form page to start from (only when there is none yet).
    if (!lpdo_setting('form_page')) {
        $id = wp_insert_post([
            'post_type' => 'page', 'post_status' => 'publish', 'post_title' => __('Order Desain', 'lightandpulse-design-orders'),
            'post_name' => 'order-desain', 'post_content' => '<!-- wp:shortcode -->[lightandpulse_design_order]<!-- /wp:shortcode -->',
        ]);
        if (!is_wp_error($id)) {
            lpdo_update_settings(['form_page' => $id]);
        }
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('lpdo_daily');
    flush_rewrite_rules();
});
