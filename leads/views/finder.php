<?php require __DIR__ . '/header.php'; ?>
<div class="page-head">
  <div>
    <h1>Find clients</h1>
    <p class="muted">Search Bali businesses on Google Maps. Best prospects: lots of reviews but no real website.</p>
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
    <label class="check"><input type="checkbox" name="nosite" value="1" <?= get('nosite') === '1' ? 'checked' : '' ?>> Only show businesses without a proper website</label>
    <button class="btn btn-primary">Search</button>
  </div>
  <div class="quick">
    <span class="muted small">Quick picks:</span>
    <?php foreach (['Villa', 'Restaurant', 'Spa', 'Surf school', 'Yoga studio', 'Tour operator', 'Dive center', 'Furniture / handicraft', 'Real estate agency', 'Wedding organizer'] as $c): ?>
      <a class="chip" href="<?= e(url('finder', ['go' => 1, 'category' => $c, 'area' => $area])) ?>"><?= e($c) ?></a>
    <?php endforeach; ?>
  </div>
</form>

<?php if ($apiKey === ''): ?>
  <div class="card">
    <h2>Connect Google Maps search</h2>
    <p>To search and import businesses automatically, add a <b>Google Places API (New)</b> key in <a href="<?= e(url('settings')) ?>">Settings</a>. Google gives a monthly free usage allowance that easily covers a solo agency's prospecting.</p>
    <p class="muted">Until then, use these manual search links and add leads by hand:</p>
  </div>
<?php endif; ?>

<?php if ($searchError): ?><div class="flash flash-err">Google search failed: <?= e($searchError) ?></div><?php endif; ?>

<?php if ($results !== null): ?>
  <form method="post" action="<?= e(url('finder_add')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e('index.php?' . http_build_query($_GET)) ?>">
    <div class="card flush">
      <div class="card-head pad">
        <h2>Results for “<?= e($query) ?>” <span class="muted">(<?= count($results) ?>)</span></h2>
        <div class="btn-row">
          <label class="check"><input type="checkbox" data-checkall="place_ids[]"> Select all new</label>
          <button class="btn btn-primary">+ Add selected to leads</button>
        </div>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th></th><th>Business</th><th>Reviews</th><th>Website</th><th>Phone</th><th>Opportunity</th></tr></thead>
          <tbody>
          <?php foreach ($results as $r): [$lvl, $why] = result_hint($r); $have = $existing[$r['place_id']] ?? null; ?>
            <tr class="<?= $have ? 'dim' : '' ?>">
              <td><?php if (!$have): ?><input type="checkbox" name="place_ids[]" value="<?= e($r['place_id']) ?>" <?= $lvl !== 'cold' ? 'checked' : '' ?>><?php endif; ?></td>
              <td>
                <b><?= e($r['name']) ?></b> <?php if ($r['status'] && $r['status'] !== 'OPERATIONAL'): ?><span class="chip warn"><?= e(strtolower(str_replace('_', ' ', $r['status']))) ?></span><?php endif; ?>
                <div class="muted small"><?= e($r['type']) ?> · <?= e($r['address']) ?></div>
                <?php if ($r['maps_url']): ?><a class="small" target="_blank" rel="noopener" href="<?= e(safe_href($r['maps_url'])) ?>">Open in Maps</a><?php endif; ?>
                <?php if ($have): ?> · <a class="small" href="<?= e(url('lead', ['id' => $have])) ?>">Already in leads →</a><?php endif; ?>
              </td>
              <td class="nowrap"><?= $r['rating'] ? '★ ' . e($r['rating']) . ' <span class="muted small">(' . (int) $r['reviews'] . ')</span>' : '—' ?></td>
              <td><?= $r['website'] ? '<a class="small" target="_blank" rel="noopener" href="' . e(safe_href($r['website'])) . '">' . e(mb_strimwidth((string) parse_url($r['website'], PHP_URL_HOST), 0, 32, '…')) . '</a>' : '<span class="warn-text">none</span>' ?></td>
              <td class="nowrap small"><?= e($r['phone'] ?: '—') ?></td>
              <td><span class="badge prio-<?= e($lvl) ?>"><?= e(ucfirst($lvl)) ?></span> <span class="small muted"><?= e($why) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>
  <?php if ($next): ?>
    <p><a class="btn" href="<?= e(url('finder', ['go' => 1, 'category' => $category, 'area' => $area, 'keyword' => $keyword, 'nosite' => get('nosite'), 'page' => $next])) ?>">Next 20 results →</a></p>
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
