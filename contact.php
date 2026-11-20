<?php
require __DIR__ . '/includes/bootstrap.php';

$active = 'contact';
$pageTitle = 'Contact us';
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (!empty($_POST['website'])) {            // honeypot: bots fill this in
        redirect(url('contact.php'));
    }
    if ($old['name'] === '' || str_len($old['name']) > 100) {
        $errors[] = 'Please enter your name.';
    }
    if ($old['email'] === '' && $old['phone'] === '') {
        $errors[] = 'Please give us an email address or phone number so we can reply.';
    }
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address does not look right.';
    }
    if (str_len($old['phone']) > 30 || ($old['phone'] !== '' && !preg_match('/^[0-9+()\-\s]{6,30}$/', $old['phone']))) {
        $errors[] = 'That phone number does not look right.';
    }
    if (str_len($old['body']) < 10 || str_len($old['body']) > 3000) {
        $errors[] = 'Please write a message of at least 10 characters.';
    }
    if (str_len($old['subject']) > 150) {
        $errors[] = 'Subject is too long.';
    }
    if (!$errors) {
        $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE ip_hash = ? AND created_at > ?');
        $st->execute([ip_hash(), date('Y-m-d H:i:s', time() - 3600)]);
        if ((int) $st->fetchColumn() >= 5) {
            $errors[] = 'You have sent several messages recently. Please try again later or use WhatsApp.';
        }
    }
    if (!$errors) {
        db()->prepare('INSERT INTO messages (name, email, phone, subject, body, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$old['name'], $old['email'], $old['phone'], $old['subject'], $old['body'], ip_hash(), date('Y-m-d H:i:s')]);
        flash('ok', 'Thank you. Your message has been received and we will get back to you soon.');
        redirect(url('contact.php'));
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container"><h1>Contact us</h1><p class="lead">Questions about a vacancy, or looking to hire? Reach out any way you like.</p></div>
</section>
<section class="section">
  <div class="container contact-layout">
    <div class="card">
      <h2>Send a message</h2>
      <?php foreach ($errors as $er): ?><div class="flash flash-err"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label>Your name *<input type="text" name="name" value="<?= e($old['name']) ?>" maxlength="100" required></label>
        <div class="two">
          <label>Email<input type="email" name="email" value="<?= e($old['email']) ?>" maxlength="150"></label>
          <label>Phone<input type="tel" name="phone" value="<?= e($old['phone']) ?>" maxlength="30"></label>
        </div>
        <label>Subject<input type="text" name="subject" value="<?= e($old['subject']) ?>" maxlength="150"></label>
        <label>Message *<textarea name="body" rows="6" maxlength="3000" required><?= e($old['body']) ?></textarea></label>
        <button class="btn btn-primary" type="submit">Send message</button>
      </form>
    </div>
    <aside class="side">
      <div class="card">
        <h3>Prefer WhatsApp?</h3>
        <p>Chat with our team directly. It is the fastest way to reach us.</p>
        <a class="btn btn-wa btn-block" href="<?= e(url('apply.php')) ?>" target="_blank" rel="nofollow noopener">Open WhatsApp</a>
      </div>
      <div class="card">
        <h3>Details</h3>
        <ul class="plain">
          <?php if (setting('contact_phone')): ?><li><strong>Phone</strong><br><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></li><?php endif; ?>
          <?php if (setting('contact_email')): ?><li><strong>Email</strong><br><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
          <?php if (setting('address')): ?><li><strong>Address</strong><br><?= e(setting('address')) ?></li><?php endif; ?>
          <?php if (setting('hours')): ?><li><strong>Hours</strong><br><?= e(setting('hours')) ?></li><?php endif; ?>
        </ul>
      </div>
    </aside>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
