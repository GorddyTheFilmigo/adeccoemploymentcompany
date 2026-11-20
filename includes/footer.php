</main>
<?php
$siteName = setting('site_name', 'ADECCO');
$socials = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'x_twitter' => 'X'];
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="footer-brand"><?= e($siteName) ?> <span><?= e(setting('tagline', 'Employment Company')) ?></span></div>
      <p><?= e(setting('footer_text')) ?></p>
    </div>
    <div>
      <h4>Explore</h4>
      <ul>
        <li><a href="<?= e(url('jobs.php')) ?>">Find jobs</a></li>
        <li><a href="<?= e(url('services.php')) ?>">Our services</a></li>
        <li><a href="<?= e(url('about.php')) ?>">About us</a></li>
        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
        <li><a href="<?= e(url('privacy.php')) ?>">Privacy policy</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul>
        <?php if (setting('contact_phone')): ?><li>Phone: <a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_email')): ?><li>Email: <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
        <?php if (setting('address')): ?><li><?= e(setting('address')) ?></li><?php endif; ?>
        <?php if (setting('hours')): ?><li><?= e(setting('hours')) ?></li><?php endif; ?>
      </ul>
      <?php $links = array_filter(array_map(fn($k) => setting($k), array_keys($socials))); if ($links): ?>
        <p class="socials">
          <?php foreach ($socials as $k => $label): if (setting($k) && preg_match('#^https?://#i', setting($k))): ?>
            <a href="<?= e(setting($k)) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
          <?php endif; endforeach; ?>
        </p>
      <?php endif; ?>
    </div>
  </div>
  <div class="container footer-bottom">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</div>
</footer>

<a class="wa-float" href="<?= e(url('apply.php')) ?>" target="_blank" rel="nofollow noopener" aria-label="Chat with us on WhatsApp">
  <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M12.04 2a9.9 9.9 0 0 0-8.5 14.95L2 22l5.2-1.5A9.94 9.94 0 1 0 12.04 2Zm0 18.1c-1.5 0-2.9-.4-4.1-1.1l-.3-.2-3.1.9.9-3-.2-.3a8.1 8.1 0 1 1 6.8 3.7Zm4.5-6.1c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1l-.7.9c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.8 4.5 3.9 1.7.7 2.3.8 3.2.6.5-.1 1.4-.6 1.6-1.2.2-.6.2-1.1.1-1.2l-.5-.3Z"/></svg>
</a>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
