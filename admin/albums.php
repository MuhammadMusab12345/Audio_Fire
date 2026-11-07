<?php
/* ==========================================================================
   ADMIN > ALBUMS (admin/albums.php)
   --------------------------------------------------------------------------
   MIRROR of the user Albums page (album.php): the exact singers returned by
   af_album_singers() (artists.in_albums = 1), grouped with their albums,
   newest album first inside each singer, empty groups still visible with an
   Add album button. Album data comes from the same af_albums_data() builder
   the user page uses, so counts can never drift.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();
require_once __DIR__ . '/../includes/singers-data.php';

$q = trim((string) ($_GET['q'] ?? ''));

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = af_row('SELECT * FROM albums WHERE id = ? LIMIT 1', [(int) $_GET['edit']]);
}
$openNew = isset($_GET['new']);

/* THE Albums-page list (same helper album.php renders) */
$singers  = af_album_singers();
$bySinger = af_albums_data($singers);
$genres   = af_all("SELECT name FROM categories WHERE kind='genre' ORDER BY sort_order, name");
/* the modal's Singer dropdown = only Albums-page singers (P3 complaint fixed) */
$artistOpts = $singers;

/* search: a singer name keeps that singer with all their albums; otherwise
   only the albums whose title / genre / description match are kept */
$groups = [];
foreach ($singers as $s) {
    $nm  = (string) $s['n'];
    $als = isset($bySinger[$nm]) ? $bySinger[$nm] : [];
    if ($q === '' || mb_stripos($nm, $q) !== false) {
        $groups[] = ['s' => $s, 'albums' => $als];
        continue;
    }
    $hit = [];
    foreach ($als as $al) {
        if (mb_stripos($al['title'], $q) !== false
            || ($al['genre'] !== '' && mb_stripos($al['genre'], $q) !== false)
            || ($al['description'] !== '' && mb_stripos($al['description'], $q) !== false)) {
            $hit[] = $al;
        }
    }
    if ($hit) {
        $groups[] = ['s' => $s, 'albums' => $hit];
    }
}
$allPage = 0;
foreach ($bySinger as $als) {
    $allPage += count($als);
}
$extras = max(0, (int) af_row('SELECT COUNT(*) c FROM albums')['c'] - $allPage);

/* current values missing from the page list get a "(keep)" option so an edit
   never wipes them (and a warning that the singer is not on the Albums page) */
$curAid = (int) ($edit['artist_id'] ?? 0);
$curAnm = (string) ($edit['artist'] ?? '');
$aidOk = false;
foreach ($artistOpts as $ao) if ((int) $ao['id'] === $curAid) { $aidOk = true; break; }
$keepA = ($curAnm !== '' && !$aidOk);
$curG = (string) ($edit['genre'] ?? '');
$keepG = ($curG !== '' && !in_array($curG, array_column($genres, 'name'), true));

/* "Add album" from a group locks that singer (only page singers can lock) */
$lockAid = 0;
$lockAnm = '';
if ($openNew && !$edit && !empty($_GET['artist'])) {
    foreach ($singers as $ao) {
        if ((int) $ao['id'] === (int) $_GET['artist']) {
            $lockAid = (int) $ao['id'];
            $lockAnm = (string) $ao['n'];
            break;
        }
    }
}

/* inner songs manager: what belongs to this album. One shared helper
   (album_songs join + legacy links) answers both here and on album.php. */
$albumSongs = [];
$attachOpts = [];
$editId = (int) ($edit['id'] ?? 0);
if ($edit) {
    $albumSongs = get_album_songs($editId);
    $inAlbum = [];
    foreach ($albumSongs as $gs) {
        $inAlbum[] = (int) $gs['id'];
    }
    $notIn = '';
    if ($inAlbum) {
        $notIn = 'AND id NOT IN (' . implode(',', array_fill(0, count($inAlbum), '?')) . ') ';
    }
    $attachOpts = af_all(
        'SELECT id, title, artist FROM songs
          WHERE (album_id IS NULL OR album_id <> ?) AND (album IS NULL OR album <> ?) ' . $notIn .
         'ORDER BY title ASC, id ASC LIMIT 400',
        array_merge([$editId, (string) $edit['title']], $inAlbum)
    );
}
/* the album's own singer, used when creating a song straight from this form */
$albumAid  = (int) ($edit['artist_id'] ?? 0);
$albumAnm  = (string) ($edit['artist'] ?? '');
$albumAnmJs = json_encode($albumAnm);

