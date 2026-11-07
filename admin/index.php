<?php
/* ==========================================================================
   ADMIN DASHBOARD (admin/index.php)
   --------------------------------------------------------------------------
   Everything below is read live from the database: stat cards with
   sparklines, an area chart, two gauges, a donut, content-health meters,
   the latest uploads (with edit / delete), the newest reviews, the newest
   accounts and the website switches.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

/* ========================================================================== */
/*  chart helpers                                                             */
/* ========================================================================== */

/** Catmull-Rom -> cubic bezier: a soft, premium looking line. */
function af_smooth_path(array $pts)
{
    $n = count($pts);
    if ($n === 0) return '';
    if ($n === 1) return sprintf('M%.1f,%.1f', $pts[0][0], $pts[0][1]);
    $d = sprintf('M%.1f,%.1f', $pts[0][0], $pts[0][1]);
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[$i - 1] ?? $pts[$i];
        $p1 = $pts[$i];
        $p2 = $pts[$i + 1];
        $p3 = $pts[$i + 2] ?? $p2;
        $c1x = $p1[0] + ($p2[0] - $p0[0]) / 6;
        $c1y = $p1[1] + ($p2[1] - $p0[1]) / 6;
        $c2x = $p2[0] - ($p3[0] - $p1[0]) / 6;
        $c2y = $p2[1] - ($p3[1] - $p1[1]) / 6;
        $d .= sprintf(' C%.1f,%.1f %.1f,%.1f %.1f,%.1f', $c1x, $c1y, $c2x, $c2y, $p2[0], $p2[1]);
    }
    return $d;
}

/** Smoothed area chart (grid + labels + one gradient fill per series). */
function af_area_chart(array $labels, array $series, $h = 250)
{
    $n = count($labels);
    if ($n < 2) return '<div class="empty tight">Not enough history yet.</div>';

    $w = 780; $pl = 34; $pr = 14; $pt = 16; $pb = 32;
    $iw = $w - $pl - $pr;
    $ih = $h - $pt - $pb;

    $max = 1;
    foreach ($series as $s) {
        foreach ($s['data'] as $v) $max = max($max, (float) $v);
    }
    $step = max(1, (int) ceil($max / 4));
    $top  = $step * 4;

    $X = function ($i) use ($pl, $iw, $n) { return $pl + ($i / ($n - 1)) * $iw; };
    $Y = function ($v) use ($pt, $ih, $top) { return $pt + $ih - (max(0, min($top, (float) $v)) / $top) * $ih; };

    static $seq = 0;
    $uid = 'afch' . (++$seq);

    $out = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Activity over time">';

    /* horizontal grid + y labels */
    for ($g = 0; $g <= 4; $g++) {
        $gy = $Y($g * $step);
        $dash = $g === 0 ? '0' : '3 6';
        $out .= '<line x1="' . $pl . '" y1="' . round($gy, 1) . '" x2="' . ($w - $pr) . '" y2="' . round($gy, 1)
              . '" stroke="var(--line)" stroke-width="1" stroke-dasharray="' . $dash . '"/>';
        $out .= '<text x="' . ($pl - 8) . '" y="' . round($gy + 3.5, 1) . '" text-anchor="end" font-size="10" '
              . 'font-weight="700" fill="var(--mut)">' . ($g * $step) . '</text>';
    }

    /* x labels - every point when there are few, otherwise every other one */
    $every = $n > 8 ? 2 : 1;
    foreach ($labels as $i => $lab) {
        if ($i % $every !== 0 && $i !== $n - 1) continue;
        $out .= '<text x="' . round($X($i), 1) . '" y="' . ($h - 9) . '" text-anchor="middle" font-size="10" '
              . 'font-weight="600" fill="var(--mut)">' . e($lab) . '</text>';
    }

    foreach ($series as $k => $s) {
        $pts = [];
        foreach ($s['data'] as $i => $v) $pts[] = [$X($i), $Y($v)];
        $line = af_smooth_path($pts);
        $area = $line . sprintf(' L%.1f,%.1f L%.1f,%.1f Z', $pts[$n - 1][0], $pt + $ih, $pts[0][0], $pt + $ih);
        $gid  = $uid . 's' . $k;

        $out .= '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="0" y2="1">'
              . '<stop offset="0%" stop-color="' . $s['color'] . '" stop-opacity=".34"/>'
              . '<stop offset="100%" stop-color="' . $s['color'] . '" stop-opacity="0"/>'
              . '</linearGradient></defs>';
        $out .= '<path d="' . $area . '" fill="url(#' . $gid . ')"/>';
        $out .= '<path d="' . $line . '" fill="none" stroke="' . $s['color'] . '" stroke-width="2.6" '
              . 'stroke-linecap="round" stroke-linejoin="round"/>';
        foreach ($pts as $p) {
            $out .= '<circle cx="' . round($p[0], 1) . '" cy="' . round($p[1], 1) . '" r="3.6" fill="var(--glass2)" '
                  . 'stroke="' . $s['color'] . '" stroke-width="2.4"/>';
        }
    }

    return $out . '</svg>';
}

