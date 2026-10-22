<?php
defined('ABSPATH') || exit;
global $wpdb;
$t = lpl_table('leads');
$where = [];
$args = [];
$q = lpl_get('q');
if ($q !== '') {
    $like = '%' . $wpdb->esc_like($q) . '%';
    $where[] = '(name LIKE %s OR contact_person LIKE %s OR category LIKE %s OR area LIKE %s OR tags LIKE %s OR email LIKE %s OR phone LIKE %s OR website LIKE %s OR summary LIKE %s)';
    array_push($args, $like, $like, $like, $like, $like, $like, $like, $like, $like);
}
foreach (['stage', 'priority', 'area', 'category', 'source'] as $f) {
    if (lpl_get($f) !== '') {
        $where[] = "$f = %s";
        $args[] = lpl_get($f);
    }
}
if (isset(LPL_SERVICES[lpl_get('service')])) {
    $where[] = "CONCAT(',', services, ',') LIKE %s";
    $args[] = '%,' . lpl_get('service') . ',%';
}
if (lpl_get('nosite') === '1') {
    $where[] = "website = ''";
}
$sorts = [
    'updated'  => 'updated_at DESC',
    'name'     => 'name ASC',
    'score'    => 'audit_score IS NULL, audit_score DESC',
    'followup' => 'next_followup IS NULL, next_followup ASC',
    'value'    => 'est_value DESC',
    'reviews'  => 'reviews IS NULL, reviews DESC',
];
$sort = isset($sorts[lpl_get('orderby')]) ? lpl_get('orderby') : 'updated';
$sql = "SELECT * FROM $t" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . $sorts[$sort] . ' LIMIT 500';
$leads = $wpdb->get_results($args ? $wpdb->prepare($sql, $args) : $sql, ARRAY_A);
$areas = $wpdb->get_col("SELECT DISTINCT area FROM $t WHERE area != '' ORDER BY area");
$categories = $wpdb->get_col("SELECT DISTINCT category FROM $t WHERE category != '' ORDER BY category");
$view = lpl_get('view') === 'board' ? 'board' : 'table';
$keep = array_filter(['q' => $q, 'stage' => lpl_get('stage'), 'priority' => lpl_get('priority'), 'area' => lpl_get('area'), 'category' => lpl_get('category'), 'source' => lpl_get('source'), 'service' => lpl_get('service'), 'nosite' => lpl_get('nosite'), 'orderby' => lpl_get('orderby')]);
?>
<div class="lpl-head">
  <div><h1>Leads <span class="muted">(<?php echo count($leads); ?>)</span></h1></div>
  <div class="lpl-actions">
    <div class="seg">
      <a class="<?php echo $view === 'table' ? 'on' : ''; ?>" href="<?php echo esc_url(lpl_url('leads', $keep)); ?>">Table</a>
      <a class="<?php echo $view === 'board' ? 'on' : ''; ?>" href="<?php echo esc_url(lpl_url('leads', ['view' => 'board'] + $keep)); ?>">Board</a>
    </div>
    <a class="btn" href="<?php echo esc_url(lpl_url('import')); ?>">Import / export</a>
    <a class="btn btn-primary" href="<?php echo esc_url(lpl_url('leads', ['edit' => 'new'])); ?>">+ Add lead</a>
  </div>
</div>

