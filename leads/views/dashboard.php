<?php require __DIR__ . '/header.php'; ?>
<div class="page-head">
  <div>
    <h1>Om Swastiastu, <?php $nm = explode(' ', (string) profile()['my_name']); echo e(stripos((string) profile()['my_name'], 'Ida Bagus ') === 0 && isset($nm[2]) ? 'Gus ' . $nm[2] : $nm[0]); ?> 👋</h1>
    <p class="muted"><?= e(date('l, j F Y')) ?> · <?= count($due) ?> follow-up(s) due today</p>
  </div>
  <div class="actions">
    <a class="btn btn-primary" href="<?= e(url('finder')) ?>">🔎 Find new clients</a>
    <a class="btn" href="<?= e(url('lead_edit')) ?>">+ Add lead</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><span>Total leads</span><b><?= $stats['total'] ?></b></div>
  <div class="stat"><span>Active</span><b><?= $stats['active'] ?></b></div>
  <div class="stat"><span>Open pipeline</span><b><?= e(rupiah($stats['pipeline'])) ?></b><small>replied → negotiation</small></div>
  <div class="stat accent"><span>Won</span><b><?= $stats['won'] ?></b><small><?= e(rupiah($stats['won_value'])) ?></small></div>
</div>

<div class="card">
  <h2>Pipeline</h2>
  <div class="funnel">
    <?php $max = max(1, ...array_values($byStage ?: [0])); foreach (STAGES as $k => $label): $c = (int) ($byStage[$k] ?? 0); ?>
      <a class="funnel-row" href="<?= e(url('leads', ['stage' => $k])) ?>">
        <span class="funnel-label"><?= e($label) ?></span>
        <span class="funnel-bar"><i class="stage-bg-<?= e($k) ?>" style="width: <?= max(2, round($c / $max * 100)) ?>%"></i></span>
        <span class="funnel-num"><?= $c ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <h2>⏰ Follow-ups due</h2>
    <?php if (!$due): ?>
      <p class="muted">Nothing due. Great time to find new clients!</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($due as $l): ?>
          <li>
            <a href="<?= e(url('lead', ['id' => $l['id']])) ?>"><b><?= e($l['name']) ?></b></a>
            <?= stage_badge($l['stage']) ?>
            <span class="muted small <?= $l['next_followup'] < date('Y-m-d') ? 'overdue' : '' ?>"><?= e($l['next_followup']) ?></span>
            <?php if ($l['whatsapp']): ?><a class="chip wa" target="_blank" rel="noopener" href="https://wa.me/<?= e($l['whatsapp']) ?>">WhatsApp</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>🔥 Best uncontacted prospects</h2>
    <?php if (!$hot): ?>
      <p class="muted">No new leads yet. <a href="<?= e(url('finder')) ?>">Search Google Maps</a> or <a href="<?= e(url('import')) ?>">import a CSV</a>.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($hot as $l): ?>
          <li>
            <?= score_badge($l['audit_score'] !== null ? (int) $l['audit_score'] : null) ?>
            <a href="<?= e(url('lead', ['id' => $l['id']])) ?>"><b><?= e($l['name']) ?></b></a>
            <span class="muted small"><?= e(trim($l['category'] . ' · ' . $l['area'], ' ·')) ?></span>
            <?= priority_badge($l['priority']) ?>
            <?php if (!$l['website']): ?><span class="chip warn">no website</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Recent activity</h2>
  <?php if (!$recent): ?><p class="muted">No activity yet.</p><?php else: ?>
  <ul class="timeline compact">
    <?php foreach ($recent as $a): ?>
      <li class="t-<?= e($a['type']) ?>">
        <span class="t-type"><?= e(ACTIVITY_TYPES[$a['type']] ?? $a['type']) ?></span>
        <a href="<?= e(url('lead', ['id' => $a['lead_id']])) ?>"><?= e($a['name']) ?></a>
        <span class="muted small"><?= e(mb_strimwidth(str_replace("\n", ' ', $a['body']), 0, 90, '…')) ?></span>
        <time><?= e($a['created_at']) ?></time>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