$adminTitle = 'Albums';
$adminCrumb = 'Content / Albums';
$activeNav  = 'albums';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/albums.php';
?>

<div class="card">
  <div class="card-h">
    <h2>All albums</h2>
    <span class="sub"><?= count($singers) ?> singer<?= count($singers) === 1 ? '' : 's' ?> &middot; <?= $allPage ?> album<?= $allPage === 1 ? '' : 's' ?> on the Albums page</span>
    <span class="spacer"></span>
    <a class="btn" href="<?= e($base) ?>/<?= e($self) ?>?new=1">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add album
    </a>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search singer or album...">
    </div>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== ''): ?><a class="chip" href="<?= e($base) ?>/<?= e($self) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if ($extras > 0): ?>
    <p class="hint"><?= $extras ?> album<?= $extras === 1 ? '' : 's' ?> from singers outside the Albums page <?= $extras === 1 ? 'is' : 'are' ?> not listed here.</p>
  <?php endif; ?>

  <?php if (!$singers): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="12" cy="12" r="4"/></svg></span>
      <b>No singers on the Albums page</b>
      <p>Tick &ldquo;Show on Albums page&rdquo; on an artist and they will appear here.</p>
      <div style="margin-top:18px"><a class="btn" href="<?= e($base) ?>/admin/artists.php">Manage artists</a></div>
    </div>
  <?php elseif (!$groups): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="12" cy="12" r="4"/></svg></span>
      <b>No albums found</b>
      <p>Try another singer or album name.</p>
    </div>
  <?php endif; ?>
</div>

<?php foreach ($groups as $g): ?>
<?php
    $s   = $g['s'];
    $als = $g['albums'];
    $nm  = (string) $s['n'];
    $aid = (int) $s['id'];
    $im  = (string) $s['im'];
