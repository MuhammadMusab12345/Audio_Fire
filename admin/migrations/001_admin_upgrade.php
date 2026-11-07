<?php
/* ==========================================================================
   MIGRATION 001 - ADMIN DATA UPGRADE (admin/migrations/001_admin_upgrade.php)
   --------------------------------------------------------------------------
   Idempotent: every step is guarded, so the file can run twice in a row and
   on an already-upgraded database without touching existing data.

     1. columns   songs.artist_id, songs.album_id, albums.artist_id (INT NULL)
                  users.is_demo (tiny flag for the seeded demo accounts)
     2. indexes   one index per new foreign-ish column
     3. backfill  link songs/albums to artists and albums by a case-insensitive
                  TRIMMED name match; rows with no match simply stay NULL
     4. seed      15 realistic demo users (is_demo = 1), skipped when present

   The old text columns (songs.artist, songs.album, albums.artist) are never
   dropped - the admin api keeps them in sync with the ids on every save.

   Run it once:   php admin/migrations/001_admin_upgrade.php
   ========================================================================== */

require_once __DIR__ . '/../../includes/db.php';   // $AF_DB + auto table setup

if (!$AF_DB) {
    echo "Database unavailable - nothing to migrate.\n";
    exit(0);
}

/** Does the column already exist? */
function m_has_column($table, $column)
{
    global $AF_DB;
    $st = $AF_DB->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $column]);
    return (int) $st->fetchColumn() > 0;
}

/** Does the index already exist? */
function m_has_index($table, $index)
{
    global $AF_DB;
    $st = $AF_DB->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute([$table, $index]);
    return (int) $st->fetchColumn() > 0;
}

$done = [];

/* ---- 1. columns ---------------------------------------------------------- */
$columns = [
    ['songs',  'artist_id', 'INT UNSIGNED NULL'],
    ['songs',  'album_id',  'INT UNSIGNED NULL'],
    ['albums', 'artist_id', 'INT UNSIGNED NULL'],
    ['users',  'is_demo',   'TINYINT(1) NOT NULL DEFAULT 0'],
];
foreach ($columns as $c) {
    if (m_has_column($c[0], $c[1])) {
        continue;
    }
    $AF_DB->exec("ALTER TABLE `{$c[0]}` ADD COLUMN `{$c[1]}` {$c[2]}");
    $done[] = "column {$c[0]}.{$c[1]} added";
}

/* ---- 2. indexes ---------------------------------------------------------- */
$indexes = [
    ['songs',  'idx_songs_artist_id',  'artist_id'],
    ['songs',  'idx_songs_album_id',   'album_id'],
    ['albums', 'idx_albums_artist_id', 'artist_id'],
];
foreach ($indexes as $i) {
    if (m_has_index($i[0], $i[1])) {
        continue;
    }
    $AF_DB->exec("ALTER TABLE `{$i[0]}` ADD INDEX `{$i[1]}` (`{$i[2]}`)");
    $done[] = "index {$i[0]}.{$i[1]} added";
}

/* ---- 3. backfill by trimmed, case-insensitive name match ----------------- */
/* Songs -> artists (singer name) */
$n = $AF_DB->exec(
    "UPDATE songs s
        SET s.artist_id = (
              SELECT a.id FROM artists a
               WHERE LOWER(TRIM(a.name)) = LOWER(TRIM(s.artist))
               ORDER BY a.id LIMIT 1)
      WHERE s.artist_id IS NULL
        AND s.artist IS NOT NULL AND TRIM(s.artist) <> ''
        AND EXISTS (SELECT 1 FROM artists a
                     WHERE LOWER(TRIM(a.name)) = LOWER(TRIM(s.artist)))"
);
if ($n > 0) {
    $done[] = "{$n} song(s) linked to an artist";
}

/* Songs -> albums (album title) */
$n = $AF_DB->exec(
    "UPDATE songs s
        SET s.album_id = (
              SELECT b.id FROM albums b
               WHERE LOWER(TRIM(b.title)) = LOWER(TRIM(s.album))
               ORDER BY b.id LIMIT 1)
      WHERE s.album_id IS NULL
        AND s.album IS NOT NULL AND TRIM(s.album) <> ''
        AND EXISTS (SELECT 1 FROM albums b
                     WHERE LOWER(TRIM(b.title)) = LOWER(TRIM(s.album)))"
);
if ($n > 0) {
    $done[] = "{$n} song(s) linked to an album";
}

