<?php
/* ==========================================================================
   ADMIN > WEBSITE SETTINGS (admin/settings.php)
   Site name / tagline / logo, the Retro TV Glow colour palette, the public
   toggles, contact details and footer text.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

/* pull every setting into one map (falls back to sane defaults) */
$defaults = [
    'site_name' => 'Audio Fire', 'site_tagline' => '', 'site_logo' => 'assets/music-logo.png',
    'admin_email' => 'muhammadmusabmehtab1@gmail.com',
    'theme_accent' => '#2E86C1', 'theme_dark' => '#34495E', 'theme_light' => '#ECF0F1',
    'theme_alert' => '#E74C3C', 'theme_name' => 'Retro TV Glow',
    'public_site' => '1', 'allow_signup' => '1', 'maintenance' => '0',
    'footer_text' => '', 'contact_email' => '', 'contact_phone' => '', 'contact_address' => '',
    'songs_per_page' => '24',
];
$S = $defaults;
if ($AF_DB) {
    foreach (af_all('SELECT setting_key, setting_value FROM settings') as $r) {
        $S[$r['setting_key']] = $r['setting_value'];
    }
}

$adminTitle = 'Website Settings';
$adminCrumb = 'System / Website settings';
$activeNav  = 'settings';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();

$PALETTE = [
    '#2E86C1' => 'Blue (primary)', '#34495E' => 'Slate (deep)', '#ECF0F1' => 'Paper (light)', '#E74C3C' => 'Red (alert)',
];
?>
<style>.content{display:grid;gap:18px}</style>

<form method="post" action="<?= e($base) ?>/admin/api.php" enctype="multipart/form-data" id="settingsForm">
  <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
  <input type="hidden" name="action" value="settings.save">
  <input type="hidden" name="redirect" value="1">
  <input type="hidden" name="back" value="admin/settings.php">

  <div class="card">
    <div class="card-h">
      <h2>Identity</h2>
      <span class="sub">Name, tagline and logo shown across the website</span>
    </div>
    <div class="grid2">
      <div class="field"><label>Website name</label>
        <input name="site_name" value="<?= e($S['site_name']) ?>"></div>
      <div class="field"><label>Tagline</label>
        <input name="site_tagline" value="<?= e($S['site_tagline']) ?>" placeholder="Discover, play and save the songs you love."></div>
      <div class="field"><label>Administrator email</label>
        <input type="email" name="admin_email" value="<?= e($S['admin_email']) ?>"></div>
      <div class="field"><label>Footer text</label>
        <input name="footer_text" value="<?= e($S['footer_text']) ?>" placeholder="© 2026 Audio Fire. All rights reserved."></div>
    </div>

    <div class="grid2" style="margin-top:15px">
      <div class="field"><label>Logo image</label>
        <label class="drop" data-drop>
          <input type="file" name="logo_file" accept="image/*">
          <span>Upload a new logo... (kept if left empty)</span>
        </label>
        <div style="display:flex;gap:14px;align-items:center;margin-top:12px">
          <span class="ph" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#2E86C1,#34495E);display:grid;place-items:center;overflow:hidden;box-shadow:var(--raise-s)">
            <img id="logoPrev" src="<?= e($S['site_logo']) ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;padding:7px" onerror="this.style.display='none'">
          </span>
          <div class="hint">Current: <span class="mono" id="logoPath"><?= e($S['site_logo']) ?></span></div>
        </div>
      </div>
      <div class="field"><label>...or paste a logo path</label>
        <input name="site_logo" value="<?= e($S['site_logo']) ?>" placeholder="assets/music-logo.png">
        <div class="hint">The logo appears in the admin sidebar and the public navbar.</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-h">
      <h2>Colour theme</h2>
      <span class="sub">Retro TV Glow palette - live preview as you pick</span>
      <span class="spacer"></span>
      <span class="tag"><?= e($S['theme_name']) ?></span>
    </div>

    <div class="grid2">
      <div class="field"><label>Primary blue</label>
        <input type="color" name="theme_accent" value="<?= e($S['theme_accent']) ?>" style="padding:5px;height:46px">
        <div class="hint mono" id="h-accent"><?= e($S['theme_accent']) ?></div></div>
      <div class="field"><label>Deep slate</label>
        <input type="color" name="theme_dark" value="<?= e($S['theme_dark']) ?>" style="padding:5px;height:46px">
        <div class="hint mono" id="h-dark"><?= e($S['theme_dark']) ?></div></div>
      <div class="field"><label>Paper light</label>
        <input type="color" name="theme_light" value="<?= e($S['theme_light']) ?>" style="padding:5px;height:46px">
        <div class="hint mono" id="h-light"><?= e($S['theme_light']) ?></div></div>
      <div class="field"><label>Alert red</label>
        <input type="color" name="theme_alert" value="<?= e($S['theme_alert']) ?>" style="padding:5px;height:46px">
        <div class="hint mono" id="h-alert"><?= e($S['theme_alert']) ?></div></div>
    </div>

    <div class="hintbox" style="margin-top:16px">
      <b>Palette in use:</b>
      <?php foreach ($PALETTE as $hex => $label): ?>
        <span style="display:inline-flex;align-items:center;gap:6px;margin:6px 14px 0 0">
          <span style="width:20px;height:20px;border-radius:6px;background:<?= e($hex) ?>;box-shadow:0 2px 6px rgba(0,0,0,.25)"></span>
          <span class="mono"><?= e($hex) ?></span> <span style="color:var(--mut)"><?= e($label) ?></span>
        </span>
      <?php endforeach; ?>
    </div>

    <div class="grid2" style="margin-top:15px">
      <div class="field"><label>Theme name</label>
        <input name="theme_name" value="<?= e($S['theme_name']) ?>"></div>
      <div class="field"><label>Songs per page</label>
        <input type="number" name="songs_per_page" min="6" max="120" value="<?= e($S['songs_per_page']) ?>"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h2>Public site switches</h2></div>
    <div class="grid3">
      <div class="field check">
        <input type="checkbox" id="public_site" name="public_site" value="1"<?= $S['public_site'] === '1' ? ' checked' : '' ?>>
        <label for="public_site">Website is live</label>
      </div>
      <div class="field check">
        <input type="checkbox" id="allow_signup" name="allow_signup" value="1"<?= $S['allow_signup'] === '1' ? ' checked' : '' ?>>
        <label for="allow_signup">Allow new sign-ups</label>
      </div>
      <div class="field check">
        <input type="checkbox" id="maintenance" name="maintenance" value="1"<?= $S['maintenance'] === '1' ? ' checked' : '' ?>>
        <label for="maintenance">Maintenance mode</label>
      </div>
    </div>
    <p class="muted" style="margin-top:12px">
      Guests can always browse, listen and watch. Turning off sign-ups only stops new registrations;
      saving songs and artists still requires an account.
    </p>
  </div>

  <div class="card">
    <div class="card-h"><h2>Contact details</h2><span class="sub">Shown on the Contact page</span></div>
    <div class="grid3">
      <div class="field"><label>Email</label><input type="email" name="contact_email" value="<?= e($S['contact_email']) ?>"></div>
      <div class="field"><label>Phone</label><input name="contact_phone" value="<?= e($S['contact_phone']) ?>"></div>
      <div class="field"><label>Address</label><input name="contact_address" value="<?= e($S['contact_address']) ?>"></div>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn" type="submit">
      <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
      Save settings
    </button>
    <a class="btn ghost" href="<?= e($base) ?>/index.php" target="_blank" rel="noopener">Preview website</a>
    <span class="hintbox">Changes apply to the public site the next time a page loads.</span>
  </div>
