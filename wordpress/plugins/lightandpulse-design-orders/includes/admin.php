<?php
/**
 * wp-admin: Design Orders menu, order and client screens, list columns, settings.
 */
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    global $menu;
    $t = 'lightandpulse-design-orders';
    add_submenu_page('edit.php?post_type=lpdo_order', __('Pengaturan', $t), __('Pengaturan', $t), 'manage_options', 'lpdo-settings', 'lpdo_settings_page');
    // Badge: orders that need the team (new, revision requested, approved and waiting for final files).
    $waiting = (int) (new WP_Query(['post_type' => 'lpdo_order', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_query' => [['key' => '_lpdo_status', 'value' => ['new', 'revision', 'approved'], 'compare' => 'IN']]]))->found_posts;
    if ($waiting) {
        foreach ((array) $menu as $i => $item) {
            if (($item[2] ?? '') === 'edit.php?post_type=lpdo_order') {
                $menu[$i][0] .= ' <span class="awaiting-mod">' . $waiting . '</span>';
            }
        }
    }
}, 20);

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['lpdo_order', 'lpdo_client'], true)) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('lpdo-admin', LPDO_URL . 'assets/admin.css', [], LPDO_VERSION);
    wp_enqueue_script('lpdo-admin', LPDO_URL . 'assets/admin.js', ['jquery'], LPDO_VERSION, true);
    wp_enqueue_script('lpdo-form', LPDO_URL . 'assets/form.js', [], LPDO_VERSION, true); // table rows in the brief
});

// ---------------------------------------------------------------------------
// Order screen
// ---------------------------------------------------------------------------
add_action('add_meta_boxes_lpdo_order', function (WP_Post $post) {
    $t = 'lightandpulse-design-orders';
    add_meta_box('lpdo_order_status', __('Status & klien', $t), 'lpdo_box_order_status', 'lpdo_order', 'side', 'high');
    add_meta_box('lpdo_order_files', __('File untuk klien', $t), 'lpdo_box_order_files', 'lpdo_order', 'normal', 'high');
    add_meta_box('lpdo_order_brief', __('Brief dari klien', $t), 'lpdo_box_order_brief', 'lpdo_order', 'normal', 'default');
    add_meta_box('lpdo_order_log', __('Riwayat', $t), 'lpdo_box_order_log', 'lpdo_order', 'normal', 'low');
});

