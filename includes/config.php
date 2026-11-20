<?php
/**
 * ADECCO – default configuration.
 * To change anything, copy config.sample.php to config.local.php and edit it there.
 */
define('APP_ROOT', dirname(__DIR__));

$CFG = [
    // SQLite database file. For best security keep it OUTSIDE the public web folder
    // and point to it from config.local.php (see README).
    'db_path'            => APP_ROOT . '/data/ADECCO.sqlite',
    'session_name'       => 'fsons_sid',
    'session_idle'       => 3600,   // admin auto-logout after 60 min of inactivity
    'debug'              => false,  // never enable on a live site
    'trust_proxy'        => false,  // set true only if behind Cloudflare / a load balancer
    'login_max_attempts' => 5,      // failed logins allowed ...
    'login_window'       => 900,    // ... per 15 minutes before a temporary lock-out
    'upload_dir'         => APP_ROOT . '/uploads',
];

if (is_file(APP_ROOT . '/config.local.php')) {
    $local = require APP_ROOT . '/config.local.php';
    if (is_array($local)) {
        $CFG = array_merge($CFG, $local);
    }
}

function cfg(string $key, $default = null)
{
    global $CFG;
    return $CFG[$key] ?? $default;
}
