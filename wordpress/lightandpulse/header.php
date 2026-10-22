<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Skip to content', 'lightandpulse'); ?></a>
<?php if (!lightandpulse_elementor_location('header')) : ?>
<header class="site-header">
  <div class="lp-container site-header__inner">
    <div class="site-branding">
      <?php if (has_custom_logo()) : the_custom_logo(); else : ?>
        <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
      <?php endif; ?>
    </div>
    <?php if (has_nav_menu('primary')) : ?>
      <button class="menu-toggle" type="button" aria-controls="primary-menu" aria-expanded="false"><span class="screen-reader-text"><?php esc_html_e('Menu', 'lightandpulse'); ?></span>☰</button>
      <nav class="primary-nav" aria-label="<?php esc_attr_e('Primary', 'lightandpulse'); ?>">
        <?php wp_nav_menu(['theme_location' => 'primary', 'menu_id' => 'primary-menu', 'container' => false, 'depth' => 2]); ?>
      </nav>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>
<div id="content" class="site-content">
