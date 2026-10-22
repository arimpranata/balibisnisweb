<?php defined('ABSPATH') || exit;
// Pages built with Elementor get the full width; Elementor manages their inner layout.
// Starter pages keep the same full-width design when Elementor is switched off.
$built_with_elementor = (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->documents->get(get_the_ID())?->is_built_with_elementor())
    || str_contains((string) get_post_field('post_content', get_the_ID()), 'lp-static');
?>
<main id="post-<?php the_ID(); ?>" <?php post_class($built_with_elementor ? 'lp-elementor' : 'lp-container lp-main'); ?>>
  <?php if (!$built_with_elementor && !is_front_page()) : ?><h1 class="entry-title"><?php the_title(); ?></h1><?php endif; ?>
  <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
  <?php if (!$built_with_elementor && (comments_open() || get_comments_number())) : comments_template(); endif; ?>
</main>
