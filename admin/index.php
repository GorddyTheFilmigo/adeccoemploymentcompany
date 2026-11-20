<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login();

$pdo = db();
$today = date('Y-m-d');
$n = fn(string $sql, array $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return (int) $s->fetchColumn(); })();

$stats = [
    'Live jobs'          => $n("SELECT COUNT(*) FROM jobs WHERE status='published' AND (deadline IS NULL OR deadline='' OR deadline>=?)", [$today]),
    'Drafts / closed'    => $n("SELECT COUNT(*) FROM jobs WHERE status!='published' OR (deadline!='' AND deadline<?)", [$today]),
    'Apply clicks (7d)'  => $n('SELECT COUNT(*) FROM apply_clicks WHERE created_at>=?', [date('Y-m-d H:i:s', strtotime('-7 days'))]),
    'Apply clicks (all)' => $n('SELECT COUNT(*) FROM apply_clicks'),
    'Unread messages'    => $n('SELECT COUNT(*) FROM messages WHERE is_read=0'),
    'Total job views'    => $n('SELECT COALESCE(SUM(views),0) FROM jobs'),
];

$top = $pdo->query("SELECT j.id, j.title, j.views, COUNT(a.id) AS clicks FROM jobs j LEFT JOIN apply_clicks a ON a.job_id=j.id GROUP BY j.id ORDER BY clicks DESC, j.views DESC LIMIT 5")->fetchAll();
$recent = $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 5')->fetchAll();
$hasSamples = $n('SELECT COUNT(*) FROM jobs WHERE is_sample=1');

// last 14 days of clicks
$days = [];
for ($i = 13; $i >= 0; $i--) {
    $days[date('Y-m-d', strtotime("-$i days"))] = 0;
}
$st = $pdo->prepare('SELECT substr(created_at,1,10) d, COUNT(*) c FROM apply_clicks WHERE created_at>=? GROUP BY d');
$st->execute([array_key_first($days) . ' 00:00:00']);
foreach ($st as $r) {
    if (isset($days[$r['d']])) {
        $days[$r['d']] = (int) $r['c'];
    }
}
$max = max(1, max($days));

admin_head('Dashboard', 'dash');
?>
<?php if (setting('whatsapp_number') === ''): ?><div class="flash flash-err">No WhatsApp number is set. Apply buttons will not work until you add one in <a href="settings.php">Settings</a>.</div><?php endif; ?>
<div class="stats">
  <?php foreach ($stats as $label => $v): ?><div class="stat"><strong><?= $v ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>

<div class="adm-cols">
  <div class="card">
    <h3>Apply clicks, last 14 days</h3>
    <div class="bars" role="img" aria-label="Bar chart of apply clicks per day">
      <?php foreach ($days as $d => $c): ?>
        <div class="bar" title="<?= e($d) ?>: <?= $c ?>"><i style="height:<?= round($c / $max * 100) ?>%"></i><span><?= e(date('j', strtotime($d))) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h3>Top jobs</h3>
    <?php if ($top): ?>
    <table class="tbl"><thead><tr><th>Job</th><th>Views</th><th>Applies</th></tr></thead><tbody>
      <?php foreach ($top as $t): ?><tr><td><a href="<?= e(admin_url('job_edit.php?id=' . $t['id'])) ?>"><?= e($t['title']) ?></a></td><td><?= (int) $t['views'] ?></td><td><?= (int) $t['clicks'] ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php else: ?><p class="muted">No jobs yet.</p><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="row-between"><h3>Latest messages</h3><a href="messages.php">All messages &rarr;</a></div>
  <?php if ($recent): ?>
  <table class="tbl"><tbody>
    <?php foreach ($recent as $m): ?><tr class="<?= $m['is_read'] ? '' : 'unread' ?>"><td><?= e($m['name']) ?></td><td><?= e(truncate((string) $m['body'], 80)) ?></td><td><?= e(format_date($m['created_at'], 'j M, H:i')) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <?php else: ?><p class="muted">No messages yet.</p><?php endif; ?>
</div>

<?php if ($hasSamples): ?>
<div class="card">
  <h3>Sample jobs are on your site</h3>
  <p>The site came with <?= $hasSamples ?> example jobs so you can see how it looks. Remove them once you have posted your own.</p>
  <form method="post" action="jobs.php" data-confirm="Delete all sample jobs?">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete_samples">
    <button class="btn btn-ghost btn-sm" type="submit">Delete sample jobs</button>
  </form>
</div>
<?php endif; ?>
<?php admin_foot();
