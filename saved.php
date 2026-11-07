<?php
/* ==========================================================================
   SAVED PAGE (saved.php)
   Everything the visitor saved - two tabs: SAVED SONGS | SAVED ARTISTS.
   Guests see their localStorage list; signed-in users see the database
   list (both are merged by assets/js/save.js).

   Deep link: saved.php?play=<song-id> starts that song when it exists -
   an id that is not in the signed-in user's list shows the real 404 page
   with a 404 status (af404 in includes/auth.php).
   ========================================================================== */
require_once __DIR__ . '/includes/auth.php';

$currentUser = af_user();

/* ---- invalid id -> the same 404 page with status 404 -------------------- */
if (isset($_GET['play']) && $_GET['play'] !== '') {
    $playId = mb_strtolower(trim(strip_tags((string) $_GET['play'])));
    $playId = trim(preg_replace('/[^a-z0-9]+/u', '-', $playId), '-');
    if ($currentUser && $AF_DB) {
        $hit = af_row(
            'SELECT item_id FROM saved_songs WHERE user_id = ? AND item_id = ? LIMIT 1',
            [(int) $currentUser['id'], $playId]
        );
        if (!$hit) {
            af404('That song is not in your saved list (anymore).');
        }
    }
}

$currentPage     = 'saved';
$pageTitle       = 'Saved | Audio Fire';
$metaDescription = 'Your saved songs and artists at Audio Fire - play a saved song, open an artist or remove anything from the list.';
$pageStyles      = array('assets/css/saved.css');
$pageScripts     = array('assets/js/saved.js');
require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<main class="sv-page">
  <header class="sv-head">
    <h1 class="sv-title">Your <span class="sv-grad">Collection</span></h1>
    <p class="sv-sub">
      <?php if ($currentUser): ?>
        Signed in as <strong><?= e($currentUser['name']) ?></strong> - your saves are stored in your account and follow you on every device.
      <?php else: ?>
        You are browsing as a guest - saves live in this browser.
        <a href="<?= $BASE ?>/account.php#signin">Sign in</a> to keep them forever.
      <?php endif; ?>
    </p>
  </header>

  <!-- tabs -->
  <div class="sv-tabs" role="tablist" aria-label="Saved items">
    <button class="sv-tab on" id="svTabSongs" type="button" role="tab" aria-selected="true" aria-controls="svPanelSongs">
      Saved Songs <span class="sv-count" id="svCountSongs">0</span>
    </button>
    <button class="sv-tab" id="svTabArtists" type="button" role="tab" aria-selected="false" aria-controls="svPanelArtists">
      Saved Artists <span class="sv-count" id="svCountArtists">0</span>
    </button>
  </div>

  <section class="sv-panel on" id="svPanelSongs" role="tabpanel" aria-labelledby="svTabSongs">
    <div class="sv-list" id="svListSongs"></div>
  </section>
  <section class="sv-panel" id="svPanelArtists" role="tabpanel" aria-labelledby="svTabArtists" hidden>
    <div class="sv-list sv-grid" id="svListArtists"></div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
