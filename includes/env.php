<?php
/* ==========================================================================
   ENV LOADER (includes/env.php)
   Plain PHP - no composer, no libraries. Reads the .env file in the project
   root (walks up from this folder, so it also works from /admin) and exposes
   env('KEY', $default).
   ========================================================================== */

if (!defined('AF_ENV_LOADED')) {
    define('AF_ENV_LOADED', true);

    /* walk up from includes/ until the .env file is found */
    $afEnvFile = '';
    $afEnvDir  = __DIR__;
    for ($i = 0; $i < 12; $i++) {
        $afTry = $afEnvDir . DIRECTORY_SEPARATOR . '.env';
        if (is_file($afTry)) {
            $afEnvFile = $afTry;
            break;
        }
        $afUp = dirname($afEnvDir);
        if ($afUp === $afEnvDir) {
            break;
        }
        $afEnvDir = $afUp;
    }
    if ($afEnvFile === '') {
        die('.env file is missing.');
    }

    /* skip empty lines and lines starting with #, split at the first "=",
       trim spaces and quotes, store the values */
    $GLOBALS['AF_ENV'] = [];
    $afLines = file($afEnvFile, FILE_IGNORE_NEW_LINES);
    if ($afLines !== false) {
        foreach ($afLines as $afLine) {
            $afLine = trim($afLine);
            if ($afLine === '' || $afLine[0] === '#') {
                continue;
            }
            $afPos = strpos($afLine, '=');
            if ($afPos === false) {
                continue;
            }
            $afKey = trim(substr($afLine, 0, $afPos));
            $afVal = trim(substr($afLine, $afPos + 1));
            $afLen = strlen($afVal);
            if ($afLen >= 2) {
                $afQ = $afVal[0];
                if (($afQ === '"' || $afQ === "'") && substr($afVal, -1) === $afQ) {
                    $afVal = substr($afVal, 1, $afLen - 2);
                }
            }
            if ($afKey !== '') {
                $GLOBALS['AF_ENV'][$afKey] = $afVal;
            }
        }
    }
    unset($afEnvFile, $afEnvDir, $afTry, $afUp, $afLines, $afLine, $afPos, $afKey, $afVal, $afLen, $afQ, $i);
}

/** Get a value from the .env file (or $default when the key is not set). */
function env($key, $default = null)
{
    $store = $GLOBALS['AF_ENV'] ?? [];
    return array_key_exists($key, $store) ? $store[$key] : $default;
}
