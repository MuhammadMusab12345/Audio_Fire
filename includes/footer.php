<!-- ===== SHARED FOOTER (assets/css/footer.css + assets/js/footer.js) ===== -->
<div class="footer-wrap">
<footer class="footer" id="footer">
  <canvas id="ripple" aria-hidden="true"></canvas>

  <nav class="cols" aria-label="Footer">
    <div class="col brand">
      <a href="<?= $BASE ?>/index.php" class="footer-logo" aria-label="Audio Fire home">
        <img src="assets/music-logo.png" alt="Audio Fire logo">
      </a>
      <p class="tag">Discover, play and save the songs you love.</p>
      <div class="social" aria-label="Social media">
        <a href="#" aria-label="Instagram">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/></svg></a>
        <a href="#" aria-label="YouTube">
          <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8zM10 15V9l5.2 3L10 15z"/></svg></a>
        <a href="#" aria-label="X (Twitter)">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H22l-7.1 8.1L23 21h-6.4l-5-6.5L5.9 21H2.8l7.6-8.7L2.5 3h6.5l4.5 5.9L18.9 3zm-1.1 16.2h1.7L7.7 4.7H5.9l11.9 14.5z"/></svg></a>
      </div>
    </div>
    <div class="col">
      <h3>Explore</h3>
      <ul>
        <li><a href="<?= $BASE ?>/index.php">Home</a></li>
        <li><a href="<?= $BASE ?>/singers.php">Singers</a></li>
        <li><a href="<?= $BASE ?>/songs.php">Songs</a></li>
        <li><a href="<?= $BASE ?>/album.php">Albums</a></li>
        <li><a href="<?= $BASE ?>/playlists.html">Playlists</a></li>
      </ul>
    </div>
    <div class="col">
      <h3>Listen</h3>
      <ul>
        <li><a href="<?= $BASE ?>/index.php#latest-songs">Latest Songs</a></li>
        <li><a href="<?= $BASE ?>/singers.php">Top Artists</a></li>
        <li><a href="<?= $BASE ?>/songs.php">New Releases</a></li>
        <li><a href="#" id="footerSaved">Saved Songs</a></li>
      </ul>
    </div>
    <div class="col">
      <h3>Support</h3>
      <ul>
        <li><a href="#">About Us</a></li>
        <li><a href="<?= $BASE ?>/contact.php">Contact</a></li>
        <li><a href="#">Help Center</a></li>
        <li><a href="#">Privacy Policy</a></li>
        <li><a href="#">Terms of Use</a></li>
      </ul>
    </div>
  </nav>

  <div class="rule"></div>

  <div class="bottom">
    <p class="legal">&copy; 2026 Audio Fire. All rights reserved.</p>
    <p class="made">Made for music lovers.</p>
  </div>
</footer>
</div>
