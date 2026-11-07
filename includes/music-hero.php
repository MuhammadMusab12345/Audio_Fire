<!-- ===== MUSIC HERO (assets/css/music-hero.css + assets/js/music-hero.js) =====
     Expects $heroSongs (built at the top of songs.php). -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <filter id="mh-fr"><feColorMatrix type="matrix" values="1 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1 0"/></filter>
  <filter id="mh-fc"><feColorMatrix type="matrix" values="0 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 1 0"/></filter>
</svg>

<section class="mh" id="mhHero" aria-label="Featured songs">
  <div class="mh-stage" id="mhStage"><div class="mh-bg on" id="mhBgA"></div><div class="mh-bg" id="mhBgB"></div></div>
  <div class="mh-shade"></div>

  <!-- LEFT: song info -->
  <div class="mh-info">
    <div class="mh-pill"><span class="mh-live"></span><span>Now playing</span><span class="mh-count" id="mhCount">01 / <?php echo str_pad(count($heroSongs), 2, "0", STR_PAD_LEFT); ?></span></div>

    <div class="mh-title" id="mhTitle">
      <canvas id="mhCanvas" aria-hidden="true"></canvas>
      <h1 class="mh-sr" id="mhSrTitle"><?php echo htmlspecialchars($heroSongs[0]["title"]); ?></h1>
    </div>

    <div class="mh-panel">
      <div class="mh-swap-group" id="mhSwap">
        <div class="mh-artist" id="mhArtist"></div>
        <div class="mh-rate"><span class="mh-stars" id="mhStars" aria-hidden="true">★★★★★</span><span id="mhRatingNum"></span><small>listener rating</small></div>
        <div class="mh-tags" id="mhTags"></div>
        <p class="mh-desc" id="mhDesc"></p>
        <div class="mh-facts">
          <div><b id="mhFDur"></b>Length</div>
          <div><b id="mhFYear"></b>Released</div>
          <div><b id="mhFPlays"></b>Plays</div>
        </div>
      </div>

      <div class="mh-seek">
        <span id="mhTNow">0:00</span>
        <input type="range" id="mhSeek" min="0" max="1000" value="0" step="1" aria-label="Seek">
        <span id="mhTEnd">0:00</span>
      </div>

      <div class="mh-actions">
        <button type="button" class="mh-btn mh-play" id="mhPlay" aria-label="Play">
          <svg class="mh-ico-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15a1 1 0 0 0 1.5.86l12-7.5a1 1 0 0 0 0-1.72l-12-7.5A1 1 0 0 0 7 4.5z"/></svg>
          <svg class="mh-ico-pause" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4.5" height="16" rx="1.2"/><rect x="13.5" y="4" width="4.5" height="16" rx="1.2"/></svg>
          <span class="mh-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
          <span id="mhPlayLabel">Play</span>
        </button>
        <button type="button" class="mh-btn mh-ghost" id="mhWatch" aria-label="Watch music video">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9.5 5 2.5-5 2.5z"/></svg>
          <span>Watch</span>
        </button>
        <button type="button" class="mh-btn mh-round" id="mhLike" aria-label="Save song" aria-pressed="false">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.8 3.6 4.5 7 4.5c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.4 0 5.6 3.3 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
        </button>
        <button type="button" class="mh-toggle" id="mhAuto" aria-pressed="true" title="Auto change songs when nothing is playing"><span class="mh-sw"></span><span>Auto</span></button>
      </div>
    </div>
  </div>

  <!-- RIGHT: landscape thumbnails -->
  <div class="mh-deck">
    <div class="mh-track" id="mhTrack" role="listbox" aria-label="Featured songs"></div>
    <div class="mh-nav">
      <button type="button" class="mh-btn mh-round" id="mhPrev" aria-label="Previous song"><svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></button>
      <div class="mh-timer" id="mhTimer"><i></i></div>
      <button type="button" class="mh-btn mh-round" id="mhNext" aria-label="Next song"><svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></button>
    </div>
  </div>

  <a href="#library" class="mh-scroll" aria-label="Browse the library">
    <span>Browse library</span>
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
  </a>

  <!-- audio comes from each song's sample video; kept out of sight -->
  <video id="mhMedia" preload="none" playsinline style="display:none"></video>
</section>

<script>
window.MH_SONGS = <?php echo json_encode($heroSongs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
<script src="assets/js/music-hero.js" defer></script>
