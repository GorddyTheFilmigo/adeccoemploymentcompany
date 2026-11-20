<?php
require __DIR__ . '/_init.php';
ensure_setup_done();
require_login(true); // admins only: this page controls the WhatsApp number

$fields = [
    'site_name' => 'Site name', 'tagline' => 'Tagline', 'meta_description' => 'Search engine description',
    'whatsapp_number' => 'WhatsApp number', 'whatsapp_message' => 'Apply message template', 'whatsapp_general' => 'General chat message',
    'contact_phone' => 'Phone', 'contact_email' => 'Email', 'address' => 'Address', 'hours' => 'Opening hours',
    'hero_title' => 'Home headline', 'hero_subtitle' => 'Home sub-headline', 'notice' => 'Notice bar text',
    'footer_text' => 'Footer text', 'facebook' => 'Facebook URL', 'instagram' => 'Instagram URL', 'linkedin' => 'LinkedIn URL', 'x_twitter' => 'X URL',
    'accent_color' => 'Accent colour', 'timezone' => 'Timezone',
];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $new = [];
    foreach ($fields as $k => $_) {
        $new[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $new['whatsapp_number'] = preg_replace('/\D+/', '', $new['whatsapp_number']);
    $new['whatsapp_number'] = ltrim($new['whatsapp_number'], '0');
    if ($new['whatsapp_number'] === '' || strlen($new['whatsapp_number']) < 9 || strlen($new['whatsapp_number']) > 15) {
        $err[] = 'WhatsApp number must be in international format with country code, e.g. 254713639172.';
    }
    if ($new['site_name'] === '') {
        $err[] = 'Site name cannot be empty.';
    }
    if ($new['accent_color'] !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $new['accent_color'])) {
        $err[] = 'Accent colour must look like #f0a41b.';
    }
    if ($new['contact_email'] !== '' && !filter_var($new['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $err[] = 'Contact email is not valid.';
    }
    if (!in_array($new['timezone'] ?: 'UTC', timezone_identifiers_list(), true)) {
        $err[] = 'Unknown timezone. Example: Africa/Nairobi.';
    }
    foreach (['facebook', 'instagram', 'linkedin', 'x_twitter'] as $k) {
        if ($new[$k] !== '' && !preg_match('#^https?://#i', $new[$k])) {
            $err[] = ucfirst($k) . ' link must start with http:// or https://';
        }
    }

    // Logo upload (optional)
    $logo = setting('logo');
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['logo'];
        $info = @getimagesize($f['tmp_name']);
        $allowed = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
        if ($f['size'] > 1024 * 1024) {
            $err[] = 'Logo must be under 1 MB.';
        } elseif (!$info || !isset($allowed[$info[2]])) {
            $err[] = 'Logo must be a PNG, JPG or WebP image.';
        } elseif (!$err) {
            $dir = cfg('upload_dir');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $name = 'logo-' . bin2hex(random_bytes(6)) . '.' . $allowed[$info[2]];
            if (move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
                if ($logo && is_file($dir . '/' . $logo)) {
                    @unlink($dir . '/' . $logo);
                }
                $logo = $name;
            } else {
                $err[] = 'Could not save the logo. Check that the uploads folder is writable.';
            }
        }
    }
    if (!empty($_POST['remove_logo']) && $logo) {
        @unlink(cfg('upload_dir') . '/' . $logo);
        $logo = '';
    }

    if (!$err) {
        foreach ($new as $k => $v) {
            set_setting($k, $v);
        }
        set_setting('logo', $logo);
        audit('settings.update', 'whatsapp=' . $new['whatsapp_number']);
        flash('ok', 'Settings saved.');
        redirect('settings.php');
    }
    $S = $new;
} else {
    $S = [];
    foreach ($fields as $k => $_) {
        $S[$k] = setting($k);
    }
}

admin_head('Settings', 'settings');
$inp = fn($k, $type = 'text') => '<input type="' . $type . '" name="' . $k . '" value="' . e($S[$k] ?? '') . '">';
?>
<?php foreach ($err as $x): ?><div class="flash flash-err"><?= e($x) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_field() ?>

  <fieldset class="card form"><legend>WhatsApp apply button</legend>
    <label>WhatsApp number (country code, digits only)<?= $inp('whatsapp_number') ?></label>
    <p class="muted small">Example: 254713639172. Every Apply button on the site opens a chat with this number.</p>
    <label>Apply message template<textarea name="whatsapp_message" rows="4"><?= e($S['whatsapp_message']) ?></textarea></label>
    <p class="muted small">Placeholders: <code>{job_title}</code> <code>{job_ref}</code> <code>{job_url}</code> <code>{site_name}</code></p>
    <label>General chat message (WhatsApp button without a job)<?= $inp('whatsapp_general') ?></label>
  </fieldset>

  <fieldset class="card form"><legend>Brand</legend>
    <div class="two"><label>Site name<?= $inp('site_name') ?></label><label>Tagline<?= $inp('tagline') ?></label></div>
    <label>Search engine description<textarea name="meta_description" rows="2"><?= e($S['meta_description']) ?></textarea></label>
    <div class="two">
      <label>Accent colour<input type="color" name="accent_color" value="<?= e(preg_match('/^#[0-9a-fA-F]{6}$/', $S['accent_color']) ? $S['accent_color'] : '#f0a41b') ?>"></label>
      <label>Timezone<?= $inp('timezone') ?></label>
    </div>
    <label>Logo (PNG/JPG/WebP, max 1 MB; leave empty to keep current)<input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></label>
    <?php if (setting('logo')): ?><label class="check"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label><?php endif; ?>
  </fieldset>

  <fieldset class="card form"><legend>Home page</legend>
    <label>Headline<?= $inp('hero_title') ?></label>
    <label>Sub-headline<textarea name="hero_subtitle" rows="2"><?= e($S['hero_subtitle']) ?></textarea></label>
    <label>Notice bar (shown under headings; leave empty to hide)<textarea name="notice" rows="2"><?= e($S['notice']) ?></textarea></label>
    <label>Footer text<textarea name="footer_text" rows="2"><?= e($S['footer_text']) ?></textarea></label>
  </fieldset>

  <fieldset class="card form"><legend>Contact details</legend>
    <div class="two"><label>Phone<?= $inp('contact_phone') ?></label><label>Email<?= $inp('contact_email', 'email') ?></label></div>
    <div class="two"><label>Address<?= $inp('address') ?></label><label>Opening hours<?= $inp('hours') ?></label></div>
    <div class="two"><label>Facebook URL<?= $inp('facebook', 'url') ?></label><label>Instagram URL<?= $inp('instagram', 'url') ?></label></div>
    <div class="two"><label>LinkedIn URL<?= $inp('linkedin', 'url') ?></label><label>X URL<?= $inp('x_twitter', 'url') ?></label></div>
  </fieldset>

  <div class="actions"><button class="btn btn-primary" type="submit">Save settings</button></div>
</form>
<?php admin_foot();
