<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user()) {
    csrf_verify();
    audit('logout');
    do_logout();
}
redirect(admin_url('login.php'));
