<?php
/* ==========================================================================
   REVIEWS API (api/reviews.php) - used by reviews.js + songs.php
   --------------------------------------------------------------------------
   GET  ?action=list[&song=slug]  -> stored reviews (newest first)
   POST {action:"add", name, role, rating, comment, improve, avatar, song}

   Server side validation (mirrors the client checks), honeypot + time
   throttle, prepared statements. The pages keep their localStorage copy as
   a fallback when the database is unavailable.
   ========================================================================== */

require_once __DIR__ . '/../includes/auth.php';

/* the request body (JSON from the pages, or a normal form post) */
$in = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($in)) {
    $in = $_POST;
}

/* action can come from the query string or from the body */
$action = $_GET['action'] ?? ($in['action'] ?? '');

/* ---- list (public, read only) ------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    if (!$GLOBALS['AF_DB']) {
        af_json(['ok' => true, 'reviews' => [], 'db' => false]);   // offline: pages use localStorage
    }
    $song  = af_item_id_review($_GET['song'] ?? '');
    $scope = $_GET['scope'] ?? '';
    if ($scope === 'songs') {
        /* every per-song review in one call (songs.php merges them in) */
        $rows = af_all('SELECT song_id, name, role, rating, comment, improve, avatar, created_at AS at
                        FROM reviews WHERE song_id IS NOT NULL ORDER BY created_at DESC LIMIT 300');
    } elseif ($song !== '') {
        $rows = af_all('SELECT song_id, name, role, rating, comment, improve, avatar, created_at AS at
                        FROM reviews WHERE song_id = ? ORDER BY created_at DESC LIMIT 100', [$song]);
    } else {
        $rows = af_all('SELECT song_id, name, role, rating, comment, improve, avatar, created_at AS at
                        FROM reviews WHERE song_id IS NULL ORDER BY created_at DESC LIMIT 100');
    }
    af_json(['ok' => true, 'reviews' => $rows, 'db' => true]);
}

/* ---- add ---------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}
af_require_csrf();

/* honeypot + minimum time + per-session throttle */
$guard = af_form_guard($in['ts'] ?? 0, $in[AF_FORM_HONEYPOT] ?? '', 'review');
if ($guard) {
    af_json(['ok' => false, 'error' => $guard], 429);
}

/* field validation (server side - the client checks are only convenience) */
$name    = trim(strip_tags((string) ($in['name'] ?? '')));
$role    = trim(strip_tags((string) ($in['role'] ?? '')));
$rating  = (int) ($in['rating'] ?? 0);
$comment = trim(strip_tags((string) ($in['comment'] ?? '')));
$improve = trim(strip_tags((string) ($in['improve'] ?? '')));
$avatar  = trim((string) ($in['avatar'] ?? ''));
$song    = af_item_id_review($in['song'] ?? '');

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 80)      $errors[] = 'Please enter your name (2 - 80 characters).';
if ($rating < 1 || $rating > 5)                          $errors[] = 'Please choose a rating between 1 and 5 stars.';
if (mb_strlen($comment) < 5 || mb_strlen($comment) > 600) $errors[] = 'Please write 5 - 600 characters of review.';
if (mb_strlen($improve) > 300)                           $errors[] = 'The "improve" note is too long (max 300).';
if ($avatar !== '' && !preg_match('#^(https://[^\s]{5,300}|data:image/[a-z+.-]+;base64,[A-Za-z0-9+/=]{10,20000})$#', $avatar)) {
    $avatar = '';                                         // unknown format: drop it
}
if ($errors) {
    af_json(['ok' => false, 'error' => implode(' ', $errors)], 400);
}
if (!$GLOBALS['AF_DB']) {
    af_json(['ok' => false, 'error' => 'Saving reviews is offline right now - your review stays in this browser.'], 503);
}

af_run(
    'INSERT INTO reviews (song_id, name, role, rating, comment, improve, avatar)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$song !== '' ? $song : null,
     mb_substr($name, 0, 80),
     $role !== '' ? mb_substr($role, 0, 60) : 'Listener',
     $rating,
     mb_substr($comment, 0, 600),
     $improve !== '' ? mb_substr($improve, 0, 300) : null,
     $avatar !== '' ? mb_substr($avatar, 0, 6000) : null]
);

af_json([
    'ok'      => true,
    'message' => 'Thanks! Your review is live.',
    'review'  => [
        'song_id' => $song !== '' ? $song : null,
        'name'    => mb_substr($name, 0, 80),
        'role'    => $role !== '' ? mb_substr($role, 0, 60) : 'Listener',
        'rating'  => $rating,
        'comment' => mb_substr($comment, 0, 600),
        'improve' => $improve !== '' ? mb_substr($improve, 0, 300) : '',
        'avatar'  => $avatar,
        'at'      => date('Y-m-d H:i:s')
    ]
]);

/* ---- local helper (same slug rules as AFS.slug in save.js) ------------- */
function af_item_id_review($value)
{
    $value = mb_strtolower(trim(strip_tags((string) $value)));
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
    return trim(substr($value, 0, 190), '-');
}
