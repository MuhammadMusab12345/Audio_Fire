/**
 * PAGE-WIDE GLITCH (home page) - every section follows ONE trigger.
 *
 * hero.js owns the only clock: its 8-second cycle writes data-phase on #hero.
 * This file mirrors that phase onto <body data-g="idle|out|in">, stamps the
 * shared .g-t class (plus a random 0-150ms --g-delay) on every card, heading,
 * image and button OUTSIDE the hero, and tracks which of them are on screen
 * with .g-inview - only those ever animate, so off-screen sections cost
 * nothing.
 *
 * The CSS lives next to the hero rules in assets/css/hero.css:
 *   body[data-g="out"] .g-t.g-inview  ->  glitch-out .32s steps(4, end)
 *   body[data-g="in"]  .g-t.g-inview  ->  glitch-in  .44s steps(4, end)
 *   prefers-reduced-motion: reduce    ->  the softer *-soft keyframes
 * so the singer swap in the hero and the rest of the page glitch in sync.
 */
(function () {
  "use strict";

  var hero = document.getElementById("hero");
  if (!hero) return;

  /* Card / heading / image / button selectors for every section after the hero.
     Deliberately NOT listed: anything whose own opacity is 0 until interaction
     (#song-title, #counter, #cursor, #close - the glitch keyframes would flash
     them back on) and anything nested inside a listed target (double clip-paths
     would swallow the inner element). */
  var TARGETS = [
    /* shared section headings (artists / now playing / latest songs / reviews) */
    ".section-heading",
    "#home-featured .hf-pop-title",
    ".footer .col h3",

    /* console fanned stack: the fan transform lives on .ocs-card, so the
       glitch rides on its two transform-free children (photo + text body) */
    ".ocs-media",
    ".ocs-body",

    /* featured artists */
    ".artists .card",

    /* now playing */
    "#home-featured .hf-title",
    "#home-featured .hf-btn",
    "#home-featured .hf-chip",
    "#home-featured .hf-row",
    "#home-featured .hf-view",

    /* latest songs (WebGL carousel + focus panel) */
    "#latest-songs #songs-mount",
    "#latest-songs #info h3",
    "#latest-songs #info a",
    "#latest-songs #info-save",

    /* reviews (their own 3s swap wins the cascade while it runs) */
    "#reviews .card",
    "#rv-dots button",

    /* footer */
    ".footer .footer-logo img",
    ".footer .social a"
  ];

  /* ---------------- phase mirror: #hero[data-phase] -> body[data-g] -------- */
  function syncPhase() {
    document.body.setAttribute("data-g", hero.getAttribute("data-phase") || "idle");
  }
  syncPhase();
  new MutationObserver(syncPhase).observe(hero, {
    attributes: true,
    attributeFilter: ["data-phase"]
  });

  /* ---------------- in-viewport gate: only .g-inview ever animates -------- */
  var io = new IntersectionObserver(function (entries) {
    for (var i = 0; i < entries.length; i++) {
      entries[i].target.classList.toggle("g-inview", entries[i].isIntersecting);
    }
  }, { threshold: 0 });

  /* ---------------- stamp: .g-t + per-element stagger -------------------- */
  var stamped = new WeakSet();

  function stamp() {
    for (var i = 0; i < TARGETS.length; i++) {
      var nodes = document.querySelectorAll(TARGETS[i]);
      for (var j = 0; j < nodes.length; j++) {
        var el = nodes[j];
        if (stamped.has(el)) continue;
        stamped.add(el);
        el.classList.add("g-t");
        el.style.setProperty("--g-delay", Math.floor(Math.random() * 151) + "ms");
        io.observe(el);
      }
    }
  }

  /* released nodes (rebuilt review slots / song rows) must leave the observer */
  function release(root) {
    if (!root || root.nodeType !== 1) return;
    if (stamped.has(root)) {
      io.unobserve(root);
      stamped.delete(root);
    }
    var inner = root.querySelectorAll(".g-t");
    for (var i = 0; i < inner.length; i++) {
      io.unobserve(inner[i]);
      stamped.delete(inner[i]);
    }
  }

  /* ---------------- dynamic content (review slots, song rows, chips) ----- */
  var stampT = 0;
  new MutationObserver(function (records) {
    for (var i = 0; i < records.length; i++) {
      var rm = records[i].removedNodes;
      for (var j = 0; j < rm.length; j++) release(rm[j]);
    }
    clearTimeout(stampT);
    stampT = setTimeout(stamp, 120);
  }).observe(document.body, { childList: true, subtree: true });

  stamp();
})();