function lpdo_box_order_status(WP_Post $post): void
{
    $t = 'lightandpulse-design-orders';
    wp_nonce_field('lpdo_save_order', '_lpdo_nonce');
    $status = lpdo_order_status($post->ID);
    $type = lpdo_order_type($post->ID);
    $clients = get_posts(['post_type' => 'lpdo_client', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    ?>
    <p><label><b><?php esc_html_e('Status', $t); ?></b><br><select name="lpdo_status" class="widefat"><?php foreach (lpdo_statuses() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($status, $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select></label></p>
    <p><label><input type="checkbox" name="lpdo_notify" value="1" checked> <?php esc_html_e('Email klien saat status menjadi “Menunggu persetujuan” atau “Selesai”', $t); ?></label></p>
    <p><label><b><?php esc_html_e('Jenis desain', $t); ?></b><br><select name="lpdo_type" class="widefat"><?php foreach (lpdo_design_types() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($type, $k); ?>><?php echo esc_html($v['label']); ?></option><?php endforeach; ?></select></label></p>
    <p><label><b><?php esc_html_e('Deadline', $t); ?></b><br><input type="date" name="lpdo_deadline" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_lpdo_deadline', true)); ?>" class="widefat"></label></p>
    <p><label><b><?php esc_html_e('Klien', $t); ?></b><br><select name="lpdo_client_parent" class="widefat"><option value="0">—</option><?php foreach ($clients as $c) : ?><option value="<?php echo (int) $c->ID; ?>" <?php selected($post->post_parent, $c->ID); ?>><?php echo esc_html(get_the_title($c)); ?></option><?php endforeach; ?></select></label></p>
    <?php if ($post->post_parent) : ?>
      <p><a class="button" href="<?php echo esc_url(lpdo_portal_url($post->post_parent) . '#order-' . $post->ID); ?>" target="_blank" rel="noopener"><?php esc_html_e('Buka halaman klien', $t); ?></a>
      <a class="button-link" href="<?php echo esc_url(get_edit_post_link($post->post_parent)); ?>"><?php esc_html_e('Data klien', $t); ?></a></p>
      <?php $wa = function_exists('lpl_wa_number') ? lpl_wa_number(lpdo_client_meta($post->post_parent, 'whatsapp')) : preg_replace('/\D/', '', lpdo_client_meta($post->post_parent, 'whatsapp')); ?>
      <?php if ($wa) : ?><p><a class="button" href="<?php echo esc_url('https://wa.me/' . $wa . '?text=' . rawurlencode(sprintf(__('Halo %1$s, update order %2$s: %3$s', $t), lpdo_client_meta($post->post_parent, 'contact_name'), lpdo_order_ref($post->ID), lpdo_portal_url($post->post_parent)))); ?>" target="_blank" rel="noopener"><?php esc_html_e('Kirim link via WhatsApp', $t); ?></a></p><?php endif; ?>
    <?php endif;
    $consent = lpdo_meta_array($post->ID, '_lpdo_consent');
    if ($consent) : ?>
      <p class="description"><?php echo esc_html(sprintf(__('Persetujuan: %1$s · versi %2$s · promo: %3$s', $t), $consent['time'] ?? '', $consent['version'] ?? '', !empty($consent['marketing']) ? __('ya', $t) : __('tidak', $t))); ?></p>
    <?php endif;
}

function lpdo_box_order_files(WP_Post $post): void
{
    $t = 'lightandpulse-design-orders';
    $downloads = lpdo_meta_array($post->ID, '_lpdo_downloads');
    foreach (['proofs' => [__('Proof / pratinjau', $t), __('Gambar atau PDF untuk diperiksa klien. Status otomatis menjadi “Menunggu persetujuan”.', $t)], 'finals' => [__('File final', $t), __('File yang bisa diunduh klien (link langsung + QR). Status otomatis menjadi “Selesai”.', $t)]] as $kind => [$title, $help]) :
        $ids = lpdo_order_files($post->ID, $kind); ?>
      <div class="lpdo-files-box" data-kind="<?php echo esc_attr($kind); ?>">
        <h4><?php echo esc_html($title); ?></h4>
        <p class="description"><?php echo esc_html($help); ?></p>
        <input type="hidden" name="lpdo_<?php echo esc_attr($kind); ?>" value="<?php echo esc_attr(implode(',', $ids)); ?>">
        <ul class="lpdo-file-list">
          <?php foreach ($ids as $id) : ?>
            <li data-id="<?php echo (int) $id; ?>"><?php echo wp_get_attachment_image($id, [60, 60], true); ?><span><?php echo esc_html(basename((string) get_attached_file($id))); ?><?php if ($kind === 'finals') : ?><small> · <?php echo esc_html(sprintf(_n('%d unduhan', '%d unduhan', (int) ($downloads[$id] ?? 0), $t), (int) ($downloads[$id] ?? 0))); ?></small><?php endif; ?></span><button type="button" class="button-link lpdo-remove" aria-label="<?php esc_attr_e('Hapus dari order', $t); ?>">×</button></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="button lpdo-add-files"><?php esc_html_e('Tambah file', $t); ?></button>
      </div>
    <?php endforeach;
    if ($post->post_parent && lpdo_order_files($post->ID, 'finals')) : ?>
      <p><b><?php esc_html_e('Link download untuk dibagikan:', $t); ?></b></p>
      <ul class="lpdo-links"><?php foreach (lpdo_order_files($post->ID, 'finals') as $id) : $url = lpdo_portal_url($post->post_parent, 'file', (string) $id); ?>
        <li><?php echo lpdo_qr_svg($url, 64); // phpcs:ignore WordPress.Security.EscapeOutput ?><code><?php echo esc_html($url); ?></code></li>
      <?php endforeach; ?></ul>
    <?php endif;
}

function lpdo_box_order_brief(WP_Post $post): void
{
    $t = 'lightandpulse-design-orders';
    $type = lpdo_order_type($post->ID) ?: 'poster';
    $values = lpdo_order_fields($post->ID);
    echo '<div class="lpdo-admin-brief">';
    foreach (lpdo_order_groups() as $gk => $g) {
        if (!empty($g['types']) && !in_array($type, $g['types'], true)) {
            continue;
        }
        echo '<h4>' . esc_html($g['title']) . '</h4><table class="form-table" role="presentation"><tbody>';
        foreach ($g['fields'] as $f) {
            $v = $values[$f['key']] ?? '';
            echo '<tr><th scope="row">' . esc_html($f['label']) . '</th><td>';
            if ($f['type'] === 'file') {
                $v = array_filter(array_map('intval', (array) $v));
                foreach ($v as $att) {
                    echo '<a href="' . esc_url(wp_get_attachment_url((int) $att)) . '" target="_blank" rel="noopener">' . wp_get_attachment_image((int) $att, [80, 80], true) . '</a> ';
                }
                echo $v ? '' : '—';
            } else {
                echo lpdo_render_field($f, $v, 'lpdo_brief'); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    $revisions = lpdo_meta_array($post->ID, '_lpdo_revisions');
    if ($revisions) {
        echo '<h4>' . esc_html__('Permintaan revisi', $t) . '</h4><ol>';
        foreach ($revisions as $r) {
            echo '<li><b>' . esc_html($r['time']) . '</b>: ' . nl2br(esc_html($r['note']));
            foreach ((array) ($r['files'] ?? []) as $att) {
                echo ' <a href="' . esc_url(wp_get_attachment_url((int) $att)) . '" target="_blank" rel="noopener">' . esc_html__('lampiran', $t) . '</a>';
            }
            echo '</li>';
        }
        echo '</ol>';
    }
    echo '</div>';
}

function lpdo_box_order_log(WP_Post $post): void
{
    $log = array_reverse(lpdo_meta_array($post->ID, '_lpdo_log'));
    if (!$log) {
        echo '<p>—</p>';
        return;
    }
    echo '<ul class="lpdo-log">';
    foreach ($log as $l) {
        echo '<li><time>' . esc_html($l['time']) . '</time> <span class="lpdo-who">' . esc_html($l['who']) . '</span> ' . esc_html($l['text']) . '</li>';
    }
    echo '</ul>';
}

add_action('save_post_lpdo_order', function (int $postId) {
    if (!isset($_POST['_lpdo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_lpdo_nonce'])), 'lpdo_save_order') || !current_user_can('edit_post', $postId) || wp_is_post_autosave($postId)) {
        return;
    }
    $type = sanitize_key(wp_unslash($_POST['lpdo_type'] ?? ''));
    if (isset(lpdo_design_types()[$type])) {
        update_post_meta($postId, '_lpdo_type', $type);
    }
    update_post_meta($postId, '_lpdo_deadline', preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['lpdo_deadline'] ?? '')) ? sanitize_text_field(wp_unslash($_POST['lpdo_deadline'])) : '');

    // Brief edits made by the team.
    $values = lpdo_order_fields($postId);
    $in = (array) wp_unslash($_POST['lpdo_brief'] ?? []);
    foreach (lpdo_fields_for_type(lpdo_order_type($postId)) as $key => $f) {
        if ($f['type'] !== 'file') {
            $values[$key] = lpdo_sanitize_value($f, $in[$key] ?? ($f['type'] === 'checkboxes' ? [] : ''));
        }
    }
    update_post_meta($postId, '_lpdo_fields', $values);

    // Files: new proofs move the order to "proof", new finals to "done".
    $newStatus = sanitize_key(wp_unslash($_POST['lpdo_status'] ?? ''));
    foreach (['proofs' => 'proof', 'finals' => 'done'] as $kind => $autoStatus) {
        $before = lpdo_order_files($postId, $kind);
        $after = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST['lpdo_' . $kind] ?? '')))));
        $after = array_values(array_filter($after, fn($id) => get_post_type($id) === 'attachment'));
        update_post_meta($postId, '_lpdo_' . $kind, $after);
        if (array_diff($after, $before)) {
            lpdo_log($postId, sprintf(__('%d file ditambahkan (%s).', 'lightandpulse-design-orders'), count(array_diff($after, $before)), $kind === 'finals' ? 'final' : 'proof'), wp_get_current_user()->display_name);
            if ($newStatus === lpdo_order_status($postId)) {
                $newStatus = $autoStatus;
            }
        }
    }

    $parent = (int) ($_POST['lpdo_client_parent'] ?? 0);
    if ($parent !== (int) get_post_field('post_parent', $postId) && ($parent === 0 || get_post_type($parent) === 'lpdo_client')) {
        remove_all_actions('save_post_lpdo_order');
        wp_update_post(['ID' => $postId, 'post_parent' => $parent]);
    }

    $old = lpdo_order_status($postId);
    lpdo_set_status($postId, $newStatus, wp_get_current_user()->display_name);
    if ($old !== $newStatus && !empty($_POST['lpdo_notify']) && in_array($newStatus, ['proof', 'done'], true)) {
        lpdo_notify_client($postId, $newStatus);
    }
});

