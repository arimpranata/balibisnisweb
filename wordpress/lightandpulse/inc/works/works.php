<?php
/**
 * Works (portfolio): a "Works" content type with website URL, client, type and
 * year; a grid shortcode and Elementor widget; and an importer that reads an
 * existing portfolio page (by default lightandpulse.com/works/).
 *
 * Screenshots: the featured image is used when set. Otherwise the card shows a
 * live screenshot of the project's website from WordPress.com's free mShots
 * service, so every work has a picture without manual uploads.
 */
defined('ABSPATH') || exit;

require __DIR__ . '/importer.php';

add_action('init', function () {
    register_post_type('lp_work', [
        'labels' => [
            'name' => __('Works', 'lightandpulse'), 'singular_name' => __('Work', 'lightandpulse'),
            'add_new_item' => __('Add work', 'lightandpulse'), 'edit_item' => __('Edit work', 'lightandpulse'),
            'all_items' => __('All works', 'lightandpulse'), 'menu_name' => __('Works', 'lightandpulse'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'menu_icon'           => 'dashicons-portfolio',
        'menu_position'       => 4,
        'supports'            => ['title', 'excerpt', 'thumbnail', 'page-attributes'],
        'exclude_from_search' => true,
    ]);
    register_taxonomy('lp_work_type', 'lp_work', [
        'labels'            => ['name' => __('Work types', 'lightandpulse'), 'singular_name' => __('Work type', 'lightandpulse')],
        'public'            => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'hierarchical'      => false,
    ]);
    foreach (['_lp_work_url', '_lp_work_client', '_lp_work_year'] as $key) {
        register_post_meta('lp_work', $key, ['type' => 'string', 'single' => true, 'show_in_rest' => false, 'auth_callback' => fn() => current_user_can('edit_posts')]);
    }
});

// ---------------------------------------------------------------------------
// Edit screen: website URL, client and year
// ---------------------------------------------------------------------------
add_action('add_meta_boxes_lp_work', function () {
    add_meta_box('lp_work_details', __('Project details', 'lightandpulse'), function (WP_Post $post) {
        wp_nonce_field('lp_work_details', '_lp_work_nonce');
        $url = (string) get_post_meta($post->ID, '_lp_work_url', true);
        ?>
        <p><label><b><?php esc_html_e('Website URL', 'lightandpulse'); ?></b><br><input type="url" name="lp_work_url" value="<?php echo esc_attr($url); ?>" class="widefat" placeholder="https://"></label></p>
        <p><label><b><?php esc_html_e('Client', 'lightandpulse'); ?></b><br><input type="text" name="lp_work_client" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_lp_work_client', true)); ?>" class="widefat"></label></p>
        <p><label><b><?php esc_html_e('Year', 'lightandpulse'); ?></b><br><input type="text" name="lp_work_year" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_lp_work_year', true)); ?>" class="small-text" inputmode="numeric"></label></p>
        <p class="description"><?php esc_html_e('Short description: use the Excerpt field. Screenshot: set a Featured image, or leave it empty to show an automatic screenshot of the website URL.', 'lightandpulse'); ?></p>
        <?php if ($url && !has_post_thumbnail($post)) : ?>
          <p><img src="<?php echo esc_url(lightandpulse_mshots($url, 600)); ?>" alt="" style="max-width:100%;border:1px solid #ddd"></p>
        <?php endif;
    }, 'lp_work', 'side', 'high');
});

add_action('save_post_lp_work', function (int $postId) {
    if (!isset($_POST['_lp_work_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_lp_work_nonce'])), 'lp_work_details') || !current_user_can('edit_post', $postId)) {
        return;
    }
    update_post_meta($postId, '_lp_work_url', esc_url_raw(wp_unslash($_POST['lp_work_url'] ?? '')));
    update_post_meta($postId, '_lp_work_client', sanitize_text_field(wp_unslash($_POST['lp_work_client'] ?? '')));
    update_post_meta($postId, '_lp_work_year', preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['lp_work_year'] ?? '')));
});

add_filter('manage_lp_work_posts_columns', function (array $cols) {
    return array_slice($cols, 0, 1, true) + ['lp_shot' => __('Screenshot', 'lightandpulse')] + array_slice($cols, 1, 1, true) + ['lp_url' => __('Website', 'lightandpulse')] + array_slice($cols, 2, null, true);
});
add_action('manage_lp_work_posts_custom_column', function (string $col, int $postId) {
    $url = (string) get_post_meta($postId, '_lp_work_url', true);
    if ($col === 'lp_shot') {
        $img = lightandpulse_work_image($postId, 300);
        echo $img ? '<img src="' . esc_url($img) . '" alt="" style="width:96px;height:60px;object-fit:cover;object-position:top;border:1px solid #ddd">' : '—';
    } elseif ($col === 'lp_url') {
        echo $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html(lightandpulse_domain($url)) . '</a>' : '—';
    }
}, 10, 2);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
/** Free live screenshot of a public URL (WordPress.com mShots). */
function lightandpulse_mshots(string $url, int $width = 1200): string
{
    $height = (int) round($width * 0.625);
    return 'https://s.wordpress.com/mshots/v1/' . rawurlencode($url) . '?w=' . $width . '&h=' . $height;
}

function lightandpulse_domain(string $url): string
{
    return preg_replace('/^www\./', '', (string) wp_parse_url($url, PHP_URL_HOST));
}

