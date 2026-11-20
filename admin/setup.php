<?php
require __DIR__ . '/_init.php';

// One-time page: only works while NO user exists. No default password is ever shipped.
if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    redirect(admin_url('login.php'));
}

$err = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        $err[] = 'Username must be 3-30 characters: letters, numbers, dot, dash or underscore.';
    }
    if ($p = password_problem($pw)) {
        $err[] = $p;
    }
    if ($pw !== $pw2) {
        $err[] = 'The two passwords do not match.';
    }
    if (!$err) {
        db()->prepare('INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, \'admin\', ?)')
            ->execute([$username, password_hash($pw, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        flash('ok', 'Admin account created. Please log in.');
        redirect(admin_url('login.php'));
    }
}

admin_head('Create your admin account');
?>
<div class="card adm-narrow">
  <p>Welcome! Create the first administrator account. This page disappears as soon as an account exists.</p>
  <?php foreach ($err as $x): ?><div class="flash flash-err"><?= e($x) ?></div><?php endforeach; ?>
  <form method="post" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <label>Username<input type="text" name="username" value="<?= e($username) ?>" required></label>
    <label>Password (10+ characters, letters and numbers)<input type="password" name="password" required></label>
    <label>Repeat password<input type="password" name="password2" required></label>
    <button class="btn btn-primary" type="submit">Create admin account</button>
  </form>
</div>
<?php admin_foot();
