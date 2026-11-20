<?php
/**
 * Public order form: [lightandpulse_design_order] and its submission handler.
 *
 * Spam protection: honeypot, signed timestamp and a per-IP rate limit (no
 * nonce, so the form keeps working on cached pages). Personal data notice and
 * consent follow UU 27/2022 (UU PDP): order handling rests on the contract,
 * marketing needs a separate, unticked consent, and each consent is recorded.
 */
defined('ABSPATH') || exit;

const LPDO_ALLOWED_MIMES = [
    'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
    'pdf' => 'application/pdf', 'zip' => 'application/zip', 'txt' => 'text/plain', 'csv' => 'text/csv',
    'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];

add_shortcode('lightandpulse_design_order', function () {
    return lpdo_render_form();
});

add_action('wp_enqueue_scripts', function () {
    wp_register_style('lpdo-form', LPDO_URL . 'assets/form.css', [], LPDO_VERSION);
    wp_register_script('lpdo-form', LPDO_URL . 'assets/form.js', [], LPDO_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
});

/** Icons for the design type cards (inline, no external requests). */
function lpdo_type_icon(string $type): string
{
    $paths = [
        'poster'        => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M8 7h8M8 11h8M8 15h5"/>',
        'menu'          => '<path d="M4 4h7v16H4zM13 4h7v16h-7z"/><path d="M6.5 8h2M6.5 12h2M15.5 8h2M15.5 12h2"/>',
        'flyer'         => '<path d="M6 3h9l3 3v15H6z"/><path d="M15 3v3h3M9 11h6M9 15h6"/>',
        'backdrop'      => '<rect x="2" y="4" width="20" height="12" rx="1"/><path d="M7 20h10M12 16v4"/>',
        'business_card' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M13 10h5M13 14h4"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$type] ?? '') . '</svg>';
}

/** One form control. $value is the saved/old value. */
function lpdo_render_field(array $f, $value = null, string $prefix = 'lpdo'): string
{
    $t = 'lightandpulse-design-orders';
    $key = $f['key'];
    $id = 'lpdo-' . $key;
    $name = $prefix . '[' . $key . ']';
    $req = !empty($f['required']);
    $reqAttr = $req ? ' required data-required="1"' : '';
    $label = esc_html($f['label']) . ($req ? ' <span class="lpdo-req" aria-hidden="true">*</span>' : '');
    $value = $value ?? ($f['default'] ?? '');
    $help = !empty($f['help']) ? '<small class="lpdo-help" id="' . esc_attr($id) . '-help">' . esc_html($f['help']) . '</small>' : '';
    $describedby = !empty($f['help']) ? ' aria-describedby="' . esc_attr($id) . '-help"' : '';
    $ph = !empty($f['placeholder']) ? ' placeholder="' . esc_attr($f['placeholder']) . '"' : '';
    $width = 'lpdo-w-' . ($f['width'] ?? 'full');

    switch ($f['type']) {
        case 'textarea':
            $control = '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="4"' . $ph . $reqAttr . $describedby . '>' . esc_textarea((string) $value) . '</textarea>';
            break;
        case 'select':
            $control = '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"' . $reqAttr . $describedby . '><option value="">' . esc_html__('Pilih…', $t) . '</option>';
            foreach ($f['options'] as $k => $o) {
                $control .= '<option value="' . esc_attr($k) . '"' . selected((string) $value, (string) $k, false) . '>' . esc_html($o) . '</option>';
            }
            $control .= '</select>';
            break;
        case 'radio':
        case 'checkboxes':
            $multi = $f['type'] === 'checkboxes';
            $out = '<fieldset class="lpdo-field lpdo-choice ' . esc_attr($width) . '"' . $describedby . '><legend>' . $label . '</legend><div class="lpdo-options">';
            foreach ($f['options'] as $k => $o) {
                $checked = $multi ? in_array((string) $k, array_map('strval', (array) $value), true) : (string) $value === (string) $k;
                $out .= '<label class="lpdo-option"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="' . esc_attr($name . ($multi ? '[]' : '')) . '" value="' . esc_attr($k) . '"' . checked($checked, true, false) . ($req && !$multi ? ' required data-required="1"' : '') . '> <span>' . esc_html($o) . '</span></label>';
            }
            return $out . '</div>' . $help . '</fieldset>';
        case 'file':
            $multiple = !empty($f['multiple']);
            $control = '<input type="file" id="' . esc_attr($id) . '" name="lpdo_files[' . esc_attr($key) . ']' . ($multiple ? '[]' : '') . '"' . ($multiple ? ' multiple' : '') . (!empty($f['accept']) ? ' accept="' . esc_attr($f['accept']) . '"' : '') . $describedby . '>';
            if (is_array($value) && $value) {
                $control .= '<small class="lpdo-help">' . esc_html(sprintf(_n('%d file sudah tersimpan.', '%d file sudah tersimpan.', count($value), $t), count($value))) . '</small>';
            }
            break;
        default:
            $types = ['email' => 'email', 'tel' => 'tel', 'url' => 'url', 'number' => 'number', 'date' => 'date', 'color' => 'color'];
            $type = $types[$f['type']] ?? 'text';
            $extra = $type === 'number' ? ' min="0"' . (!empty($f['step']) ? ' step="' . esc_attr($f['step']) . '"' : '') : '';
            $extra .= $type === 'date' ? ' min="' . esc_attr(wp_date('Y-m-d')) . '"' : '';
            $auto = ['contact_name' => 'name', 'email' => 'email', 'whatsapp' => 'tel', 'business_name' => 'organization', 'website' => 'url'][$key] ?? '';
            $control = '<input type="' . $type . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr(is_array($value) ? '' : (string) $value) . '"' . $ph . $extra . $reqAttr . $describedby . ($auto ? ' autocomplete="' . $auto . '"' : '') . '>';
    }
    return '<div class="lpdo-field ' . esc_attr($width) . ' lpdo-type-' . esc_attr($f['type']) . '"><label for="' . esc_attr($id) . '">' . $label . '</label>' . $control . $help . '</div>';
}

function lpdo_render_group(array $group, string $groupKey, array $values, string $prefix = 'lpdo'): string
{
    $types = !empty($group['types']) ? ' data-types="' . esc_attr(implode(' ', $group['types'])) . '"' : '';
    $html = '<section class="lpdo-group" data-group="' . esc_attr($groupKey) . '"' . $types . '><h3>' . esc_html($group['title']) . '</h3>';
    if (!empty($group['intro'])) {
        $html .= '<p class="lpdo-intro">' . esc_html($group['intro']) . '</p>';
    }
    $html .= '<div class="lpdo-grid">';
    foreach ($group['fields'] as $f) {
        $html .= lpdo_render_field($f, $values[$f['key']] ?? null, $prefix);
    }
    return $html . '</div></section>';
}

/** The order form. With $client (portal), business details are pre-filled and folded away. */
function lpdo_render_form(?WP_Post $client = null): string
{
    $t = 'lightandpulse-design-orders';
    wp_enqueue_style('lpdo-form');
    wp_enqueue_script('lpdo-form');

    // Values from a failed submission (kept for 15 minutes) or from the client record.
    $old = [];
    $errors = [];
    if (!empty($_GET['lpdo_form']) && preg_match('/^[a-z0-9]{16}$/', (string) $_GET['lpdo_form'])) {
        $saved = get_transient('lpdo_form_' . sanitize_key(wp_unslash($_GET['lpdo_form'])));
        if (is_array($saved)) {
            $old = $saved['values'];
            $errors = $saved['errors'];
        }
    }
    $clientValues = [];
    if ($client) {
        foreach (lpdo_client_fields() as $g) {
            foreach ($g['fields'] as $f) {
                $clientValues[$f['key']] = $f['type'] === 'file' ? [] : lpdo_client_meta($client->ID, $f['key']);
            }
        }
    }
    $values = array_merge($clientValues, $old);
    $agency = lpdo_setting('agency_name');
    $ts = (string) time();

    ob_start();
    ?>
    <div class="lpdo" id="lpdo-order-form">
      <?php if ($errors) : ?>
        <div class="lpdo-notice is-error" role="alert"><p><strong><?php esc_html_e('Ada yang perlu dilengkapi:', $t); ?></strong></p><ul><?php foreach ($errors as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form class="lpdo-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-max-mb="<?php echo (int) lpdo_setting('max_upload_mb'); ?>">
        <input type="hidden" name="action" value="lpdo_submit">
        <input type="hidden" name="lpdo_ts" value="<?php echo esc_attr($ts); ?>">
        <input type="hidden" name="lpdo_sig" value="<?php echo esc_attr(wp_hash('lpdo_form|' . $ts)); ?>">
        <input type="hidden" name="lpdo_back" value="<?php echo esc_url(remove_query_arg(['lpdo_form', 'lpdo'])); ?>">
        <?php if ($client) : ?>
          <input type="hidden" name="lpdo_client_id" value="<?php echo (int) $client->ID; ?>">
          <input type="hidden" name="lpdo_client_key" value="<?php echo esc_attr(lpdo_client_key($client->ID)); ?>">
        <?php endif; ?>
        <label class="lpdo-hp" aria-hidden="true">Website URL<input type="text" name="lpdo_company_url" tabindex="-1" autocomplete="off"></label>

        <div class="lpdo-step">
          <h2><span class="lpdo-step-num">1</span> <?php esc_html_e('Bisnis & branding', $t); ?></h2>
          <?php if ($client) : ?>
            <details class="lpdo-fold"><summary><?php printf(esc_html__('Memesan sebagai %s. Ubah data bisnis & branding', $t), '<strong>' . esc_html(get_the_title($client)) . '</strong>'); ?></summary>
          <?php endif; ?>
          <?php foreach (lpdo_client_fields() as $gk => $g) { echo lpdo_render_group($g, $gk, $values, 'lpdo_client'); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
          <?php if ($client) : ?></details><?php endif; ?>
        </div>

        <div class="lpdo-step">
          <h2><span class="lpdo-step-num">2</span> <?php esc_html_e('Jenis desain', $t); ?></h2>
          <fieldset class="lpdo-types">
            <legend class="screen-reader-text"><?php esc_html_e('Pilih jenis desain', $t); ?></legend>
            <?php foreach (lpdo_design_types() as $k => $type) : ?>
              <label class="lpdo-type-card">
                <input type="radio" name="lpdo_type" value="<?php echo esc_attr($k); ?>" required <?php checked(($old['_type'] ?? '') === $k); ?>>
                <span class="lpdo-type-icon"><?php echo lpdo_type_icon($k); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
                <span class="lpdo-type-name"><?php echo esc_html($type['label']); ?></span>
                <span class="lpdo-type-desc"><?php echo esc_html($type['desc']); ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        </div>

        <div class="lpdo-step lpdo-details">
          <h2><span class="lpdo-step-num">3</span> <?php esc_html_e('Detail order', $t); ?></h2>
          <p class="lpdo-intro lpdo-pick-first"><?php esc_html_e('Pilih jenis desain di atas untuk melihat pertanyaan yang sesuai.', $t); ?></p>
          <?php foreach (lpdo_order_groups() as $gk => $g) { echo lpdo_render_group($g, $gk, $old); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
        </div>

        <div class="lpdo-step lpdo-privacy">
          <p><?php
            echo esc_html(sprintf(__('Data yang Anda isi (nama, kontak, data bisnis, logo dan file) diproses oleh %s untuk membuat dan mengirim desain pesanan Anda.', $t), $agency));
            if (lpdo_privacy_url()) {
                echo ' <a href="' . esc_url(lpdo_privacy_url()) . '" target="_blank" rel="noopener">' . esc_html__('Kebijakan Privasi', $t) . '</a>';
            }
          ?></p>
          <details>
            <summary><?php esc_html_e('Rincian pemrosesan data pribadi', $t); ?></summary>
            <?php echo lpdo_privacy_details(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
          </details>
          <label class="lpdo-option lpdo-consent lpdo-consent-cards" data-types="business_card">
            <input type="checkbox" name="pdp_consent_third_party" value="1" data-required="1">
            <span><?php esc_html_e('Saya menyatakan setiap orang yang datanya dicantumkan di kartu nama telah setuju datanya dicetak.', $t); ?></span>
          </label>
          <label class="lpdo-option lpdo-consent">
            <input type="checkbox" name="pdp_consent_marketing" value="1">
            <span><?php echo esc_html(sprintf(__('Saya bersedia menerima info promo dan penawaran dari %s lewat WhatsApp atau email (opsional, bisa dibatalkan kapan saja).', $t), $agency)); ?></span>
          </label>
          <input type="hidden" name="pdp_context" value="design_order">
          <input type="hidden" name="pdp_notice_version" value="<?php echo esc_attr(LPDO_NOTICE_VERSION); ?>">
        </div>

        <div class="lpdo-submit">
          <button type="submit" class="lpdo-button"><?php esc_html_e('Kirim order', $t); ?></button>
          <p class="lpdo-help"><?php echo esc_html(sprintf(__('Dengan menekan Kirim order, Anda meminta %s memproses data di atas untuk pesanan ini.', $t), $agency)); ?></p>
        </div>
      </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

/** The 7 elements of UU PDP Pasal 21 (1), plus recipients and contact. */
function lpdo_privacy_details(): string
{
    $t = 'lightandpulse-design-orders';
    $agency = esc_html(lpdo_setting('agency_name'));
    $email = esc_html((string) lpdo_setting('notify_email'));
    $months = (int) lpdo_setting('retention_months');
    $rows = [
        __('Pengendali data', $t) => $agency . ($email ? ' · ' . $email : ''),
        __('Dasar pemrosesan', $t) => esc_html__('Pelaksanaan perjanjian atas pesanan desain Anda (UU 27/2022 Pasal 20 ayat 2 huruf b). Info promo hanya dengan persetujuan terpisah (huruf a).', $t),
        __('Tujuan', $t) => esc_html__('Memproses pesanan, menghubungi Anda lewat WhatsApp/email, menampilkan halaman klien dan file desain, serta administrasi tagihan.', $t),
        __('Jenis data', $t) => esc_html__('Nama bisnis, nama kontak, nomor WhatsApp, email, Instagram/website, alamat (opsional), logo, file referensi dan foto. Untuk kartu nama: nama, jabatan, telepon dan email orang yang dicantumkan.', $t),
        __('Penerima', $t) => esc_html(sprintf(__('Tim %s, penyedia hosting website ini, dan percetakan (hanya file desain, bila cetak diurus kami).', $t), lpdo_setting('agency_name'))),
        __('Jangka waktu & retensi', $t) => $months > 0
            ? esc_html(sprintf(__('Disimpan selama hubungan kerja, lalu dihapus atau dianonimkan %d bulan setelah aktivitas terakhir.', $t), $months))
            : esc_html__('Disimpan selama hubungan kerja dan dihapus atas permintaan Anda.', $t),
        __('Hak Anda', $t) => esc_html(sprintf(__('Mengakses, memperbaiki, menghapus data, menarik persetujuan dan membatasi pemrosesan. Hubungi %s; kami tanggapi paling lambat 3 × 24 jam.', $t), lpdo_setting('notify_email'))),
        __('Keamanan', $t) => esc_html__('Halaman klien hanya bisa dibuka dengan link rahasia. Jangan bagikan link tersebut kepada pihak lain.', $t),
    ];
    $html = '<dl class="lpdo-dl">';
    foreach ($rows as $k => $v) {
        $html .= '<dt>' . esc_html($k) . '</dt><dd>' . $v . '</dd>';
    }
    return $html . '</dl>';
}

// ---------------------------------------------------------------------------
// Submission
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_lpdo_submit', 'lpdo_handle_submit');
add_action('admin_post_lpdo_submit', 'lpdo_handle_submit');

function lpdo_handle_submit(): void
{
    $t = 'lightandpulse-design-orders';
    $back = wp_validate_redirect(esc_url_raw(wp_unslash($_POST['lpdo_back'] ?? '')), home_url('/'));
    $fail = function (array $errors, array $values) use ($back) {
        $token = strtolower(wp_generate_password(16, false));
        set_transient('lpdo_form_' . $token, ['errors' => $errors, 'values' => $values], 15 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('lpdo_form', $token, $back) . '#lpdo-order-form');
        exit;
    };

    // Bots: honeypot, bad signature or too fast. Pretend it worked.
    $ts = (int) ($_POST['lpdo_ts'] ?? 0);
    if (!empty($_POST['lpdo_company_url']) || !hash_equals(wp_hash('lpdo_form|' . $ts), (string) ($_POST['lpdo_sig'] ?? '')) || time() - $ts < 4 || time() - $ts > 2 * DAY_IN_SECONDS) {
        wp_safe_redirect(add_query_arg('lpdo', 'sent', $back));
        exit;
    }
    $rateKey = 'lpdo_rate_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ((int) get_transient($rateKey) >= 6) {
        $fail([__('Terlalu banyak order dari koneksi Anda. Coba lagi dalam satu jam atau hubungi kami lewat WhatsApp.', $t)], []);
    }

    // Existing client (portal) or a new one.
    $client = null;
    if (!empty($_POST['lpdo_client_id'])) {
        $client = lpdo_client_by_key(sanitize_text_field(wp_unslash($_POST['lpdo_client_key'] ?? '')));
        if (!$client || $client->ID !== (int) $_POST['lpdo_client_id']) {
            wp_die(esc_html__('Link halaman klien tidak valid.', $t), 403);
        }
    }

    $type = sanitize_key(wp_unslash($_POST['lpdo_type'] ?? ''));
    $errors = [];
    if (!isset(lpdo_design_types()[$type])) {
        $errors[] = __('Pilih jenis desain.', $t);
        $type = '';
    }

    // Client fields.
    $clientIn = (array) wp_unslash($_POST['lpdo_client'] ?? []);
    $clientIn = is_array($clientIn) ? $clientIn : [];
    $clientVals = [];
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            if ($f['type'] === 'file') {
                continue;
            }
            $clientVals[$f['key']] = lpdo_sanitize_value($f, $clientIn[$f['key']] ?? '');
            if (!empty($f['required']) && $clientVals[$f['key']] === '') {
                $errors[] = sprintf(__('%s wajib diisi.', $t), $f['label']);
            }
        }
    }
    if ($clientVals['email'] !== '' && !is_email($clientVals['email'])) {
        $errors[] = __('Alamat email tidak valid.', $t);
    }

    // Order fields for the chosen type only.
    $orderIn = (array) wp_unslash($_POST['lpdo'] ?? []);
    $orderVals = [];
    foreach ($type ? lpdo_fields_for_type($type) : [] as $key => $f) {
        if ($f['type'] === 'file') {
            continue;
        }
        $orderVals[$key] = lpdo_sanitize_value($f, $orderIn[$key] ?? '');
        if (!empty($f['required']) && ($orderVals[$key] === '' || $orderVals[$key] === [])) {
            $errors[] = sprintf(__('%s wajib diisi.', $t), $f['label']);
        }
    }
    if ($type === 'business_card' && empty($_POST['pdp_consent_third_party'])) {
        $errors[] = __('Centang pernyataan bahwa orang yang datanya dicantumkan di kartu nama sudah setuju.', $t);
    }
    if ($errors) {
        $fail($errors, array_merge($clientVals, $orderVals, ['_type' => $type]));
    }
    set_transient($rateKey, (int) get_transient($rateKey) + 1, HOUR_IN_SECONDS);

    // Save the client.
    if (!$client) {
        $clientId = wp_insert_post(['post_type' => 'lpdo_client', 'post_status' => 'publish', 'post_title' => $clientVals['business_name'], 'post_name' => sanitize_title($clientVals['business_name'])], true);
        if (is_wp_error($clientId)) {
            wp_die(esc_html__('Order tidak dapat disimpan. Silakan coba lagi.', $t));
        }
        lpdo_client_key($clientId);
    } else {
        $clientId = $client->ID;
    }
    foreach ($clientVals as $k => $v) {
        update_post_meta($clientId, '_lpdo_' . $k, $v);
    }
    update_post_meta($clientId, '_lpdo_last_activity', time());

    // Save the order.
    $types = lpdo_design_types();
    $orderId = wp_insert_post([
        'post_type' => 'lpdo_order', 'post_status' => 'publish', 'post_parent' => $clientId,
        'post_title' => $types[$type]['label'] . ': ' . $orderVals['title'],
    ], true);
    if (is_wp_error($orderId)) {
        wp_die(esc_html__('Order tidak dapat disimpan. Silakan coba lagi.', $t));
    }
    update_post_meta($orderId, '_lpdo_type', $type);
    update_post_meta($orderId, '_lpdo_status', 'new');
    update_post_meta($orderId, '_lpdo_deadline', $orderVals['deadline'] ?? '');

    // Uploads: the logo goes to the client, everything else to the order.
    $uploadErrors = [];
    $logo = lpdo_handle_uploads('logo', $clientId, $uploadErrors);
    if ($logo) {
        set_post_thumbnail($clientId, $logo[0]);
    }
    foreach (lpdo_fields_for_type($type) as $key => $f) {
        if ($f['type'] === 'file') {
            $ids = lpdo_handle_uploads($key, $orderId, $uploadErrors);
            if ($ids) {
                $orderVals[$key] = $ids;
            }
        }
    }
    update_post_meta($orderId, '_lpdo_fields', $orderVals);

    // Consent record (UU PDP Pasal 24): time, notice version, purposes.
    update_post_meta($orderId, '_lpdo_consent', [
        'time'        => current_time('mysql'),
        'version'     => sanitize_text_field(wp_unslash($_POST['pdp_notice_version'] ?? LPDO_NOTICE_VERSION)),
        'context'     => 'design_order',
        'basis'       => 'contract',
        'marketing'   => !empty($_POST['pdp_consent_marketing']),
        'third_party' => !empty($_POST['pdp_consent_third_party']),
    ]);
    if (!empty($_POST['pdp_consent_marketing'])) {
        update_post_meta($clientId, '_lpdo_marketing_consent', current_time('mysql'));
    }
    lpdo_log($orderId, __('Order dikirim oleh klien.', $t), 'client');
    if ($uploadErrors) {
        lpdo_log($orderId, implode(' ', $uploadErrors), 'system');
    }

    lpdo_sync_lead($clientId, $orderId);
    do_action('lpdo_order_created', $orderId, $clientId);

    wp_safe_redirect(add_query_arg('lpdo', 'sent', lpdo_portal_url($clientId)) . '#order-' . $orderId);
    exit;
}

/**
 * Save the files posted for one field as attachments of $parentId.
 * Only listed file types and sizes are accepted; returns attachment IDs.
 */
function lpdo_handle_uploads(string $key, int $parentId, array &$errors): array
{
    if (empty($_FILES['lpdo_files']['name'][$key])) {
        return [];
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $files = $_FILES['lpdo_files']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated per file below.
    $names = (array) $files['name'][$key];
    $max = max(1, (int) lpdo_setting('max_upload_mb')) * MB_IN_BYTES;
    $ids = [];
    foreach ($names as $i => $name) {
        if ($name === '' || count($ids) >= 10) {
            continue;
        }
        $file = [
            'name'     => sanitize_file_name((string) $name),
            'type'     => (string) (array_values((array) $files['type'][$key])[$i] ?? ''),
            'tmp_name' => (string) (array_values((array) $files['tmp_name'][$key])[$i] ?? ''),
            'error'    => (int) (array_values((array) $files['error'][$key])[$i] ?? UPLOAD_ERR_NO_FILE),
            'size'     => (int) (array_values((array) $files['size'][$key])[$i] ?? 0),
        ];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            continue;
        }
        if ($file['size'] > $max) {
            $errors[] = sprintf(__('%s dilewati: lebih besar dari batas ukuran.', 'lightandpulse-design-orders'), $file['name']);
            continue;
        }
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], LPDO_ALLOWED_MIMES);
        if (empty($check['ext'])) {
            $errors[] = sprintf(__('%s dilewati: jenis file tidak diizinkan.', 'lightandpulse-design-orders'), $file['name']);
            continue;
        }
        $id = media_handle_sideload($file, $parentId, null, ['test_form' => false, 'mimes' => LPDO_ALLOWED_MIMES]);
        if (!is_wp_error($id)) {
            update_post_meta($id, '_lpdo_upload', $key);
            $ids[] = (int) $id;
        }
    }
    return $ids;
}

/** Add the client to Lightandpulse Leads (theme CRM) when it is active. */
function lpdo_sync_lead(int $clientId, int $orderId): void
{
    if (!function_exists('lpl_insert_lead') || !function_exists('lpl_log_activity')) {
        return;
    }
    $leadId = (int) get_post_meta($clientId, '_lpdo_lead_id', true);
    if (!$leadId || !lpl_find_lead($leadId)) {
        $leadId = lpl_insert_lead([
            'name'           => lpdo_client_meta($clientId, 'business_name'),
            'contact_person' => lpdo_client_meta($clientId, 'contact_name'),
            'phone'          => lpdo_client_meta($clientId, 'whatsapp'),
            'whatsapp'       => lpl_wa_number(lpdo_client_meta($clientId, 'whatsapp')),
            'email'          => lpdo_client_meta($clientId, 'email'),
            'website'        => lpdo_client_meta($clientId, 'website'),
            'instagram'      => ltrim(lpdo_client_meta($clientId, 'instagram'), '@'),
            'stage'          => 'replied',
            'priority'       => 'hot',
            'source'         => 'website_form',
            'tags'           => get_post_meta($clientId, '_lpdo_marketing_consent', true) ? 'design-order, marketing-consent' : 'design-order',
            'next_followup'  => wp_date('Y-m-d'),
        ], __('Klien dari form Order Desain.', 'lightandpulse-design-orders'));
        if ($leadId) {
            update_post_meta($clientId, '_lpdo_lead_id', $leadId);
        }
    }
    if ($leadId) {
        lpl_log_activity($leadId, 'note', sprintf(__('Order desain baru %1$s: %2$s', 'lightandpulse-design-orders'), lpdo_order_ref($orderId), get_the_title($orderId)));
    }
}
