<?php
/* ==========================================================================
   ADMIN > ADMIN ACCOUNT (admin/account.php)
   Quick profile / password page for the administrator.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$me   = af_admin();
$stats = [
    'last' => (string) (af_row('SELECT last_login l FROM admins WHERE id = ?', [(int) $me['id']])['l'] ?? '—'),
    'since'=> (string) (af_row('SELECT created_at c FROM admins WHERE id = ?', [(int) $me['id']])['c'] ?? '—'),
];

$adminTitle = 'Admin Account';
$adminCrumb = 'System / Your account';
$activeNav  = 'admin-acct';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
?>
<style>.content{display:grid;gap:18px}</style>

<div class="card">
  <div class="card-h">
    <h2>Your administrator account</h2>
    <span class="sub">This login only opens the admin panel - it is never a normal website account</span>
  </div>

  <div class="two">
    <form method="post" action="<?= e($base) ?>/admin/api.php">
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="profile.save">
      <input type="hidden" name="redirect" value="1">
      <input type="hidden" name="back" value="admin/account.php">
      <div class="grid2">
        <div class="field"><label>Display name</label>
          <input name="name" value="<?= e($me['name']) ?>" required></div>
        <div class="field"><label>Login email</label>
          <input type="email" name="email" value="<?= e($me['email']) ?>" required></div>
      </div>
      <div class="form-actions">
        <button class="btn" type="submit">Save profile</button>
        <a class="btn ghost" href="<?= e($base) ?>/admin/settings.php">Website settings</a>
      </div>
    </form>

    <ul class="list-plain">
      <li><span class="dot" style="background:#2E86C1"></span>Account id<small>#<?= (int) $me['id'] ?></small></li>
      <li><span class="dot" style="background:#1f9d63"></span>Last sign-in<small><?= e(date('M j, Y H:i', strtotime($stats['last'])) ?: '—') ?></small></li>
      <li><span class="dot" style="background:#34495E"></span>Created<small><?= e(date('M j, Y', strtotime($stats['since'])) ?: '—') ?></small></li>
      <li><span class="dot" style="background:#E74C3C"></span>Panel access<small>Administrator only</small></li>
    </ul>
  </div>
</div>

<div class="card">
  <div class="card-h">
    <h2>Change password</h2>
    <span class="sub">Minimum 8 characters</span>
  </div>
  <form method="post" action="<?= e($base) ?>/admin/api.php" style="max-width:520px">
    <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
    <input type="hidden" name="action" value="password.change">
    <input type="hidden" name="redirect" value="1">
    <input type="hidden" name="back" value="admin/account.php">
    <div class="field"><label>Current password</label>
      <input type="password" name="current_password" autocomplete="current-password" required></div>
    <div class="field" style="margin-top:13px"><label>New password</label>
      <input type="password" name="new_password" autocomplete="new-password" required></div>
    <div class="field" style="margin-top:13px"><label>Repeat new password</label>
      <input type="password" name="confirm_password" autocomplete="new-password" required></div>
    <div class="form-actions"><button class="btn" type="submit">Change password</button></div>
  </form>
</div>

<div class="card">
  <div class="card-h"><h2>Session</h2></div>
  <form method="post" action="<?= e($base) ?>/admin/logout.php">
    <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
    <div class="form-actions" style="margin-top:0">
      <button class="btn red" type="submit">Log out of the admin panel</button>
      <a class="btn ghost" href="<?= e($base) ?>/index.php" target="_blank" rel="noopener">Open the website</a>
      <span class="hintbox">Signing out here keeps any normal website sign-in untouched.</span>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
