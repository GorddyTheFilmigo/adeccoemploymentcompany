<?php
/** Reusable public-site fragments. */

function job_card(array $j): string
{
    $meta = array_filter([
        $j['location'] ? '<span class="meta-item">&#9679; ' . e($j['location']) . '</span>' : '',
        $j['job_type'] ? '<span class="meta-item">' . e($j['job_type']) . '</span>' : '',
        !empty($j['salary']) ? '<span class="meta-item">' . e($j['salary']) . '</span>' : '',
    ]);
    $new = (strtotime($j['created_at']) > time() - 7 * 86400) ? '<span class="tag tag-new">New</span>' : '';
    $feat = !empty($j['featured']) ? '<span class="tag tag-feat">Featured</span>' : '';
    ob_start(); ?>
    <article class="job-card">
      <div class="job-card-top">
        <?php if (!empty($j['category_name'])): ?><span class="chip"><?= e($j['category_name']) ?></span><?php endif; ?>
        <?= $feat . $new ?>
      </div>
      <h3><a href="<?= e(job_url((int) $j['id'])) ?>"><?= e($j['title']) ?></a></h3>
      <?php if (!empty($j['employer'])): ?><p class="employer"><?= e($j['employer']) ?></p><?php endif; ?>
      <p class="summary"><?= e($j['summary'] ? truncate((string) $j['summary'], 120) : plain_excerpt($j['description'], 120)) ?></p>
      <div class="job-meta"><?= implode('', $meta) ?></div>
      <div class="job-card-actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(job_url((int) $j['id'])) ?>">View details</a>
        <a class="btn btn-wa btn-sm" href="<?= e(url('apply.php?id=' . (int) $j['id'])) ?>" rel="nofollow noopener" target="_blank">Apply</a>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

/** SELECT used by the public job lists. */
function live_jobs_sql(): string
{
    return "FROM jobs j LEFT JOIN categories c ON c.id = j.category_id
            WHERE j.status = 'published' AND (j.deadline IS NULL OR j.deadline = '' OR j.deadline >= :today)";
}

function notice_html(): string
{
    $n = setting('notice');
    return $n ? '<div class="notice"><div class="container">' . e($n) . '</div></div>' : '';
}
