<?php
/**
 * Website lead form: [lightandpulse_lead_form] shortcode and an Elementor widget.
 * Enquiries become hot leads (source "Website form") in the Leads CRM.
 *
 * Spam protection uses a honeypot, a signed timestamp (forms sent in under
 * 3 seconds are bots) and a per-IP rate limit. No nonce, so the form keeps
 * working on pages cached by LiteSpeed or other page caches.
 */
defined('ABSPATH') || exit;

add_shortcode('lightandpulse_lead_form', function ($atts) {
    $atts = shortcode_atts([
        'button'        => __('Send enquiry', 'lightandpulse'),
        'services'      => 'yes',
        'success'       => __('Thank you! We will reply on WhatsApp or email within one working day.', 'lightandpulse'),
    ], $atts, 'lightandpulse_lead_form');
    return lpl_render_lead_form([
        'button'   => $atts['button'],
        'services' => $atts['services'] !== 'no',
        'success'  => $atts['success'],
    ]);
});

function lpl_render_lead_form(array $args = []): string
{
    static $instance = 0;
    $instance++;
    $args += ['button' => __('Send enquiry', 'lightandpulse'), 'services' => true, 'success' => __('Thank you! We will be in touch soon.', 'lightandpulse')];
    $id = 'lp-lead-form' . ($instance > 1 ? '-' . $instance : '');
    $ts = (string) time();
    $status = isset($_GET['lp_form']) ? sanitize_key(wp_unslash($_GET['lp_form'])) : '';

    ob_start();
    ?>
    <div class="lp-lead-form-wrap" id="<?php echo esc_attr($id); ?>">
      <?php if ($status === 'sent') : ?>
        <p class="lp-form-notice" role="status"><?php echo esc_html($args['success']); ?></p>
      <?php elseif ($status === 'error') : ?>
        <p class="lp-form-notice is-error" role="alert"><?php esc_html_e('Please fill in your name and a WhatsApp number or email, then try again.', 'lightandpulse'); ?></p>
      <?php elseif ($status === 'wait') : ?>
        <p class="lp-form-notice is-error" role="alert"><?php esc_html_e('Too many enquiries from your connection. Please try again later or contact us on WhatsApp.', 'lightandpulse'); ?></p>
      <?php endif; ?>
      <form class="lp-lead-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="lpl_public_lead">
        <input type="hidden" name="lp_ts" value="<?php echo esc_attr($ts); ?>">
        <input type="hidden" name="lp_sig" value="<?php echo esc_attr(wp_hash('lpl_form|' . $ts)); ?>">
        <input type="hidden" name="lp_anchor" value="<?php echo esc_attr($id); ?>">
        <label class="lp-hp" aria-hidden="true">Leave empty<input type="text" name="lp_company_url" tabindex="-1" autocomplete="off"></label>
        <div class="lp-row">
          <label><span><?php esc_html_e('Your name *', 'lightandpulse'); ?></span><input type="text" name="lp_name" required autocomplete="name"></label>
          <label><span><?php esc_html_e('Business name', 'lightandpulse'); ?></span><input type="text" name="lp_business" autocomplete="organization"></label>
        </div>
        <div class="lp-row">
          <label><span><?php esc_html_e('WhatsApp', 'lightandpulse'); ?></span><input type="tel" name="lp_phone" autocomplete="tel" placeholder="0812…"></label>
          <label><span><?php esc_html_e('Email', 'lightandpulse'); ?></span><input type="email" name="lp_email" autocomplete="email"></label>
        </div>
        <label><span><?php esc_html_e('Current website (if any)', 'lightandpulse'); ?></span><input type="url" name="lp_website" placeholder="https://"></label>
        <?php if ($args['services']) : ?>
          <fieldset class="lp-services-wrap">
            <legend><?php esc_html_e('What do you need?', 'lightandpulse'); ?></legend>
            <div class="lp-services">
              <?php foreach (LPL_SERVICES as $k => $label) : ?>
                <label><input type="checkbox" name="lp_services[]" value="<?php echo esc_attr($k); ?>"> <?php echo esc_html($label); ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endif; ?>
        <label><span><?php esc_html_e('Message', 'lightandpulse'); ?></span><textarea name="lp_message" rows="4"></textarea></label>
        <p><button type="submit"><?php echo esc_html($args['button']); ?></button></p>
      </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

add_action('admin_post_nopriv_lpl_public_lead', 'lpl_handle_public_lead');
add_action('admin_post_lpl_public_lead', 'lpl_handle_public_lead');

function lpl_handle_public_lead(): void
{
    $back = wp_get_referer() ?: home_url('/');
    $back = remove_query_arg('lp_form', $back);
    $anchor = sanitize_html_class(lpl_post('lp_anchor', 'lp-lead-form')) ?: 'lp-lead-form';
    $go = function (string $status) use ($back, $anchor) {
        wp_safe_redirect(add_query_arg('lp_form', $status, $back) . '#' . $anchor);
        exit;
    };

    // Bots: honeypot filled, bad signature, or submitted too fast. Pretend success.
    $ts = (int) lpl_post('lp_ts');
    $sigOk = hash_equals(wp_hash('lpl_form|' . $ts), lpl_post('lp_sig'));
    if (lpl_post('lp_company_url') !== '' || !$sigOk || time() - $ts < 3 || time() - $ts > 7 * DAY_IN_SECONDS) {
        $go('sent');
    }

    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $rateKey = 'lpl_rate_' . md5($ip);
    $count = (int) get_transient($rateKey);
    if ($count >= 5) {
        $go('wait');
    }
    set_transient($rateKey, $count + 1, HOUR_IN_SECONDS);

    $name = sanitize_text_field(lpl_post('lp_name'));
    $phone = sanitize_text_field(lpl_post('lp_phone'));
    $email = sanitize_email(lpl_post('lp_email'));
    if ($name === '' || ($phone === '' && $email === '')) {
        $go('error');
    }
    $business = sanitize_text_field(lpl_post('lp_business'));
    $message = sanitize_textarea_field(lpl_post('lp_message'));
    $services = array_values(array_intersect(array_keys(LPL_SERVICES), array_map('sanitize_key', (array) ($_POST['lp_services'] ?? []))));

    $id = lpl_insert_lead([
        'name'           => mb_substr($business !== '' ? $business : $name, 0, 200),
        'contact_person' => $name,
        'phone'          => $phone,
        'whatsapp'       => lpl_wa_number($phone),
        'email'          => $email,
        'website'        => lpl_normalize_url(lpl_post('lp_website')),
        'stage'          => 'new',
        'priority'       => 'hot',
        'services'       => implode(',', $services),
        'source'         => 'website_form',
        'next_followup'  => lpl_today(),
        'summary'        => $message,
    ], 'Enquiry from the website form' . ($message !== '' ? ":\n\n" . $message : '.'));

    if ($id && (int) lpl_setting('form_notify') === 1) {
        $to = lpl_setting('my_email') ?: get_option('admin_email');
        $lines = [
            'Name: ' . $name,
            'Business: ' . ($business ?: '—'),
            'WhatsApp: ' . ($phone ?: '—'),
            'Email: ' . ($email ?: '—'),
            'Services: ' . ($services ? implode(', ', array_map(fn($s) => LPL_SERVICES[$s], $services)) : '—'),
            '',
            $message,
            '',
            'Open the lead: ' . lpl_url('leads', ['lead' => $id]),
        ];
        $headers = $email ? ['Reply-To: ' . $name . ' <' . $email . '>'] : [];
        wp_mail($to, sprintf('[%s] New enquiry: %s', get_bloginfo('name'), $business ?: $name), implode("\n", $lines), $headers);
    }
    $go('sent');
}

// Elementor widget
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once LPL_DIR . '/elementor-widget.php';
    $widgets_manager->register(new \Lightandpulse_Lead_Form_Widget());
});