/** Small sparkline for the stat cards. */
function af_spark(array $vals, $color = 'var(--g1)')
{
    if (!count($vals)) return '';
    $w = 86; $h = 30; $n = count($vals);
    $mx = max($vals); $mn = min($vals);
    if ($mx <= $mn) { $mx = $mn + 1; }
    $pts = [];
    for ($i = 0; $i < $n; $i++) {
        $x = $n === 1 ? $w / 2 : ($i / ($n - 1)) * $w;
        $y = ($h - 5) - ((($vals[$i] - $mn) / ($mx - $mn)) * ($h - 8)) + 2;
        $pts[] = [$x, $y];
    }
    $line = af_smooth_path($pts);
    $fill = $line . sprintf(' L%.1f,%.1f L%.1f,%.1f Z', $w, $h, 0, $h);
    return '<span class="spk"><svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" aria-hidden="true">'
         . '<path d="' . $fill . '" fill="' . $color . '" opacity=".16"/>'
         . '<path d="' . $line . '" fill="none" stroke="' . $color . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>'
         . '</svg></span>';
}

/** Half dial meter. */
function af_gauge($ratio, $big, $small, $id)
{
    $ratio = max(0, min(1, (float) $ratio));
    $r = 78; $cx = 100; $cy = 96;
    $len = M_PI * $r;
    $d   = sprintf('M%.1f,%.1f A%d,%d 0 0 1 %.1f,%.1f', $cx - $r, $cy, $r, $r, $cx + $r, $cy);
    return '<div class="gauge">'
         . '<svg viewBox="0 0 200 112" aria-hidden="true">'
         . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="0">'
         . '<stop offset="0%" stop-color="var(--sage)"/><stop offset="100%" stop-color="var(--g1)"/>'
         . '</linearGradient></defs>'
         . '<path d="' . $d . '" fill="none" stroke="var(--track)" stroke-width="15" stroke-linecap="round"/>'
         . '<path d="' . $d . '" fill="none" stroke="url(#' . $id . ')" stroke-width="15" stroke-linecap="round" '
         . 'stroke-dasharray="' . round($ratio * $len, 1) . ' ' . round($len, 1) . '"/>'
         . '</svg>'
         . '<div class="v">' . $big . '<small>' . $small . '</small></div>'
         . '</div>';
}

/** Donut ring with a centred total. */
function af_donut(array $segs)
{
    $total = 0;
    foreach ($segs as $s) $total += $s['v'];
    if ($total < 1) $total = 1;
    $r = 70; $c = 2 * M_PI * $r;
    $off = 0; $svg = '';
    foreach ($segs as $s) {
        $len = ($s['v'] / $total) * $c;
        if ($len <= 0) continue;
        $svg .= sprintf(
            '<circle cx="90" cy="90" r="%d" fill="none" stroke="%s" stroke-width="23" stroke-dasharray="%.1f %.1f" stroke-dashoffset="%.1f"/>',
            $r, $s['c'], max(0, $len - 2.5), $c, -$off
        );
        $off += $len;
    }
    return '<div class="donut"><svg viewBox="0 0 180 180" aria-hidden="true">' . $svg . '</svg>'
         . '<b>' . $total . '<small>items</small></b></div>';
}

/** "3 days ago" / "just now". */
function af_age($ts)
{
    $ts = (int) $ts;
    $d  = time() - $ts;
    if ($ts <= 0) return '';
    if ($d < 60)    return 'just now';
    if ($d < 3600)  return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' hr ago';
    if ($d < 604800) return floor($d / 86400) . ' d ago';
    return date('d M Y', $ts);
}

/** Count rows per day / month key (cached result rows). */
function af_counts($sql, $since)
{
    if (empty($GLOBALS['AF_DB'])) return [];
    $out = [];
    foreach ((array) af_all($sql, [$since]) as $r) {
        $out[$r['d']] = (int) $r['c'];
    }
    return $out;
}

