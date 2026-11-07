    </div><!-- /.content -->

    <footer class="panel-foot">
      Audio Fire Admin &middot; <?= date('Y') ?> &middot; All content is stored in the database
    </footer>
  </div><!-- /.main -->
</div><!-- /.app -->

<script>
/* =============================================================== AFAdmin === */
var AFAdmin = (function(){
  var meta = document.querySelector('meta[name="af-csrf"]');
  var csrf = meta ? meta.getAttribute('content') : '';
  var root = document.querySelector('link[rel="stylesheet"][href*="admin.css"]');
  var href = root ? root.getAttribute('href') : '';
  /* strip "/admin/assets/admin.css..." -> install root; when the stylesheet
     link is missing (hard refresh race), fall back to the page URL */
  var BASE = href
    ? href.replace(/\/admin\/assets\/admin\.css.*$/, '')
    : location.pathname.replace(/\/admin\/[^\/]*$/, '');

  /* ---------------------------------------------------------- toast ------ */
  var toastEl = document.createElement('div');
  toastEl.className = 'toast';
  document.body.appendChild(toastEl);
  var tTimer;
  function toast(msg, ok){
    toastEl.textContent = msg;
    toastEl.classList.toggle('err', ok === false);
    toastEl.classList.add('show');
    clearTimeout(tTimer);
    tTimer = setTimeout(function(){ toastEl.classList.remove('show'); }, 2800);
  }

  /* ---------------------------------------------------------- modal ------ */
  function modal(id, on){
    var m = document.getElementById(id);
    if (!m) return;
    if (on) { m.classList.add('on'); document.body.style.overflow = 'hidden'; }
    else    { m.classList.remove('on'); document.body.style.overflow = ''; }
  }

  /* ------------------------------------------------------------ post ----- */
  /* Shared JSON POST to admin/api.php. Always resolves to a parsed object:
     a non-JSON body becomes "Server error (HTTP <status>): <first 140 chars>"
     (full body logged to the console) and a true network failure becomes
     "Cannot reach the server". The optional button is disabled and shows a
     spinner while the request is in flight. */
  function post(fd, btn){
    if (btn) { btn.disabled = true; btn.classList.add('is-busy'); }
    return fetch(BASE + '/admin/api.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){
        return r.text().then(function(t){
          var j = null;
          try { j = JSON.parse(t); } catch(e) { j = null; }
          if (!j || typeof j !== 'object'){
            var head = String(t || '').replace(/\s+/g, ' ').trim().slice(0, 140);
            console.error('[AF] non-JSON response, HTTP ' + r.status, t);
            return { ok: false, error: 'Server error (HTTP ' + r.status + '): ' + (head || 'empty response') };
          }
          return j;
        });
      })
      .catch(function(e){
        console.error('[AF] request failed', e);
        return { ok: false, error: 'Cannot reach the server' };
      })
      .then(function(j){
        if (btn) { btn.disabled = false; btn.classList.remove('is-busy'); }
        return j;
      });
  }

  return { csrf: csrf, base: BASE, toast: toast, modal: modal, post: post };
})();
window.AFAdmin = AFAdmin;

/* new-row flash: after a save the list reloads newest-first and the row the
   user just created/edited glows briefly (set by the save handlers) */
(function(){
  try {
    var raw = sessionStorage.getItem('af_flash_row');
    if (!raw) return;
    sessionStorage.removeItem('af_flash_row');
    var i = raw.indexOf('|');
    if (i < 0) return;
    var page = raw.slice(0, i), id = raw.slice(i + 1);
    if (location.pathname.split('/').pop() !== page) return;
    var row = document.querySelector('tr[data-id="' + id + '"]');
    if (!row) return;
    row.classList.add('row-new');
    if (row.scrollIntoView) row.scrollIntoView({ block: 'center' });
    setTimeout(function(){ row.classList.remove('row-new'); }, 2600);
  } catch(e){}
})();

/* flash message saved by the previous request -> shown as a toast */
(function(){
  var f = document.getElementById('af-flash');
  if (f && f.textContent) AFAdmin.toast(f.textContent, f.getAttribute('data-ok') !== '0');
})();

/* ================================================================ theme === */
(function(){
  var btn = document.getElementById('themebtn');
  if (!btn) return;
  btn.addEventListener('click', function(){
    var el = document.documentElement;
    var next = el.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    el.setAttribute('data-theme', next);
    try { localStorage.setItem('af-admin-theme', next); } catch(e){}
    document.dispatchEvent(new CustomEvent('af-theme', { detail: next }));
  });
})();

