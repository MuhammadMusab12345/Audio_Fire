<?php
/* ==========================================================================
   CONFIG (includes/config.php)
   Central configuration for the whole site: database credentials (read from
   the .env file), site URL and security switches. Loaded by
   includes/auth.php -> every page + every api/ endpoint. This file NEVER
   outputs anything (safe to include before headers are sent) and is blocked
   for direct web access by .htaccess.

   !!! Keep real credentials in .env only - never copy them into public files.
   ========================================================================== */

/* ---- MySQL (from the .env file in the project root) --------------------- */
require_once __DIR__ . '/env.php';    // env() - reads .env (works from / and /admin)

define('AF_DB_HOST', env('DB_HOST', 'localhost'));
define('AF_DB_PORT', 3306);
define('AF_DB_NAME', env('DB_NAME', 'audio_fire'));
define('AF_DB_USER', env('DB_USER', 'root'));
define('AF_DB_PASS', env('DB_PASS', ''));            // never printed anywhere
define('AF_DB_CHARSET', 'utf8mb4');

/* ---- Site -------------------------------------------------------------- */
define('AF_SITE_NAME', 'Audio Fire');
define('AF_BASE_URL', '/Final Audio Fire Website/Opencode');  // folder of the site inside htdocs

/* ---- Security switches -------------------------------------------------- */
// PHP must never print errors on screen in production - they go to a log file.
define('AF_ERROR_LOG', __DIR__ . '/../data/php-errors.log');
define('AF_HIDE_ERRORS', true);
// Simple per-session throttle for public forms (reviews / contact).
define('AF_FORM_WAIT_SECONDS', 20);   // min seconds between two submissions
define('AF_FORM_HONEYPOT', 'af_company');
