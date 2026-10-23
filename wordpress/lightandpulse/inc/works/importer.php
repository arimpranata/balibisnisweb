<?php
/**
 * Works → Import: reads an existing portfolio page (default
 * https://lightandpulse.com/works/), finds the projects on it, and lets you
 * pick which ones to import. Runs on your server, so it works wherever your
 * hosting can reach the page.
 *
 * Detection, in order:
 *  1. Links to other websites (client sites) with the nearest heading,
 *     paragraph and image around them.
 *  2. Links to project detail pages on the same site (e.g. /works/villa-x/):
 *     each page is opened and its title, description, image and first link
 *     to another website are read.
 */
defined('ABSPATH') || exit;

const LIGHTANDPULSE_WORKS_SOURCE = 'https://lightandpulse.com/works/';

/** Hosts that are never a client's website. */
const LIGHTANDPULSE_NOT_CLIENT_HOSTS = [
    'facebook.com', 'instagram.com', 'twitter.com', 'x.com', 'linkedin.com', 'youtube.com', 'youtu.be', 'tiktok.com',
    'pinterest.com', 'wa.me', 'whatsapp.com', 'api.whatsapp.com', 't.me', 'telegram.me', 'wordpress.org', 'wordpress.com',
    'elementor.com', 'woocommerce.com', 'google.com', 'goo.gl', 'maps.app.goo.gl', 'g.page', 'behance.net', 'dribbble.com',
    'github.com', 'gravatar.com', 'w.org', 'schema.org', 'gmpg.org', 'fonts.googleapis.com', 'cloudflare.com',
];

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=lp_work', __('Import works', 'lightandpulse'), __('Import from website', 'lightandpulse'), 'edit_posts', 'lp-works-import', 'lightandpulse_works_import_page');
});

