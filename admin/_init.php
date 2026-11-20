<?php
/** Common start for every admin page. */
define('IN_ADMIN', true);
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function admin_head(string $title, string $nav = ''): void
{
    $u = current_user();
    $items = [
        'dash'       => ['Dashboard', 'index.php'],
        'jobs'       => ['Jobs', 'jobs.php'],
        'categories' => ['Categories', 'categories.php'],
        'messages'   => ['Messages', 'messages.php'],
        'pages'      => ['Page content', 'pages.php'],
    ];
    if ($u && $u['role'] === 'admin') {
        $items['settings'] = ['Settings', 'settings.php'];
        $items['users'] = ['Users', 'users.php'];
        $items['audit'] = ['Activity log', 'audit.php'];
    }
    $unread = $u ? (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn() : 0;
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> | <?= e(setting('site_name', '4SONS')) ?> Admin</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head><body class="admin">
<?php if ($u): ?>
<div class="adm-shell">
  <aside class="adm-side">
    <div class="adm-brand"><?= e(setting('site_name', '4SONS')) ?> <small>Admin</small></div>
    <nav>
      <?php foreach ($items as $k => [$label, $href]): ?>
        <a href="<?= e(admin_url($href)) ?>" class="<?= $nav === $k ? 'on' : '' ?>"><?= e($label) ?><?= $k === 'messages' && $unread ? ' <b class="badge">' . $unread . '</b>' : '' ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View website &nearr;</a>
      <a href="<?= e(admin_url('account.php')) ?>" class="<?= $nav === 'account' ? 'on' : '' ?>">My account</a>
    </nav>
    <form method="post" action="<?= e(admin_url('logout.php')) ?>" class="adm-logout">
      <?= csrf_field() ?><span><?= e($u['username']) ?> (<?= e($u['role']) ?>)</span>
      <button class="btn btn-ghost btn-sm" type="submit">Log out</button>
    </form>
  </aside>
  <main class="adm-main">
<?php else: ?>
<main class="adm-bare">
<?php endif; ?>
<h1 class="adm-title"><?= e($title) ?></h1>
<?= flash_html() ?>
<?php
}

function admin_foot(): void
{
    echo '</main>' . (current_user() ? '</div>' : '') . '<script src="' . e(asset('js/app.js')) . '" defer></script></body></html>';
}

/** Redirect to first-run setup if no admin exists yet. */
function ensure_setup_done(): void
{
    if ((int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        redirect(admin_url('setup.php'));
    }
}
