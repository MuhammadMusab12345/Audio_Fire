/* ==========================================================================
   MUSIC HERO  (assets/js/music-hero.js)
   Data comes from PHP: window.MH_SONGS  (see shared/music-hero.php)
   - glitch landscape background + landscape thumbnails
   - white cursor-reactive particle title
   - real playback (audio of the song's sample video), seek bar, auto change
   Talks to the songs page only through two events:
     "mh:watch"  (hero -> page)  open the video modal
     "mh:pause"  (page -> hero)  stop hero audio when a video opens
   ========================================================================== */
(function () {
  'use strict';

  var SONGS = window.MH_SONGS || [];
  var root = document.getElementById('mhHero');
  if (!SONGS.length || !root) return;

  var $ = function (id) { return document.getElementById(id); };
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var rnd = function (a, b) { return a + Math.random() * (b - a); };
  var INTERVAL = 6000;           // auto change every 6s while nothing is playing
  /* likes go through the shared save system (window.AFS - assets/js/save.js):
     localStorage for guests, database when signed in, badge + toast included */

  var idx = 0, auto = true, playing = false, switching = false, visible = true;
  var timerId = null, activeBg = 'A';

  var media = $('mhMedia');
  var seek = $('mhSeek');
  var track = $('mhTrack');
  var timerEl = $('mhTimer');
  var stage = $('mhStage');

  /* ---------- helpers ---------- */
  function fmt(n) {
    if (!isFinite(n) || n < 0) n = 0;
    var m = Math.floor(n / 60), s = Math.floor(n % 60);
    return m + ':' + (s < 10 ? '0' : '') + s;
  }
  function hexToRgb(h) { return [1, 3, 5].map(function (i) { return parseInt(h.slice(i, i + 2), 16); }).join(','); }
  /* page theme: the texture/lighting in base.css reads --mid-light, --mid, --deep, --accent-soft.
     We derive them from the hero song's accent and set them on <html>, so the whole page
     below the hero glides (about 1s) into the same light as the hero. */
  function hexToHsl(h) {
    var r = parseInt(h.slice(1, 3), 16) / 255, g = parseInt(h.slice(3, 5), 16) / 255, b = parseInt(h.slice(5, 7), 16) / 255;
    var mx = Math.max(r, g, b), mn = Math.min(r, g, b), l = (mx + mn) / 2, d = mx - mn, hh = 0, s = 0;
    if (d) {
      s = d / (1 - Math.abs(2 * l - 1));
      if (mx === r) hh = ((g - b) / d) % 6; else if (mx === g) hh = (b - r) / d + 2; else hh = (r - g) / d + 4;
      hh = (hh * 60 + 360) % 360;
    }
    return [hh, s * 100, l * 100];
  }
  function applyPageTheme(hex) {
    var h = hexToHsl(hex)[0], st = document.documentElement.style;
    st.setProperty('--accent-soft', 'hsl(' + h.toFixed(0) + ',90%,76%)');
    st.setProperty('--mid-light',  'hsl(' + h.toFixed(0) + ',52%,30%)');
    st.setProperty('--mid',        'hsl(' + h.toFixed(0) + ',58%,16%)');
    st.setProperty('--deep',       'hsl(' + h.toFixed(0) + ',55%,7%)');
  }
  function setP(el, p) { el.style.setProperty('--p', p.toFixed(2) + '%'); }

  /* preload every poster so the glitch swap never waits on the network */
  SONGS.forEach(function (s, i) { setTimeout(function () { (new Image()).src = s.img; }, i === 0 ? 0 : 400 + i * 150); });

  /* ---------- thumbnail cards ---------- */
  SONGS.forEach(function (s, i) {
    var c = document.createElement('div');
    c.className = 'mh-card';
    c.setAttribute('role', 'option');
    c.tabIndex = 0;
    c.innerHTML =
      '<img src="' + s.thumb + '" alt="" draggable="false" ' + (i < 4 ? 'loading="eager"' : 'loading="lazy"') + '>' +
      '<span class="mh-num">' + String(i + 1).padStart(2, '0') + '</span>' +
      '<span class="mh-card-eq" aria-hidden="true"><i></i><i></i><i></i></span>' +
      '<span class="mh-hover-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>' +
      '<div class="mh-cap"><b></b><span></span></div>';
    c.querySelector('b').textContent = s.title;
    c.querySelector('.mh-cap span').textContent = s.artist;
    c.setAttribute('aria-label', s.title + ' – ' + s.artist);
    c.addEventListener('click', function () {
      if (i === idx) { togglePlay(); } else { go(i, true, true); }
    });
    c.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); c.click(); }
    });
    track.appendChild(c);
  });
  var cards = Array.prototype.slice.call(track.children);

  /* ---------- glitch background ---------- */
  function glitchBg(src) {
    var url = 'url("' + src + '")';
    var next = activeBg === 'A' ? $('mhBgB') : $('mhBgA');
    var prev = activeBg === 'A' ? $('mhBgA') : $('mhBgB');
    next.style.backgroundImage = url;
    next.classList.add('on');
    prev.classList.remove('on');
    activeBg = activeBg === 'A' ? 'B' : 'A';
    if (reduce) return;

    [['mh-fr', -1], ['mh-fc', 1]].forEach(function (pair) {
      var d = document.createElement('div');
      d.className = 'mh-split';
      d.style.backgroundImage = url;
      d.style.filter = 'url(#' + pair[0] + ')';
      stage.appendChild(d);
      var dir = pair[1];
      d.animate([
        { opacity: .9, transform: 'translateX(' + dir * 26 + 'px)' },
        { opacity: .7, transform: 'translateX(' + dir * -14 + 'px)', offset: .35 },
        { opacity: .8, transform: 'translateX(' + dir * 10 + 'px)', offset: .6 },
        { opacity: 0, transform: 'translateX(0)' }
      ], { duration: 560, easing: 'steps(7)' }).onfinish = function () { d.remove(); };
    });

    for (var i = 0; i < 8; i++) {
      var d = document.createElement('div');
      d.className = 'mh-slice';
      d.style.backgroundImage = url;
      var top = rnd(0, 92), h = rnd(3, 15);
      d.style.clipPath = 'inset(' + top + '% 0 ' + Math.max(0, 100 - top - h) + '% 0)';
      stage.appendChild(d);
      (function (el) {
        var a = rnd(-90, 90), b = rnd(-50, 50);
        el.animate([
          { transform: 'translateX(' + a + 'px)', opacity: 1 },
          { transform: 'translateX(' + b + 'px)', offset: .5, opacity: 1 },
          { transform: 'translateX(0)', opacity: 0 }
        ], { duration: rnd(300, 620), delay: rnd(0, 120), easing: 'steps(5)', fill: 'both' }).onfinish = function () { el.remove(); };
      })(d);
    }
    next.animate([
      { filter: 'brightness(2.2) saturate(1.8)' },
      { filter: 'brightness(.7)', offset: .3 },
      { filter: 'brightness(1.5) hue-rotate(20deg)', offset: .55 },
      { filter: 'saturate(1.1) contrast(1.05)' }
    ], { duration: 480, easing: 'steps(6)' });
  }

  /* ---------- info panel ---------- */
  function saveId(s) {
    /* same id on every page: slug of "title artist" (AFS.id) */
    return window.AFS ? AFS.id(s.title, s.artist) : String(s.id);
  }
  function savedList() {
    return window.AFS ? AFS.list('song') : [];
  }
  function isLiked(s) {
    var id = saveId(s);
    return savedList().some(function (x) { return x.id === id; });
  }

  function fillInfo(s, i) {
    root.style.setProperty('--mh-accent', s.accent);
    applyPageTheme(s.accent);
    root.style.setProperty('--mh-accent-rgb', hexToRgb(s.accent));
    $('mhCount').textContent = String(i + 1).padStart(2, '0') + ' / ' + String(SONGS.length).padStart(2, '0');
    $('mhSrTitle').textContent = s.title;
    $('mhArtist').textContent = s.artist;
    $('mhStars').style.setProperty('--p', (s.rating / 5 * 100).toFixed(0) + '%');
    $('mhRatingNum').textContent = s.rating.toFixed(1);
    $('mhTags').innerHTML = '';
    [s.genre, s.language, s.album].forEach(function (t) {
      var el = document.createElement('span');
      el.className = 'mh-tag'; el.textContent = t;
      $('mhTags').appendChild(el);
    });
    $('mhDesc').textContent = s.desc;
    $('mhFDur').textContent = fmt(s.dur);
    $('mhFYear').textContent = s.year;
    $('mhFPlays').textContent = s.plays;
    $('mhTEnd').textContent = fmt(s.dur);
    var liked = isLiked(s);
    $('mhLike').classList.toggle('liked', liked);
    $('mhLike').setAttribute('aria-pressed', liked);
    var g = $('mhSwap'); g.classList.remove('mh-swap'); void g.offsetWidth; g.classList.add('mh-swap');
    if ('mediaSession' in navigator && window.MediaMetadata) {
      navigator.mediaSession.metadata = new MediaMetadata({
        title: s.title, artist: s.artist, album: s.album,
        artwork: [{ src: s.thumb, sizes: '640x357', type: 'image/jpeg' }]
      });
    }
  }

  /* ---------- change song ---------- */
  function go(i, manual, forcePlay) {
    idx = (i + SONGS.length) % SONGS.length;
    var s = SONGS[idx];
    var shouldPlay = !!forcePlay || playing;

    cards.forEach(function (c, k) { c.classList.toggle('active', k === idx); });
    var c = cards[idx];
    c.classList.remove('glitch'); void c.offsetWidth; c.classList.add('glitch');
    track.scrollTo({ left: c.offsetLeft - (track.clientWidth - c.offsetWidth) / 2, behavior: reduce ? 'auto' : 'smooth' });

    glitchBg(s.img);
    fillInfo(s, idx);
    particles.setText(s.title);

    seek.value = 0; setP(seek, 0); $('mhTNow').textContent = '0:00';

    if (shouldPlay) {
      loadAndPlay();
    } else {
      switching = true; media.pause(); media.removeAttribute('src'); media.dataset.i = '';
      setTimeout(function () { switching = false; }, 150);
      setPlayingUI(false);
    }
    restartTimer();
  }

  /* ---------- playback ---------- */
  function loadAndPlay() {
    var s = SONGS[idx];
    switching = true;
    if (media.dataset.i !== String(idx)) { media.src = s.video; media.dataset.i = String(idx); }
    var p = media.play();
    if (p && p.catch) p.catch(function () { setPlayingUI(false); });
    setTimeout(function () { switching = false; }, 150);
    setPlayingUI(true);
  }
  function togglePlay() {
    if (playing) { media.pause(); } else { loadAndPlay(); }
  }
  function setPlayingUI(on) {
    playing = on;
    root.classList.toggle('is-playing', on);
    $('mhPlayLabel').textContent = on ? 'Pause' : 'Play';
    $('mhPlay').setAttribute('aria-label', on ? 'Pause' : 'Play');
    cards.forEach(function (c, k) { c.classList.toggle('playing', on && k === idx); });
    restartTimer();
  }

  media.addEventListener('pause', function () { if (!switching && !media.ended) setPlayingUI(false); });
  media.addEventListener('play', function () { if (!playing) setPlayingUI(true); });
  media.addEventListener('ended', function () { go(idx + 1, false, true); });
  media.addEventListener('error', function () { if (media.getAttribute('src')) setPlayingUI(false); });
  media.addEventListener('loadedmetadata', function () { if (isFinite(media.duration)) $('mhTEnd').textContent = fmt(media.duration); });
  media.addEventListener('timeupdate', function () {
    var d = media.duration || SONGS[idx].dur;
    var p = Math.min(100, media.currentTime / d * 100);
    $('mhTNow').textContent = fmt(media.currentTime);
    if (!seekDragging) { seek.value = (p * 10).toFixed(0); setP(seek, p); }
    if (playing) timerEl.firstElementChild.style.width = p + '%';
  });

  var seekDragging = false;
  seek.addEventListener('pointerdown', function () { seekDragging = true; });
  addEventListener('pointerup', function () { seekDragging = false; });
  seek.addEventListener('input', function () {
    var p = Number(seek.value) / 10;
    setP(seek, p);
    if (media.dataset.i === String(idx) && isFinite(media.duration)) media.currentTime = media.duration * p / 100;
    else { seek.value = 0; setP(seek, 0); }
  });

  $('mhPlay').addEventListener('click', togglePlay);

  $('mhWatch').addEventListener('click', function () {
    media.pause();
    var s = SONGS[idx];
    document.dispatchEvent(new CustomEvent('mh:watch', { detail: { title: s.title, artist: s.artist, video: s.video } }));
  });
  document.addEventListener('mh:pause', function () { media.pause(); });

  $('mhLike').addEventListener('click', function () {
    var s = SONGS[idx];
    /* AFS.toggle adds/removes, shows the toast and updates the badge */
    if (window.AFS) {
      AFS.toggle('song', { id: saveId(s), title: s.title, artist: s.artist });
    }
    var liked = isLiked(s);
    this.classList.toggle('liked', liked); this.setAttribute('aria-pressed', liked);
  });

  if ('mediaSession' in navigator) {
    try {
      navigator.mediaSession.setActionHandler('play', function () { loadAndPlay(); });
      navigator.mediaSession.setActionHandler('pause', function () { media.pause(); });
      navigator.mediaSession.setActionHandler('previoustrack', function () { go(idx - 1, true); });
      navigator.mediaSession.setActionHandler('nexttrack', function () { go(idx + 1, true); });
    } catch (e) {}
  }

  /* ---------- auto change (only while nothing is playing) ---------- */
  function restartTimer() {
    clearTimeout(timerId);
    timerEl.classList.remove('run');
    timerEl.firstElementChild.style.width = '';
    void timerEl.offsetWidth;
    if (playing) {
      var d = media.duration || SONGS[idx].dur;
      timerEl.firstElementChild.style.width = Math.min(100, media.currentTime / d * 100) + '%';
      return;
    }
    if (!auto || !visible || document.hidden) return;
    timerEl.style.setProperty('--mh-dur', INTERVAL + 'ms');
    timerEl.classList.add('run');
    timerId = setTimeout(function () { go(idx + 1, false); }, INTERVAL);
  }
  $('mhAuto').addEventListener('click', function (e) {
    auto = !auto; e.currentTarget.setAttribute('aria-pressed', auto); restartTimer();
  });
  document.addEventListener('visibilitychange', restartTimer);
  $('mhNext').addEventListener('click', function () { go(idx + 1, true); });
  $('mhPrev').addEventListener('click', function () { go(idx - 1, true); });

  addEventListener('keydown', function (e) {
    var t = e.target;
    if (t && /^(INPUT|SELECT|TEXTAREA)$/.test(t.tagName) && t !== seek) return;
    if (t && t.isContentEditable) return;
    if (!visible) return;
    var modal = document.getElementById('videoModal');
    if (modal && modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') go(idx + 1, true);
    if (e.key === 'ArrowLeft') go(idx - 1, true);
  });

  /* ---------- particle title: pure white, pushed away by the cursor ---------- */
  var particles = (function () {
    var wrap = $('mhTitle'), canvas = $('mhCanvas'), ctx = canvas.getContext('2d');
    var off = document.createElement('canvas'), octx = off.getContext('2d', { willReadFrequently: true });
    var W = 0, H = 0, dpr = 1, list = [], text = '', mx = -1e4, my = -1e4, gen = 0, running = false;
    var RADIUS = 120, DISPERSION = 16, RETURN = .08, FRICTION = .85, DOT = 1.7;

    function layout() {
      var fam = 'Syne, "Arial Black", Impact, sans-serif';
      var font = function (px) { return '800 ' + px + 'px ' + fam; };
      octx.font = font(100);
      var w1 = octx.measureText(text).width;
      var fs1 = Math.min(H * .7, W * .98 / w1 * 100, 150);
      var lines = [text], fs = fs1;
      if (text.indexOf(' ') > -1) {
        var words = text.split(' '), best = null;
        for (var k = 1; k < words.length; k++) {
          var a = words.slice(0, k).join(' '), b = words.slice(k).join(' ');
          var wm = Math.max(octx.measureText(a).width, octx.measureText(b).width);
          if (!best || wm < best.wm) best = { a: a, b: b, wm: wm };
        }
        var fs2 = Math.min(H * .43, W * .98 / best.wm * 100, 150);
        if (fs2 > fs1 * 1.18) { lines = [best.a, best.b]; fs = fs2; }
      }
      return { lines: lines, fs: fs, font: font(fs) };
    }

    function sample() {
      if (!W || !H || !text) return [];
      off.width = W * dpr; off.height = H * dpr;
      octx.setTransform(dpr, 0, 0, dpr, 0, 0);
      octx.clearRect(0, 0, W, H);
      var L = layout();
      octx.font = L.font; octx.fillStyle = '#fff'; octx.textAlign = 'left'; octx.textBaseline = 'middle';
      var lh = L.fs * .98, y0 = H / 2 - (L.lines.length - 1) * lh / 2;
      L.lines.forEach(function (t, i) { octx.fillText(t, 4, y0 + i * lh); });
      var step = Math.max(1, Math.round(3 * dpr));
      var data = octx.getImageData(0, 0, off.width, off.height), pts = [];
      for (var y = 0; y < data.height; y += step)
        for (var x = 0; x < data.width; x += step)
          if (data.data[(y * data.width + x) * 4 + 3] > 128) pts.push([x / dpr, y / dpr]);
      return pts;
    }

    function apply(burst) {
      var pts = sample(); if (!pts.length) return;
      var g = ++gen, n = pts.length;
      for (var i = 0; i < n; i++) {
        var p = list[i];
        if (!p) { p = list[i] = { x: pts[i][0] + rnd(-30, 30), y: pts[i][1] + rnd(-30, 30), vx: 0, vy: 0, kill: false }; }
        p.ox = pts[i][0]; p.oy = pts[i][1]; p.kill = false;
        if (burst && !reduce) { p.vx += rnd(-9, 9); p.vy += rnd(-9, 9); }
      }
      for (var j = n; j < list.length; j++) { var t = pts[(Math.random() * n) | 0]; list[j].ox = t[0]; list[j].oy = t[1]; list[j].kill = true; }
      if (list.length > n) setTimeout(function () { if (gen === g) list = list.filter(function (p) { return !p.kill; }); }, 650);
    }

    function resize() {
      var r = wrap.getBoundingClientRect(); if (!r.width) return;
      dpr = Math.min(window.devicePixelRatio || 1, 2); W = r.width; H = r.height;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      apply(false);
    }

    function frame() {
      if (!visible) { running = false; return; }
      ctx.clearRect(0, 0, W, H);
      var hasMouse = mx > -1e3;
      for (var i = 0; i < list.length; i++) {
        var p = list[i];
        if (hasMouse) {
          var dx = mx - p.x, dy = my - p.y, d = Math.sqrt(dx * dx + dy * dy);
          if (d < RADIUS && d > .01) {
            var f = (RADIUS - d) / RADIUS;
            p.vx -= dx / d * f * DISPERSION; p.vy -= dy / d * f * DISPERSION;
          }
        }
        p.vx += (p.ox - p.x) * RETURN; p.vy += (p.oy - p.y) * RETURN;
        p.vx *= FRICTION; p.vy *= FRICTION;
        p.x += p.vx; p.y += p.vy;
      }
      ctx.fillStyle = '#ffffff';
      ctx.beginPath();
      for (var k = 0; k < list.length; k++) { var q = list[k]; ctx.moveTo(q.x + DOT, q.y); ctx.arc(q.x, q.y, DOT, 0, 6.2832); }
      ctx.fill();
      requestAnimationFrame(frame);
    }
    function run() { if (!running) { running = true; requestAnimationFrame(frame); } }

    /* listen on the whole window so the effect also feels the cursor just outside the letters */
    addEventListener('pointermove', function (e) {
      var r = canvas.getBoundingClientRect();
      var x = e.clientX - r.left, y = e.clientY - r.top;
      if (x > -RADIUS && x < r.width + RADIUS && y > -RADIUS && y < r.height + RADIUS) { mx = x; my = y; }
      else { mx = my = -1e4; }
    }, { passive: true });
    document.addEventListener('pointerleave', function () { mx = my = -1e4; });
    addEventListener('blur', function () { mx = my = -1e4; });
    addEventListener('pointerup', function (e) { if (e.pointerType === 'touch') mx = my = -1e4; });
    addEventListener('pointercancel', function () { mx = my = -1e4; });

    if (window.ResizeObserver) new ResizeObserver(resize).observe(wrap); else addEventListener('resize', resize);

    return {
      setText: function (t) { text = t; if (W) apply(true); },
      ready: function () { resize(); run(); },
      run: run
    };
  })();

  /* pause heavy work while the hero is off-screen */
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      visible = entries[0].isIntersecting;
      if (visible) particles.run();
      restartTimer();
    }, { threshold: .12 }).observe(root);
  }

  /* ---------- start (wait for the display font so the first title is sampled correctly) ---------- */
  (function start() {
    var done = false;
    function run() { if (done) return; done = true; go(0, false); particles.ready(); }
    var t = setTimeout(run, 1200);
    if (document.fonts && document.fonts.load) {
      document.fonts.load('800 100px Syne').catch(function () {}).then(function () { clearTimeout(t); run(); });
    } else { run(); }
  })();
})();
