<?php
defined('ABSPATH') || exit;
get_header();
if (!lightandpulse_elementor_location('single')) : ?>
<main class="lp-container lp-main">
  <h1><?php esc_html_e('Page not found', 'lightandpulse'); ?></h1>
  <p><?php esc_html_e('The page you were looking for does not exist. Try a search:', 'lightandpulse'); ?></p>
  <?php get_search_form(); ?>
</main>
<?php endif;
get_footer();
