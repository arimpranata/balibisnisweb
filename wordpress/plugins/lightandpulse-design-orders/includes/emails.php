<?php
/**
 * Email notifications. Plain text with links only: no personal data beyond
 * the business name and order title (UU PDP Pasal 36).
 */
defined('ABSPATH') || exit;

function lpdo_mail(string $to, string $subject, array $lines): void
{
    if (!is_email($to)) {
        return;
    }
    $lines[] = '';
    $lines[] = '— ' . lpdo_setting('agency_name');
    wp_mail($to, $subject, implode("\n", $lines));
}

add_action('lpdo_order_created', function (int $orderId, int $clientId) {
    $t = 'lightandpulse-design-orders';
    $ref = lpdo_order_ref($orderId);
    $name = get_the_title($clientId);
    lpdo_mail((string) lpdo_setting('notify_email'), sprintf(__('[Order desain] %1$s %2$s dari %3$s', $t), $ref, get_the_title($orderId), $name), [
        sprintf(__('Order baru %1$s dari %2$s.', $t), $ref, $name),
        '',
        __('Buka di wp-admin:', $t) . ' ' . admin_url('post.php?post=' . $orderId . '&action=edit'),
    ]);
    lpdo_mail(lpdo_client_meta($clientId, 'email'), sprintf(__('Order %1$s diterima – %2$s', $t), $ref, lpdo_setting('agency_name')), [
        sprintf(__('Halo %s,', $t), lpdo_client_meta($clientId, 'contact_name')),
        '',
        sprintf(__('Terima kasih, order "%1$s" (%2$s) sudah kami terima.', $t), get_the_title($orderId), $ref),
        __('Pantau status, setujui desain dan unduh file di halaman klien Anda:', $t),
        lpdo_portal_url($clientId),
        '',
        __('Simpan link ini dan jangan bagikan kepada pihak lain.', $t),
    ]);
}, 10, 2);

add_action('lpdo_client_feedback', function (int $orderId, string $result) {
    $t = 'lightandpulse-design-orders';
    $client = (int) get_post_field('post_parent', $orderId);
    $subject = $result === 'approved'
        ? sprintf(__('[Disetujui] %1$s %2$s', $t), lpdo_order_ref($orderId), get_the_title($orderId))
        : sprintf(__('[Revisi] %1$s %2$s', $t), lpdo_order_ref($orderId), get_the_title($orderId));
    lpdo_mail((string) lpdo_setting('notify_email'), $subject, [
        sprintf($result === 'approved' ? __('%s menyetujui desain.', $t) : __('%s meminta revisi.', $t), get_the_title($client)),
        admin_url('post.php?post=' . $orderId . '&action=edit'),
    ]);
}, 10, 2);

/** Tell the client when there is something to review or download (sent from the admin screen). */
function lpdo_notify_client(int $orderId, string $status): void
{
    $t = 'lightandpulse-design-orders';
    $clientId = (int) get_post_field('post_parent', $orderId);
    $messages = [
        'proof' => [__('Desain siap diperiksa: %s', $t), __('Desain untuk order "%1$s" (%2$s) siap Anda periksa. Setujui atau minta revisi di halaman klien:', $t)],
        'done'  => [__('File desain siap diunduh: %s', $t), __('File final untuk order "%1$s" (%2$s) siap diunduh di halaman klien:', $t)],
    ];
    if (!$clientId || !isset($messages[$status])) {
        return;
    }
    lpdo_mail(lpdo_client_meta($clientId, 'email'), sprintf($messages[$status][0], get_the_title($orderId)), [
        sprintf(__('Halo %s,', $t), lpdo_client_meta($clientId, 'contact_name')),
        '',
        sprintf($messages[$status][1], get_the_title($orderId), lpdo_order_ref($orderId)),
        lpdo_portal_url($clientId) . '#order-' . $orderId,
    ]);
    lpdo_log($orderId, __('Email notifikasi dikirim ke klien.', $t), 'system');
}
