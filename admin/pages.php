<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login();
$pdo = db();

$slug = (string) ($_GET['slug'] ?? $_POST['slug'] ?? '');
$st = $pdo->prepare('SELECT * FROM pages WHERE slug=?');
$st->execute([$slug]);
$page = $st->fetch();

if ($page && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    if ($title === '' || $content === '') {
        flash('err', 'Title and content cannot be empty.');
    } else {
        $pdo->prepare('UPDATE pages SET title=?, content=?, updated_at=? WHERE slug=?')->execute([$title, $content, date('Y-m-d H:i:s'), $slug]);
        audit('page.update', $slug);
        flash('ok', 'Page saved.');
        redirect('pages.php');
    }
    $page['title'] = $title;
    $page['content'] = $content;
}

if ($page) {
    admin_head('Edit: ' . $page['title'], 'pages');
    ?>
    <form method="post" class="card form">
      <?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>">
      <label>Page title<input type="text" name="title" value="<?= e($page['title']) ?>" required></label>
      <label>Content<textarea name="content" rows="18" required><?= e($page['content']) ?></textarea></label>
      <p class="muted small">Formatting: blank line = new paragraph, <code>## Heading</code>, lines starting with <code>- </code> become bullets, <code>**bold**</code>. HTML is not allowed.</p>
      <div class="actions"><button class="btn btn-primary" type="submit">Save page</button><a class="btn btn-ghost" href="pages.php">Cancel</a></div>
    </form>
    <?php
    admin_foot();
    exit;
}

$rows = $pdo->query('SELECT * FROM pages ORDER BY title')->fetchAll();
admin_head('Page content', 'pages');
?>
<p class="muted">Edit the text of your About, Services and Privacy pages. Home page wording is in <a href="settings.php">Settings</a>.</p>
<div class="card tight"><table class="tbl">
  <thead><tr><th>Page</th><th>Last updated</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['title']) ?></td><td><?= e(format_date($r['updated_at'], 'j M Y, H:i')) ?></td><td><a href="pages.php?slug=<?= e($r['slug']) ?>">Edit</a></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php admin_foot();
