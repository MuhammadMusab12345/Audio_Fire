<?php
/* ==========================================================================
   AUTH + SECURITY (includes/auth.php)
   The single bootstrap every page and every api/ endpoint includes.

   * hides PHP errors from the screen (logs them to data/php-errors.log)
   * starts a hardened session (HttpOnly / SameSite / secure on HTTPS)
   * CSRF token helpers (form field + AJAX header)
   * login / register / logout with password_hash() + session_regenerate_id()
   * output escaping helper  e()  and  af404()
   * simple per-session throttle for public forms (honeypot + min time)
   ========================================================================== */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';          // sets $AF_DB (NULL when MySQL is down)

/* ---- 1. errors: never print them on screen, keep them in a log file ---- */
if (AF_HIDE_ERRORS) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    @ini_set('log_errors', '1');
    @ini_set('error_log', AF_ERROR_LOG);
}

/* ---- 2. hardened session (cookies can only be set before any output) --- */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,                 // dies with the browser
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,           // https only (off on plain localhost)
        'httponly' => true,              // no JavaScript access to the cookie
        'samesite' => 'Lax',             // blocks cross-site POSTs (CSRF belt)
    ]);
    session_name('AFSESS');
    session_start();
}

/* ---- 3. small shared helpers ------------------------------------------- */

/** Escape anything echoed into HTML. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Slug id shared with the front end (AFS.slug in assets/js/save.js):
 * "Stay With Me Sam Smith" -> "stay-with-me-sam-smith".
 * Used for saved-song ids so every page + the database agree.
 */
function af_slug($text)
{
    $text = mb_strtolower(trim(strip_tags((string) $text)));
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    return trim(mb_substr($text, 0, 190), '-');
}

/** The current CSRF token (created on first use, per session). */
function af_csrf()
{
    if (empty($_SESSION['af_csrf'])) {
        $_SESSION['af_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['af_csrf'];
}

/** Check a submitted token against the session token (timing safe). */
function af_csrf_ok($token)
{
    return is_string($token) && !empty($_SESSION['af_csrf'])
        && hash_equals($_SESSION['af_csrf'], $token);
}

/**
 * Check the CSRF token of an AJAX call. Accepts the value from the
 * X-CSRF-Token header or from a "csrf" POST field. Dies with 403 JSON.
 */
function af_require_csrf()
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!af_csrf_ok($token)) {
        af_json(['ok' => false, 'error' => 'Security check failed. Reload the page and try again.'], 403);
    }
}

/** Send a JSON response and stop. Always the only thing on the wire:
 *  any buffered output is discarded and the content type is JSON. */
function af_json($data, $status = 200)
{
    $GLOBALS['AF_JSON_DONE'] = true;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

/** Show the custom 404 page with a real 404 status (bad ids, dead links). */
function af404($message = '')
{
    http_response_code(404);
    $GLOBALS['AF_404_MESSAGE'] = (string) $message;
    /* when called from an api/ script, head.php must still build links
       for the site root, not for /api - force the install folder */
    $GLOBALS['AF_FORCE_BASE'] = AF_BASE_URL;
    require __DIR__ . '/../404.php';
    exit;
}

/** Logged-in user row (or NULL). Cheap: one query per request. */
function af_user()
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $cached = null;
    $db = $GLOBALS['AF_DB'] ?? null;
    if ($db && !empty($_SESSION['uid'])) {
        $cached = af_row(
            'SELECT id, name, username, email FROM users WHERE id = ? LIMIT 1',
            [(int) $_SESSION['uid']]
        );
        if (!$cached) {                    // account deleted meanwhile
            unset($_SESSION['uid']);
        }
    }
    return $cached;
}

/* ---- 4. form throttle (honeypot + minimum time + per-session limit) ---- */

/**
 * Validate a public form submission server side.
 * $renderedAt = timestamp issued when the form was shown,
 * $form       = short name so two forms do not share one throttle window.
 * Returns NULL when OK, or an error message string.
 */
