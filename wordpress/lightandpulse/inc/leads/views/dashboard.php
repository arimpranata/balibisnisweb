<?php
defined('ABSPATH') || exit;
global $wpdb;
$t = lpl_table('leads');
$today = lpl_today();
$stats = [
    'total'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $t"),
    'active'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE stage NOT IN ('won','lost')"),
    'won'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE stage = 'won'"),
    'pipeline'  => (int) $wpdb->get_var("SELECT COALESCE(SUM(est_value),0) FROM $t WHERE stage IN ('replied','proposal','negotiation')"),
    'won_value' => (int) $wpdb->get_var("SELECT COALESCE(SUM(est_value),0) FROM $t WHERE stage = 'won'"),
];
$byStage = [];
foreach ($wpdb->get_results("SELECT stage, COUNT(*) c FROM $t GROUP BY stage", ARRAY_A) as $r) {
    $byStage[$r['stage']] = (int) $r['c'];
}
$due = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE next_followup IS NOT NULL AND next_followup <= %s AND stage NOT IN ('won','lost') ORDER BY next_followup LIMIT 15", $today), ARRAY_A);
$hot = $wpdb->get_results("SELECT * FROM $t WHERE stage IN ('new','researched') ORDER BY (priority='hot') DESC, COALESCE(audit_score, 50) DESC, COALESCE(reviews, 0) DESC LIMIT 8", ARRAY_A);
$recent = $wpdb->get_results('SELECT a.*, l.name FROM ' . lpl_table('activities') . " a JOIN $t l ON l.id = a.lead_id ORDER BY a.id DESC LIMIT 10", ARRAY_A);
?>
<div class="lpl-head">
  <div>
    <h1>Om Swastiastu 👋</h1>
    <p class="muted"><?php echo esc_html(wp_date('l, j F Y')); ?> · <?php echo count($due); ?> follow-up(s) due</p>
  </div>
  <div class="lpl-actions">
    <a class="btn btn-primary" href="<?php echo esc_url(lpl_url('finder')); ?>">🔎 Find new clients</a>
    <a class="btn" href="<?php echo esc_url(lpl_url('leads', ['edit' => 'new'])); ?>">+ Add lead</a>
  </div>
</div>

<div class="stats">
  <div class="stat"><span>Total leads</span><b><?php echo (int) $stats['total']; ?></b></div>
  <div class="stat"><span>Active</span><b><?php echo (int) $stats['active']; ?></b></div>
  <div class="stat"><span>Open pipeline</span><b><?php echo esc_html(lpl_rupiah($stats['pipeline'])); ?></b><small>replied → negotiation</small></div>
  <div class="stat accent"><span>Won</span><b><?php echo (int) $stats['won']; ?></b><small><?php echo esc_html(lpl_rupiah($stats['won_value'])); ?></small></div>
</div>

<div class="card">
  <h2>Pipeline</h2>
  <div class="funnel">
    <?php $max = max(1, ...array_values($byStage ?: [0])); foreach (LPL_STAGES as $k => $label) : $c = (int) ($byStage[$k] ?? 0); ?>
      <a class="funnel-row" href="<?php echo esc_url(lpl_url('leads', ['stage' => $k])); ?>">
        <span class="funnel-label"><?php echo esc_html($label); ?></span>
        <span class="funnel-bar"><i class="stage-bg-<?php echo esc_attr($k); ?>" style="width: <?php echo (int) max(2, round($c / $max * 100)); ?>%"></i></span>
        <span class="funnel-num"><?php echo $c; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <h2>⏰ Follow-ups due</h2>
    <?php if (!$due) : ?>
      <p class="muted">Nothing due. Great time to find new clients!</p>
    <?php else : ?>
      <ul class="list">
        <?php foreach ($due as $l) : ?>
          <li>
            <a href="<?php echo esc_url(lpl_url('leads', ['lead' => $l['id']])); ?>"><b><?php echo esc_html($l['name']); ?></b></a>
            <?php echo lpl_stage_badge($l['stage']); ?>
            <span class="muted small <?php echo $l['next_followup'] < $today ? 'overdue' : ''; ?>"><?php echo esc_html($l['next_followup']); ?></span>
            <?php if ($l['whatsapp']) : ?><a class="chip wa" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/' . $l['whatsapp']); ?>">WhatsApp</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>🔥 Best uncontacted prospects</h2>
    <?php if (!$hot) : ?>
      <p class="muted">No new leads yet. <a href="<?php echo esc_url(lpl_url('finder')); ?>">Search for businesses</a> or <a href="<?php echo esc_url(lpl_url('import')); ?>">import a CSV</a>.</p>
    <?php else : ?>
      <ul class="list">
        <?php foreach ($hot as $l) : ?>
          <li>
            <?php echo lpl_score_badge($l['audit_score']); ?>
            <a href="<?php echo esc_url(lpl_url('leads', ['lead' => $l['id']])); ?>"><b><?php echo esc_html($l['name']); ?></b></a>
            <span class="muted small"><?php echo esc_html(trim($l['category'] . ' · ' . $l['area'], ' ·')); ?></span>
            <?php echo lpl_priority_badge($l['priority']); ?>
            <?php if (!$l['website']) : ?><span class="chip warn">no website</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Recent activity</h2>
  <?php if (!$recent) : ?><p class="muted">No activity yet.</p><?php else : ?>
  <ul class="timeline compact">
    <?php foreach ($recent as $a) : ?>
      <li class="t-<?php echo esc_attr($a['type']); ?>">
        <span class="t-type"><?php echo esc_html(LPL_ACTIVITY_TYPES[$a['type']] ?? $a['type']); ?></span>
        <a href="<?php echo esc_url(lpl_url('leads', ['lead' => $a['lead_id']])); ?>"><?php echo esc_html($a['name']); ?></a>
        <span class="muted small"><?php echo esc_html(mb_strimwidth(str_replace("\n", ' ', $a['body']), 0, 90, '…')); ?></span>
        <time><?php echo esc_html($a['created_at']); ?></time>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