/* ============================================================== sidebar === */
(function(){
  var side = document.getElementById('side'),
      open = document.getElementById('menubtn'),
      scrim = document.getElementById('scrim');
  function close(){ if(side) side.classList.remove('on'), side.classList.remove('open'); if(scrim) scrim.classList.remove('on'); }
  function toggle(){
    if (!side) return;
    var on = !side.classList.contains('open');
    side.classList.toggle('open', on);
    if (scrim) scrim.classList.toggle('on', on);
  }
  if (open) open.addEventListener('click', toggle);
  if (scrim) scrim.addEventListener('click', close);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();

/* ======================================================= admin chip ====== */
/* Initials avatar in the top bar -> Account / Log out dropdown */
(function(){
  var btn = document.getElementById('whoBtn'),
      pop = document.getElementById('whoPop');
  if (!btn || !pop) return;
  function setOpen(on){
    pop.classList.toggle('on', on);
    btn.setAttribute('aria-expanded', on ? 'true' : 'false');
  }
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    setOpen(!pop.classList.contains('on'));
  });
  document.addEventListener('click', function(e){
    if (!pop.contains(e.target) && !btn.contains(e.target)) setOpen(false);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') setOpen(false);
  });
})();

/* =================================================== global search ====== */
/* Top bar box -> admin/search_api.php, debounced 250ms, minimum 2 chars.
   Grouped JSON (songs / artists / albums / users, 5 per group) rendered in a
   .notif-panel dropdown. Keys: Up / Down / Enter / Esc; Ctrl+K or "/" focuses. */
(function(){
  var box   = document.getElementById('globalSearch'),
      wrap  = document.getElementById('searchWrap'),
      panel = document.getElementById('searchPanel'),
      list  = document.getElementById('searchResults');
  if (!box || !wrap || !panel || !list) return;

  var timer = null, seq = 0, items = [], active = -1;

  var ICONS = {
    music:     '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
    headphones:'<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a9 9 0 0 1 18 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
    mic:       '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/>',
    disc:      '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="2"/>',
    users:     '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
  };

  function esc(s){
    return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
    });
  }
  function icon(name){
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" ' +
           'stroke-linecap="round" stroke-linejoin="round">' + (ICONS[name] || ICONS.music) + '</svg>';
  }
  function setOpen(on){
    panel.classList.toggle('on', on);
    box.setAttribute('aria-expanded', on ? 'true' : 'false');
    if (!on) active = -1;
  }
  function paintActive(){
    items.forEach(function(a, i){
      a.classList.toggle('on', i === active);
      a.setAttribute('aria-selected', i === active ? 'true' : 'false');
    });
    if (active >= 0 && items[active] && items[active].scrollIntoView) {
      items[active].scrollIntoView({ block: 'nearest' });
    }
  }

  /* titles / subtitles / chips are escaped server-side, urls come from ints */
  function render(data){
    var html = '';
    (data.groups || []).forEach(function(g){
      if (!g.items || !g.items.length) return;
      html += '<div class="res-head">' + esc(g.label) + '</div>';
      g.items.forEach(function(it){
        html += '<a class="notif-item" role="option" aria-selected="false" href="' + esc(it.url) + '">' +
                '<span class="nic">' + icon(it.icon) + '</span>' +
                '<span><b>' + it.title + '</b><small>' + it.subtitle + '</small></span>' +
                '<span class="chip">' + esc(it.chip) + '</span></a>';
      });
    });
    if (!html) {
      html = '<div class="notif-empty">No matches for &ldquo;' + esc(data.q || box.value) +
             '&rdquo;. Try a song, artist, album or user name.</div>';
    }
    list.innerHTML = html;
    items = list.querySelectorAll('.notif-item');
    active = -1;
    setOpen(true);
    paintActive();
  }

  function search(){
    var q = box.value.trim();
    if (q.length < 2){
      list.innerHTML = '<div class="notif-empty">Type at least 2 characters to search the whole panel.</div>';
      setOpen(false);
      return;
    }
    var id = ++seq;
    list.innerHTML = '<div class="notif-empty">Searching&hellip;</div>';
    setOpen(true);
    fetch(AFAdmin.base + '/admin/search_api.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(j){ if (id === seq) render(j); })
      .catch(function(){
        if (id !== seq) return;
        list.innerHTML = '<div class="notif-empty">Search is unavailable right now.</div>';
      });
  }

  box.addEventListener('input', function(){
    clearTimeout(timer);
    timer = setTimeout(search, 250);
  });
  box.addEventListener('focus', function(){
    if (box.value.trim().length >= 2) search();
  });

  box.addEventListener('keydown', function(e){
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp'){
      if (!items.length) return;
      e.preventDefault();
      active = e.key === 'ArrowDown'
        ? (active + 1) % items.length
        : (active - 1 + items.length) % items.length;
      paintActive();
    } else if (e.key === 'Enter'){
      /* an active result wins; otherwise let the page's own filter form
         (form.filters) handle Enter exactly as before */
      if (active >= 0 && items[active]){
        e.preventDefault();
        e.stopImmediatePropagation();
        location.href = items[active].getAttribute('href');
      }
    } else if (e.key === 'Escape'){
      if (panel.classList.contains('on')){
        e.preventDefault();
        e.stopPropagation();
        setOpen(false);
      }
    }
  });

  document.addEventListener('click', function(e){
    if (!wrap.contains(e.target)) setOpen(false);
  });
  document.addEventListener('keydown', function(e){
    var el = document.activeElement;
    var typing = el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')){
      e.preventDefault();
      box.focus();
      box.select();
    } else if (e.key === '/' && !typing){
      e.preventDefault();
      box.focus();
      box.select();
    } else if (e.key === 'Escape' && panel.classList.contains('on')){
      setOpen(false);
    }
  });
})();

