<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$active = 'jobs';
$id = (int) ($_GET['id'] ?? 0);
$job = $id ? find_job($id) : null;
$isAdmin = (bool) current_user();

if (!$job || ($job['status'] !== 'published' && !$isAdmin)) {
    fail_page(404, 'Job not found', 'This vacancy does not exist or is no longer available.');
}

$closed = !empty($job['deadline']) && $job['deadline'] < date('Y-m-d');
$draft = $job['status'] !== 'published';

if (!$isAdmin && !is_bot()) {
    db()->prepare('UPDATE jobs SET views = views + 1 WHERE id = ?')->execute([$id]);
}

$st = db()->prepare("SELECT j.*, c.name AS category_name " . live_jobs_sql() . " AND j.id != :id AND j.category_id IS :cat ORDER BY j.created_at DESC LIMIT 3");
$st->execute([':today' => date('Y-m-d'), ':id' => $id, ':cat' => $job['category_id']]);
$related = $job['category_id'] ? $st->fetchAll() : [];

$pageTitle = $job['title'];
$metaDesc = $job['summary'] ?: plain_excerpt($job['description'], 150);
include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container">
    <p class="crumbs"><a href="<?= e(url('jobs.php')) ?>">&larr; All jobs</a></p>
    <h1><?= e($job['title']) ?></h1>
    <div class="job-meta big">
      <?php if ($job['category_name']): ?><span class="chip"><?= e($job['category_name']) ?></span><?php endif; ?>
      <?php if ($job['location']): ?><span class="meta-item">&#9679; <?= e($job['location']) ?></span><?php endif; ?>
      <span class="meta-item"><?= e($job['job_type']) ?></span>
      <?php if ($job['salary']): ?><span class="meta-item"><?= e($job['salary']) ?></span><?php endif; ?>
    </div>
  </div>
</section>
<?php if ($draft): ?><div class="notice"><div class="container">Preview: this job is a draft and is only visible to logged-in admins.</div></div><?php endif; ?>

<section class="section">
  <div class="container detail-layout">
    <article class="prose card">
      <?php if ($job['summary']): ?><p class="lead"><?= e($job['summary']) ?></p><?php endif; ?>
      <?= render_text($job['description']) ?>
      <?php if (trim((string) $job['requirements'])): ?><h3>Requirements</h3><?= render_text($job['requirements']) ?><?php endif; ?>
      <?php if (trim((string) $job['benefits'])): ?><h3>What we offer</h3><?= render_text($job['benefits']) ?><?php endif; ?>
    </article>

    <aside class="side">
      <div class="card apply-box">
        <?php if ($closed): ?>
          <h3>Applications closed</h3>
          <p>The deadline for this vacancy has passed. Message us to ask about similar roles.</p>
          <a class="btn btn-wa btn-block" href="<?= e(url('apply.php')) ?>" target="_blank" rel="nofollow noopener">Ask on WhatsApp</a>
        <?php else: ?>
          <h3>Interested in this role?</h3>
          <p>Tap Apply to open WhatsApp and message our team about this job.</p>
          <a class="btn btn-wa btn-block" href="<?= e(url('apply.php?id=' . $id)) ?>" target="_blank" rel="nofollow noopener">Apply on WhatsApp</a>
        <?php endif; ?>
        <dl class="facts">
          <div><dt>Reference</dt><dd><?= e(job_ref($id)) ?></dd></div>
          <?php if ($job['employer']): ?><div><dt>Employer</dt><dd><?= e($job['employer']) ?></dd></div><?php endif; ?>
          <div><dt>Posted</dt><dd><?= e(format_date($job['created_at'])) ?></dd></div>
          <?php if ($job['deadline']): ?><div><dt>Apply by</dt><dd><?= e(format_date($job['deadline'])) ?></dd></div><?php endif; ?>
        </dl>
      </div>
      <p class="safe">Never pay money to secure a job. If anything looks wrong, ask us on WhatsApp first.</p>
    </aside>
  </div>
</section>

<?php if ($related): ?>
<section class="section alt">
  <div class="container">
    <div class="section-head"><h2>Similar vacancies</h2></div>
    <div class="grid-3"><?php foreach ($related as $j) echo job_card($j); ?></div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