/** Featured image if set, otherwise an automatic screenshot of the work's URL. */
function lightandpulse_work_image(int $postId, int $width = 1200): string
{
    if (has_post_thumbnail($postId)) {
        return (string) get_the_post_thumbnail_url($postId, $width <= 400 ? 'medium' : 'large');
    }
    $url = (string) get_post_meta($postId, '_lp_work_url', true);
    return $url ? lightandpulse_mshots($url, $width) : '';
}

function lightandpulse_get_works(int $limit = -1): array
{
    return get_posts(['post_type' => 'lp_work', 'post_status' => 'publish', 'numberposts' => $limit, 'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC']]);
}

// ---------------------------------------------------------------------------
// Front end: [lightandpulse_works] grid and [lightandpulse_works_section]
// ---------------------------------------------------------------------------
function lightandpulse_render_works(array $args = []): string
{
    $args += ['limit' => -1, 'filter' => true];
    $works = lightandpulse_get_works((int) $args['limit']);
    if (!$works) {
        return current_user_can('edit_posts')
            ? '<p class="lp-empty">' . sprintf(wp_kses_post(__('No works yet. Add them in <a href="%1$s">Works</a> or <a href="%2$s">import them from your current website</a>.', 'lightandpulse')), esc_url(admin_url('edit.php?post_type=lp_work')), esc_url(admin_url('edit.php?post_type=lp_work&page=lp-works-import'))) . '</p>'
            : '';
    }

    $types = [];
    $cards = '';
    foreach ($works as $i => $work) {
        $url = (string) get_post_meta($work->ID, '_lp_work_url', true);
        $terms = get_the_terms($work->ID, 'lp_work_type') ?: [];
        foreach ($terms as $t) {
            $types[$t->slug] = $t->name;
        }
        $slugs = implode(' ', wp_list_pluck($terms, 'slug'));
        $meta = array_filter([implode(', ', wp_list_pluck($terms, 'name')), (string) get_post_meta($work->ID, '_lp_work_year', true)]);
        $img = lightandpulse_work_image($work->ID);
        $domain = $url ? lightandpulse_domain($url) : '';
        $open = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener"' : '<span';
        $close = $url ? '</a>' : '</span>';
        $cards .= '<article class="lp-work" data-types="' . esc_attr($slugs) . '" style="--i:' . (int) $i . '">'
            . $open . ' class="lp-work__shot" tabindex="-1" aria-hidden="true">'
            . '<span class="lp-work__bar"><i></i><i></i><i></i><em>' . esc_html($domain ?: get_the_title($work)) . '</em></span>'
            . ($img ? '<img src="' . esc_url($img) . '" alt="" loading="lazy" decoding="async" width="1200" height="750">' : '<span class="lp-work__noimg"></span>')
            . $close
            . '<div class="lp-work__body">'
            . ($meta ? '<p class="lp-work__meta">' . esc_html(implode(' · ', $meta)) . '</p>' : '')
            . '<h3 class="lp-work__title">' . ($url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html(get_the_title($work)) . '</a>' : esc_html(get_the_title($work))) . '</h3>'
            . ($work->post_excerpt ? '<p class="lp-work__desc">' . esc_html($work->post_excerpt) . '</p>' : '')
            . ($url ? '<a class="lp-work__link" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($domain) . ' <span aria-hidden="true">↗</span><span class="screen-reader-text"> ' . esc_html__('(opens in a new tab)', 'lightandpulse') . '</span></a>' : '')
            . '</div></article>';
    }

    $filters = '';
    if ($args['filter'] && count($types) > 1) {
        asort($types);
        $filters = '<div class="lp-works-filter" role="group" aria-label="' . esc_attr__('Filter works', 'lightandpulse') . '"><button type="button" class="is-active" data-filter="*" aria-pressed="true">' . esc_html__('All', 'lightandpulse') . '</button>';
        foreach ($types as $slug => $name) {
            $filters .= '<button type="button" data-filter="' . esc_attr($slug) . '" aria-pressed="false">' . esc_html($name) . '</button>';
        }
        $filters .= '</div>';
    }
    return '<div class="lp-works" data-lp-works>' . $filters . '<div class="lp-works-grid">' . $cards . '</div></div>';
}

add_shortcode('lightandpulse_works', function ($atts) {
    $atts = shortcode_atts(['limit' => -1, 'filter' => 'yes'], $atts, 'lightandpulse_works');
    return lightandpulse_render_works(['limit' => (int) $atts['limit'], 'filter' => $atts['filter'] !== 'no']);
});

/** Homepage block: heading + latest works + link to all. Renders nothing when there are no works. */
add_shortcode('lightandpulse_works_section', function ($atts) {
    $atts = shortcode_atts(['limit' => 6], $atts, 'lightandpulse_works_section');
    if (!lightandpulse_get_works(1)) {
        return '';
    }
    $page = get_page_by_path('works');
    return '<div class="lp-works-section">'
        . '<div class="lp-works-section__head"><div><p class="lp-label">' . esc_html__('Selected work', 'lightandpulse') . '</p>'
        . '<h2>' . wp_kses_post(__('Websites we’ve <em>built and launched.</em>', 'lightandpulse')) . '</h2></div>'
        . ($page ? '<a class="lp-text-link" href="' . esc_url(get_permalink($page)) . '">' . esc_html__('All works', 'lightandpulse') . ' <span aria-hidden="true">→</span></a>' : '')
        . '</div>'
        . lightandpulse_render_works(['limit' => (int) $atts['limit'], 'filter' => false])
        . '</div>';
});

add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once __DIR__ . '/elementor-widget.php';
    $widgets_manager->register(new \Lightandpulse_Works_Widget());
});
