<?php defined('ABSPATH') || exit; ?>
<div class="lpl-head"><div><h1>Import / export</h1></div></div>
<div class="grid2">
  <?php echo lpl_form_open('import', ['class' => 'card form', 'enctype' => 'multipart/form-data']); ?>
    <h2>Import leads from CSV</h2>
    <p class="small">Needs a header row. Recognised columns: <code>name</code> (required), <code>contact_person</code>, <code>category</code>, <code>area</code>, <code>address</code>, <code>phone</code>, <code>whatsapp</code>, <code>email</code>, <code>website</code>, <code>instagram</code>, <code>facebook</code>, <code>maps_url</code>, <code>tags</code>, <code>summary</code>, <code>est_value</code>.</p>
    <p class="muted small">Indonesian headers like <code>nama</code>, <code>telepon</code>, <code>kategori</code>, <code>lokasi</code>, <code>catatan</code> also work. Rows with the same name and area as an existing lead are skipped.</p>
    <label>CSV file<input type="file" name="csv" accept=".csv,text/csv" required></label>
    <label>Source<select name="source"><?php foreach (LPL_SOURCES as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($k, 'import'); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select></label>
    <div class="btn-row"><button class="btn btn-primary">Import</button></div>
  </form>
  <?php echo lpl_form_open('export', ['class' => 'card form']); ?>
    <h2>Export leads</h2>
    <p class="small">Download every lead as a CSV file you can open in Excel or Google Sheets, or keep as a backup.</p>
    <div class="btn-row"><button class="btn">Download CSV</button></div>
  </form>
</div>
