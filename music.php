<?php
/* ==========================================================================
   MUSIC PAGE - vinyl player, language filter, Spotify style song grid
   (converted from the original self contained "VinylVibe" page)
   ========================================================================== */
$currentPage  = 'music';
$pageTitle    = 'Music | Audio Fire';
$pageStyles   = array('assets/css/music.css');
$pageScripts  = array('songs-data.php', 'assets/js/music.js');
require __DIR__ . '/includes/head.php';
?>

<div class="bg" id="bg"></div>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- ===================== HERO: list + rotating vinyl + now playing ====== -->
  <section id="hero">
    <div class="glass list"><div class="chips"></div><div id="list"></div></div>
    <div class="stage" id="stage"><div class="plat"></div><div class="disk"><div class="lab" id="lab"></div></div><div class="arm"></div></div>
    <div class="glass info"><img id="cov" alt=""><h1 id="ti"></h1><span id="ar"></span><button class="pill" id="big">Play</button></div>
  </section>

  <!-- ===================== FEATURED: half cropped disk + Popular ========= -->
  <section id="feat">
    <div class="big" id="bigd"><div class="disk"><div class="lab"><span id="bl"></span></div></div>
      <svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="49" fill="none" stroke="rgba(255,255,255,.18)" stroke-width=".4"/><circle id="ring" cx="50" cy="50" r="49" fill="none" stroke="var(--c1)" stroke-width=".8" stroke-linecap="round" style="filter:brightness(1.5)" stroke-dasharray="307.9" stroke-dashoffset="307.9"/></svg></div>
    <div class="fr"><h2 class="hd" id="fh"></h2><p class="tag" id="ft"></p><div class="chips"></div>
      <div class="pop"><h3>Popular</h3><div id="pop"></div></div><button class="view" onclick="location.hash='cards'">View all songs</button></div>
  </section>

  <!-- ===================== ALL SONGS: card grid ========================== -->
  <section id="cards"><h2 class="hd" style="font-size:clamp(36px,5vw,76px)">Pick your <span class="out">song</span></h2><div class="chips"></div><div class="grid" id="grid"></div></section>

  <!-- ===================== FIXED PLAYER BAR ============================== -->
  <div class="glass bar"><img id="bi" alt=""><div class="n"><p id="bt"></p><small id="ba"></small></div>
    <div class="ctl"><button id="pv" aria-label="Previous">&#9198;</button><button class="pp" id="pp" aria-label="Play or pause">&#9654;</button><button id="nx" aria-label="Next">&#9197;</button></div>
    <div class="seek"><span id="t0">0:00</span><div class="trk" id="trk"><b id="fill"></b></div><span id="t1">0:00</span></div></div>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
