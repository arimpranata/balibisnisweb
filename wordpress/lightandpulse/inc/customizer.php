<?php
/**
 * Customizer: brand colour and a floating WhatsApp button (the main way Bali clients get in touch).
 */
defined('ABSPATH') || exit;

add_action('customize_register', function (WP_Customize_Manager $wp_customize) {
    $wp_customize->add_section('lightandpulse_options', [
        'title'    => __('Lightandpulse', 'lightandpulse'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('lightandpulse_accent', ['default' => '#ff6b2c', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'lightandpulse_accent', [
        'label'   => __('Accent colour (links, buttons)', 'lightandpulse'),
        'section' => 'lightandpulse_options',
    ]));

    $wp_customize->add_setting('lightandpulse_whatsapp', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp_customize->add_control('lightandpulse_whatsapp', [
        'label'       => __('Floating WhatsApp button number', 'lightandpulse'),
        'description' => __('e.g. 0812 3456 7890. Leave empty to hide the button.', 'lightandpulse'),
        'section'     => 'lightandpulse_options',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('lightandpulse_whatsapp_text', ['default' => 'Halo Lightandpulse, saya tertarik dengan jasa website.', 'sanitize_callback' => 'sanitize_text_field']);
    $wp_customize->add_control('lightandpulse_whatsapp_text', [
        'label'   => __('WhatsApp pre-filled message', 'lightandpulse'),
        'section' => 'lightandpulse_options',
        'type'    => 'text',
    ]);
});

add_action('wp_head', function () {
    $accent = sanitize_hex_color(get_theme_mod('lightandpulse_accent', '#ff6b2c')) ?: '#ff6b2c';
    if (strtolower($accent) !== '#ff6b2c') {
        printf('<style id="lightandpulse-accent">:root{--lp-accent:%s}</style>' . "\n", esc_html($accent));
    }
});

add_action('wp_footer', function () {
    $number = function_exists('lpl_wa_number') ? lpl_wa_number((string) get_theme_mod('lightandpulse_whatsapp', '')) : '';
    if ($number === '') {
        return;
    }
    $url = 'https://wa.me/' . $number . '?text=' . rawurlencode((string) get_theme_mod('lightandpulse_whatsapp_text', ''));
    printf(
        '<a class="lp-wa-float" href="%s" target="_blank" rel="noopener" aria-label="%s"><svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7c-2 0-4-.5-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-1-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.7 3.7 0 0 0-1.1 2.7 6.4 6.4 0 0 0 1.3 3.4 14.7 14.7 0 0 0 5.7 5c2.1.9 2.9 1 4 .8.6-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5l-.6-.4z"/></svg></a>',
        esc_url($url),
        esc_attr__('Chat on WhatsApp', 'lightandpulse')
    );
});
