<?php
/**
 * Client portal page. Standalone (no theme header/footer) so it carries the
 * client's own branding. No third-party fonts or scripts are loaded.
 *
 * @var WP_Post   $client
 * @var WP_Post[] $orders
 */
defined('ABSPATH') || exit;

$t = 'lightandpulse-design-orders';
$cid = $client->ID;
$primary = lpdo_hex(lpdo_client_meta($cid, 'color_primary'), '#1f2937');
$secondary = lpdo_hex(lpdo_client_meta($cid, 'color_secondary'), '#f5f5f4');
$accent = lpdo_hex(lpdo_client_meta($cid, 'color_accent'), '#ff6a3d');
$logoId = (int) get_post_thumbnail_id($cid);
$logoUrl = $logoId ? wp_get_attachment_image_url($logoId, 'medium') : '';
$name = get_the_title($client);
$logoLight = $logoId && lpdo_logo_is_light($logoId);
$tagline = lpdo_client_meta($cid, 'tagline');
$font = lpdo_client_meta($cid, 'brand_font');
$agency = lpdo_setting('agency_name');
$statuses = lpdo_statuses();
$types = lpdo_design_types();
$steps = ['new', 'review', 'design', 'proof', 'approved', 'done'];
$notice = sanitize_key(wp_unslash($_GET['lpdo'] ?? ''));
$notices = [
    'sent'         => __('Terima kasih! Order Anda sudah kami terima. Kami akan menghubungi Anda lewat WhatsApp atau email. Simpan link halaman ini untuk memantau status dan mengunduh file.', $t),
    'approved'     => __('Terima kasih, desain sudah disetujui. File final akan muncul di halaman ini.', $t),
    'revision'     => __('Permintaan revisi terkirim. Kami akan mengabari Anda saat versi baru siap.', $t),
    'note-missing' => __('Tuliskan catatan revisi sebelum mengirim.', $t),
    'profile'      => __('Data bisnis & branding tersimpan.', $t),
    'profile-error' => __('Data bisnis belum tersimpan: nama bisnis, nama kontak, WhatsApp dan email wajib diisi dengan benar.', $t),
];
$portalUrl = lpdo_portal_url($cid);
$counts = ['waiting' => 0, 'active' => 0, 'done' => 0];
foreach ($orders as $o) {
    $st = lpdo_order_status($o->ID);
    $counts[$st === 'proof' ? 'waiting' : ($st === 'done' ? 'done' : 'active')]++;
}
$initials = strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N} ]/u', '', $name), 0, 2));
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_html(sprintf(__('%s · Portal desain', $t), $name)); ?></title>
<link rel="stylesheet" href="<?php echo esc_url(LPDO_URL . 'assets/form.css?ver=' . LPDO_VERSION); ?>">
<link rel="stylesheet" href="<?php echo esc_url(LPDO_URL . 'assets/portal.css?ver=' . LPDO_VERSION); ?>">
<style>:root{--c-primary:<?php echo esc_html($primary); ?>;--c-on-primary:<?php echo esc_html(lpdo_text_on($primary)); ?>;--c-secondary:<?php echo esc_html($secondary); ?>;--c-on-secondary:<?php echo esc_html(lpdo_text_on($secondary)); ?>;--c-accent:<?php echo esc_html($accent); ?>;--c-on-accent:<?php echo esc_html(lpdo_text_on($accent)); ?>}</style>
</head>
<body class="lpdo-portal">
<header class="lpdo-brandbar">
  <div class="lpdo-wrap lpdo-brandbar__inner">
    <div class="lpdo-brand">
      <?php if ($logoUrl) : ?><img class="lpdo-brand__logo<?php echo $logoLight ? ' is-light' : ''; ?>" src="<?php echo esc_url($logoUrl); ?>" alt="<?php echo esc_attr($name); ?>"><?php else : ?><span class="lpdo-brand__initials" aria-hidden="true"><?php echo esc_html($initials); ?></span><?php endif; ?>
      <div><p class="lpdo-brand__name"><?php echo esc_html($name); ?></p><?php if ($tagline) : ?><p class="lpdo-brand__tagline"><?php echo esc_html($tagline); ?></p><?php endif; ?></div>
    </div>
    <a class="lpdo-btn lpdo-btn--light" href="#new-order" data-open-new-order><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> <?php esc_html_e('Order desain baru', $t); ?></a>
  </div>
