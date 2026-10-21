</main>
<?php if (is_logged_in()): ?>
<footer class="foot"><?= e(profile()['my_agency']) ?> · <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></footer>
<?php endif; ?>
<script src="assets/app.js?v=<?= e(APP_VERSION) ?>"></script>
</body>
</html>
