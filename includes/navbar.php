<!-- ===== SHARED NAVBAR (assets/css/navbar.css + assets/js/navbar.js) =====
     $currentPage decides which link gets .active (set it before including) -->
<nav class="navbar-container" aria-label="Main navigation" data-current="<?= htmlspecialchars($currentPage) ?>">
  <a href="<?= $BASE ?>/index.php" class="brand-logo" aria-label="Home">
    <img src="assets/music-logo.png" alt="Audio Fire logo">
  </a>
  <div class="nav-links">
    <div class="active-indicator" id="indicator"></div>
    <a href="<?= $BASE ?>/index.php" class="nav-item<?= $currentPage === 'home' ? ' active' : '' ?>">Home</a>
    <a href="<?= $BASE ?>/singers.php" class="nav-item<?= $currentPage === 'singers' ? ' active' : '' ?>">Singers</a>
    <a href="<?= $BASE ?>/songs.php" class="nav-item<?= $currentPage === 'songs' ? ' active' : '' ?>">Songs</a>
    <a href="<?= $BASE ?>/music.php" class="nav-item<?= $currentPage === 'music' ? ' active' : '' ?>">Music</a>
    <a href="<?= $BASE ?>/album.php" class="nav-item<?= $currentPage === 'album' ? ' active' : '' ?>">Album</a>
    <a href="<?= $BASE ?>/reviews.php" class="nav-item<?= $currentPage === 'reviews' ? ' active' : '' ?>">Reviews</a>
    <a href="<?= $BASE ?>/contact.php" class="nav-item<?= $currentPage === 'contact' ? ' active' : '' ?>">Contact</a>
  </div>
  <div class="actions-container">
    <button class="icon-btn" id="savedToggle" type="button" aria-label="Saved songs" title="Saved Songs">
      <svg viewBox="0 0 24 24"><path d="M6 4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21l-6-4-6 4z"></path></svg>
    </button>
    <button class="icon-btn" id="searchToggle" type="button" aria-label="Toggle search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
    </button>
    <div class="search-box" id="searchBox"><input class="search-input" type="search" placeholder="Search..." aria-label="Search music"></div>
    <a href="<?= $BASE ?>/account.php#signin" class="account-btn<?= $currentPage === 'account' ? ' active' : '' ?>"<?= $currentPage === 'account' ? ' aria-current="page"' : '' ?>>Account</a>
    <button class="icon-btn menu-btn" id="menuToggle" type="button" aria-label="Open menu">
      <svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>
    </button>
  </div>
</nav>

<div class="overlay" id="overlay"></div>
<aside class="sidebar" id="sidebar" aria-label="Mobile navigation">
  <div class="sidebar-header">
    <div class="sidebar-brand"><img src="assets/music-logo.png" alt=""> Audio Fire</div>
    <button class="icon-btn" id="closeSidebar" type="button" aria-label="Close menu"><svg viewBox="0 0 24 24"><path d="m18 6-12 12"></path><path d="m6 6 12 12"></path></svg></button>
  </div>
  <nav class="sidebar-links">
    <a href="<?= $BASE ?>/index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7"></path><path d="M5 9v12h14V9"></path></svg>Home</a>
    <a href="<?= $BASE ?>/singers.php" class="<?= $currentPage === 'singers' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg>Singers</a>
    <a href="<?= $BASE ?>/songs.php" class="<?= $currentPage === 'songs' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>Songs</a>
    <a href="<?= $BASE ?>/music.php" class="<?= $currentPage === 'music' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>Music</a>
    <a href="<?= $BASE ?>/album.php" class="<?= $currentPage === 'album' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="12" cy="12" r="4"></circle></svg>Album</a>
    <a href="<?= $BASE ?>/reviews.php" class="<?= $currentPage === 'reviews' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z"></path></svg>Reviews</a>
    <a href="<?= $BASE ?>/contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M3 8l9 6 9-6"></path></svg>Contact</a>
  </nav>
  <div class="sidebar-bottom">
    <a href="<?= $BASE ?>/account.php#signin" class="<?= $currentPage === 'account' ? 'active' : '' ?>">Account / Login</a>
    <button class="secondary" id="mobileSaved" type="button">View Saved Songs</button>
  </div>
</aside>

<aside class="sidebar" id="savedPanel" aria-label="Saved songs">
  <div class="sidebar-header">
    <span class="sidebar-brand">Saved</span>
    <button class="icon-btn" id="closeSaved" type="button" aria-label="Close saved songs"><svg viewBox="0 0 24 24"><path d="m18 6-12 12"></path><path d="m6 6 12 12"></path></svg></button>
  </div>
  <p style="font-size:13px;color:rgba(255,255,255,.65);line-height:1.6">Your saved songs appear here. Tap the heart on any card to keep it.</p>
  <div class="saved-list" id="savedList"></div>
  <div class="sidebar-bottom">
    <a class="secondary saved-viewall" href="<?= $BASE ?>/saved.php">Saved Songs &amp; Artists &rarr;</a>
    <button class="secondary" id="clearSaved" type="button">Clear Saved Songs</button>
  </div>
</aside>
