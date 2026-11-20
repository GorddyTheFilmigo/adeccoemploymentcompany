<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
$user = require_login();

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cur = (string) ($_POST['current'] ?? '');
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password2'] ?? '');
    $st = db()->prepare('SELECT password_hash FROM users WHERE id=?');
    $st->execute([$user['id']]);
    if (!password_verify($cur, (string) $st->fetchColumn())) {
        $err[] = 'Your current password is incorrect.';
    }
    if ($p = password_problem($pw)) {
        $err[] = $p;
    }
    if ($pw !== $pw2) {
        $err[] = 'The new passwords do not match.';
    }
    if (!$err) {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        audit('account.password_change');
        flash('ok', 'Password changed.');
        redirect('account.php');
    }
}
admin_head('My account', 'account');
?>
<div class="card adm-narrow">
  <p>Signed in as <strong><?= e($user['username']) ?></strong> (<?= e($user['role']) ?>).</p>
  <?php foreach ($err as $x): ?><div class="flash flash-err"><?= e($x) ?></div><?php endforeach; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Current password<input type="password" name="current" autocomplete="current-password" required></label>
    <label>New password (10+ characters, letters and numbers)<input type="password" name="password" autocomplete="new-password" required></label>
    <label>Repeat new password<input type="password" name="password2" autocomplete="new-password" required></label>
    <button class="btn btn-primary" type="submit">Change password</button>
  </form>
</div>
<?php admin_foot();
