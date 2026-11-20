<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $ids = [$id];
    }

    if ($action === 'delete_samples') {
        $c = $pdo->exec('DELETE FROM jobs WHERE is_sample = 1');
        audit('jobs.delete_samples', (string) $c);
        flash('ok', "Deleted $c sample job(s).");
    } elseif ($ids && in_array($action, ['publish', 'draft', 'closed', 'delete', 'feature', 'unfeature'], true)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $now = date('Y-m-d H:i:s');
        switch ($action) {
            case 'publish':   $pdo->prepare("UPDATE jobs SET status='published', updated_at='$now' WHERE id IN ($in)")->execute($ids); break;
            case 'draft':     $pdo->prepare("UPDATE jobs SET status='draft', updated_at='$now' WHERE id IN ($in)")->execute($ids); break;
            case 'closed':    $pdo->prepare("UPDATE jobs SET status='closed', updated_at='$now' WHERE id IN ($in)")->execute($ids); break;
            case 'feature':   $pdo->prepare("UPDATE jobs SET featured=1 WHERE id IN ($in)")->execute($ids); break;
            case 'unfeature': $pdo->prepare("UPDATE jobs SET featured=0 WHERE id IN ($in)")->execute($ids); break;
            case 'delete':    $pdo->prepare("DELETE FROM jobs WHERE id IN ($in)")->execute($ids); break;
        }
        audit('jobs.' . $action, implode(',', $ids));
        flash('ok', 'Done: ' . $action . ' applied to ' . count($ids) . ' job(s).');
    } else {
        flash('err', 'Nothing was selected.');
    }
    redirect('jobs.php?' . http_build_query(array_filter(['status' => $_POST['back_status'] ?? '', 'q' => $_POST['back_q'] ?? ''])));
}

$status = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ' WHERE 1=1';
$p = [];
if (in_array($status, ['published', 'draft', 'closed'], true)) {
    $where .= ' AND j.status = ?';
    $p[] = $status;
}
if ($q !== '') {
    $where .= ' AND (j.title LIKE ? OR j.employer LIKE ? OR j.location LIKE ?)';
    array_push($p, "%$q%", "%$q%", "%$q%");
}
$per = 20;
$st = $pdo->prepare('SELECT COUNT(*) FROM jobs j' . $where);
$st->execute($p);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min(current_page(), $pages);
$st = $pdo->prepare('SELECT j.*, c.name AS cat, (SELECT COUNT(*) FROM apply_clicks a WHERE a.job_id=j.id) AS clicks FROM jobs j LEFT JOIN categories c ON c.id=j.category_id' . $where . ' ORDER BY j.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
$st->execute($p);
$rows = $st->fetchAll();

admin_head('Jobs', 'jobs');
$today = date('Y-m-d');
?>
<div class="row-between">
  <form method="get" class="inline-form">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search jobs">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach (['published', 'draft', 'closed'] as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
  </form>
  <a class="btn btn-primary" href="job_edit.php">+ Add job</a>
</div>

<form method="post" class="card tight">
  <?= csrf_field() ?>
  <input type="hidden" name="back_status" value="<?= e($status) ?>"><input type="hidden" name="back_q" value="<?= e($q) ?>">
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr><th><input type="checkbox" data-select-all aria-label="Select all"></th><th>Ref</th><th>Title</th><th>Category</th><th>Location</th><th>Status</th><th>Views</th><th>Applies</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $expired = $r['deadline'] && $r['deadline'] < $today; ?>
      <tr>
        <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>"></td>
        <td><?= e(job_ref((int) $r['id'])) ?></td>
        <td><a href="job_edit.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a><?= $r['featured'] ? ' <span class="tag tag-feat">Featured</span>' : '' ?><?= $r['is_sample'] ? ' <span class="tag">Sample</span>' : '' ?></td>
        <td><?= e($r['cat'] ?? '-') ?></td>
        <td><?= e($r['location']) ?></td>
        <td><span class="pill pill-<?= e($r['status']) ?>"><?= e($r['status']) ?></span><?= $expired ? ' <small class="muted">expired</small>' : '' ?></td>
        <td><?= (int) $r['views'] ?></td>
        <td><?= (int) $r['clicks'] ?></td>
        <td class="nowrap"><a href="<?= e(url('job.php?id=' . (int) $r['id'])) ?>" target="_blank" rel="noopener">View</a> &middot; <a href="job_edit.php?id=<?= (int) $r['id'] ?>">Edit</a></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="muted">No jobs found.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
  <div class="bulk">
    <select name="action" aria-label="Bulk action">
      <option value="">With selected...</option>
      <option value="publish">Publish</option><option value="draft">Move to draft</option><option value="closed">Mark closed</option>
      <option value="feature">Feature</option><option value="unfeature">Unfeature</option><option value="delete">Delete</option>
    </select>
    <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
  </div>
</form>
<?= pager_html($page, $pages, array_filter(['status' => $status, 'q' => $q])) ?>
<?php admin_foot();
