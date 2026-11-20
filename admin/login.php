<?php
require __DIR__ . '/_init.php';
ensure_setup_done();

if (current_user()) {
    redirect(admin_url('index.php'));
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $err = attempt_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? '')) ?? '';
    if (!$err) {
        audit('login');
        redirect(admin_url('index.php'));
    }
}

admin_head('Admin login');
?>
<div class="card adm-narrow">
  <?php if ($err): ?><div class="flash flash-err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Username<input type="text" name="username" autocomplete="username" required autofocus></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-primary" type="submit">Log in</button>
  </form>
  <p class="muted"><a href="<?= e(url('index.php')) ?>">&larr; Back to website</a></p>
</div>
<?php admin_foot();
