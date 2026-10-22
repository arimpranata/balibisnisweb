<?php defined('ABSPATH') || exit; ?>
</div><!-- #content -->
<?php if (!lightandpulse_elementor_location('footer')) : ?>
<footer class="site-footer">
  <div class="lp-container">
    <?php if (is_active_sidebar('footer-1')) : ?><div class="footer-widgets"><?php dynamic_sidebar('footer-1'); ?></div><?php endif; ?>
    <?php if (has_nav_menu('footer')) : wp_nav_menu(['theme_location' => 'footer', 'container' => 'nav', 'container_class' => 'footer-nav', 'depth' => 1]); endif; ?>
    <p class="site-info">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></p>
  </div>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
