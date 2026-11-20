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
    wp_register_script('lpdo-preview', LPDO_URL . 'assets/preview.js', [], LPDO_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
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
        'banner'        => '<path d="M7 3h10v14H7z"/><path d="M12 17v4M8 21h8M10 7h4M10 10h4"/>',
        'greeting'      => '<path d="M3 8l9-5 9 5v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M3 8l9 6 9-6"/>',
        'digital'       => '<rect x="2" y="4" width="14" height="10" rx="1"/><rect x="17" y="8" width="5" height="10" rx="1"/><path d="M6 18h6"/>',
        'directory'     => '<path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z"/><path d="M5 17a3 3 0 0 1 3-3h11M9 8h6"/>',
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
    $typesAttr = !empty($f['types']) ? ' data-types="' . esc_attr(implode(' ', $f['types'])) . '"' : '';

    switch ($f['type']) {
        case 'textarea':
            $control = '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="' . (int) ($f['rows'] ?? 4) . '"' . $ph . $reqAttr . $describedby . '>' . esc_textarea((string) $value) . '</textarea>';
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
            $out = '<fieldset class="lpdo-field lpdo-choice ' . esc_attr($width) . '"' . $typesAttr . $describedby . '><legend>' . $label . '</legend><div class="lpdo-options">';
            foreach ($f['options'] as $k => $o) {
                $checked = $multi ? in_array((string) $k, array_map('strval', (array) $value), true) : (string) $value === (string) $k;
                $out .= '<label class="lpdo-option"><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="' . esc_attr($name . ($multi ? '[]' : '')) . '" value="' . esc_attr($k) . '"' . checked($checked, true, false) . ($req && !$multi ? ' required data-required="1"' : '') . '> <span>' . esc_html($o) . '</span></label>';
            }
            return $out . '</div>' . $help . '</fieldset>';
        case 'file':
            $multiple = !empty($f['multiple']);
            $control = '<div class="lpdo-drop" data-drop>'
                . '<div class="lpdo-drop__zone"><input type="file" class="lpdo-drop__input" id="' . esc_attr($id) . '" name="lpdo_files[' . esc_attr($key) . ']' . ($multiple ? '[]' : '') . '"' . ($multiple ? ' multiple' : '') . (!empty($f['accept']) ? ' accept="' . esc_attr($f['accept']) . '"' : '') . $describedby . '>'
                . '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>'
                . '<span class="lpdo-drop__text"><span class="lpdo-drop__btn">' . esc_html__('Pilih file', $t) . '</span> ' . esc_html($multiple ? __('atau seret ke sini. Bisa lebih dari satu.', $t) : __('atau seret ke sini.', $t)) . '</span></div>'
                . '<ul class="lpdo-drop__list" aria-live="polite"></ul></div>';
            if (is_array($value) && $value) {
                $control .= '<small class="lpdo-help">' . esc_html(sprintf(_n('%d file sudah tersimpan.', '%d file sudah tersimpan.', count($value), $t), count($value))) . '</small>';
            }
            break;
        case 'repeater':
            return lpdo_render_repeater($f, $value, $prefix, $id, $label, $help);
        default:
            $types = ['email' => 'email', 'tel' => 'tel', 'url' => 'url', 'number' => 'number', 'date' => 'date', 'color' => 'color'];
            $type = $types[$f['type']] ?? 'text';
            $extra = !empty($f['inputmode']) ? ' inputmode="' . esc_attr($f['inputmode']) . '"' : '';
            $extra .= $type === 'number' ? ' min="0"' . (!empty($f['step']) ? ' step="' . esc_attr($f['step']) . '"' : '') : '';
            $extra .= $type === 'date' ? ' min="' . esc_attr(wp_date('Y-m-d')) . '"' : '';
            $auto = ['contact_name' => 'name', 'email' => 'email', 'whatsapp' => 'tel', 'business_name' => 'organization', 'website' => 'url'][$key] ?? '';
            $control = '<input type="' . $type . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr(is_array($value) ? '' : (string) $value) . '"' . $ph . $extra . $reqAttr . $describedby . ($auto ? ' autocomplete="' . $auto . '"' : '') . '>';
    }
    return '<div class="lpdo-field ' . esc_attr($width) . ' lpdo-type-' . esc_attr($f['type']) . '"' . $typesAttr . '><label for="' . esc_attr($id) . '">' . $label . '</label>' . $control . $help . '</div>';
}