// ---------------------------------------------------------------------------
// Client screen
// ---------------------------------------------------------------------------
add_action('add_meta_boxes_lpdo_client', function () {
    $t = 'lightandpulse-design-orders';
    add_meta_box('lpdo_client_portal', __('Halaman klien', $t), 'lpdo_box_client_portal', 'lpdo_client', 'side', 'high');
    add_meta_box('lpdo_client_details', __('Data bisnis & branding', $t), 'lpdo_box_client_details', 'lpdo_client', 'normal', 'high');
    add_meta_box('lpdo_client_orders', __('Order', $t), 'lpdo_box_client_orders', 'lpdo_client', 'normal', 'default');
});

function lpdo_box_client_portal(WP_Post $post): void
{
    $t = 'lightandpulse-design-orders';
    if ($post->post_status === 'auto-draft') {
        echo '<p>' . esc_html__('Simpan klien untuk membuat link halaman.', $t) . '</p>';
        return;
    }
    $url = lpdo_portal_url($post->ID);
    echo lpdo_qr_svg($url, 180); // phpcs:ignore WordPress.Security.EscapeOutput
    ?>
    <p><input type="text" readonly class="widefat" value="<?php echo esc_attr($url); ?>" onclick="this.select()"></p>
    <p><a class="button button-primary" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Buka', $t); ?></a>
      <a class="button" href="<?php echo esc_url(lpdo_portal_url($post->ID, 'qr', 'page')); ?>"><?php esc_html_e('Unduh QR', $t); ?></a></p>
    <p><label><input type="checkbox" name="lpdo_new_key" value="1"> <?php esc_html_e('Buat link baru (link lama tidak berlaku lagi)', $t); ?></label></p>
    <p class="description"><?php esc_html_e('Logo klien = Featured image.', $t); ?></p>
    <?php
}

