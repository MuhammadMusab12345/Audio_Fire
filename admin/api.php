<?php
/* ==========================================================================
   ADMIN API (admin/api.php) - every create / update / delete of the panel
   --------------------------------------------------------------------------
   POST form fields:  action=<name>  csrf=<token>  ...payload
   Response:           JSON  {ok:true, ...} | {ok:false, error:"..."}

   Actions
      song.save      song.delete      song.toggle
      song.attach    song.detach      song.shuffle
      album.save     album.delete
     artist.save    artist.delete
     category.save  category.delete
     user.save      user.delete      user.status
     review.delete  review.approve
     message.read   message.delete
     settings.save  password.change
   ========================================================================== */

/* ---- always answer with pure JSON ---------------------------------------
   No notice, warning or fatal may leave this script as HTML: errors are
   captured here, written to admin/logs/error.log and answered as
   {ok:false, error:"..."} so the panel's JS never sees a non-JSON body. */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

/** Append one line to admin/logs/error.log (created on demand). */
function af_api_log($msg)
{
    static $dir = null;
    if ($dir === null) {
        $dir = __DIR__ . '/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    }
    @file_put_contents($dir . '/error.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

/** Discard any buffered output and answer the caller with clean JSON. */
function af_api_bail($e)
{
    static $done = false;
    if ($done) return;
    $done = true;
    af_api_log((string) $e);
    while (ob_get_level() > 0) { @ob_end_clean(); }
    $host  = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $local = strpos($host, 'localhost') === 0 || strpos($host, '127.0.0.1') === 0 || $host === '[::1]';
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode(['ok' => false, 'error' => $local ? $e->getMessage() : 'Server error. Please try again.']);
    exit;
}

/* warnings / fatals -> exceptions -> af_api_bail(); notices are logged only */
set_error_handler(static function ($no, $str, $file = '', $line = 0) {
    if (!(error_reporting() & $no)) return false;                 // @-silenced
    if (in_array($no, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED], true)) {
        af_api_log('[notice ' . $no . '] ' . $str . ' @ ' . $file . ':' . $line);
        return true;                                              // never to the wire
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});
set_exception_handler(static function ($e) { af_api_bail($e); });
register_shutdown_function(static function () {
    if (!empty($GLOBALS['AF_JSON_DONE'])) return;                 // af_json() already answered
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        af_api_bail(new ErrorException($e['message'], 0, $e['type'], (string) ($e['file'] ?? ''), (int) ($e['line'] ?? 0)));
    }
});

require_once __DIR__ . '/includes/boot.php';

if (!af_is_admin()) {
    af_json(['ok' => false, 'error' => 'Administrator session required.'], 401);
}

/* ---- GET endpoints (read-only JSON helpers for the forms) --------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['action'] ?? '') === 'albums.for_artist') {
        $aid    = (int) ($_GET['artist_id'] ?? 0);
        $albums = [];
        /* no singer id -> empty list (the forms hide the Album dropdown
           instead of ever showing "all albums" for nobody) */
        if ($AF_DB && $aid) {
            $ar = af_row('SELECT name FROM artists WHERE id = ? LIMIT 1', [$aid]);
            $nm = (string) ($ar['name'] ?? '');
            $albums = af_api_col('albums', 'artist_id')
                ? af_all('SELECT id, title FROM albums WHERE artist_id = ? OR artist = ? ORDER BY title ASC, id ASC', [$aid, $nm])
                : af_all('SELECT id, title FROM albums WHERE artist = ? ORDER BY title ASC, id ASC', [$nm]);
        }
        af_json(['ok' => true, 'albums' => $albums]);
    }
    af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    af_json(['ok' => false, 'error' => 'POST only.'], 405);
}
/* post_max_size exceeded -> PHP empties $_POST/$_FILES before any code runs;
   catch it here so the caller gets a real message, not a CSRF failure */
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES)) {
    af_json(['ok' => false, 'error' => 'The upload was larger than the server allows (post_max_size). Raise post_max_size / upload_max_filesize in php.ini or admin/.user.ini.'], 413);
}
af_require_csrf();

$action = (string) ($_POST['action'] ?? '');
$wantRedirect = !empty($_POST['redirect']);   // classic form post -> redirect back

