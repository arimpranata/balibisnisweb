<?php require __DIR__ . '/header.php'; ?>
<div class="card auth-card wide">
  <h1>Welcome 👋</h1>
  <p class="muted">First-time setup. These details fill in your outreach messages — you can change them later in Settings.</p>
  <form method="post" action="<?= e(url('setup')) ?>">
    <?= csrf_field() ?>
    <div class="grid2">
      <label>Your name<input name="my_name" value="Ida Bagus Arim Pranata" required></label>
      <label>Agency<input name="my_agency" value="Lightandpulse Digital Agency" required></label>
      <label>Website<input name="my_website" value="https://lightandpulse.com"></label>
      <label>WhatsApp / phone<input name="my_phone" placeholder="0812…"></label>
      <label>Email<input type="email" name="my_email" placeholder="hello@…"></label>
    </div>
    <hr>
    <div class="grid2">
      <label>Choose a password (min. 10 chars)<input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
      <label>Confirm password<input type="password" name="password_confirm" minlength="10" required autocomplete="new-password"></label>
    </div>
    <button class="btn btn-primary">Start finding clients</button>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
