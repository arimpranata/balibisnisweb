<?php
/**
 * Appearance → Lightandpulse setup: one click to turn a fresh WordPress into
 * the lightandpulse.com website (pages, menus, homepage, Elementor Site
 * Settings, permalinks). Safe to run again: existing pages are kept unless
 * you choose to overwrite them.
 */
defined('ABSPATH') || exit;

require __DIR__ . '/builder.php';
require __DIR__ . '/content.php';

const LIGHTANDPULSE_BRAND = [
    'name'     => 'Lightandpulse',
    'tagline'  => 'Websites that turn Bali visitors into direct bookings',
    'colors'   => ['ink' => '#0b1020', 'accent' => '#ff6b2c', 'accent2' => '#ff2e74', 'text' => '#3a4356', 'teal' => '#10b3a3', 'soft' => '#f4f5f8'],
    'display'  => 'Bricolage Grotesque',
    'body'     => 'Plus Jakarta Sans',
];

add_action('admin_menu', function () {
    add_theme_page(__('Lightandpulse setup', 'lightandpulse'), __('Lightandpulse setup', 'lightandpulse'), 'manage_options', 'lightandpulse-setup', 'lightandpulse_setup_page');
});

// Nudge after activation until the setup has run (or is dismissed).
add_action('admin_notices', function () {
    if (!current_user_can('manage_options') || get_option('lightandpulse_setup_done') || get_option('lightandpulse_setup_dismissed')) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && $screen->id === 'appearance_page_lightandpulse-setup') {
        return;
    }
    printf(
        '<div class="notice notice-info"><p><b>%s</b> %s</p><p><a class="button button-primary" href="%s">%s</a> <a class="button-link" href="%s">%s</a></p></div>',
        esc_html__('Lightandpulse is active.', 'lightandpulse'),
        esc_html__('Set up the website pages, menus and Elementor styles in one click.', 'lightandpulse'),
        esc_url(admin_url('themes.php?page=lightandpulse-setup')),
        esc_html__('Open setup', 'lightandpulse'),
        esc_url(wp_nonce_url(admin_url('admin-post.php?action=lightandpulse_setup_dismiss'), 'lightandpulse_setup_dismiss')),
        esc_html__('Dismiss', 'lightandpulse')
    );
});

add_action('admin_post_lightandpulse_setup_dismiss', function () {
    check_admin_referer('lightandpulse_setup_dismiss');
    if (current_user_can('manage_options')) {
        update_option('lightandpulse_setup_dismissed', 1, false);
    }
    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
});

add_action('admin_post_lightandpulse_setup', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to do this.', 'lightandpulse'), 403);
    }
    check_admin_referer('lightandpulse_setup');
    $opts = array_map('sanitize_key', (array) ($_POST['steps'] ?? []));
    $log = lightandpulse_run_setup([
        'pages'     => in_array('pages', $opts, true),
        'overwrite' => in_array('overwrite', $opts, true),
        'menus'     => in_array('menus', $opts, true),
        'identity'  => in_array('identity', $opts, true),
        'elementor' => in_array('elementor', $opts, true),
        'permalinks'=> in_array('permalinks', $opts, true),
    ]);
    set_transient('lightandpulse_setup_log', $log, 300);
    wp_safe_redirect(admin_url('themes.php?page=lightandpulse-setup&done=1'));
    exit;
});