/** Bail out with a message (redirects back to the calling page when asked). */
function fail($msg)
{
    global $wantRedirect;
    if ($wantRedirect) {
        af_flash('err', $msg);
        $back = (string) ($_POST['back'] ?? 'admin/index.php');
        $back = preg_replace('#[^a-z0-9_./-]#i', '', $back);
        if ($back === '' || strpos($back, '..') !== false) $back = 'admin/index.php';
        af_admin_redirect($back);
    }
    af_json(['ok' => false, 'error' => $msg], 400);
}
function done($data = [])
{
    global $wantRedirect;
    if ($wantRedirect) {
        af_flash('ok', $data['message'] ?? 'Saved.');
        $back = (string) ($_POST['back'] ?? 'admin/index.php');
        $back = preg_replace('#[^a-z0-9_./-]#i', '', $back);
        if ($back === '' || strpos($back, '..') !== false) $back = 'admin/index.php';
        af_admin_redirect($back);
    }
    af_json(['ok' => true, 'message' => $data['message'] ?? 'Saved.'] + $data);
}

/** Does this column exist on this database? Cached per table.column. */
function af_api_col($table, $col)
{
    static $cache = [];
    $k = $table . '.' . $col;
    if (array_key_exists($k, $cache)) return $cache[$k];
    $cache[$k] = false;
    if (!empty($GLOBALS['AF_DB'])) {
        try {
            /* information_schema + placeholders: MariaDB rejects `SHOW ... LIKE ?` */
            $cache[$k] = (bool) af_row(
                'SELECT 1 FROM information_schema.columns
                  WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                [(string) $table, (string) $col]
            );
        } catch (Throwable $e) {
            $cache[$k] = false;
        }
    }
    return $cache[$k];
}

