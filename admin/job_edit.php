<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login();
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$job = $id ? find_job($id) : null;
if ($id && !$job) {
    flash('err', 'That job does not exist.');
    redirect('jobs.php');
}
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();

$f = $job ?: ['title' => '', 'category_id' => '', 'employer' => '', 'location' => '', 'job_type' => 'Full-time', 'salary' => '', 'summary' => '', 'description' => '', 'requirements' => '', 'benefits' => '', 'deadline' => '', 'status' => 'published', 'featured' => 0];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (['title', 'employer', 'location', 'salary', 'summary', 'description', 'requirements', 'benefits', 'deadline'] as $k) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $f['category_id'] = (int) ($_POST['category_id'] ?? 0) ?: null;
    $f['job_type'] = in_array($_POST['job_type'] ?? '', JOB_TYPES, true) ? $_POST['job_type'] : 'Full-time';
    $f['status'] = in_array($_POST['status'] ?? '', ['published', 'draft', 'closed'], true) ? $_POST['status'] : 'draft';
    $f['featured'] = empty($_POST['featured']) ? 0 : 1;

    if ($f['title'] === '' || str_len($f['title']) > 150) {
        $err[] = 'Please enter a job title (max 150 characters).';
    }
    if ($f['description'] === '') {
        $err[] = 'Please add a description.';
    }
    if ($f['deadline'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['deadline'])) {
        $err[] = 'Deadline must be a valid date.';
    }
    if (str_len($f['summary']) > 250) {
        $err[] = 'Summary must be 250 characters or fewer.';
    }
    if (!$err) {
        $now = date('Y-m-d H:i:s');
        $vals = [$f['title'], $f['category_id'], $f['employer'], $f['location'], $f['job_type'], $f['salary'], $f['summary'], $f['description'], $f['requirements'], $f['benefits'], $f['deadline'] ?: null, $f['status'], $f['featured']];
        if ($job) {
            $pdo->prepare('UPDATE jobs SET title=?, category_id=?, employer=?, location=?, job_type=?, salary=?, summary=?, description=?, requirements=?, benefits=?, deadline=?, status=?, featured=?, is_sample=0, updated_at=? WHERE id=?')
                ->execute(array_merge($vals, [$now, $id]));
            audit('job.update', "#$id " . $f['title']);
            flash('ok', 'Job updated.');
        } else {
            $pdo->prepare('INSERT INTO jobs (title, category_id, employer, location, job_type, salary, summary, description, requirements, benefits, deadline, status, featured, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(array_merge($vals, [$now, $now]));
            $id = (int) $pdo->lastInsertId();
            audit('job.create', "#$id " . $f['title']);
            flash('ok', 'Job created.');
        }
        redirect('jobs.php');
    }
}

admin_head($job ? 'Edit job ' . job_ref($id) : 'Add job', 'jobs');
?>
<?php foreach ($err as $x): ?><div class="flash flash-err"><?= e($x) ?></div><?php endforeach; ?>
<form method="post" class="card form">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>">
  <label>Job title *<input type="text" name="title" value="<?= e($f['title']) ?>" maxlength="150" required></label>
  <div class="two">
    <label>Category
      <select name="category_id"><option value="">None</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $f['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Employer / company (shown publicly, optional)<input type="text" name="employer" value="<?= e($f['employer']) ?>"></label>
  </div>
  <div class="three">
    <label>Location<input type="text" name="location" value="<?= e($f['location']) ?>"></label>
    <label>Job type
      <select name="job_type"><?php foreach (JOB_TYPES as $t): ?><option <?= $f['job_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
    </label>
    <label>Salary (optional)<input type="text" name="salary" value="<?= e($f['salary']) ?>" placeholder="e.g. KES 25,000 / month"></label>
  </div>
  <label>Short summary (max 250 characters)<input type="text" name="summary" value="<?= e($f['summary']) ?>" maxlength="250"></label>
  <label>Description *<textarea name="description" rows="9" required><?= e($f['description']) ?></textarea></label>
  <label>Requirements<textarea name="requirements" rows="5"><?= e($f['requirements']) ?></textarea></label>
  <label>What we offer<textarea name="benefits" rows="4"><?= e($f['benefits']) ?></textarea></label>
  <p class="muted small">Formatting: blank line = new paragraph, <code>## Heading</code>, lines starting with <code>- </code> become bullets, <code>**bold**</code>.</p>
  <div class="three">
    <label>Application deadline (optional)<input type="date" name="deadline" value="<?= e((string) $f['deadline']) ?>"></label>
    <label>Status
      <select name="status"><?php foreach (['published', 'draft', 'closed'] as $s): ?><option <?= $f['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    </label>
    <label class="check"><input type="checkbox" name="featured" value="1" <?= $f['featured'] ? 'checked' : '' ?>> Featured (shown first)</label>
  </div>
  <div class="actions">
    <button class="btn btn-primary" type="submit">Save job</button>
    <a class="btn btn-ghost" href="jobs.php">Cancel</a>
  </div>
</form>
<?php if ($job): ?>
<form method="post" action="jobs.php" class="card" data-confirm="Permanently delete this job?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $id ?>">
  <button class="btn btn-danger btn-sm" type="submit">Delete this job</button>
</form>
<?php endif; ?>
<?php admin_foot();
