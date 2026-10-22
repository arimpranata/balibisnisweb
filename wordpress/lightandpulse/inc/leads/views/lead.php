<?php
defined('ABSPATH') || exit;
global $wpdb;
$lead = lpl_find_lead((int) lpl_get('lead'));
if (!$lead) {
    echo '<div class="card"><h2>Lead not found</h2><p><a href="' . esc_url(lpl_url('leads')) . '">Back to leads</a></p></div>';
    return;
}
$activities = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . lpl_table('activities') . ' WHERE lead_id = %d ORDER BY id DESC', $lead['id']), ARRAY_A);
$templates = $wpdb->get_results('SELECT * FROM ' . lpl_table('templates') . ' ORDER BY channel, name', ARRAY_A);
$audit = $lead['audit_json'] ? json_decode($lead['audit_json'], true) : null;
$svc = array_filter(explode(',', (string) $lead['services']));
$rendered = [];
foreach ($templates as $tpl) {
    $rendered[] = [
        'id' => (int) $tpl['id'], 'channel' => $tpl['channel'], 'name' => $tpl['name'],
        'subject' => lpl_render_template((string) $tpl['subject'], $lead),
        'body' => lpl_render_template($tpl['body'], $lead),
    ];
}
// Pre-select the most relevant opener.
$hint = $lead['source'] === 'website_form' ? 'enquiry' : ($lead['website'] === '' ? 'no website' : ($audit ? 'audit' : ''));
$defaultTpl = null;
foreach ($rendered as $r) {
    if ($hint !== '' && stripos($r['name'], $hint) !== false) {
        $defaultTpl = $r['id'];
        break;
    }
}
$followDays = (int) lpl_setting('followup_days');
$lid = (int) $lead['id'];
?>
<div class="lpl-head">
  <div>
    <a class="muted small" href="<?php echo esc_url(lpl_url('leads')); ?>">← All leads</a>
    <h1><?php echo esc_html($lead['name']); ?></h1>
    <p class="muted"><?php echo esc_html(trim($lead['category'] . ' · ' . $lead['area'], ' ·')); ?>
      <?php if ($lead['rating']) : ?> · ★ <?php echo esc_html($lead['rating']); ?> (<?php echo (int) $lead['reviews']; ?> reviews)<?php endif; ?></p>
  </div>
  <div class="lpl-actions">
    <?php echo lpl_form_open('lead_stage', ['class' => 'inline']); ?>
      <input type="hidden" name="id" value="<?php echo $lid; ?>">
      <select name="stage" data-autosubmit aria-label="Stage">
        <?php foreach (LPL_STAGES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($lead['stage'], $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?>
      </select>
    </form>
    <a class="btn" href="<?php echo esc_url(lpl_url('leads', ['edit' => $lid])); ?>">Edit</a>
  </div>
</div>

<div class="contact-bar">
  <?php if ($lead['whatsapp']) : ?><a class="btn btn-wa" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/' . $lead['whatsapp']); ?>">WhatsApp +<?php echo esc_html($lead['whatsapp']); ?></a><?php endif; ?>
  <?php if ($lead['phone']) : ?><a class="btn" href="<?php echo esc_url('tel:' . preg_replace('/[^\d+]/', '', $lead['phone'])); ?>">📞 <?php echo esc_html($lead['phone']); ?></a><?php endif; ?>
  <?php if ($lead['email']) : ?><a class="btn" href="<?php echo esc_url('mailto:' . $lead['email']); ?>">✉️ <?php echo esc_html($lead['email']); ?></a><?php endif; ?>
  <?php if ($lead['instagram']) : ?><a class="btn btn-ig" target="_blank" rel="noopener" href="<?php echo esc_url(lpl_instagram_url($lead['instagram'])); ?>">Instagram</a><?php endif; ?>
  <?php if ($lead['facebook']) : ?><a class="btn" target="_blank" rel="noopener" href="<?php echo esc_url($lead['facebook']); ?>">Facebook</a><?php endif; ?>
  <?php if ($lead['website']) : ?><a class="btn" target="_blank" rel="noopener" href="<?php echo esc_url($lead['website']); ?>">🌐 Website</a><?php endif; ?>
  <a class="btn" target="_blank" rel="noopener" href="<?php echo esc_url($lead['maps_url'] ?: 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lead['name'] . ' ' . $lead['area'] . ' Bali')); ?>">📍 Map</a>
  <?php if ($lead['source'] === 'openstreetmap') : ?><a class="btn" target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lead['name'] . ' ' . ($lead['area'] ?: 'Bali'))); ?>">⭐ Google reviews</a><?php endif; ?>
  <?php if (!$lead['instagram']) : ?><a class="btn btn-ghost" target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode('site:instagram.com "' . $lead['name'] . '" ' . ($lead['area'] ?: 'Bali'))); ?>">Find Instagram</a><?php endif; ?>
  <?php if (!$lead['email'] && $lead['website']) : ?><a class="btn btn-ghost" target="_blank" rel="noopener" href="<?php echo esc_url('https://www.google.com/search?q=' . rawurlencode('"@' . preg_replace('/^www\./', '', (string) wp_parse_url($lead['website'], PHP_URL_HOST)) . '"')); ?>">Find email</a><?php endif; ?>