/** Bytes under uploads/ - cached on disk for ten minutes. */
function af_storage_bytes()
{
    $dir = __DIR__ . '/../data';
    $file = $dir . '/storage.cache';
    if (is_file($file)) {
        $p = explode('|', (string) @file_get_contents($file));
        if (count($p) === 2 && time() - (int) $p[1] < 600 && (float) $p[0] > 0) {
            return (float) $p[0];
        }
    }
    $bytes = 0;
    $up = __DIR__ . '/../uploads';
    if (is_dir($up)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) $bytes += $f->getSize();
        }
    }
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    @file_put_contents($file, $bytes . '|' . time());
    return (float) $bytes;
}

/* ========================================================================== */
/*  counters                                                                  */
/* ========================================================================== */
$zero = ['c' => 0];
$C = [
    'music'    => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM songs WHERE section='music'")['c'] : 0,
    'video'    => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM songs WHERE section='video'")['c'] : 0,
    'spot'     => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM songs WHERE section='spot'")['c'] : 0,
    'artists'  => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM artists')['c'] : 0,
    'albums'   => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM albums')['c'] : 0,
    'cats'     => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM categories')['c'] : 0,
    'users'    => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM users')['c'] : 0,
    'active'   => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM users WHERE status='active'")['c'] : 0,
    'reviews'  => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM reviews')['c'] : 0,
    'approved' => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM reviews WHERE approved = 1')['c'] : 0,
    'saved'    => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM saved_songs')['c'] : 0,
    'msgs'     => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM contact_messages')['c'] : 0,
    'unread'   => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')['c'] : 0,
    'thumbs'   => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM songs WHERE thumb <> ''")['c'] : 0,
    'media'    => $AF_DB ? (int) af_row("SELECT COUNT(*) c FROM songs WHERE media <> ''")['c'] : 0,
    'newbadge' => $AF_DB ? (int) af_row('SELECT COUNT(*) c FROM songs WHERE is_new = 1')['c'] : 0,
];
$library   = $C['music'] + $C['video'] + $C['spot'];
$avgRating = $AF_DB ? (float) (af_row('SELECT AVG(rating) a FROM reviews')['a'] ?? 0) : 0;

/* ========================================================================== */
/*  activity series                                                           */
/* ========================================================================== */
$weekLabels = []; $wkUsers = []; $wkReviews = []; $wkMsgs = []; $wkSongs = [];
$since7 = date('Y-m-d 00:00:00', strtotime('-6 days'));
$uMap = af_counts('SELECT DATE(created_at) d, COUNT(*) c FROM users WHERE created_at >= ? GROUP BY DATE(created_at)', $since7);
$rMap = af_counts('SELECT DATE(created_at) d, COUNT(*) c FROM reviews WHERE created_at >= ? GROUP BY DATE(created_at)', $since7);
$mMap = af_counts('SELECT DATE(created_at) d, COUNT(*) c FROM contact_messages WHERE created_at >= ? GROUP BY DATE(created_at)', $since7);
$sMap = af_counts("SELECT DATE(created_at) d, COUNT(*) c FROM songs WHERE created_at >= ? GROUP BY DATE(created_at)", $since7);
for ($i = 6; $i >= 0; $i--) {
    $k = date('Y-m-d', strtotime("-{$i} days"));
    $weekLabels[] = date('D', strtotime($k));
    $wkUsers[]   = $uMap[$k] ?? 0;
    $wkReviews[] = $rMap[$k] ?? 0;
    $wkMsgs[]    = $mMap[$k] ?? 0;
    $wkSongs[]   = $sMap[$k] ?? 0;
}

$yearLabels = []; $yrUsers = []; $yrReviews = [];
$since12 = date('Y-m-01 00:00:00', strtotime('-11 months'));
$yuMap = af_counts("SELECT DATE_FORMAT(created_at, '%Y-%m') d, COUNT(*) c FROM users WHERE created_at >= ? GROUP BY d", $since12);
$yrMap = af_counts("SELECT DATE_FORMAT(created_at, '%Y-%m') d, COUNT(*) c FROM reviews WHERE created_at >= ? GROUP BY d", $since12);
for ($i = 11; $i >= 0; $i--) {
    $k = date('Y-m', strtotime(date('Y-m-01') . " -{$i} months"));
    $yearLabels[] = date('M', strtotime($k . '-01'));
    $yrUsers[]   = $yuMap[$k] ?? 0;
    $yrReviews[] = $yrMap[$k] ?? 0;
}

