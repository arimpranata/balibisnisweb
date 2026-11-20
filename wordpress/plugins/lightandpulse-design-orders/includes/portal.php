<?php
/**
 * Client portal: /client/{slug}/{key}/ with the client's branding, orders,
 * proofs to approve, and final files with direct download links and QR codes.
 *
 * The 24-character key in the URL is the only way in (no account needed), so
 * the page is never indexed or cached, and files are only served when they
 * belong to that client.
 */
defined('ABSPATH') || exit;

add_action('template_redirect', function () {
    $key = (string) get_query_var('lpdo_key');
    if ($key === '') {
        return;
    }
    $client = lpdo_client_by_key($key);
    if (!$client) {
        status_header(404);
        nocache_headers();
        wp_die(esc_html__('Halaman klien tidak ditemukan. Periksa kembali link Anda atau hubungi kami.', 'lightandpulse-design-orders'), '', ['response' => 404]);
    }
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Referrer-Policy: no-referrer', true);

    $action = (string) get_query_var('lpdo_action');
    $target = (string) get_query_var('lpdo_target');
    if ($action === 'file') {
        lpdo_send_file($client, (int) $target);
    } elseif ($action === 'zip') {
        lpdo_send_zip($client, (int) $target);
    } elseif ($action === 'qr') {
        lpdo_send_qr($client, $target);
    }

    // Keep the address canonical if the client was renamed.
    $requested = trim((string) wp_parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    if (!str_contains($requested, '/' . $client->post_name . '/')) {
        wp_safe_redirect(add_query_arg(array_intersect_key($_GET, ['lpdo' => 1]), lpdo_portal_url($client->ID)), 301);
        exit;
    }

    $orders = lpdo_client_orders($client->ID);
    include LPDO_DIR . '/templates/portal.php';
    exit;
}, 1);

/** Is this attachment a proof or final file of one of the client's orders? Returns the order ID. */
function lpdo_file_order(WP_Post $client, int $attId): int
{
    foreach (lpdo_client_orders($client->ID) as $order) {
        if (in_array($attId, lpdo_order_files($order->ID, 'finals'), true) || in_array($attId, lpdo_order_files($order->ID, 'proofs'), true)) {
            return $order->ID;
        }
    }
    return 0;
}

function lpdo_send_file(WP_Post $client, int $attId): void
{
    $orderId = lpdo_file_order($client, $attId);
    $path = $orderId ? get_attached_file($attId) : '';
    if (!$path || !is_readable($path)) {
        status_header(404);
        wp_die(esc_html__('File tidak ditemukan.', 'lightandpulse-design-orders'), '', ['response' => 404]);
    }
    $type = get_post_mime_type($attId) ?: 'application/octet-stream';
    // Images and PDFs open in the browser when asked (previews); everything else downloads.
    $inline = isset($_GET['view']) && preg_match('#^(image/|application/pdf)#', $type);
    if (!$inline) {
        $counts = lpdo_meta_array($orderId, '_lpdo_downloads');
        $counts[$attId] = (int) ($counts[$attId] ?? 0) + 1;
        update_post_meta($orderId, '_lpdo_downloads', $counts);
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . sanitize_file_name(basename($path)) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function lpdo_send_zip(WP_Post $client, int $orderId): void
{
    $order = get_post($orderId);
    $files = $order && (int) $order->post_parent === $client->ID ? lpdo_order_files($orderId, 'finals') : [];
    if (!$files || !class_exists('ZipArchive')) {
        status_header(404);
        wp_die(esc_html__('File tidak tersedia.', 'lightandpulse-design-orders'), '', ['response' => 404]);
    }
    require_once ABSPATH . 'wp-admin/includes/file.php'; // wp_tempnam() is not loaded on the front end
    $tmp = wp_tempnam('lpdo-zip');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        wp_die(esc_html__('ZIP tidak dapat dibuat. Unduh file satu per satu.', 'lightandpulse-design-orders'));
    }
    foreach ($files as $attId) {
        $path = get_attached_file($attId);
        if ($path && is_readable($path)) {
            $zip->addFile($path, basename($path));
        }
    }
    $zip->close();
    $name = sanitize_file_name(get_the_title($client) . '-' . lpdo_order_ref($orderId)) . '.zip';
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($tmp));
    header('Content-Disposition: attachment; filename="' . $name . '"');
    readfile($tmp);
    @unlink($tmp);
    exit;
}

/** QR targets: "page" (this portal), "file-{id}" (a direct download), "zip-{order}". */
function lpdo_qr_target_url(WP_Post $client, string $target): string
{
    if ($target === 'page') {
        return lpdo_portal_url($client->ID);
    }
    if (preg_match('/^file-(\d+)$/', $target, $m) && lpdo_file_order($client, (int) $m[1])) {
        return lpdo_portal_url($client->ID, 'file', $m[1]);
    }
    if (preg_match('/^zip-(\d+)$/', $target, $m) && (int) get_post_field('post_parent', (int) $m[1]) === $client->ID) {
        return lpdo_portal_url($client->ID, 'zip', $m[1]);
    }
    return '';
}

function lpdo_send_qr(WP_Post $client, string $target): void
{
    $url = lpdo_qr_target_url($client, $target);
    if ($url === '') {
        status_header(404);
        wp_die(esc_html__('QR tidak ditemukan.', 'lightandpulse-design-orders'), '', ['response' => 404]);
    }
    lpdo_qr_send($url, 'qr-' . $client->post_name . '-' . $target, ($_GET['format'] ?? '') === 'svg' ? 'svg' : 'png');
}

// ---------------------------------------------------------------------------
// Client actions from the portal: approve a proof or ask for a revision
// ---------------------------------------------------------------------------
add_action('admin_post_nopriv_lpdo_portal', 'lpdo_handle_portal_action');
add_action('admin_post_lpdo_portal', 'lpdo_handle_portal_action');

function lpdo_handle_portal_action(): void
{
    $t = 'lightandpulse-design-orders';
    $client = lpdo_client_by_key(sanitize_text_field(wp_unslash($_POST['lpdo_client_key'] ?? '')));
    if ($client && sanitize_key(wp_unslash($_POST['lpdo_decision'] ?? '')) === 'profile') {
        lpdo_save_profile($client);
    }
    $orderId = (int) ($_POST['lpdo_order'] ?? 0);
    if (!$client || (int) get_post_field('post_parent', $orderId) !== $client->ID || get_post_type($orderId) !== 'lpdo_order') {
        wp_die(esc_html__('Link halaman klien tidak valid.', $t), 403);
    }
    $decision = sanitize_key(wp_unslash($_POST['lpdo_decision'] ?? ''));
    $result = '';
    if ($decision === 'approve' && in_array(lpdo_order_status($orderId), ['proof', 'revision'], true)) {
        lpdo_set_status($orderId, 'approved', 'client');
        lpdo_log($orderId, __('Klien menyetujui desain.', $t), 'client');
        $result = 'approved';
    } elseif ($decision === 'revision') {
        $note = sanitize_textarea_field(wp_unslash($_POST['lpdo_note'] ?? ''));
        if ($note === '') {
            wp_safe_redirect(add_query_arg('lpdo', 'note-missing', lpdo_portal_url($client->ID)) . '#order-' . $orderId);
            exit;
        }
        $errors = [];
        $files = lpdo_handle_uploads('revision_files', $orderId, $errors);
        $revisions = lpdo_meta_array($orderId, '_lpdo_revisions');
        $revisions[] = ['time' => current_time('mysql'), 'note' => $note, 'files' => $files];
        update_post_meta($orderId, '_lpdo_revisions', $revisions);
        lpdo_log($orderId, __('Klien meminta revisi:', $t) . ' ' . $note, 'client');
        lpdo_set_status($orderId, 'revision', 'client');
        $result = 'revision';
    }
    update_post_meta($client->ID, '_lpdo_last_activity', time());
    if ($result) {
        do_action('lpdo_client_feedback', $orderId, $result);
    }
    wp_safe_redirect(add_query_arg('lpdo', $result ?: 'nothing', lpdo_portal_url($client->ID)) . '#order-' . $orderId);
    exit;
}

/** Client edits business & branding from the portal's Brand kit panel. */
function lpdo_save_profile(WP_Post $client): void
{
    $in = (array) wp_unslash($_POST['lpdo_client'] ?? []);
    $vals = [];
    $ok = true;
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            if ($f['type'] === 'file') {
                continue;
            }
            $vals[$f['key']] = lpdo_sanitize_value($f, $in[$f['key']] ?? '');
            if (!empty($f['required']) && $vals[$f['key']] === '') {
                $ok = false;
            }
        }
    }
    if (!$ok || !is_email($vals['email'])) {
        wp_safe_redirect(add_query_arg('lpdo', 'profile-error', lpdo_portal_url($client->ID)));
        exit;
    }
    foreach ($vals as $k => $v) {
        update_post_meta($client->ID, '_lpdo_' . $k, $v);
    }
    $errors = [];
    $logo = lpdo_handle_uploads('logo', $client->ID, $errors);
    if ($logo) {
        set_post_thumbnail($client->ID, $logo[0]);
    }
    if ($vals['business_name'] !== get_the_title($client)) {
        wp_update_post(['ID' => $client->ID, 'post_title' => $vals['business_name']]);
    }
    update_post_meta($client->ID, '_lpdo_last_activity', time());
    wp_safe_redirect(add_query_arg('lpdo', 'profile', lpdo_portal_url($client->ID)));
    exit;
}

/** Keep search engines away from portal pages even if a link leaks. */
add_filter('wp_robots', function (array $robots) {
    if (get_query_var('lpdo_key')) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
});
