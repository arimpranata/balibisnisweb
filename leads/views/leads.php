<?php require __DIR__ . '/header.php'; $qs = $_GET; ?>
<div class="page-head">
  <div><h1>Leads <span class="muted">(<?= count($leads) ?>)</span></h1></div>
  <div class="actions">
    <div class="seg">
      <a class="<?= $view === 'table' ? 'on' : '' ?>" href="<?= e('index.php?' . http_build_query(['view' => 'table'] + $qs)) ?>">Table</a>
      <a class="<?= $view === 'board' ? 'on' : '' ?>" href="<?= e('index.php?' . http_build_query(['view' => 'board'] + $qs)) ?>">Board</a>
    </div>
    <a class="btn" href="<?= e(url('import')) ?>">Import</a>
    <a class="btn" href="<?= e(url('export')) ?>">Export CSV</a>
    <a class="btn btn-primary" href="<?= e(url('lead_edit')) ?>">+ Add lead</a>
  </div>
</div>

<form class="filters card" method="get">
  <input type="hidden" name="p" value="leads">
  <input type="hidden" name="view" value="<?= e($view) ?>">
  <input type="search" name="q" value="<?= e(get('q')) ?>" placeholder="Search name, area, tag, phone, notes…" class="grow">
  <select name="stage"><option value="">All stages</option><?php foreach (STAGES as $k => $v): ?><option value="<?= e($k) ?>" <?= get('stage') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <select name="priority"><option value="">Any priority</option><?php foreach (PRIORITIES as $k => $v): ?><option value="<?= e($k) ?>" <?= get('priority') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <select name="area"><option value="">All areas</option><?php foreach ($areas as $a): ?><option <?= get('area') === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select>
  <select name="category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option <?= get('category') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  <select name="service"><option value="">Any service</option><?php foreach (SERVICES as $k => $v): ?><option value="<?= e($k) ?>" <?= get('service') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <select name="sort">
    <?php foreach (['updated' => 'Recently updated', 'score' => 'Opportunity score', 'followup' => 'Follow-up date', 'reviews' => 'Most reviews', 'value' => 'Deal value', 'name' => 'Name A–Z'] as $k => $v): ?>
      <option value="<?= e($k) ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($v) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="check"><input type="checkbox" name="nosite" value="1" <?= get('nosite') === '1' ? 'checked' : '' ?>> No website</label>
  <button class="btn btn-primary">Filter</button>
  <a class="btn btn-ghost" href="<?= e(url('leads', ['view' => $view])) ?>">Reset</a>
</form>

<?php if (!$leads): ?>
  <div class="card empty">
    <p>No leads match.</p>
    <a class="btn btn-primary" href="<?= e(url('finder')) ?>">Find clients on Google Maps</a>
  </div>
<?php elseif ($view === 'board'): ?>
  <div class="board">
    <?php foreach (STAGES as $sk => $sl): $col = array_filter($leads, fn($l) => $l['stage'] === $sk); ?>
      <section class="col" data-stage="<?= e($sk) ?>">
        <h3><?= e($sl) ?> <span><?= count($col) ?></span></h3>
        <?php foreach ($col as $l): ?>
          <article class="kcard" draggable="true" data-id="<?= (int) $l['id'] ?>">
            <a href="<?= e(url('lead', ['id' => $l['id']])) ?>"><b><?= e($l['name']) ?></b></a>
            <div class="muted small"><?= e(trim($l['category'] . ' · ' . $l['area'], ' ·')) ?></div>
            <div class="kmeta">
              <?= priority_badge($l['priority']) ?>
              <?= score_badge($l['audit_score'] !== null ? (int) $l['audit_score'] : null) ?>
              <?php if ($l['est_value']): ?><span class="small"><?= e(rupiah((int) $l['est_value'])) ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
  <form id="stage-form" method="post" action="<?= e(url('lead_stage')) ?>" hidden>
    <?= csrf_field() ?><input type="hidden" name="id"><input type="hidden" name="stage"><input type="hidden" name="back" value="board">
  </form>
  <p class="muted small">Tip: drag cards between columns to change their stage.</p>
<?php else: ?>
  <form method="post" action="<?= e(url('audit_bulk')) ?>" id="bulk">
    <?= csrf_field() ?>
    <div class="table-wrap card flush">
      <table class="table">
        <thead><tr>
          <th><input type="checkbox" data-checkall="ids[]" aria-label="Select all"></th>
          <th>Business</th><th>Area</th><th>Stage</th><th>Priority</th>
          <th title="Opportunity score — higher = more room to help">Score</th>
          <th>Reviews</th><th>Follow-up</th><th>Connect</th>
        </tr></thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int) $l['id'] ?>"></td>
            <td>
              <a href="<?= e(url('lead', ['id' => $l['id']])) ?>"><b><?= e($l['name']) ?></b></a>
              <div class="muted small"><?= e($l['category']) ?><?php if (!$l['website']): ?> · <span class="warn-text">no website</span><?php endif; ?></div>
            </td>
            <td><?= e($l['area']) ?></td>
            <td><?= stage_badge($l['stage']) ?></td>
            <td><?= priority_badge($l['priority']) ?></td>
            <td><?= score_badge($l['audit_score'] !== null ? (int) $l['audit_score'] : null) ?></td>
            <td><?= $l['rating'] ? '★ ' . e($l['rating']) . ' <span class="muted small">(' . (int) $l['reviews'] . ')</span>' : '—' ?></td>
            <td class="<?= $l['next_followup'] && $l['next_followup'] < date('Y-m-d') ? 'overdue' : '' ?>"><?= e($l['next_followup'] ?? '—') ?></td>
            <td class="nowrap">
              <?php if ($l['whatsapp']): ?><a class="chip wa" target="_blank" rel="noopener" href="https://wa.me/<?= e($l['whatsapp']) ?>">WA</a><?php endif; ?>
              <?php if ($l['email']): ?><a class="chip" href="mailto:<?= e($l['email']) ?>">Email</a><?php endif; ?>
              <?php if ($l['instagram']): ?><a class="chip ig" target="_blank" rel="noopener" href="<?= e(instagram_url($l['instagram'])) ?>">IG</a><?php endif; ?>
              <?php if ($l['website']): ?><a class="chip" target="_blank" rel="noopener" href="<?= e(safe_href($l['website'])) ?>">Web</a><?php endif; ?>
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
<?php require __DIR__ . '/footer.php'; ?>
