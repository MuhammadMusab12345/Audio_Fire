/* ==========================================================================
   SAVED PAGE (assets/js/saved.js)
   Renders the two tabs (saved songs / saved artists) from the shared save
   system (window.AFS - assets/js/save.js), removes items without a reload
   and plays saved songs inline through the site's single audio element
   (AF.createAudio from assets/js/main.js).
   ========================================================================== */
(function () {
  'use strict';

  if (!window.AFS) return;                     // save.js failed to load

  const $ = id => document.getElementById(id);
  const listSongs   = $('svListSongs');
  const listArtists = $('svListArtists');
  if (!listSongs || !listArtists) return;

  /* the id from saved.php?play=<id> (server already checked it for
     signed-in visitors - see saved.php) */
  const playId = new URLSearchParams(location.search).get('play') || '';
  let playFired = false;                        // autoplay only once

  /* ---- one shared audio element for the whole page ---------------------- */
  const audio = AF.createAudio();
  let playingRow = null, playingBtn = null;

  function stopRow() {
    audio.pause();
    if (playingBtn) { playingBtn.textContent = '▶ Play'; playingBtn.classList.remove('playing'); }
    if (playingRow) playingRow.classList.remove('sv-playing');
    playingBtn = null; playingRow = null;
  }
  audio.addEventListener('ended', stopRow);

  /* ---- helpers ---------------------------------------------------------- */
  const vinylSvg = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">' +
    '<rect width="120" height="120" fill="#16101f"/>' +
    '<circle cx="60" cy="60" r="40" fill="#0b0710" stroke="rgba(255,255,255,.16)"/>' +
    '<circle cx="60" cy="60" r="26" fill="none" stroke="rgba(255,255,255,.08)"/>' +
    '<circle cx="60" cy="60" r="10" fill="#c04be0"/></svg>'
  );

  function emptyBox(text, cta) {
    const box = document.createElement('div');
    box.className = 'sv-empty';
    box.innerHTML =
      '<div class="sv-empty-vinyl" aria-hidden="true"></div>' +
      '<p></p>' +
      (cta ? '<a class="sv-btn sv-primary" href="' + cta.href + '">' + cta.label + '</a>' : '');
    box.querySelector('p').textContent = text;
    return box;
  }

  /* ---- songs tab --------------------------------------------------------- */
  function renderSongs() {
    const songs = AFS.list('song');
    $('svCountSongs').textContent = String(songs.length);
    listSongs.replaceChildren();
    if (!songs.length) {
      listSongs.appendChild(emptyBox(
        'No saved songs yet - tap the heart on any song card to keep it here.',
        { href: 'music.php', label: 'Browse Music' }
      ));
      return;
    }
    songs.forEach(song => {
      const row = document.createElement('div');
      row.className = 'sv-row';
      row.innerHTML =
        '<img class="sv-thumb" alt="" loading="lazy">' +
        '<div class="sv-info"><strong></strong><span></span></div>' +
        '<div class="sv-actions">' +
        (song.src ? '<button class="sv-btn sv-play" type="button">&#9654; Play</button>' : '') +
        '<a class="sv-btn sv-open" href="#">Open</a>' +
        '<button class="sv-btn sv-remove" type="button" aria-label="Remove">Remove</button>' +
        '</div>';
      const img = row.querySelector('.sv-thumb');
      img.src = song.img || vinylSvg;
      img.alt = 'Cover of ' + song.title;
      row.querySelector('strong').textContent = song.title;
      row.querySelector('span').textContent = song.artist;

      const playBtn = row.querySelector('.sv-play');
      if (playBtn) {
        playBtn.addEventListener('click', () => {
          if (playingRow === row && !audio.paused) { stopRow(); return; }
          stopRow();
          audio.src = song.src;                 // single audio: nothing else can overlap
          audio.play().catch(() => {
            AFToast.show('This audio file could not be played.', 'err');
            stopRow();
          });
          playBtn.textContent = '⏸ Pause';
          playBtn.classList.add('playing');
          row.classList.add('sv-playing');
          playingRow = row; playingBtn = playBtn;
        });
      }

      /* Open: the song's own page (library entry) or the music page */
      const open = row.querySelector('.sv-open');
      open.href = song.href || 'music.php';
      if (!song.src && !song.href) open.href = 'songs.php';

      row.querySelector('.sv-remove').addEventListener('click', () => {
        stopRowIf(row);
        AFS.remove('song', song.id);            // triggers af:saves -> re-render
      });

      listSongs.appendChild(row);

      /* deep link: saved.php?play=<id> starts this row right away */
      if (playId && song.id === playId && !playFired) {
        playFired = true;
        playBtn && playBtn.click();
      }
    });
  }
  function stopRowIf(row) { if (playingRow === row) stopRow(); }

  /* ---- artists tab ------------------------------------------------------- */
  function renderArtists() {
    const artists = AFS.list('artist');
    $('svCountArtists').textContent = String(artists.length);
    listArtists.replaceChildren();
    if (!artists.length) {
      listArtists.appendChild(emptyBox(
        'No saved artists yet - save an artist from the Home page or the Singers page.',
        { href: 'singers.php', label: 'Meet the Singers' }
      ));
      return;
    }
    artists.forEach(artist => {
      const card = document.createElement('div');
      card.className = 'sv-artist';
      card.innerHTML =
        '<img class="sv-face" alt="" loading="lazy">' +
        '<strong class="sv-name"></strong>' +
        '<div class="sv-actions">' +
        '<a class="sv-btn sv-open" href="singers.php">Open</a>' +
        '<button class="sv-btn sv-remove" type="button" aria-label="Remove">Remove</button>' +
        '</div>';
      const img = card.querySelector('.sv-face');
      img.src = artist.img || vinylSvg;
      img.alt = artist.name;
      card.querySelector('.sv-name').textContent = artist.name;
      card.querySelector('.sv-remove').addEventListener('click', () => {
        AFS.remove('artist', artist.id);
      });
      listArtists.appendChild(card);
    });
  }

  /* ---- tabs -------------------------------------------------------------- */
  const tabs = [$('svTabSongs'), $('svTabArtists')];
  const panels = [$('svPanelSongs'), $('svPanelArtists')];
  function selectTab(i, pushHash) {
    tabs.forEach((t, k) => {
      t.classList.toggle('on', k === i);
      t.setAttribute('aria-selected', String(k === i));
      panels[k].classList.toggle('on', k === i);
      panels[k].hidden = k !== i;
    });
    if (pushHash) history.replaceState(null, '', i ? '#artists' : '#songs');
  }
  tabs.forEach((t, i) => t.addEventListener('click', () => selectTab(i, true)));
  if (location.hash === '#artists') selectTab(1, false);

  /* ---- re-render on every save change (this page + other tabs) ----------- */
  function renderAll() { renderSongs(); renderArtists(); }
  document.addEventListener('af:saves', renderAll);
  renderAll();
})();
