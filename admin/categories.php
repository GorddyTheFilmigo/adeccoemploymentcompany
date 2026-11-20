<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    try {
        if ($action === 'add' && $name !== '' && str_len($name) <= 80) {
            $pdo->prepare('INSERT INTO categories (name, sort_order) VALUES (?, (SELECT COALESCE(MAX(sort_order),0)+1 FROM categories))')->execute([$name]);
            audit('category.add', $name);
            flash('ok', 'Category added.');
        } elseif ($action === 'rename' && $id && $name !== '') {
            $pdo->prepare('UPDATE categories SET name=? WHERE id=?')->execute([$name, $id]);
            audit('category.rename', "#$id $name");
            flash('ok', 'Category renamed.');
        } elseif ($action === 'delete' && $id) {
            $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
            audit('category.delete', "#$id");
            flash('ok', 'Category deleted. Its jobs are now uncategorised.');
        } else {
            flash('err', 'Please enter a category name (max 80 characters).');
        }
    } catch (PDOException $ex) {
        flash('err', 'A category with that name already exists.');
    }
    redirect('categories.php');
}

$rows = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.category_id=c.id) AS n FROM categories c ORDER BY sort_order, name')->fetchAll();
admin_head('Categories', 'categories');
?>
<div class="card">
  <form method="post" class="inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <input type="text" name="name" placeholder="New category name" maxlength="80" required>
    <button class="btn btn-primary btn-sm" type="submit">Add category</button>
  </form>
</div>
<div class="card tight"><div class="table-wrap">
<table class="tbl">
  <thead><tr><th>Name</th><th>Jobs</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td>
        <form method="post" class="inline-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="text" name="name" value="<?= e($r['name']) ?>" maxlength="80" required>
          <button class="btn btn-ghost btn-sm" type="submit">Rename</button>
        </form>
      </td>
      <td><?= (int) $r['n'] ?></td>
      <td>
        <form method="post" data-confirm="Delete this category? Jobs in it become uncategorised.">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn btn-danger btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php admin_foot();
