<?php
/* ==========================================================================
   COLUMN MIGRATIONS (includes/migrations.php)
   Idempotent: every change is guarded by an information_schema check, so the
   file can run on every request and twice in a row safely. Nothing existing
   is dropped or renamed - only missing columns are added.
   ========================================================================== */

function af_migrate_columns()
{
    global $AF_DB;
    static $ran = false;
    if ($ran || !$AF_DB) {
        return;
    }
    $ran = true;

    $defs = [
        // table,        column,        definition
        ['songs',  'artist_id',  'INT UNSIGNED NULL'],
        ['songs',  'album_id',   'INT UNSIGNED NULL'],
        ['albums', 'artist_id',  'INT UNSIGNED NULL'],
        ['users',  'is_demo',    "TINYINT(1) NOT NULL DEFAULT 0"],
        ['users',  'avatar_color', 'VARCHAR(16) DEFAULT NULL'],
        ['artists', 'in_albums', "TINYINT(1) NOT NULL DEFAULT 0"],
    ];

    foreach ($defs as $d) {
        list($table, $col, $ddl) = $d;
        try {
            $sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?';
            $st = $AF_DB->prepare($sql);
            $st->execute([$table, $col]);
            if ((int) $st->fetchColumn() === 0) {
                $AF_DB->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$ddl}");
                if ($table === 'artists' && $col === 'in_albums') {
                    /* one-time seed: every current Full Lineup singer starts on the
                       Albums page. Runs only with the column, so an admin can later
                       untick a singer without this re-enabling it. */
                    try {
                        $AF_DB->exec("UPDATE artists SET in_albums = 1 WHERE section = 'lineup'");
                    } catch (PDOException $e) {
                        @error_log('[AF migrate] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
                    }
                }
            }
        } catch (PDOException $e) {
            @error_log('[AF migrate] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
        }
    }

    /* index for the Albums-page filter (guarded, safe to run every request) */
    try {
        $st = $AF_DB->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $st->execute(['artists', 'idx_artists_in_albums']);
        if ((int) $st->fetchColumn() === 0) {
            $AF_DB->exec('CREATE INDEX idx_artists_in_albums ON artists (in_albums)');
        }
    } catch (PDOException $e) {
        @error_log('[AF migrate] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
    }

    /* album <-> song links (a song can live in any number of albums).
       Guarded, so it runs on every request and on an old database alike. */
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
        @error_log('[AF migrate] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
    }

    /* next automatic order value for a table (MAX(sort_order)+1) */
}

/** MAX(sort_order)+1 for a table - used by every admin insert. */
function af_next_order($table)
{
    global $AF_DB;
    if (!$AF_DB) {
        return 1;
    }
    $table = preg_replace('/[^a-z_]/i', '', (string) $table);
    try {
        $v = $AF_DB->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM `{$table}`")->fetchColumn();
        return max(1, (int) $v);
    } catch (PDOException $e) {
        return 1;
    }
}

af_migrate_columns();
