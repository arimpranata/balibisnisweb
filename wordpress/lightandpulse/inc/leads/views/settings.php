<?php
defined('ABSPATH') || exit;
$s = lpl_settings();
$hasKey = (string) $s['places_api_key'] !== '';
?>
<div class="lpl-head"><div><h1>Leads settings</h1></div></div>
<?php echo lpl_form_open('settings_save', ['class' => 'form']); ?>
  <div class="card">
    <h2>Your profile</h2>
    <p class="muted small">Used in message templates, and on the website's Contact page, footer and WhatsApp button.</p>
    <div class="grid2">
      <label>Name used in messages<input name="my_name" value="<?php echo esc_attr($s['my_name']); ?>"></label>
      <label>Agency<input name="my_agency" value="<?php echo esc_attr($s['my_agency']); ?>"></label>
      <label>Website<input name="my_website" value="<?php echo esc_attr($s['my_website']); ?>"></label>
      <label>WhatsApp / phone<input name="my_phone" value="<?php echo esc_attr($s['my_phone']); ?>"></label>
      <label>Email<input type="email" name="my_email" value="<?php echo esc_attr($s['my_email']); ?>"></label>
      <label>Studio address (shown on the website)<input name="my_address" value="<?php echo esc_attr($s['my_address']); ?>"></label>
      <label>Default follow-up after outreach (days)<input type="number" min="1" max="30" name="followup_days" value="<?php echo (int) $s['followup_days']; ?>"></label>
    </div>
  </div>
  <div class="card">
    <h2>Website lead form</h2>
    <p class="small">Put the form on any page with the <b>Lightandpulse Lead Form</b> Elementor widget, or the shortcode <code>[lightandpulse_lead_form]</code>. Enquiries arrive here as hot leads.</p>
    <label class="check"><input type="checkbox" name="form_notify" value="1" <?php checked((int) $s['form_notify'], 1); ?>> Email me at <?php echo esc_html($s['my_email'] ?: get_option('admin_email')); ?> when a new enquiry arrives</label>
  </div>
  <div class="card">
    <h2>OpenStreetMap search (free)</h2>
    <p class="small">Works with no account or key. If the main server is busy, the search automatically retries on a mirror.</p>
    <label>Overpass server<input name="osm_endpoint" value="<?php echo esc_attr($s['osm_endpoint']); ?>"></label>
  </div>
  <div class="card">
    <h2>Google Maps search (optional, paid after free credit)</h2>
    <p class="small">Status: <?php echo $hasKey ? '<span class="lpl-badge stage-won">Connected</span>' : '<span class="lpl-badge prio-cold">Not connected</span>'; ?></p>
    <ol class="small">
      <li>Open <a target="_blank" rel="noopener" href="https://console.cloud.google.com/apis/library/places.googleapis.com">Google Cloud Console → Places API (New)</a> and enable it.</li>
      <li>Create an API key under <i>APIs &amp; Services → Credentials</i>, restricted to Places API (New) and your server's IP.</li>
      <li>Set a daily quota cap to avoid surprise charges.</li>
    </ol>
    <label>API key<input type="password" name="places_api_key" placeholder="<?php echo $hasKey ? esc_attr__('Saved. Leave blank to keep it.', 'lightandpulse') : 'AIza…'; ?>" autocomplete="off"></label>
    <?php if ($hasKey) : ?><label class="check"><input type="checkbox" name="remove_api_key" value="1"> Remove saved key</label><?php endif; ?>
  </div>
  <div class="btn-row"><button class="btn btn-primary">Save settings</button></div>
</form>
