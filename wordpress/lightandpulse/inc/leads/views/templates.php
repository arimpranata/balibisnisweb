<?php
defined('ABSPATH') || exit;
global $wpdb;
$templates = $wpdb->get_results('SELECT * FROM ' . lpl_table('templates') . ' ORDER BY channel, name', ARRAY_A);
$edit = null;
if (lpl_get('id') !== '') {
    $edit = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . lpl_table('templates') . ' WHERE id = %d', (int) lpl_get('id')), ARRAY_A);
}
$t = $edit ?: ['id' => 0, 'name' => '', 'channel' => 'whatsapp', 'language' => 'id', 'subject' => '', 'body' => ''];
?>
<div class="lpl-head"><div><h1>Message templates</h1><p class="muted">Reusable WhatsApp &amp; email openers. Placeholders are filled from each lead.</p></div></div>
<div class="detail">
  <div class="detail-main">
    <?php foreach ($templates as $tp) : ?>
      <div class="card tpl">
        <div class="card-head">
          <h3><span class="chip <?php echo $tp['channel'] === 'whatsapp' ? 'wa' : ''; ?>"><?php echo $tp['channel'] === 'email' ? 'Email' : 'WhatsApp'; ?></span> <span class="chip"><?php echo esc_html(strtoupper($tp['language'])); ?></span> <?php echo esc_html($tp['name']); ?></h3>
          <div class="btn-row">
            <a class="btn btn-ghost" href="<?php echo esc_url(lpl_url('templates', ['id' => $tp['id']])); ?>#tpl-form">Edit</a>
            <?php echo lpl_form_open('template_delete', ['class' => 'inline', 'data-confirm' => 'Delete this template?']); ?><input type="hidden" name="id" value="<?php echo (int) $tp['id']; ?>"><button class="btn btn-ghost">Delete</button></form>
          </div>
        </div>
        <?php if ($tp['subject']) : ?><p class="small"><b>Subject:</b> <?php echo esc_html($tp['subject']); ?></p><?php endif; ?>
        <pre class="tpl-body"><?php echo esc_html($tp['body']); ?></pre>
      </div>
    <?php endforeach; ?>
  </div>
  <aside class="detail-side">
    <?php echo lpl_form_open('template_save', ['class' => 'card sticky', 'id' => 'tpl-form']); ?>
      <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
      <h2><?php echo $t['id'] ? 'Edit template' : 'New template'; ?></h2>
      <label>Name<input name="name" value="<?php echo esc_attr($t['name']); ?>" required></label>
      <div class="grid2">
        <label>Channel<select name="channel"><option value="whatsapp" <?php selected($t['channel'], 'whatsapp'); ?>>WhatsApp</option><option value="email" <?php selected($t['channel'], 'email'); ?>>Email</option></select></label>
        <label>Language<select name="language"><option value="id" <?php selected($t['language'], 'id'); ?>>Bahasa Indonesia</option><option value="en" <?php selected($t['language'], 'en'); ?>>English</option></select></label>
      </div>
      <label>Email subject<input name="subject" value="<?php echo esc_attr($t['subject']); ?>"></label>
      <label>Message<textarea name="body" rows="12" required><?php echo esc_textarea($t['body']); ?></textarea></label>
      <p class="small muted">Placeholders: <code>{name}</code> <code>{contact}</code> <code>{area}</code> <code>{category}</code> <code>{website}</code> <code>{audit_findings}</code> <code>{my_name}</code> <code>{my_agency}</code> <code>{my_website}</code> <code>{my_phone}</code> <code>{my_email}</code></p>
      <div class="btn-row"><button class="btn btn-primary">Save</button><?php if ($t['id']) : ?><a class="btn btn-ghost" href="<?php echo esc_url(lpl_url('templates')); ?>">Cancel</a><?php endif; ?></div>
    </form>
  </aside>
</div>
