<?php require __DIR__ . '/header.php'; ?>
<div class="page-head">
  <div>
    <h1>Find clients</h1>
    <p class="muted">Search real Bali businesses. Best prospects: reachable businesses with no real website.</p>
  </div>
</div>

<form method="get" class="card finder-form">
  <input type="hidden" name="p" value="finder">
  <input type="hidden" name="go" value="1">
  <div class="grid3">
    <label>Category<input name="category" value="<?= e($category) ?>" list="cat-list" placeholder="Villa, Spa, Surf school…"></label>
    <label>Area<input name="area" value="<?= e($area) ?>" list="area-list" placeholder="Canggu, Ubud…"></label>
    <label>…or free keyword<input name="keyword" value="<?= e($keyword) ?>" placeholder="e.g. vegan restaurant, silver jewelry"></label>
  </div>
  <div class="btn-row">
    <div class="seg" role="radiogroup" aria-label="Search source">
      <label class="seg-opt"><input type="radio" name="source" value="osm" <?= $source === 'osm' ? 'checked' : '' ?>> OpenStreetMap <small>free</small></label>
      <label class="seg-opt <?= $apiKey === '' ? 'disabled' : '' ?>" title="<?= $apiKey === '' ? 'Add a Google Places API key in Settings to enable' : '' ?>"><input type="radio" name="source" value="google" <?= $source === 'google' ? 'checked' : '' ?> <?= $apiKey === '' ? 'disabled' : '' ?>> Google Maps <small>API key</small></label>
    </div>
    <label class="check"><input type="checkbox" name="nosite" value="1" <?= get('nosite') === '1' ? 'checked' : '' ?>> Only businesses without a proper website</label>
    <button class="btn btn-primary" data-loading="Searching…">Search</button>
  </div>
  <div class="quick">
    <span class="muted small">Quick picks:</span>
    <?php foreach (['Villa', 'Restaurant', 'Spa', 'Surf school', 'Yoga studio', 'Tour operator', 'Dive center', 'Furniture / handicraft', 'Real estate agency', 'Wedding organizer'] as $c): ?>
      <a class="chip" href="<?= e(url('finder', ['go' => 1, 'category' => $c, 'area' => $area, 'source' => $source])) ?>"><?= e($c) ?></a>
    <?php endforeach; ?>
  </div>
</form>

<?php if ($source === 'osm' && $results === null): ?>
  <p class="muted small">Searching OpenStreetMap is free and needs no account. It has fewer reviews than Google, but often lists phone, email and Instagram. Use the Google Maps link on each result to check reviews before you reach out.</p>
<?php endif; ?>

<?php if ($searchError): ?><div class="flash flash-err">Search failed: <?= e($searchError) ?></div><?php endif; ?>

