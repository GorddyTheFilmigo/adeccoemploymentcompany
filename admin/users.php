<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
$me = require_login(true);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'add') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $pw = (string) ($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'editor';
        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            flash('err', 'Username must be 3-30 characters: letters, numbers, dot, dash or underscore.');
        } elseif ($p = password_problem($pw)) {
            flash('err', $p);
        } else {
            try {
                $pdo->prepare('INSERT INTO users (username, password_hash, role, created_at) VALUES (?,?,?,?)')
                    ->execute([$username, password_hash($pw, PASSWORD_DEFAULT), $role, date('Y-m-d H:i:s')]);
                audit('user.add', "$username ($role)");
                flash('ok', 'User created.');
            } catch (PDOException $ex) {
                flash('err', 'That username is already taken.');
            }
        }
    } elseif ($action === 'reset' && $id) {
        $pw = (string) ($_POST['password'] ?? '');
        if ($p = password_problem($pw)) {
            flash('err', $p);
        } else {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            audit('user.reset_password', "#$id");
            flash('ok', 'Password reset.');
        }
    } elseif ($action === 'delete' && $id) {
        $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        $st = $pdo->prepare('SELECT role FROM users WHERE id=?');
        $st->execute([$id]);
        $role = $st->fetchColumn();
        if ($id === (int) $me['id']) {
            flash('err', 'You cannot delete your own account.');
        } elseif ($role === 'admin' && $admins <= 1) {
            flash('err', 'You cannot delete the last administrator.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            audit('user.delete', "#$id");
            flash('ok', 'User deleted.');
        }
    }
    redirect('users.php');
}

$rows = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
admin_head('Users', 'users');
?>
<p class="muted"><strong>Admins</strong> can do everything. <strong>Editors</strong> can manage jobs, categories, messages and page content, but cannot change Settings (such as the WhatsApp number), manage users or view the activity log.</p>
<div class="card tight"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Username</th><th>Role</th><th>Last login</th><th>Reset password</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['username']) ?></td><td><?= e($r['role']) ?></td>
      <td><?= e($r['last_login'] ? format_date($r['last_login'], 'j M Y, H:i') : 'never') ?></td>
      <td>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="password" name="password" placeholder="New password" autocomplete="new-password" required><button class="btn btn-ghost btn-sm" type="submit">Reset</button></form>
      </td>
      <td>
        <?php if ((int) $r['id'] !== (int) $me['id']): ?>
        <form method="post" data-confirm="Delete this user?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>

<form method="post" class="card form adm-narrow">
  <h3>Add a user</h3>
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <label>Username<input type="text" name="username" required></label>
  <label>Password (10+ characters, letters and numbers)<input type="password" name="password" autocomplete="new-password" required></label>
  <label>Role<select name="role"><option value="editor">Editor</option><option value="admin">Admin</option></select></label>
  <button class="btn btn-primary" type="submit">Create user</button>
</form>
<?php admin_foot();
