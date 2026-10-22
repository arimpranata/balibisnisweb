<?php require __DIR__ . '/header.php'; $pf = profile(); $hasKey = (string) setting('places_api_key', '') !== ''; ?>
<div class="page-head"><div><h1>Settings</h1></div></div>
<form method="post" action="<?= e(url('settings')) ?>" class="form">
  <?= csrf_field() ?>
  <div class="card">
    <h2>Your profile</h2>
    <p class="muted small">Used in message templates.</p>
    <div class="grid2">
      <label>Name used in messages<input name="my_name" value="<?= e($pf['my_name']) ?>"></label>
      <label>Agency<input name="my_agency" value="<?= e($pf['my_agency']) ?>"></label>
      <label>Website<input name="my_website" value="<?= e($pf['my_website']) ?>"></label>
      <label>WhatsApp / phone<input name="my_phone" value="<?= e($pf['my_phone']) ?>"></label>
      <label>Email<input type="email" name="my_email" value="<?= e($pf['my_email']) ?>"></label>
      <label>Default follow-up after outreach (days)<input type="number" min="1" max="30" name="followup_days" value="<?= (int) setting('followup_days', '3') ?>"></label>
    </div>
  </div>
  <div class="card">
    <h2>OpenStreetMap search (free)</h2>
    <p class="small">Works with no account or key. If the main server is busy, switch to a mirror such as <code>https://overpass.kumi.systems/api/interpreter</code>.</p>
    <label>Overpass server<input name="osm_endpoint" value="<?= e(setting('osm_endpoint', OSM_DEFAULT_ENDPOINT)) ?>"></label>
  </div>
  <div class="card">
    <h2>Google Maps search (optional, paid after free credit)</h2>
    <p class="small">Status: <?= $hasKey ? '<span class="badge stage-won">Connected</span>' : '<span class="badge prio-cold">Not connected</span>' ?></p>
    <ol class="small">
      <li>Open <a target="_blank" rel="noopener" href="https://console.cloud.google.com/apis/library/places.googleapis.com">Google Cloud Console → Places API (New)</a> and enable it.</li>
      <li>Create an API key under <i>APIs &amp; Services → Credentials</i>.</li>
      <li>Restrict the key to <b>Places API (New)</b> and, ideally, to your server's IP address.</li>
      <li>Set a daily quota cap to avoid surprise charges.</li>
    </ol>
    <label>API key<input type="password" name="places_api_key" placeholder="<?= $hasKey ? '•••••••• (saved — leave blank to keep)' : 'AIza…' ?>" autocomplete="off"></label>
    <?php if ($hasKey): ?><label class="check"><input type="checkbox" name="remove_api_key" value="1"> Remove saved key</label><?php endif; ?>
  </div>
  <div class="card">
    <h2>Change password</h2>
    <div class="grid2">
      <label>Current password<input type="password" name="current_password" autocomplete="current-password"></label>
      <label>New password (min. 10)<input type="password" name="new_password" autocomplete="new-password"></label>
    </div>
  </div>
  <div class="card">
    <h2>Backup</h2>
    <p class="small">All data lives in <code>leads/data/leads.sqlite</code>. Download a copy regularly, or <a href="<?= e(url('export')) ?>">export leads as CSV</a>.</p>
  </div>
  <div class="btn-row"><button class="btn btn-primary">Save settings</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
