<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/partials.php';

$active = 'home';
$today = date('Y-m-d');

$st = db()->prepare('SELECT j.*, c.name AS category_name ' . live_jobs_sql() . ' ORDER BY j.featured DESC, j.created_at DESC LIMIT 6');
$st->execute([':today' => $today]);
$featured = $st->fetchAll();

$st = db()->prepare('SELECT COUNT(*) ' . live_jobs_sql());
$st->execute([':today' => $today]);
$liveCount = (int) $st->fetchColumn();

$cats = db()->query("SELECT c.id, c.name, COUNT(j.id) AS n FROM categories c
    LEFT JOIN jobs j ON j.category_id = c.id AND j.status = 'published' AND (j.deadline IS NULL OR j.deadline = '' OR j.deadline >= '" . $today . "')
    GROUP BY c.id ORDER BY c.sort_order, c.name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <p class="eyebrow"><?= e(setting('site_name', 'ADECCO')) ?> &middot; <?= e(setting('tagline', 'Employment Company')) ?></p>
      <h1><?= e(setting('hero_title', 'Find work that fits your life.')) ?></h1>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>
      <form class="search-card" action="<?= e(url('jobs.php')) ?>" method="get">
        <label><span class="sr">Keyword</span><input type="search" name="q" placeholder="Job title or keyword"></label>
        <label><span class="sr">Category</span>
          <select name="category">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label><span class="sr">Location</span><input type="text" name="location" placeholder="Location"></label>
        <button class="btn btn-primary" type="submit">Search jobs</button>
      </form>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="block b1"></div><div class="block b2"></div><div class="block b3"></div>
      <div class="block b4"><strong><?= $liveCount ?></strong><span>open vacanc<?= $liveCount === 1 ? 'y' : 'ies' ?></span></div>
    </div>
  </div>
</section>
<?= notice_html() ?>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Latest vacancies</h2>
      <a href="<?= e(url('jobs.php')) ?>">See all jobs &rarr;</a>
    </div>
    <?php if ($featured): ?>
      <div class="grid-3"><?php foreach ($featured as $j) echo job_card($j); ?></div>
    <?php else: ?>
      <p class="empty">No vacancies are open right now. Please check back soon, or message us on WhatsApp.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section alt">
  <div class="container">
    <div class="section-head"><h2>Browse by category</h2></div>
    <div class="cat-grid">
      <?php foreach ($cats as $c): ?>
        <a class="cat" href="<?= e(url('jobs.php?category=' . (int) $c['id'])) ?>">
          <strong><?= e($c['name']) ?></strong>
          <span><?= (int) $c['n'] ?> job<?= (int) $c['n'] === 1 ? '' : 's' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><h2>How it works</h2></div>
    <ol class="steps">
      <li><span>1</span><h3>Find a vacancy</h3><p>Search by keyword, category or location and open any job to read the details.</p></li>
      <li><span>2</span><h3>Tap Apply</h3><p>The Apply button opens a WhatsApp chat with our team, with the job already filled in for you.</p></li>
      <li><span>3</span><h3>We guide you</h3><p>Our team replies, confirms what is needed and walks you through the next steps.</p></li>
    </ol>
  </div>
</section>

<section class="cta">
  <div class="container cta-row">
    <div>
      <h2>Hiring? Let us find your next team member.</h2>
      <p>Tell us what you need and we will help you shortlist suitable candidates.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('contact.php')) ?>">Talk to us</a>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
