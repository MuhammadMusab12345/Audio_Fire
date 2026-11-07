<?php
/* ==========================================================================
   REVIEWS PAGE (reviews.php)
   Converted from the original self contained "Final reviews.html" - same
   design and behaviour, now on the shared head / navbar / footer.
   ========================================================================== */
$currentPage     = 'reviews';
$pageTitle       = 'Reviews | Audio Fire';
$metaDescription = 'Real words from our listeners - ratings, reviews and the write-a-review form of the Audio Fire music site.';
$pageStyles      = array('assets/css/reviews.css');
$pageScripts     = array('assets/js/reviews.js');
require __DIR__ . '/includes/head.php';
?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="rv-page">
<svg width="0" height="0" style="position:absolute"><filter id="wisp"><feTurbulence type="fractalNoise" baseFrequency=".008 .012" numOctaves="2" seed="4"><animate attributeName="baseFrequency" dur="40s" values=".008 .012;.012 .008;.008 .012" repeatCount="indefinite"/></feTurbulence><feDisplacementMap in="SourceGraphic" scale="140"/></filter></svg>
<div class="smoke"><b></b><b></b><b></b><b></b></div><div class="notes" id="notes"></div>

<main class="shell">
 <div class="brand"><span class="eq"><i></i><i></i><i></i><i></i></span></div>
 <h1><span>What people</span><span class="o">are saying</span></h1>
 <p class="sub">Real words from our listeners. Share yours too.</p>
 <div class="summary"><div class="pill"><b id="avg">0.0</b>average rating</div><div class="pill"><b id="count">0</b>reviews</div></div>

 <section class="grid" id="grid"></section>

 <section class="formbox">
  <h2>Write a review</h2>
  <p class="sub" style="margin:6px 0 0">Tell us what you liked and what we can improve</p>
  <form id="f" novalidate>
   <!-- bot trap + form render time (checked again on the server - api/reviews.php) -->
   <input type="text" id="af_company" name="af_company" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
   <input type="hidden" id="ts" value="<?= time() ?>">
   <div class="row">
    <div><label>Name</label><input id="name" maxlength="30" placeholder="Anna Khan"></div>
    <div><label>Designation (optional)</label><input id="role" maxlength="30" placeholder="Music lover"></div>
   </div>
   <div><label>Your rating</label><div class="pick" id="pick"><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span></div></div>
   <div><label>Your photo (optional)</label>
    <div class="photo"><img id="prev" alt=""><button type="button" class="btn ghost" id="upbtn">Choose image</button><input type="file" id="file" accept="image/*" hidden></div></div>
   <div><label>Your review</label><textarea id="text" maxlength="250" placeholder="What do you think about the songs and the website?"></textarea></div>
   <div><label>What should we improve?</label>
    <div class="chips" id="chips"><div class="chip">Design</div><div class="chip">Song quality</div><div class="chip">Speed</div><div class="chip">More songs</div><div class="chip">Player</div><div class="chip">Nothing, it's great</div></div></div>
   <div><label>Extra suggestion (optional)</label><textarea id="tip" maxlength="200" placeholder="How can we make it better?"></textarea></div>
   <div class="msg" id="msg"></div>
   <button class="btn" type="submit">Submit review</button>
  </form>
 </section>
</main>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
