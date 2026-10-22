<?php
defined('ABSPATH') || exit;
global $wpdb;
$apiKey = (string) lpl_setting('places_api_key');
$category = lpl_get('category');
$area = lpl_get('area');
$keyword = lpl_get('keyword');
// OpenStreetMap is free and the default; Google is used only when chosen and a key is saved.
$source = lpl_get('source') === 'google' && $apiKey !== '' ? 'google' : 'osm';
$query = trim(($keyword !== '' ? $keyword : $category) . ($area !== '' ? " in $area" : '') . ' Bali');
$results = null;
$fromCache = false;
$searchError = null;
$next = null;
$existing = [];
if (lpl_get('go') === '1' && ($keyword !== '' || $category !== '')) {
    $cacheKey = 'lpl_s_' . md5(implode('|', [$source, mb_strtolower($category), mb_strtolower($area), mb_strtolower($keyword), lpl_get('pagetoken')]));
    $res = lpl_get('refresh') === '1' ? false : get_transient($cacheKey);
    $fromCache = is_array($res);
    if (!$fromCache) {
        @set_time_limit(90);
        $res = $source === 'google'
            ? lpl_places_search($apiKey, $query, lpl_get('pagetoken') ?: null)
            : lpl_osm_search($category, $area, $keyword, (string) lpl_setting('osm_endpoint'));
        if (!isset($res['error'])) {
            set_transient($cacheKey, $res, 12 * HOUR_IN_SECONDS);
        }
    }
    if (isset($res['error'])) {
        $searchError = $res['error'];
    } else {
        $results = $res['results'];
        $next = $res['next'];
        if (lpl_get('nosite') === '1') {
            $results = array_values(array_filter($results, fn($r) => lpl_result_hint($r)[0] !== 'cold'));
        }
        $ids = array_column($results, 'place_id');
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '%s'));
            foreach ($wpdb->get_results($wpdb->prepare('SELECT place_id, id FROM ' . lpl_table('leads') . " WHERE place_id IN ($in)", $ids), ARRAY_A) as $row) {
                $existing[$row['place_id']] = (int) $row['id'];
            }
        }
        // Remembered for "Add to leads", so adding never re-runs the search.
        set_transient(lpl_finder_cache_key(), [
            'results'  => array_column($results, null, 'place_id'),
            'category' => $category ?: $keyword,
            'area'     => $area,
            'source'   => $source === 'google' ? 'google_maps' : 'openstreetmap',
        ], DAY_IN_SECONDS);
    }
}
$self = lpl_url('finder', array_filter(['go' => lpl_get('go'), 'category' => $category, 'area' => $area, 'keyword' => $keyword, 'source' => $source, 'nosite' => lpl_get('nosite'), 'pagetoken' => lpl_get('pagetoken')]));
?>
<div class="lpl-head">
  <div>
    <h1>Find clients</h1>
    <p class="muted">Search real Bali businesses. Best prospects: reachable businesses with no real website.</p>
  </div>
</div>

<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="card finder-form">
  <input type="hidden" name="page" value="lpl-finder">
  <input type="hidden" name="go" value="1">
  <div class="grid3">
    <label>Category<input name="category" value="<?php echo esc_attr($category); ?>" list="cat-list" placeholder="Villa, Spa, Surf school…"></label>
    <label>Area<input name="area" value="<?php echo esc_attr($area); ?>" list="area-list" placeholder="Canggu, Ubud…"></label>
    <label>…or business name / keyword<input name="keyword" value="<?php echo esc_attr($keyword); ?>" placeholder="e.g. warung, silver"></label>
  </div>
  <div class="btn-row">
    <div class="seg" role="radiogroup" aria-label="Search source">
      <label class="seg-opt"><input type="radio" name="source" value="osm" <?php checked($source, 'osm'); ?>> OpenStreetMap <small>free</small></label>
      <label class="seg-opt <?php echo $apiKey === '' ? 'disabled' : ''; ?>" title="<?php echo $apiKey === '' ? esc_attr__('Add a Google Places API key in Settings to enable', 'lightandpulse') : ''; ?>"><input type="radio" name="source" value="google" <?php checked($source, 'google'); ?> <?php disabled($apiKey === ''); ?>> Google Maps <small>API key</small></label>
    </div>
    <label class="check"><input type="checkbox" name="nosite" value="1" <?php checked(lpl_get('nosite'), '1'); ?>> Only businesses without a proper website</label>
    <button class="btn btn-primary" data-loading="Searching…">Search</button>
  </div>
  <div class="quick">
    <span class="muted small">Quick picks:</span>
    <?php foreach (['Villa', 'Restaurant', 'Spa', 'Surf school', 'Yoga studio', 'Tour operator', 'Dive center', 'Furniture / handicraft', 'Real estate agency', 'Wedding organizer'] as $c) : ?>
      <a class="chip" href="<?php echo esc_url(lpl_url('finder', ['go' => 1, 'category' => $c, 'area' => $area, 'source' => $source])); ?>"><?php echo esc_html($c); ?></a>
    <?php endforeach; ?>
  </div>
