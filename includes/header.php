<?php
/** Public site header. Expects optional $pageTitle, $metaDesc, $active. */
$siteName = setting('site_name', 'ADECCO');
$tagline  = setting('tagline', 'Employment Company');
$pageTitle = $pageTitle ?? '';
$metaDesc  = $metaDesc ?? setting('meta_description');
$active    = $active ?? '';
$accent    = preg_match('/^#[0-9a-fA-F]{6}$/', setting('accent_color')) ? setting('accent_color') : '#f0a41b';
$logo      = setting('logo');
$nav = [
    'home'     => ['Home', 'index.php'],
    'jobs'     => ['Find Jobs', 'jobs.php'],
    'services' => ['Services', 'services.php'],
    'about'    => ['About', 'about.php'],
    'contact'  => ['Contact', 'contact.php'],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ? $pageTitle . ' | ' : '') . $siteName . ($pageTitle ? '' : ' ' . $tagline)) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta name="theme-color" content="#0f2f33">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230f2f33'/%3E%3Crect x='6' y='6' width='9' height='9' rx='2' fill='%23<?= e(ltrim($accent, '#')) ?>'/%3E%3Crect x='17' y='6' width='9' height='9' rx='2' fill='%23fff' opacity='.9'/%3E%3Crect x='6' y='17' width='9' height='9' rx='2' fill='%23fff' opacity='.9'/%3E%3Crect x='17' y='17' width='9' height='9' rx='2' fill='%23fff' opacity='.55'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<style>:root{--accent:<?= e($accent) ?>}</style>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="container header-row">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="<?= e($siteName) ?> home">
      <?php if ($logo && is_file(cfg('upload_dir') . '/' . $logo)): ?>
        <img class="brand-logo" src="<?= e(url('uploads/' . $logo)) ?>" alt="<?= e($siteName) ?>">
      <?php else: ?>
        <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        <span class="brand-text"><strong><?= e($siteName) ?></strong><small><?= e($tagline) ?></small></span>
      <?php endif; ?>
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="nav" aria-label="Menu"><span></span><span></span><span></span></button>
    <nav id="nav" class="nav" aria-label="Main">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-wa btn-sm nav-cta" href="<?= e(url('apply.php')) ?>" rel="nofollow noopener" target="_blank">WhatsApp us</a>
    </nav>
  </div>
</header>
<main id="main">
<?= flash_html() ?>