/** A table the client fills row by row (menu items, people on business cards…). */
function lpdo_render_repeater(array $f, $value, string $prefix, string $id, string $label, string $help): string
{
    $t = 'lightandpulse-design-orders';
    $rows = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    $count = max(count($rows), (int) ($f['rows'] ?? 1), 1);
    $tracks = implode(' ', array_map(fn($c) => 'minmax(0,' . (int) ($c['size'] ?? 2) . 'fr)', $f['columns']));
    $row = function ($i, array $data) use ($f, $prefix, $t) {
        $html = '<li class="lpdo-rep__row">';
        foreach ($f['columns'] as $c => $col) {
            $name = $prefix . '[' . $f['key'] . '][' . $i . '][' . $col['key'] . ']';
            $v = (string) ($data[$col['key']] ?? '');
            $req = !empty($col['required']) && !empty($f['required']) && $i === 0 ? ' required data-required="1"' : '';
            $ph = !empty($col['placeholder']) && $i === 0 ? ' placeholder="' . esc_attr($col['placeholder']) . '"' : '';
            $html .= '<label class="lpdo-rep__cell"><span class="lpdo-rep__label">' . esc_html($col['label']) . (!empty($col['required']) ? ' <span class="lpdo-req" aria-hidden="true">*</span>' : '') . '</span>';
            if ($col['type'] === 'select') {
                $html .= '<select name="' . esc_attr($name) . '"' . $req . '><option value="">—</option>';
                foreach ($col['options'] as $k => $o) {
                    $html .= '<option value="' . esc_attr($k) . '"' . selected($v, (string) $k, false) . '>' . esc_html($o) . '</option>';
                }
                $html .= '</select>';
            } else {
                $html .= '<input type="' . ($col['type'] === 'number' ? 'number' : 'text') . '" name="' . esc_attr($name) . '" value="' . esc_attr($v) . '"' . $ph . (!empty($col['inputmode']) ? ' inputmode="' . esc_attr($col['inputmode']) . '"' : '') . $req . '>';
            }
            $html .= '</label>';
        }
        return $html . '<button type="button" class="lpdo-rep__remove" data-rep-remove aria-label="' . esc_attr__('Hapus baris', $t) . '" title="' . esc_attr__('Hapus baris', $t) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></li>';
    };
    $html = '<div class="lpdo-field lpdo-w-full lpdo-type-repeater"' . (!empty($f['types']) ? ' data-types="' . esc_attr(implode(' ', $f['types'])) . '"' : '') . ' role="group" aria-labelledby="' . esc_attr($id) . '-label"><span class="lpdo-label" id="' . esc_attr($id) . '-label">' . $label . '</span>';
    $html .= '<div class="lpdo-rep" id="' . esc_attr($id) . '" data-rep data-next="' . $count . '" style="--rep-cols:' . esc_attr($tracks) . '">';
    $html .= '<div class="lpdo-rep__head" aria-hidden="true">';
    foreach ($f['columns'] as $col) {
        $html .= '<span>' . esc_html($col['label']) . (!empty($col['required']) ? ' <span class="lpdo-req">*</span>' : '') . '</span>';
    }
    $html .= '<span></span></div><ol class="lpdo-rep__rows">';
    for ($i = 0; $i < $count; $i++) {
        $html .= $row($i, $rows[$i] ?? []);
    }
    $html .= '</ol><template>' . $row('__i__', []) . '</template>';
    $html .= '<button type="button" class="lpdo-rep__add" data-rep-add><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> ' . esc_html($f['add_label'] ?? __('Tambah baris', $t)) . '</button>';
    return $html . '</div>' . $help . '</div>';
}

function lpdo_render_group(array $group, string $groupKey, array $values, string $prefix = 'lpdo'): string
{
    $types = !empty($group['types']) ? ' data-types="' . esc_attr(implode(' ', $group['types'])) . '"' : '';
    $fields = '<div class="lpdo-grid">';
    $filled = false;
    foreach ($group['fields'] as $f) {
        $v = $values[$f['key']] ?? null;
        $filled = $filled || ($v !== null && $v !== '' && $v !== []);
        $fields .= lpdo_render_field($f, $v, $prefix);
    }
    $fields .= '</div>';
    $intro = !empty($group['intro']) ? '<p class="lpdo-intro">' . esc_html($group['intro']) . '</p>' : '';

    // Optional groups fold away; they open by themselves when they already hold answers.
    if (!empty($group['optional'])) {
        return '<details class="lpdo-group lpdo-group--optional" data-group="' . esc_attr($groupKey) . '"' . $types . ($filled ? ' open' : '') . '>'
            . '<summary><span class="lpdo-acc__title">' . esc_html($group['title']) . '</span><svg class="lpdo-acc__chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></summary>'
            . '<div class="lpdo-acc__body">' . $intro . $fields . '</div></details>';
    }
    return '<section class="lpdo-group" data-group="' . esc_attr($groupKey) . '"' . $types . '><h3>' . esc_html($group['title']) . '</h3>' . $intro . $fields . '</section>';
}