<?php if ($results !== null): ?>
  <form method="post" action="<?= e(url('finder_add')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e('index.php?' . http_build_query(array_diff_key($_GET, ['refresh' => 1]))) ?>">
    <div class="card flush">
      <div class="card-head pad">
        <h2>Results for “<?= e($query) ?>” <span class="muted">(<?= count($results) ?> · <?= $source === 'osm' ? 'OpenStreetMap' : 'Google Maps' ?>)</span></h2>
        <?php if ($fromCache): ?><a class="small" href="<?= e('index.php?' . http_build_query(['refresh' => 1] + $_GET)) ?>" data-loading-link="Searching…">Saved results · refresh</a><?php endif; ?>
        <div class="btn-row">
          <label class="check"><input type="checkbox" data-checkall="place_ids[]"> Select all new</label>
          <button class="btn btn-primary" data-loading="Adding…">+ Add selected to leads</button>
        </div>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th></th><th>Business</th><?php if ($source === 'google'): ?><th>Reviews</th><?php endif; ?><th>Website</th><th>Contact</th><th>Opportunity</th></tr></thead>
          <tbody>
          <?php foreach ($results as $r): [$lvl, $why] = result_hint($r); $have = $existing[$r['place_id']] ?? null; ?>
            <tr class="<?= $have ? 'dim' : '' ?>">
              <td><?php if (!$have): ?><input type="checkbox" name="place_ids[]" value="<?= e($r['place_id']) ?>" <?= $lvl !== 'cold' ? 'checked' : '' ?>><?php endif; ?></td>
              <td>
                <b><?= e($r['name']) ?></b> <?php if ($r['status'] && $r['status'] !== 'OPERATIONAL'): ?><span class="chip warn"><?= e(strtolower(str_replace('_', ' ', $r['status']))) ?></span><?php endif; ?>
                <div class="muted small"><?= e($r['type']) ?> · <?= e($r['address']) ?></div>
                <?php if ($r['maps_url']): ?><a class="small" target="_blank" rel="noopener" href="<?= e(safe_href($r['maps_url'])) ?>"><?= $source === 'osm' ? 'OpenStreetMap' : 'Open in Maps' ?></a><?php endif; ?>
                <?php if (!empty($r['gmaps_url'])): ?> · <a class="small" target="_blank" rel="noopener" href="<?= e($r['gmaps_url']) ?>">Google reviews</a><?php endif; ?>
                <?php if ($have): ?> · <a class="small" href="<?= e(url('lead', ['id' => $have])) ?>">Already in leads →</a><?php endif; ?>
              </td>
              <?php if ($source === 'google'): ?><td class="nowrap"><?= $r['rating'] ? '★ ' . e($r['rating']) . ' <span class="muted small">(' . (int) $r['reviews'] . ')</span>' : '—' ?></td><?php endif; ?>
              <td><?= $r['website'] ? '<a class="small" target="_blank" rel="noopener" href="' . e(safe_href($r['website'])) . '">' . e(mb_strimwidth((string) parse_url($r['website'], PHP_URL_HOST), 0, 32, '…')) . '</a>' : '<span class="warn-text">none</span>' ?></td>
              <td class="small">
                <?php if ($r['phone']): ?><div class="nowrap"><?= e($r['phone']) ?></div><?php endif; ?>
                <?php if (!empty($r['email'])): ?><div><?= e($r['email']) ?></div><?php endif; ?>
                <?php if (!empty($r['instagram'])): ?><a target="_blank" rel="noopener" href="<?= e(instagram_url($r['instagram'])) ?>">@<?= e($r['instagram']) ?></a><?php endif; ?>
                <?php if (!$r['phone'] && empty($r['email']) && empty($r['instagram'])): ?>—<?php endif; ?>
              </td>
              <td><span class="badge prio-<?= e($lvl) ?>"><?= e(ucfirst($lvl)) ?></span> <span class="small muted"><?= e($why) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>
  <?php if ($next): ?>
    <p><a class="btn" href="<?= e(url('finder', ['go' => 1, 'category' => $category, 'area' => $area, 'keyword' => $keyword, 'nosite' => get('nosite'), 'page' => $next, 'source' => $source])) ?>">Next 20 results →</a></p>
  <?php endif; ?>
<?php endif; ?>

<?php $q = trim(($keyword ?: ($category ?: 'villa')) . ' ' . ($area ?: 'Bali')); $tag = strtolower(preg_replace('/[^a-z]/i', '', ($area ?: 'bali') . ($keyword ?: ($category ?: 'villa')))); ?>
<div class="card">
  <h2>Manual prospecting links</h2>
  <p class="muted small">Other places Bali businesses show up — open, then add promising ones with “+ Add lead”.</p>
  <div class="link-grid">
    <a target="_blank" rel="noopener" href="https://www.google.com/maps/search/<?= e(rawurlencode($q . ' Bali')) ?>">📍 Google Maps: <?= e($q) ?></a>
    <a target="_blank" rel="noopener" href="https://www.instagram.com/explore/tags/<?= e(rawurlencode($tag)) ?>/">📸 Instagram #<?= e($tag) ?></a>
    <a target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode($q . ' Bali site:instagram.com')) ?>">🔎 Instagram profiles via Google</a>
    <a target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode($q . ' Bali "wixsite.com" OR "business.site" OR "blogspot.com"')) ?>">🧱 Sites on Wix / Blogger / Business Profile</a>
    <a target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode($q . ' Bali "Powered by WordPress"')) ?>">🔧 Old WordPress sites</a>
    <a target="_blank" rel="noopener" href="https://www.tripadvisor.com/Search?q=<?= e(rawurlencode($q . ' Bali')) ?>">🦉 Tripadvisor</a>
    <a target="_blank" rel="noopener" href="https://www.facebook.com/search/pages?q=<?= e(rawurlencode($q . ' Bali')) ?>">👥 Facebook pages</a>
    <a target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode('"' . ($keyword ?: ($category ?: 'villa')) . '" Bali "baru buka" OR "grand opening" OR "now open"')) ?>">🎉 New openings (need a site soon)</a>
  </div>
</div>
<datalist id="cat-list"><?php foreach (BALI_CATEGORIES as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
<datalist id="area-list"><?php foreach (BALI_AREAS as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>
<?php require __DIR__ . '/footer.php'; ?>
