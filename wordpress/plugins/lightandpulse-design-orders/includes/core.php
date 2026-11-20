<?php
/**
 * Content types, settings, portal URLs and shared helpers.
 */
defined('ABSPATH') || exit;

add_action('init', 'lpdo_register_types');
add_action('init', 'lpdo_add_rewrites');

function lpdo_register_types(): void
{
    $t = 'lightandpulse-design-orders';
    register_post_type('lpdo_client', [
        'labels' => ['name' => __('Klien', $t), 'singular_name' => __('Klien', $t), 'add_new_item' => __('Tambah klien', $t), 'edit_item' => __('Edit klien', $t), 'all_items' => __('Semua klien', $t), 'menu_name' => __('Klien', $t)],
        'public' => false, 'show_ui' => true, 'show_in_menu' => 'edit.php?post_type=lpdo_order', 'supports' => ['title', 'thumbnail'], 'capability_type' => 'post', 'map_meta_cap' => true,
    ]);
    register_post_type('lpdo_order', [
        'labels' => ['name' => __('Order desain', $t), 'singular_name' => __('Order', $t), 'add_new_item' => __('Tambah order', $t), 'edit_item' => __('Edit order', $t), 'all_items' => __('Semua order', $t), 'menu_name' => __('Design Orders', $t)],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-art', 'menu_position' => 5, 'supports' => ['title'], 'capability_type' => 'post', 'map_meta_cap' => true,
    ]);
}

