<?php
/* ==========================================================================
   ADMIN BOOT (admin/includes/boot.php)
   Every admin page starts with:  require __DIR__ . '/includes/boot.php';
   --------------------------------------------------------------------------
   * loads the shared session / CSRF / database helpers (includes/auth.php)
   * adds the admin session helpers:  af_admin() / af_admin_login() /
     af_admin_logout() / af_admin_required()
   * keeps the admin identity in $_SESSION['aid'] (separate from a normal
     user's $_SESSION['uid'], so signing in as a user never grants the panel)
   ========================================================================== */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/content.php';   // af_setting() / af_artists() / ...

/* ------------------------------------------------------------ asset cache */
/**
 * Cache-busting stamp for the admin stylesheet.
 *
 * The stylesheet is served with Last-Modified/ETag but no Cache-Control or
 * Expires header, so browsers fall back to heuristic freshness and can keep an
 * old admin.css alive for hours after a redesign. PHP output is always fresh,
 * which makes the page look half-updated: new markup with stale styles.
 *
 * Appending this stamp to the asset URL changes the URL whenever the file
 * changes, which forces the browser to download the new file immediately.
 */
if (!defined('AF_ASSET_V')) {
    $afAssetCss = __DIR__ . '/../assets/admin.css';
    define('AF_ASSET_V', (string) (@filemtime($afAssetCss) ?: 1));
}

/* ----------------------------------------------------------------- guards */

/** The signed-in administrator row (or NULL). One query per request. */
function af_admin()
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $cached = null;
    $db = $GLOBALS['AF_DB'] ?? null;
    if ($db && !empty($_SESSION['aid'])) {
        $cached = af_row(
            'SELECT id, name, email FROM admins WHERE id = ? LIMIT 1',
            [(int) $_SESSION['aid']]
        );
        if (!$cached) {                     // account removed meanwhile
            unset($_SESSION['aid']);
        }
    }
    return $cached;
}

/** True when the visitor is a signed-in administrator. */
function af_is_admin()
{
    return af_admin() !== null;
}

/**
 * Sign in an administrator. Returns [ok, message].
 * Email + password checked against the `admins` table (password_hash()).
 * A slow, always-run verify keeps the response time from leaking whether
 * the account exists.
 */
function af_admin_login($email, $password)
{
    global $AF_DB;
    if (!$AF_DB) {
        return [false, 'Database is unavailable right now - please try again later.'];
    }
    $row = af_row(
        'SELECT id, name, email, password_hash FROM admins WHERE email = ? LIMIT 1',
        [strtolower(trim((string) $email))]
    );
    $hash = $row['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidi';
    $ok   = $row && password_verify((string) $password, $hash);
    if (!$ok) {
        return [false, 'Wrong administrator email or password.'];
    }
    session_regenerate_id(true);            // fresh id on privilege change
    $_SESSION['aid']       = (int) $row['id'];
    $_SESSION['af_csrf']   = bin2hex(random_bytes(32));
    unset($_SESSION['uid']);                 // the panel is never a normal session
    af_run('UPDATE admins SET last_login = NOW() WHERE id = ?', [(int) $row['id']]);
    return [true, 'Welcome back, ' . $row['name'] . '!'];
}

/** Sign the administrator out (the normal session survives untouched). */
function af_admin_logout()
{
    unset($_SESSION['aid']);
    if (!empty($_SESSION['af_csrf'])) {
        $_SESSION['af_csrf'] = bin2hex(random_bytes(32));
    }
}

/* --------------------------------------------------------- page redirect */

/** Absolute path of the admin folder inside the install ("" at the root). */
function af_admin_base()
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('#/admin$#', '', $dir);
    return ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
}

