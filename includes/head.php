<?php
/* ==========================================================================
   SHARED <head> + <body> opener - used by EVERY page of the site
   --------------------------------------------------------------------------
   Set these variables BEFORE requiring this file:

     $currentPage     'home' | 'music' | 'reviews' | 'account' | 'songs'
                      | 'album' | 'singers' | 'playlists' | 'contact'
                      (navbar.php highlights the matching link)

     $pageTitle       <title> text

     $metaDescription optional meta description

     $pageStyles      optional array of page specific CSS files,
                      loaded AFTER the shared ones (assets/css/music.php ...)

    $pageFontHref    optional extra Google Fonts URL (page specific families),
                      loaded after the shared fonts link

   $BASE is the install folder ("" at the document root, "/sub" in a
   sub folder) - page links are built as <?= $BASE ?>/page.php
   ========================================================================== */

/* ---- security + session bootstrap (runs BEFORE any HTML output) --------
    Starts the hardened session, hides PHP errors, loads the database and
    provides e() / af_csrf() / af_user() / af404() (includes/auth.php). */
require_once __DIR__ . '/auth.php';

/* ---- response hygiene (security headers live in .htaccess so that static
    files get them too; here we only hide the PHP version signature) ------- */
if (!headers_sent()) {
    header_remove('X-Powered-By');
}

if (!isset($currentPage))     $currentPage = 'home';
if (!isset($pageTitle))       $pageTitle = 'Audio Fire';
if (!isset($metaDescription)) $metaDescription = 'Cloud AI - Interactive audio-visual showcase featuring premier recording artists, cinematic WebGL smoke, and cursor-driven particle typography.';
if (!isset($pageStyles))      $pageStyles = array();
if (!isset($pageFontHref))    $pageFontHref = '';

/**
 * Cache-busting stamp for the stylesheets.
 *
 * None of the site's CSS files send a Cache-Control or Expires header, so a
 * browser falls back to heuristic freshness and can keep serving an old sheet
 * for hours after a redesign - the page markup is always fresh (it is PHP),
 * which makes the result look half-updated. Appending the newest file time
 * under assets/css/ to the URL changes the URL whenever any sheet changes and
 * forces an immediate re-download.
 */
function af_css_v()
{
    static $stamp = null;
    if ($stamp === null) {
        $stamp = 1;
        foreach ((array) glob(__DIR__ . '/../assets/css/*.css') as $afCssFile) {
            $afMtime = @filemtime($afCssFile);
            if ($afMtime && $afMtime > $stamp) {
                $stamp = $afMtime;
            }
        }
    }
    return $stamp;
}

$scriptDir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'));
$BASE = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : rtrim($scriptDir, '/');
/* af404() can render this page from inside an api/ script - the install
   folder is then forced so all links point back at the site root */
if (isset($AF_FORCE_BASE)) {
    $BASE = $AF_FORCE_BASE;
}

/* ---- values the shared JS needs (assets/js/save.js, api/* calls) ------- */
$afUser    = af_user();                    // logged-in row or NULL
$afAvatar  = '';
if ($afUser) {
    $afAvatar = 'https://i.pravatar.cc/80?u=' . urlencode($afUser['email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
<title><?= htmlspecialchars($pageTitle) ?></title>

<!-- Values for the shared JS: CSRF token for every AJAX call + the logged-in
     user (empty when visiting as a guest). Never contains secrets. -->
<meta name="af-csrf" content="<?= e(af_csrf()) ?>">
<meta name="af-user" content="<?= $afUser ? e($afUser['name']) : '' ?>">
<meta name="af-user-avatar" content="<?= e($afAvatar) ?>">
<meta name="af-base" content="<?= e($BASE) ?>">

<!-- Favicon (same image as the admin panel) -->
<link rel="icon" type="image/png" href="<?= e($BASE) ?>/assets/img/favicon.png">

<!-- Google Fonts: Montserrat (hero & body) + Playfair Display (headings)
     + Inter / Manrope (Reviews, Account and Music pages) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;700&family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,500&display=swap" rel="stylesheet">
<?php if ($pageFontHref !== ''): ?>
<link href="<?= htmlspecialchars($pageFontHref) ?>" rel="stylesheet">
<?php endif; ?>

<!-- Shared stylesheets (the ?v= stamp busts the browser cache - see af_css_v()) -->
<link rel="stylesheet" href="assets/css/base.css?v=<?= af_css_v() ?>">
<link rel="stylesheet" href="assets/css/navbar.css?v=<?= af_css_v() ?>">
<link rel="stylesheet" href="assets/css/footer.css?v=<?= af_css_v() ?>">
<!-- style.css comes after navbar/footer so its theme bridge wins over their fallbacks -->
<link rel="stylesheet" href="assets/css/style.css?v=<?= af_css_v() ?>">
<?php foreach ($pageStyles as $cssFile): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>?v=<?= af_css_v() ?>">
<?php endforeach; ?>
</head>
<body>
