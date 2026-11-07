<?php
/* ==========================================================================
   ACCOUNT PAGE (account.php) - Sign In + Sign Up stay in one file and
   switch in-page (hash: #signin / #signup - the navbar links use them).
   Converted from the original self contained "Final Account page.html" -
   same design and behaviour, now on the shared head / navbar / footer.
   ========================================================================== */
$currentPage     = 'account';
$pageTitle       = 'Account | Audio Fire';
$metaDescription = 'Sign in or create your Audio Fire account and start listening.';
$pageStyles      = array('assets/css/account.css');
$pageScripts     = array('assets/js/account.js');

/* The "Admin Login" entry point lives here, but only for as long as the
   visitor is NOT already signed in as an administrator (the button is then
   removed from the page entirely). admin/includes/boot.php only declares the
   af_admin() / af_is_admin() helpers - it never redirects on its own.       */
require_once __DIR__ . '/admin/includes/boot.php';
$isAdminLoggedIn = af_is_admin();

require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="ac-page">
<svg width="0" height="0" style="position:absolute"><filter id="wisp"><feTurbulence type="fractalNoise" baseFrequency=".008 .012" numOctaves="2" seed="4"><animate attributeName="baseFrequency" dur="40s" values=".008 .012;.012 .008;.008 .012" repeatCount="indefinite"/></feTurbulence><feDisplacementMap in="SourceGraphic" scale="140"/></filter></svg>
<div class="smoke"><b></b><b></b><b></b><b></b></div>
<main class="card">
<div class="brand"><span class="eq"><i></i><i></i><i></i><i></i></span></div>
<section class="stage"><div class="notes" id="notes"></div><div class="chars" id="chars">
<div class="c purple"><div class="face"><div class="eye"><i></i></div><div class="eye"><i></i></div></div></div>
<div class="c black"><div class="face"><div class="eye"><i></i></div><div class="eye"><i></i></div></div></div>
<div class="c orange"><div class="face"><div class="dot"></div><div class="dot"></div></div></div>
<div class="c yellow"><div class="face"><div class="dot"></div><div class="dot"></div><div class="mouth"></div></div></div>
</div></section>
<section class="panels">
<!-- signed-in state: shown by account.js when the session has a user -->
<div class="panel" id="signedin" hidden><h1><span>Hey,</span><span class="o" id="siName">friend</span></h1><p class="sub" id="siEmail"></p>
<div class="opts"><a href="<?= $BASE ?>/saved.php">Open my saved songs &rarr;</a></div>
<div class="msg"></div>
<button class="btn" id="siLogout" type="button">Log out</button>
</div>

<div class="panel on" id="signin"><h1><span>Welcome</span><span class="o">back!</span></h1><p class="sub">Please enter your details</p>
<form novalidate id="formSignin">
<div><label>Email</label><input type="email" name="email" placeholder="anna@gmail.com"></div>
<div class="field"><label>Password</label><input type="password" name="password" data-pw placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"><button type="button" class="eyebtn" aria-label="Show password"><svg viewBox="0 0 24 24"><g class="on"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></g><g class="off"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></g></svg></button></div>
<div class="opts"><span><input type="checkbox" id="rm"><label for="rm">Remember for 30 days</label></span><a href="#">Forgot password?</a></div>
<div class="msg"></div>
<button class="btn">Log in</button>
<button type="button" class="btn ghost"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>Log in with Google</button>
</form>
<?php if (!$isAdminLoggedIn): ?>
<p class="alt" style="margin-top:16px;font-size:.7rem;letter-spacing:.16em;text-transform:uppercase">or</p>
<button type="button" class="btn ghost" id="adminOpen" data-go="adminlogin">
  <svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 8.9 8 10 4.6-1.1 8-5 8-10V5z"/><path d="m9 12 2 2 4-4"/></svg>
  Admin Login
</button>
<?php endif; ?>
<p class="alt">Don't have an account? <a href="#signup" data-go="signup">Sign Up</a></p></div>

<div class="panel" id="signup"><h1><span>Create</span><span class="o">account</span></h1><p class="sub">Join and start listening</p>
<form novalidate id="formSignup">
<div class="row"><div><label>Full name</label><input name="name" placeholder="Ali Khan"></div><div><label>Username</label><input name="username" placeholder="ali_music"></div></div>
<div class="row"><div><label>Email</label><input type="email" name="email" placeholder="anna@gmail.com"></div><div><label>Phone</label><input type="tel" name="phone" placeholder="03xx xxxxxxx"></div></div>
<div><label>Address</label><input name="address" placeholder="House 12, Street 4, Lahore"></div>
<div class="row"><div><label>Country</label><select name="country"><option>Pakistan</option><option>India</option><option>UAE</option><option>UK</option><option>USA</option></select></div><div><label>Favourite genre</label><select name="genre"><option>Pop</option><option>Hip-Hop</option><option>R&amp;B</option><option>Rock</option><option>Qawwali</option></select></div></div>
<div class="row"><div class="field"><label>Password</label><input type="password" name="password" data-pw placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"><button type="button" class="eyebtn" aria-label="Show password"><svg viewBox="0 0 24 24"><g class="on"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></g><g class="off"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></g></svg></button></div>
<div class="field"><label>Confirm password</label><input type="password" name="confirm" data-pw placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"><button type="button" class="eyebtn" aria-label="Show password"><svg viewBox="0 0 24 24"><g class="on"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></g><g class="off"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></g></svg></button></div></div>
<div class="meter" id="meter"><i></i><i></i><i></i><i></i><span>Password strength</span></div>
<div class="opts"><span><input type="checkbox" id="tc" name="terms"><label for="tc">I agree to the Terms &amp; Privacy Policy</label></span></div>
<div class="msg"></div>
<button class="btn">Sign up</button>
</form>
<p class="alt">Already have an account? <a href="#signin" data-go="signin">Log in</a></p></div>

<?php if (!$isAdminLoggedIn): ?>
<!-- =============================================================== ADMIN ==
     A completely separate form: it does NOT go through api/auth.php, it posts
     straight to admin/login.php, which checks the admins table and bounces
     straight into the dashboard on success. Hidden entirely once the visitor
     already holds an administrator session.
     ==================================================================== -->
<div class="panel" id="adminlogin"><h1><span>Admin</span><span class="o">login</span></h1><p class="sub">Restricted area</p>
<form method="post" action="<?= e($BASE) ?>/admin/login.php" id="formAdmin" novalidate>
<input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
<input type="hidden" name="next" value="index.php">
<div><label>Email</label><input type="email" name="email" placeholder="you@example.com" autocomplete="username" spellcheck="false"></div>
<div class="field"><label>Password</label><input type="password" name="password" data-pw placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password"><button type="button" class="eyebtn" aria-label="Show password"><svg viewBox="0 0 24 24"><g class="on"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></g><g class="off"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></g></svg></button></div>
<div class="msg"></div>
<button class="btn">Open admin dashboard</button>
</form>
<p class="alt">Not an administrator? <a href="#signin" data-go="signin">Back to sign in</a></p></div>
<?php endif; ?>
</section></main>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
