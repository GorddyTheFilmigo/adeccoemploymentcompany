<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login(true);

$per = 50;
$total = (int) db()->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min(current_page(), $pages);
$rows = db()->query('SELECT * FROM audit_log ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per))->fetchAll();

admin_head('Activity log', 'audit');
?>
<div class="card tight"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="nowrap"><?= e(format_date($r['created_at'], 'j M Y, H:i')) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['detail']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="muted">Nothing logged yet.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?= pager_html($page, $pages) ?>
<?php admin_foot();