/* ======================================================== notifications === */
(function(){
  var btn   = document.getElementById('notifBtn'),
      panel = document.getElementById('notifPanel'),
      list  = document.getElementById('notifList'),
      dot   = document.getElementById('notifDot'),
      read  = document.getElementById('notifRead');
  if (!btn || !panel) return;

  function svgFor(kind){
    if (kind === 'user')    return '<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/></svg>';
    if (kind === 'review')  return '<svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .9-5.2 4.9 1.4 7-6.2-3.5-6.2 3.5 1.4-7L2 9.4l7-.9z"/></svg>';
    return '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 8 9 6 9-6"/></svg>';
  }

  function setCount(n){
    if (dot) dot.classList.toggle('hide', !n);
  }

  function render(items, total){
    if (!list) return;
    if (!items || !items.length){
      list.innerHTML = '<div class="notif-empty">You are all caught up. New users, reviews and messages show up here.</div>';
      setCount(0);
      return;
    }
    list.innerHTML = items.map(function(n){
      return '<a class="notif-item" href="' + (n.href || '#') + '">' +
             '<span class="nic">' + svgFor(n.kind) + '</span>' +
             '<span><b>' + esc(n.title) + '</b><small>' + esc(n.body) + '</small></span>' +
             '<time>' + esc(n.when) + '</time></a>';
    }).join('');
    setCount(typeof total === 'number' ? total : items.length);
  }

  function esc(s){
    return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
    });
  }

  function load(){
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'notifications');
    fetch(AFAdmin.base + '/admin/api.php', { method:'POST', body:d, credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(j){ if (j.ok) render(j.items, j.count); })
      .catch(function(){});
  }

  function openState(on){
    panel.classList.toggle('on', on);
    if (on) load();
  }

  btn.addEventListener('click', function(e){
    e.stopPropagation();
    openState(!panel.classList.contains('on'));
  });
  document.addEventListener('click', function(e){
    if (!panel.contains(e.target) && e.target !== btn) openState(false);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') openState(false);
  });

  if (read) read.addEventListener('click', function(e){
    e.preventDefault(); e.stopPropagation();
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'notifications.read');
    fetch(AFAdmin.base + '/admin/api.php', { method:'POST', body:d, credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(){ setCount(0); render([], 0); })
      .catch(function(){});
  });

  /* first paint + poll so a brand-new user lights the bell up live */
  load();
  setInterval(load, 45000);
})();

/* ============================================================ modals ====== */
(function(){
  document.addEventListener('click', function(e){
    var m = e.target.closest ? e.target.closest('.modal') : null;
    if (m && e.target === m) AFAdmin.modal(m.id, false);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape'){
      document.querySelectorAll('.modal.on').forEach(function(m){ AFAdmin.modal(m.id, false); });
    }
  });
  /* any [data-close] button closes its nearest modal */
  document.addEventListener('click', function(e){
    var t = e.target.closest ? e.target.closest('[data-close]') : null;
    if (!t) return;
    var m = t.closest('.modal');
    if (m) AFAdmin.modal(m.id, false);
  });
})();
</script>

<?php if (!empty($adminScript)): ?>
<script>
<?= $adminScript ?>
</script>
<?php endif; ?>
</body>
</html>
