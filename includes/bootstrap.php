<?php
/** Loaded first by every page. */

require __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', cfg('debug') ? '1' : '0');
ini_set('log_errors', '1');

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';

set_exception_handler(function (Throwable $e) {
    error_log('[ADECCO] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo cfg('debug')
        ? '<pre>' . htmlspecialchars((string) $e) . '</pre>'
        : '<!doctype html><meta charset="utf-8"><title>Error</title><body style="font-family:sans-serif;padding:3rem"><h1>Something went wrong</h1><p>Please try again in a moment.</p></body>';
});

$tz = setting('timezone', 'UTC');
date_default_timezone_set(in_array($tz, timezone_identifiers_list(), true) ? $tz : 'UTC');

send_security_headers();
start_session();
