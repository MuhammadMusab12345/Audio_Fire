<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$q    = trim((string) ($_GET['q'] ?? ''));
$q    = mb_strlen($q) > 60 ? mb_substr($q, 0, 60) : $q;
$like = '%' . $q . '%';

$items = [];
$ok    = false;

if ($AF_DB && $q !== '') {
    $ok = true;
    try {
        $songRows = af_all(
            "SELECT id, title, artist, thumb, media, section FROM songs
             WHERE section IN ('music','video')
               AND (title LIKE ? OR artist LIKE ?)
             ORDER BY id DESC
             LIMIT 8",
            [$like, $like]
        );
        foreach ($songRows as $r) {
            $isVideo = (string) ($r['section'] ?? '') === 'video';
            $items[] = [
                'type'  => $isVideo ? 'Song' : 'Music',
                'title' => (string) ($r['title'] ?? ''),
                'sub'   => (string) ($r['artist'] ?? ''),
                'img'   => (string) ($r['thumb'] ?? ''),
                'url'   => $isVideo ? 'songs.php' : 'music.php',
            ];
        }

        $artistRows = af_all(
            'SELECT id, name, image FROM artists
             WHERE name LIKE ?
             ORDER BY id DESC
             LIMIT 8',
            [$like]
        );
        foreach ($artistRows as $r) {
            $items[] = [
                'type'  => 'Artist',
                'title' => (string) ($r['name'] ?? ''),
                'sub'   => 'Singer',
                'img'   => (string) ($r['image'] ?? ''),
                'url'   => 'singers.php',
            ];
        }

        $albumRows = af_all(
            'SELECT id, title, cover, artist FROM albums
             WHERE title LIKE ? OR artist LIKE ?
             ORDER BY id DESC
             LIMIT 8',
            [$like, $like]
        );
        foreach ($albumRows as $r) {
            $items[] = [
                'type'  => 'Album',
                'title' => (string) ($r['title'] ?? ''),
                'sub'   => (string) ($r['artist'] ?? ''),
                'img'   => (string) ($r['cover'] ?? ''),
                'url'   => 'album.php',
            ];
        }
    } catch (Throwable $e) {
        $items = [];
        $ok    = false;
    }
}

echo json_encode(['ok' => $ok, 'items' => $items]);