</form>

<?php if ($source === 'osm' && $results === null && !$searchError) : ?>
  <p class="muted small">Searching OpenStreetMap is free and needs no account. It has fewer reviews than Google, but often lists phone, email and Instagram. Use the Google reviews link on each result before you reach out.</p>
<?php endif; ?>

<?php if ($searchError) : ?><div class="notice notice-error inline"><p><?php echo esc_html__('Search failed:', 'lightandpulse') . ' ' . esc_html($searchError); ?></p></div><?php endif; ?>

<?php if ($results !== null) : ?>
  <?php echo lpl_form_open('finder_add'); ?>
    <input type="hidden" name="back" value="<?php echo esc_url($self); ?>">
    <div class="card flush">
      <div class="card-head pad">
        <h2>Results for “<?php echo esc_html($query); ?>” <span class="muted">(<?php echo count($results); ?> · <?php echo $source === 'osm' ? 'OpenStreetMap' : 'Google Maps'; ?>)</span></h2>
        <?php if ($fromCache) : ?><a class="small" href="<?php echo esc_url(add_query_arg('refresh', 1, $self)); ?>" data-loading-link="Searching…">Saved results · refresh</a><?php endif; ?>
        <div class="btn-row">
          <label class="check"><input type="checkbox" data-checkall="place_ids[]"> Select all</label>
          <button class="btn btn-primary" data-loading="Adding…">+ Add selected to leads</button>
        </div>
      </div>
      <?php if (!$results) : ?>
        <p class="pad muted">No businesses found. Try another category, a wider area (leave Area empty for all of Bali), or a keyword.</p>
      <?php else : ?>
      <div class="table-wrap">
        <table class="lpl-table">
          <thead><tr><th></th><th>Business</th><?php if ($source === 'google') : ?><th>Reviews</th><?php endif; ?><th>Website</th><th>Contact</th><th>Opportunity</th></tr></thead>
          <tbody>
          <?php foreach ($results as $r) : [$lvl, $why] = lpl_result_hint($r); $have = $existing[$r['place_id']] ?? null; ?>
            <tr class="<?php echo $have ? 'dim' : ''; ?>">
              <td><?php if (!$have) : ?><input type="checkbox" name="place_ids[]" value="<?php echo esc_attr($r['place_id']); ?>" <?php checked($lvl !== 'cold'); ?>><?php endif; ?></td>
              <td>
                <b><?php echo esc_html($r['name']); ?></b> <?php if ($r['status'] && $r['status'] !== 'OPERATIONAL') : ?><span class="chip warn"><?php echo esc_html(strtolower(str_replace('_', ' ', $r['status']))); ?></span><?php endif; ?>
                <div class="muted small"><?php echo esc_html(trim($r['type'] . ' · ' . $r['address'], ' ·')); ?></div>
                <?php if ($r['maps_url']) : ?><a class="small" target="_blank" rel="noopener" href="<?php echo esc_url($r['maps_url']); ?>"><?php echo $source === 'osm' ? 'OpenStreetMap' : 'Open in Maps'; ?></a><?php endif; ?>
                <?php if (!empty($r['gmaps_url'])) : ?> · <a class="small" target="_blank" rel="noopener" href="<?php echo esc_url($r['gmaps_url']); ?>">Google reviews</a><?php endif; ?>
                <?php if ($have) : ?> · <a class="small" href="<?php echo esc_url(lpl_url('leads', ['lead' => $have])); ?>">Already in leads →</a><?php endif; ?>
              </td>
              <?php if ($source === 'google') : ?><td class="nowrap"><?php echo $r['rating'] ? '★ ' . esc_html($r['rating']) . ' <span class="muted small">(' . (int) $r['reviews'] . ')</span>' : '—'; ?></td><?php endif; ?>
              <td><?php echo $r['website'] ? '<a class="small" target="_blank" rel="noopener" href="' . esc_url($r['website']) . '">' . esc_html(mb_strimwidth((string) wp_parse_url($r['website'], PHP_URL_HOST), 0, 32, '…')) . '</a>' : '<span class="warn-text">none</span>'; ?></td>
              <td class="small">
                <?php if ($r['phone']) : ?><div class="nowrap"><?php echo esc_html($r['phone']); ?></div><?php endif; ?>
                <?php if (!empty($r['email'])) : ?><div><?php echo esc_html($r['email']); ?></div><?php endif; ?>
                <?php if (!empty($r['instagram'])) : ?><a target="_blank" rel="noopener" href="<?php echo esc_url(lpl_instagram_url($r['instagram'])); ?>">@<?php echo esc_html($r['instagram']); ?></a><?php endif; ?>
                <?php if (!$r['phone'] && empty($r['email']) && empty($r['instagram'])) : ?>—<?php endif; ?>
              </td>
              <td><span class="lpl-badge prio-<?php echo esc_attr($lvl); ?>"><?php echo esc_html(ucfirst($lvl)); ?></span> <span class="small muted"><?php echo esc_html($why); ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </form>
  <?php if ($next) : ?>
    <p><a class="btn" href="<?php echo esc_url(lpl_url('finder', ['go' => 1, 'category' => $category, 'area' => $area, 'keyword' => $keyword, 'nosite' => lpl_get('nosite'), 'pagetoken' => $next, 'source' => $source])); ?>">Next 20 results →</a></p>
  <?php endif; ?>
