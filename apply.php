<?php
/**
 * Apply button target. Logs the click (anonymously) and redirects to WhatsApp.
 * The WhatsApp number and message come from Admin > Settings.
 */
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$job = null;

if ($id) {
    $job = find_job($id, true);
    if (!$job) {
        flash('err', 'Sorry, that vacancy is no longer open.');
        redirect(url('jobs.php'));
    }
    if (!is_bot() && !current_user()) {
        db()->prepare('INSERT INTO apply_clicks (job_id, job_title, ip_hash, created_at) VALUES (?, ?, ?, ?)')
            ->execute([$job['id'], $job['title'], ip_hash(), date('Y-m-d H:i:s')]);
    }
}

if (whatsapp_number() === '') {
    fail_page(503, 'Temporarily unavailable', 'Applications are not available right now. Please use the contact page.');
}

header('Cache-Control: no-store');
redirect(wa_link($job));