function lpdo_box_client_details(WP_Post $post): void
{
    wp_nonce_field('lpdo_save_client', '_lpdo_client_nonce');
    foreach (lpdo_client_fields() as $g) {
        echo '<h4>' . esc_html($g['title']) . '</h4><div class="lpdo-admin-grid">';
        foreach ($g['fields'] as $f) {
            if ($f['type'] === 'file') {
                continue;
            }
            echo lpdo_render_field($f, lpdo_client_meta($post->ID, $f['key']), 'lpdo_client'); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
        }
        echo '</div>';
    }
    if ($consent = get_post_meta($post->ID, '_lpdo_marketing_consent', true)) {
        echo '<p class="description">' . esc_html(sprintf(__('Setuju menerima promo sejak %s.', 'lightandpulse-design-orders'), $consent)) . '</p>';
    }
}

function lpdo_box_client_orders(WP_Post $post): void
{
    $orders = lpdo_client_orders($post->ID);
    if (!$orders) {
        echo '<p>—</p>';
        return;
    }
    echo '<ul>';
    foreach ($orders as $o) {
        echo '<li><a href="' . esc_url(get_edit_post_link($o->ID)) . '">' . esc_html(lpdo_order_ref($o->ID) . ' ' . get_the_title($o)) . '</a> · ' . esc_html(lpdo_statuses()[lpdo_order_status($o->ID)]) . '</li>';
    }
    echo '</ul>';
}

