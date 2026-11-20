# 4SONS Employment Company – Website

A complete recruitment website with a job board, WhatsApp **Apply** buttons and a secure admin panel.
Built with **PHP 8+ and SQLite**. There is no database server to install; the whole database is one file.

## What you get

**Public site:** Home, Find Jobs (search, category, location and type filters, pagination), Job detail pages,
Services, About, Contact form, Privacy policy, floating WhatsApp button, mobile friendly.

**Apply button:** opens WhatsApp chat with your number with a ready message
("I would like to apply for {job title} (Ref 4S-0001)"). Each click is counted so you can see which jobs get interest.
The number is set in **Admin > Settings** (currently `254713639172`), so you never need to touch code to change it.

**Admin panel (`/admin`):** dashboard with stats and chart, add/edit/publish/draft/close/feature/delete jobs
(bulk actions), categories, contact messages inbox, edit About/Services/Privacy text, settings (WhatsApp number and
message, brand name, colour, logo, contact details, social links, home page wording), user accounts
(Admin / Editor roles), change password, activity log.

## Install (shared hosting / cPanel) – about 5 minutes

1. Make sure your host runs **PHP 8.0 or newer** with `pdo_sqlite` (almost all do).
2. Upload everything in this folder to `public_html` (or a sub-folder) and extract it.
3. Make sure the folders `data/` and `uploads/` are **writable** by PHP (permissions 755, or 775 if needed).
4. Open `https://yourdomain.com/admin/` in your browser. On first visit you are asked to **create your admin username and password**.
   There is no default password. This page disappears once an account exists.
5. Go to **Settings** and check your WhatsApp number, contact details and logo. Then add your jobs
   and delete the sample jobs (button on the dashboard).
6. Turn on your host's free SSL certificate, then uncomment the HTTPS redirect lines at the bottom of `.htaccess`.

### Local test
```
php -S localhost:8000
```
then open http://localhost:8000

### Nginx (VPS)
`.htaccess` is ignored by Nginx, so add rules that deny access to private folders:
```
location ~ ^/(data|includes)/ { deny all; }
location ~* \.(sqlite|sqlite3|db|log|md)$ { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```
Also set `'trust_proxy' => true` in `config.local.php` only if you are behind Cloudflare or a load balancer.

## Security built in

- Passwords hashed with bcrypt/Argon (`password_hash`); minimum 10 characters, letters and numbers.
- Login lock-out after 5 failed attempts (15 minutes); session ID regenerated on login; 60 minute idle auto-logout;
  session bound to the browser; HttpOnly + SameSite cookies (Secure on HTTPS).
- CSRF token on every form; every database query is parameterised (SQL injection safe);
  all output escaped (XSS safe); no raw HTML accepted from the admin.
- Strict Content-Security-Policy, clickjacking and MIME-sniff protection, admin pages marked `noindex`.
- Logo uploads verified as real PNG/JPG/WebP, renamed randomly, and scripts blocked in `uploads/`.
- Applicant privacy: Apply clicks store only a salted, hashed IP (no names or phone numbers).
- Contact form has a honeypot and rate limit against spam.
- Admin actions are written to an activity log. Editors cannot change settings or users.

### Strongly recommended
- Move the database **outside** `public_html`: copy `config.sample.php` to `config.local.php`, set `db_path`
  to e.g. `/home/youraccount/4sons-data/4sons.sqlite`, and move the `data/` contents there.
- **Back up** `data/4sons.sqlite` and `uploads/` regularly (download them from your host).
- Keep PHP updated through your hosting panel.

## Moving to PostgreSQL / MySQL later
SQLite comfortably handles tens of thousands of visitors a month. If you outgrow it, the schema is in
`includes/db.php` and all queries use standard PDO, so migration is straightforward for a developer.

## Running it responsibly
Job seekers will trust this site with their time and personal information. Display your business
registration details, state clearly that you **never charge applicants** fees to get a job (the safety notice in
Settings does this; edit as needed), and only post real vacancies. Recruiters who take money for fake or
unconfirmed jobs harm people and risk prosecution.

## File map
```
index.php jobs.php job.php apply.php contact.php about.php services.php privacy.php   public pages
admin/        admin panel          includes/   code, config, database (blocked from web)
assets/       css and js           data/       SQLite database and sessions (blocked from web)
uploads/      logo uploads         config.sample.php  optional overrides
```
