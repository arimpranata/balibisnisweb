<?php
require __DIR__ . '/header.php';
$svc = array_filter(explode(',', (string) $lead['services']));
$rendered = [];
foreach ($templates as $t) {
    $rendered[] = [
        'id' => (int) $t['id'], 'channel' => $t['channel'], 'name' => $t['name'],
        'subject' => render_template((string) $t['subject'], $lead),
        'body' => render_template($t['body'], $lead),
    ];
}
// Pre-select the most relevant opener: "no website" pitch, or audit findings once audited
$hint = $lead['website'] === '' ? 'no website' : ($audit ? 'audit' : '');
$defaultTpl = null;
foreach ($rendered as $r) {
    if ($hint !== '' && stripos($r['name'], $hint) !== false) {
        $defaultTpl = $r['id'];
        break;
    }
}
?>
<div class="page-head">
  <div>
    <a class="muted small" href="<?= e(url('leads')) ?>">← All leads</a>
    <h1><?= e($lead['name']) ?></h1>
    <p class="muted"><?= e(trim($lead['category'] . ' · ' . $lead['area'], ' ·')) ?>
      <?php if ($lead['rating']): ?> · ★ <?= e($lead['rating']) ?> (<?= (int) $lead['reviews'] ?> reviews)<?php endif; ?></p>
  </div>
  <div class="actions">
    <form method="post" action="<?= e(url('lead_stage')) ?>" class="inline">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
      <select name="stage" onchange="this.form.submit()" aria-label="Stage">
        <?php foreach (STAGES as $k => $v): ?><option value="<?= e($k) ?>" <?= $lead['stage'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    </form>
    <a class="btn" href="<?= e(url('lead_edit', ['id' => $lead['id']])) ?>">Edit</a>
  </div>
</div>

<div class="contact-bar">
  <?php if ($lead['whatsapp']): ?><a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/<?= e($lead['whatsapp']) ?>">WhatsApp +<?= e($lead['whatsapp']) ?></a><?php endif; ?>
  <?php if ($lead['phone']): ?><a class="btn" href="tel:<?= e(preg_replace('/[^\d+]/', '', $lead['phone'])) ?>">📞 <?= e($lead['phone']) ?></a><?php endif; ?>
  <?php if ($lead['email']): ?><a class="btn" href="mailto:<?= e($lead['email']) ?>">✉️ <?= e($lead['email']) ?></a><?php endif; ?>
  <?php if ($lead['instagram']): ?><a class="btn btn-ig" target="_blank" rel="noopener" href="<?= e(instagram_url($lead['instagram'])) ?>">Instagram</a><?php endif; ?>
  <?php if ($lead['facebook']): ?><a class="btn" target="_blank" rel="noopener" href="<?= e(safe_href($lead['facebook'])) ?>">Facebook</a><?php endif; ?>
  <?php if ($lead['website']): ?><a class="btn" target="_blank" rel="noopener" href="<?= e(safe_href($lead['website'])) ?>">🌐 Website</a><?php endif; ?>
  <a class="btn" target="_blank" rel="noopener" href="<?= e($lead['maps_url'] ? safe_href($lead['maps_url']) : 'https://www.google.com/maps/search/' . rawurlencode($lead['name'] . ' ' . $lead['area'] . ' Bali')) ?>">📍 Maps</a>
  <?php if ($lead['source'] === 'openstreetmap'): ?><a class="btn" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($lead['name'] . ' ' . ($lead['area'] ?: 'Bali'))) ?>">⭐ Google reviews</a><?php endif; ?>
  <?php if (!$lead['instagram']): ?><a class="btn btn-ghost" target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode('site:instagram.com "' . $lead['name'] . '" ' . ($lead['area'] ?: 'Bali'))) ?>">Find Instagram</a><?php endif; ?>
  <?php if (!$lead['email'] && $lead['website']): ?><a class="btn btn-ghost" target="_blank" rel="noopener" href="https://www.google.com/search?q=<?= e(rawurlencode('"@' . preg_replace('/^www\./', '', (string) parse_url($lead['website'], PHP_URL_HOST)) . '"')) ?>">Find email</a><?php endif; ?>
