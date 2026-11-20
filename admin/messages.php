<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id && $action === 'delete') {
        $pdo->prepare('DELETE FROM messages WHERE id=?')->execute([$id]);
        audit('message.delete', "#$id");
        flash('ok', 'Message deleted.');
        redirect('messages.php');
    } elseif ($id && $action === 'unread') {
        $pdo->prepare('UPDATE messages SET is_read=0 WHERE id=?')->execute([$id]);
        redirect('messages.php');
    } elseif ($action === 'read_all') {
        $pdo->exec('UPDATE messages SET is_read=1');
        redirect('messages.php');
    }
}

$view = (int) ($_GET['id'] ?? 0);
if ($view) {
    $st = $pdo->prepare('SELECT * FROM messages WHERE id=?');
    $st->execute([$view]);
    $m = $st->fetch();
    if ($m) {
        $pdo->prepare('UPDATE messages SET is_read=1 WHERE id=?')->execute([$view]);
        admin_head('Message from ' . $m['name'], 'messages');
        $digits = preg_replace('/\D+/', '', (string) $m['phone']);
        ?>
        <div class="card">
          <dl class="facts">
            <div><dt>Received</dt><dd><?= e(format_date($m['created_at'], 'j M Y, H:i')) ?></dd></div>
            <div><dt>Name</dt><dd><?= e($m['name']) ?></dd></div>
            <?php if ($m['email']): ?><div><dt>Email</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd></div><?php endif; ?>
            <?php if ($m['phone']): ?><div><dt>Phone</dt><dd><?= e($m['phone']) ?><?= strlen($digits) >= 8 ? ' &middot; <a href="https://wa.me/' . e($digits) . '" target="_blank" rel="noopener noreferrer">WhatsApp</a>' : '' ?></dd></div><?php endif; ?>
            <?php if ($m['subject']): ?><div><dt>Subject</dt><dd><?= e($m['subject']) ?></dd></div><?php endif; ?>
          </dl>
          <p style="white-space:pre-wrap"><?= e($m['body']) ?></p>
          <div class="actions">
            <a class="btn btn-ghost btn-sm" href="messages.php">&larr; Back</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="unread"><button class="btn btn-ghost btn-sm" type="submit">Mark unread</button></form>
            <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
          </div>
        </div>
        <?php
        admin_foot();
        exit;
    }
}

$per = 25;
$total = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min(current_page(), $pages);
$rows = $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per))->fetchAll();

admin_head('Messages', 'messages');
?>
<div class="row-between">
  <span class="muted"><?= $total ?> message<?= $total === 1 ? '' : 's' ?></span>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-ghost btn-sm" type="submit">Mark all read</button></form>
</div>
<div class="card tight"><div class="table-wrap">
<table class="tbl">
  <thead><tr><th>From</th><th>Subject / message</th><th>Contact</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $m): ?>
    <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
      <td><a href="messages.php?id=<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a></td>
      <td><?= e(truncate((string) ($m['subject'] ?: $m['body']), 70)) ?></td>
      <td><?= e($m['email'] ?: $m['phone']) ?></td>
      <td class="nowrap"><?= e(format_date($m['created_at'], 'j M, H:i')) ?></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="muted">No messages yet.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?= pager_html($page, $pages) ?>
<?php admin_foot();
