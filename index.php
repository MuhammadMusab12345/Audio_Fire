<?php
/* ==========================================================================
   HOME PAGE (index.php)
   hero -> console -> featured artists -> "Now Playing" -> latest songs -> reviews
   ========================================================================== */
$currentPage     = 'home';
$pageTitle       = 'Audio Fire | Featured Artists & Audio Experience';
$metaDescription = 'Cloud AI â€” Interactive audio-visual showcase featuring premier recording artists, cinematic WebGL smoke, and cursor-driven particle typography.';
$pageStyles      = array(
  'assets/css/hero.css',
  'assets/css/artists.css',
  'assets/css/console.css',
  'assets/css/songs.css',
  'assets/css/home-featured.css',
  'assets/css/home-reviews.css',
);
$pageScripts     = array(
  'assets/js/particle-typography.js',
  'assets/js/hero.js',
  'assets/js/artists.js?v=' . (int) @filemtime(__DIR__ . '/assets/js/artists.js'),
  'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
  'assets/js/songs.js?v=' . (int) @filemtime(__DIR__ . '/assets/js/songs.js'),
  'songs-data.php',
  'assets/js/home-featured.js',
  'assets/js/home-reviews.js',
);
require_once __DIR__ . '/includes/content.php';   // DB-backed content + fallbacks

/* ---- console card stack (home) + featured grid, straight from the DB ---- */
$consoleCards = af_artists('console');
if (!$consoleCards) {
    $consoleCards = af_console_fallback();
}
$homeArtists = af_artists('home');
if (!$homeArtists) {
    $homeArtists = af_home_artists_fallback();
}