/** The order form. With $client (portal), business details are pre-filled and folded away. */
function lpdo_render_form(?WP_Post $client = null): string
{
    $t = 'lightandpulse-design-orders';
    wp_enqueue_style('lpdo-form');
    wp_enqueue_script('lpdo-form');
    wp_enqueue_script('lpdo-preview');

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
    // "Duplikat": start from an earlier order of this client (files are reused unless replaced).
    $copyFrom = 0;
    if ($client && !empty($_GET['lpdo_copy'])) {
        $src = (int) $_GET['lpdo_copy'];
        if (get_post_type($src) === 'lpdo_order' && (int) get_post_field('post_parent', $src) === $client->ID) {
            $copyFrom = $src;
            if (!$old) {
                $srcType = lpdo_order_type($src);
                foreach (lpdo_fields_for_type($srcType) as $key => $f) {
                    $v = lpdo_order_fields($src)[$key] ?? null;
                    if ($v !== null && $f['type'] !== 'file') {
                        $old[$key] = $v;
                    }
                }
                $old['_type'] = $srcType;
            }
        }
    }
    $values = array_merge($clientValues, $old);
    $agency = lpdo_setting('agency_name');
    $ts = (string) time();
    // Brand data for the live preview (the public form reads its own inputs instead).
    $brand = ['name' => $values['business_name'] ?? ''];
    foreach (['color_primary', 'color_secondary', 'color_accent', 'contact_print', 'tagline'] as $k) {
        $brand[$k] = (string) ($values[$k] ?? '');
    }
    $logoId = $client ? (int) get_post_thumbnail_id($client->ID) : 0;
    $brand['logo'] = $logoId ? (string) wp_get_attachment_image_url($logoId, 'medium') : '';
    $brand['logo_light'] = $logoId && lpdo_logo_is_light($logoId);

    ob_start();
    ?>
    <div class="lpdo lpdo--preview" id="lpdo-order-form">
      <?php if ($errors) : ?>
        <div class="lpdo-notice is-error" role="alert"><p><strong><?php esc_html_e('Ada yang perlu dilengkapi:', $t); ?></strong></p><ul><?php foreach ($errors as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="lpdo-shell">
      <form class="lpdo-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-max-mb="<?php echo (int) lpdo_setting('max_upload_mb'); ?>" data-max-files="<?php echo (int) lpdo_max_files(); ?>" data-max-post="<?php echo (int) wp_convert_hr_to_bytes((string) ini_get('post_max_size')); ?>">
        <input type="hidden" name="action" value="lpdo_submit">
        <input type="hidden" name="lpdo_ts" value="<?php echo esc_attr($ts); ?>">
        <input type="hidden" name="lpdo_sig" value="<?php echo esc_attr(wp_hash('lpdo_form|' . $ts)); ?>">
        <input type="hidden" name="lpdo_back" value="<?php echo esc_url(remove_query_arg(['lpdo_form', 'lpdo'])); ?>">
        <?php if ($copyFrom) : ?>
          <input type="hidden" name="lpdo_copy_from" value="<?php echo (int) $copyFrom; ?>">
          <div class="lpdo-copy-note"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg><p><?php echo esc_html(sprintf(__('Disalin dari %1$s "%2$s". Ubah yang perlu saja; file dari order itu ikut dipakai kecuali Anda unggah file baru di kolom yang sama.', $t), lpdo_order_ref($copyFrom), lpdo_order_fields($copyFrom)['title'] ?? get_the_title($copyFrom))); ?> <a href="<?php echo esc_url(remove_query_arg('lpdo_copy') . '#new-order'); ?>"><?php esc_html_e('Mulai dari kosong', $t); ?></a></p></div>
        <?php endif; ?>
        <?php if ($client) : ?>
          <input type="hidden" name="lpdo_client_id" value="<?php echo (int) $client->ID; ?>">
          <input type="hidden" name="lpdo_client_key" value="<?php echo esc_attr(lpdo_client_key($client->ID)); ?>">
        <?php endif; ?>
        <label class="lpdo-hp" aria-hidden="true">Website URL<input type="text" name="lpdo_company_url" tabindex="-1" autocomplete="off"></label>

        <?php $n = 0; if (!$client) : ?>
        <div class="lpdo-step">
          <header class="lpdo-step__head"><span class="lpdo-step-num"><?php echo ++$n; ?></span><div><h2><?php esc_html_e('Bisnis & branding', $t); ?></h2><p><?php esc_html_e('Dipakai untuk semua order dan halaman klien Anda.', $t); ?></p></div></header>
          <?php foreach (lpdo_client_fields() as $gk => $g) { echo lpdo_render_group($g, $gk, $values, 'lpdo_client'); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
        </div>
        <?php endif; ?>

        <div class="lpdo-step">
          <header class="lpdo-step__head"><span class="lpdo-step-num"><?php echo ++$n; ?></span><div><h2><?php esc_html_e('Jenis desain', $t); ?></h2><p><?php esc_html_e('Pilih satu. Pertanyaan berikutnya menyesuaikan jenis desain.', $t); ?></p></div></header>
          <fieldset class="lpdo-types">
            <legend class="screen-reader-text"><?php esc_html_e('Pilih jenis desain', $t); ?></legend>
            <?php foreach (lpdo_design_types() as $k => $type) : ?>
              <label class="lpdo-type-card">
                <input type="radio" name="lpdo_type" value="<?php echo esc_attr($k); ?>" required <?php checked(($old['_type'] ?? '') === $k); ?>>
                <span class="lpdo-type-icon"><?php echo lpdo_type_icon($k); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
                <span class="lpdo-type-name"><?php echo esc_html($type['label']); ?></span>
                <span class="lpdo-type-desc"><?php echo esc_html($type['desc']); ?></span>
                <span class="lpdo-type-check" aria-hidden="true"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        </div>

        <div class="lpdo-step lpdo-details">
          <header class="lpdo-step__head"><span class="lpdo-step-num"><?php echo ++$n; ?></span><div><h2><?php esc_html_e('Detail order', $t); ?></h2><p><?php esc_html_e('Semakin lengkap brief-nya, semakin cepat dan tepat hasilnya.', $t); ?></p></div></header>
          <p class="lpdo-intro lpdo-pick-first"><?php esc_html_e('Pilih jenis desain di atas untuk melihat pertanyaan yang sesuai.', $t); ?></p>
          <?php foreach (lpdo_order_groups() as $gk => $g) { echo lpdo_render_group($g, $gk, $old); } // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
        </div>

        <div class="lpdo-step lpdo-privacy">
          <header class="lpdo-step__head"><span class="lpdo-step-num"><?php echo ++$n; ?></span><div><h2><?php esc_html_e('Kirim order', $t); ?></h2><p><?php esc_html_e('Periksa kembali, lalu kirim. Status order bisa dipantau di halaman klien Anda.', $t); ?></p></div></header>
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
        <div class="lpdo-submit">
          <button type="submit" class="lpdo-button"><?php esc_html_e('Kirim order', $t); ?> <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
          <p class="lpdo-submit-error" role="alert" hidden></p>
          <p class="lpdo-help"><?php echo esc_html(sprintf(__('Dengan menekan Kirim order, Anda meminta %s memproses data di atas untuk pesanan ini.', $t), $agency)); ?></p>
        </div>
        </div>
      </form>
      <aside class="lpdo-preview" data-brand="<?php echo esc_attr(wp_json_encode($brand)); ?>" aria-label="<?php esc_attr_e('Pratinjau desain', $t); ?>">
        <div class="lpdo-preview__inner">
          <div class="lpdo-preview__head"><strong><?php esc_html_e('Pratinjau', $t); ?></strong><button type="button" class="lpdo-preview__close" data-preview-close aria-label="<?php esc_attr_e('Tutup pratinjau', $t); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
          <div class="lpdo-preview__stage" aria-live="off"></div>
          <div class="lpdo-preview__chips"></div>
          <p class="lpdo-preview__note"><?php esc_html_e('Gambaran kasar dari isian Anda, bukan desain final. Tim kami yang akan menata desain sebenarnya.', $t); ?></p>
        </div>
      </aside>
      </div>
      <button type="button" class="lpdo-preview-toggle" data-preview-open><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg> <?php esc_html_e('Pratinjau', $t); ?></button>
    </div>
    <?php
    return (string) ob_get_clean();
}

/** Business & branding form in the portal's Brand kit panel. */
function lpdo_render_profile_form(WP_Post $client): string
{
    $t = 'lightandpulse-design-orders';
    $values = [];
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            $values[$f['key']] = $f['type'] === 'file' ? [] : lpdo_client_meta($client->ID, $f['key']);
        }
    }
    $html = '<div class="lpdo"><form class="lpdo-form lpdo-profile-form" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '" data-max-mb="' . (int) lpdo_setting('max_upload_mb') . '" data-max-files="' . (int) lpdo_max_files() . '">';
    $html .= '<input type="hidden" name="action" value="lpdo_portal"><input type="hidden" name="lpdo_decision" value="profile">';
    $html .= '<input type="hidden" name="lpdo_client_key" value="' . esc_attr(lpdo_client_key($client->ID)) . '">';
    foreach (lpdo_client_fields() as $gk => $g) {
        $html .= lpdo_render_group($g, $gk, $values, 'lpdo_client');
    }
    $html .= '<div class="lpdo-submit"><button type="submit" class="lpdo-button">' . esc_html__('Simpan', $t) . '</button><p class="lpdo-submit-error" role="alert" hidden></p>';
    $html .= '<p class="lpdo-help">' . esc_html__('Logo baru menggantikan logo lama. Data ini dipakai untuk order berikutnya.', $t) . '</p></div>';
    return $html . '</form></div>';
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
        __('Jenis data', $t) => esc_html__('Nama bisnis, nama kontak, nomor WhatsApp, telepon kantor, email, Instagram/website, kontak yang dicetak di desain, alamat (opsional), logo, file referensi dan foto. Untuk kartu nama: nama, jabatan, telepon dan email orang yang dicantumkan.', $t),
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

    // Client fields. Orders from the portal use the stored client data.
    $useStored = $client && !isset($_POST['lpdo_client']);
    $clientIn = $useStored ? [] : (array) wp_unslash($_POST['lpdo_client'] ?? []);
    $clientIn = is_array($clientIn) ? $clientIn : [];
    $clientVals = [];
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            if ($f['type'] === 'file') {
                continue;
            }
            $clientVals[$f['key']] = $useStored ? lpdo_client_meta($client->ID, $f['key']) : lpdo_sanitize_value($f, $clientIn[$f['key']] ?? '');
            if (!$useStored && !empty($f['required']) && $clientVals[$f['key']] === '') {
                $errors[] = sprintf(__('%s wajib diisi.', $t), $f['label']);
            }
        }
    }
    if (!$useStored && $clientVals['email'] !== '' && !is_email($clientVals['email'])) {
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
        $errors = array_merge($errors, lpdo_validate_value($f, $orderVals[$key]));
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
    if (!$useStored) {
        foreach ($clientVals as $k => $v) {
            update_post_meta($clientId, '_lpdo_' . $k, $v);
        }
    }
    update_post_meta($clientId, '_lpdo_last_activity', time());

    // Duplicated from an earlier order of the same client?
    $copyFrom = (int) ($_POST['lpdo_copy_from'] ?? 0);
    if ($copyFrom && (get_post_type($copyFrom) !== 'lpdo_order' || (int) get_post_field('post_parent', $copyFrom) !== $clientId)) {
        $copyFrom = 0;
    }

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

    // Uploads: the logo goes to the client, everything else to the order.
    $uploadErrors = [];
    $logo = lpdo_handle_uploads('logo', $clientId, $uploadErrors);
    if ($logo) {
        set_post_thumbnail($clientId, $logo[0]);
    }
    foreach (lpdo_fields_for_type($type) as $key => $f) {
        if ($f['type'] === 'file') {
            $ids = lpdo_handle_uploads($key, $orderId, $uploadErrors);
            if (!$ids && $copyFrom) {
                $ids = array_values(array_filter(array_map('intval', (array) (lpdo_order_fields($copyFrom)[$key] ?? [])), fn($id) => get_post_type($id) === 'attachment'));
            }
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
    lpdo_log($orderId, $copyFrom ? sprintf(__('Order dikirim oleh klien (duplikat dari %s).', $t), lpdo_order_ref($copyFrom)) : __('Order dikirim oleh klien.', $t), 'client');
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
        if ($name === '' || count($ids) >= 20) {
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

/** Files one submission can carry: PHP's max_file_uploads (often 20), capped at 40. */
function lpdo_max_files(): int
{
    $n = (int) ini_get('max_file_uploads');
    return $n > 0 ? min($n, 40) : 20;
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
