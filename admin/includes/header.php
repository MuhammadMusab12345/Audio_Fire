<?php
/* ==========================================================================
   ADMIN SHELL (admin/includes/header.php)
   --------------------------------------------------------------------------
   Every admin page runs, after its own PHP, a plain:

       require __DIR__ . '/includes/header.php';

   which opens the document, prints the sidebar + top bar + page head and
   leaves <div class="content"> open for the page to fill. The matching
   includes/footer.php closes it.

   Expected from the page (all optional, sensible defaults below):
       $adminTitle     - <h1> in the page head
       $adminCrumb     - small eyebrow line above the title
       $adminSubtitle  - one line of supporting copy under the title
       $adminActions   - raw HTML for the buttons on the right of the head
       $activeNav      - key in $adminNav to mark as current
   ========================================================================== */

if (!isset($adminTitle))    $adminTitle = 'Dashboard';
if (!isset($adminCrumb))    $adminCrumb = '';
if (!isset($adminSubtitle)) $adminSubtitle = '';
if (!isset($adminActions))  $adminActions = '';
if (!isset($activeNav))     $activeNav = '';

$me      = af_admin();
$base    = af_admin_base();
$logoSrc = $base . '/assets/music-logo.png';
$csrf    = af_csrf();
$site    = af_setting('site_name', 'Audio Fire');
$notifN  = af_notif_count();
$flash   = af_take_flash();
$initial = strtoupper(mb_substr(trim((string) ($me['name'] ?? '')), 0, 1)) ?: 'A';

/* --------------------------------------------------------------- nav data */
/**
 * The sidebar. `$adminNav` maps a key to either NULL (a section divider) or
 * [label, href, icon]. Dividers are labelled by key: __community -> Community,
 * __system -> General, anything else -> Menu.
 *
 * $activeNav carries the current page's key and is compared below.
 */
function af_nav_count($table, $fallback = 0, $where = '')
{
    if (!($GLOBALS['AF_DB'] ?? null)) {
        return (int) $fallback;
    }
    $sql = 'SELECT COUNT(*) c FROM ' . $table . ($where !== '' ? ' WHERE ' . $where : '');
    try {
        $n = af_row($sql);
    } catch (PDOException $e) {
        // A missing/renamed table must never take the whole panel down with
        // a 500: log it and show the fallback number instead.
        @error_log('[AF nav count] ' . $e->getMessage() . "\n", 3, AF_ERROR_LOG);
        return (int) $fallback;
    }
    return isset($n['c']) ? (int) $n['c'] : (int) $fallback;
}

