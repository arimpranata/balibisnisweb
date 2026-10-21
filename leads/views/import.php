<?php require __DIR__ . '/header.php'; ?>
<div class="page-head"><div><a class="muted small" href="<?= e(url('leads')) ?>">← Leads</a><h1>Import leads</h1></div></div>
<form method="post" enctype="multipart/form-data" action="<?= e(url('import')) ?>" class="card form">
  <?= csrf_field() ?>
  <p>Upload a CSV with a header row. Recognised columns:</p>
  <p><code>name</code> (required), <code>contact_person</code>, <code>category</code>, <code>area</code>, <code>address</code>, <code>phone</code>, <code>whatsapp</code>, <code>email</code>, <code>website</code>, <code>instagram</code>, <code>facebook</code>, <code>maps_url</code>, <code>tags</code>, <code>summary</code>, <code>est_value</code></p>
  <p class="muted small">Indonesian headers like <code>nama</code>, <code>telepon</code>, <code>kategori</code>, <code>lokasi</code>, <code>catatan</code> also work. Rows with the same name + area as an existing lead are skipped.</p>
  <div class="grid2">
    <label>CSV file<input type="file" name="csv" accept=".csv,text/csv" required></label>
    <label>Source<select name="source"><option value="import">Import</option><option value="instagram">Instagram</option><option value="referral">Referral</option><option value="walk_in">Walk-in / networking</option><option value="google_maps">Google Maps</option></select></label>
  </div>
  <div class="btn-row"><button class="btn btn-primary">Import</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
