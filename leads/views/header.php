<?php $f = flash(); $authed = is_logged_in(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? '') ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css?v=<?= e(APP_VERSION) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='14' fill='%23f59e0b'/><path d='M6 17h5l3-7 4 13 3-6h5' stroke='%23fff' stroke-width='2.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
</head>
<body class="<?= $authed ? 'app' : 'auth' ?>">
<?php if ($authed): ?>
<header class="topbar">
  <a class="brand" href="<?= e(url('dashboard')) ?>">
    <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><circle cx="16" cy="16" r="14" fill="var(--accent)"/><path d="M6 17h5l3-7 4 13 3-6h5" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span>Light<b>&amp;</b>Pulse <small>Leads</small></span>
  </a>
  <button class="nav-toggle" type="button" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
  <nav class="nav">
    <?php foreach (['dashboard' => 'Dashboard', 'finder' => 'Find clients', 'leads' => 'Leads', 'templates' => 'Templates', 'settings' => 'Settings'] as $k => $label): ?>
      <a href="<?= e(url($k)) ?>" class="<?= ($page ?? '') === $k || (($page ?? '') === 'lead' && $k === 'leads') ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('logout')) ?>" class="inline"><?= csrf_field() ?><button class="linkish">Log out</button></form>
  </nav>
</header>
<?php endif; ?>
<main class="<?= $authed ? 'container' : 'auth-wrap' ?>">
<?php if ($f): ?><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endif; ?>
