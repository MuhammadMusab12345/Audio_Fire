/* ==========================================================================
   HOME PAGE - "NOW PLAYING" featured vinyl section (#home-featured)
   Same behaviour as the music page's featured block: rotating vinyl +
   progress ring + tonearm, huge title, language chips and a Popular list.
   Song data comes from assets/js/songs-data.js (shared with music.php).
   Home theme only - colours are CSS variables (--accent), no theme change.
   One audio element per page (AF.createAudio() from assets/js/main.js).
   ========================================================================== */
(function () {
  'use strict';

  const root = document.getElementById('home-featured');
  if (!root) return;

  const SONGS = (typeof AF_SONGS !== 'undefined') ? AF_SONGS : [];
  const LANGS = (typeof AF_LANGS !== 'undefined') ? AF_LANGS : ['All'];
  if (!SONGS.length) return;

  const $ = s => root.querySelector(s);
  const fmt = AF.fmt;

  let cur = 0, playing = false, lang = 'All';

  /* ---- the page's single audio element ---- */
  const a = AF.createAudio();
  a.onended = () => go(1);
  a.onplay = () => { playing = true; ui(); };
  a.onpause = () => { playing = false; ui(); };

  /* ---- language filter ---- */
  const L = () => SONGS.map((s, i) => (lang === 'All' || s.l === lang) ? i : -1).filter(i => i >= 0);

  function chips() {
    const box = $('#hf-chips');
    box.innerHTML = '';
    LANGS.forEach(function (l) {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'hf-chip' + (l === lang ? ' hf-on' : '');
      b.textContent = l;
      b.onclick = () => { lang = l; render(); };
      box.appendChild(b);
    });
  }

  function row(i, n) {
    const s = SONGS[i];
    const r = document.createElement('div');
    r.className = 'hf-row' + (i === cur ? ' hf-on' : '');
    r.tabIndex = 0;
    r.setAttribute('role', 'button');
    r.innerHTML = (n ? '<i>' + String(n).padStart(2, '0') + '</i>' : '') +
      '<img src="' + s.i + '" alt="" loading="lazy"><div><p>' + s.t + '</p><small>' + s.a + '</small></div>' +
      '<em>' + fmt(s.d) + '</em>';
    /* save heart (assets/js/save.js injects the button) */
    r.dataset.save = 'song';
    if (window.AFS) r.dataset.saveId = AFS.id(s.t, s.a);
    r.dataset.saveTitle = s.t;
    r.dataset.saveArtist = s.a;
    r.dataset.saveSrc = s.s;
    r.dataset.saveImg = s.i;
    r.onclick = () => load(i, true);
    r.onkeydown = e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); load(i, true); } };
    return r;
  }

  /* ---- Popular list: 5 rows from the current song, following the filter ---- */
  function popular() {
    const box = $('#hf-pop');
    const l = L();
    if (!l.length) {
      box.innerHTML = '<p class="hf-empty">Is language ka abhi koi song nahi hai.</p>';
      return;
    }
    box.innerHTML = '';
    const st = Math.max(0, l.indexOf(cur));
    const slice = l.slice(st, st + 5);
    slice.forEach((i, k) => box.appendChild(row(i, k + 1)));
    if (l.length < 5) {
      const rest = l.slice(0, 5 - slice.length).filter(i => !slice.includes(i));
      rest.forEach((i, k) => box.appendChild(row(i, slice.length + k + 1)));
    }
  }

  function ui() {
    const s = SONGS[cur];
    const w = s.t.split(' ');

    $('#hf-lab-text').textContent = String(cur + 1).padStart(2, '0') + '. ' + s.t;
    $('#hf-title').innerHTML = w.length > 1
      ? w.slice(0, -1).join(' ') + ' <span class="hf-out">' + w.slice(-1) + '</span>'
      : s.t;
    $('#hf-meta').textContent = s.a + ' \u00b7 ' + s.l;
    $('#hf-play').innerHTML = playing ? '&#9208;' : '&#9654;';

    root.classList.toggle('hf-playing', playing);
    syncSave();
    chips();
    popular();
  }

  /* ---- save heart for the featured song (#hf-save in index.php) --------- */
  function syncSave() {
    const btn = $('#hf-save');
    if (!btn) return;
    const s = SONGS[cur];
    const on = !!(window.AFS && AFS.has('song', AFS.id(s.t, s.a)));
    btn.classList.toggle('on', on);
    btn.setAttribute('aria-pressed', String(on));
    btn.setAttribute('aria-label', (on ? 'Remove ' : 'Save ') + s.t);
  }

  function load(i, auto) {
    cur = i;
    a.src = SONGS[i].s;
    if (auto) a.play().catch(() => {});
    else { playing = false; a.pause(); }
    ui();
  }

  function toggle() {
    if (a.src) { playing ? a.pause() : a.play().catch(() => {}); }
    else load(cur, true);
  }

  function go(d) {
    const l = L();
    if (!l.length) return;
    const p = l.indexOf(cur);
    load(p < 0 ? l[0] : l[(p + d + l.length) % l.length], true);
  }

  /* ---- progress ring ---- */
  setInterval(function () {
    const t = a.currentTime || 0;
    const d = a.duration || SONGS[cur].d;
    const r = Math.min(1, t / d);
    const ring = $('#hf-ring');
    if (ring) ring.setAttribute('stroke-dashoffset', String(307.9 * (1 - r)));
  }, 250);

  /* ---- own controls ---- */
  $('#hf-play').onclick = toggle;
  $('#hf-next').onclick = () => go(1);
  const saveBtn = $('#hf-save');
  if (saveBtn) {
    saveBtn.onclick = () => {
      const s = SONGS[cur];
      if (window.AFS) AFS.toggle('song', { id: AFS.id(s.t, s.a), title: s.t, artist: s.a, src: s.s, img: s.i });
      syncSave();
    };
    /* keep the heart in sync with saves from anywhere on the site */
    document.addEventListener('af:saves', syncSave);
  }

  load(0, false);
})();