/** Does this table exist on this database? Cached per table name. */
function af_api_table($table)
{
    $key = preg_replace('/[^a-z_]/i', '', (string) $table);
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $cache[$key] = false;
    if ($key !== '' && !empty($GLOBALS['AF_DB'])) {
        try {
            /* information_schema + placeholders: MariaDB rejects `SHOW ... LIKE ?` */
            $cache[$key] = (bool) af_row(
                'SELECT 1 FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name = ?',
                [$key]
            );
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
    }
    return $cache[$key];
}

/** albums.tracks = number of songs the Album page will show for that album. */
function af_api_tracks($albumId)
{
    $albumId = (int) $albumId;
    $n = function_exists('get_album_songs') ? count(get_album_songs($albumId)) : 0;
    af_run('UPDATE albums SET tracks = ? WHERE id = ?', [$n, $albumId]);
    return $n;
}

/** af_upload() wrapper: fails the request with the specific upload error. */
function af_up($field, $subdir)
{
    $path = af_upload($field, $subdir);
    if ($path === null && !empty($_FILES[$field])
        && (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        fail((string) ($GLOBALS['AF_UPLOAD_ERR'] ?? 'The file could not be uploaded.'));
    }
    return $path;
}

/** "col1=?,col2=?" for an UPDATE built from a column map. */
function af_sql_set(array $map)
{
    return implode(',', array_map(static function ($k) { return $k . '=?'; }, array_keys($map)));
}

/** "(?,?,?)" placeholder list matching the keys of a column map. */
function af_sql_marks(array $map)
{
    return '(' . implode(',', array_fill(0, count($map), '?')) . ')';
}

if (!$AF_DB) {
    fail('Database is unavailable.');
}

/* ========================================================================= */
/*  SONGS (music + videos + spotlight share one table)                       */
/* ========================================================================= */

if ($action === 'song.save') {
    $id       = af_int('id');
    /* which admin form posted decides the section server-side (never the client):
       form=songs -> the Videos/Songs form (section "video"), form=music -> Music.
       A bare `section` is only kept for older callers (albums quick-add, spot). */
    $form = af_in('form', 20);
    if ($form === 'songs') {
        $section = 'video';
    } elseif ($form === 'music') {
        $section = 'music';
    } else {
        $section = af_in('section', 20);
        if (!in_array($section, ['music', 'video', 'spot'], true)) $section = 'music';
    }
    $title    = af_in('title', 191);
    $artist   = af_in('artist', 160);
    if ($title === '')    fail('Title is required.');

    $album    = af_in('album', 191) ?: null;
    $year     = af_int('year') ?: null;
    $genre    = af_in('genre', 80) ?: null;
    $language = af_in('language', 40) ?: null;
    $region   = af_in('region', 60) ?: null;
    $duration = af_int('duration');
    $color    = af_in('color', 16) ?: null;
    $desc     = af_in('description', 600) ?: null;
    $isNew    = af_flag('is_new');
    $artistId = af_int('artist_id') ?: null;   // singers dropdown (artists table)
    $albumId  = af_int('album_id') ?: null;    // albums dropdown
    $hasArt   = af_api_col('songs', 'artist_id');
    $hasAlb   = af_api_col('songs', 'album_id');

    /* the stored row: source of truth for "keep the old file" on update */
    $old = null;
    if ($id) {
        $old = af_row('SELECT artist, album, thumb, media'
            . ($hasArt ? ', artist_id' : '') . ($hasAlb ? ', album_id' : '')
            . ' FROM songs WHERE id = ?', [$id]);
        if (!$old) fail('That song no longer exists.');
    }

    /* on update, keep the stored names only when the form posted neither id nor text
       (an explicit empty artist_id / album_id means "clear it", so it is honoured) */
    if ($old) {
        if (!array_key_exists('artist_id', $_POST) && $artist === '') {
            $artist   = $old['artist'];
            if ($hasArt) $artistId = !empty($old['artist_id']) ? (int) $old['artist_id'] : null;
        }
        if ($hasAlb && !array_key_exists('album_id', $_POST) && ($album === null || $album === '')) {
            $album   = $old['album'];
            $albumId = !empty($old['album_id']) ? (int) $old['album_id'] : null;
        }
    }
    if ($artist === '' || $artist === null) $artist = 'Various Artists';

    /* keep the singer / album NAME columns in sync with the ids */
    if ($artistId) {
        $ar = af_row('SELECT name FROM artists WHERE id = ? LIMIT 1', [$artistId]);
        if ($ar) { $artist = $ar['name']; } else { $artistId = null; }
    }
    if ($albumId) {
        $al = af_row('SELECT title FROM albums WHERE id = ? LIMIT 1', [$albumId]);
        if ($al) { $album = $al['title']; } else { $albumId = null; }
    } elseif ($album !== null && $album !== '') {
        $al = af_row('SELECT id FROM albums WHERE title = ? LIMIT 1', [$album]);
        $albumId = $al ? (int) $al['id'] : null;
    }

    /* media + thumb: a new upload replaces, anything else keeps the old file */
    $thumb = af_up('thumb_file', 'thumbs');
    if ($thumb === null) $thumb = af_in('thumb', 255) ?: ($old ? ($old['thumb'] ?: null) : null);
    $media = af_up('media_file', $section === 'video' ? 'video' : 'audio');
    if ($media === null) $media = af_in('media', 255) ?: ($old ? ($old['media'] ?: null) : null);
    /* the file is only ever required on CREATE - an edit without one keeps it */
    if (!$old && !$media) fail('An audio or video file is required for a new song.');

    /* only touch columns that actually exist on this database */
    $map = [
        'section' => $section, 'title' => $title, 'artist' => $artist, 'album' => $album,
        'year' => $year, 'genre' => $genre, 'language' => $language, 'region' => $region,
        'thumb' => $thumb, 'media' => $media, 'duration' => $duration, 'color' => $color,
        'is_new' => $isNew, 'description' => $desc,
    ];
    if ($hasArt) $map['artist_id'] = $artistId;
    if ($hasAlb) $map['album_id']  = $albumId;

    if ($id) {
        af_run('UPDATE songs SET ' . af_sql_set($map) . ' WHERE id = ?', array_merge(array_values($map), [$id]));
        /* keep the old and new album track counters right when the album changes */
        if ($hasAlb) {
            $oldAl = (int) (($old['album_id'] ?? 0) ?: 0);
            $newAl = (int) (($albumId ?? 0) ?: 0);
            if ($oldAl !== $newAl) {
                foreach (array_unique(array_filter([$oldAl, $newAl])) as $alid) {
                    af_api_tracks($alid);
                }
            }
        }
        done(['message' => 'Song updated.', 'id' => $id]);
    }
    $map['sort_order'] = 0;   // lists are newest-first (ORDER BY id DESC)
    af_run('INSERT INTO songs (' . implode(',', array_keys($map)) . ') VALUES ' . af_sql_marks($map), array_values($map));
    $newId = (int) $AF_DB->lastInsertId();
    if ($albumId && $hasAlb) {   // a song born inside an album bumps its track count
        af_api_tracks($albumId);
    }
    done(['message' => 'Song added.', 'id' => $newId]);
}

if ($action === 'song.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    $hasAlb = af_api_col('songs', 'album_id');
    $hasJoin = af_api_table('album_songs');
    $row = af_row('SELECT id' . ($hasAlb ? ', album_id' : '') . ' FROM songs WHERE id = ? LIMIT 1', [$id]);
    if (!$row) fail('That song no longer exists.');
    /* every album holding this song, so their track counts can be fixed */
    $alIds = [];
    if ($hasJoin) {
        foreach (af_all('SELECT album_id FROM album_songs WHERE song_id = ?', [$id]) as $r) {
            $alIds[] = (int) $r['album_id'];
        }
        af_run('DELETE FROM album_songs WHERE song_id = ?', [$id]);
    }
    if ($hasAlb && !empty($row['album_id'])) {
        $alIds[] = (int) $row['album_id'];
    }
    af_run('DELETE FROM songs WHERE id = ?', [$id]);
    foreach (array_unique($alIds) as $aid) {
        if ($aid > 0) {
            af_api_tracks($aid);
        }
    }
    done(['message' => 'Song deleted.']);
}

/* attach / detach a song from an album (albums page, inner songs manager).
   album_songs is the source of truth - one song may sit in any number of
   albums; songs.album_id / songs.album are still kept in step so the older
   columns shown in the Songs admin never go stale. */
if ($action === 'song.attach' || $action === 'song.detach') {
    if (!af_api_col('songs', 'album_id')) {
        fail('Album links need the songs.album_id column (run admin/migrations/001_admin_upgrade.php).');
    }
    if (!af_api_table('album_songs')) {
        fail('The album_songs table is missing - load any page once (includes/migrations.php) and try again.');
    }
    $songId  = af_int('song_id');
    $albumId = af_int('album_id');
    if (!$songId || !$albumId) fail('Missing id.');
    $al = af_row('SELECT id, title FROM albums WHERE id = ? LIMIT 1', [$albumId]);
    if (!$al) fail('That album no longer exists.');
    if (!af_row('SELECT id FROM songs WHERE id = ? LIMIT 1', [$songId])) fail('That song no longer exists.');

    $inJoin = af_row('SELECT id FROM album_songs WHERE album_id = ? AND song_id = ? LIMIT 1', [$albumId, $songId]);
    $inLink = af_row(
        'SELECT id FROM songs WHERE id = ? AND (album_id = ? OR album = ?) LIMIT 1',
        [$songId, $albumId, (string) $al['title']]
    );

    if ($action === 'song.attach') {
        if ($inJoin || $inLink) {
            fail('That song is already in this album.');
        }
        $next = af_row('SELECT IFNULL(MAX(sort_order), 0) + 1 n FROM album_songs WHERE album_id = ?', [$albumId]);
        af_run('INSERT INTO album_songs (album_id, song_id, sort_order) VALUES (?, ?, ?)',
            [$albumId, $songId, (int) ($next['n'] ?? 1)]);
        af_run('UPDATE songs SET album_id = ?, album = ? WHERE id = ? AND (album_id IS NULL OR album_id = ?)',
            [$albumId, (string) $al['title'], $songId, $albumId]);
    } else {
        if (!$inJoin && !$inLink) {
            fail('That song is not in this album.');
        }
        if ($inJoin) {
            af_run('DELETE FROM album_songs WHERE album_id = ? AND song_id = ?', [$albumId, $songId]);
        }
        if ($inLink) {
            af_run('UPDATE songs SET album_id = NULL, album = NULL WHERE id = ?', [$songId]);
        }
    }
    $n = af_api_tracks($albumId);
    done([
        'message' => $action === 'song.attach' ? 'Song added to the album.' : 'Song removed from the album.',
        'tracks'  => $n,
    ]);
}

/* Shuffle: swap this album's songs for random tracks of the Songs library
   (the same pool the Album page seed uses - section "video", 6 by default). */
if ($action === 'song.shuffle') {
    if (!af_api_table('album_songs')) {
        fail('The album_songs table is missing - load any page once (includes/migrations.php) and try again.');
    }
    $albumId = af_int('album_id');
    if (!$albumId) fail('Missing id.');
    $al = af_row('SELECT id, title FROM albums WHERE id = ? LIMIT 1', [$albumId]);
    if (!$al) fail('That album no longer exists.');
    $limit = af_int('count') ?: 6;
    $limit = max(1, min(50, $limit));

    $pool = af_all("SELECT id FROM songs WHERE section = 'video' ORDER BY RAND() LIMIT " . $limit);
    if (!$pool) fail('The Songs library has no songs to shuffle in.');

    af_run('DELETE FROM album_songs WHERE album_id = ?', [$albumId]);
    /* drop the older album links too, so the shuffle really replaces them */
    af_run('UPDATE songs SET album_id = NULL, album = NULL
             WHERE album_id = ? OR (album = ? AND (album_id IS NULL OR album_id = 0))',
        [$albumId, (string) $al['title']]);
    $n = 0;
    foreach ($pool as $p) {
        af_run('INSERT INTO album_songs (album_id, song_id, sort_order) VALUES (?, ?, ?)',
            [$albumId, (int) $p['id'], $n]);
        $n++;
    }
    done([
        'message' => 'Shuffled - ' . $n . ' random song' . ($n === 1 ? '' : 's') . ' in this album.',
        'tracks'  => af_api_tracks($albumId),
    ]);
}

if ($action === 'song.toggle') {
    $id = af_int('id');
    $col = af_in('col', 12);
    if (!in_array($col, ['is_new'], true)) fail('Unsupported column.');
    af_run("UPDATE songs SET {$col} = IF({$col} = 1, 0, 1) WHERE id = ?", [$id]);
    $v = af_row("SELECT {$col} v FROM songs WHERE id = ?", [$id]);
    done(['message' => 'Updated.', 'value' => (int) ($v['v'] ?? 0)]);
}

/* ========================================================================= */
/*  ALBUMS                                                                   */
/* ========================================================================= */

if ($action === 'album.save') {
    $id    = af_int('id');
    $title = af_in('title', 191);
    if ($title === '') fail('Album title is required.');
    $artistId = af_int('artist_id') ?: null;
    $artist = af_in('artist', 160) ?: null;
    $hasArt = af_api_col('albums', 'artist_id');
    $hasAlb = af_api_col('songs', 'album_id');
    if (!array_key_exists('artist_id', $_POST) && $artist === null && $id) {   // keep stored name on update
        $old = af_row('SELECT artist' . ($hasArt ? ', artist_id' : '') . ' FROM albums WHERE id = ?', [$id]);
        if ($old) { $artist = $old['artist']; if ($hasArt) $artistId = !empty($old['artist_id']) ? (int) $old['artist_id'] : null; }
    }
    if ($artistId) {
        $ar = af_row('SELECT name FROM artists WHERE id = ? LIMIT 1', [$artistId]);
        if ($ar) { $artist = $ar['name']; } else { $artistId = null; }
    } elseif ($artist !== null && $artist !== '') {
        $ar = af_row('SELECT id FROM artists WHERE name = ? LIMIT 1', [$artist]);
        $artistId = $ar ? (int) $ar['id'] : null;
    }
    $year   = af_int('year') ?: null;
    $genre  = af_in('genre', 80) ?: null;
    $desc   = af_in('description', 600) ?: null;
    /* a new upload replaces the cover, anything else keeps the stored one */
    $oldCover = $id ? af_row('SELECT cover FROM albums WHERE id = ?', [$id]) : null;
    $cover  = af_up('cover_file', 'covers');
    if ($cover === null) $cover = af_in('cover', 255) ?: ($oldCover ? ($oldCover['cover'] ?: null) : null);
    /* tracks is maintained automatically from the songs inside the album
       (the same get_album_songs() the Album page and this panel list) */
    $tracks = $id ? count(get_album_songs((int) $id)) : 0;

    $map = [
        'title' => $title, 'artist' => $artist, 'cover' => $cover,
        'year' => $year, 'genre' => $genre, 'tracks' => $tracks, 'description' => $desc,
    ];
    if ($hasArt) $map['artist_id'] = $artistId;

    if ($id) {
        af_run('UPDATE albums SET ' . af_sql_set($map) . ' WHERE id = ?', array_merge(array_values($map), [$id]));
        done(['message' => 'Album updated.', 'id' => $id]);
    }
    $map['sort_order'] = 0;   // lists are newest-first (ORDER BY id DESC)
    af_run('INSERT INTO albums (' . implode(',', array_keys($map)) . ') VALUES ' . af_sql_marks($map), array_values($map));
    done(['message' => 'Album added.', 'id' => (int) $AF_DB->lastInsertId()]);
}

if ($action === 'album.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    $al = af_row('SELECT id, title FROM albums WHERE id = ? LIMIT 1', [$id]);
    if (!$al) fail('That album no longer exists.');
    $mode = af_in('mode', 10);   // keep (default) = songs survive, delete = they go too
    $hasAlb = af_api_col('songs', 'album_id');
    $hasJoin = af_api_table('album_songs');
    if ($mode === 'delete') {
        /* songs that belong to no other album go with it; shared ones stay */
        if ($hasJoin) {
            af_run('DELETE s FROM songs s
                     JOIN album_songs a ON a.song_id = s.id AND a.album_id = ?
                    WHERE NOT EXISTS (SELECT 1 FROM album_songs x WHERE x.song_id = s.id AND x.album_id <> ?)',
                [$id, $id]);
        }
        if ($hasAlb) {
            af_run('DELETE FROM songs WHERE album_id = ? OR (album = ? AND (album_id IS NULL OR album_id = 0))',
                [$id, (string) $al['title']]);
        } else {
            af_run('DELETE FROM songs WHERE album = ?', [(string) $al['title']]);
        }
        if ($hasJoin) af_run('DELETE FROM album_songs WHERE album_id = ?', [$id]);
        af_run('DELETE FROM albums WHERE id = ?', [$id]);
        done(['message' => 'Album and its songs deleted.']);
    }
    if ($hasAlb) af_run('UPDATE songs SET album_id = NULL WHERE album_id = ?', [$id]);   // keep the songs
    if ($hasJoin) af_run('DELETE FROM album_songs WHERE album_id = ?', [$id]);
    af_run('DELETE FROM albums WHERE id = ?', [$id]);
    done(['message' => 'Album deleted. Its songs were kept.']);
}

/* ========================================================================= */
/*  ARTISTS                                                                  */
/* ========================================================================= */

if ($action === 'artist.save') {
    $id      = af_int('id');
    $section = af_in('section', 20);
    if (!in_array($section, ['console', 'home', 'singer', 'feature', 'lineup'], true)) $section = 'lineup';
    $name = af_in('name', 160);
    if ($name === '') fail('Artist name is required.');

    $role    = af_in('role', 120) ?: null;
    $tagline = af_in('tagline', 191) ?: null;
    $desc    = af_in('description', 4000) ?: null;
    $accent  = af_in('accent', 16) ?: null;
    $genre   = af_in('genre', 80) ?: null;
    $birth   = af_in('birth', 40) ?: null;
    $place   = af_in('place', 120) ?: null;
    $debut   = af_int('debut') ?: null;
    $label   = af_in('label', 120) ?: null;
    $listen  = af_in('listeners', 20) ?: null;
    $gender  = af_in('gender', 10);
    if (!in_array($gender, ['male', 'female', 'other'], true)) $gender = 'male';
    $years   = af_in('years', 255) ?: null;
    if ($years !== null) {
        $parts = preg_split('/[^0-9]+/', $years, -1, PREG_SPLIT_NO_EMPTY);
        $years = $parts ? implode(',', array_slice($parts, 0, 12)) : null;
    }
    $url     = af_in('url', 255) ?: null;
    $rating  = (float) ($_POST['rating'] ?? 0);
    $feat    = af_flag('is_featured');
    $isNew   = af_flag('is_new');
    $inAlb   = af_flag('in_albums');   // "Show on Albums page" (admin/albums.php mirrors this list)
    /* a new upload replaces the photo, anything else keeps the stored one */
    $oldImg = $id ? af_row('SELECT image FROM artists WHERE id = ?', [$id]) : null;
    $image   = af_up('image_file', 'artists');
    if ($image === null) $image = af_in('image', 255) ?: ($oldImg ? ($oldImg['image'] ?: null) : null);

    if ($id) {
        af_run('UPDATE artists SET section=?,name=?,role=?,tagline=?,description=?,image=?,accent=?,genre=?,birth=?,place=?,debut=?,label=?,listeners=?,gender=?,years=?,url=?,rating=?,is_featured=?,is_new=?,in_albums=? WHERE id=?',
            [$section, $name, $role, $tagline, $desc, $image, $accent, $genre, $birth, $place,
             $debut, $label, $listen, $gender, $years, $url, $rating, $feat, $isNew, $inAlb, $id]);
        done(['message' => 'Artist updated.', 'id' => $id]);
    }
    af_run('INSERT INTO artists (section,name,role,tagline,description,image,accent,genre,birth,place,debut,label,listeners,gender,years,url,rating,is_featured,is_new,in_albums,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$section, $name, $role, $tagline, $desc, $image, $accent, $genre, $birth, $place,
         $debut, $label, $listen, $gender, $years, $url, $rating, $feat, $isNew, $inAlb, 0]);
    done(['message' => 'Artist added.', 'id' => (int) $AF_DB->lastInsertId()]);
}

if ($action === 'artist.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    if (!af_row('SELECT id FROM artists WHERE id = ? LIMIT 1', [$id])) fail('That artist no longer exists.');
    af_run('DELETE FROM artists WHERE id = ?', [$id]);
    done(['message' => 'Artist deleted.']);
}

/* ========================================================================= */
/*  CATEGORIES                                                               */
/* ========================================================================= */

if ($action === 'category.save') {
    $id   = af_int('id');
    $kind = af_in('kind', 20);
    $name = af_in('name', 80);
    if (!in_array($kind, ['genre', 'language', 'region'], true)) fail('Unknown category type.');
    if ($name === '') fail('Name is required.');
    if ($id) {
        af_run('UPDATE categories SET kind=?, name=? WHERE id=?', [$kind, $name, $id]);
        done(['message' => 'Category updated.', 'id' => $id]);
    }
    try {
        af_run('INSERT INTO categories (kind,name,sort_order) VALUES (?,?,?)', [$kind, $name, 0]);
    } catch (PDOException $e) {
        fail('That category already exists.');
    }
    done(['message' => 'Category added.', 'id' => (int) $AF_DB->lastInsertId()]);
}

if ($action === 'category.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    af_run('DELETE FROM categories WHERE id = ?', [$id]);
    done(['message' => 'Category deleted.']);
}

/* ========================================================================= */
/*  USERS                                                                    */
/* ========================================================================= */

if ($action === 'user.save') {
    $id = af_int('id');
    if (!$id) fail('Missing user id.');
    $row = af_row('SELECT id FROM users WHERE id = ? LIMIT 1', [$id]);
    if (!$row) fail('That account no longer exists.');

    $name = af_in('name', 80);
    $username = af_in('username', 40);
    $email = strtolower(af_in('email', 160));
    if ($name === '')     fail('Name is required.');
    if (!preg_match('/^[a-z0-9_.]{3,40}$/i', $username)) fail('Username must be 3-40 letters, numbers, "." or "_".');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');

    $dup = af_row('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1', [$email, $username, $id]);
    if ($dup) fail('Another account already uses that email or username.');

    $phone = af_in('phone', 30) ?: null;
    $address = af_in('address', 255) ?: null;
    $status = af_in('status', 20);
    if (!in_array($status, ['active', 'suspended'], true)) $status = 'active';

    af_run('UPDATE users SET name=?, username=?, email=?, phone=?, address=?, status=? WHERE id=?',
        [$name, $username, $email, $phone, $address, $status, $id]);

    /* optional password reset from the panel */
    $newPass = (string) ($_POST['password'] ?? '');
    if ($newPass !== '') {
        if (mb_strlen($newPass) < 6) fail('New password must be at least 6 characters.');
        af_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($newPass, PASSWORD_DEFAULT), $id]);
    }
    done(['message' => 'User updated.']);
}

