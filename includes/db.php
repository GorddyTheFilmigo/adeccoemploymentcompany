<?php
/** SQLite connection + automatic schema creation. */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = cfg('db_path');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    db_migrate($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void
{
    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version >= 1) {
        return;
    }

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        name  TEXT PRIMARY KEY,
        value TEXT
    );
    CREATE TABLE IF NOT EXISTS users (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        username      TEXT NOT NULL UNIQUE COLLATE NOCASE,
        password_hash TEXT NOT NULL,
        role          TEXT NOT NULL DEFAULT 'editor',
        created_at    TEXT NOT NULL,
        last_login    TEXT
    );
    CREATE TABLE IF NOT EXISTS categories (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT NOT NULL UNIQUE COLLATE NOCASE,
        sort_order INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS jobs (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        title        TEXT NOT NULL,
        category_id  INTEGER REFERENCES categories(id) ON DELETE SET NULL,
        employer     TEXT,
        location     TEXT,
        job_type     TEXT NOT NULL DEFAULT 'Full-time',
        salary       TEXT,
        summary      TEXT,
        description  TEXT NOT NULL,
        requirements TEXT,
        benefits     TEXT,
        deadline     TEXT,
        status       TEXT NOT NULL DEFAULT 'published',
        featured     INTEGER NOT NULL DEFAULT 0,
        is_sample    INTEGER NOT NULL DEFAULT 0,
        views        INTEGER NOT NULL DEFAULT 0,
        created_at   TEXT NOT NULL,
        updated_at   TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status);
    CREATE INDEX IF NOT EXISTS idx_jobs_category ON jobs(category_id);
    CREATE TABLE IF NOT EXISTS apply_clicks (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id     INTEGER REFERENCES jobs(id) ON DELETE SET NULL,
        job_title  TEXT,
        ip_hash    TEXT,
        created_at TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_clicks_job ON apply_clicks(job_id);
    CREATE INDEX IF NOT EXISTS idx_clicks_date ON apply_clicks(created_at);
    CREATE TABLE IF NOT EXISTS messages (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT NOT NULL,
        email      TEXT,
        phone      TEXT,
        subject    TEXT,
        body       TEXT NOT NULL,
        is_read    INTEGER NOT NULL DEFAULT 0,
        ip_hash    TEXT,
        created_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS pages (
        slug       TEXT PRIMARY KEY,
        title      TEXT NOT NULL,
        content    TEXT NOT NULL,
        updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS login_attempts (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        ip         TEXT NOT NULL,
        username   TEXT,
        created_at INTEGER NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip, created_at);
    CREATE TABLE IF NOT EXISTS audit_log (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER,
        username   TEXT,
        action     TEXT NOT NULL,
        detail     TEXT,
        created_at TEXT NOT NULL
    );
    ");

    db_seed($pdo);
    $pdo->exec('PRAGMA user_version = 1');
}

function db_seed(PDO $pdo): void
{
    $now = date('Y-m-d H:i:s');

    // ---- Default settings (all editable in Admin > Settings) -------------
    $settings = [
        '_secret'           => bin2hex(random_bytes(32)),
        'site_name'         => '4SONS',
        'tagline'           => 'Employment Company',
        'meta_description'  => '4SONS Employment Company connects job seekers with trusted employers. Browse current vacancies and apply directly on WhatsApp.',
        'whatsapp_number'   => '254713639172',
        'whatsapp_message'  => "Hello 4SONS, I would like to apply for the position of {job_title} (Ref: {job_ref}).\n{job_url}",
        'whatsapp_general'  => 'Hello 4SONS, I would like to make an inquiry.',
        'contact_phone'     => '+254 713 639 172',
        'contact_email'     => '',
        'address'           => '',
        'hours'             => 'Mon - Fri, 8:00 - 17:00',
        'hero_title'        => 'Find work that fits your life.',
        'hero_subtitle'     => 'Browse current vacancies, tap Apply, and chat with our team directly on WhatsApp. No long forms, no waiting around.',
        'notice'            => 'Safety tip: always confirm job details directly with our team on WhatsApp, and never send money to anyone who promises to guarantee you a job.',
        'footer_text'       => 'We connect job seekers with employers and help both sides move forward with clarity and respect.',
        'facebook'          => '',
        'instagram'         => '',
        'linkedin'          => '',
        'x_twitter'         => '',
        'accent_color'      => '#f0a41b',
        'timezone'          => 'Africa/Nairobi',
        'logo'              => '',
    ];
    $st = $pdo->prepare('INSERT OR IGNORE INTO settings (name, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    // ---- Categories -------------------------------------------------------
    $cats = ['Hospitality & Catering', 'Healthcare & Care Work', 'Construction & Trades', 'Logistics & Warehousing',
             'Domestic & Household', 'Office & Administration', 'Sales & Customer Service', 'Security & Facilities'];
    $st = $pdo->prepare('INSERT OR IGNORE INTO categories (name, sort_order) VALUES (?, ?)');
    foreach ($cats as $i => $c) {
        $st->execute([$c, $i]);
    }

    // ---- Page content -----------------------------------------------------
    $pages = [
        'about' => ['About us', "## Who we are\n\n4SONS is an employment company that connects job seekers with employers. We keep the process simple: you see the vacancy, you tap Apply, and you speak to a real person on WhatsApp.\n\n## What we believe\n\n- Every candidate deserves clear, honest information about a role.\n- Employers deserve people who are prepared and well matched.\n- Good communication saves everyone time.\n\n## How to reach us\n\nUse the Apply button on any vacancy, or the WhatsApp button at the bottom of the page, and our team will respond as soon as possible."],
        'services' => ['Our services', "## For job seekers\n\n- Job matching based on your skills and experience\n- CV and application guidance\n- Interview preparation tips\n- Direct support through WhatsApp\n\n## For employers\n\n- Recruitment and candidate screening\n- Shortlisting of suitable applicants\n- Temporary and contract staffing\n- Support through the hiring process\n\n## Interested?\n\nGet in touch through the contact page or WhatsApp and tell us what you need."],
        'privacy' => ['Privacy policy', "## What we collect\n\nWhen you use the contact form we store the details you submit (name, phone, email and message) so that we can respond to you.\n\nWhen you tap an Apply button we record that a click happened for statistics. We do not store your name or phone number from that click.\n\n## WhatsApp\n\nConversations that happen on WhatsApp are handled through WhatsApp and are subject to its own privacy terms.\n\n## How we use your information\n\nWe use your information only to respond to you and to match you with suitable vacancies. We do not sell your personal information.\n\n## Contact\n\nIf you want your information removed, contact us using the details on the contact page."],
    ];
    $st = $pdo->prepare('INSERT OR IGNORE INTO pages (slug, title, content, updated_at) VALUES (?, ?, ?, ?)');
    foreach ($pages as $slug => [$title, $content]) {
        $st->execute([$slug, $title, $content, $now]);
    }

    // ---- Sample jobs (flagged so the admin can remove them in one click) -----
    $catIds = [];
    foreach ($pdo->query('SELECT id, name FROM categories') as $r) {
        $catIds[$r['name']] = (int) $r['id'];
    }
    $samples = [
        ['Warehouse Associate', 'Logistics & Warehousing', 'Nairobi', 'Full-time', 'Negotiable', 1,
         'Pick, pack and prepare orders in a busy warehouse.',
         "## About the role\n\nYou will receive, store and dispatch goods, keep the work area safe and tidy, and help the team meet daily targets.\n\n## Responsibilities\n\n- Receive and check deliveries\n- Pick and pack orders accurately\n- Load and unload vehicles\n- Keep stock records up to date",
         "- Ability to lift and move goods safely\n- Reliable and punctual\n- Previous warehouse experience is an advantage", "- Training provided\n- Opportunity for permanent contract"],
        ['Front Desk Receptionist', 'Office & Administration', 'Nairobi', 'Full-time', 'Negotiable', 1,
         'Welcome visitors, answer calls and support the office team.',
         "## About the role\n\nYou will be the first point of contact for guests and callers, and will support day-to-day administration.\n\n## Responsibilities\n\n- Greet and direct visitors\n- Answer phones and manage inquiries\n- Maintain schedules and records",
         "- Good communication skills in English\n- Basic computer skills\n- Friendly, professional manner", "- Friendly working environment"],
        ['Restaurant Waiter / Waitress', 'Hospitality & Catering', 'Mombasa', 'Full-time', 'Negotiable', 0,
         'Provide friendly table service in a busy restaurant.',
         "## About the role\n\nTake orders, serve food and drinks and make sure guests have a great experience.\n\n## Responsibilities\n\n- Welcome and seat guests\n- Take and serve orders\n- Keep the dining area clean",
         "- Customer-service attitude\n- Ability to work shifts", "- Tips\n- Meals on duty"],
        ['Care Assistant', 'Healthcare & Care Work', 'Nakuru', 'Full-time', 'Negotiable', 0,
         'Support clients with daily living in a caring environment.',
         "## About the role\n\nAssist clients with personal care, meals and mobility while treating each person with dignity.\n\n## Responsibilities\n\n- Support with daily routines\n- Report changes in a client's condition\n- Keep a clean, safe environment",
         "- Patient and compassionate\n- Care or nursing training is an advantage", "- Ongoing training"],
        ['Site Mason', 'Construction & Trades', 'Kisumu', 'Contract', 'Negotiable', 0,
         'Skilled mason needed for building projects.',
         "## About the role\n\nWork with the site team on walls, foundations and finishes.\n\n## Responsibilities\n\n- Read and follow plans\n- Lay blocks and bricks to standard\n- Follow site safety rules",
         "- Proven masonry experience\n- Own basic hand tools", "- Paid on schedule"],
        ['Customer Service Agent', 'Sales & Customer Service', 'Remote', 'Part-time', 'Negotiable', 0,
         'Help customers by phone and chat.',
         "## About the role\n\nRespond to customer questions, solve problems and keep records of each conversation.\n\n## Responsibilities\n\n- Answer calls and messages\n- Log and follow up on requests\n- Escalate complex cases",
         "- Clear communication\n- Basic computer skills", "- Flexible hours"],
    ];
    $st = $pdo->prepare('INSERT INTO jobs (title, category_id, location, job_type, salary, featured, is_sample, summary, description, requirements, benefits, status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, \'published\', ?, ?)');
    foreach ($samples as $s) {
        [$title, $cat, $loc, $type, $salary, $feat, $summary, $desc, $req, $ben] = $s;
        $st->execute([$title, $catIds[$cat] ?? null, $loc, $type, $salary, $feat, $summary, $desc, $req, $ben, $now, $now]);
    }
}
