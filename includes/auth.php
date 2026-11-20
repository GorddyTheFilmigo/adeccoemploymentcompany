<?php
/** Sessions, login throttling and role checks. */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = APP_ROOT . '/data/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) cfg('session_idle', 3600));
    session_name(cfg('session_name', 'fsons_sid'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $now = time();
    if (isset($_SESSION['last_seen']) && ($now - $_SESSION['last_seen']) > cfg('session_idle', 3600)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = $now;
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . setting('_secret'));
        if (($_SESSION['fp'] ?? '') === $fp) {
            $st = db()->prepare('SELECT id, username, role FROM users WHERE id = ?');
            $st->execute([$_SESSION['uid']]);
            $user = $st->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(bool $adminOnly = false): array
{
    $u = current_user();
    if (!$u) {
        redirect(admin_url('login.php'));
    }
    if ($adminOnly && $u['role'] !== 'admin') {
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
    return $u;
}

function login_locked(string $username): bool
{
    $since = time() - (int) cfg('login_window', 900);
    $max = (int) cfg('login_max_attempts', 5);
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?');
    $st->execute([ip_hash(), $since]);
    if ((int) $st->fetchColumn() >= $max) {
        return true;
    }
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE username = ? AND created_at > ?');
    $st->execute([strtolower($username), $since]);
    return (int) $st->fetchColumn() >= $max * 3;
}

/** Returns null on success or an error message. */
function attempt_login(string $username, string $password): ?string
{
    $username = trim($username);
    if (login_locked($username)) {
        return 'Too many failed attempts. Please wait 15 minutes and try again.';
    }
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();

    // Always run a hash check so timing does not reveal whether the user exists.
    $hash = $row['password_hash'] ?? password_hash('dummy-password', PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash) && $row;

    if (!$ok) {
        db()->prepare('INSERT INTO login_attempts (ip, username, created_at) VALUES (?, ?, ?)')
            ->execute([ip_hash(), strtolower($username), time()]);
        return 'Incorrect username or password.';
    }

    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $row['id'];
    $_SESSION['fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . setting('_secret'));
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $row['id']]);
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([ip_hash()]);
    db()->prepare('DELETE FROM login_attempts WHERE created_at < ?')->execute([time() - 86400]);
    return null;
}

function do_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function password_problem(string $pw): ?string
{
    if (strlen($pw) < 10) {
        return 'Password must be at least 10 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        return 'Password must contain both letters and numbers.';
    }
    return null;
}
