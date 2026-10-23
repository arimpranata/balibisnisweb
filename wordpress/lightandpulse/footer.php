<?php defined('ABSPATH') || exit; ?>
</div><!-- #content -->
<?php if (!lightandpulse_elementor_location('footer')) : ?>
<footer class="site-footer">
  <div class="lp-container">
    <div class="site-footer__grid">
      <div class="site-footer__brand">
        <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>"><?php echo lightandpulse_mark(30); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?><span><?php bloginfo('name'); ?></span></a>
        <p><?php esc_html_e('A digital agency from Bali, Indonesia, since 2012: web development, graphic design and digital marketing.', 'lightandpulse'); ?></p>
      </div>
      <?php if (has_nav_menu('footer')) : ?>
        <div>
          <h2 class="site-footer__title"><?php esc_html_e('Explore', 'lightandpulse'); ?></h2>
          <?php wp_nav_menu(['theme_location' => 'footer', 'container' => 'nav', 'container_class' => 'footer-nav', 'depth' => 1]); ?>
        </div>
      <?php endif; ?>
      <div>
        <h2 class="site-footer__title"><?php esc_html_e('Contact', 'lightandpulse'); ?></h2>
        <?php echo do_shortcode('[lightandpulse_contact_info]'); ?>
      </div>
      <?php if (is_active_sidebar('footer-1')) : ?><div class="footer-widgets"><?php dynamic_sidebar('footer-1'); ?></div><?php endif; ?>
    </div>
    <div class="site-footer__bottom">
      <p><?php bloginfo('description'); ?></p>
      <p>&copy; <?php echo esc_html(wp_date('Y')); ?></p>
      <p><?php esc_html_e('Bali, Indonesia', 'lightandpulse'); ?><?php if (get_privacy_policy_url()) : ?> · <?php the_privacy_policy_link(); ?><?php endif; ?></p>
    </div>
  </div>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
