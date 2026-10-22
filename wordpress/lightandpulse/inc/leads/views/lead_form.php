<?php
defined('ABSPATH') || exit;
$lead = ctype_digit(lpl_get('edit')) ? lpl_find_lead((int) lpl_get('edit')) : null;
$l = $lead ?? ['id' => 0, 'stage' => 'new', 'priority' => 'warm', 'source' => 'manual', 'services' => ''];
$v = fn($k) => esc_attr($l[$k] ?? '');
$svc = array_filter(explode(',', (string) ($l['services'] ?? '')));
?>
<div class="lpl-head">
  <div>
    <a class="muted small" href="<?php echo esc_url($lead ? lpl_url('leads', ['lead' => $lead['id']]) : lpl_url('leads')); ?>">← Back</a>
    <h1><?php echo $lead ? 'Edit lead' : 'Add lead'; ?></h1>
  </div>
</div>
<?php echo lpl_form_open('lead_save', ['class' => 'card form']); ?>
  <input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
  <h2>Business</h2>
  <div class="grid3">
    <label>Business name *<input name="name" value="<?php echo $v('name'); ?>" required></label>
    <label>Category<input name="category" value="<?php echo $v('category'); ?>" list="cat-list" placeholder="Villa, Cafe, Spa…"></label>
    <label>Area<input name="area" value="<?php echo $v('area'); ?>" list="area-list" placeholder="Canggu, Ubud…"></label>
  </div>
  <label>Address<input name="address" value="<?php echo $v('address'); ?>"></label>

  <h2>Contact</h2>
  <div class="grid3">
    <label>Contact person<input name="contact_person" value="<?php echo $v('contact_person'); ?>" placeholder="Owner / manager name"></label>
    <label>Phone<input name="phone" value="<?php echo $v('phone'); ?>" placeholder="0361… / 0812…"></label>
    <label>WhatsApp<input name="whatsapp" value="<?php echo $v('whatsapp'); ?>" placeholder="Auto from phone if empty"></label>
    <label>Email<input type="email" name="email" value="<?php echo $v('email'); ?>"></label>
    <label>Website<input name="website" value="<?php echo $v('website'); ?>" placeholder="example.com"></label>
    <label>Instagram<input name="instagram" value="<?php echo $v('instagram'); ?>" placeholder="@handle"></label>
    <label>Facebook URL<input name="facebook" value="<?php echo $v('facebook'); ?>"></label>
    <label>Map URL<input name="maps_url" value="<?php echo $v('maps_url'); ?>"></label>
  </div>

  <h2>Deal</h2>
  <div class="grid3">
    <label>Stage<select name="stage"><?php foreach (LPL_STAGES as $k => $s) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($l['stage'], $k); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></label>
    <label>Priority<select name="priority"><?php foreach (LPL_PRIORITIES as $k => $s) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($l['priority'], $k); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></label>
    <label>Estimated value (IDR)<input name="est_value" inputmode="numeric" value="<?php echo !empty($l['est_value']) ? esc_attr(number_format((int) $l['est_value'], 0, ',', '.')) : ''; ?>" placeholder="e.g. 7.500.000"></label>
    <label>Next follow-up<input type="date" name="next_followup" value="<?php echo $v('next_followup'); ?>"></label>
    <label>Source<select name="source"><?php foreach (LPL_SOURCES as $k => $s) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($l['source'], $k); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></label>
    <label>Tags<input name="tags" value="<?php echo $v('tags'); ?>" placeholder="comma, separated"></label>
  </div>
  <fieldset class="checks">
    <legend>Services to pitch</legend>
    <?php foreach (LPL_SERVICES as $k => $s) : ?>
      <label class="check"><input type="checkbox" name="services[]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, $svc, true)); ?>> <?php echo esc_html($s); ?></label>
    <?php endforeach; ?>
  </fieldset>
  <label>Notes<textarea name="summary" rows="4" placeholder="Owner's needs, budget, decision maker, competitors…"><?php echo esc_textarea($l['summary'] ?? ''); ?></textarea></label>
  <div class="btn-row"><button class="btn btn-primary">Save lead</button></div>
</form>
<datalist id="cat-list"><?php foreach (LPL_BALI_CATEGORIES as $c) : ?><option value="<?php echo esc_attr($c); ?>"><?php endforeach; ?></datalist>
<datalist id="area-list"><?php foreach (LPL_BALI_AREAS as $a) : ?><option value="<?php echo esc_attr($a); ?>"><?php endforeach; ?></datalist>