function lightandpulse_setup_page(): void
{
    $log = isset($_GET['done']) ? get_transient('lightandpulse_setup_log') : null;
    $elementor = did_action('elementor/loaded');
    $done = get_option('lightandpulse_setup_done');
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Lightandpulse setup', 'lightandpulse'); ?></h1>
      <?php if ($log) : ?>
        <div class="notice notice-success"><p><b><?php esc_html_e('Done.', 'lightandpulse'); ?></b> <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank"><?php esc_html_e('View the website →', 'lightandpulse'); ?></a></p><ul style="list-style:disc;margin-left:1.5em"><?php foreach ($log as $line) : ?><li><?php echo esc_html($line); ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <?php if (!$elementor) : ?>
        <div class="notice notice-warning inline"><p><?php echo wp_kses_post(sprintf(__('Elementor is not active. The pages will still be created and will look right, but to edit them visually install <a href="%s">Elementor</a> first, then run this setup with “Overwrite” ticked.', 'lightandpulse'), esc_url(admin_url('plugin-install.php?s=elementor&tab=search&type=term')))); ?></p></div>
      <?php endif; ?>
      <p><?php esc_html_e('Turns this WordPress into the lightandpulse.com website. You can run it again at any time.', 'lightandpulse'); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="lightandpulse_setup">
        <?php wp_nonce_field('lightandpulse_setup'); ?>
        <table class="form-table" role="presentation"><tbody>
          <tr><th><?php esc_html_e('Pages', 'lightandpulse'); ?></th><td>
            <label><input type="checkbox" name="steps[]" value="pages" checked> <?php esc_html_e('Create Home, Services, Packages, About, Insights (blog) and Contact, and set the homepage', 'lightandpulse'); ?></label><br>
            <label><input type="checkbox" name="steps[]" value="overwrite"> <?php esc_html_e('Overwrite the content of these pages if they already exist', 'lightandpulse'); ?></label>
          </td></tr>
          <tr><th><?php esc_html_e('Menus', 'lightandpulse'); ?></th><td><label><input type="checkbox" name="steps[]" value="menus" checked> <?php esc_html_e('Create the main and footer menus (with a “Start a project” button)', 'lightandpulse'); ?></label></td></tr>
          <tr><th><?php esc_html_e('Site identity', 'lightandpulse'); ?></th><td><label><input type="checkbox" name="steps[]" value="identity" checked> <?php printf(esc_html__('Site title “%1$s” and tagline “%2$s”', 'lightandpulse'), esc_html(LIGHTANDPULSE_BRAND['name']), esc_html(LIGHTANDPULSE_BRAND['tagline'])); ?></label></td></tr>
          <tr><th>Elementor</th><td><label><input type="checkbox" name="steps[]" value="elementor" <?php checked($elementor); disabled(!$elementor); ?>> <?php esc_html_e('Set Elementor Site Settings: brand colours, fonts and content width', 'lightandpulse'); ?></label></td></tr>
          <tr><th><?php esc_html_e('Permalinks', 'lightandpulse'); ?></th><td><label><input type="checkbox" name="steps[]" value="permalinks" checked> <?php esc_html_e('Use clean links like /services/', 'lightandpulse'); ?></label></td></tr>
        </tbody></table>
        <?php submit_button($done ? __('Run setup again', 'lightandpulse') : __('Set up website', 'lightandpulse')); ?>
      </form>
      <h2><?php esc_html_e('After setup', 'lightandpulse'); ?></h2>
      <ol>
        <li><?php echo wp_kses_post(sprintf(__('Add your WhatsApp number and email in <a href="%s">Leads → Settings</a>. The Contact page, footer and floating WhatsApp button use them.', 'lightandpulse'), esc_url(admin_url('admin.php?page=lpl-settings')))); ?></li>
        <li><?php echo wp_kses_post(sprintf(__('Upload your logo in <a href="%s">Appearance → Customize → Site Identity</a>.', 'lightandpulse'), esc_url(admin_url('customize.php?autofocus[section]=title_tagline')))); ?></li>
        <li><?php esc_html_e('Add your portfolio, client logos, testimonials and prices in Elementor when you have them. The starter pages leave these out on purpose.', 'lightandpulse'); ?></li>
      </ol>
    </div>
    <?php
}