function af_form_guard($renderedAt, $honeypotValue = '', $form = 'default')
{
    /* honeypot: real users never fill the hidden "company" field */
    if (trim((string) $honeypotValue) !== '') {
        return 'Submission rejected.';
    }
    /* time gate: the form must have been open for at least 3 seconds */
    $ts = (int) $renderedAt;
    if ($ts <= 0 || time() - $ts < 3) {
        return 'Please wait a moment and try again.';
    }
    /* session throttle: max one submission per AF_FORM_WAIT_SECONDS */
    $key = 'af_form_last_' . preg_replace('/[^a-z]/', '', $form);
    $last = (int) ($_SESSION[$key] ?? 0);
    if ($last && time() - $last < AF_FORM_WAIT_SECONDS) {
        return 'You are doing that too fast - please wait a few seconds.';
    }
    $_SESSION[$key] = time();
    return null;
}

/* ---- 5. account actions (used by api/auth.php) ------------------------- */

/**
 * Validate the sign-up fields.
 * Returns an array of error messages (empty = valid).
 */
function af_validate_signup(array $in)
{
    $errors = [];
    $name     = trim($in['name'] ?? '');
    $username = trim($in['username'] ?? '');
    $email    = trim($in['email'] ?? '');
    $phone    = trim($in['phone'] ?? '');
    $address  = trim($in['address'] ?? '');
    $pass     = (string) ($in['password'] ?? '');
    $pass2    = (string) ($in['confirm'] ?? '');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $errors[] = 'Please enter your full name (2 - 80 characters).';
    }
    if (!preg_match('/^[a-z0-9_.]{3,40}$/i', $username)) {
        $errors[] = 'Username must be 3 - 40 letters, numbers, "." or "_".';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !preg_match('/^[+0-9()\-\s]{7,30}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (mb_strlen($address) < 3 || mb_strlen($address) > 255) {
        $errors[] = 'Please enter your address (3 - 255 characters).';
    }
    if (mb_strlen($pass) < 6 || mb_strlen($pass) > 200) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Passwords do not match.';
    }
    return $errors;
}

/**
 * Create the account. Returns [ok, message, userRow].
 * Uses prepared statements everywhere + password_hash().
 */
function af_register(array $in)
{
    global $AF_DB;
    if (!$AF_DB) {
        return [false, 'Database is unavailable right now - please try later.', null];
    }
    $errors = af_validate_signup($in);
    if ($errors) {
        return [false, implode(' ', $errors), null];
    }
    $name = trim($in['name']);
    $username = trim($in['username']);
    $email = strtolower(trim($in['email']));

    if (af_row('SELECT id FROM users WHERE email = ? LIMIT 1', [$email])) {
        return [false, 'An account with this email already exists - please sign in.', null];
    }
    if (af_row('SELECT id FROM users WHERE username = ? LIMIT 1', [$username])) {
        return [false, 'This username is already taken - please pick another.', null];
    }
    af_run(
        'INSERT INTO users (name, username, email, phone, address, password_hash)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$name, $username, $email,
         trim($in['phone'] ?? '') ?: null,
         trim($in['address'] ?? '') ?: null,
         password_hash((string) $in['password'], PASSWORD_DEFAULT)]
    );
    $id = (int) $AF_DB->lastInsertId();
    return [true, 'Account created.', ['id' => $id, 'name' => $name, 'username' => $username, 'email' => $email]];
}

/** Sign in. Returns [ok, message]. */
function af_login($emailOrUser, $password)
{
    global $AF_DB;
    if (!$AF_DB) {
        return [false, 'Database is unavailable right now - please try later.'];
    }
    $idField = filter_var($emailOrUser, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $row = af_row(
        "SELECT id, password_hash FROM users WHERE {$idField} = ? LIMIT 1",
        [trim((string) $emailOrUser)]
    );
    /* always run a verify so the response time does not reveal if the
       account exists (user enumeration protection) */
    $hash = $row['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidi';
    $ok = $row && password_verify((string) $password, $hash);
    if (!$ok) {
        return [false, 'Wrong email / username or password.'];
    }
    af_session_login((int) $row['id']);
    return [true, 'Welcome back!'];
}

/** Put the user in the session with a fresh session id (anti fixation). */
function af_session_login($userId)
{
    session_regenerate_id(true);           // new session id on privilege change
    $_SESSION['uid']   = (int) $userId;
    $_SESSION['af_csrf'] = bin2hex(random_bytes(32)); // new CSRF token too
}

/** Sign out. */
function af_logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