/** Redirect to a URL inside the site and stop. */
function af_admin_redirect($path)
{
    $url = af_admin_base() . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

/* --------------------------------------------------------------- require */

/**
 * Call at the top of every admin page except login.php:
 * bounces guests to admin/login.php with a "next" hint.
 */
function af_admin_required()
{
    if (!af_is_admin()) {
        $next = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        af_admin_redirect('admin/login.php?next=' . rawurlencode($next));
    }
}

/* ---------------------------------------------------------- small helpers */

/** Sanitise a short text field. */
function af_in($key, $max = 190)
{
    $v = isset($_POST[$key]) ? $_POST[$key] : '';
    if (!is_scalar($v)) {
        return '';
    }
    $v = trim(strip_tags((string) $v));
    return mb_substr($v, 0, (int) $max);
}

/** Sanitise an integer field. */
function af_int($key, $default = 0)
{
    return isset($_POST[$key]) && is_scalar($_POST[$key]) ? (int) $_POST[$key] : (int) $default;
}

/** Sanitise a 0/1 checkbox / tinyint. */
function af_flag($key)
{
    return !empty($_POST[$key]) ? 1 : 0;
}

/** Store an uploaded file under uploads/ and return its site-relative path.
 *  On failure $GLOBALS['AF_UPLOAD_ERR'] holds a specific, user-facing reason. */
function af_upload($field, $subdir = 'admin')
{
    $GLOBALS['AF_UPLOAD_ERR'] = null;
    if (empty($_FILES[$field])) {
        return null;                       // no file was ever picked
    }
    $f = $_FILES[$field];
    $err = (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        if ($err === UPLOAD_ERR_NO_FILE) return null;          // empty input, not an error
        $GLOBALS['AF_UPLOAD_ERR'] = (string) [
            UPLOAD_ERR_INI_SIZE   => 'The file is larger than the server allows (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'The file is larger than the form allows (upload_max_filesize).',
            UPLOAD_ERR_PARTIAL    => 'The upload was interrupted - please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server cannot store the upload: missing temporary directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server cannot store the upload: failed to write the file.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
        ][$err] ?? 'Upload failed (PHP error ' . $err . ').';
        return null;
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        $GLOBALS['AF_UPLOAD_ERR'] = 'Upload failed (invalid temporary file).';
        return null;
    }
    if ($f['size'] > 24 * 1024 * 1024) {
        $GLOBALS['AF_UPLOAD_ERR'] = 'The file is larger than the 24 MB limit.';
        return null;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $ok  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp3', 'mp4', 'm4a', 'wav', 'ogg', 'webm'];
    if (!in_array($ext, $ok, true)) {
        $GLOBALS['AF_UPLOAD_ERR'] = 'File type "' . $ext . '" is not allowed. Allowed: ' . implode(', ', $ok) . '.';
        return null;
    }
    $dir = __DIR__ . '/../../uploads/' . $subdir;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        $GLOBALS['AF_UPLOAD_ERR'] = 'Could not save the uploaded file (check the uploads/ folder permissions).';
        return null;
    }
    return 'uploads/' . $subdir . '/' . $name;
}

/**
 * Turn a stored media path into something an <img src> can load.
 * Cover art / media live at the site root (uploads/..., images/...), but the
 * panel sits one folder deeper, so a raw path would 404 - prefix it with the
 * base URL unless it is already absolute or a data URI.
 */
function af_src($path)
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (strpos($path, 'data:') === 0 || preg_match('#^(https?:)?//#i', $path) || $path[0] === '/') {
        return $path;
    }
    return af_admin_base() . '/' . ltrim($path, '/');
}

/**
 * Notification bell helpers.
 *
 * `created_at` is stamped by MySQL, while PHP's date() runs on its own
 * timezone - comparing formatted strings across the two clocks silently eats
 * every new row. So the "last mark as read" moment is stored as a plain Unix
 * timestamp and compared through MySQL's own FROM_UNIXTIME() / NOW().
 */

/** Unix timestamp of the last "mark all read" (0 = never). */
function af_notif_seen()
{
    if (!($GLOBALS['AF_DB'] ?? null)) {
        return 0;
    }
    $raw = (string) (af_row("SELECT setting_value v FROM settings WHERE setting_key = 'admin_notif_seen'")['v'] ?? '');
    if ($raw === '') {
        return 0;
    }
    if (ctype_digit($raw)) {                       // new format: unix seconds
        return (int) $raw;
    }
    $t = strtotime($raw);                          // legacy "Y-m-d H:i:s" rows
    return $t ? $t : 0;
}

/** [whereFragment, params] selecting rows newer than the last mark-as-read. */
function af_notif_since()
{
    $ts = af_notif_seen();
    if ($ts > 0) {
        return ['created_at >= FROM_UNIXTIME(?)', [$ts]];
    }
    return ['created_at >= NOW() - INTERVAL 7 DAY', []];
}

/** Remember "everything up to now has been seen". */
function af_notif_mark_read()
{
    if ($GLOBALS['AF_DB'] ?? null) {
        af_run("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_notif_seen', ?)", [(string) time()]);
    }
}

/** How many unseen users + reviews + messages there are right now. */
function af_notif_count($includeUsers = true)
{
    if (!($GLOBALS['AF_DB'] ?? null)) {
        return 0;
    }
    list($w, $p) = af_notif_since();
    $n = (int) af_row('SELECT COUNT(*) c FROM reviews WHERE ' . $w, $p)['c']
       + (int) af_row('SELECT COUNT(*) c FROM contact_messages WHERE ' . $w, $p)['c'];
    if ($includeUsers) {
        $n += (int) af_row('SELECT COUNT(*) c FROM users WHERE ' . $w, $p)['c'];
    }
    return $n;
}

/** Small flash message stored for the next page load. */
function af_flash($type, $text)
{
    $_SESSION['af_flash'] = ['type' => $type, 'text' => $text];
}
function af_take_flash()
{
    $f = $_SESSION['af_flash'] ?? null;
    unset($_SESSION['af_flash']);
    return $f;
}