</div>

<div class="detail">
  <div class="detail-main">

    <!-- CONNECT -->
    <div class="card" id="connect">
      <h2>💬 Connect</h2>
      <?php if (!$lead['whatsapp'] && !$lead['email']): ?>
        <p class="flash flash-warn">Add a WhatsApp number or email (Edit) to send messages from here. You can still copy the message for Instagram DM.</p>
      <?php endif; ?>
      <form method="post" action="<?= e(url('connect')) ?>" target="_blank" id="composer">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
        <label>Template
          <select id="tpl-select">
            <option value="">— Write your own —</option>
            <?php foreach ($rendered as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $r['id'] === $defaultTpl ? 'selected' : '' ?>>[<?= $r['channel'] === 'email' ? 'Email' : 'WA' ?>] <?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="subject-row">Email subject<input name="subject" id="msg-subject"></label>
        <label>Message<textarea name="message" id="msg-body" rows="10" required></textarea></label>
        <div class="btn-row">
          <button class="btn btn-wa" name="channel" value="whatsapp" <?= $lead['whatsapp'] ? '' : 'disabled' ?>>Send via WhatsApp</button>
          <button class="btn" name="channel" value="email" <?= $lead['email'] ? '' : 'disabled' ?>>Send via Email</button>
          <button class="btn btn-ghost" type="button" data-copy="#msg-body">Copy (for IG DM)</button>
        </div>
        <p class="muted small">Sending opens WhatsApp/your mail app, logs the message here, moves the lead to “Contacted” and schedules a follow-up in <?= (int) setting('followup_days', '3') ?> days.</p>
      </form>
      <script type="application/json" id="tpl-data"><?= json_encode($rendered, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    </div>

    <!-- AUDIT -->
    <div class="card">
      <div class="card-head">
        <h2>🩺 Website audit <?= score_badge($lead['audit_score'] !== null ? (int) $lead['audit_score'] : null) ?></h2>
        <form method="post" action="<?= e(url('audit')) ?>" class="inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
          <button class="btn" data-loading="Checking website…"><?= $audit ? 'Re-run audit' : 'Run audit' ?></button>
        </form>
      </div>
      <?php if (!$audit): ?>
        <p class="muted">Check <?= $lead['website'] ? e($lead['website']) : 'this business' ?> for problems you can fix: HTTPS, mobile-friendliness, outdated WordPress, Wix/other platforms, missing WhatsApp button, SEO basics and speed. Findings flow into your audit message templates as <code>{audit_findings}</code>.</p>
      <?php else: $sg = $audit['signals']; ?>
        <p class="muted small">Checked <?= e($audit['checked']) ?><?= !empty($sg['final_url']) ? ' · ' . e($sg['final_url']) : '' ?></p>
        <?php if (isset($sg['platform'])): ?>
          <div class="signals">
            <span class="sig">Platform: <b><?= e($sg['platform']) ?><?= !empty($sg['wp_version']) ? ' ' . e($sg['wp_version']) : '' ?></b></span>
            <?php foreach (['https' => 'HTTPS', 'viewport' => 'Mobile', 'elementor' => 'Elementor', 'woocommerce' => 'WooCommerce', 'whatsapp_link' => 'WA button', 'booking' => 'Booking', 'meta_description' => 'Meta desc', 'og_tags' => 'Social tags', 'schema' => 'Schema', 'analytics' => 'Analytics', 'languages' => 'Multi-language'] as $k => $label): ?>
              <span class="sig <?= !empty($sg[$k]) ? 'yes' : 'no' ?>"><?= !empty($sg[$k]) ? '✓' : '✗' ?> <?= e($label) ?></span>
            <?php endforeach; ?>
            <span class="sig">Response: <b><?= e($sg['load_time']) ?>s</b></span>
            <span class="sig">HTML: <b><?= e($sg['html_kb']) ?> KB</b></span>
          </div>
          <p class="small">Also run <a target="_blank" rel="noopener" href="https://pagespeed.web.dev/analysis?url=<?= e(rawurlencode((string) ($sg['final_url'] ?? $lead['website']))) ?>">PageSpeed Insights</a> for a full performance report.</p>
        <?php endif; ?>
        <?php if ($audit['findings']): ?>
          <ul class="findings">
            <?php foreach ($audit['findings'] as $fd): ?>
              <li><span class="pts">+<?= (int) $fd['points'] ?></span> <?= e($fd['text']) ?><?php if ($fd['service'] && isset(SERVICES[$fd['service']])): ?> <span class="chip"><?= e(SERVICES[$fd['service']]) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>✅ This site looks healthy. Pitch a care plan, SEO, or WooCommerce instead.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- ACTIVITY -->
    <div class="card">
      <h2>📝 Activity</h2>
      <form method="post" action="<?= e(url('activity_add')) ?>" class="activity-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
        <div class="row">
          <select name="type"><?php foreach (ACTIVITY_TYPES as $k => $v): if (in_array($k, ['stage', 'audit'], true)) continue; ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select>
          <label class="inline-label">Next follow-up <input type="date" name="next_followup" value="<?= e($lead['next_followup']) ?>"></label>
        </div>
        <textarea name="body" rows="3" placeholder="What happened? e.g. Spoke with owner, wants a quote for a villa site with booking…"></textarea>
        <button class="btn btn-primary">Save</button>
      </form>
      <ul class="timeline">
        <?php foreach ($activities as $a): ?>
          <li class="t-<?= e($a['type']) ?>">
            <div class="t-head">
              <span class="t-type"><?= e(ACTIVITY_TYPES[$a['type']] ?? $a['type']) ?></span>
              <time><?= e($a['created_at']) ?></time>
              <form method="post" action="<?= e(url('activity_delete')) ?>" class="inline" data-confirm="Delete this activity?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>"><input type="hidden" name="aid" value="<?= (int) $a['id'] ?>">
                <button class="linkish small" aria-label="Delete">×</button>
              </form>
            </div>
            <div class="t-body"><?= nl2br(e($a['body'])) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <aside class="detail-side">
    <div class="card">
      <h2>Details</h2>
      <dl class="kv">
        <dt>Stage</dt><dd><?= stage_badge($lead['stage']) ?></dd>
        <dt>Priority</dt><dd><?= priority_badge($lead['priority']) ?></dd>
        <dt>Contact</dt><dd><?= e($lead['contact_person'] ?: '—') ?></dd>
        <dt>Deal value</dt><dd><?= $lead['est_value'] ? e(rupiah((int) $lead['est_value'])) : '—' ?></dd>
        <dt>Follow-up</dt><dd class="<?= $lead['next_followup'] && $lead['next_followup'] < date('Y-m-d') ? 'overdue' : '' ?>"><?= e($lead['next_followup'] ?? '—') ?></dd>
        <dt>Last contact</dt><dd><?= e($lead['last_contacted'] ?? '—') ?></dd>
        <dt>Source</dt><dd><?= e(str_replace('_', ' ', $lead['source'])) ?></dd>
        <dt>Address</dt><dd><?= e($lead['address'] ?: '—') ?></dd>
        <dt>Tags</dt><dd><?php foreach (array_filter(array_map('trim', explode(',', (string) $lead['tags']))) as $tg): ?><a class="chip" href="<?= e(url('leads', ['q' => $tg])) ?>"><?= e($tg) ?></a> <?php endforeach; ?></dd>
        <dt>Added</dt><dd><?= e($lead['created_at']) ?></dd>
      </dl>
    </div>
    <div class="card">
      <h2>Services to pitch</h2>
      <?php if (!$svc): ?><p class="muted small">Run the audit or edit the lead to pick services.</p><?php endif; ?>
      <ul class="svc">
        <?php foreach ($svc as $s): ?><li><?= e(SERVICES[$s] ?? $s) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php if ($lead['summary']): ?>
      <div class="card"><h2>Notes</h2><p><?= nl2br(e($lead['summary'])) ?></p></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('lead_delete')) ?>" data-confirm="Delete <?= e($lead['name']) ?> and all its activity? This cannot be undone.">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
      <button class="btn btn-danger btn-block">Delete lead</button>
    </form>
  </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>
