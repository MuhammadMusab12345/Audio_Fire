<?php
/* ==========================================================================
   MIGRATION 002 - ALBUM SONGS (admin/migrations/002_album_songs.php)
   --------------------------------------------------------------------------
   Idempotent: every step is guarded, so the file can run twice in a row and
   on an already-seeded database without touching existing rows.

     1. table     album_songs (album <-> song join: one song may belong to any
                  number of albums) - the same CREATE the site keeps running
                  from includes/migrations.php
     2. albums    every singer flagged in_albums = 1 that has no album yet
                  gets "<Artist name> - Best Songs" (artist_id + cover set)
     3. seed      every Albums-page album with no album_songs rows receives 6
                  random songs from the Songs library (section = 'video');
                  an album that already carries rows is never re-seeded, and
                  albums.tracks is kept in step with the rows inserted

   Run it once:   php admin/migrations/002_album_songs.php
   ========================================================================== */

require_once __DIR__ . '/../../includes/db.php';   // $AF_DB + auto table setup

if (!$AF_DB) {
    echo "Database unavailable - nothing to migrate.\n";
    exit(0);
}

/* ---- 1. table ----------------------------------------------------------- */
try {
    $AF_DB->exec(
        'CREATE TABLE IF NOT EXISTS album_songs (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            album_id   INT UNSIGNED NOT NULL,
            song_id    INT UNSIGNED NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_album_songs (album_id, song_id),
            KEY idx_album_songs_album (album_id),
            KEY idx_album_songs_song (song_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
} catch (PDOException $e) {
    echo "Could not create album_songs: " . $e->getMessage() . "\n";
    exit(1);
}
echo "album_songs table ready.\n";

/* ---- 2. one album per Albums-page singer that has none ------------------ */
$singers = af_all('SELECT id, name, image FROM artists WHERE in_albums = 1 ORDER BY id ASC');
$made = 0;
foreach ($singers as $s) {
    $aid = (int) $s['id'];
    $nm  = (string) $s['name'];
    if ($nm === '' || $aid <= 0) {
        continue;
    }
    /* any album that already claims this singer counts - never add a second */
    $has = af_row('SELECT id FROM albums WHERE artist_id = ? OR artist = ? LIMIT 1', [$aid, $nm]);
    if ($has) {
        continue;
    }
    $title = $nm . ' - Best Songs';
    af_run(
        'INSERT INTO albums (title, artist, cover, tracks, sort_order, artist_id)
         VALUES (?, ?, ?, 0, 0, ?)',
        [$title, $nm, ($s['image'] ?: null), $aid]
    );
    $made++;
}
echo "albums created: {$made}\n";

/* ---- 3. seed 6 random Songs-library tracks per empty album -------------- */
$names = [];
$ids   = [];
foreach ($singers as $s) {
    $names[] = (string) $s['name'];
    $ids[]   = (int) $s['id'];
}
$seeded = 0;
$tracks = 0;
if ($ids) {
    foreach (af_all('SELECT id, title, artist, artist_id FROM albums ORDER BY id ASC') as $al) {
        $onPage = in_array((int) ($al['artist_id'] ?? 0), $ids, true)
            || in_array((string) ($al['artist'] ?? ''), $names, true);
        if (!$onPage) {
            continue;                       // not shown on the Albums page
        }
        $aid = (int) $al['id'];
        $cnt = af_row('SELECT COUNT(*) c FROM album_songs WHERE album_id = ?', [$aid]);
        if ((int) ($cnt['c'] ?? 0) > 0) {
            continue;                       // already seeded - leave it alone
        }
        $pool = af_all("SELECT id FROM songs WHERE section = 'video' ORDER BY RAND() LIMIT 6");
        $n = 0;
        foreach ($pool as $p) {
            af_run('INSERT INTO album_songs (album_id, song_id, sort_order) VALUES (?, ?, ?)',
                [$aid, (int) $p['id'], $n]);
            $n++;
        }
        af_run('UPDATE albums SET tracks = ? WHERE id = ?', [$n, $aid]);
        if ($n > 0) {
            $seeded++;
            $tracks += $n;
        }
    }
}
echo "albums seeded: {$seeded} ({$tracks} tracks)\n";
echo "done.\n";