$weekUp = 0;
for ($i = 1; $i < 7; $i++) { $weekUp += $wkUsers[$i]; }

/* ========================================================================== */
/*  donut / health meters                                                     */
/* ========================================================================== */
$langSegs = []; $langOther = 0;
if ($AF_DB) {
    $rows = (array) af_all(
        "SELECT COALESCE(NULLIF(TRIM(language), ''), 'Unlisted') g, COUNT(*) c
           FROM songs GROUP BY g ORDER BY c DESC, g ASC LIMIT 5"
    );
    $palette = ['#1f7a4d', '#7fc29b', '#c9a66b', '#3f7fa8', '#d97b5a', '#8a8fa8'];
    $totalLang = 0;
    foreach ($rows as $i => $r) { $totalLang += (int) $r['c']; }
    $keep = 0;
    foreach ($rows as $i => $r) {
        $c = (int) $r['c'];
        if ($i < 4) {
            $langSegs[] = ['k' => $r['g'], 'v' => $c, 'c' => $palette[$i % count($palette)]];
            $keep += $c;
        } else {
            $langOther += $c;
        }
    }
    if ($langOther > 0) $langSegs[] = ['k' => 'Other', 'v' => $langOther, 'c' => '#9aa7a0'];
}
if (!count($langSegs)) {
    $langSegs = [['k' => 'No data', 'v' => 1, 'c' => 'var(--track)']];
}

$pct = function ($a, $b) { return $b > 0 ? (int) round($a / $b * 100) : 0; };
$health = [
    ['Has cover art',   $pct($C['thumbs'], $library)],
    ['Has media file',  $pct($C['media'], $library)],
    ['Marked as "New"', $pct($C['newbadge'], $library)],
    ['Reviews approved',$pct($C['approved'], $C['reviews'])],
    ['Active accounts', $pct($C['active'], $C['users'])],
];

/* rating spread (5 -> 1) */
$spread = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$spreadMax = 0;
if ($AF_DB) {
    foreach ((array) af_all('SELECT rating, COUNT(*) c FROM reviews GROUP BY rating') as $r) {
        $k = (int) $r['rating'];
        if (isset($spread[$k])) { $spread[$k] = (int) $r['c']; $spreadMax = max($spreadMax, (int) $r['c']); }
    }
}
if ($spreadMax < 1) $spreadMax = 1;

/* ========================================================================== */
/*  latest rows                                                               */
/* ========================================================================== */
$latest = $AF_DB ? (array) af_all(
    "SELECT id, section, title, artist, language, thumb, is_new,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
       FROM songs ORDER BY id DESC LIMIT 8"
) : [];

$ratings = [];
if ($AF_DB) {
    foreach ((array) af_all('SELECT song_id, AVG(rating) a, COUNT(*) c FROM reviews WHERE song_id <> "" GROUP BY song_id') as $r) {
        $key = (int) $r['song_id'];
        if ($key > 0) $ratings[$key] = ['a' => round((float) $r['a'], 1), 'c' => (int) $r['c']];
    }
}

$latestReviews = $AF_DB ? (array) af_all(
    'SELECT id, name, role, rating, comment, approved,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
       FROM reviews ORDER BY id DESC LIMIT 4'
) : [];

$newUsers = $AF_DB ? (array) af_all(
    'SELECT id, name, username, email, role, status,
            TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
       FROM users ORDER BY id DESC LIMIT 6'
) : [];

$storage     = af_storage_bytes();
$storageCap  = 1024 * 1024 * 1024;                 // 1 GB
$storagePct  = (int) min(100, round($storage / $storageCap * 100));
$storageTxt  = $storage >= 1048576
    ? number_format($storage / 1048576, 1) . ' MB'
    : number_format($storage / 1024, 0) . ' KB';

$approvalPct = $C['reviews'] ? $C['approved'] / $C['reviews'] : 0;
$activePct   = $C['users']   ? $C['active'] / $C['users']     : 0;

/* ---- settings used by the switches -------------------------------------- */
$swPublic  = af_setting('public_site', '1') !== '0';
$swSignup  = af_setting('allow_signup', '1') !== '0';
$swMaint   = af_setting('maintenance', '0') === '1';
$swNotify  = af_setting('notify_new_user', '1') !== '0';

