<?php require __DIR__ . '/header.php'; $t = $edit ?? ['id' => 0, 'name' => '', 'channel' => 'whatsapp', 'language' => 'id', 'subject' => '', 'body' => '']; ?>
<div class="page-head"><div><h1>Message templates</h1><p class="muted">Reusable WhatsApp &amp; email openers. Placeholders are filled from each lead.</p></div></div>
<div class="detail">
  <div class="detail-main">
    <?php foreach ($templates as $tp): ?>
      <div class="card tpl">
        <div class="card-head">
          <h3><span class="chip <?= $tp['channel'] === 'whatsapp' ? 'wa' : '' ?>"><?= $tp['channel'] === 'email' ? 'Email' : 'WhatsApp' ?></span> <span class="chip"><?= e(strtoupper($tp['language'])) ?></span> <?= e($tp['name']) ?></h3>
          <div class="btn-row">
            <a class="btn btn-ghost" href="<?= e(url('templates', ['id' => $tp['id']])) ?>#form">Edit</a>
            <form method="post" action="<?= e(url('template_delete')) ?>" class="inline" data-confirm="Delete this template?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $tp['id'] ?>"><button class="btn btn-ghost">Delete</button></form>
          </div>
        </div>
        <?php if ($tp['subject']): ?><p class="small"><b>Subject:</b> <?= e($tp['subject']) ?></p><?php endif; ?>
        <pre class="tpl-body"><?= e($tp['body']) ?></pre>
      </div>
    <?php endforeach; ?>
  </div>
  <aside class="detail-side">
    <form method="post" action="<?= e(url('template_save')) ?>" class="card sticky" id="form">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <h2><?= $t['id'] ? 'Edit template' : 'New template' ?></h2>
      <label>Name<input name="name" value="<?= e($t['name']) ?>" required></label>
      <div class="grid2">
        <label>Channel<select name="channel"><option value="whatsapp" <?= $t['channel'] === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option><option value="email" <?= $t['channel'] === 'email' ? 'selected' : '' ?>>Email</option></select></label>
        <label>Language<select name="language"><option value="id" <?= $t['language'] === 'id' ? 'selected' : '' ?>>Bahasa Indonesia</option><option value="en" <?= $t['language'] === 'en' ? 'selected' : '' ?>>English</option></select></label>
      </div>
      <label>Email subject<input name="subject" value="<?= e($t['subject']) ?>"></label>
      <label>Message<textarea name="body" rows="12" required><?= e($t['body']) ?></textarea></label>
      <p class="small muted">Placeholders: <code>{name}</code> <code>{contact}</code> <code>{area}</code> <code>{category}</code> <code>{website}</code> <code>{audit_findings}</code> <code>{my_name}</code> <code>{my_agency}</code> <code>{my_website}</code> <code>{my_phone}</code> <code>{my_email}</code></p>
      <div class="btn-row"><button class="btn btn-primary">Save</button><?php if ($t['id']): ?><a class="btn btn-ghost" href="<?= e(url('templates')) ?>">Cancel</a><?php endif; ?></div>
    </form>
  </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>
