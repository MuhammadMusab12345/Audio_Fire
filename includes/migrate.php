<?php
/* ==========================================================================
   MIGRATE (includes/migrate.php)
   Runs database.sql (CREATE TABLE IF NOT EXISTS ...) the first time a page
   loads while the database exists but the tables do not. Called once by
   includes/db.php. The statements are read from database.sql so the file
   stays the single source of truth for the schema.
   ========================================================================== */

$sqlFile = __DIR__ . '/../database.sql';
if (!is_readable($sqlFile)) {
    return;
}

$raw = file_get_contents($sqlFile);

/* strip "-- ..." comment lines */
$raw = preg_replace('/^\s*--.*$/m', '', $raw);

/* one statement per ';' (the file has no DELIMITER / procedure blocks) */
$statements = array_filter(array_map('trim', explode(';', $raw)));

foreach ($statements as $sql) {
    if ($sql === '') {
        continue;
    }
    /* skip statements that need extra privileges or change the default DB */
    if (preg_match('/^\s*(CREATE\s+DATABASE|USE)\b/i', $sql)) {
        continue;
    }
    try {
        $AF_DB->exec($sql);
    } catch (PDOException $e) {
        @error_log('[AF migrate] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
    }
}