<?php endif; ?>

<?php $q = trim(($keyword ?: ($category ?: 'villa')) . ' ' . ($area ?: 'Bali')); $tag = strtolower(preg_replace('/[^a-z]/i', '', ($area ?: 'bali') . ($keyword ?: ($category ?: 'villa')))); ?>
<div class="card">
  <h2>Manual prospecting links</h2>
  <p class="muted small">Other places Bali businesses show up. Open one, then add promising businesses with “+ Add lead”.</p>
  <div class="link-grid">
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/maps/search/' . rawurlencode($q . ' Bali')); ?>">📍 Google Maps: <?php echo esc_html($q); ?></a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.instagram.com/explore/tags/' . rawurlencode($tag) . '/'); ?>">📸 Instagram #<?php echo esc_html($tag); ?></a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode($q . ' Bali site:instagram.com')); ?>">🔎 Instagram profiles via Google</a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode($q . ' Bali "wixsite.com" OR "business.site" OR "blogspot.com"')); ?>">🧱 Sites on Wix / Blogger</a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode($q . ' Bali "Powered by WordPress"')); ?>">🔧 Old WordPress sites</a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.tripadvisor.com/Search?q=' . rawurlencode($q . ' Bali')); ?>">🦉 Tripadvisor</a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.facebook.com/search/pages?q=' . rawurlencode($q . ' Bali')); ?>">👥 Facebook pages</a>
    <a target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode('"' . ($keyword ?: ($category ?: 'villa')) . '" Bali "baru buka" OR "grand opening" OR "now open"')); ?>">🎉 New openings</a>
  </div>
</div>
<datalist id="cat-list"><?php foreach (LPL_BALI_CATEGORIES as $c) : ?><option value="<?php echo esc_attr($c); ?>"><?php endforeach; ?></datalist>
<datalist id="area-list"><?php foreach (LPL_BALI_AREAS as $a) : ?><option value="<?php echo esc_attr($a); ?>"><?php endforeach; ?></datalist>
