<?php
/* ==========================================================================
   SAVE API (api/save.php) - used by assets/js/save.js
   --------------------------------------------------------------------------
   GET  ?action=list                       -> the logged-in user's saved list
   POST {action:"add"|"remove", kind, item}-> save / unsave one item
   POST {action:"merge", songs, artists}   -> merge the guest's localStorage
                                              list into the database (login)
   POST {action:"clear"}                   -> remove everything saved

   Rules: logged in only, CSRF checked, every value sanitised, prepared
   statements everywhere. Guests are handled in localStorage by save.js.
   ========================================================================== */

require_once __DIR__ . '/../includes/auth.php';   // session + CSRF + db + helpers

$user = af_user();
if (!$user) {
    af_json(['ok' => false, 'error' => 'Please sign in to save items.'], 401);
}

/* the request body (JSON from save.js, or a normal form post) */
$in = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($in)) {
    $in = $_POST;
}

/* action can come from the query string or from the body */
$action = $_GET['action'] ?? ($in['action'] ?? '');

/* ---- sanitising helpers ------------------------------------------------- */

/** slug id: letters, numbers, dashes only (matches AFS.slug in save.js) */
function af_item_id($value)
{
    $value = mb_strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
    return trim(substr($value, 0, 190), '-');
}

/** short free text (titles, names, ...) */
function af_item_text($value, $max = 190)
{
    $value = trim(strip_tags((string) $value));
    return mb_substr($value, 0, $max);
}

/** media / image path: relative, no ".." trickery */
function af_item_path($value)
{
    $value = str_replace('\\', '/', trim((string) $value));
    if ($value === '' || str_contains($value, '..') || str_starts_with($value, '/') || preg_match('#^https?://#i', $value)) {
        return '';
    }
    return mb_substr($value, 0, 255);
}

/** Validate + clean one item of the given kind (NULL when unusable). */
function af_clean_item($kind, $raw)
{
    if (!is_array($raw)) {
        return null;
    }
    if ($kind === 'artist') {
        $name = af_item_text($raw['name'] ?? ($raw['title'] ?? ''), 160);
        if ($name === '') {
            return null;
        }
        return ['id' => af_item_id($raw['id'] ?? $name), 'name' => $name, 'img' => af_item_path($raw['img'] ?? '')];
    }
    $title = af_item_text($raw['title'] ?? '', 190);
    if ($title === '') {
        return null;
    }
    return [
        'id'     => af_item_id($raw['id'] ?? ($title . ' ' . ($raw['artist'] ?? ''))),
        'title'  => $title,
        'artist' => af_item_text($raw['artist'] ?? '', 160) ?: 'Unknown Artist',
        'src'    => af_item_path($raw['src'] ?? ''),
        'img'    => af_item_path($raw['img'] ?? ''),
    ];
}

/** Is the id already saved by this user? */
function af_saved($table, $userId, $itemId)
{
    return (bool) af_row(
        "SELECT id FROM `{$table}` WHERE user_id = ? AND item_id = ? LIMIT 1",
        [$userId, $itemId]
    );
}

function af_add($table, $userId, $item, $kind)
{
    if ($kind === 'artist') {
        return af_run(
            "INSERT INTO `{$table}` (user_id, item_id, name, image_path) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), image_path = VALUES(image_path)",
            [$userId, $item['id'], $item['name'], $item['img'] ?: null]
        );
    }
    return af_run(
        "INSERT INTO `{$table}` (user_id, item_id, title, artist, media_path, image_path) VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE title = VALUES(title), artist = VALUES(artist)",
        [$userId, $item['id'], $item['title'], $item['artist'], $item['src'] ?: null, $item['img'] ?: null]
    );
}

/* ---- handle the request -------------------------------------------------- */

/* GET list --------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $songs = af_all(
        'SELECT item_id AS id, title, artist, media_path AS src, image_path AS img
         FROM saved_songs WHERE user_id = ? ORDER BY created_at DESC',
        [$user['id']]
    );
    $artists = af_all(
        'SELECT item_id AS id, name, image_path AS img
         FROM saved_artists WHERE user_id = ? ORDER BY created_at DESC',
        [$user['id']]
    );
    af_json(['ok' => true, 'songs' => $songs, 'artists' => $artists]);
}

/* everything else changes data -> CSRF first ------------------------------ */
af_require_csrf();

/* GET list of any other method is unknown --------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}

switch ($action) {

    /* ---- add / remove one item ------------------------------------------ */
    case 'add':
    case 'remove': {
        $kind = ($in['kind'] ?? '') === 'artist' ? 'artist' : 'song';
        $item = af_clean_item($kind, $in['item'] ?? null);
        if (!$item) {
            af_json(['ok' => false, 'error' => 'That item cannot be saved.'], 400);
        }
        $table = $kind === 'artist' ? 'saved_artists' : 'saved_songs';
        if ($action === 'add') {
            af_add($table, $user['id'], $item, $kind);
            af_json(['ok' => true, 'saved' => true]);
        }
        af_run("DELETE FROM `{$table}` WHERE user_id = ? AND item_id = ?", [$user['id'], $item['id']]);
        af_json(['ok' => true, 'saved' => false]);
    }

    /* ---- merge the guest's localStorage list into the database ---------- */
    case 'merge': {
        $added = ['songs' => 0, 'artists' => 0];
        foreach (['song' => 'songs', 'artist' => 'artists'] as $kind => $key) {
            $items = is_array($in[$key] ?? null) ? array_slice($in[$key], 0, 300) : [];
            foreach ($items as $raw) {
                $item = af_clean_item($kind, $raw);
                if (!$item || af_saved($kind === 'artist' ? 'saved_artists' : 'saved_songs', $user['id'], $item['id'])) {
                    continue;
                }
                af_add($kind === 'artist' ? 'saved_artists' : 'saved_songs', $user['id'], $item, $kind);
                $added[$key]++;
            }
        }
        af_json(['ok' => true, 'added' => $added]);
    }

    /* ---- clear everything ------------------------------------------------ */
    case 'clear': {
        af_run('DELETE FROM saved_songs WHERE user_id = ?', [$user['id']]);
        af_run('DELETE FROM saved_artists WHERE user_id = ?', [$user['id']]);
        af_json(['ok' => true]);
    }

    default:
        af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}
