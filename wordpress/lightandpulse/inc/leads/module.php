<?php
/**
 * Lightandpulse Leads — find, manage and connect with potential clients in Bali.
 *
 * Self-contained module: everything lives in this folder, so it can be moved
 * into a plugin later without changes. Data is stored in its own tables
 * (wp_lp_leads, wp_lp_activities, wp_lp_templates), so switching themes hides
 * the menu but never deletes leads.
 */
defined('ABSPATH') || exit;

define('LPL_DIR', __DIR__);
define('LPL_URI', get_template_directory_uri() . '/inc/leads');

require LPL_DIR . '/osm.php';
require LPL_DIR . '/helpers.php';
require LPL_DIR . '/places.php';
require LPL_DIR . '/audit.php';
require LPL_DIR . '/handlers.php';
require LPL_DIR . '/public-form.php';

// Install or upgrade tables, and give administrators access.
add_action('after_switch_theme', 'lpl_activate');
add_action('admin_init', function () {
    if (get_option('lpl_db_version') !== LPL_DB_VERSION) {
        lpl_activate();
    }
});

function lpl_activate(): void
{
    lpl_install();
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap(LPL_CAP)) {
        $admin->add_cap(LPL_CAP);
    }
}

const LPL_PAGES = [
    'dashboard' => 'Dashboard',
    'finder'    => 'Find clients',
    'leads'     => 'All leads',
    'templates' => 'Message templates',
    'import'    => 'Import / export',
    'settings'  => 'Settings',
];

add_action('admin_menu', function () {
    global $wpdb;
    $due = 0;
    if (get_option('lpl_db_version')) {
        $due = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . lpl_table('leads') . " WHERE next_followup IS NOT NULL AND next_followup <= %s AND stage NOT IN ('won','lost')",
            lpl_today()
        ));
    }
    $bubble = $due ? ' <span class="awaiting-mod">' . $due . '</span>' : '';
    add_menu_page(__('Leads', 'lightandpulse'), __('Leads', 'lightandpulse') . $bubble, LPL_CAP, 'lpl-dashboard', 'lpl_render_page', 'dashicons-groups', 3);
    foreach (LPL_PAGES as $slug => $label) {
        add_submenu_page('lpl-dashboard', $label, $label, LPL_CAP, 'lpl-' . $slug, 'lpl_render_page');
    }
});

add_action('admin_enqueue_scripts', function (string $hook) {
    if (!str_contains($hook, 'lpl-')) {
        return;
    }
    wp_enqueue_style('lpl-admin', LPL_URI . '/assets/admin.css', [], LIGHTANDPULSE_VERSION);
    wp_enqueue_script('lpl-admin', LPL_URI . '/assets/admin.js', [], LIGHTANDPULSE_VERSION, true);
});

/** Router for every Leads admin page. */
function lpl_render_page(): void
{
    if (!current_user_can(LPL_CAP)) {
        wp_die(esc_html__('You do not have access to Leads.', 'lightandpulse'));
    }
    $page = preg_replace('/^lpl-/', '', lpl_get('page', 'lpl-dashboard'));
    $view = match (true) {
        $page === 'leads' && lpl_get('lead') !== '' => 'lead',
        $page === 'leads' && lpl_get('edit') !== '' => 'lead_form',
        default                                     => $page,
    };
    $file = LPL_DIR . '/views/' . $view . '.php';
    if (!isset(LPL_PAGES[$page]) || !is_file($file)) {
        $file = LPL_DIR . '/views/dashboard.php';
        $page = 'dashboard';
    }
    $flash = lpl_flash();
    echo '<div class="wrap lpl">';
    if ($flash) {
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($flash['type'] === 'error' ? 'error' : 'success'), esc_html($flash['msg']));
    }
    require $file;
    echo '</div>';
}

// Show follow-ups on the main WordPress dashboard too.
add_action('wp_dashboard_setup', function () {
    if (!current_user_can(LPL_CAP) || !get_option('lpl_db_version')) {
        return;
    }
    wp_add_dashboard_widget('lpl_followups', __('Leads: follow-ups due', 'lightandpulse'), function () {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT id, name, next_followup FROM ' . lpl_table('leads') . " WHERE next_followup IS NOT NULL AND next_followup <= %s AND stage NOT IN ('won','lost') ORDER BY next_followup LIMIT 8",
            lpl_today()
        ), ARRAY_A);
        if (!$rows) {
            echo '<p>' . esc_html__('Nothing due today.', 'lightandpulse') . ' <a href="' . esc_url(lpl_url('finder')) . '">' . esc_html__('Find new clients', 'lightandpulse') . '</a></p>';
            return;
        }
        echo '<ul>';
        foreach ($rows as $r) {
            printf('<li><a href="%s">%s</a> — %s</li>', esc_url(lpl_url('leads', ['lead' => $r['id']])), esc_html($r['name']), esc_html($r['next_followup']));
        }
        echo '</ul>';
    });
});