add_action('save_post_lpdo_client', function (int $postId) {
    if (!isset($_POST['_lpdo_client_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_lpdo_client_nonce'])), 'lpdo_save_client') || !current_user_can('edit_post', $postId) || wp_is_post_autosave($postId)) {
        return;
    }
    $in = (array) wp_unslash($_POST['lpdo_client'] ?? []);
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            if ($f['type'] !== 'file') {
                update_post_meta($postId, '_lpdo_' . $f['key'], lpdo_sanitize_value($f, $in[$f['key']] ?? ''));
            }
        }
    }
    lpdo_client_key($postId);
    if (!empty($_POST['lpdo_new_key'])) {
        update_post_meta($postId, '_lpdo_key', lpdo_new_key());
    }
    if (!get_post_meta($postId, '_lpdo_last_activity', true)) {
        update_post_meta($postId, '_lpdo_last_activity', time());
    }
});

// ---------------------------------------------------------------------------
// List columns
// ---------------------------------------------------------------------------
add_filter('manage_lpdo_order_posts_columns', function (array $c) {
    $t = 'lightandpulse-design-orders';
    return ['cb' => $c['cb'], 'title' => __('Order', $t), 'lpdo_client' => __('Klien', $t), 'lpdo_type' => __('Jenis', $t), 'lpdo_status' => __('Status', $t), 'lpdo_deadline' => __('Deadline', $t), 'date' => $c['date']];
});
add_action('manage_lpdo_order_posts_custom_column', function (string $col, int $id) {
    switch ($col) {
        case 'lpdo_client':
            $p = (int) get_post_field('post_parent', $id);
            echo $p ? '<a href="' . esc_url(get_edit_post_link($p)) . '">' . esc_html(get_the_title($p)) . '</a>' : '—';
            break;
        case 'lpdo_type':
            echo esc_html(lpdo_design_types()[lpdo_order_type($id)]['label'] ?? '—');
            break;
        case 'lpdo_status':
            $s = lpdo_order_status($id);
            echo '<span class="lpdo-pill lpdo-pill--' . esc_attr($s) . '">' . esc_html(lpdo_statuses()[$s]) . '</span>';
            break;
        case 'lpdo_deadline':
            $d = (string) get_post_meta($id, '_lpdo_deadline', true);
            echo $d ? '<span class="' . ($d < wp_date('Y-m-d') && !in_array(lpdo_order_status($id), ['done', 'approved'], true) ? 'lpdo-late' : '') . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($d))) . '</span>' : '—';
            break;
    }
}, 10, 2);
add_filter('manage_edit-lpdo_order_sortable_columns', fn($c) => $c + ['lpdo_deadline' => 'lpdo_deadline']);
add_action('pre_get_posts', function (WP_Query $q) {
    if (is_admin() && $q->is_main_query() && $q->get('orderby') === 'lpdo_deadline') {
        $q->set('meta_key', '_lpdo_deadline');
        $q->set('orderby', 'meta_value');
    }
});

