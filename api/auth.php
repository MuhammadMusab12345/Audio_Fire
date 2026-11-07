<?php
/* ==========================================================================
   AUTH API (api/auth.php) - used by assets/js/account.js
   --------------------------------------------------------------------------
   POST {action:"register", name, username, email, phone, address,
         password, confirm}
   POST {action:"login",    email, password}
   POST {action:"logout"}
   GET  {action:"me"}                       -> the signed-in user (or null)

   password_hash() / password_verify(), prepared statements, CSRF on every
   change, session_regenerate_id() on sign in, throttled login attempts.
   ========================================================================== */

require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$in = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($in)) {
    $in = $_POST;
}

/* ---- who am i (no CSRF needed - reads only) ----------------------------- */
if ($action === 'me') {
    af_json(['ok' => true, 'user' => af_user()]);
}

af_require_csrf();     // every write needs a valid token

/* ---- login attempt throttle (per session): 8 tries / 10 minutes --------- */
$now  = time();
$try  = &$_SESSION['af_login_try'];
$try  = is_array($try) ? $try : ['n' => 0, 't' => $now];
if ($now - (int) $try['t'] > 600) {
    $try = ['n' => 0, 't' => $now];
}

switch ($action) {

    case 'register': {
        if ($try['n'] >= 8) {
            af_json(['ok' => false, 'error' => 'Too many attempts - please wait a few minutes.'], 429);
        }
        list($ok, $message, $row) = af_register($in);
        if (!$ok) {
            $try['n']++;
            af_json(['ok' => false, 'error' => $message], 400);
        }
        af_session_login((int) $row['id']);    // sign the new user in
        af_json(['ok' => true, 'message' => $message, 'user' => $row, 'csrf' => af_csrf()]);
    }

    case 'login': {
        if ($try['n'] >= 8) {
            af_json(['ok' => false, 'error' => 'Too many attempts - please wait a few minutes.'], 429);
        }
        list($ok, $message) = af_login($in['email'] ?? '', $in['password'] ?? '');
        if (!$ok) {
            $try['n']++;
            af_json(['ok' => false, 'error' => $message], 400);
        }
        $try = ['n' => 0, 't' => $now];
        $user = af_user();
        af_json(['ok' => true, 'message' => $message, 'user' => $user, 'csrf' => af_csrf()]);
    }

    case 'logout': {
        af_logout();
        af_json(['ok' => true, 'message' => 'Signed out.']);
    }

    default:
        af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}