/** Run the selected setup steps; returns a human-readable log. */
function lightandpulse_run_setup(array $steps): array
{
    $log = [];
    $ids = (array) get_option('lightandpulse_pages', []);

    if ($steps['identity']) {
        update_option('blogname', LIGHTANDPULSE_BRAND['name']);
        update_option('blogdescription', LIGHTANDPULSE_BRAND['tagline']);
        $log[] = __('Site title and tagline set.', 'lightandpulse');
    }

    if ($steps['pages']) {
        foreach (lightandpulse_starter_pages() as $slug => $page) {
            $existing = get_page_by_path($slug, OBJECT, 'page');
            $id = $existing ? $existing->ID : 0;
            if ($id && !$steps['overwrite']) {
                $ids[$slug] = $id;
                $log[] = sprintf(__('Kept existing page: %s', 'lightandpulse'), $page['title']);
                continue;
            }
            $postarr = [
                'ID'           => $id,
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $page['title'],
                'post_name'    => $slug,
                'post_content' => $page['sections'] ? lightandpulse_to_html($page['sections']) : '',
            ];
            $id = $id ? wp_update_post(wp_slash($postarr), true) : wp_insert_post(wp_slash($postarr), true);
            if (is_wp_error($id)) {
                $log[] = sprintf(__('Could not save %1$s: %2$s', 'lightandpulse'), $page['title'], $id->get_error_message());
                continue;
            }
            if ($page['sections']) {
                update_post_meta($id, '_elementor_edit_mode', 'builder');
                update_post_meta($id, '_elementor_template_type', 'wp-page');
                update_post_meta($id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
                update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode(lightandpulse_to_elementor($page['sections']))));
                update_post_meta($id, '_wp_page_template', 'elementor_header_footer');
                delete_post_meta($id, '_elementor_css');
            }
            $ids[$slug] = $id;
            $log[] = sprintf($existing ? __('Updated page: %s', 'lightandpulse') : __('Created page: %s', 'lightandpulse'), $page['title']);
        }
        update_option('lightandpulse_pages', $ids, false);
        if (!empty($ids['home'])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $ids['home']);
        }
        if (!empty($ids['insights'])) {
            update_option('page_for_posts', $ids['insights']);
        }
        $log[] = __('Homepage and blog page set.', 'lightandpulse');
    }

    if ($steps['menus']) {
        $log[] = lightandpulse_setup_menus($ids);
    }

    if ($steps['elementor'] && did_action('elementor/loaded')) {
        $log[] = lightandpulse_setup_elementor_kit();
    }

    if ($steps['permalinks']) {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        flush_rewrite_rules();
        $log[] = __('Permalinks set to /%postname%/.', 'lightandpulse');
    }

    if (did_action('elementor/loaded') && class_exists('\Elementor\Plugin')) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
    }
    update_option('lightandpulse_setup_done', time(), false);
    return $log;
}

function lightandpulse_setup_menus(array $ids): string
{
    $make = function (string $name, string $location, array $items) {
        $menu = wp_get_nav_menu_object($name);
        $menuId = $menu ? $menu->term_id : wp_create_nav_menu($name);
        if (is_wp_error($menuId)) {
            return;
        }
        // Rebuild the items so running setup twice doesn't duplicate them.
        foreach ((array) wp_get_nav_menu_items($menuId) as $item) {
            wp_delete_post($item->ID, true);
        }
        foreach ($items as $i => $it) {
            $args = ['menu-item-title' => $it['title'], 'menu-item-status' => 'publish', 'menu-item-position' => $i + 1, 'menu-item-classes' => $it['class'] ?? ''];
            if (!empty($it['page'])) {
                $args += ['menu-item-object' => 'page', 'menu-item-object-id' => $it['page'], 'menu-item-type' => 'post_type'];
            } else {
                $args += ['menu-item-type' => 'custom', 'menu-item-url' => $it['url']];
            }
            wp_update_nav_menu_item($menuId, 0, $args);
        }
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations[$location] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);
    };
    $page = fn(string $slug, string $title) => !empty($ids[$slug]) ? ['title' => $title, 'page' => $ids[$slug]] : null;

    $make('Main menu', 'primary', array_values(array_filter([
        $page('services', 'Services'),
        $page('packages', 'Packages'),
        $page('about', 'About'),
        $page('insights', 'Insights'),
        ['title' => 'Start a project', 'url' => home_url('/contact/'), 'class' => 'lp-nav-cta'],
    ])));
    $make('Footer menu', 'footer', array_values(array_filter([
        $page('services', 'Services'),
        $page('packages', 'Packages'),
        $page('about', 'About'),
        $page('insights', 'Insights'),
        $page('contact', 'Contact'),
    ])));
    return __('Main and footer menus created.', 'lightandpulse');
}