add_filter('manage_lpdo_client_posts_columns', function (array $c) {
    $t = 'lightandpulse-design-orders';
    return ['cb' => $c['cb'], 'lpdo_logo' => '', 'title' => __('Klien', $t), 'lpdo_contact' => __('Kontak', $t), 'lpdo_orders' => __('Order', $t), 'lpdo_portal' => __('Halaman klien', $t)];
});
add_action('manage_lpdo_client_posts_custom_column', function (string $col, int $id) {
    $t = 'lightandpulse-design-orders';
    switch ($col) {
        case 'lpdo_logo':
            echo get_the_post_thumbnail($id, [40, 40]) ?: '<span class="lpdo-swatch-mini" style="background:' . esc_attr(lpdo_hex(lpdo_client_meta($id, 'color_primary'), '#ddd')) . '"></span>';
            break;
        case 'lpdo_contact':
            echo esc_html(trim(lpdo_client_meta($id, 'contact_name') . ' · ' . lpdo_client_meta($id, 'whatsapp'), ' ·'));
            break;
        case 'lpdo_orders':
            echo (int) count(lpdo_client_orders($id));
            break;
        case 'lpdo_portal':
            echo '<a href="' . esc_url(lpdo_portal_url($id)) . '" target="_blank" rel="noopener">' . esc_html__('Buka', $t) . '</a>';
            break;
    }
}, 10, 2);

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function lpdo_settings_page(): void
{
    $t = 'lightandpulse-design-orders';
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['_lpdo_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_lpdo_settings_nonce'])), 'lpdo_settings')) {
        lpdo_update_settings([
            'notify_email'     => sanitize_email(wp_unslash($_POST['notify_email'] ?? '')),
            'agency_name'      => sanitize_text_field(wp_unslash($_POST['agency_name'] ?? '')),
            'form_page'        => (int) ($_POST['form_page'] ?? 0),
            'max_upload_mb'    => max(1, min(50, (int) ($_POST['max_upload_mb'] ?? 10))),
            'retention_months' => max(0, min(120, (int) ($_POST['retention_months'] ?? 24))),
            'privacy_url'      => esc_url_raw(wp_unslash($_POST['privacy_url'] ?? '')),
        ]);
        echo '<div class="notice notice-success"><p>' . esc_html__('Pengaturan disimpan.', $t) . '</p></div>';
    }
    $s = lpdo_settings();
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Design Orders: pengaturan', $t); ?></h1>
      <form method="post">
        <?php wp_nonce_field('lpdo_settings', '_lpdo_settings_nonce'); ?>
        <table class="form-table" role="presentation"><tbody>
          <tr><th><label for="agency_name"><?php esc_html_e('Nama agency (pengendali data)', $t); ?></label></th><td><input id="agency_name" name="agency_name" class="regular-text" value="<?php echo esc_attr($s['agency_name']); ?>"></td></tr>
          <tr><th><label for="notify_email"><?php esc_html_e('Email notifikasi & kontak privasi', $t); ?></label></th><td><input id="notify_email" type="email" name="notify_email" class="regular-text" value="<?php echo esc_attr($s['notify_email']); ?>"></td></tr>
          <tr><th><label for="form_page"><?php esc_html_e('Halaman form order', $t); ?></label></th><td><?php wp_dropdown_pages(['name' => 'form_page', 'id' => 'form_page', 'selected' => (int) $s['form_page'], 'show_option_none' => '—']); ?><p class="description"><?php esc_html_e('Shortcode: [lightandpulse_design_order]', $t); ?></p></td></tr>
          <tr><th><label for="max_upload_mb"><?php esc_html_e('Ukuran file maks. (MB)', $t); ?></label></th><td><input id="max_upload_mb" type="number" min="1" max="50" name="max_upload_mb" value="<?php echo (int) $s['max_upload_mb']; ?>" class="small-text"><p class="description"><?php echo esc_html(sprintf(__('Batas server: %s.', $t), size_format(wp_max_upload_size()))); ?></p></td></tr>
          <tr><th><label for="retention_months"><?php esc_html_e('Retensi data (bulan)', $t); ?></label></th><td><input id="retention_months" type="number" min="0" max="120" name="retention_months" value="<?php echo (int) $s['retention_months']; ?>" class="small-text"><p class="description"><?php esc_html_e('Data pribadi klien dianonimkan otomatis setelah sekian bulan tanpa aktivitas. 0 = nonaktif. Angka ini juga tampil di pemberitahuan privasi form.', $t); ?></p></td></tr>
          <tr><th><label for="privacy_url"><?php esc_html_e('URL Kebijakan Privasi', $t); ?></label></th><td><input id="privacy_url" type="url" name="privacy_url" class="regular-text" value="<?php echo esc_attr($s['privacy_url']); ?>" placeholder="<?php echo esc_attr(get_privacy_policy_url()); ?>"><p class="description"><?php esc_html_e('Kosongkan untuk memakai halaman Kebijakan Privasi WordPress.', $t); ?></p></td></tr>
        </tbody></table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}
