<?php defined('ABSPATH') || exit; ?>
<main class="lp-container lp-main">
  <?php if (is_search()) : ?>
    <h1><?php printf(esc_html__('Search results for “%s”', 'lightandpulse'), esc_html(get_search_query())); ?></h1>
  <?php elseif (is_archive()) : ?>
    <?php the_archive_title('<h1>', '</h1>'); the_archive_description('<div class="archive-description">', '</div>'); ?>
  <?php elseif (is_home() && !is_front_page()) : ?>
    <h1><?php single_post_title(); ?></h1>
  <?php endif; ?>

  <?php if (have_posts()) : ?>
    <div class="lp-grid">
      <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('lp-card'); ?>>
          <?php if (has_post_thumbnail()) : ?><a href="<?php the_permalink(); ?>" class="lp-card__thumb"><?php the_post_thumbnail('medium_large'); ?></a><?php endif; ?>
          <h2 class="lp-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <p class="entry-meta"><?php echo esc_html(get_the_date()); ?></p>
          <div class="lp-card__excerpt"><?php the_excerpt(); ?></div>
        </article>
      <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(); ?>
  <?php else : ?>
    <p><?php esc_html_e('Nothing found.', 'lightandpulse'); ?></p>
    <?php get_search_form(); ?>
  <?php endif; ?>
</main>
