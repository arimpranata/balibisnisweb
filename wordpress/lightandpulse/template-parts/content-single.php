<?php defined('ABSPATH') || exit;
$built_with_elementor = class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->documents->get(get_the_ID())?->is_built_with_elementor();
?>
<main id="post-<?php the_ID(); ?>" <?php post_class($built_with_elementor ? 'lp-elementor' : 'lp-container lp-main lp-narrow'); ?>>
  <?php if (!$built_with_elementor) : ?>
    <header class="entry-header">
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php if (get_post_type() === 'post') : ?><p class="entry-meta"><?php echo esc_html(get_the_date()); ?> · <?php the_author(); ?></p><?php endif; ?>
    </header>
    <?php if (has_post_thumbnail()) : ?><figure class="entry-thumb"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
  <?php endif; ?>
  <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
  <?php if (!$built_with_elementor) : ?>
    <?php the_post_navigation(); ?>
    <?php if (comments_open() || get_comments_number()) : comments_template(); endif; ?>
  <?php endif; ?>
</main>
