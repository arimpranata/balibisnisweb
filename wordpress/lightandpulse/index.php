<?php
defined('ABSPATH') || exit;
get_header();

if (is_singular()) {
    if (!lightandpulse_elementor_location('single')) {
        while (have_posts()) {
            the_post();
            get_template_part('template-parts/content', is_page() ? 'page' : 'single');
        }
    }
} elseif (!lightandpulse_elementor_location('archive')) {
    get_template_part('template-parts/archive');
}

get_footer();
