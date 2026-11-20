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
];
$portalUrl = lpdo_portal_url($cid);
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
      <?php if ($logoUrl) : ?><img class="lpdo-brand__logo" src="<?php echo esc_url($logoUrl); ?>" alt="<?php echo esc_attr($name); ?>"><?php else : ?><span class="lpdo-brand__initials" aria-hidden="true"><?php echo esc_html($initials); ?></span><?php endif; ?>
      <div><p class="lpdo-brand__name"><?php echo esc_html($name); ?></p><?php if ($tagline) : ?><p class="lpdo-brand__tagline"><?php echo esc_html($tagline); ?></p><?php endif; ?></div>
    </div>
    <a class="lpdo-btn lpdo-btn--accent" href="#new-order" data-open-new-order><?php esc_html_e('+ Order desain baru', $t); ?></a>
  </div>
</header>

<main class="lpdo-wrap lpdo-main">
  <?php if (isset($notices[$notice])) : ?><div class="lpdo-notice <?php echo $notice === 'note-missing' ? 'is-error' : ''; ?>" role="status"><p><?php echo esc_html($notices[$notice]); ?></p></div><?php endif; ?>

  <div class="lpdo-layout">
    <section class="lpdo-orders" aria-labelledby="lpdo-orders-title">
      <h1 id="lpdo-orders-title"><?php esc_html_e('Order desain', $t); ?></h1>
      <?php if (!$orders) : ?><p class="lpdo-muted"><?php esc_html_e('Belum ada order.', $t); ?></p><?php endif; ?>

      <?php foreach ($orders as $order) :
          $oid = $order->ID;
          $type = lpdo_order_type($oid);
          $status = lpdo_order_status($oid);
          $fields = lpdo_order_fields($oid);
          $finals = lpdo_order_files($oid, 'finals');
          $proofs = lpdo_order_files($oid, 'proofs');
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
            <span class="lpdo-status lpdo-status--<?php echo esc_attr($status); ?>"><?php echo esc_html($statuses[$status]); ?></span>
          </header>

          <ol class="lpdo-progress" aria-label="<?php esc_attr_e('Tahap order', $t); ?>">
            <?php foreach ($steps as $i => $s) : ?>
              <li class="<?php echo $i < $stepIndex ? 'is-done' : ($i === $stepIndex ? 'is-current' : ''); ?>"<?php echo $i === $stepIndex ? ' aria-current="step"' : ''; ?>><span><?php echo esc_html($s === 'proof' && $status === 'revision' ? $statuses['revision'] : $statuses[$s]); ?></span></li>
            <?php endforeach; ?>
          </ol>

          <?php if ($finals) : ?>
            <div class="lpdo-block">
              <h3><?php esc_html_e('File siap diunduh', $t); ?></h3>
              <ul class="lpdo-files">
                <?php foreach ($finals as $fid) :
                    $dl = lpdo_portal_url($cid, 'file', (string) $fid);
                    $mime = (string) get_post_mime_type($fid);
                    ?>
                  <li class="lpdo-file">
                    <div class="lpdo-file__thumb"><?php echo str_starts_with($mime, 'image/') ? '<img src="' . esc_url(add_query_arg('view', 1, $dl)) . '" alt="" loading="lazy">' : '<span>' . esc_html(strtoupper(pathinfo((string) get_attached_file($fid), PATHINFO_EXTENSION))) . '</span>'; ?></div>
                    <div class="lpdo-file__info">
                      <p class="lpdo-file__name"><?php echo esc_html(basename((string) get_attached_file($fid))); ?></p>
                      <p class="lpdo-muted"><?php echo esc_html(lpdo_file_size($fid)); ?></p>
                      <div class="lpdo-file__actions">
                        <a class="lpdo-btn lpdo-btn--primary" href="<?php echo esc_url($dl); ?>"><?php esc_html_e('Unduh', $t); ?></a>
                        <button type="button" class="lpdo-btn lpdo-btn--ghost" data-copy="<?php echo esc_attr($dl); ?>"><?php esc_html_e('Salin link', $t); ?></button>
                        <a class="lpdo-btn lpdo-btn--ghost" href="<?php echo esc_url(lpdo_portal_url($cid, 'qr', 'file-' . $fid)); ?>"><?php esc_html_e('Unduh QR', $t); ?></a>
                      </div>
                    </div>
                    <div class="lpdo-file__qr" title="<?php esc_attr_e('Pindai untuk mengunduh file ini', $t); ?>"><?php echo lpdo_qr_svg($dl, 112, '#111111', '#ffffff', __('QR untuk mengunduh file ini', $t)); // phpcs:ignore WordPress.Security.EscapeOutput -- generated SVG. ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($finals) > 1 && class_exists('ZipArchive')) : $zip = lpdo_portal_url($cid, 'zip', (string) $oid); ?>
                <div class="lpdo-zip">
                  <?php echo lpdo_qr_svg($zip, 88, '#111111', '#ffffff', __('QR untuk mengunduh semua file', $t)); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                  <div><p><strong><?php esc_html_e('Semua file dalam satu ZIP', $t); ?></strong></p><a class="lpdo-btn lpdo-btn--primary" href="<?php echo esc_url($zip); ?>"><?php esc_html_e('Unduh semua (ZIP)', $t); ?></a></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($proofs && in_array($status, ['proof', 'revision'], true)) : ?>
            <div class="lpdo-block">
              <h3><?php esc_html_e('Desain untuk Anda periksa', $t); ?></h3>
              <div class="lpdo-proofs">
                <?php foreach ($proofs as $pid) : $view = add_query_arg('view', 1, lpdo_portal_url($cid, 'file', (string) $pid)); ?>
                  <a class="lpdo-proof" href="<?php echo esc_url($view); ?>" target="_blank" rel="noopener">
                    <?php if (str_starts_with((string) get_post_mime_type($pid), 'image/')) : ?><img src="<?php echo esc_url($view); ?>" alt="<?php esc_attr_e('Pratinjau desain', $t); ?>" loading="lazy"><?php else : ?><span><?php echo esc_html(basename((string) get_attached_file($pid))); ?></span><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
              <?php if ($status === 'proof') : ?>
                <div class="lpdo-decide">
                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="lpdo_portal"><input type="hidden" name="lpdo_client_key" value="<?php echo esc_attr(lpdo_client_key($cid)); ?>">
                    <input type="hidden" name="lpdo_order" value="<?php echo (int) $oid; ?>"><input type="hidden" name="lpdo_decision" value="approve">
                    <button class="lpdo-btn lpdo-btn--accent"><?php esc_html_e('Setujui desain', $t); ?></button>
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
                    $v = $fields[$f['key']] ?? '';
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

    <aside class="lpdo-side">
      <div class="lpdo-card">
        <h2><?php esc_html_e('Brand kit', $t); ?></h2>
        <?php if ($logoId) : ?><a class="lpdo-kit-logo" href="<?php echo esc_url(wp_get_attachment_url($logoId)); ?>" download><img src="<?php echo esc_url($logoUrl); ?>" alt="<?php echo esc_attr(sprintf(__('Logo %s', $t), $name)); ?>"></a><?php endif; ?>
        <ul class="lpdo-swatches">
          <?php foreach ([__('Utama', $t) => $primary, __('Kedua', $t) => $secondary, __('Aksen', $t) => $accent] as $label => $hex) : ?>
            <li><button type="button" class="lpdo-swatch" data-copy="<?php echo esc_attr($hex); ?>" style="--sw:<?php echo esc_attr($hex); ?>"><span class="lpdo-swatch__chip"></span><span><?php echo esc_html($label); ?></span><code><?php echo esc_html($hex); ?></code></button></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($font) : ?><p class="lpdo-muted"><?php echo esc_html(sprintf(__('Font: %s', $t), $font)); ?></p><?php endif; ?>
      </div>
      <div class="lpdo-card lpdo-share">
        <h2><?php esc_html_e('Link halaman ini', $t); ?></h2>
        <?php echo lpdo_qr_svg($portalUrl, 156, '#111111', '#ffffff', __('QR halaman klien', $t)); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <div class="lpdo-share__actions">
          <button type="button" class="lpdo-btn lpdo-btn--primary" data-copy="<?php echo esc_attr($portalUrl); ?>"><?php esc_html_e('Salin link', $t); ?></button>
          <a class="lpdo-btn lpdo-btn--ghost" href="<?php echo esc_url(lpdo_portal_url($cid, 'qr', 'page')); ?>"><?php esc_html_e('Unduh QR', $t); ?></a>
        </div>
        <p class="lpdo-muted"><?php esc_html_e('Link ini rahasia: siapa pun yang memilikinya bisa melihat order dan file Anda. Bagikan hanya kepada tim Anda.', $t); ?></p>
      </div>
    </aside>
  </div>

  <details class="lpdo-new" id="new-order">
    <summary><?php esc_html_e('+ Order desain baru', $t); ?></summary>
    <?php echo lpdo_render_form($client); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
  </details>
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
<script src="<?php echo esc_url(LPDO_URL . 'assets/portal.js?ver=' . LPDO_VERSION); ?>"></script>
</body>
</html>