/* ---- page chrome --------------------------------------------------------- */
$adminTitle    = 'Dashboard';
$adminCrumb    = 'Overview';
$activeNav     = 'dashboard';
$adminSubtitle = 'Every number, chart and row below is live from your database.';
$adminActions  =
    '<a class="btn" href="videos.php?new=1"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add video</a>' .
    '<a class="btn pri" href="songs.php?new=1"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add song</a>';

require __DIR__ . '/includes/header.php';
?>

<!-- ==================================================== HEADLINE NUMBERS === -->
<div class="g12">

  <div class="card stat hero c3">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
    <div class="n"><?= number_format($library) ?></div>
    <div class="t">Total songs</div>
    <div class="d"><?= $C['music'] ?> music &middot; <?= $C['video'] ?> videos &middot; <?= $C['spot'] ?> spotlight</div>
    <div class="ft"><span class="chip"><?= count($wkSongs) ? array_sum($wkSongs) : 0 ?> added / 7d</span><?= af_spark($wkSongs, '#ffffff') ?></div>
  </div>

  <div class="card stat c3 c-blue">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="14" height="14" rx="3"/><path d="m22 8-6 4 6 4z"/></svg></span>
    <div class="n"><?= number_format($C['video']) ?></div>
    <div class="t">Videos</div>
    <div class="d">Watched on the public site by every visitor</div>
    <div class="ft"><span class="chip sand"><?= $pct($C['video'], $library) ?>% of library</span></div>
  </div>

  <div class="card stat c3 c-gold">
    <span class="ic"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6"/></svg></span>
    <div class="n"><?= number_format($C['users']) ?></div>
    <div class="t">Registered users</div>
    <div class="d"><?= $C['active'] ?> active<?= $C['users'] - $C['active'] > 0 ? ' &middot; ' . ($C['users'] - $C['active']) . ' suspended' : '' ?></div>
    <div class="ft"><span class="chip">+<?= $weekUp ?> this week</span><?= af_spark($wkUsers, '#c9a66b') ?></div>
  </div>

  <div class="card stat c3 c-violet">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v4"/></svg></span>
    <div class="n"><?= number_format($C['artists']) ?></div>
    <div class="t">Artists</div>
    <div class="d"><?= $C['albums'] ?> albums &middot; <?= $C['cats'] ?> categories</div>
    <div class="ft"><span class="chip sand"><?= $C['saved'] ?> community saves</span></div>
  </div>

</div>

<!-- ====================================================== CHART + METERS === -->
<div class="g12">

  <div class="card c8">
    <h3>
      Site activity
      <span class="seg">
        <button type="button" class="on" data-range="week">7 days</button>
        <button type="button" data-range="year">12 months</button>
      </span>
    </h3>
    <div class="lg">
      <span><i class="sw-green"></i>Reviews</span>
      <span><i class="sw-sand"></i>New users</span>
      <span><i class="sw-blue"></i>Messages</span>
    </div>
    <div class="chart" id="chartWeek"><?= af_area_chart($weekLabels, [
        ['color' => '#2f9e6a', 'data' => $wkReviews],
        ['color' => '#c9a66b', 'data' => $wkUsers],
        ['color' => '#3f7fa8', 'data' => $wkMsgs],
    ]) ?></div>
    <div class="chart" id="chartYear" hidden><?= af_area_chart($yearLabels, [
        ['color' => '#2f9e6a', 'data' => $yrReviews],
        ['color' => '#c9a66b', 'data' => $yrUsers],
    ], 250) ?></div>
  </div>

  <div class="card c4">
    <h3>Quality meters</h3>
    <div class="gg">
      <div><?= af_gauge($avgRating / 5, number_format($avgRating, 1), 'avg of 5', 'gG1') ?></div>
      <div><?= af_gauge($approvalPct, (int) round($approvalPct * 100) . '%', $C['approved'] . '/' . $C['reviews'] . ' approved', 'gG2') ?></div>
    </div>

    <div class="mt16">
      <div class="tg tg-first">
        <span>Storage used</span>
        <span class="chip <?= $storagePct > 85 ? 'red' : 'sand' ?>"><?= $storageTxt ?> / 1 GB</span>
      </div>
      <div class="bar"><i style="width:<?= $storagePct ?>%"></i></div>

      <div class="tg">
        <span>Library on air</span>
        <span class="chip"><?= $C['music'] + $C['video'] + $C['spot'] ?> items</span>
      </div>
      <div class="bar"><i style="width:<?= $pct($library, 500) ?>%"></i></div>

      <div class="tg">
        <span>Active accounts</span>
        <span class="chip"><?= (int) round($activePct * 100) ?>%</span>
      </div>
      <div class="bar"><i style="width:<?= (int) round($activePct * 100) ?>%"></i></div>
    </div>
  </div>

