<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$active = 'jobs';
$pageTitle = 'Find jobs';

$q    = trim((string) ($_GET['q'] ?? ''));
$cat  = (int) ($_GET['category'] ?? 0);
$loc  = trim((string) ($_GET['location'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
if (!in_array($type, JOB_TYPES, true)) {
    $type = '';
}

$where = '';
$params = [':today' => date('Y-m-d')];
if ($q !== '') {
    $where .= ' AND (j.title LIKE :q OR j.summary LIKE :q OR j.description LIKE :q OR j.employer LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($cat) {
    $where .= ' AND j.category_id = :cat';
    $params[':cat'] = $cat;
}
if ($loc !== '') {
    $where .= ' AND j.location LIKE :loc';
    $params[':loc'] = '%' . $loc . '%';
}
if ($type !== '') {
    $where .= ' AND j.job_type = :type';
    $params[':type'] = $type;
}

$perPage = 9;
$st = db()->prepare('SELECT COUNT(*) ' . live_jobs_sql() . $where);
$st->execute($params);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min(current_page(), $pages);

$st = db()->prepare('SELECT j.*, c.name AS category_name ' . live_jobs_sql() . $where . ' ORDER BY j.featured DESC, j.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$st->execute($params);
$jobs = $st->fetchAll();
$cats = db()->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container">
    <h1>Find jobs</h1>
    <p class="lead"><?= $total ?> vacanc<?= $total === 1 ? 'y' : 'ies' ?> found</p>
  </div>
</section>
<?= notice_html() ?>
<section class="section">
  <div class="container jobs-layout">
    <aside>
      <form class="filters" method="get" action="">
        <label>Keyword<input type="search" name="q" value="<?= e($q) ?>" placeholder="Job title or keyword"></label>
        <label>Category
          <select name="category">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Location<input type="text" name="location" value="<?= e($loc) ?>" placeholder="City or region"></label>
        <label>Job type
          <select name="type">
            <option value="">Any type</option>
            <?php foreach (JOB_TYPES as $t): ?><option <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
          </select>
        </label>
        <button class="btn btn-primary" type="submit">Apply filters</button>
        <?php if ($q || $cat || $loc || $type): ?><a class="reset" href="<?= e(url('jobs.php')) ?>">Clear all</a><?php endif; ?>
      </form>
    </aside>
    <div>
      <?php if ($jobs): ?>
        <div class="grid-2"><?php foreach ($jobs as $j) echo job_card($j); ?></div>
        <?= pager_html($page, $pages, array_filter(['q' => $q, 'category' => $cat ?: '', 'location' => $loc, 'type' => $type])) ?>
      <?php else: ?>
        <div class="empty">
          <h3>No matching jobs</h3>
          <p>Try fewer filters, or message us on WhatsApp and tell us what you are looking for.</p>
          <a class="btn btn-wa" href="<?= e(url('apply.php')) ?>" target="_blank" rel="nofollow noopener">Chat on WhatsApp</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
