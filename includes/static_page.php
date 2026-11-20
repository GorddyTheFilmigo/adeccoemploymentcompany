<?php
/** Renders an editable content page. Set $slug and $active before including. */
require_once __DIR__ . '/bootstrap.php';

$st = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$st->execute([$slug]);
$pg = $st->fetch();
if (!$pg) {
    fail_page(404, 'Page not found', 'We could not find that page.');
}
$pageTitle = $pg['title'];
include __DIR__ . '/header.php';
?>
<section class="page-head"><div class="container"><h1><?= e($pg['title']) ?></h1></div></section>
<section class="section">
  <div class="container narrow">
    <div class="prose"><?= render_text($pg['content']) ?></div>
    <?php if ($slug !== 'privacy'): ?>
      <p class="cta-inline">
        <a class="btn btn-wa" href="<?= e(url('apply.php')) ?>" target="_blank" rel="nofollow noopener">Chat with us on WhatsApp</a>
        <a class="btn btn-ghost" href="<?= e(url('jobs.php')) ?>">Browse jobs</a>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
