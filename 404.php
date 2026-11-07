<?php
/* ==========================================================================
   404 PAGE (404.php)
   Custom "page not found" - same glass / vinyl design as the rest of the
   site, shared navbar + footer.

   Reached in three ways:
     * Apache ErrorDocument (see .htaccess) for any dead URL
     * af404() in includes/auth.php when a page or API is given an id that
       does not exist (real HTTP 404 status in both cases)
     * opened directly: http://localhost/Opencode/404.php
   ========================================================================== */
$currentPage     = '404';                 // matches no navbar link on purpose
$pageTitle       = 'Page Not Found | Audio Fire';
$metaDescription = 'That page could not be found - head back to the Audio Fire home page or browse the song library.';
$pageStyles      = array('assets/css/404.css');
require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<main class="nf-page">
  <div class="nf-glow" aria-hidden="true"></div>

  <div class="nf-card">
    <!-- spinning vinyl with the missing record -->
    <div class="nf-vinyl" aria-hidden="true">
      <div class="nf-disc"><span class="nf-hole"></span></div>
      <div class="nf-arm"></div>
    </div>

    <p class="nf-code">404</p>
    <h1 class="nf-title">This record<span>skipped.</span></h1>
    <p class="nf-text">
      <?= isset($AF_404_MESSAGE) && $AF_404_MESSAGE !== '' ? e($AF_404_MESSAGE) : 'The page you are looking for is not on this track. It may have been moved or the link is broken.' ?>
    </p>

    <div class="nf-actions">
      <a class="nf-btn nf-primary" href="<?= $BASE ?>/index.php">Back to Home</a>
      <a class="nf-btn" href="<?= $BASE ?>/songs.php">Browse Songs</a>
      <button class="nf-btn nf-ghost" type="button" id="nf-back">Go Back</button>
    </div>
  </div>
</main>

<script>
/* "Go Back" - stay on the site when there is history, otherwise go home */
(function () {
  var b = document.getElementById('nf-back');
  if (!b) return;
  b.addEventListener('click', function () {
    if (history.length > 1) history.back();
    else location.href = '<?= $BASE ?>/index.php';
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
