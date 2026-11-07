/* ==========================================================================
   SAVE SYSTEM (assets/js/save.js) - window.AFS - loaded on EVERY page
   --------------------------------------------------------------------------
   What it does
   * one shared store for saved SONGS and ARTISTS   localStorage: af_saves_v1
   * migrates the old navbar store (musicSavedSongs) the first time it runs
   * heart button on any element marked   data-save="song|artist"
   * live badge count on the navbar bookmark button (#savedToggle)
   * syncs with the database through api/save.php when the visitor is logged
     in (guests stay in localStorage; their items are merged into the
     database the moment they sign in - see api/save.php?action=merge)
   * cross tab sync (storage event) + cross widget sync (af:saves event)

   Contract for markup (everything optional except data-save):
     data-save="song|artist"     makes the element save-able
     data-save-id="slug"         stable id (auto: slug of title + artist)
     data-save-title / -artist   display text
     data-save-src="x.mp3"       audio file (songs - enables inline play)
     data-save-img="path.webp"   thumbnail
     data-save-href="songs.php"  open link when there is no audio
   ========================================================================== */
(function () {
  'use strict';

  const KEY = 'af_saves_v1';          // this module's store
  const OLD_KEY = 'musicSavedSongs';  // pre-save-system store (migrated once)

  /* ---- values injected by includes/head.php (missing on static pages) --- */
  const meta = n => { const m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };
  const USER = meta('af-user');                     // '' = guest
  const CSRF = meta('af-csrf');
  const BASE = meta('af-base') || '';
  const API  = BASE + '/api/save.php';

  /* ---- helpers ---------------------------------------------------------- */
  const $ = (sel, root) => (root || document).querySelector(sel);
  const arr = v => Array.isArray(v) ? v : [];

  /** Stable id shared by every page: "Stay With Me Sam Smith" -> slug. */
  function slug(text) {
    return String(text || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 190);
  }
  function songId(title, artist) { return slug(title + ' ' + (artist || '')); }

  /* ---- store ------------------------------------------------------------ */
  let store = { songs: [], artists: [] };

  function normalize(kind, raw) {
    raw = raw || {};
    if (kind === 'song') {
      const title = String(raw.title || '').trim();
      if (!title) return null;
      const artist = String(raw.artist || '').trim();
      return {
        id: String(raw.id || songId(title, artist)),
        title: title,
        artist: artist || 'Unknown Artist',
        src: String(raw.src || raw.s || ''),
        img: String(raw.img || raw.i || ''),
        href: String(raw.href || '')
      };
    }
    const name = String(raw.name || raw.title || '').trim();
    if (!name) return null;
    return {
      id: String(raw.id || slug(name)),
      name: name,
      img: String(raw.img || '')
    };
  }

  function load() {
    let data = null;
    try { data = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) { data = null; }
    store = {
      songs:   arr(data && data.songs).map(s => normalize('song', s)).filter(Boolean),
      artists: arr(data && data.artists).map(a => normalize('artist', a)).filter(Boolean)
    };

    /* one time migration of the old navbar saved-songs list --------------- */
    try {
      const old = arr(JSON.parse(localStorage.getItem(OLD_KEY) || '[]'));
      old.forEach(o => {
        const s = normalize('song', { title: o.title, artist: o.artist, id: o.id });
        if (s && !store.songs.some(x => x.id === s.id)) store.songs.push(s);
      });
      if (old.length) {
        persist();
        localStorage.removeItem(OLD_KEY);   // migrated - the old key goes away
      }
    } catch (e) { /* corrupt old data: ignore */ }
  }

  function persist() {
    try { localStorage.setItem(KEY, JSON.stringify(store)); }
    catch (e) { /* private mode / quota: keep working in memory */ }
  }

  /* ---- server sync (only for logged-in visitors) ------------------------ */
  function remote(action, payload) {
    if (!USER || !CSRF) return Promise.resolve(null);   // guest -> local only
    return fetch(API + (action ? '?action=' + action : ''), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      credentials: 'same-origin',
      body: JSON.stringify(payload || {})
    }).then(r => r.json().catch(() => null)).catch(() => null);
  }

  /** Pull the server list, union it with the local one, push local-only
      items back (this is the "merge saved items on login" step). */
  function pullFromServer() {
    if (!USER || !CSRF) return Promise.resolve(null);
    return fetch(API + '?action=list', { credentials: 'same-origin' })
      .then(r => r.json().catch(() => null))
      .then(res => {
        if (!res || !res.ok) return null;
        let changed = false;
        arr(res.songs).forEach(raw => {
          const s = normalize('song', raw);
          if (s && !store.songs.some(x => x.id === s.id)) { store.songs.push(s); changed = true; }
        });
        arr(res.artists).forEach(raw => {
          const a = normalize('artist', raw);
          if (a && !store.artists.some(x => x.id === a.id)) { store.artists.push(a); changed = true; }
        });

        /* local items the server does not know yet -> merge them up */
        const upSongs = store.songs.filter(s => !arr(res.songs).some(x => String(x.id) === s.id));
        const upArt   = store.artists.filter(a => !arr(res.artists).some(x => String(x.id) === a.id));
        if (upSongs.length || upArt.length) {
          remote('merge', { songs: upSongs, artists: upArt });
        }
        if (changed) { persist(); emit(); }
        return true;
      })
      .catch(() => null);
  }

  /* ---- public state ----------------------------------------------------- */
  function list(kind) { return kind === 'song' ? store.songs : store.artists; }
  function has(kind, id) { return list(kind).some(x => x.id === id); }
  function count() { return store.songs.length + store.artists.length; }

  /** Add or remove one item. Returns true when it is now saved. */
  function toggle(kind, raw) {
    const item = normalize(kind, raw);
    if (!item) return false;
    const bucket = kind === 'song' ? store.songs : store.artists;
    const at = bucket.findIndex(x => x.id === item.id);
    const added = at < 0;
    if (added) bucket.push(item); else bucket.splice(at, 1);
    persist();
    emit();
    if (USER && CSRF) {
      remote('', { action: added ? 'add' : 'remove', kind: kind, item: item });
    }
    if (window.AFToast) {
      const label = kind === 'song' ? item.title : item.name;
      window.AFToast.show(added ? 'Saved “' + label + '”' : 'Removed “' + label + '”', added ? 'ok' : '');
    }
    return added;
  }

  /** Remove by id (saved page / navbar panel). */
  function remove(kind, id) {
    const bucket = kind === 'song' ? store.songs : store.artists;
    const at = bucket.findIndex(x => x.id === id);
    if (at < 0) return;
    const item = bucket[at];
    bucket.splice(at, 1);
    persist();
    emit();
    if (USER && CSRF) remote('', { action: 'remove', kind: kind, item: item });
  }

  /** Wipe everything (saved page button). */
  function clear() {
    store = { songs: [], artists: [] };
    persist();
    emit();
    if (USER && CSRF) remote('', { action: 'clear' });
  }

  /* ---- events + badge --------------------------------------------------- */
  function emit() { document.dispatchEvent(new CustomEvent('af:saves', { detail: store })); }

  function syncBadge() {
    const btn = document.getElementById('savedToggle');
    if (!btn) return;
    let badge = document.getElementById('afBadge');
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'af-badge';
      badge.id = 'afBadge';
      badge.setAttribute('aria-hidden', 'true');
      btn.appendChild(badge);
    }
    const n = count();
    badge.textContent = n > 99 ? '99+' : String(n);
    badge.classList.toggle('show', n > 0);
    btn.setAttribute('aria-label', n ? 'Saved songs and artists (' + n + ' saved)' : 'Saved songs and artists');
  }

  /* ---- heart buttons ---------------------------------------------------- */
  function metaOf(el) {
    const kind = el.dataset.save === 'artist' ? 'artist' : 'song';
    const text = sel => { const n = el.querySelector(sel); return n ? n.textContent.trim() : ''; };
    /* image: an explicit data-save-img wins - even when it is empty
       (some cards carry huge inline data-URI photos we never store) */
    const pickImg = () => el.hasAttribute('data-save-img')
      ? (el.getAttribute('data-save-img') || '')
      : (el.querySelector('img') ? el.querySelector('img').getAttribute('src') : '');
    if (kind === 'artist') {
      return normalize('artist', {
        id: el.dataset.saveId,
        name: el.dataset.saveTitle || text('.name') || text('.fname') || el.getAttribute('aria-label') || '',
        img: pickImg()
      });
    }
    return normalize('song', {
      id: el.dataset.saveId,
      title: el.dataset.saveTitle || text('[data-save-label]') || text('.name') || text('.title') || text('h3') || text('h4') || text('strong'),
      artist: el.dataset.saveArtist || text('.artist') || text('.role') || '',
      src: el.dataset.saveSrc || '',
      img: pickImg(),
      href: el.dataset.saveHref || ''
    });
  }

  const SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.6-4.7-9.6-9.2A5.45 5.45 0 0 1 12 6.6a5.45 5.45 0 0 1 9.6 5.2C19.6 16.3 12 21 12 21z"/></svg>';

  function enhance(root) {
    const scope = root && root.querySelectorAll ? root : document;
    let found = scope.querySelectorAll ? scope.querySelectorAll('[data-save]') : [];
    if (scope.matches && scope.matches('[data-save]')) found = [scope].concat(Array.from(found));
    Array.from(found).forEach(el => {
      if (!el.dataset.afWired) el.dataset.afWired = '1';
      let btn = el.querySelector(':scope > .af-save');
      if (!btn) {
        btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'af-save';
        btn.innerHTML = SVG;
        el.appendChild(btn);
      }
      const item = metaOf(el);
      if (!item) return;
      btn.dataset.kind = el.dataset.save === 'artist' ? 'artist' : 'song';
      btn.dataset.id = item.id;
      const label = btn.dataset.kind === 'song' ? item.title : item.name;
      btn.setAttribute('aria-label', (has(btn.dataset.kind, item.id) ? 'Remove ' : 'Save ') + label);
      btn.setAttribute('title', btn.getAttribute('aria-label'));
      btn.onclick = function (ev) {
        ev.preventDefault(); ev.stopPropagation();   // never follow the card link / start the song
        const on = toggle(btn.dataset.kind, item);
        btn.classList.toggle('on', on);
        btn.setAttribute('aria-pressed', String(on));
        btn.setAttribute('aria-label', (on ? 'Remove ' : 'Save ') + label);
      };
      btn.classList.toggle('on', has(btn.dataset.kind, item.id));
      btn.setAttribute('aria-pressed', btn.classList.contains('on'));
    });
  }

  function applyStates() {
    Array.from(document.querySelectorAll('.af-save')).forEach(btn => {
      const on = has(btn.dataset.kind || 'song', btn.dataset.id || '');
      btn.classList.toggle('on', on);
      btn.setAttribute('aria-pressed', String(on));
    });
  }

  /* ---- boot ------------------------------------------------------------- */
  load();

  function afterChange() { syncBadge(); applyStates(); }

  document.addEventListener('af:saves', afterChange);
  /* another tab changed a save -> reload our copy */
  addEventListener('storage', ev => {
    if (ev.key && ev.key !== KEY) return;
    load(); afterChange();
  });

  /* dynamically rendered cards (music.js, album.js, ...) get hearts too */
  let queued = null;
  const observer = new MutationObserver(muts => {
    for (const m of muts) {
      for (const node of m.addedNodes) {
        if (node.nodeType === 1 && (node.matches('[data-save]') || node.querySelector('[data-save]'))) {
          if (queued) return;
          queued = setTimeout(() => { queued = null; enhance(document); }, 60);
          return;
        }
      }
    }
  });

  function start() {
    enhance(document);
    syncBadge();
    if (document.body) observer.observe(document.body, { childList: true, subtree: true });
    /* logged in visitor: pull the saved list from the database and merge */
    if (USER) pullFromServer();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();

  /* ---- legacy bridge (music-hero.js calls window.saveSong) -------------- */
  window.saveSong = function (song) { toggle('song', song); };

  /* ---- public API -------------------------------------------------------- */
  window.AFS = {
    id: songId,
    slug: slug,
    has: has,
    list: list,
    count: count,
    toggle: toggle,
    remove: remove,
    clear: clear,
    enhance: enhance,
    sync: pullFromServer,
    user: USER
  };
})();