/** /client/{slug}/{key}/ plus file, ZIP and QR endpoints under it. */
function lpdo_add_rewrites(): void
{
    add_rewrite_tag('%lpdo_key%', '([A-Za-z0-9]{24})');
    add_rewrite_tag('%lpdo_action%', '([a-z]+)');
    add_rewrite_tag('%lpdo_target%', '([a-z0-9-]+)');
    $base = '^client/([^/]+)/([A-Za-z0-9]{24})';
    add_rewrite_rule($base . '/?$', 'index.php?lpdo_key=$matches[2]', 'top');
    add_rewrite_rule($base . '/(file|zip|qr)/([a-z0-9-]+)/?$', 'index.php?lpdo_key=$matches[2]&lpdo_action=$matches[3]&lpdo_target=$matches[4]', 'top');
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
function lpdo_settings(): array
{
    return array_merge([
        'notify_email'     => get_option('admin_email'),
        'agency_name'      => get_bloginfo('name') ?: 'Lightandpulse',
        'form_page'        => 0,
        'max_upload_mb'    => 10,
        'retention_months' => 24,
        'privacy_url'      => '',
    ], (array) get_option('lpdo_settings', []));
}

function lpdo_setting(string $key)
{
    return lpdo_settings()[$key] ?? null;
}

function lpdo_update_settings(array $values): void
{
    update_option('lpdo_settings', array_merge((array) get_option('lpdo_settings', []), $values), false);
}

function lpdo_privacy_url(): string
{
    return (string) (lpdo_setting('privacy_url') ?: get_privacy_policy_url());
}

// ---------------------------------------------------------------------------
// Clients
// ---------------------------------------------------------------------------
function lpdo_new_key(): string
{
    // 24 random letters/digits: the secret that opens a client's portal.
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $key = '';
    for ($i = 0; $i < 24; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

function lpdo_client_by_key(string $key): ?WP_Post
{
    if (!preg_match('/^[A-Za-z0-9]{24}$/', $key)) {
        return null;
    }
    $found = get_posts(['post_type' => 'lpdo_client', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_lpdo_key', 'meta_value' => $key, 'suppress_filters' => true]);
    if (!$found || !hash_equals((string) get_post_meta($found[0]->ID, '_lpdo_key', true), $key)) {
        return null;
    }
    return $found[0];
}

function lpdo_client_key(int $clientId): string
{
    $key = (string) get_post_meta($clientId, '_lpdo_key', true);
    if (!preg_match('/^[A-Za-z0-9]{24}$/', $key)) {
        $key = lpdo_new_key();
        update_post_meta($clientId, '_lpdo_key', $key);
    }
    return $key;
}

function lpdo_client_meta(int $clientId, string $key): string
{
    return (string) get_post_meta($clientId, '_lpdo_' . $key, true);
}

/** Portal URL, optionally to a file/zip/qr endpoint. */
function lpdo_portal_url(int $clientId, string $action = '', string $target = ''): string
{
    $slug = get_post_field('post_name', $clientId) ?: 'client';
    $path = 'client/' . rawurlencode($slug) . '/' . lpdo_client_key($clientId) . '/';
    if ($action !== '') {
        $path .= $action . '/' . $target . '/';
    }
    return home_url(user_trailingslashit($path));
}

function lpdo_client_orders(int $clientId): array
{
    return get_posts(['post_type' => 'lpdo_order', 'post_status' => 'any', 'numberposts' => -1, 'post_parent' => $clientId, 'orderby' => 'date', 'order' => 'DESC']);
}

// ---------------------------------------------------------------------------
// Orders
// ---------------------------------------------------------------------------
/** A meta value that should be an array. (array) '' would give [''], not []. */
function lpdo_meta_array(int $postId, string $key): array
{
    $value = get_post_meta($postId, $key, true);
    return is_array($value) ? $value : [];
}

function lpdo_order_fields(int $orderId): array
{
    return lpdo_meta_array($orderId, '_lpdo_fields');
}

function lpdo_order_type(int $orderId): string
{
    return (string) get_post_meta($orderId, '_lpdo_type', true);
}

function lpdo_order_status(int $orderId): string
{
    $s = (string) get_post_meta($orderId, '_lpdo_status', true);
    return isset(lpdo_statuses()[$s]) ? $s : 'new';
}

/** Attachment IDs: 'proofs' (previews to approve) or 'finals' (files to download). */
function lpdo_order_files(int $orderId, string $kind): array
{
    return array_values(array_filter(array_map('intval', lpdo_meta_array($orderId, '_lpdo_' . $kind))));
}

/** Links the team shares: 'proofs' (designs to review) or 'finals' (files to download). Each: label, url. */
function lpdo_order_links(int $orderId, string $kind): array
{
    $out = [];
    foreach (lpdo_meta_array($orderId, '_lpdo_' . $kind . '_links') as $l) {
        $url = is_array($l) ? esc_url_raw((string) ($l['url'] ?? ''), ['http', 'https']) : '';
        if ($url !== '') {
            $out[] = ['label' => sanitize_text_field((string) ($l['label'] ?? '')), 'url' => $url];
        }
    }
    return $out;
}

/** Field definition for editing those links in wp-admin (a two-column table). */
function lpdo_links_field(string $kind): array
{
    $t = 'lightandpulse-design-orders';
    return ['key' => $kind, 'label' => $kind === 'finals' ? __('File final', $t) : __('Proof', $t), 'type' => 'repeater', 'rows' => 1,
        'add_label' => __('Tambah link', $t), 'columns' => [
            ['key' => 'label', 'label' => __('Nama (mis. Poster A4, PDF cetak)', $t), 'type' => 'text', 'size' => 2],
            ['key' => 'url', 'label' => __('Link / URL (Google Drive, Canva, Dropbox…)', $t), 'type' => 'text', 'size' => 4, 'inputmode' => 'url', 'placeholder' => 'https://'],
        ]];
}

/** Everything the client can open for an order: shared links plus files from older versions. */
function lpdo_order_items(int $orderId, string $kind): array
{
    $clientId = (int) get_post_field('post_parent', $orderId);
    $items = [];
    foreach (lpdo_order_links($orderId, $kind) as $i => $l) {
        $host = (string) wp_parse_url($l['url'], PHP_URL_HOST);
        $items[] = ['label' => $l['label'] ?: preg_replace('/^www\./', '', $host), 'url' => $l['url'], 'host' => preg_replace('/^www\./', '', $host), 'qr' => 'link-' . $orderId . '-' . $kind . '-' . $i];
    }
    foreach (lpdo_order_files($orderId, $kind) as $attId) {
        $url = lpdo_portal_url($clientId, 'file', (string) $attId);
        $items[] = ['label' => basename((string) get_attached_file($attId)), 'url' => $kind === 'proofs' ? add_query_arg('view', 1, $url) : $url, 'host' => lpdo_file_size($attId), 'qr' => 'file-' . $attId];
    }
    return $items;
}

function lpdo_log(int $orderId, string $text, string $who = 'system'): void
{
    $log = lpdo_meta_array($orderId, '_lpdo_log');
    $log[] = ['time' => current_time('mysql'), 'who' => $who, 'text' => $text];
    update_post_meta($orderId, '_lpdo_log', $log);
}

function lpdo_set_status(int $orderId, string $status, string $who = 'system'): void
{
    $old = lpdo_order_status($orderId);
    if (!isset(lpdo_statuses()[$status]) || $old === $status) {
        return;
    }
    update_post_meta($orderId, '_lpdo_status', $status);
    lpdo_log($orderId, sprintf(__('Status: %1$s → %2$s', 'lightandpulse-design-orders'), lpdo_statuses()[$old], lpdo_statuses()[$status]), $who);
    do_action('lpdo_status_changed', $orderId, $status, $old);
}

/** "#LP-0042" style reference shown to clients. */
function lpdo_order_ref(int $orderId): string
{
    return '#LP-' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
}

// ---------------------------------------------------------------------------
// Colour helpers for client branding
// ---------------------------------------------------------------------------
function lpdo_hex(string $value, string $fallback): string
{
    return sanitize_hex_color($value) ?: $fallback;
}

/** Black or white text, whichever reads better on a background colour. */
function lpdo_text_on(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    return (($r * 299 + $g * 587 + $b * 114) / 1000) >= 150 ? '#111111' : '#ffffff';
}

function lpdo_file_size(int $attId): string
{
    $file = get_attached_file($attId);
    return $file && is_readable($file) ? size_format(filesize($file), 1) : '';
}

/**
 * Is the logo mostly light (e.g. a white logo)? Then it needs a dark
 * background. Measured once from the visible pixels and cached.
 */
function lpdo_logo_is_light(int $attId): bool
{
    $cached = get_post_meta($attId, '_lpdo_logo_light', true);
    if ($cached !== '') {
        return $cached === '1';
    }
    $light = false;
    $path = get_attached_file($attId);
    $meta = wp_get_attachment_metadata($attId);
    if (!empty($meta['sizes']['thumbnail']['file']) && $path) {
        $path = dirname($path) . '/' . $meta['sizes']['thumbnail']['file'];
    }
    if ($path && is_readable($path) && function_exists('imagecreatefromstring') && str_starts_with((string) get_post_mime_type($attId), 'image/')) {
        $im = @imagecreatefromstring((string) file_get_contents($path));
        if ($im) {
            $w = imagesx($im);
            $h = imagesy($im);
            $sum = 0;
            $n = 0;
            $step = max(1, (int) floor(min($w, $h) / 40));
            for ($x = 0; $x < $w; $x += $step) {
                for ($y = 0; $y < $h; $y += $step) {
                    $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                    if ($c['alpha'] > 90) {
                        continue; // transparent
                    }
                    $sum += 0.2126 * $c['red'] + 0.7152 * $c['green'] + 0.0722 * $c['blue'];
                    $n++;
                }
            }
            imagedestroy($im);
            $light = $n > 0 && $sum / $n > 200;
        }
    }
    update_post_meta($attId, '_lpdo_logo_light', $light ? '1' : '0');
    return $light;
}