/* Albums -> artists (artist name) */
$n = $AF_DB->exec(
    "UPDATE albums al
        SET al.artist_id = (
              SELECT a.id FROM artists a
               WHERE LOWER(TRIM(a.name)) = LOWER(TRIM(al.artist))
               ORDER BY a.id LIMIT 1)
      WHERE al.artist_id IS NULL
        AND al.artist IS NOT NULL AND TRIM(al.artist) <> ''
        AND EXISTS (SELECT 1 FROM artists a
                     WHERE LOWER(TRIM(a.name)) = LOWER(TRIM(al.artist)))"
);
if ($n > 0) {
    $done[] = "{$n} album(s) linked to an artist";
}

/* ---- 4. fifteen realistic demo users ------------------------------------- */
$demoUsers = [
    ['Ali Raza',      'ali.raza',      'ali.raza@example.com',      '0300 1234567', 'House 12, Street 4, Lahore'],
    ['Sana Iqbal',    'sana.iqbal',    'sana.iqbal@example.com',    '0311 2233445', 'Flat 3B, Gulberg, Lahore'],
    ['Bilal Ahmed',   'bilal.ahmed',   'bilal.ahmed@example.com',   '0322 5566778', 'Plot 45, G-11, Islamabad'],
    ['Hina Malik',    'hina.malik',    'hina.malik@example.com',    '0333 8899001', '22 Canal Road, Lahore'],
    ['Usman Tariq',   'usman.tariq',   'usman.tariq@example.com',   '0345 1122334', '7 University Road, Karachi'],
    ['Zara Khan',     'zara.khan',     'zara.khan@example.com',     '0301 4455667', 'Block C, DHA Phase 5, Karachi'],
    ['Hamza Siddiq',  'hamza.siddiq',  'hamza.siddiq@example.com',  '0312 7788990', '19 Mall Road, Murree'],
    ['Ayesha Noor',   'ayesha.noor',   'ayesha.noor@example.com',   '0321 3344556', 'Street 8, F-7, Islamabad'],
    ['Kamran Bashir', 'kamran.bashir', 'kamran.bashir@example.com', '0334 6677889', '41 Sharah-e-Faisal, Karachi'],
    ['Fatima Javed',  'fatima.javed',  'fatima.javed@example.com',  '0302 9900112', 'House 5, Cantt, Rawalpindi'],
    ['Danish Ali',    'danish.ali',    'danish.ali@example.com',    '0313 2233441', 'Block B, Johar Town, Lahore'],
    ['Mehwish Aslam', 'mehwish.aslam', 'mehwish.aslam@example.com', '0323 5566771', '3 Bank Road, Peshawar'],
    ['Shahzaib Khan', 'shahzaib.khan', 'shahzaib.khan@example.com', '0335 8899002', 'Sector G-9, Islamabad'],
    ['Noor Fatima',   'noor.fatima',   'noor.fatima@example.com',   '0303 1122339', '2 Canal View, Lahore'],
    ['Talha Mehmood', 'talha.mehmood', 'talha.mehmood@example.com', '0314 4455662', 'Saddar Bazaar, Rawalpindi'],
];

$present = (int) $AF_DB->query('SELECT COUNT(*) FROM users WHERE is_demo = 1')->fetchColumn();
if ($present >= count($demoUsers)) {
    $done[] = "demo users: already seeded ({$present})";
} else {
    $hash = password_hash('demo1234', PASSWORD_DEFAULT);
    $st   = $AF_DB->prepare(
        'INSERT INTO users (name, username, email, phone, address, role, status, password_hash, is_demo)
         VALUES (?, ?, ?, ?, ?, \'user\', \'active\', ?, 1)'
    );
    $added = 0;
    foreach ($demoUsers as $u) {
        /* skip anything that is already on the table (unique email/username) */
        $exists = $AF_DB->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $exists->execute([$u[1], $u[2]]);
        if ((int) $exists->fetchColumn() > 0) {
            continue;
        }
        try {
            $st->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $hash]);
            $added++;
        } catch (PDOException $e) {
            @error_log('[AF migration 001] demo user skipped: ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
        }
    }
    if ($added) {
        $done[] = "{$added} demo user(s) seeded";
    }
}

/* ---- report -------------------------------------------------------------- */
if ($done) {
    echo "Migration 001 complete:\n  - " . implode("\n  - ", $done) . "\n";
} else {
    echo "Migration 001: nothing to do (already up to date).\n";
}