/** Brand colours, fonts and width in Elementor → Site Settings. */
function lightandpulse_setup_elementor_kit(): string
{
    $kitId = (int) get_option('elementor_active_kit');
    if (!$kitId) {
        return __('Elementor kit not found; skipped Site Settings.', 'lightandpulse');
    }
    $c = LIGHTANDPULSE_BRAND['colors'];
    $settings = (array) get_post_meta($kitId, '_elementor_page_settings', true);
    $settings['system_colors'] = [
        ['_id' => 'primary', 'title' => 'Primary', 'color' => $c['accent']],
        ['_id' => 'secondary', 'title' => 'Secondary', 'color' => $c['ink']],
        ['_id' => 'text', 'title' => 'Text', 'color' => $c['text']],
        ['_id' => 'accent', 'title' => 'Accent', 'color' => $c['teal']],
    ];
    $settings['custom_colors'] = [
        ['_id' => 'lp_pink', 'title' => 'Pulse pink', 'color' => $c['accent2']],
        ['_id' => 'lp_soft', 'title' => 'Soft background', 'color' => $c['soft']],
    ];
    $font = fn(string $id, string $title, string $family, string $weight) => [
        '_id' => $id, 'title' => $title, 'typography_typography' => 'custom',
        'typography_font_family' => $family, 'typography_font_weight' => $weight,
    ];
    $settings['system_typography'] = [
        $font('primary', 'Primary', LIGHTANDPULSE_BRAND['display'], '700'),
        $font('secondary', 'Secondary', LIGHTANDPULSE_BRAND['display'], '600'),
        $font('text', 'Text', LIGHTANDPULSE_BRAND['body'], '400'),
        $font('accent', 'Accent', LIGHTANDPULSE_BRAND['body'], '600'),
    ];
    $settings['container_width'] = ['unit' => 'px', 'size' => 1200, 'sizes' => []];
    $settings['site_name'] = LIGHTANDPULSE_BRAND['name'];
    $settings['site_description'] = LIGHTANDPULSE_BRAND['tagline'];
    update_post_meta($kitId, '_elementor_page_settings', $settings);
    update_option('elementor_container_width', 1200);
    return __('Elementor Site Settings updated: colours, fonts and 1200px content width.', 'lightandpulse');
}

// ---------------------------------------------------------------------------
// Shortcodes used by the starter pages and footer
// ---------------------------------------------------------------------------
add_shortcode('lightandpulse_contact_info', function () {
    $s = function_exists('lpl_settings') ? lpl_settings() : [];
    $wa = function_exists('lpl_wa_number') ? lpl_wa_number($s['my_phone'] ?? '') : '';
    $rows = [];
    if ($wa !== '') {
        $rows[] = ['chat', 'WhatsApp', '<a href="' . esc_url('https://wa.me/' . $wa) . '" target="_blank" rel="noopener">' . esc_html($s['my_phone']) . '</a>'];
    }
    if (!empty($s['my_email'])) {
        $rows[] = ['mail', __('Email', 'lightandpulse'), '<a href="' . esc_url('mailto:' . $s['my_email']) . '">' . esc_html($s['my_email']) . '</a>'];
    }
    $rows[] = ['pin', __('Studio', 'lightandpulse'), esc_html__('Bali, Indonesia', 'lightandpulse')];
    $out = '<ul class="lp-contact-list">';
    foreach ($rows as [$icon, $label, $value]) {
        $out .= '<li>' . lightandpulse_icon($icon) . '<span><small>' . esc_html($label) . '</small>' . $value . '</span></li>';
    }
    return $out . '</ul>';
});
