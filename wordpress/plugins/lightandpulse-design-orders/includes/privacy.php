<?php
/**
 * UU PDP support: WordPress Export/Erase Personal Data tools (Tools → Export
 * / Erase Personal Data) and automatic retention clean-up.
 */
defined('ABSPATH') || exit;

function lpdo_clients_by_email(string $email): array
{
    return get_posts(['post_type' => 'lpdo_client', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_lpdo_email', 'meta_value' => $email]);
}

add_filter('wp_privacy_personal_data_exporters', function (array $exporters) {
    $exporters['lightandpulse-design-orders'] = [
        'exporter_friendly_name' => __('Lightandpulse Design Orders', 'lightandpulse-design-orders'),
        'callback' => function (string $email) {
            $data = [];
            foreach (lpdo_clients_by_email($email) as $client) {
                $items = [];
                foreach (lpdo_client_fields() as $g) {
                    foreach ($g['fields'] as $f) {
                        $v = $f['type'] === 'file' ? '' : lpdo_client_meta($client->ID, $f['key']);
                        if ($v !== '') {
                            $items[] = ['name' => $f['label'], 'value' => $v];
                        }
                    }
                }
                $items[] = ['name' => __('Halaman klien', 'lightandpulse-design-orders'), 'value' => lpdo_portal_url($client->ID)];
                $data[] = ['group_id' => 'lpdo-client', 'group_label' => __('Klien desain', 'lightandpulse-design-orders'), 'item_id' => 'lpdo-client-' . $client->ID, 'data' => $items];
                foreach (lpdo_client_orders($client->ID) as $order) {
                    $rows = [['name' => __('Order', 'lightandpulse-design-orders'), 'value' => lpdo_order_ref($order->ID) . ' ' . get_the_title($order)]];
                    foreach (lpdo_fields_for_type(lpdo_order_type($order->ID)) as $key => $f) {
                        $v = lpdo_order_fields($order->ID)[$key] ?? '';
                        $v = $f['type'] === 'file' ? '' : lpdo_display_value($f, $v);
                        if ($v !== '') {
                            $rows[] = ['name' => $f['label'], 'value' => $v];
                        }
                    }
                    $consent = lpdo_meta_array($order->ID, '_lpdo_consent');
                    if ($consent) {
                        $rows[] = ['name' => __('Catatan persetujuan', 'lightandpulse-design-orders'), 'value' => wp_json_encode($consent)];
                    }
                    $data[] = ['group_id' => 'lpdo-orders', 'group_label' => __('Order desain', 'lightandpulse-design-orders'), 'item_id' => 'lpdo-order-' . $order->ID, 'data' => $rows];
                }
            }
            return ['data' => $data, 'done' => true];
        },
    ];
    return $exporters;
});

add_filter('wp_privacy_personal_data_erasers', function (array $erasers) {
    $erasers['lightandpulse-design-orders'] = [
        'eraser_friendly_name' => __('Lightandpulse Design Orders', 'lightandpulse-design-orders'),
        'callback' => function (string $email) {
            $removed = false;
            foreach (lpdo_clients_by_email($email) as $client) {
                lpdo_anonymize_client($client->ID);
                $removed = true;
            }
            return ['items_removed' => $removed, 'items_retained' => false, 'messages' => [], 'done' => true];
        },
    ];
    return $erasers;
});

/**
 * Remove a client's personal data: contact details, files the client uploaded
 * (logo, references, photos), the brief text and the portal link. The order
 * records stay as anonymous history; design files uploaded by the team
 * (proofs and finals) are kept. Delete the orders as well if those must go too.
 */
function lpdo_anonymize_client(int $clientId): void
{
    foreach (lpdo_client_fields() as $g) {
        foreach ($g['fields'] as $f) {
            delete_post_meta($clientId, '_lpdo_' . $f['key']);
        }
    }
    delete_post_meta($clientId, '_lpdo_marketing_consent');
    update_post_meta($clientId, '_lpdo_key', lpdo_new_key()); // old portal link stops working
    $logo = (int) get_post_thumbnail_id($clientId);
    if ($logo) {
        wp_delete_attachment($logo, true);
    }
    foreach (lpdo_client_orders($clientId) as $order) {
        foreach (get_children(['post_parent' => $order->ID, 'post_type' => 'attachment']) as $att) {
            if (get_post_meta($att->ID, '_lpdo_upload', true)) {
                wp_delete_attachment($att->ID, true); // client uploads only
            }
        }
        update_post_meta($order->ID, '_lpdo_fields', ['title' => lpdo_order_fields($order->ID)['title'] ?? '']);
        delete_post_meta($order->ID, '_lpdo_revisions');
        lpdo_log($order->ID, __('Data pribadi klien dihapus / dianonimkan.', 'lightandpulse-design-orders'));
    }
    wp_update_post(['ID' => $clientId, 'post_title' => sprintf(__('Klien dianonimkan #%d', 'lightandpulse-design-orders'), $clientId), 'post_name' => 'client-' . $clientId]);
    update_post_meta($clientId, '_lpdo_anonymized', current_time('mysql'));
}

/** Daily: anonymise clients with no activity for the configured number of months. */
add_action('lpdo_daily', function () {
    $months = (int) lpdo_setting('retention_months');
    if ($months <= 0) {
        return;
    }
    $cutoff = strtotime("-$months months");
    $old = get_posts([
        'post_type' => 'lpdo_client', 'post_status' => 'any', 'numberposts' => 50, 'fields' => 'ids',
        'meta_query' => [
            ['key' => '_lpdo_last_activity', 'value' => $cutoff, 'compare' => '<', 'type' => 'NUMERIC'],
            ['key' => '_lpdo_anonymized', 'compare' => 'NOT EXISTS'],
        ],
    ]);
    foreach ($old as $id) {
        lpdo_anonymize_client((int) $id);
    }
});

/** Suggested text for the site's Privacy Policy (Settings → Privacy → Policy guide). */
add_action('admin_init', function () {
    if (!function_exists('wp_add_privacy_policy_content')) {
        return;
    }
    $months = (int) lpdo_setting('retention_months');
    wp_add_privacy_policy_content(__('Lightandpulse Design Orders', 'lightandpulse-design-orders'), wp_kses_post(
        '<p>' . __('Saat Anda memesan desain, kami memproses nama bisnis, nama kontak, nomor WhatsApp, email, Instagram/website, alamat (opsional), logo, file referensi dan isi brief untuk melaksanakan pesanan Anda (UU 27/2022 Pasal 20 ayat 2 huruf b). Info promo hanya dikirim dengan persetujuan terpisah.', 'lightandpulse-design-orders') . '</p>'
        . '<p>' . ($months > 0 ? sprintf(__('Data disimpan selama hubungan kerja dan dihapus atau dianonimkan %d bulan setelah aktivitas terakhir.', 'lightandpulse-design-orders'), $months) : __('Data disimpan selama hubungan kerja dan dihapus atas permintaan.', 'lightandpulse-design-orders')) . '</p>'
        . '<p>' . __('Setiap klien memiliki halaman pribadi yang hanya bisa dibuka dengan link rahasia. Anda dapat meminta akses, perbaikan atau penghapusan data kapan saja; kami menanggapi paling lambat 3 × 24 jam.', 'lightandpulse-design-orders') . '</p>'
    ));
});
