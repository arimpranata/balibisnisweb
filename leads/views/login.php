<?php require __DIR__ . '/header.php'; ?>
<div class="card auth-card">
  <div class="auth-logo">
    <svg viewBox="0 0 32 32" width="48" height="48" aria-hidden="true"><circle cx="16" cy="16" r="14" fill="var(--accent)"/><path d="M6 17h5l3-7 4 13 3-6h5" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </div>
  <h1>Lightandpulse Leads</h1>
  <p class="muted">Find, manage &amp; connect with Bali clients.</p>
  <?php if (!empty($error)): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?>
    <label>Password<input type="password" name="password" required autofocus autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block">Log in</button>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