</div>

<div class="detail">
  <div class="detail-main">

    <div class="card" id="connect">
      <h2>💬 Connect</h2>
      <?php if (!$lead['whatsapp'] && !$lead['email']) : ?>
        <p class="flash-warn">Add a WhatsApp number or email (Edit) to send messages from here. You can still copy the message for Instagram DM.</p>
      <?php endif; ?>
      <?php echo lpl_form_open('connect', ['target' => '_blank']); ?>
        <input type="hidden" name="id" value="<?php echo $lid; ?>">
        <label>Template
          <select id="tpl-select">
            <option value="">— Write your own —</option>
            <?php foreach ($rendered as $r) : ?>
              <option value="<?php echo (int) $r['id']; ?>" <?php selected($r['id'], $defaultTpl); ?>>[<?php echo $r['channel'] === 'email' ? 'Email' : 'WA'; ?>] <?php echo esc_html($r['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="subject-row">Email subject<input name="subject" id="msg-subject"></label>
        <label>Message<textarea name="message" id="msg-body" rows="10" required></textarea></label>
        <div class="btn-row">
          <button class="btn btn-wa" name="channel" value="whatsapp" <?php disabled(!$lead['whatsapp']); ?>>Send via WhatsApp</button>
          <button class="btn" name="channel" value="email" <?php disabled(!$lead['email']); ?>>Send via Email</button>
          <button class="btn btn-ghost" type="button" data-copy="#msg-body">Copy (for IG DM)</button>
        </div>
        <p class="muted small">Sending opens WhatsApp or your mail app in a new tab, logs the message here, moves the lead to “Contacted” and schedules a follow-up in <?php echo $followDays; ?> days. Reload this page afterwards to see the update.</p>
      </form>
      <script type="application/json" id="tpl-data"><?php echo wp_json_encode($rendered, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    </div>

    <div class="card">
      <div class="card-head">
        <h2>🩺 Website audit <?php echo lpl_score_badge($lead['audit_score']); ?></h2>
        <?php echo lpl_form_open('audit', ['class' => 'inline']); ?>
          <input type="hidden" name="id" value="<?php echo $lid; ?>">
          <button class="btn" data-loading="Checking website…"><?php echo $audit ? 'Re-run audit' : 'Run audit'; ?></button>
        </form>
      </div>
      <?php if (!$audit) : ?>
        <p class="muted">Check <?php echo $lead['website'] ? esc_html($lead['website']) : 'this business'; ?> for problems you can fix: HTTPS, mobile-friendliness, outdated WordPress, Wix/other platforms, missing WhatsApp button, SEO basics and speed. Findings flow into your audit templates as <code>{audit_findings}</code>.</p>
      <?php else : $sg = $audit['signals']; ?>
        <p class="muted small">Checked <?php echo esc_html($audit['checked']); ?><?php echo !empty($sg['final_url']) ? ' · ' . esc_html($sg['final_url']) : ''; ?></p>
        <?php if (isset($sg['platform'])) : ?>
          <div class="signals">
            <span class="sig">Platform: <b><?php echo esc_html($sg['platform'] . (!empty($sg['wp_version']) ? ' ' . $sg['wp_version'] : '')); ?></b></span>
            <?php foreach (['https' => 'HTTPS', 'viewport' => 'Mobile', 'elementor' => 'Elementor', 'woocommerce' => 'WooCommerce', 'whatsapp_link' => 'WA button', 'booking' => 'Booking', 'meta_description' => 'Meta desc', 'og_tags' => 'Social tags', 'schema' => 'Schema', 'analytics' => 'Analytics', 'languages' => 'Multi-language'] as $k => $label) : ?>
              <span class="sig <?php echo !empty($sg[$k]) ? 'yes' : 'no'; ?>"><?php echo !empty($sg[$k]) ? '✓' : '✗'; ?> <?php echo esc_html($label); ?></span>
            <?php endforeach; ?>
            <span class="sig">Response: <b><?php echo esc_html($sg['load_time']); ?>s</b></span>
            <span class="sig">HTML: <b><?php echo esc_html($sg['html_kb']); ?> KB</b></span>
          </div>
          <p class="small">Also run <a target="_blank" rel="noopener" href="<?php echo esc_url('https://pagespeed.web.dev/analysis?url=' . rawurlencode((string) ($sg['final_url'] ?? $lead['website']))); ?>">PageSpeed Insights</a> for a full performance report.</p>
        <?php endif; ?>
        <?php if ($audit['findings']) : ?>
          <ul class="findings">
            <?php foreach ($audit['findings'] as $fd) : ?>
              <li><span class="pts">+<?php echo (int) $fd['points']; ?></span> <?php echo esc_html($fd['text']); ?><?php if ($fd['service'] && isset(LPL_SERVICES[$fd['service']])) : ?> <span class="chip"><?php echo esc_html(LPL_SERVICES[$fd['service']]); ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else : ?>
          <p>✅ This site looks healthy. Pitch a care plan, SEO, or WooCommerce instead.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>📝 Activity</h2>
      <?php echo lpl_form_open('activity_add', ['class' => 'activity-form']); ?>
        <input type="hidden" name="id" value="<?php echo $lid; ?>">
        <div class="row">
          <select name="type" aria-label="Activity type"><?php foreach (LPL_ACTIVITY_TYPES as $k => $v) : if (in_array($k, ['stage', 'audit'], true)) { continue; } ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select>
          <label class="inline-label">Next follow-up <input type="date" name="next_followup" value="<?php echo esc_attr($lead['next_followup']); ?>"></label>
        </div>
        <textarea name="body" rows="3" placeholder="What happened? e.g. Spoke with owner, wants a quote for a villa site with booking…"></textarea>
        <button class="btn btn-primary">Save</button>
      </form>
      <ul class="timeline">
        <?php foreach ($activities as $a) : ?>
          <li class="t-<?php echo esc_attr($a['type']); ?>">
            <div class="t-head">
              <span class="t-type"><?php echo esc_html(LPL_ACTIVITY_TYPES[$a['type']] ?? $a['type']); ?></span>
              <time><?php echo esc_html($a['created_at']); ?></time>
              <?php echo lpl_form_open('activity_delete', ['class' => 'inline', 'data-confirm' => 'Delete this activity?']); ?>
                <input type="hidden" name="id" value="<?php echo $lid; ?>"><input type="hidden" name="aid" value="<?php echo (int) $a['id']; ?>">
                <button class="linkish small" aria-label="Delete activity">×</button>
              </form>
            </div>
            <div class="t-body"><?php echo nl2br(esc_html($a['body'])); ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <aside class="detail-side">
    <div class="card">
      <h2>Details</h2>
      <dl class="kv">
        <dt>Stage</dt><dd><?php echo lpl_stage_badge($lead['stage']); ?></dd>
        <dt>Priority</dt><dd><?php echo lpl_priority_badge($lead['priority']); ?></dd>
        <dt>Contact</dt><dd><?php echo esc_html($lead['contact_person'] ?: '—'); ?></dd>
        <dt>Deal value</dt><dd><?php echo $lead['est_value'] ? esc_html(lpl_rupiah((int) $lead['est_value'])) : '—'; ?></dd>
        <dt>Follow-up</dt><dd class="<?php echo $lead['next_followup'] && $lead['next_followup'] < lpl_today() ? 'overdue' : ''; ?>"><?php echo esc_html($lead['next_followup'] ?: '—'); ?></dd>
        <dt>Last contact</dt><dd><?php echo esc_html($lead['last_contacted'] ?: '—'); ?></dd>
        <dt>Source</dt><dd><?php echo esc_html(LPL_SOURCES[$lead['source']] ?? $lead['source']); ?></dd>
        <dt>Address</dt><dd><?php echo esc_html($lead['address'] ?: '—'); ?></dd>
        <dt>Tags</dt><dd><?php foreach (array_filter(array_map('trim', explode(',', (string) $lead['tags']))) as $tg) : ?><a class="chip" href="<?php echo esc_url(lpl_url('leads', ['q' => $tg])); ?>"><?php echo esc_html($tg); ?></a> <?php endforeach; ?></dd>
        <dt>Added</dt><dd><?php echo esc_html($lead['created_at']); ?></dd>
      </dl>
    </div>
    <div class="card">
      <h2>Services to pitch</h2>
      <?php if (!$svc) : ?><p class="muted small">Run the audit or edit the lead to pick services.</p><?php endif; ?>
      <ul class="svc"><?php foreach ($svc as $s) : ?><li><?php echo esc_html(LPL_SERVICES[$s] ?? $s); ?></li><?php endforeach; ?></ul>
    </div>
    <?php if ($lead['summary']) : ?>
      <div class="card"><h2>Notes</h2><p><?php echo nl2br(esc_html($lead['summary'])); ?></p></div>
    <?php endif; ?>
    <?php echo lpl_form_open('lead_delete', ['data-confirm' => 'Delete ' . $lead['name'] . ' and all its activity? This cannot be undone.']); ?>
      <input type="hidden" name="id" value="<?php echo $lid; ?>">
      <button class="btn btn-danger btn-block">Delete lead</button>
    </form>
  </aside>
</div>
