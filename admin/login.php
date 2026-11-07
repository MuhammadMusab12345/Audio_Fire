<?php
/* ==========================================================================
   ADMIN SIGN IN (admin/login.php)
   --------------------------------------------------------------------------
   Private entry to the panel. The email + password are checked against the
   `admins` table by af_admin_login() (bcrypt). A successful sign in stores the
   administrator id in $_SESSION['aid'] - deliberately separate from a normal
   user's $_SESSION['uid'], so signing in as a user never grants the panel.

   Nothing about the credentials is echoed back into the page.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';

/* Already signed in? Go where they were headed (or the dashboard). */
if (af_is_admin()) {
    $next = isset($_GET['next']) ? trim((string) $_GET['next']) : '';
    if ($next === '' || $next === 'login.php' || $next === 'logout.php') {
        $next = 'index.php';
    }
    af_admin_redirect('admin/' . $next);
}

$base = af_admin_base();
$logo = $base . '/assets/music-logo.png';
$csrf = af_csrf();                 // creates the token on the very first visit
$site = af_setting('site_name', 'Audio Fire');

$error = '';
$next  = isset($_GET['next']) ? trim((string) $_GET['next']) : '';
if ($next === '' || $next === 'login.php' || $next === 'logout.php') {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* CSRF first: a forged post never reaches the password check. */
    $sent = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if ($sent === '' || $csrf === '' || !hash_equals($csrf, $sent)) {
        $error = 'This form expired. Please try again.';
    } else {
        $email    = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        if ($email === '' || $password === '') {
            $error = 'Enter your administrator email and password.';
        } else {
            list($ok, $msg) = af_admin_login($email, $password);
            if ($ok) {
                af_admin_redirect('admin/' . $next);
            }
            $error = $msg;
        }
        /* The token is single use: rotate it whatever the outcome. */
        $_SESSION['af_csrf'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['af_csrf'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Administrator sign in | Audio Fire</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>/admin/assets/admin.css?v=<?= e(AF_ASSET_V) ?>">
<link rel="icon" type="image/png" href="<?= e($base) ?>/assets/img/favicon.png">
<script>
/* theme before paint: light / dark, remembered across visits */
(function(){
  try{
    var t = localStorage.getItem('af-admin-theme');
    if(!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', t);
  }catch(e){}
})();
</script>
</head>
<body class="login-page">

<!-- Slow green / gray / black smoke behind everything (.smoke in admin.css) -->
<div class="smoke" aria-hidden="true"><i class="s-green"></i><i class="s-gray"></i><i class="s-black"></i><i class="s-noise"></i></div>

<button class="iconbtn themebtn" id="themebtn" type="button" aria-label="Toggle dark / light mode"
        style="position:fixed;top:18px;right:18px;z-index:5">
  <svg class="sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
  <svg class="moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
</button>

<div class="login-wrap">
  <!-- The logo image already carries the AUDIO FIRE wordmark, so it sits alone
       on the page background above the card - no tile, no panel, no glow and
       no second copy of the name underneath it. -->
  <img class="login-logo" src="<?= e($logo) ?>" alt="<?= e($site) ?>"
       onerror="this.style.display='none'">
  <form class="login-card" method="post" id="loginForm" novalidate>
    <p class="sub">Administrator sign in &middot; restricted area</p>

    <div class="login-err<?= $error !== '' ? ' on' : '' ?>" id="err">
      <svg viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>
      </svg>
      <span><?= e($error) ?></span>
    </div>

    <div class="field">
      <label for="email">Email address</label>
      <div class="inp">
        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 8 9 6 9-6"/></svg>
        <input type="email" id="email" name="email" autocomplete="username"
               placeholder="you@example.com" required
               value="<?= e(isset($_POST['email']) ? (string) $_POST['email'] : '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <div class="inp">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" id="password" name="password" autocomplete="current-password"
               placeholder="Your password" required>
        <button type="button" class="login-eye" id="eye" aria-label="Show password">
          <svg class="eye-on" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg class="eye-off" viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 5.2A9.7 9.7 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.4 4.3M6.3 6.4A17.4 17.4 0 0 0 2 12s3.6 7 10 7a9.9 9.9 0 0 0 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
        </button>
      </div>
    </div>

    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <button class="btn pri" type="submit" id="go">
      <span class="btn-t">Sign in to the panel
        <svg class="arw" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
      </span>
      <span class="btn-busy">Checking...</span>
    </button>

    <p class="login-foot">
      This area is restricted to Audio Fire administrators.<br>
      <a href="<?= e($base) ?>/index.php">&larr; Back to the website</a>
    </p>
  </form>
</div>

<script>
(function(){
  var eye = document.getElementById('eye');
  var pw  = document.getElementById('password');
  if (eye && pw) {
    eye.addEventListener('click', function(){
      var show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      eye.classList.toggle('on', show);
      eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  }

  var form = document.getElementById('loginForm');
  var go   = document.getElementById('go');
  if (form && go) {
    form.addEventListener('submit', function(){
      if (!form.checkValidity()) return;
      go.classList.add('loading');
      go.disabled = true;
    });
  }

  /* Enter anywhere submits, so the form works even with focus on the button. */
  document.addEventListener('keydown', function(e){
    if (e.key === 'Enter' && form && form.checkValidity()) form.requestSubmit();
  });
})();
</script>
</body>
</html>