</div>

<!-- ======================================================= DONUT + METERS === -->
<div class="g12">

  <div class="card c5">
    <h3>Library by language</h3>
    <div class="donutwrap">
      <?= af_donut($langSegs) ?>
      <div class="leg">
        <?php foreach ($langSegs as $s): ?>
          <span><i style="background:<?= e($s['c']) ?>"></i><?= e($s['k']) ?> (<?= $s['v'] ?>)</span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card c4">
    <h3>Content health</h3>
    <?php foreach ($health as $h): ?>
      <div class="dist">
        <span><?= e($h[0]) ?></span>
        <span class="tr"><i style="width:<?= $h[1] ?>%"></i></span>
        <span><?= $h[1] ?>%</span>
      </div>
    <?php endforeach; ?>
    <div class="hintbox">
      Everything listed here drives the public site. Missing cover art falls back to the coloured placeholder
      the design already uses.
    </div>
  </div>

  <div class="card c3">
    <h3>Ratings</h3>
    <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
      <div class="dist">
        <span class="star"><?= $star ?>&#9733;</span>
        <span class="tr"><i style="width:<?= (int) round($spread[$star] / $spreadMax * 100) ?>%"></i></span>
        <span><?= $spread[$star] ?></span>
      </div>
    <?php endforeach; ?>

    <div class="lbl-in mt18">Quick totals</div>
    <ul class="list-plain">
      <li><span class="dot d-green"></span>Albums<small><?= $C['albums'] ?></small></li>
      <li><span class="dot d-sage"></span>Saved songs<small><?= $C['saved'] ?></small></li>
      <li><span class="dot d-sand"></span>Messages<small><?= $C['msgs'] ?><?= $C['unread'] ? ' (' . $C['unread'] . ' new)' : '' ?></small></li>
      <li><span class="dot d-blue"></span>Spotlight<small><?= $C['spot'] ?></small></li>
    </ul>
  </div>

</div>

