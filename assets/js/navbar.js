/* ==========================================================================
   AUDIO FIRE - SHARED NAVBAR behaviour
   active link by filename, pill indicator, search box, mobile sidebar,
   saved-songs panel (the list itself lives in the shared save system -
   window.AFS / assets/js/save.js: localStorage + database when logged in)
   ========================================================================== */
(function () {
  const $ = id => document.getElementById(id);
  const indicator = $('indicator');
  const navItems = Array.from(document.querySelectorAll('.nav-item'));
  const sideLinks = Array.from(document.querySelectorAll('.sidebar-links a'));
  const sidebar = $('sidebar');
  const savedPanel = $('savedPanel');
  const overlay = $('overlay');

  /* ---- mark the current page as .active (by filename) ----
     hrefs may be written as "/music.php" (root install) while
     location.pathname gives "music.php" - compare only the filename
     so the server rendered $currentPage highlight is never stripped. */
  function currentFile() {
    const path = location.pathname.split('/').pop().toLowerCase();
    return path === '' || path === '/' ? 'index.php' : path;
  }
  function markActive() {
    const file = currentFile();
    let matched = null;
    const all = navItems.concat(sideLinks);
    all.forEach(link => {
      const href = (link.getAttribute('href') || '').split('#')[0];
      const hrefFile = href.split('/').pop().toLowerCase();
      const on = hrefFile && hrefFile === file;
      link.classList.toggle('active', !!on);
      if (on && !matched && navItems.indexOf(link) > -1) matched = link;
    });
    updateIndicator(matched || document.querySelector('.nav-item.active'));
  }

  function updateIndicator(item) {
    if (!item || !indicator || !item.offsetWidth) return;
    indicator.style.width = item.offsetWidth + 'px';
    indicator.style.left = item.offsetLeft + 'px';
  }

  navItems.forEach(item => item.addEventListener('click', () => {
    navItems.forEach(link => link.classList.remove('active'));
    item.classList.add('active');
    updateIndicator(item);
  }));

  /* ---- search (live dropdown from search.php + jump to the library) ---- */
  const searchBase = (document.querySelector('meta[name="af-base"]') || { content: '' }).content;
  const searchBox = $('searchBox');
  const searchInput = document.querySelector('.search-input');
  let dropEl = null;
  let dropRows = [];
  let dropActive = -1;
  let dropTimer = null;
  let dropCtrl = null;
  let dropQuery = '';

  function ensureDrop() {
    if (!dropEl || !searchBox.contains(dropEl)) {
      dropEl = document.createElement('div');
      dropEl.className = 'search-drop';
      dropEl.setAttribute('role', 'listbox');
      searchBox.appendChild(dropEl);
    }
    return dropEl;
  }
  function closeDrop() {
    if (dropTimer) { clearTimeout(dropTimer); dropTimer = null; }
    if (dropCtrl) { dropCtrl.abort(); dropCtrl = null; }
    dropRows = [];
    dropActive = -1;
    if (dropEl) { dropEl.classList.remove('open'); dropEl.replaceChildren(); }
  }
  function setActive(next) {
    if (!dropRows.length) { dropActive = -1; return; }
    const n = dropRows.length;
    dropActive = ((next % n) + n) % n;
    dropRows.forEach((r, i) => r.el.classList.toggle('active', i === dropActive));
    const el = dropRows[dropActive].el;
    if (el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
  }
  function dropMessage(text) {
    const drop = ensureDrop();
    drop.replaceChildren();
    dropRows = [];
    dropActive = -1;
    const msg = document.createElement('div');
    msg.className = 'search-drop-msg';
    msg.textContent = text;
    drop.appendChild(msg);
    drop.classList.add('open');
  }
  function renderDrop(items) {
    const drop = ensureDrop();
    drop.replaceChildren();
    dropRows = [];
    dropActive = -1;
    if (!items.length) { dropMessage('No results'); return; }
    items.forEach(item => {
      const title = String(item.title || '');
      const row = document.createElement('a');
      row.className = 'search-drop-row';
      row.href = String(item.url || '#');
      row.setAttribute('role', 'option');
      const placeholder = () => {
        const ph = document.createElement('span');
        ph.className = 'search-drop-ph';
        ph.textContent = title.slice(0, 1).toUpperCase();
        return ph;
      };
      if (item.img) {
        const thumb = document.createElement('img');
        thumb.className = 'search-drop-thumb';
        thumb.src = String(item.img);
        thumb.alt = '';
        thumb.loading = 'lazy';
        thumb.addEventListener('error', () => { if (thumb.parentNode) thumb.parentNode.replaceChild(placeholder(), thumb); });
        row.appendChild(thumb);
      } else {
        row.appendChild(placeholder());
      }
      const info = document.createElement('span');
      info.className = 'search-drop-info';
      const t = document.createElement('span');
      t.className = 'search-drop-title';
      t.textContent = title;
      const s = document.createElement('span');
      s.className = 'search-drop-sub';
      s.textContent = String(item.sub || '');
      info.append(t, s);
      const badge = document.createElement('span');
      badge.className = 'search-drop-badge';
      badge.textContent = String(item.type || '');
      row.append(info, badge);
      const entry = { el: row, url: String(item.url || '#') };
      row.addEventListener('mouseenter', () => setActive(dropRows.indexOf(entry)));
      drop.appendChild(row);
      dropRows.push(entry);
    });
    drop.classList.add('open');
  }
  function runSearch(value) {
    dropQuery = value;
    if (dropCtrl) { dropCtrl.abort(); dropCtrl = null; }
    if (!value) { closeDrop(); return; }
    dropCtrl = new AbortController();
    dropMessage('Searching...');
    fetch(searchBase + '/search.php?q=' + encodeURIComponent(value), { signal: dropCtrl.signal })
      .then(res => { if (!res.ok) throw new Error('search failed'); return res.json(); })
      .then(data => {
        if (dropQuery !== value) return;
        if (data && data.ok && Array.isArray(data.items)) renderDrop(data.items);
        else closeDrop();
      })
      .catch(err => {
        if (err && err.name === 'AbortError') return;
        if (dropQuery === value) closeDrop();
      });
  }

  $('searchToggle').addEventListener('click', () => {
    const box = $('searchBox');
    box.classList.toggle('open');
    if (box.classList.contains('open')) box.querySelector('input').focus();
    else { box.querySelector('input').value = ''; closeDrop(); }
  });
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      if (dropTimer) clearTimeout(dropTimer);
      const v = searchInput.value.trim();
      dropTimer = setTimeout(() => { dropTimer = null; runSearch(v); }, 250);
    });
    /* Enter opens the highlighted row, otherwise the old library search */
    searchInput.addEventListener('keydown', e => {
      if (e.key === 'Escape') { closeDrop(); return; }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        if (!dropRows.length) return;
        e.preventDefault();
        setActive(dropActive + (e.key === 'ArrowDown' ? 1 : -1));
        return;
      }
      if (e.key !== 'Enter') return;
      e.preventDefault();
      if (dropActive > -1 && dropRows[dropActive]) {
        const url = dropRows[dropActive].url;
        closeDrop();
        window.location = url;
        return;
      }
      closeDrop();
      const q = searchInput.value.trim();
      /* already on the library page? just keep typing there */
      if (location.pathname.split('/').pop().toLowerCase() === 'songs.php') {
        const live = document.getElementById('searchInput');
        if (live) { live.value = q; live.dispatchEvent(new Event('input')); live.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
      }
      location.href = searchBase + '/songs.php' + (q ? '?q=' + encodeURIComponent(q) : '');
    });
  }
  document.addEventListener('click', e => {
    if (dropEl && dropEl.classList.contains('open') && searchBox && !searchBox.contains(e.target)) closeDrop();
  });

  /* ---- panels (mobile sidebar / saved songs) ---- */
  function closePanels() {
    sidebar.classList.remove('open');
    savedPanel.classList.remove('open');
    overlay.classList.remove('open');
    document.body.classList.remove('menu-open');
  }
  function openPanel(panel) {
    closePanels();
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.classList.add('menu-open');
  }
  $('menuToggle').addEventListener('click', () => openPanel(sidebar));
  $('closeSidebar').addEventListener('click', closePanels);
  $('savedToggle').addEventListener('click', () => { renderSaved(); openPanel(savedPanel); });
  $('mobileSaved').addEventListener('click', () => { renderSaved(); openPanel(savedPanel); });
  $('closeSaved').addEventListener('click', closePanels);
  overlay.addEventListener('click', closePanels);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanels(); });

  /* ---- saved songs (shared save system - assets/js/save.js / window.AFS) -
     The panel shows the saved SONGS list; the full list (songs + artists)
     lives on saved.php. Everything stays in sync through AFS events. --- */
  function getSavedSongs() {
    return (window.AFS ? AFS.list('song') : []);
  }
  function saveSong(song) {
    if (window.AFS) AFS.toggle('song', song);   // toast + badge + DB handled inside
    renderSaved();
  }
  function removeSong(id) {
    if (window.AFS) AFS.remove('song', id);
    renderSaved();
  }
  function renderSaved() {
    const list = $('savedList');
    if (!list) return;
    list.replaceChildren();
    const songs = getSavedSongs();
    /* mobile button shows the live count too */
    const mob = $('mobileSaved');
    if (mob) mob.textContent = songs.length ? 'View Saved Songs (' + songs.length + ')' : 'View Saved Songs';
    if (!songs.length) {
      const empty = document.createElement('p');
      empty.textContent = 'No saved songs yet.';
      empty.style.cssText = 'padding:20px 5px;text-align:center;color:rgba(255,255,255,.6);font-size:14px';
      list.appendChild(empty);
      return;
    }
    songs.forEach(song => {
      const row = document.createElement('div');
      row.className = 'saved-song';
      const info = document.createElement('div');
      info.className = 'song-info';
      const title = document.createElement('strong');
      title.textContent = song.title;
      const artist = document.createElement('span');
      artist.textContent = song.artist;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'remove-song';
      remove.textContent = 'Remove';
      remove.addEventListener('click', () => removeSong(song.id));
      info.append(title, artist);
      row.append(info, remove);
      list.appendChild(row);
    });
  }
  $('clearSaved').addEventListener('click', () => {
    if (window.AFS) {
      /* clear only the songs - artists live on saved.php */
      getSavedSongs().forEach(s => AFS.remove('song', s.id));
    }
    renderSaved();
  });

  /* legacy global: music-hero.js and older code call window.saveSong */
  window.saveSong = saveSong;

  /* re-render the open panel + count whenever a save changes
     (any page, any widget, any other tab) */
  document.addEventListener('af:saves', renderSaved);

  window.addEventListener('resize', () => updateIndicator(document.querySelector('.nav-item.active')));
  window.addEventListener('load', () => { markActive(); });
  markActive();
  renderSaved();
})();