function lightandpulse_works_import_page(): void
{
    $found = get_transient('lp_works_scan_' . get_current_user_id());
    $error = get_transient('lp_works_scan_error_' . get_current_user_id());
    delete_transient('lp_works_scan_error_' . get_current_user_id());
    $source = $found['source'] ?? LIGHTANDPULSE_WORKS_SOURCE;
    $existing = [];
    foreach (get_posts(['post_type' => 'lp_work', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
        $existing[untrailingslashit((string) get_post_meta($id, '_lp_work_url', true))] = $id;
    }
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Import works from a website', 'lightandpulse'); ?></h1>
      <?php if (isset($_GET['imported'])) : ?><div class="notice notice-success"><p><?php printf(esc_html__('Imported %d work(s).', 'lightandpulse'), (int) $_GET['imported']); ?> <a href="<?php echo esc_url(admin_url('edit.php?post_type=lp_work')); ?>"><?php esc_html_e('View works', 'lightandpulse'); ?></a></p></div><?php endif; ?>
      <?php if ($error) : ?><div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
      <p><?php esc_html_e('Reads your existing portfolio page and lists the projects it finds. Nothing is saved until you choose what to import.', 'lightandpulse'); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="lp_works_scan">
        <?php wp_nonce_field('lp_works_scan'); ?>
        <p><label><b><?php esc_html_e('Portfolio page', 'lightandpulse'); ?></b><br><input type="url" name="source" value="<?php echo esc_attr($source); ?>" class="regular-text" required></label>
        <?php submit_button(__('Scan page', 'lightandpulse'), 'secondary', 'submit', false); ?></p>
      </form>

      <?php if (!empty($found['items'])) : ?>
        <h2><?php printf(esc_html__('Found %1$d project(s) on %2$s', 'lightandpulse'), count($found['items']), esc_html($found['source'])); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="lp_works_import">
          <?php wp_nonce_field('lp_works_import'); ?>
          <table class="widefat striped">
            <thead><tr><td class="check-column"><input type="checkbox" checked onclick="document.querySelectorAll('.lp-imp').forEach(c=>c.checked=this.checked)"></td><th><?php esc_html_e('Screenshot', 'lightandpulse'); ?></th><th><?php esc_html_e('Title', 'lightandpulse'); ?></th><th><?php esc_html_e('Short description', 'lightandpulse'); ?></th><th><?php esc_html_e('Website', 'lightandpulse'); ?></th></tr></thead>
            <tbody>
            <?php foreach ($found['items'] as $i => $it) : $dupe = $it['url'] && isset($existing[untrailingslashit($it['url'])]); ?>
              <tr>
                <th class="check-column"><input class="lp-imp" type="checkbox" name="pick[]" value="<?php echo (int) $i; ?>" <?php checked(!$dupe); ?>></th>
                <td style="width:140px"><img src="<?php echo esc_url($it['image'] ?: ($it['url'] ? lightandpulse_mshots($it['url'], 400) : '')); ?>" alt="" style="width:128px;height:80px;object-fit:cover;object-position:top;border:1px solid #ddd"><br><small><?php echo $it['image'] ? esc_html__('from page', 'lightandpulse') : esc_html__('automatic screenshot', 'lightandpulse'); ?></small></td>
                <td><input type="text" name="items[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr($it['title']); ?>" class="widefat"><?php if ($dupe) : ?><br><small><?php esc_html_e('Already imported', 'lightandpulse'); ?></small><?php endif; ?></td>
                <td><textarea name="items[<?php echo (int) $i; ?>][desc]" rows="3" class="widefat"><?php echo esc_textarea($it['desc']); ?></textarea></td>
                <td><input type="url" name="items[<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr($it['url']); ?>" class="widefat"><input type="hidden" name="items[<?php echo (int) $i; ?>][image]" value="<?php echo esc_attr($it['image']); ?>"></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <p><label><input type="checkbox" name="download_images" value="1" checked> <?php esc_html_e('Copy images from the page into the Media Library (otherwise an automatic screenshot of each website is used)', 'lightandpulse'); ?></label></p>
          <?php submit_button(__('Import selected', 'lightandpulse')); ?>
        </form>
      <?php elseif ($found) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e('No projects were found on that page. Check the address, or add works by hand under Works → Add work.', 'lightandpulse'); ?></p></div>
      <?php endif; ?>
    </div>
    <?php
}

add_action('admin_post_lp_works_scan', function () {
    if (!current_user_can('edit_posts')) {
        wp_die('', 403);
    }
    check_admin_referer('lp_works_scan');
    @set_time_limit(180);
    $source = esc_url_raw(wp_unslash($_POST['source'] ?? LIGHTANDPULSE_WORKS_SOURCE));
    $result = lightandpulse_scan_works($source);
    $uid = get_current_user_id();
    if (isset($result['error'])) {
        set_transient('lp_works_scan_error_' . $uid, $result['error'], 300);
        delete_transient('lp_works_scan_' . $uid);
    } else {
        set_transient('lp_works_scan_' . $uid, ['source' => $source, 'items' => $result['items']], HOUR_IN_SECONDS);
    }
    wp_safe_redirect(admin_url('edit.php?post_type=lp_work&page=lp-works-import'));
    exit;
});

add_action('admin_post_lp_works_import', function () {
    if (!current_user_can('publish_posts')) {
        wp_die('', 403);
    }
    check_admin_referer('lp_works_import');
    @set_time_limit(300);
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $items = (array) wp_unslash($_POST['items'] ?? []);
    $picked = array_map('intval', (array) ($_POST['pick'] ?? []));
    $download = !empty($_POST['download_images']);
    $count = 0;
    foreach ($picked as $order => $i) {
        if (empty($items[$i]['title'])) {
            continue;
        }
        $it = $items[$i];
        $postId = wp_insert_post([
            'post_type'    => 'lp_work',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field($it['title']),
            'post_excerpt' => sanitize_textarea_field($it['desc'] ?? ''),
            'menu_order'   => $order,
        ], true);
        if (is_wp_error($postId)) {
            continue;
        }
        update_post_meta($postId, '_lp_work_url', esc_url_raw($it['url'] ?? ''));
        $image = esc_url_raw($it['image'] ?? '');
        if ($download && $image) {
            $att = media_sideload_image($image, $postId, sanitize_text_field($it['title']), 'id');
            if (!is_wp_error($att)) {
                set_post_thumbnail($postId, $att);
            }
        }
        $count++;
    }
    delete_transient('lp_works_scan_' . get_current_user_id());
    wp_safe_redirect(admin_url('edit.php?post_type=lp_work&page=lp-works-import&imported=' . $count));
    exit;
});

// ---------------------------------------------------------------------------
// Scanner
// ---------------------------------------------------------------------------
function lightandpulse_fetch_html(string $url): array
{
    $res = wp_safe_remote_get($url, ['timeout' => 20, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0 (compatible; LightandpulseImporter/1.0; +' . home_url('/') . ')']);
    if (is_wp_error($res)) {
        return ['error' => sprintf(__('Could not open %1$s: %2$s', 'lightandpulse'), $url, $res->get_error_message())];
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code >= 400) {
        return ['error' => sprintf(__('%1$s returned HTTP %2$d.', 'lightandpulse'), $url, $code)];
    }
    return ['html' => (string) wp_remote_retrieve_body($res)];
}

function lightandpulse_dom(string $html): DOMXPath
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);
    // Site chrome never holds portfolio items.
    foreach (iterator_to_array($xp->query('//script|//style|//noscript|//header|//footer|//nav|//*[contains(concat(" ",normalize-space(@class)," ")," site-header ")]|//*[contains(concat(" ",normalize-space(@class)," ")," site-footer ")]|//*[@data-elementor-type="header"]|//*[@data-elementor-type="footer"]')) as $n) {
        $n->parentNode?->removeChild($n);
    }
    return $xp;
}

function lightandpulse_abs_url(string $href, string $base): string
{
    $href = trim(html_entity_decode($href));
    if ($href === '' || preg_match('~^(mailto:|tel:|javascript:|#|data:)~i', $href)) {
        return '';
    }
    if (preg_match('~^https?://~i', $href)) {
        return $href;
    }
    $p = wp_parse_url($base);
    $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    if (str_starts_with($href, '//')) {
        return $p['scheme'] . ':' . $href;
    }
    if (str_starts_with($href, '/')) {
        return $origin . $href;
    }
    return $origin . preg_replace('~/[^/]*$~', '/', $p['path'] ?? '/') . $href;
}

function lightandpulse_is_client_host(string $host, string $sourceHost): bool
{
    $host = strtolower(preg_replace('/^www\./', '', $host));
    if ($host === '' || $host === strtolower(preg_replace('/^www\./', '', $sourceHost))) {
        return false;
    }
    foreach (LIGHTANDPULSE_NOT_CLIENT_HOSTS as $bad) {
        if ($host === $bad || str_ends_with($host, '.' . $bad)) {
            return false;
        }
    }
    return true;
}

function lightandpulse_clean(string $text, int $max = 0): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    return $max && mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
}

function lightandpulse_img_src(DOMElement $img, string $base): string
{
    foreach (['data-src', 'data-lazy-src', 'data-original', 'src'] as $attr) {
        $v = $img->getAttribute($attr);
        if ($v && !str_starts_with($v, 'data:')) {
            return lightandpulse_abs_url($v, $base);
        }
    }
    $srcset = $img->getAttribute('srcset') ?: $img->getAttribute('data-srcset');
    if ($srcset) {
        $first = trim(explode(' ', trim(explode(',', $srcset)[0]))[0]);
        return lightandpulse_abs_url($first, $base);
    }
    return '';
}

/** Title, description and image from the closest "card" around a link. */
function lightandpulse_card_details(DOMXPath $xp, DOMElement $a, string $base, string $sourceHost): array
{
    $node = $a;
    $card = $a;
    for ($depth = 0; $depth < 6 && $node->parentNode instanceof DOMElement; $depth++) {
        $node = $node->parentNode;
        // Stop before a container that holds several different client sites (that's the whole grid).
        $hosts = [];
        foreach ($xp->query('.//a[@href]', $node) as $l) {
            $h = (string) wp_parse_url(lightandpulse_abs_url($l->getAttribute('href'), $base), PHP_URL_HOST);
            if (lightandpulse_is_client_host($h, $sourceHost)) {
                $hosts[preg_replace('/^www\./', '', strtolower($h))] = true;
            }
        }
        if (count($hosts) > 1) {
            break;
        }
        $card = $node;
        if ($xp->query('.//img', $node)->length && $xp->query('.//h1|.//h2|.//h3|.//h4|.//h5', $node)->length) {
            break;
        }
    }
    $heading = $xp->query('.//h1|.//h2|.//h3|.//h4|.//h5', $card)->item(0);
    $title = $heading ? lightandpulse_clean($heading->textContent, 120) : '';
    $linkText = lightandpulse_clean($a->textContent, 120);
    if ($title === '' && $linkText !== '' && !preg_match('/^(visit|view|see|open|live|website|lihat|kunjungi|more|read more|selengkapnya)\b/i', $linkText) && !preg_match('~^https?://~', $linkText)) {
        $title = $linkText;
    }
    $desc = '';
    foreach ($xp->query('.//p', $card) as $p) {
        $t = lightandpulse_clean($p->textContent);
        if (mb_strlen($t) >= 25 && $t !== $title) {
            $desc = lightandpulse_clean($t, 240);
            break;
        }
    }
    $img = $xp->query('.//img', $card)->item(0);
    return ['title' => $title, 'desc' => $desc, 'image' => $img instanceof DOMElement ? lightandpulse_img_src($img, $base) : ''];
}

function lightandpulse_scan_works(string $source): array
{
    $page = lightandpulse_fetch_html($source);
    if (isset($page['error'])) {
        return $page;
    }
    $sourceHost = (string) wp_parse_url($source, PHP_URL_HOST);
    $sourcePath = rtrim((string) wp_parse_url($source, PHP_URL_PATH), '/') . '/';
    $xp = lightandpulse_dom($page['html']);

    // 1. Links to client websites.
    $items = [];
    foreach ($xp->query('//a[@href]') as $a) {
        $url = lightandpulse_abs_url($a->getAttribute('href'), $source);
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if (!$url || !lightandpulse_is_client_host($host, $sourceHost)) {
            continue;
        }
        $key = preg_replace('/^www\./', '', strtolower($host));
        $d = lightandpulse_card_details($xp, $a, $source, $sourceHost);
        if (!isset($items[$key])) {
            $items[$key] = ['url' => untrailingslashit(strtok($url, '#')) . '/', 'title' => '', 'desc' => '', 'image' => ''];
        }
        // Several links can point to one site (image + title + button): keep the best details.
        foreach (['title', 'desc', 'image'] as $f) {
            if ($items[$key][$f] === '' && $d[$f] !== '') {
                $items[$key][$f] = $d[$f];
            }
        }
    }

    // 2. Project detail pages on the same site (e.g. /works/project-name/).
    if (count($items) < 2) {
        $detail = [];
        foreach ($xp->query('//a[@href]') as $a) {
            $url = strtok(lightandpulse_abs_url($a->getAttribute('href'), $source), '#');
            $p = wp_parse_url((string) $url);
            if (!$url || strcasecmp((string) ($p['host'] ?? ''), $sourceHost) !== 0) {
                continue;
            }
            $path = rtrim((string) ($p['path'] ?? ''), '/') . '/';
            if ($path !== $sourcePath && str_starts_with($path, $sourcePath) && !preg_match('~/(page|category|tag|feed)/~', $path)) {
                $detail[$path] = $url;
            }
        }
        foreach (array_slice($detail, 0, 40) as $url) {
            $d = lightandpulse_scan_detail($url, $sourceHost);
            if ($d && $d['title'] !== '') {
                $items[$d['url']] = $d;
            }
        }
    }

    foreach ($items as $k => &$it) {
        if ($it['title'] === '') {
            $it['title'] = ucwords(str_replace(['-', '.'], ' ', preg_replace('/\.(com|co\.id|id|net|org|io|biz|info|bali)(\/.*)?$/', '', lightandpulse_domain($it['url']))));
        }
    }
    unset($it);
    return ['items' => array_values($items)];
}

function lightandpulse_scan_detail(string $url, string $sourceHost): ?array
{
    $page = lightandpulse_fetch_html($url);
    if (isset($page['error'])) {
        return null;
    }
    $html = $page['html'];
    $meta = function (string $prop) use ($html): string {
        if (preg_match('~<meta[^>]+(?:property|name)=["\']' . preg_quote($prop, '~') . '["\'][^>]+content=["\']([^"\']*)~i', $html, $m)
            || preg_match('~<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:property|name)=["\']' . preg_quote($prop, '~') . '["\']~i', $html, $m)) {
            return lightandpulse_clean($m[1]);
        }
        return '';
    };
    $xp = lightandpulse_dom($html);
    $h1 = $xp->query('//h1')->item(0);
    $title = $h1 ? lightandpulse_clean($h1->textContent, 120) : preg_replace('/\s*[|–—-]\s*[^|–—-]+$/u', '', $meta('og:title'));
    $desc = $meta('og:description') ?: $meta('description');
    if ($desc === '') {
        foreach ($xp->query('//main//p|//article//p|//p') as $p) {
            $t = lightandpulse_clean($p->textContent);
            if (mb_strlen($t) >= 25) {
                $desc = $t;
                break;
            }
        }
    }
    $site = '';
    foreach ($xp->query('//a[@href]') as $a) {
        $href = lightandpulse_abs_url($a->getAttribute('href'), $url);
        if ($href && lightandpulse_is_client_host((string) wp_parse_url($href, PHP_URL_HOST), $sourceHost)) {
            $site = $href;
            break;
        }
    }
    return ['url' => $site ?: $url, 'title' => $title, 'desc' => lightandpulse_clean($desc, 240), 'image' => $meta('og:image')];
}