</form>

<!-- ====================================================== ADMIN PASSWORD == -->
<div class="card">
  <div class="card-h">
    <h2>Administrator account</h2>
    <span class="sub">Profile and password for the private admin login</span>
  </div>

  <div class="two">
    <form method="post" action="<?= e($base) ?>/admin/api.php">
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="profile.save">
      <input type="hidden" name="redirect" value="1">
      <input type="hidden" name="back" value="admin/settings.php">
      <div class="grid2">
        <div class="field"><label>Display name</label>
          <input name="name" value="<?= e(af_admin()['name'] ?? '') ?>" required></div>
        <div class="field"><label>Login email</label>
          <input type="email" name="email" value="<?= e(af_admin()['email'] ?? '') ?>" required></div>
      </div>
      <div class="form-actions"><button class="btn ghost" type="submit">Update profile</button></div>
    </form>

    <form method="post" action="<?= e($base) ?>/admin/api.php">
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="password.change">
      <input type="hidden" name="redirect" value="1">
      <input type="hidden" name="back" value="admin/settings.php">
      <div class="field"><label>Current password</label>
        <input type="password" name="current_password" autocomplete="current-password" required></div>
      <div class="field" style="margin-top:12px"><label>New password (min 8)</label>
        <input type="password" name="new_password" autocomplete="new-password" required></div>
      <div class="field" style="margin-top:12px"><label>Repeat new password</label>
        <input type="password" name="confirm_password" autocomplete="new-password" required></div>
      <div class="form-actions"><button class="btn" type="submit">Change password</button></div>
    </form>
  </div>
</div>

<?php
$adminScript = <<<JS
(function(){
  /* live colour hint + tint the admin chrome while picking */
  ['accent','dark','light','alert'].forEach(function(k){
    var inp = document.querySelector('input[name="theme_' + k + '"]');
    var out = document.getElementById('h-' + k);
    if (!inp) return;
    inp.addEventListener('input', function(){
      if (out) out.textContent = inp.value.toUpperCase();
      if (k === 'accent') document.documentElement.style.setProperty('--g1', inp.value);
      if (k === 'dark')   document.documentElement.style.setProperty('--g2', inp.value);
      if (k === 'alert')  document.documentElement.style.setProperty('--accent', inp.value);
    });
  });

  /* logo preview */
  var logo = document.querySelector('input[name="logo_file"]');
  if (logo) logo.addEventListener('change', function(){
    var f = logo.files && logo.files[0];
    if (!f || f.type.indexOf('image/') !== 0) return;
    var img = document.getElementById('logoPrev');
    if (img) { img.style.display = 'block'; img.src = URL.createObjectURL(f); }
    var p = document.getElementById('logoPath');
    if (p) p.textContent = f.name + ' (new upload)';
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