if ($action === 'user.status') {
    $id = af_int('id');
    $status = af_in('status', 20);
    if (!in_array($status, ['active', 'suspended'], true)) fail('Unknown status.');
    if (!af_row('SELECT id FROM users WHERE id = ? LIMIT 1', [$id])) fail('That account no longer exists.');
    af_run('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    done(['message' => 'User ' . $status . '.']);
}

if ($action === 'user.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    if ($id === (int) ($_SESSION['uid'] ?? 0)) fail('You cannot delete the account you are signed in with.');
    if (!af_row('SELECT id FROM users WHERE id = ? LIMIT 1', [$id])) fail('That account no longer exists.');
    af_run('DELETE FROM users WHERE id = ?', [$id]);   // saved_* rows cascade
    done(['message' => 'User and their saved list deleted.']);
}

/* ========================================================================= */
/*  REVIEWS                                                                  */
/* ========================================================================= */

if ($action === 'review.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    if (!af_row('SELECT id FROM reviews WHERE id = ? LIMIT 1', [$id])) fail('That review no longer exists.');
    af_run('DELETE FROM reviews WHERE id = ?', [$id]);
    done(['message' => 'Review deleted.']);
}

if ($action === 'review.approve') {
    $id = af_int('id');
    $v = af_int('approved');
    af_run('UPDATE reviews SET approved = ? WHERE id = ?', [$v ? 1 : 0, $id]);
    done(['message' => $v ? 'Review approved.' : 'Review hidden.']);
}

/* ========================================================================= */
/*  CONTACT MESSAGES                                                         */
/* ========================================================================= */

if ($action === 'message.read') {
    $id = af_int('id');
    af_run('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [$id]);
    done(['message' => 'Marked as read.']);
}

if ($action === 'message.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    if (!af_row('SELECT id FROM contact_messages WHERE id = ? LIMIT 1', [$id])) fail('That message no longer exists.');
    af_run('DELETE FROM contact_messages WHERE id = ?', [$id]);
    done(['message' => 'Message deleted.']);
}

/* ========================================================================= */
/*  SAVED (community hearts)                                                 */
/* ========================================================================= */

if ($action === 'saved.delete') {
    $id = af_int('id');
    if (!$id) fail('Missing id.');
    if (!af_row('SELECT id FROM saved_songs WHERE id = ? LIMIT 1', [$id])) fail('That save no longer exists.');
    af_run('DELETE FROM saved_songs WHERE id = ?', [$id]);
    done(['message' => 'Removed from the saved list.']);
}

/* ========================================================================= */
/*  SETTINGS  (key/value - every field is whitelisted)                       */
/* ========================================================================= */

if ($action === 'settings.save') {
    $allowed = [
        'site_name', 'site_tagline', 'site_logo', 'admin_email',
        'theme_accent', 'theme_dark', 'theme_light', 'theme_alert', 'theme_name',
        'public_site', 'allow_signup', 'maintenance', 'notify_new_user',
        'footer_text', 'contact_email', 'contact_phone', 'contact_address', 'songs_per_page',
    ];
    $stored = 0;
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $_POST)) continue;
        $val = is_scalar($_POST[$key]) ? (string) $_POST[$key] : '';
        $val = mb_substr(trim($val), 0, 4000);
        af_run('REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)', [$key, $val]);
        $stored++;
    }
    $logo = af_up('logo_file', 'brand');
    if ($logo) {
        af_run('REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)', ['site_logo', $logo]);
        $stored++;
    }
    if (!$stored) fail('Nothing to save.');
    done(['message' => 'Website settings saved.']);
}

