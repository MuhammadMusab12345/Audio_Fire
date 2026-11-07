/* ==========================================================================
   AUDIO FIRE - SHARED SITE SCRIPT (loaded on every page by includes/scripts.php)
   --------------------------------------------------------------------------
   AF.createAudio()  creates THE page's single <audio> element.
                      preload="none", and if a second player ever starts on
                      the same page the first one is paused (no double audio).
   AF.fmt(seconds)   "m:ss" time formatter used by the music player and by
                      the home "Now Playing" section.
   ========================================================================== */
window.AF = (function () {
  'use strict';

  const players = [];

  function createAudio() {
    const audio = new Audio();
    audio.preload = 'none';
    players.push(audio);

    /* one page, one sound: any other audio on this page stops */
    audio.addEventListener('play', function () {
      players.forEach(function (other) {
        if (other !== audio && !other.paused) other.pause();
      });
    });

    return audio;
  }

  function fmt(seconds) {
    const s = Math.max(0, Math.floor(Number(seconds) || 0));
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }

  return { createAudio: createAudio, fmt: fmt };
})();
