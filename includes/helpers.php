<?php
/** General helpers shared by the public site and the admin panel. */

const JOB_TYPES = ['Full-time', 'Part-time', 'Contract', 'Temporary', 'Internship'];

/* ---------- Output & URLs ---------- */

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return cfg('trust_proxy') && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    // Plain string handling (not dirname) so Windows backslashes can never leak into URLs.
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $pos = strrpos($script, '/');
    $dir = $pos === false ? '' : substr($script, 0, $pos);      // folder of the running script
    if (defined('IN_ADMIN')) {
        $pos = strrpos($dir, '/');
        $dir = $pos === false ? '' : substr($dir, 0, $pos);     // one level up from /admin
    }
    return $base = rtrim($dir, '/');
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function absolute_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https' : 'http') . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/* ---------- Settings ---------- */

function settings_all(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        foreach (db()->query('SELECT name, value FROM settings') as $r) {
            $cache[$r['name']] = $r['value'];
        }
    }
    return $cache;
}

function setting(string $name, string $default = ''): string
{
    $all = settings_all();
    return isset($all[$name]) && $all[$name] !== '' ? (string) $all[$name] : $default;
}

function set_setting(string $name, string $value): void
{
    $st = db()->prepare('INSERT OR REPLACE INTO settings (name, value) VALUES (?, ?)');
    $st->execute([$name, $value]);
    settings_all(true);
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_html(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = $f['type'] === 'ok' ? 'flash-ok' : 'flash-err';
        $out .= '<div class="flash ' . $cls . '" role="status">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Your form expired or was invalid. Please go back, refresh the page and try again.');
    }
}

/* ---------- Text helpers ---------- */

function inline_fmt(string $s): string
{
    return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($s));
}

/** Safe mini-formatter: blank line = paragraph, "## " = heading, "- " = bullet, **bold**. */
function render_text(?string $text): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));
    if ($text === '') {
        return '';
    }
    $out = '';
    foreach (preg_split("/\n{2,}/", $text) as $block) {
        $lines = explode("\n", trim($block));
        if (preg_match('/^##\s+(.+)$/', $lines[0], $m)) {
            $out .= '<h3>' . inline_fmt($m[1]) . '</h3>';
            array_shift($lines);
            if (!$lines) {
                continue;
            }
        }
        $isList = true;
        foreach ($lines as $l) {
            if (!preg_match('/^\s*[-*•]\s+/u', $l)) {
                $isList = false;
                break;
            }
        }
        if ($isList) {
            $out .= '<ul>';
            foreach ($lines as $l) {
                $out .= '<li>' . inline_fmt(preg_replace('/^\s*[-*•]\s+/u', '', $l)) . '</li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<p>' . implode('<br>', array_map('inline_fmt', $lines)) . '</p>';
        }
    }
    return $out;
}

/** Plain-text excerpt from formatted text (drops ## headings, bullets, **bold**). */
function plain_excerpt(?string $text, int $len): string
{
    $t = preg_replace('/^\s*##\s+.*$/m', ' ', (string) $text);
    $t = preg_replace('/^\s*[-*•]\s+/m', '', $t);
    $t = str_replace('**', '', $t);
    return truncate(trim(preg_replace('/\s+/', ' ', $t)), $len);
}

function truncate(string $s, int $len): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($s, 0, $len, '…', 'UTF-8');
    }
    return strlen($s) > $len ? substr($s, 0, $len - 1) . '…' : $s;
}

function format_date(?string $s, string $fmt = 'j M Y'): string
{
    if (!$s) {
        return '';
    }
    $t = strtotime($s);
    return $t ? date($fmt, $t) : '';
}

function str_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

/* ---------- Client info ---------- */

function client_ip(): string
{
    if (cfg('trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_hash(): string
{
    return substr(hash_hmac('sha256', client_ip(), setting('_secret', 'x')), 0, 24);
}

function is_bot(): bool
{
    return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python-requests/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

/* ---------- Audit log ---------- */

function audit(string $action, string $detail = ''): void
{
    $u = function_exists('current_user') ? current_user() : null;
    $st = db()->prepare('INSERT INTO audit_log (user_id, username, action, detail, created_at) VALUES (?, ?, ?, ?, ?)');
    $st->execute([$u['id'] ?? null, $u['username'] ?? 'system', $action, $detail, date('Y-m-d H:i:s')]);
}

/* ---------- Jobs & WhatsApp ---------- */

function job_ref(int $id): string
{
    return sprintf('4S-%04d', $id);
}

function job_url(int $id): string
{
    return url('job.php?id=' . $id);
}

function find_job(int $id, bool $liveOnly = false): ?array
{
    $sql = 'SELECT j.*, c.name AS category_name FROM jobs j LEFT JOIN categories c ON c.id = j.category_id WHERE j.id = ?';
    $params = [$id];
    if ($liveOnly) {
        $sql .= " AND j.status = 'published' AND (j.deadline IS NULL OR j.deadline = '' OR j.deadline >= ?)";
        $params[] = date('Y-m-d');
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function whatsapp_number(): string
{
    $n = preg_replace('/\D+/', '', setting('whatsapp_number', ''));
    return ltrim((string) $n, '0');
}

function wa_link(?array $job = null): string
{
    $number = whatsapp_number();
    if ($job) {
        $tpl = setting('whatsapp_message', 'Hello, I would like to apply for {job_title} (Ref: {job_ref}).');
        $text = strtr($tpl, [
            '{job_title}' => $job['title'],
            '{job_ref}'   => job_ref((int) $job['id']),
            '{job_url}'   => absolute_url('job.php?id=' . (int) $job['id']),
            '{site_name}' => setting('site_name', '4SONS'),
        ]);
    } else {
        $text = setting('whatsapp_general', 'Hello, I would like to make an inquiry.');
    }
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($text);
}

/* ---------- Pagination ---------- */

function pager_html(int $page, int $pages, array $params = []): string
{
    if ($pages <= 1) {
        return '';
    }
    $link = function (int $p, string $label, string $cls = '') use ($params) {
        $q = http_build_query(array_merge($params, ['page' => $p]));
        return '<a class="' . $cls . '" href="?' . e($q) . '">' . $label . '</a>';
    };
    $out = '<nav class="pager" aria-label="Pagination">';
    if ($page > 1) {
        $out .= $link($page - 1, '&larr; Prev');
    }
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $out .= $i === $page ? '<span class="current">' . $i . '</span>' : $link($i, (string) $i);
    }
    if ($page < $pages) {
        $out .= $link($page + 1, 'Next &rarr;');
    }
    return $out . '</nav>';
}

function current_page(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

/* ---------- Security headers & errors ---------- */

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function fail_page(int $code, string $title, string $message): void
{
    http_response_code($code);
    $pageTitle = $title;
    include APP_ROOT . '/includes/header.php';
    echo '<section class="section"><div class="container narrow center"><h1>' . e($title) . '</h1><p class="lead">' . e($message) . '</p><p><a class="btn btn-primary" href="' . e(url('index.php')) . '">Back to home</a></p></div></section>';
    include APP_ROOT . '/includes/footer.php';
    exit;
}