</header>

<main class="lpdo-wrap lpdo-main">
  <?php if (isset($notices[$notice])) : ?><div class="lpdo-notice <?php echo in_array($notice, ['note-missing', 'profile-error'], true) ? 'is-error' : ''; ?>" role="status"><p><?php echo esc_html($notices[$notice]); ?></p></div><?php endif; ?>

  <div class="lpdo-layout">
    <section class="lpdo-orders" aria-labelledby="lpdo-orders-title">
      <div class="lpdo-orders__head">
        <div>
          <h1 id="lpdo-orders-title"><?php esc_html_e('Order desain', $t); ?></h1>
          <?php if ($orders) : ?>
            <p class="lpdo-stats">
              <span><?php echo esc_html(sprintf(_n('%d order', '%d order', count($orders), $t), count($orders))); ?></span>
              <?php if ($counts['waiting']) : ?><span class="is-warn"><?php echo esc_html(sprintf(__('%d menunggu persetujuan Anda', $t), $counts['waiting'])); ?></span><?php endif; ?>
              <?php if ($counts['active']) : ?><span><?php echo esc_html(sprintf(__('%d sedang dikerjakan', $t), $counts['active'])); ?></span><?php endif; ?>
              <?php if ($counts['done']) : ?><span class="is-ok"><?php echo esc_html(sprintf(__('%d selesai', $t), $counts['done'])); ?></span><?php endif; ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!$orders) : ?>
        <div class="lpdo-empty">
          <span class="lpdo-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.5 5.5L20 9.3l-4 4 1 5.7-5-2.8-5 2.8 1-5.7-4-4 5.5-.8z"/></svg></span>
          <h2><?php esc_html_e('Belum ada order', $t); ?></h2>
          <p><?php esc_html_e('Mulai order desain pertama Anda. Data bisnis dan brand kit Anda sudah tersimpan, jadi tidak perlu diisi lagi.', $t); ?></p>
          <a class="lpdo-btn lpdo-btn--primary" href="#new-order" data-open-new-order><?php esc_html_e('Buat order pertama', $t); ?></a>
        </div>
      <?php endif; ?>

      <?php foreach ($orders as $order) :
          $oid = $order->ID;
          $type = lpdo_order_type($oid);
          $status = lpdo_order_status($oid);
          $fields = lpdo_order_fields($oid);
          $finals = lpdo_order_items($oid, 'finals');
          $proofs = lpdo_order_items($oid, 'proofs');
          $legacyFinals = lpdo_order_files($oid, 'finals');
          $deadline = (string) get_post_meta($oid, '_lpdo_deadline', true);
          $stepIndex = array_search($status === 'revision' ? 'proof' : $status, $steps, true);
          ?>
        <article class="lpdo-order" id="order-<?php echo (int) $oid; ?>">
          <header class="lpdo-order__head">
            <span class="lpdo-order__icon"><?php echo lpdo_type_icon($type); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></span>
            <div class="lpdo-order__title">
              <p class="lpdo-order__meta"><?php echo esc_html(($types[$type]['label'] ?? '') . ' · ' . lpdo_order_ref($oid) . ' · ' . get_the_date('', $order)); ?></p>
              <h2><?php echo esc_html($fields['title'] ?? get_the_title($order)); ?></h2>
              <?php if ($deadline) : ?><p class="lpdo-order__meta"><?php echo esc_html(sprintf(__('Deadline: %s', $t), date_i18n(get_option('date_format'), strtotime($deadline)))); ?></p><?php endif; ?>
            </div>
            <div class="lpdo-order__side">
              <span class="lpdo-status lpdo-status--<?php echo esc_attr($status); ?>"><?php echo esc_html($statuses[$status]); ?></span>
              <a class="lpdo-dup" href="<?php echo esc_url(add_query_arg('lpdo_copy', $oid, $portalUrl) . '#new-order'); ?>" title="<?php esc_attr_e('Buat order baru dari brief ini', $t); ?>"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg> <?php esc_html_e('Duplikat', $t); ?></a>
            </div>
          </header>

          <ol class="lpdo-progress" aria-label="<?php esc_attr_e('Tahap order', $t); ?>">
            <?php foreach ($steps as $i => $s) : ?>
              <li class="<?php echo $i < $stepIndex ? 'is-done' : ($i === $stepIndex ? 'is-current' : ''); ?>"<?php echo $i === $stepIndex ? ' aria-current="step"' : ''; ?>><span><?php echo esc_html($s === 'proof' && $status === 'revision' ? $statuses['revision'] : $statuses[$s]); ?></span></li>
            <?php endforeach; ?>
          </ol>

          <?php if ($finals) : ?>
            <div class="lpdo-block">
              <div class="lpdo-block__head"><h3><?php esc_html_e('File siap diunduh', $t); ?></h3><div class="lpdo-view" role="group" aria-label="<?php esc_attr_e('Tampilan', $t); ?>"><button type="button" data-view="list" aria-label="<?php esc_attr_e('List', $t); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></button><button type="button" data-view="grid" aria-label="<?php esc_attr_e('Grid', $t); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></button></div></div>
              <ul class="lpdo-links lpdo-links--finals" data-views>
                <?php foreach ($finals as $item) : ?>
                  <li class="lpdo-link">
                    <span class="lpdo-link__icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg></span>
                    <span class="lpdo-link__text"><strong><?php echo esc_html($item['label']); ?></strong><small><?php echo esc_html($item['host']); ?></small></span>
                    <span class="lpdo-link__actions">
                      <a class="lpdo-btn lpdo-btn--primary" href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Buka / unduh', $t); ?></a>
                      <button type="button" class="lpdo-btn lpdo-btn--ghost" data-copy="<?php echo esc_attr($item['url']); ?>"><?php esc_html_e('Salin link', $t); ?></button>
                      <a class="lpdo-btn lpdo-btn--ghost" href="<?php echo esc_url(lpdo_portal_url($cid, 'qr', $item['qr'])); ?>"><?php esc_html_e('Unduh QR', $t); ?></a>
                    </span>
                    <span class="lpdo-link__qr" title="<?php esc_attr_e('Pindai untuk membuka link ini', $t); ?>"><?php echo lpdo_qr_svg($item['url'], 84, '#111111', '#ffffff', __('QR link file', $t)); // phpcs:ignore WordPress.Security.EscapeOutput -- generated SVG. ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($legacyFinals) > 1 && class_exists('ZipArchive')) : $zip = lpdo_portal_url($cid, 'zip', (string) $oid); ?>
                <p class="lpdo-zip-line"><a class="lpdo-btn lpdo-btn--ghost" href="<?php echo esc_url($zip); ?>"><?php esc_html_e('Unduh semua file unggahan (ZIP)', $t); ?></a></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($proofs && in_array($status, ['proof', 'revision'], true)) : ?>
            <div class="lpdo-block">
              <div class="lpdo-block__head"><h3><?php esc_html_e('Desain untuk Anda periksa', $t); ?></h3><div class="lpdo-view" role="group" aria-label="<?php esc_attr_e('Tampilan', $t); ?>"><button type="button" data-view="list" aria-label="<?php esc_attr_e('List', $t); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></button><button type="button" data-view="grid" aria-label="<?php esc_attr_e('Grid', $t); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></button></div></div>
              <ul class="lpdo-links lpdo-links--proofs" data-views>
                <?php foreach ($proofs as $item) : ?>
                  <li><a class="lpdo-link lpdo-link--btn" href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer">
                    <span class="lpdo-link__icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg></span>
                    <span class="lpdo-link__text"><strong><?php echo esc_html($item['label']); ?></strong><small><?php echo esc_html($item['host']); ?></small></span>
                    <span class="lpdo-link__go" aria-hidden="true">→</span>
                  </a></li>
                <?php endforeach; ?>
              </ul>
              <?php if ($status === 'proof') : ?>
                <div class="lpdo-decide">
                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="lpdo_portal"><input type="hidden" name="lpdo_client_key" value="<?php echo esc_attr(lpdo_client_key($cid)); ?>">
                    <input type="hidden" name="lpdo_order" value="<?php echo (int) $oid; ?>"><input type="hidden" name="lpdo_decision" value="approve">
                    <button class="lpdo-btn lpdo-btn--primary"><?php esc_html_e('Setujui desain', $t); ?></button>
                  </form>
                  <details class="lpdo-revise">
                    <summary class="lpdo-btn lpdo-btn--ghost"><?php esc_html_e('Minta revisi', $t); ?></summary>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                      <input type="hidden" name="action" value="lpdo_portal"><input type="hidden" name="lpdo_client_key" value="<?php echo esc_attr(lpdo_client_key($cid)); ?>">
                      <input type="hidden" name="lpdo_order" value="<?php echo (int) $oid; ?>"><input type="hidden" name="lpdo_decision" value="revision">
                      <label for="lpdo-note-<?php echo (int) $oid; ?>"><?php esc_html_e('Apa yang perlu diubah?', $t); ?></label>
                      <textarea id="lpdo-note-<?php echo (int) $oid; ?>" name="lpdo_note" rows="4" required></textarea>
                      <label for="lpdo-rev-<?php echo (int) $oid; ?>"><?php esc_html_e('Lampiran (opsional)', $t); ?></label>
                      <input type="file" id="lpdo-rev-<?php echo (int) $oid; ?>" name="lpdo_files[revision_files][]" multiple accept="image/*,application/pdf">
                      <button class="lpdo-btn lpdo-btn--primary"><?php esc_html_e('Kirim permintaan revisi', $t); ?></button>
                    </form>
                  </details>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <details class="lpdo-brief">
            <summary><?php esc_html_e('Lihat brief order', $t); ?></summary>
            <?php foreach (lpdo_order_groups() as $group) :
                if (!empty($group['types']) && !in_array($type, $group['types'], true)) { continue; }
                $rows = '';
                foreach ($group['fields'] as $f) {
                    if (!lpdo_field_applies($f, $type)) { continue; }
                    $v = $fields[$f['key']] ?? '';
                    if ($f['type'] === 'repeater') {
                        if ($v) { $rows .= '<dt class="lpdo-dl__wide">' . esc_html($f['label']) . '</dt><dd class="lpdo-dl__wide">' . lpdo_repeater_table($f, $v) . '</dd>'; }
                        continue;
                    }
                    if ($f['type'] === 'file') {
                        $v = $v ? sprintf(_n('%d file', '%d file', count((array) $v), $t), count((array) $v)) : '';
                    } else {
                        $v = lpdo_display_value($f, $v);
                    }
                    if ($v !== '') { $rows .= '<dt>' . esc_html($f['label']) . '</dt><dd>' . nl2br(esc_html($v)) . '</dd>'; }
                }
                if ($rows === '') { continue; } ?>
              <h4><?php echo esc_html($group['title']); ?></h4><dl class="lpdo-dl"><?php echo $rows; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></dl>
            <?php endforeach; ?>
          </details>
        </article>
      <?php endforeach; ?>
    </section>

  </div>

  <details class="lpdo-new" id="new-order"<?php echo $orders ? '' : ' open'; ?>>
    <summary><span class="lpdo-new__plus" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span><span><strong><?php esc_html_e('Order desain baru', $t); ?></strong><small><?php esc_html_e('Pilih jenis desain, isi brief, kirim. Data bisnis Anda sudah tersimpan.', $t); ?></small></span></summary>
    <?php echo lpdo_render_form($client); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
  </details>

  <div class="lpdo-fab" aria-label="<?php esc_attr_e('Alat', $t); ?>">
    <button type="button" class="lpdo-fab__btn" data-dialog="lpdo-kit" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 0 18c1 0 1.6-.8 1.6-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.8-1.7 1.7-1.7H16a5 5 0 0 0 5-5c0-4-4-7.2-9-7.2z"/><circle cx="7.5" cy="11.5" r="1.2"/><circle cx="10.5" cy="7.5" r="1.2"/><circle cx="15.5" cy="8" r="1.2"/></svg><span><?php esc_html_e('Brand kit', $t); ?></span></button>
    <button type="button" class="lpdo-fab__btn" data-dialog="lpdo-share" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v0M14 21h3M20 17v4"/></svg><span><?php esc_html_e('Link & QR', $t); ?></span></button>
  </div>

  <dialog class="lpdo-drawer" id="lpdo-kit" aria-labelledby="lpdo-kit-title">
    <div class="lpdo-drawer__head"><h2 id="lpdo-kit-title"><?php esc_html_e('Brand kit', $t); ?></h2><button type="button" class="lpdo-drawer__close" data-close aria-label="<?php esc_attr_e('Tutup', $t); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
    <div class="lpdo-drawer__body">
      <?php if ($logoId) : ?>
        <div class="lpdo-kit-logos">
          <a class="lpdo-kit-logo" href="<?php echo esc_url(wp_get_attachment_url($logoId)); ?>" download title="<?php esc_attr_e('Unduh logo', $t); ?>"><img src="<?php echo esc_url($logoUrl); ?>" alt="<?php echo esc_attr(sprintf(__('Logo %s', $t), $name)); ?>"></a>
          <a class="lpdo-kit-logo is-dark" href="<?php echo esc_url(wp_get_attachment_url($logoId)); ?>" download title="<?php esc_attr_e('Unduh logo', $t); ?>"><img src="<?php echo esc_url($logoUrl); ?>" alt=""></a>
        </div>
      <?php endif; ?>
      <ul class="lpdo-swatches">
        <?php foreach ([__('Utama', $t) => $primary, __('Kedua', $t) => $secondary, __('Aksen', $t) => $accent] as $label => $hex) : ?>
          <li><button type="button" class="lpdo-swatch" data-copy="<?php echo esc_attr($hex); ?>" style="--sw:<?php echo esc_attr($hex); ?>"><span class="lpdo-swatch__chip"></span><span><?php echo esc_html($label); ?></span><code><?php echo esc_html($hex); ?></code></button></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($font) : ?><p class="lpdo-muted"><?php echo esc_html(sprintf(__('Font: %s', $t), $font)); ?></p><?php endif; ?>
      <?php $contactPrint = lpdo_client_meta($cid, 'contact_print'); if ($contactPrint) : ?>
        <p class="lpdo-kit-label"><?php esc_html_e('Kontak standar di desain', $t); ?></p>
        <p class="lpdo-kit-contact"><?php echo nl2br(esc_html($contactPrint)); ?></p>
      <?php endif; ?>
      <details class="lpdo-profile"<?php echo $notice === 'profile-error' ? ' open' : ''; ?>>
        <summary><?php esc_html_e('Ubah data bisnis & branding', $t); ?></summary>
        <?php echo lpdo_render_profile_form($client); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
      </details>
    </div>
  </dialog>

  <dialog class="lpdo-drawer lpdo-share" id="lpdo-share" aria-labelledby="lpdo-share-title">
    <div class="lpdo-drawer__head"><h2 id="lpdo-share-title"><?php esc_html_e('Link halaman ini', $t); ?></h2><button type="button" class="lpdo-drawer__close" data-close aria-label="<?php esc_attr_e('Tutup', $t); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
    <div class="lpdo-drawer__body">
      <?php echo lpdo_qr_svg($portalUrl, 200, '#111111', '#ffffff', __('QR halaman klien', $t)); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <div class="lpdo-share__actions">
        <button type="button" class="lpdo-btn lpdo-btn--primary" data-copy="<?php echo esc_attr($portalUrl); ?>"><?php esc_html_e('Salin link', $t); ?></button>
        <a class="lpdo-btn lpdo-btn--ghost" href="<?php echo esc_url(lpdo_portal_url($cid, 'qr', 'page')); ?>"><?php esc_html_e('Unduh QR', $t); ?></a>
      </div>
      <p class="lpdo-muted"><?php esc_html_e('Link ini rahasia: siapa pun yang memilikinya bisa melihat order dan file Anda. Bagikan hanya kepada tim Anda.', $t); ?></p>
    </div>
  </dialog>
</main>

<footer class="lpdo-footer">
  <div class="lpdo-wrap">
    <p><?php echo esc_html(sprintf(__('Portal desain oleh %s', $t), $agency)); ?>
      <?php if (lpdo_privacy_url()) : ?> · <a href="<?php echo esc_url(lpdo_privacy_url()); ?>"><?php esc_html_e('Kebijakan Privasi', $t); ?></a><?php endif; ?>
      <?php if (lpdo_setting('notify_email')) : ?> · <a href="mailto:<?php echo esc_attr(lpdo_setting('notify_email')); ?>?subject=<?php echo rawurlencode(sprintf(__('Permintaan data pribadi – %s', $t), $name)); ?>"><?php esc_html_e('Akses, ubah atau hapus data saya', $t); ?></a><?php endif; ?>
    </p>
  </div>
</footer>
<script src="<?php echo esc_url(LPDO_URL . 'assets/form.js?ver=' . LPDO_VERSION); ?>"></script>
<script src="<?php echo esc_url(LPDO_URL . 'assets/preview.js?ver=' . LPDO_VERSION); ?>"></script>
<script src="<?php echo esc_url(LPDO_URL . 'assets/portal.js?ver=' . LPDO_VERSION); ?>"></script>
</body>
</html>