require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>


  <!-- ======================================================================
       FIXED FULL-PAGE TEXTURE (below the hero) + SOFT PAGE LIGHTING
       Gradient -> grayscale grunge (soft-light) -> vignette -> film grain
       ====================================================================== -->
  <div id="bg-layer" aria-hidden="true"></div>

  <div class="page-lighting" aria-hidden="true">
    <div class="top-glow"></div>
    <div class="light-beam"></div>
  </div>

  <!-- ======================================================================
       HERO SECTION (Cloud AI - UNTOUCHED except the compact info block)
       ====================================================================== -->
  <section class="hero" id="hero" data-phase="idle">
    <div class="bg" id="bg"></div>
    <div class="vignette"></div>

    <!-- Cinematic smoke: WebGL canvas (assets/js/hero.js -> Smoke), hero only -->
    <canvas id="smoke" aria-hidden="true"></canvas>

    <div class="portrait" id="portrait">
      <img id="pImg" class="glitch-target" alt="">
      <img id="pR" class="rgb rgb-r" alt="" aria-hidden="true">
      <img id="pC" class="rgb rgb-c" alt="" aria-hidden="true">
      <div class="missing glitch-target"><div id="missingTxt"></div></div>
    </div>
    <div class="scanlines"></div>

    <div class="info">
      <h1 class="sr-only" id="h1"></h1>
      <!-- Artist NAME stays OUTSIDE the card (particle typography) -->
      <div class="glitch-target" id="names" aria-hidden="true"></div>

      <!-- All other artist details inside ONE iPhone-style frosted glass card -->
      <div class="hero-card glitch-target" id="heroMeta">
        <div class="hm-rating" id="hmRating" role="img">
          <span class="hm-stars" id="hmStars" aria-hidden="true"></span>
          <span class="hm-score" id="hmScore"></span>
        </div>
        <ul class="hm-chips" id="hmChips"></ul>
        <p class="hm-tagline" id="hmTagline"></p>
        <p class="hm-bio" id="hmBio"></p>

        <!-- 3 key numbers per singer (monthly listeners / top song / albums) -->
        <div class="hm-stats">
          <div class="hm-stat">
            <span class="hm-stat-label">Monthly listeners</span>
            <span class="hm-stat-value" id="hmListeners"></span>
          </div>
          <div class="hm-stat">
            <span class="hm-stat-label">Top song</span>
            <span class="hm-stat-value" id="hmTopSong"></span>
          </div>
          <div class="hm-stat">
            <span class="hm-stat-label">Albums</span>
            <span class="hm-stat-value" id="hmAlbums"></span>
          </div>
        </div>

        <ul class="tracks" id="tracks"></ul>

        <div class="hm-actions">
          <button class="all">All Tracks</button>
          <a class="hm-cta" href="#artists">Explore artists</a>
        </div>
      </div>
    </div>

  </section>

  <!-- ======================================================================
       ONE MERGED SHOWCASE SECTION (id="showcase")
       a) CONSOLE block on top  ->  small gap  ->  b) SINGERS block below
       ====================================================================== -->
  <main class="showcase" id="showcase">
    <!-- ==================================================================
         1. CONSOLE BLOCK (id="console" - interactive fanned card stack)
         ================================================================== -->
    <section class="console-section" id="console">
      <div class="console-container">
        <h2 class="sr-only">Choose Your Universe</h2>

        <!-- Fanned glass card stack -->
        <div class="ocs-root">
          <div class="ocs-stage" id="stage" data-open="false">
                          <?php foreach ($consoleCards as $card):
                              $cName  = $card['name'] ?? '';
                              $cRole  = $card['role'] ?? '';
                              $cDesc  = $card['description'] ?? '';
                              $cTag   = $card['place'] ?? ($card['tagline'] ?? '');
                              $cImg   = $card['image'] ?? '';
                              $cBadge = strtoupper(preg_replace('/[^A-Za-z]/', '', preg_replace('/\s+.*$/', '', $cName)) ?: mb_substr($cName, 0, 2));
                              if (strlen($cBadge) < 2) { $cBadge = strtoupper(mb_substr($cName, 0, 2)); }
                          ?>
                <article class="ocs-card" tabindex="0" aria-label="<?= e($cName) ?>, <?= e($cRole) ?>" data-save="artist" data-save-title="<?= e($cName) ?>" data-save-img="<?= e($cImg) ?>">
                  <span class="ocs-spotlight" aria-hidden="true"></span>
                  <div class="ocs-media">
                    <img src="<?= e($cImg) ?>" alt="<?= e($cName) ?>" draggable="false" loading="lazy">
                    <a class="ocs-arrow" href="#" aria-label="Open <?= e($cName) ?>">
                      <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                    <span class="ocs-badge"><?= e($cBadge) ?></span>
                  </div>
                  <div class="ocs-body">
                    <p class="ocs-role"><?= e($cRole) ?></p>
                    <h3 class="ocs-name"><?= e($cName) ?></h3>
                    <p class="ocs-desc"><?= e($cDesc) ?></p>
                    <p class="ocs-tag"><?= e($cTag) ?></p>
                  </div>
                </article>
                          <?php endforeach; ?>

                      </div>
        </div>
      </div>
    </section>

    <!-- Small gap between console and singers -->
    <div class="showcase-gap" aria-hidden="true"></div>

    <!-- ==================================================================
         2. SINGERS BLOCK (id="artists")
         ================================================================== -->
    <section class="artists-section" id="artists">
      <div class="artists">
        <h2 class="section-heading">Featured<br><span class="outline">Artists</span></h2>

        <div class="grid">
                      <?php foreach ($homeArtists as $ha):
                          $hName  = $ha['name'] ?? '';
                          $hRole  = $ha['role'] ?: $ha['name'];
                          $hImg   = $ha['image'] ?? '';
                          $hId    = $ha['save_id'] ?? ('home-artist-' . ($ha['id'] ?? 'x'));
                          $hH     = $ha['height'] ?? 150;
                          $hTitle = $ha['tagline'] ?? $hName;
                      ?>
            <a class="card" href="#" style="--h:<?= e($hH) ?>%" data-save="artist" data-save-id="<?= e($hId) ?>" data-save-title="<?= e($hTitle) ?>" data-save-img="<?= e($hImg) ?>">
              <span class="card-spotlight" aria-hidden="true"></span>
              <span class="card-glass" aria-hidden="true"></span>
              <span class="photo"><img src="<?= e($hImg) ?>" alt="<?= e($hName) ?>" width="513" height="514" loading="lazy"></span>
              <span class="info"><strong class="name"><?= e($hName) ?></strong><span class="role"><?= e(strtoupper($hRole)) ?></span></span>
            </a>
                      <?php endforeach; ?>

                  </div>
      </div>
    </section>

    <!-- ==================================================================
         2b. NOW PLAYING - featured vinyl block (assets/css/home-featured.css
         + assets/js/home-featured.js, scoped to #home-featured)
         Same song data as music.php (songs-data.php)
         ================================================================== -->
    <section id="home-featured" aria-labelledby="hf-heading">
      <div class="hf-head">
        <h2 class="section-heading" id="hf-heading">Now<br><span class="outline">Playing</span></h2>
        <p>Ek click par vinyl ghoomna shuru &mdash; language filter se apna favorite choose karein</p>
      </div>

      <div class="hf-body">
        <!-- half cropped rotating disk + progress ring + tonearm -->
        <div class="hf-stage" id="hf-stage" aria-hidden="true">
          <div class="hf-disk"><div class="hf-lab" id="hf-lab"><span id="hf-lab-text"></span></div></div>
          <svg class="hf-ring-svg" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="49" fill="none" stroke="rgba(255,255,255,.18)" stroke-width=".4"></circle>
            <circle id="hf-ring" cx="50" cy="50" r="49" fill="none" stroke="var(--accent)" stroke-width=".9" stroke-linecap="round" stroke-dasharray="307.9" stroke-dashoffset="307.9"></circle>
          </svg>
          <div class="hf-arm"></div>
        </div>

        <div class="hf-main">
          <h3 class="hf-title" id="hf-title"></h3>
          <p class="hf-meta" id="hf-meta"></p>

          <div class="hf-controls">
            <button class="hf-btn hf-pp" id="hf-play" type="button" aria-label="Play or pause">&#9654;</button>
            <button class="hf-btn" id="hf-next" type="button" aria-label="Next song">&#9197;</button>
            <!-- save the featured song (shared save system - assets/js/save.js) -->
            <button class="hf-btn hf-save-btn" id="hf-save" type="button" aria-label="Save this song" aria-pressed="false">&#9829;</button>
          </div>

          <div class="hf-chips" id="hf-chips"></div>

          <div class="hf-pop">
            <h4 class="hf-pop-title">Popular</h4>
            <div class="hf-pop-list" id="hf-pop"></div>
          </div>

          <a class="hf-view" href="music.php">View all songs</a>
        </div>
      </div>
    </section>

    <!-- ==================================================================
         3. LATEST SONGS (transparent WebGL carousel - texture shows through)
         Edit the song list in: assets/js/songs.js  ->  const SONGS = [...]
         ================================================================== -->
    <section id="latest-songs">
      <div class="section-head">
        <h2 class="section-heading">Latest<br><span class="outline">Songs</span></h2>
        <p>Drag, scroll ya arrow keys se songs dekhein &mdash; center wale par click karein</p>
      </div>

      <div id="songs-stage" tabindex="0" role="region" aria-label="Latest songs carousel">
        <div id="songs-mount"></div>
        <div class="edge left"></div>
        <div class="edge right"></div>
        <p id="song-title" class="t"></p>
        <p id="counter" class="t"></p>
        <div id="cursor">View</div>
        <button id="close" type="button" aria-label="Close">Close</button>
        <div id="info">
          <h3 id="info-title"></h3>
          <span class="af-new" id="info-new" hidden>NEW</span>
          <p class="meta" id="info-meta"></p>
          <div class="info-actions">
            <a id="info-link" href="#">&#9654; Play Song</a>
            <!-- save this song (shared save system - assets/js/save.js) -->
            <button id="info-save" type="button" aria-label="Save this song" aria-pressed="false">&#9829; Save</button>
          </div>
        </div>
        <div class="fallback" id="fallback">Is carousel ke liye WebGL chahiye, jo is browser me available nahi hai.</div>
      </div>
    </section>
  </main>

  <!-- ===== REVIEWS SECTION (from reviews-section_done.html) ===== -->
<section id="reviews">
  <h2 class="section-heading">What Fans<br><span class="outline">Say</span></h2>
  <div class="row" id="rv-row" aria-live="off"></div>
  <div class="dots" id="rv-dots" aria-label="Review pages"></div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
