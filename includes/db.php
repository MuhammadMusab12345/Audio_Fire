<?php
/* ==========================================================================
   DATABASE (includes/db.php)
   One shared PDO connection for the whole site. Credentials come from the
   .env file (includes/env.php). If the connection cannot be made the page
   stops with "Database connection failed." (the password is never printed);
   if only a later setup step fails, $AF_DB stays NULL and the client falls
   back to localStorage (see assets/js/save.js).

   On first run the tables from database.sql are created automatically
   (CREATE TABLE IF NOT EXISTS) so the save / review / auth features work
   out of the box on XAMPP.
   ========================================================================== */

require_once __DIR__ . '/env.php';     // .env loader (env()) - works from / and /admin
require_once __DIR__ . '/config.php';

/** @var PDO|null $AF_DB  shared connection (NULL when MySQL is down) */
$AF_DB = null;

/* ---- connect (lazy, once per request) ---------------------------------- */
static $connected = false;
if (!$connected) {
    $connected = true;
    try {
        /* connect to the server first (without picking a database) so the
           database itself can be created on a fresh XAMPP install */
        $serverDsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            AF_DB_HOST, AF_DB_PORT, AF_DB_CHARSET
        );
        /* ---- connect (credentials come from .env, never printed) --------- */
        try {
            $AF_DB = new PDO($serverDsn, AF_DB_USER, AF_DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // fail loud (we catch above)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
            ]);
        } catch (PDOException $e) {
            // wrong host / user / password -> say so, print no details (no password)
            @error_log('[AF db] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
            $AF_DB = null;
            die('Database connection failed.');
        }

        /* some hosts do not allow CREATE DATABASE - the schema already
           exists there, so that failure is not fatal (only logged) */
        try {
            $AF_DB->exec(
                'CREATE DATABASE IF NOT EXISTS `' . AF_DB_NAME . '`
                 DEFAULT CHARACTER SET ' . AF_DB_CHARSET . '
                 COLLATE utf8mb4_unicode_ci'
            );
        } catch (PDOException $e) {
            @error_log('[AF db] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
        }

        /* wrong DB_NAME -> the connection is useless, never print credentials */
        try {
            $AF_DB->exec('USE `' . AF_DB_NAME . '`');
        } catch (PDOException $e) {
            @error_log('[AF db] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
            $AF_DB = null;
            die('Database connection failed.');
        }

        /* ---- pin MySQL's session clock to PHP's timezone -----------------
           XAMPP's server clock and date.timezone can be hours apart, and
           every created_at / NOW() below is stamped by MySQL while every
           label, grouping and boundary in PHP comes from date(). Leaving the
           session on its default zone shifts "today" and makes the
           notification / activity windows unreliable. date('P') always
           yields +-HH:MM, which is exactly what SET time_zone expects. */
        $AF_DB->exec("SET time_zone = '" . date('P') . "'");

        /* ---- auto migrate: create the tables once if they are missing ---- */
        $has = $AF_DB->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$has) {
            require_once __DIR__ . '/migrate.php';   // runs database.sql (CREATE TABLE IF NOT EXISTS ...)
        }
        /* ---- idempotent column migrations (artist_id, album_id, is_demo...) */
        require_once __DIR__ . '/migrations.php';
    } catch (PDOException $e) {
        // MySQL down / wrong credentials -> log it, keep the site alive.
        $AF_DB = null;
        @error_log('[AF db] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
    }
}

/* ---- tiny helpers (used by api/* endpoints) ---------------------------- */

/** Fetch all rows of a prepared statement. */
function af_all($sql, array $params = [])
{
    global $AF_DB;
    $st = $AF_DB->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Fetch one row (or NULL) of a prepared statement. */
function af_row($sql, array $params = [])
{
    global $AF_DB;
    $st = $AF_DB->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Run an INSERT/UPDATE/DELETE and return the affected row count. */
function af_run($sql, array $params = [])
{
    global $AF_DB;
    $st = $AF_DB->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