?>
<div class="card" style="margin-top:14px">
  <div class="card-h">
    <div class="cell-media">
      <?php if ($im): ?>
        <img class="thumb" src="<?= af_src($im) ?>" alt="" loading="lazy">
      <?php else: ?>
        <span class="ph"><?= e(strtoupper(mb_substr($nm, 0, 1))) ?></span>
      <?php endif; ?>
      <span><b><?= e($nm) ?></b><small><?= count($als) ?> album<?= count($als) === 1 ? '' : 's' ?></small></span>
    </div>
    <span class="spacer"></span>
    <a class="btn xs" href="?new=1&artist=<?= $aid ?>">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add album
    </a>
  </div>

  <?php if (!$als): ?>
    <p class="hint">No albums yet &mdash; this singer shows as an empty group on the Albums page.</p>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Album</th><th>Year</th><th>Genre</th><th>Tracks</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($als as $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
          <td>
            <div class="cell-media">
              <?php if ($r['cover']): ?>
                <img class="thumb" src="<?= af_src($r['cover']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <span class="ph"><?= e(strtoupper(mb_substr($r['title'], 0, 1))) ?></span>
              <?php endif; ?>
              <span><b><?= e($r['title']) ?></b><small><?= e($r['description'] ?: 'Album') ?></small></span>
            </div>
          </td>
          <td class="num"><?= $r['year'] ? e($r['year']) : '—' ?></td>
          <td><?= $r['genre'] ? '<span class="tag">' . e($r['genre']) . '</span>' : '—' ?></td>
          <td class="num"><?= (int) $r['tracks'] ?></td>
          <td>
            <div class="row-actions">
              <a class="btn xs ghost" href="?edit=<?= (int) $r['id'] ?>">Edit</a>
              <button class="btn xs red" type="button" data-del="album" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['title']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- ===================================================== ADD / EDIT MODAL = -->
<div class="modal<?= ($edit || $openNew) ? ' on' : '' ?>" id="albumModal">
  <div class="sheet">
    <div class="modal-h">
      <h3><?= $edit ? 'Edit album' : ($lockAid ? 'Add album for ' . e($lockAnm) : 'Add album') ?></h3>
      <span class="spacer"></span>
      <button class="iconbtn" type="button" onclick="AFAdmin.modal('albumModal', false)" aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="post" action="<?= e($base) ?>/admin/api.php" enctype="multipart/form-data" data-ajax>
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="album.save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="form-err" hidden></div>

      <div class="fgroup">
        <div class="fgroup-t">Basic info</div>
        <div class="form-grid">
          <div class="field"><label>Album title <i class="req">*</i></label>
            <input name="title" required value="<?= e($edit['title'] ?? '') ?>" placeholder="In the Lonely Hour"></div>
          <div class="field"><label>Singer <i class="req">*</i></label>
            <?php if ($lockAid): ?>
              <select disabled><option selected><?= e($lockAnm) ?></option></select>
              <input type="hidden" name="artist_id" value="<?= $lockAid ?>">
              <small class="hint">Singer locked from the Albums page group.</small>
            <?php else: ?>
              <select name="artist_id">
                <option value="">Select singer</option>
                <?php if ($keepA): ?><option value="0" data-keep selected><?= e($curAnm) ?> (keep)</option><?php endif; ?>
                <?php foreach ($artistOpts as $a): ?>
                  <option value="<?= (int) $a['id'] ?>"<?= !$keepA && $curAid === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($keepA): ?>
                <input type="hidden" name="artist" value="<?= e($curAnm) ?>">
                <small class="hint">Not on the Albums page &mdash; tick &ldquo;Show on Albums page&rdquo; on this artist to move them here.</small>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div class="field"><label>Year</label>
            <input type="number" name="year" min="1900" max="2100" value="<?= e($edit['year'] ?? '') ?>" placeholder="2014"></div>
          <div class="field"><label>Genre</label>
            <select name="genre">
              <option value="">—</option>
              <?php if ($keepG): ?><option value="<?= e($curG) ?>" selected><?= e($curG) ?> (keep)</option><?php endif; ?>
              <?php foreach ($genres as $g): ?>
                <option value="<?= e($g['name']) ?>"<?= !$keepG && $curG === $g['name'] ? ' selected' : '' ?>><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
      </div>

      <div class="fgroup">
        <div class="fgroup-t">Media</div>
        <div class="form-grid">
          <div class="field"><label>Cover art</label>
            <div class="media">
              <?php if (!empty($edit['cover'])): ?>
                <img class="media-prev" src="<?= af_src($edit['cover']) ?>" alt="Current cover">
              <?php else: ?>
                <img class="media-prev" alt="" hidden>
              <?php endif; ?>
              <label class="drop" data-drop>
                <input type="file" name="cover_file" accept="image/*">
                <span class="drop-btn"><?= $edit ? 'Change' : 'Choose cover' ?></span>
                <small class="drop-name">JPG, PNG or WebP</small>
              </label>
            </div>
          </div>
        </div>
      </div>

      <details class="fmore">
        <summary>More details (optional)</summary>
        <div class="form-grid">
          <div class="field span2"><label>Description</label>
            <textarea name="description" placeholder="Optional blurb about the album"><?= e($edit['description'] ?? '') ?></textarea></div>
        </div>
      </details>

      <?php if ($edit): ?>
      <div class="mgr">
        <div class="field">
          <label>Songs in this album &mdash; <?= count($albumSongs) ?></label>
          <?php if (!$albumSongs): ?>
            <div class="hint">No songs yet. Open a track in Music / Songs and pick this album, or add one below.</div>
          <?php else: ?>
            <ul class="songlist">
              <?php foreach ($albumSongs as $s): ?>
                <li>
                  <span class="dot"></span>
                  <span class="meta"><b><?= e($s['title']) ?></b><small><?= e($s['artist'] ?: '—') ?><?= $s['duration'] ? ' · ' . floor($s['duration'] / 60) . ':' . str_pad($s['duration'] % 60, 2, '0', STR_PAD_LEFT) : '' ?></small></span>
                  <span class="spacer"></span>
                  <a class="btn xs ghost" href="<?= e($base) ?>/admin/<?= $s['section'] === 'video' ? 'videos' : 'songs' ?>.php?edit=<?= (int) $s['id'] ?>">Open</a>
                  <button class="btn xs ghost" type="button" data-detach="<?= (int) $s['id'] ?>">Remove</button>
                  <button class="btn xs red" type="button" data-del="song" data-id="<?= (int) $s['id'] ?>" data-name="<?= e($s['title']) ?>">Delete</button>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <div class="attach-row">
            <select id="attachSong">
              <option value="">Add an existing song...</option>
              <?php foreach ($attachOpts as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= e($s['title'] . ' — ' . ($s['artist'] ?: '—')) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn sm" type="button" id="attachBtn">Add</button>
            <button class="btn sm ghost" type="button" id="shuffleBtn">Shuffle random songs</button>
          </div>

          <div class="newsong">
            <div class="field"><label>New song title <i class="req">*</i></label>
              <input id="nsTitle" placeholder="Stay With Me"></div>
            <div class="field"><label>Audio file <i class="req">*</i></label>
              <input type="file" id="nsFile" accept="audio/*"></div>
            <button class="btn" type="button" id="nsAdd">Add song</button>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="form-actions">
        <a class="btn ghost" href="<?= e($base) ?>/<?= e($self) ?>">Cancel</a>
        <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add album' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3 id="delTitle">Delete this album?</h3></div>
    <p class="muted" id="delName">Songs keep their own album name - only this row is removed.</p>
    <div class="form-actions">
      <button class="btn ghost" type="button" id="delCancel">Cancel</button>
      <span class="fa-r">
        <button class="btn ghost" type="button" id="delKeepSongs" hidden>Keep songs</button>
        <button class="btn red" type="button" id="delYes">Delete songs</button>
      </span>
    </div>
  </div>
</div>

<?php
$adminScript = <<<JS
(function(){
  var base = '{$base}';
  var editId = '{$editId}';
  var albumArtistId = {$albumAid};
  var albumArtistNm = {$albumAnmJs};

  var gs = document.getElementById('globalSearch');
  if (gs) {
    var f = document.querySelector('form.filters input[name="q"]');
    if (f) { gs.value = f.value; gs.addEventListener('keydown', function(e){
      if (e.key === 'Enter') { f.value = gs.value; f.form.submit(); }
    }); }
  }

  /* AJAX save: shared AFAdmin.post -> always JSON; success -> toast + reload,
     failure -> toast + inline error */
  document.querySelectorAll('form[data-ajax]').forEach(function(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var errBox = form.querySelector('.form-err');
      if (errBox) { errBox.hidden = true; errBox.textContent = ''; }
      var btn = form.querySelector('button[type="submit"]');
      AFAdmin.post(new FormData(form), btn).then(function(j){
        if (j.ok) {
          AFAdmin.toast(j.message || 'Saved.', true);
          if (j.id) { try { sessionStorage.setItem('af_flash_row', location.pathname.split('/').pop() + '|' + j.id); } catch(e){} }
          setTimeout(function(){ location.reload(); }, 450);
        } else {
          var msg = j.error || 'Save failed.';
          AFAdmin.toast(msg, false);
          if (errBox) { errBox.textContent = msg; errBox.hidden = false; form.scrollTop = 0; }
        }
      });
    });
  });

  /* cover picker: preview instantly */
  document.querySelectorAll('[data-drop] input[type=file]').forEach(function(inp){
    inp.addEventListener('change', function(){
      var file = inp.files && inp.files[0];
      if (!file) return;
      var box = inp.closest('[data-drop]');
      var nm = box.querySelector('.drop-name');
      if (nm) nm.textContent = file.name;
      var field = inp.closest('.field');
      if (field && file.type.indexOf('image/') === 0) {
        var img = field.querySelector('.media-prev');
        if (img) { img.src = URL.createObjectURL(file); img.hidden = false; }
      }
    });
    var box = inp.closest('[data-drop]');
    ['dragover','dragenter'].forEach(function(ev){ box.addEventListener(ev, function(e){ e.preventDefault(); box.classList.add('over'); }); });
    ['dragleave','drop'].forEach(function(ev){ box.addEventListener(ev, function(e){ e.preventDefault(); box.classList.remove('over'); }); });
    box.addEventListener('drop', function(e){
      if (e.dataTransfer.files && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; inp.dispatchEvent(new Event('change')); }
    });
  });

  /* delete flow: albums ask "keep songs" vs "delete songs"; songs just go */
  var pending = null;
  var delTitle = document.getElementById('delTitle');
  var delName  = document.getElementById('delName');
  var delKeep  = document.getElementById('delKeepSongs');
  var delYes   = document.getElementById('delYes');
  function sendDelete(mode){
    if (!pending) return;
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', pending.kind + '.delete');
    d.append('id', pending.id);
    if (mode) d.append('mode', mode);
    AFAdmin.post(d).then(function(j){
      if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
      else AFAdmin.toast(j.error || 'Delete failed', false);
    });
  }
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del, name: b.dataset.name || ''};
      var isAlbum = pending.kind === 'album';
      delTitle.textContent = isAlbum ? 'Delete this album?' : 'Delete this song?';
      delKeep.hidden = !isAlbum;
      delYes.textContent = isAlbum ? 'Delete songs' : 'Yes, delete';
      delName.textContent = isAlbum
        ? 'The album "' + pending.name + '" will be removed. Keep its songs, or delete them too.'
        : '"' + pending.name + '" will be removed from the website.';
      AFAdmin.modal('delModal', true);
    });
  });
  document.getElementById('delCancel').addEventListener('click', function(){ AFAdmin.modal('delModal', false); });
  delKeep.addEventListener('click', function(){ sendDelete('keep'); });
  delYes.addEventListener('click', function(){ sendDelete(pending && pending.kind === 'album' ? 'delete' : null); });

  /* inner songs manager: attach / detach without leaving the album form */
  function albumSong(action, songId){
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', action);
    d.append('song_id', songId);
    d.append('album_id', editId);
    AFAdmin.post(d).then(function(j){
      if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
      else AFAdmin.toast(j.error || 'Failed', false);
    });
  }
  document.querySelectorAll('[data-detach]').forEach(function(b){
    b.addEventListener('click', function(){ albumSong('song.detach', b.dataset.detach); });
  });
  var attachBtn = document.getElementById('attachBtn');
  if (attachBtn) attachBtn.addEventListener('click', function(){
    var sel = document.getElementById('attachSong');
    if (!sel || !sel.value) { AFAdmin.toast('Pick a song first', false); return; }
    albumSong('song.attach', sel.value);
  });

  /* Shuffle: swap this album's songs for 6 random Songs-library tracks */
  var shuffleBtn = document.getElementById('shuffleBtn');
  if (shuffleBtn) shuffleBtn.addEventListener('click', function(){
    if (!editId) { AFAdmin.toast('Save the album first', false); return; }
    if (!window.confirm("Replace this album's songs with 6 random songs?")) return;
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'song.shuffle');
    d.append('album_id', editId);
    AFAdmin.post(d).then(function(j){
      if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
      else AFAdmin.toast(j.error || 'Shuffle failed', false);
    });
  });

  /* create a new song straight inside this album */
  var nsAdd = document.getElementById('nsAdd');
  if (nsAdd) nsAdd.addEventListener('click', function(){
    var t = document.getElementById('nsTitle');
    var f = document.getElementById('nsFile');
    if (!t || !t.value.trim()) { AFAdmin.toast('Song title is required', false); if (t) t.focus(); return; }
    if (!f || !f.files || !f.files[0]) { AFAdmin.toast('Choose an audio file', false); return; }
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'song.save');
    d.append('section', 'music');
    d.append('title', t.value.trim());
    d.append('album_id', editId);
    if (albumArtistId) d.append('artist_id', String(albumArtistId));
    else if (albumArtistNm) d.append('artist', albumArtistNm);
    d.append('media_file', f.files[0]);
    AFAdmin.post(d).then(function(j){
      if (j.ok) { AFAdmin.toast(j.message || 'Song added.', true); setTimeout(function(){ location.reload(); }, 550); }
      else AFAdmin.toast(j.error || 'Could not add the song', false);
    });
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