<form class="filters card" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
  <input type="hidden" name="page" value="lpl-leads">
  <?php if ($view === 'board') : ?><input type="hidden" name="view" value="board"><?php endif; ?>
  <input type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Search name, area, tag, phone, notes…" class="grow">
  <select name="stage"><option value="">All stages</option><?php foreach (LPL_STAGES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected(lpl_get('stage'), $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
  <select name="priority"><option value="">Any priority</option><?php foreach (LPL_PRIORITIES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected(lpl_get('priority'), $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
  <select name="area"><option value="">All areas</option><?php foreach ($areas as $a) : ?><option <?php selected(lpl_get('area'), $a); ?>><?php echo esc_html($a); ?></option><?php endforeach; ?></select>
  <select name="category"><option value="">All categories</option><?php foreach ($categories as $c) : ?><option <?php selected(lpl_get('category'), $c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?></select>
  <select name="source"><option value="">Any source</option><?php foreach (LPL_SOURCES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected(lpl_get('source'), $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
  <select name="service"><option value="">Any service</option><?php foreach (LPL_SERVICES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected(lpl_get('service'), $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
  <select name="orderby">
    <?php foreach (['updated' => 'Recently updated', 'score' => 'Opportunity score', 'followup' => 'Follow-up date', 'reviews' => 'Most reviews', 'value' => 'Deal value', 'name' => 'Name A–Z'] as $k => $v) : ?>
      <option value="<?php echo esc_attr($k); ?>" <?php selected($sort, $k); ?>><?php echo esc_html($v); ?></option>
    <?php endforeach; ?>
  </select>
  <label class="check"><input type="checkbox" name="nosite" value="1" <?php checked(lpl_get('nosite'), '1'); ?>> No website</label>
  <button class="btn btn-primary">Filter</button>
  <a class="btn btn-ghost" href="<?php echo esc_url(lpl_url('leads', $view === 'board' ? ['view' => 'board'] : [])); ?>">Reset</a>
</form>

<?php if (!$leads) : ?>
  <div class="card empty">
    <p>No leads match.</p>
    <a class="btn btn-primary" href="<?php echo esc_url(lpl_url('finder')); ?>">Find clients</a>
  </div>
<?php elseif ($view === 'board') : ?>
  <div class="board">
    <?php foreach (LPL_STAGES as $sk => $sl) : $col = array_filter($leads, fn($l) => $l['stage'] === $sk); ?>
      <section class="col" data-stage="<?php echo esc_attr($sk); ?>">
        <h3><?php echo esc_html($sl); ?> <span><?php echo count($col); ?></span></h3>
        <?php foreach ($col as $l) : ?>
          <article class="kcard" draggable="true" data-id="<?php echo (int) $l['id']; ?>">
            <a href="<?php echo esc_url(lpl_url('leads', ['lead' => $l['id']])); ?>"><b><?php echo esc_html($l['name']); ?></b></a>
            <div class="muted small"><?php echo esc_html(trim($l['category'] . ' · ' . $l['area'], ' ·')); ?></div>
            <div class="kmeta">
              <?php echo lpl_priority_badge($l['priority']); ?>
              <?php echo lpl_score_badge($l['audit_score']); ?>
              <?php if ($l['est_value']) : ?><span class="small"><?php echo esc_html(lpl_rupiah((int) $l['est_value'])); ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
  <?php echo lpl_form_open('lead_stage', ['id' => 'stage-form', 'hidden' => 'hidden']); ?>
    <input type="hidden" name="id"><input type="hidden" name="stage"><input type="hidden" name="back" value="board">
  </form>
  <p class="muted small">Tip: drag cards between columns to change their stage.</p>
<?php else : ?>
  <?php echo lpl_form_open('audit_bulk'); ?>
    <div class="table-wrap card flush">
      <table class="lpl-table">
        <thead><tr>
          <th><input type="checkbox" data-checkall="ids[]" aria-label="Select all"></th>
          <th>Business</th><th>Area</th><th>Stage</th><th>Priority</th>
          <th title="Opportunity score — higher = more room to help">Score</th>
          <th>Reviews</th><th>Follow-up</th><th>Connect</th>
        </tr></thead>
        <tbody>
        <?php foreach ($leads as $l) : ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo (int) $l['id']; ?>"></td>
            <td>
              <a href="<?php echo esc_url(lpl_url('leads', ['lead' => $l['id']])); ?>"><b><?php echo esc_html($l['name']); ?></b></a>
              <div class="muted small"><?php echo esc_html($l['category']); ?><?php if (!$l['website']) : ?> · <span class="warn-text">no website</span><?php endif; ?></div>
            </td>
            <td><?php echo esc_html($l['area']); ?></td>
            <td><?php echo lpl_stage_badge($l['stage']); ?></td>
            <td><?php echo lpl_priority_badge($l['priority']); ?></td>
            <td><?php echo lpl_score_badge($l['audit_score']); ?></td>
            <td class="nowrap"><?php echo $l['rating'] ? '★ ' . esc_html($l['rating']) . ' <span class="muted small">(' . (int) $l['reviews'] . ')</span>' : '—'; ?></td>
            <td class="nowrap <?php echo $l['next_followup'] && $l['next_followup'] < lpl_today() ? 'overdue' : ''; ?>"><?php echo esc_html($l['next_followup'] ?: '—'); ?></td>
            <td class="nowrap">
              <?php if ($l['whatsapp']) : ?><a class="chip wa" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/' . $l['whatsapp']); ?>">WA</a><?php endif; ?>
              <?php if ($l['email']) : ?><a class="chip" href="<?php echo esc_url('mailto:' . $l['email']); ?>">Email</a><?php endif; ?>
              <?php if ($l['instagram']) : ?><a class="chip ig" target="_blank" rel="noopener" href="<?php echo esc_url(lpl_instagram_url($l['instagram'])); ?>">IG</a><?php endif; ?>
              <?php if ($l['website']) : ?><a class="chip" target="_blank" rel="noopener" href="<?php echo esc_url($l['website']); ?>">Web</a><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="bulkbar">
      <button class="btn" data-loading="Auditing websites…">🩺 Audit selected websites</button>
      <span class="muted small">Max 15 at a time. Leads without a website score 95 automatically.</span>
    </div>
  </form>
<?php endif; ?>
