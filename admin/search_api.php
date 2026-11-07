<?php
/* ==========================================================================
   GLOBAL SEARCH API (admin/search_api.php)
   --------------------------------------------------------------------------
   Driven by the top-bar box in includes/header.php and includes/footer.php:

       GET admin/search_api.php?q=...   ->   JSON

   Four groups, max 5 rows each:
       songs    section = "music" -> chip "Music", icon music
                section = "video" -> chip "Song",  icon headphones
       artists  chip "Artist", icon mic
       albums   chip "Album",  icon disc
       users    chip "User",   icon users

   Every statement is prepared through af_all() (no string interpolation of
   user input) and every value that reaches the browser leaves through e(),
   so the client can drop the results straight into HTML.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';

/* Bounces guests to login.php - the panel is never searchable anonymously. */
af_admin_required();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$base = af_admin_base();
$q    = trim((string) ($_GET['q'] ?? ''));
/* LIKE wildcards are not user input: strip them rather than let them widen
   the match, and keep the term short so one request cannot scan the world. */
$q    = str_replace(['%', '_'], '', $q);
$q    = mb_substr($q, 0, 64);
$like = '%' . $q . '%';

$groups = [];

if (mb_strlen($q) >= 2) {
    try {
        /* ---------------------------------------------------------- songs */
        $rows = af_all(
            'SELECT id, title, artist, section FROM songs
              WHERE title LIKE ? OR artist LIKE ? OR album LIKE ?
              ORDER BY title ASC, id DESC
              LIMIT 5',
            [$like, $like, $like]
        );
        $items = [];
        foreach ($rows as $r) {
            $video  = (string) ($r['section'] ?? '') === 'video';
            $artist = trim((string) ($r['artist'] ?? ''));
            $items[] = [
                'icon'     => $video ? 'headphones' : 'music',
                'title'    => e($r['title'] ?? ''),
                'subtitle' => e($artist !== '' ? $artist : ($video ? 'Video' : 'Music')),
                'chip'     => $video ? 'Song' : 'Music',
                'url'      => $base . '/admin/' . ($video ? 'videos' : 'songs') . '.php?edit=' . (int) $r['id'],
            ];
        }
        $groups[] = ['key' => 'songs', 'label' => 'Songs', 'items' => $items];

        /* -------------------------------------------------------- artists */
        $rows = af_all(
            'SELECT id, name, role, genre FROM artists
              WHERE name LIKE ? OR role LIKE ? OR genre LIKE ?
              ORDER BY name ASC, id DESC
              LIMIT 5',
            [$like, $like, $like]
        );
        $items = [];
        foreach ($rows as $r) {
            $sub = trim((string) ($r['role'] ?? ''));
            if ($sub === '') {
                $sub = trim((string) ($r['genre'] ?? ''));
            }
            $items[] = [
                'icon'     => 'mic',
                'title'    => e($r['name'] ?? ''),
                'subtitle' => e($sub !== '' ? $sub : 'Artist'),
                'chip'     => 'Artist',
                'url'      => $base . '/admin/artists.php?edit=' . (int) $r['id'],
            ];
        }
        $groups[] = ['key' => 'artists', 'label' => 'Artists', 'items' => $items];

        /* --------------------------------------------------------- albums */
        $rows = af_all(
            'SELECT id, title, artist, year FROM albums
              WHERE title LIKE ? OR artist LIKE ? OR genre LIKE ?
              ORDER BY title ASC, id DESC
              LIMIT 5',
            [$like, $like, $like]
        );
        $items = [];
        foreach ($rows as $r) {
            $sub = trim((string) ($r['artist'] ?? ''));
            if ($sub === '' && !empty($r['year'])) {
                $sub = (string) $r['year'];
            }
            $items[] = [
                'icon'     => 'disc',
                'title'    => e($r['title'] ?? ''),
                'subtitle' => e($sub !== '' ? $sub : 'Album'),
                'chip'     => 'Album',
                'url'      => $base . '/admin/albums.php?edit=' . (int) $r['id'],
            ];
        }
        $groups[] = ['key' => 'albums', 'label' => 'Albums', 'items' => $items];

        /* ---------------------------------------------------------- users */
        $rows = af_all(
            'SELECT id, name, username, email FROM users
              WHERE name LIKE ? OR username LIKE ? OR email LIKE ?
              ORDER BY name ASC, id DESC
              LIMIT 5',
            [$like, $like, $like]
        );
        $items = [];
        foreach ($rows as $r) {
            $sub = trim((string) ($r['email'] ?? ''));
            if ($sub === '') {
                $sub = trim((string) ($r['username'] ?? ''));
            }
            $items[] = [
                'icon'     => 'users',
                'title'    => e($r['name'] ?? ''),
                'subtitle' => e($sub !== '' ? $sub : 'User'),
                'chip'     => 'User',
                'url'      => $base . '/admin/users.php?edit=' . (int) $r['id'],
            ];
        }
        $groups[] = ['key' => 'users', 'label' => 'Users', 'items' => $items];
    } catch (PDOException $e) {
        /* A missing table must never surface as a 500 to the search box. */
        @error_log('[AF search] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
        $groups = [];
    }
}

echo json_encode(
    ['ok' => true, 'q' => $q, 'groups' => $groups],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