<!-- ============================================= LATEST UPLOADS + REVIEWS === -->
<div class="g12">

  <div class="card c7">
    <h3>
      Latest uploads
      <span class="h3-tools">
        <a class="btn sm" href="videos.php">All videos</a>
        <a class="btn sm" href="songs.php">All music</a>
      </span>
    </h3>

    <?php if (!$latest): ?>
      <div class="empty">
        <span class="ic"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
        <b>No songs yet</b>
        <p>Add your first track and it appears on the website right away.</p>
        <div class="mt14"><a class="btn pri" href="songs.php?new=1">Add song</a></div>
      </div>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th>Track</th>
              <th>Type</th>
              <th>Rating</th>
              <th>Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latest as $s):
              $isVid = $s['section'] === 'video';
              $page  = $isVid ? 'videos.php' : 'songs.php';
              $rt    = $ratings[(int) $s['id']] ?? null;
            ?>
              <tr data-searchable>
                <td>
                  <div class="cell-media">
                    <?php if (!empty($s['thumb'])): ?>
                      <img class="thumb" src="<?= e(af_src($s['thumb'])) ?>" alt="" loading="lazy"
                           onerror="this.outerHTML='&lt;span class=&quot;ph&quot;&gt;<?= e(strtoupper(mb_substr($s['title'], 0, 1))) ?>&lt;/span&gt;'">
                    <?php else: ?>
                      <span class="ph"><?= e(strtoupper(mb_substr($s['title'], 0, 1))) ?></span>
                    <?php endif; ?>
                    <span>
                      <b><?= e($s['title']) ?><?= $s['is_new'] ? '<span class="new">NEW</span>' : '' ?></b>
                      <small><?= e($s['artist'] ?: 'Unknown artist') ?><?= $s['language'] ? ' &middot; ' . e($s['language']) : '' ?></small>
                    </span>
                  </div>
                </td>
                <td><span class="tag <?= $isVid ? 'amber' : 'green' ?>"><?= $isVid ? 'Video' : 'Music' ?></span></td>
                <td>
                  <?php if ($rt): ?>
                    <span class="star">&#9733;</span> <?= e($rt['a']) ?>
                    <small class="muted">(<?= $rt['c'] ?>)</small>
                  <?php else: ?>
                    <span class="muted">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td class="muted"><?= e(af_age(time() - (int) $s['age'])) ?></td>
                <td>
                  <div class="row-actions">
                    <a class="btn xs" href="<?= e($page) ?>?edit=<?= (int) $s['id'] ?>">Edit</a>
                    <button class="btn xs red" type="button" data-del="song" data-id="<?= (int) $s['id'] ?>"
                            data-label="&ldquo;<?= e($s['title']) ?>&rdquo;">Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card c5">
    <h3>
      Latest reviews
      <a class="btn sm" href="reviews.php">Moderate</a>
    </h3>

    <?php if (!$latestReviews): ?>
      <div class="empty">
        <span class="ic"><svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .9-5.2 4.9 1.4 7-6.2-3.5-6.2 3.5 1.4-7L2 9.4l7-.9z"/></svg></span>
        <b>No reviews yet</b>
        <p>Ratings left by listeners will appear here.</p>
      </div>
    <?php else: foreach ($latestReviews as $rv): ?>
      <div class="rev">
        <span class="av g-green">
          <?= e(strtoupper(mb_substr($rv['name'], 0, 1))) ?>
        </span>
        <div class="rev-body">
          <b class="rev-name"><?= e($rv['name']) ?>
            <?php if (empty($rv['approved'])): ?><span class="tag amber">Pending</span><?php endif; ?>
          </b>
          <div>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="<?= $i <= (int) $rv['rating'] ? 'star' : 'muted' ?>">&#9733;</span>
            <?php endfor; ?>
            <small class="muted"><?= e(af_age(time() - (int) $rv['age'])) ?></small>
          </div>
          <p><?= e(mb_substr((string) $rv['comment'], 0, 150)) ?><?= mb_strlen((string) $rv['comment']) > 150 ? '&hellip;' : '' ?></p>
          <div class="acts">
            <button class="btn xs" type="button" data-approve="<?= (int) $rv['id'] ?>"
                    data-v="<?= ((int) $rv['approved']) ? 0 : 1 ?>">
              <?= ((int) $rv['approved']) ? 'Hide' : 'Approve' ?>
            </button>
            <button class="btn xs red" type="button" data-del="review" data-id="<?= (int) $rv['id'] ?>"
                    data-label="this review">Delete</button>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

</div>

<!-- ======================================================= SWITCHES + USERS -->
<div class="g12">

  <div class="card c5">
    <h3>Website controls</h3>

    <div class="tg">
      <span>Public site online<small>Visitors can browse, listen and watch</small></span>
      <button class="sw<?= $swPublic ? ' on' : '' ?>" type="button" role="switch"
              aria-checked="<?= $swPublic ? 'true' : 'false' ?>" data-set="public_site" aria-label="Public site online"></button>
    </div>

    <div class="tg">
      <span>Allow sign ups<small>New visitors can create an account</small></span>
      <button class="sw<?= $swSignup ? ' on' : '' ?>" type="button" role="switch"
              aria-checked="<?= $swSignup ? 'true' : 'false' ?>" data-set="allow_signup" aria-label="Allow sign ups"></button>
    </div>

    <div class="tg">
      <span>Maintenance mode<small>Show a holding page instead of the site</small></span>
      <button class="sw<?= $swMaint ? ' on' : '' ?>" type="button" role="switch"
              aria-checked="<?= $swMaint ? 'true' : 'false' ?>" data-set="maintenance" aria-label="Maintenance mode"></button>
    </div>

    <div class="tg">
      <span>New user notifications<small>Ring the bell when somebody registers</small></span>
      <button class="sw<?= $swNotify ? ' on' : '' ?>" type="button" role="switch"
              aria-checked="<?= $swNotify ? 'true' : 'false' ?>" data-set="notify_new_user" aria-label="New user notifications"></button>
    </div>

    <div class="note mt16">
      <svg class="i" viewBox="0 0 24 24">
        <path d="M12 3 3 7v6c0 5 3.8 8.4 9 8 5.2.4 9-3 9-8V7z"/><path d="m9 12 2 2 4-4"/>
      </svg>
      <span><b>Guests stay read-only</b>Browsing, playing and watching are open to everyone - saving songs
        or artists asks the visitor to sign in first.</span>
    </div>
  </div>

  <div class="card c7">
    <h3>
      New users
      <span class="h3-tools">
        <?php if ($C['users']): ?><span class="new live">LIVE</span><?php endif; ?>
        <a class="btn sm" href="users.php">Manage</a>
      </span>
    </h3>

    <?php if (!$newUsers): ?>
      <div class="empty">
        <span class="ic"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 4a4 4 0 0 1 0 8"/></svg></span>
        <b>No accounts yet</b>
        <p>Every new registration lands here instantly, and the bell at the top rings.</p>
      </div>
    <?php else: foreach ($newUsers as $u): ?>
      <div class="mini">
        <span class="ph g-sand">
          <?= e(strtoupper(mb_substr($u['name'], 0, 1))) ?>
        </span>
        <span class="mini-body">
          <b><?= e($u['name']) ?>
            <?php if ($u['status'] !== 'active'): ?><span class="tag amber">Suspended</span><?php endif; ?>
          </b>
          <small><?= e($u['email']) ?> &middot; @<?= e($u['username']) ?></small>
        </span>
        <span class="r">
          <?= e(af_age(time() - (int) $u['age'])) ?>
          <a class="btn xs" href="users.php?edit=<?= (int) $u['id'] ?>">Edit</a>
          <button class="btn xs red" type="button" data-del="user" data-id="<?= (int) $u['id'] ?>"
                  data-label="the account of <?= e($u['name']) ?>">Delete</button>
        </span>
      </div>
    <?php endforeach; endif; ?>
  </div>

