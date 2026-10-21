<?php
require __DIR__ . '/header.php';
$l = $lead ?? ['id' => 0, 'stage' => 'new', 'priority' => 'warm', 'source' => 'manual', 'services' => ''];
$v = fn($k) => e($l[$k] ?? '');
$svc = array_filter(explode(',', (string) ($l['services'] ?? '')));
?>
<div class="page-head">
  <div>
    <a class="muted small" href="<?= e($lead ? url('lead', ['id' => $lead['id']]) : url('leads')) ?>">← Back</a>
    <h1><?= $lead ? 'Edit lead' : 'Add lead' ?></h1>
  </div>
</div>
<form method="post" action="<?= e(url('lead_save')) ?>" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
  <h2>Business</h2>
  <div class="grid3">
    <label>Business name *<input name="name" value="<?= $v('name') ?>" required></label>
    <label>Category<input name="category" value="<?= $v('category') ?>" list="cat-list" placeholder="Villa, Cafe, Spa…"></label>
    <label>Area<input name="area" value="<?= $v('area') ?>" list="area-list" placeholder="Canggu, Ubud…"></label>
  </div>
  <label>Address<input name="address" value="<?= $v('address') ?>"></label>

  <h2>Contact</h2>
  <div class="grid3">
    <label>Contact person<input name="contact_person" value="<?= $v('contact_person') ?>" placeholder="Owner / manager name"></label>
    <label>Phone<input name="phone" value="<?= $v('phone') ?>" placeholder="0361… / 0812…"></label>
    <label>WhatsApp<input name="whatsapp" value="<?= $v('whatsapp') ?>" placeholder="Auto from phone if empty"></label>
    <label>Email<input type="email" name="email" value="<?= $v('email') ?>"></label>
    <label>Website<input name="website" value="<?= $v('website') ?>" placeholder="example.com"></label>
    <label>Instagram<input name="instagram" value="<?= $v('instagram') ?>" placeholder="@handle"></label>
    <label>Facebook URL<input name="facebook" value="<?= $v('facebook') ?>"></label>
    <label>Google Maps URL<input name="maps_url" value="<?= $v('maps_url') ?>"></label>
  </div>

  <h2>Deal</h2>
  <div class="grid3">
    <label>Stage<select name="stage"><?php foreach (STAGES as $k => $s): ?><option value="<?= e($k) ?>" <?= $l['stage'] === $k ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
    <label>Priority<select name="priority"><?php foreach (PRIORITIES as $k => $s): ?><option value="<?= e($k) ?>" <?= $l['priority'] === $k ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
    <label>Estimated value (IDR)<input name="est_value" inputmode="numeric" value="<?= !empty($l['est_value']) ? e(number_format((int) $l['est_value'], 0, ',', '.')) : '' ?>" placeholder="e.g. 7.500.000"></label>
    <label>Next follow-up<input type="date" name="next_followup" value="<?= $v('next_followup') ?>"></label>
    <label>Source<select name="source"><?php foreach (['manual' => 'Manual', 'google_maps' => 'Google Maps', 'instagram' => 'Instagram', 'referral' => 'Referral', 'walk_in' => 'Walk-in / networking', 'import' => 'Import', 'inbound' => 'Inbound (they contacted me)'] as $k => $s): ?><option value="<?= e($k) ?>" <?= $l['source'] === $k ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
    <label>Tags<input name="tags" value="<?= $v('tags') ?>" placeholder="comma, separated"></label>
  </div>
  <fieldset class="checks">
    <legend>Services to pitch</legend>
    <?php foreach (SERVICES as $k => $s): ?>
      <label class="check"><input type="checkbox" name="services[]" value="<?= e($k) ?>" <?= in_array($k, $svc, true) ? 'checked' : '' ?>> <?= e($s) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <label>Notes<textarea name="summary" rows="4" placeholder="Owner's needs, budget, decision maker, competitors…"><?= $v('summary') ?></textarea></label>
  <div class="btn-row"><button class="btn btn-primary">Save lead</button></div>
</form>
<datalist id="cat-list"><?php foreach (BALI_CATEGORIES as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
<datalist id="area-list"><?php foreach (BALI_AREAS as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>
<?php require __DIR__ . '/footer.php'; ?>