$adminNav = [
    '__menu'    => null,

    'dashboard' => ['Dashboard', 'admin/index.php',
        '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'],

    'songs'     => ['Music', 'admin/songs.php',
        '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        af_nav_count('songs')],

    'videos'    => ['Songs', 'admin/videos.php',
        '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a9 9 0 0 1 18 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
        af_nav_count('songs', 0, "section = 'video'")],

    'albums'    => ['Albums', 'admin/albums.php',
        '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="2"/>',
        af_nav_count('albums')],

    'artists'   => ['Artists', 'admin/artists.php',
        '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/>',
        af_nav_count('artists')],

    'categories'=> ['Categories', 'admin/categories.php',
        '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5"/>'],

    '__community' => null,

    'users'     => ['Users', 'admin/users.php',
        '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],

    'reviews'   => ['Reviews', 'admin/reviews.php',
        '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],

    'messages'  => ['Messages', 'admin/messages.php',
        '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'],

    'saved'     => ['Saved Songs', 'admin/saved.php',
        '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>'],

    '__system'  => null,

    'settings'  => ['Website Settings', 'admin/settings.php',
        '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>'],

    'admin-acct'=> ['Admin Account', 'admin/account.php',
        '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> | Audio Fire Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($base) ?>/admin/assets/admin.css?v=<?= e(AF_ASSET_V) ?>">
<style>
/* New chrome that admin.css has no rule for: the three top-bar zones, the
   admin chip and the global search dropdown. Everything else here reuses the
   utility classes and components already defined in admin.css. */

/* --- A. top bar: LEFT = menu + title / CENTER = search / RIGHT = controls -- */
.top{gap:10px}
.top-left{display:flex;align-items:center;gap:10px;min-width:0;flex:none}
.top-title{
  font-family:var(--font-hd);font-size:15.5px;font-weight:700;letter-spacing:-.02em;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.search-wrap{position:relative;flex:1 1 auto;min-width:150px;max-width:520px}
.top .search{width:100%;max-width:none;height:44px;padding:0 16px}
.top-right{margin-left:auto;display:flex;gap:10px;align-items:center;flex:none}
.top .iconbtn,.top .ubtn,.who-btn{height:44px}
.top .iconbtn{width:44px}
.top .ubtn{padding:0 16px}
/* one icon set: 18px, 1.75 stroke, everywhere the chrome draws an icon */
.side svg,.top svg,.btn svg{width:18px;height:18px;stroke-width:1.75 !important}

/* --- B. admin chip: initials avatar + name/role + Account / Log out ------- */
.who{position:relative}
.who-btn{
  display:flex;align-items:center;gap:9px;height:44px;padding:0 12px 0 5px;
  border:1px solid var(--bd);border-radius:99px;background:var(--btn-face);
  color:var(--ink);cursor:pointer;font-family:var(--font-ui);
  box-shadow:inset 0 1px 0 var(--btn-hi),0 3px 0 var(--btn-edge),0 6px 14px var(--btn-sh);
  transition:transform .16s,box-shadow .16s;
}
.who-btn:hover{transform:translateY(-1px)}
.who-btn:active{transform:translateY(3px);box-shadow:inset 0 1px 0 var(--btn-hi),0 1px 0 var(--btn-edge),0 3px 6px var(--btn-sh)}
.who-btn:focus-visible{outline:none;box-shadow:inset 0 1px 0 var(--btn-hi),0 3px 0 var(--btn-edge),0 0 0 4px var(--tint)}
.who .av{border-radius:50%;width:34px;height:34px;font-size:14px}
.who-txt{display:flex;flex-direction:column;align-items:flex-start;line-height:1.25;text-align:left}
.who-txt b{font-size:12.8px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.who-txt small{font-size:11px;white-space:nowrap}
.who-btn .chev{width:16px;height:16px;opacity:.65;flex:none}
.who-pop{width:236px;top:calc(100% + 10px);right:0;padding:6px;z-index:65}
.who-pop .notif-item{width:100%;border:0;background:transparent;font:inherit;color:inherit;text-align:left;cursor:pointer}
.who-pop form{margin:0}
.who-pop .nic{width:32px;height:32px}

/* --- C. global search dropdown (same shell as .notif-panel) -------------- */
.search-drop{left:0;right:auto;top:calc(100% + 10px);width:min(480px,94vw);z-index:65}
.search-drop .res-head{
  padding:9px 11px 4px;font-size:10.5px;letter-spacing:.14em;
  text-transform:uppercase;font-weight:700;color:var(--mut);
}
.search-drop .notif-item .nic{width:34px;height:34px}
.search-drop .notif-item .nic svg{width:18px;height:18px}
.search-drop .notif-item .chip{margin-left:auto;flex:none}
.search-drop .notif-item.on{background:var(--tint);color:var(--ink)}

/* --- responsive: the three zones only stay on one row when there is room -- */
@media (max-width:1023px){
  .search-wrap{order:9;flex-basis:100%;max-width:none;min-width:0}
  .top-title{max-width:44vw}
}
@media (min-width:1024px){
  .top{flex-wrap:nowrap}
}
</style>
<link rel="icon" type="image/png" href="<?= e($base) ?>/assets/img/favicon.png">
<meta name="af-csrf" content="<?= e($csrf) ?>">
<meta name="af-admin" content="<?= e($me['name'] ?? 'Administrator') ?>">
<meta name="af-notif" content="<?= (int) $notifN ?>">
<script>
/* theme before paint: light / dark, remembered across visits */
(function(){
  try{
    var t = localStorage.getItem('af-admin-theme');
    if(!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', t);
  }catch(e){}
})();
</script>
</head>
<body>
<!-- Slow green / gray / black smoke behind everything (.smoke in admin.css) -->
<div class="smoke" aria-hidden="true"><i class="s-green"></i><i class="s-gray"></i><i class="s-black"></i><i class="s-noise"></i></div>
<div class="scrim" id="scrim"></div>
<div class="app">

  <!-- ============================== SIDEBAR ============================== -->
  <aside class="side" id="side">
    <!-- The logo is the full Audio Fire lockup, wordmark included, so it is
         shown on its own - no tile, no panel and no duplicated name text. -->
    <a class="logo" href="<?= e($base) ?>/admin/index.php">
      <img src="<?= e($logoSrc) ?>" alt="Audio Fire" onerror="this.style.display='none'">
    </a>

    <div class="scroll">
      <nav class="nav">
        <?php foreach ($adminNav as $key => $item): if ($item === null): ?>
          <div class="lbl"><?= e($key === '__community' ? 'Community' : ($key === '__system' ? 'General' : 'Menu')) ?></div>
        <?php else:
          list($label, $href, $svg) = $item;
          $count = isset($item[3]) ? (int) $item[3] : null;
        ?>
          <a href="<?= e($base) ?>/<?= e($href) ?>"<?= $activeNav === $key ? ' class="on"' : '' ?>>
            <svg viewBox="0 0 24 24"><?= $svg ?></svg><?= e($label) ?>
            <?php if ($count !== null && $count > 0): ?><span class="pill"><?= $count ?></span><?php endif; ?>
          </a>
        <?php endif; endforeach ?>
      </nav>

      <div class="lbl">Account</div>
      <nav class="nav">
        <form method="post" action="<?= e($base) ?>/admin/logout.php" id="logoutForm">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="navlink w-100">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
            Log out
          </button>
        </form>
      </nav>
    </div>

    <div class="side-foot">
      <a class="btn sm w-100" href="<?= e($base) ?>/index.php" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
        View website
      </a>
    </div>
  </aside>

  <!-- ================================ MAIN ============================== -->
  <div class="main">

    <header class="top">
      <!-- ============================ LEFT ============================== -->
      <div class="top-left">
        <button class="iconbtn menubtn" id="menubtn" type="button" aria-label="Open menu">
          <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
        </button>
        <span class="top-title"><?= e($adminTitle) ?></span>
      </div>

      <!-- =========================== CENTER ============================= -->
      <div class="search-wrap" id="searchWrap">
        <div class="search">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" id="globalSearch" autocomplete="off" spellcheck="false"
                 placeholder="Search songs, artists, albums, users..."
                 aria-label="Search the whole panel"
                 role="combobox" aria-expanded="false" aria-controls="searchPanel" aria-autocomplete="list">
        </div>
        <div class="notif-panel search-drop" id="searchPanel" role="listbox" aria-label="Search results">
          <div class="notif-list" id="searchResults">
            <div class="notif-empty">Type at least 2 characters to search the whole panel.</div>
          </div>
        </div>
      </div>

      <!-- ============================ RIGHT ============================= -->
      <div class="top-right">
        <!-- USER PANEL SHORTCUT -->
        <a class="ubtn" href="<?= e($base) ?>/index.php" target="_blank" rel="noopener" title="Open the public website (user panel)">
          <svg viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>User Panel</span>
        </a>

        <!-- THEME -->
        <button class="iconbtn themebtn" id="themebtn" type="button" aria-label="Toggle dark / light mode" title="Toggle dark / light">
          <svg class="sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>

        <!-- NOTIFICATIONS -->
        <div class="notif-wrap">
          <button class="iconbtn" id="notifBtn" type="button" aria-label="Notifications" aria-haspopup="true">
            <svg viewBox="0 0 24 24"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
            <em id="notifDot" class="hide"></em>
          </button>
          <div class="notif-panel" id="notifPanel" role="menu">
            <div class="notif-head">
              <span>Notifications</span>
              <span class="spacer"></span>
              <button type="button" id="notifRead">Mark all read</button>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-empty">Loading...</div>
            </div>
            <div class="notif-foot">
              <a href="<?= e($base) ?>/admin/users.php">Open user management</a>
            </div>
          </div>
        </div>

        <!-- ADMIN CHIP: initials + name/role, no logo -->
        <div class="who">
          <button class="who-btn" id="whoBtn" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="whoPop">
            <span class="av"><?= e($initial) ?></span>
            <span class="who-txt">
              <b><?= e($me['name'] ?? 'Administrator') ?></b>
              <small>Administrator</small>
            </span>
            <svg class="chev" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="notif-panel who-pop" id="whoPop" role="menu">
            <a class="notif-item" role="menuitem" href="<?= e($base) ?>/admin/account.php">
              <span class="nic"><svg viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <span><b>Account</b><small>Profile and password</small></span>
            </a>
            <form method="post" action="<?= e($base) ?>/admin/logout.php">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <button class="notif-item" type="submit" role="menuitem">
                <span class="nic"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span><b>Log out</b><small>End this session</small></span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <!-- ============================== PAGE HEAD =========================== -->
    <div class="head">
      <div>
        <?php if ($adminCrumb !== ''): ?><div class="crumb"><?= e($adminCrumb) ?></div><?php endif; ?>
        <h1><?= e($adminTitle) ?></h1>
        <?php if ($adminSubtitle !== ''): ?><p><?= e($adminSubtitle) ?></p><?php endif; ?>
      </div>
      <?php if ($adminActions !== ''): ?><div class="acts"><?= $adminActions ?></div><?php endif; ?>
    </div>

    <div class="content">
      <?php if (!empty($flash)): ?>
      <div id="af-flash" data-ok="<?= ($flash['type'] ?? 'ok') === 'err' ? '0' : '1' ?>" hidden><?= e($flash['text'] ?? '') ?></div>
      <?php endif; ?>