</div>

<!-- ===================================================== DELETE CONFIRM ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h">
      <h3 id="delTitle">Delete this item?</h3>
    </div>
    <p class="muted">
      It is removed from the panel <em>and</em> from the public website immediately.
      This cannot be undone.
    </p>
    <div class="form-actions">
      <button class="btn ghost" type="button" data-close>Cancel</button>
      <button class="btn red" type="button" id="delGo">Delete</button>
    </div>
  </div>
</div>

<?php
$adminScript = <<<JS
/* ------------------------------------------------------ chart range ----- */
document.querySelectorAll('.seg [data-range]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    btn.parentNode.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
    btn.classList.add('on');
    var week = btn.dataset.range === 'week';
    document.getElementById('chartWeek').hidden = !week;
    document.getElementById('chartYear').hidden = week;
  });
});

/* ---------------------------------------------------- website switches -- */
document.querySelectorAll('[data-set]').forEach(function (sw) {
  sw.addEventListener('click', function () {
    var on = !sw.classList.contains('on');
    sw.classList.toggle('on', on);
    sw.setAttribute('aria-checked', on ? 'true' : 'false');
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'settings.save');
    d.append(sw.dataset.set, on ? '1' : '0');
    AFAdmin.post(d).then(function (j) {
      AFAdmin.toast(j.ok ? (j.message || 'Setting updated.') : (j.error || 'Failed'), !!j.ok);
      if (!j.ok) { sw.classList.toggle('on', !on); sw.setAttribute('aria-checked', !on ? 'true' : 'false'); }
    });
  });
});

/* ------------------------------------------------------ review approve -- */
document.addEventListener('click', function (e) {
  var b = e.target.closest ? e.target.closest('[data-approve]') : null;
  if (!b) return;
  var d = new FormData();
  d.append('csrf', AFAdmin.csrf);
  d.append('action', 'review.approve');
  d.append('id', b.dataset.approve);
  d.append('approved', b.dataset.v);
  AFAdmin.post(d).then(function (j) { if (j.ok) location.reload(); else AFAdmin.toast(j.error || 'Failed', false); });
});

/* -------------------------------------------------------- delete flow --- */
var delTarget = null;
document.addEventListener('click', function (e) {
  var b = e.target.closest ? e.target.closest('[data-del]') : null;
  if (!b) return;
  delTarget = { action: b.dataset.del + '.delete', id: b.dataset.id };
  document.getElementById('delTitle').textContent = 'Delete ' + (b.dataset.label || 'this item') + '?';
  AFAdmin.modal('delModal', true);
});
document.getElementById('delGo').addEventListener('click', function () {
  if (!delTarget) return;
  var go = document.getElementById('delGo');
  var d = new FormData();
  d.append('csrf', AFAdmin.csrf);
  d.append('action', delTarget.action);
  d.append('id', delTarget.id);
  AFAdmin.post(d, go).then(function (j) {
    if (j.ok) { AFAdmin.toast(j.message || 'Deleted.', true); setTimeout(function () { location.reload(); }, 550); }
    else AFAdmin.toast(j.error || 'Delete failed', false);
  });
});
JS;

require __DIR__ . '/includes/footer.php';
