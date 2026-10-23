<?php
/**
 * Lightandpulse theme setup.
 *
 * The theme stays deliberately light so Elementor controls the design:
 * Elementor Pro Theme Builder can replace the header, footer, single and archive
 * templates, and free Elementor's Canvas / Full Width page templates work as-is.
 */
defined('ABSPATH') || exit;

define('LIGHTANDPULSE_VERSION', '1.2.0');
define('LIGHTANDPULSE_DIR', get_template_directory());
define('LIGHTANDPULSE_URI', get_template_directory_uri());

if (!isset($content_width)) {
    $content_width = 1200;
}

add_action('after_setup_theme', function () {
    load_theme_textdomain('lightandpulse', LIGHTANDPULSE_DIR . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', ['height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    // WooCommerce
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => __('Primary menu', 'lightandpulse'),
        'footer'  => __('Footer menu', 'lightandpulse'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    // Switzer is served by Fontshare (its licence allows the API, not bundling the files).
    wp_enqueue_style('lightandpulse-switzer', 'https://api.fontshare.com/v2/css?f[]=switzer@400,500,600,700&display=swap', [], null);
    wp_enqueue_style('lightandpulse-fonts', 'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@1&family=JetBrains+Mono:wght@400;500&display=swap', [], null);
    wp_enqueue_style('lightandpulse', LIGHTANDPULSE_URI . '/assets/css/theme.css', [], LIGHTANDPULSE_VERSION);
    wp_enqueue_script('lightandpulse', LIGHTANDPULSE_URI . '/assets/js/theme.js', [], LIGHTANDPULSE_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});

// Elementor Pro Theme Builder: let it take over header, footer, single and archive.
add_action('elementor/theme/register_locations', function ($manager) {
    $manager->register_all_core_location();
});

/** Render an Elementor Pro location; returns true when Elementor printed it. */
function lightandpulse_elementor_location(string $location): bool
{
    return function_exists('elementor_theme_do_location') && elementor_theme_do_location($location);
}

// Elementor: use the theme's content width and fonts as sensible defaults.
add_action('after_switch_theme', function () {
    if (get_option('elementor_container_width') === false) {
        update_option('elementor_container_width', 1200);
    }
    // Let Elementor's global colours and fonts win over the theme's.
    update_option('elementor_disable_color_schemes', 'yes');
    update_option('elementor_disable_typography_schemes', 'yes');
});

add_action('wp_head', function () {
    echo '<link rel="preconnect" href="https://api.fontshare.com" crossorigin><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<meta name="theme-color" content="#101012">' . "\n";
}, 1);

// Front page flag for header styling.
add_filter('body_class', function (array $classes) {
    if (is_front_page()) {
        $classes[] = 'lp-front';
    }
    return $classes;
});

add_action('widgets_init', function () {
    register_sidebar([
        'name'          => __('Footer', 'lightandpulse'),
        'id'            => 'footer-1',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);
});

require LIGHTANDPULSE_DIR . '/inc/icons.php';
require LIGHTANDPULSE_DIR . '/inc/customizer.php';
require LIGHTANDPULSE_DIR . '/inc/leads/module.php';
require LIGHTANDPULSE_DIR . '/inc/setup/setup.php';