/* ========================================================================= */
/*  ADMIN PASSWORD                                                           */
/* ========================================================================= */

if ($action === 'password.change') {
    $me = af_admin();
    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $row = af_row('SELECT password_hash FROM admins WHERE id = ? LIMIT 1', [(int) $me['id']]);
    if (!$row || !password_verify($current, $row['password_hash'])) {
        fail('Your current password is not correct.');
    }
    if (mb_strlen($new) < 8) fail('New password must be at least 8 characters.');
    if ($new !== $confirm)   fail('The new passwords do not match.');
    af_run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), (int) $me['id']]);
    $_SESSION['af_csrf'] = bin2hex(random_bytes(32));
    done(['message' => 'Administrator password changed.']);
}

/* ---- name / email of the admin account ---------------------------------- */
if ($action === 'profile.save') {
    $me = af_admin();
    $name  = af_in('name', 80);
    $email = strtolower(af_in('email', 160));
    if ($name === '') fail('Name is required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
    $dup = af_row('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1', [$email, (int) $me['id']]);
    if ($dup) fail('That email is already used by another administrator.');
    af_run('UPDATE admins SET name = ?, email = ? WHERE id = ?', [$name, $email, (int) $me['id']]);
    done(['message' => 'Profile updated.']);
}

/* ========================================================================= */
/*  NOTIFICATIONS - the top bar bell (new users / reviews / messages)        */
/* ========================================================================= */

if ($action === 'notifications') {
    list($since, $sinceParams) = af_notif_since();
    $notify = af_setting('notify_new_user', '1') !== '0';
    $items  = [];

    /* age is diffed by MySQL itself, so PHP's timezone can never skew it */
    if ($notify) {
        foreach ((array) af_all(
            'SELECT name, email, created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) age
               FROM users WHERE ' . $since . ' ORDER BY created_at DESC LIMIT 10',
            $sinceParams
        ) as $u) {
            $items[] = [
                'kind'  => 'user',
                'title' => 'New user registered',
                'body'  => trim($u['name'] . '  ' . $u['email']),
                'href'  => af_admin_base() . '/admin/users.php',
                'age'   => max(0, (int) $u['age']),
            ];
        }
    }

    foreach ((array) af_all(
        'SELECT name, rating, comment, TIMESTAMPDIFF(SECOND, created_at, NOW()) age
           FROM reviews WHERE ' . $since . ' ORDER BY created_at DESC LIMIT 10',
        $sinceParams
    ) as $r) {
        $items[] = [
            'kind'  => 'review',
            'title' => 'New review (' . (int) $r['rating'] . '★)',
            'body'  => $r['name'] . ' — ' . mb_substr((string) $r['comment'], 0, 70),
            'href'  => af_admin_base() . '/admin/reviews.php',
            'age'   => max(0, (int) $r['age']),
        ];
    }

    foreach ((array) af_all(
        'SELECT name, subject, TIMESTAMPDIFF(SECOND, created_at, NOW()) age
           FROM contact_messages WHERE ' . $since . ' ORDER BY created_at DESC LIMIT 10',
        $sinceParams
    ) as $m) {
        $items[] = [
            'kind'  => 'message',
            'title' => 'New contact message',
            'body'  => trim(($m['subject'] ?? '') . ' — ' . $m['name']),
            'href'  => af_admin_base() . '/admin/messages.php',
            'age'   => max(0, (int) $m['age']),
        ];
    }

    usort($items, function ($a, $b) { return $a['age'] - $b['age']; });

    /* the badge counts everything since the last "mark as read"; the dropdown
       only shows the freshest 12 entries */
    $count = af_notif_count($notify);
    $items = array_slice($items, 0, 12);

    foreach ($items as &$it) {
        $age = (int) $it['age'];
        if ($age < 60)       $when = 'now';
        elseif ($age < 3600) $when = floor($age / 60) . 'm';
        elseif ($age < 86400)$when = floor($age / 3600) . 'h';
        else                 $when = floor($age / 86400) . 'd';
        $it['when'] = $when;
        unset($it['age']);
    }
    unset($it);

    af_json(['ok' => true, 'count' => $count, 'items' => $items]);
}

if ($action === 'notifications.read') {
    af_notif_mark_read();
    af_json(['ok' => true, 'message' => 'All notifications marked as read.']);
}

af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
