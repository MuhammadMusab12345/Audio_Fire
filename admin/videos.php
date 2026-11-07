<?php
/* ==========================================================================
   ADMIN > SONGS (admin/videos.php)   [was: Videos]
   Video songs that drive the public Songs library (public/songs.php).
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();
require_once __DIR__ . '/../includes/singers-data.php';

$section = 'video';
$q       = trim((string) ($_GET['q'] ?? ''));
$genre   = trim((string) ($_GET['genre'] ?? ''));
$region  = trim((string) ($_GET['region'] ?? ''));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$per     = 15;

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = af_row("SELECT * FROM songs WHERE id = ? AND section = 'video' LIMIT 1", [(int) $_GET['edit']]);
}
$openNew = isset($_GET['new']);

$where = ["section = 'video'"]; $args = [];
if ($q !== '')    { $where[] = '(title LIKE ? OR artist LIKE ? OR album LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($genre !== '') { $where[] = 'genre = ?'; $args[] = $genre; }
if ($region !== ''){ $where[] = 'region = ?'; $args[] = $region; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM songs WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT * FROM songs WHERE $w ORDER BY id DESC LIMIT $per OFFSET $off", $args);
$genres  = af_all("SELECT name FROM categories WHERE kind='genre' ORDER BY sort_order, name");
$regions = af_all("SELECT name FROM categories WHERE kind='region' ORDER BY sort_order, name");
$langs   = af_all("SELECT name FROM categories WHERE kind='language' ORDER BY sort_order, name");
$artistOpts = af_all('SELECT id, name FROM artists ORDER BY name');
/* Album dropdown: only the chosen singer's albums, and only singers that are
   on the Albums page (af_album_singers) get the dropdown at all - the field
   is hidden for everyone else and the song just saves without an album. */
$albumSingers   = af_album_singers();
$albumSingerIds = [];
foreach ($albumSingers as $as) { $albumSingerIds[] = (int) $as['id']; }
$albumOpts = [];

/* current values missing from their table get a "(keep)" option so an edit never wipes them */
$curAid = (int) ($edit['artist_id'] ?? 0);
$curAnm = (string) ($edit['artist'] ?? '');
$aidOk = false;
foreach ($artistOpts as $ao) if ((int) $ao['id'] === $curAid) { $aidOk = true; break; }
$keepA = ($curAnm !== '' && !$aidOk);
if ($curAid && in_array($curAid, $albumSingerIds, true)) {
    $albumOpts = af_all('SELECT id, title FROM albums WHERE artist_id = ? OR artist = ? ORDER BY title ASC, id ASC', [$curAid, $curAnm]);
}
$curAlid = (int) ($edit['album_id'] ?? 0);
$curAlnm = (string) ($edit['album'] ?? '');
$alidOk = false;
foreach ($albumOpts as $ao) if ((int) $ao['id'] === $curAlid) { $alidOk = true; break; }
$keepAl = ($curAlnm !== '' && !$alidOk);
$curG = (string) ($edit['genre'] ?? '');
$keepG = ($curG !== '' && !in_array($curG, array_column($genres, 'name'), true));
$curL = (string) ($edit['language'] ?? '');
$keepL = ($curL !== '' && !in_array($curL, array_column($langs, 'name'), true));
$curR = (string) ($edit['region'] ?? '');
$keepR = ($curR !== '' && !in_array($curR, array_column($regions, 'name'), true));

$adminTitle  = 'Songs';
$adminCrumb  = 'Library / Songs';
$activeNav   = 'videos';
$adminActions = '<a class="btn" href="' . e(af_admin_base()) . '/admin/videos.php?new=1">'
    . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add song</a>';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/videos.php';

function vqs(array $over = [])
{
    global $q, $genre, $region, $page;
    $p = array_filter(['q' => $q, 'genre' => $genre, 'region' => $region, 'page' => $page]);
    $p = array_merge($p, $over);
    return $p ? '?' . http_build_query($p) : '';
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="card">
  <div class="card-h">
    <h2>All songs</h2>
    <span class="sub"><?= $total ?> song<?= $total === 1 ? '' : 's' ?> in the public library</span>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, artist, album...">
    </div>
    <select class="chip" name="genre" style="box-shadow:var(--inset-s);padding:9px 14px">
      <option value="">All genres</option>
      <?php foreach ($genres as $g): ?>
        <option value="<?= e($g['name']) ?>"<?= $genre === $g['name'] ? ' selected' : '' ?>><?= e($g['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="chip" name="region" style="box-shadow:var(--inset-s);padding:9px 14px">
      <option value="">All regions</option>
      <?php foreach ($regions as $g): ?>
        <option value="<?= e($g['name']) ?>"<?= $region === $g['name'] ? ' selected' : '' ?>><?= e($g['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== '' || $genre !== '' || $region !== ''): ?>
      <a class="chip" href="<?= e($base) ?>/<?= e($self) ?>">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="m10 9 5 3-5 3z"/></svg></span>
      <b>No songs found</b>
      <p><?= ($q !== '' || $genre !== '' || $region !== '') ? 'Nothing matches that filter.' : 'Add your first song to populate the public Songs library.' ?></p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Song</th><th>Album</th><th>Year</th><th>Genre</th><th>Region</th><th>Status</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
          <td>
            <div class="cell-media">
              <?php if ($r['thumb']): ?>
                <img class="thumb" src="<?= af_src($r['thumb']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <span class="ph"><?= e(strtoupper(mb_substr($r['title'], 0, 1))) ?></span>
              <?php endif; ?>
              <span><b><?= e($r['title']) ?></b><small><?= e($r['artist']) ?></small></span>
            </div>
          </td>
          <td><?= e($r['album'] ?: '—') ?></td>
          <td class="num"><?= e($r['year'] ?: '—') ?></td>
          <td><?= $r['genre'] ? '<span class="tag">' . e($r['genre']) . '</span>' : '—' ?></td>
          <td><?= $r['region'] ? '<span class="tag slate">' . e($r['region']) . '</span>' : '—' ?></td>
          <td>
            <?php if ($r['is_new']): ?><span class="tag red">New</span>
            <?php else: ?><span class="tag green">Live</span><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <button class="btn xs ghost" type="button" data-toggle="song" data-id="<?= (int) $r['id'] ?>" title="Toggle the New badge">New</button>
              <a class="btn xs ghost" href="?edit=<?= (int) $r['id'] ?>">Edit</a>
              <button class="btn xs red" type="button" data-del="song" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['title']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(vqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(vqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(vqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ===================================================== ADD / EDIT MODAL = -->
<div class="modal<?= ($edit || $openNew) ? ' on' : '' ?>" id="songModal">
  <div class="sheet">
    <div class="modal-h">
      <h3><?= $edit ? 'Edit song' : 'Add song' ?></h3>
      <span class="spacer"></span>
      <button class="iconbtn" type="button" onclick="AFAdmin.modal('songModal', false)" aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="post" action="<?= e($base) ?>/admin/api.php" enctype="multipart/form-data" data-ajax>
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="song.save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <input type="hidden" name="form" value="songs">

      <div class="form-err" hidden></div>

      <div class="fgroup">
        <div class="fgroup-t">Basic info</div>
        <div class="form-grid">
          <div class="field"><label>Title <i class="req">*</i></label>
            <input name="title" required value="<?= e($edit['title'] ?? '') ?>" placeholder="Midnight Drive"></div>
          <div class="field"><label>Singer <i class="req">*</i></label>
            <select name="artist_id" required>
              <option value="">Select singer</option>
              <?php if ($keepA): ?><option value="0" data-keep selected><?= e($curAnm) ?> (keep)</option><?php endif; ?>
              <?php foreach ($artistOpts as $a): ?>
                <option value="<?= (int) $a['id'] ?>"<?= !$keepA && $curAid === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($keepA): ?><input type="hidden" name="artist" value="<?= e($curAnm) ?>"><?php endif; ?>
          </div>
          <div class="field" id="albumField"<?= ($curAid && in_array($curAid, $albumSingerIds, true)) ? '' : ' style="display:none"' ?>><label>Album</label>
            <select name="album_id">
              <option value="">No album</option>
              <?php if ($keepAl): ?><option value="0" data-keep selected><?= e($curAlnm) ?> (keep)</option><?php endif; ?>
              <?php foreach ($albumOpts as $o): ?>
                <option value="<?= (int) $o['id'] ?>"<?= !$keepAl && $curAlid === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['title']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($keepAl): ?><input type="hidden" name="album" value="<?= e($curAlnm) ?>"><?php endif; ?>
          </div>
          <div class="field"><label>Genre</label>
            <select name="genre">
              <option value="">—</option>
              <?php if ($keepG): ?><option value="<?= e($curG) ?>" selected><?= e($curG) ?> (keep)</option><?php endif; ?>
              <?php foreach ($genres as $g): ?>
                <option value="<?= e($g['name']) ?>"<?= !$keepG && $curG === $g['name'] ? ' selected' : '' ?>><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Language</label>
            <select name="language">
              <option value="">—</option>
              <?php if ($keepL): ?><option value="<?= e($curL) ?>" selected><?= e($curL) ?> (keep)</option><?php endif; ?>
              <?php foreach ($langs as $l): ?>
                <option value="<?= e($l['name']) ?>"<?= !$keepL && $curL === $l['name'] ? ' selected' : '' ?>><?= e($l['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Region</label>
            <select name="region">
              <option value="">—</option>
              <?php if ($keepR): ?><option value="<?= e($curR) ?>" selected><?= e($curR) ?> (keep)</option><?php endif; ?>
              <?php foreach ($regions as $r): ?>
                <option value="<?= e($r['name']) ?>"<?= !$keepR && $curR === $r['name'] ? ' selected' : '' ?>><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
      </div>

      <div class="fgroup">
        <div class="fgroup-t">Media</div>
        <div class="form-grid">
          <div class="field"><label>Video file (MP4) <?= $edit ? '' : '<i class="req">*</i>' ?></label>
            <div class="media">
              <?php if (!empty($edit['media'])): ?>
                <div class="media-cur">
                  <video controls src="<?= af_src($edit['media']) ?>"></video>
                  <small class="fname"><?= e(basename((string) $edit['media'])) ?></small>
                </div>
              <?php endif; ?>
              <label class="drop" data-drop>
                <input type="file" name="media_file" accept="video/*">
                <span class="drop-btn"><?= $edit ? 'Change' : 'Choose video' ?></span>
                <small class="drop-name">MP4, WebM or MOV</small>
              </label>
            </div>
          </div>
          <div class="field"><label>Thumbnail</label>
            <div class="media">
              <?php if (!empty($edit['thumb'])): ?>
                <img class="media-prev" src="<?= af_src($edit['thumb']) ?>" alt="Current thumbnail">
              <?php else: ?>
                <img class="media-prev" alt="" hidden>
              <?php endif; ?>
              <label class="drop" data-drop>
                <input type="file" name="thumb_file" accept="image/*">
                <span class="drop-btn"><?= $edit ? 'Change' : 'Choose image' ?></span>
                <small class="drop-name">JPG, PNG or WebP</small>
              </label>
            </div>
          </div>
        </div>
      </div>

      <details class="fmore">
        <summary>More details (optional)</summary>
        <div class="form-grid">
          <div class="field"><label>Year</label>
            <input type="number" name="year" min="1900" max="2100" value="<?= e($edit['year'] ?? '') ?>" placeholder="2025"></div>
          <div class="field"><label>Duration (seconds)</label>
            <input type="number" name="duration" min="0" max="10000" value="<?= e($edit['duration'] ?? '') ?>" placeholder="214"></div>
          <div class="field"><label>Accent colour</label>
            <div class="color-ctl">
              <input type="color" name="color" value="<?= e($edit['color'] ?? '#5091b2') ?>">
              <span class="hex"><?= e($edit['color'] ?? '#5091b2') ?></span>
            </div></div>
          <div class="field check">
            <input type="checkbox" id="is_new" name="is_new" value="1"<?= !empty($edit['is_new']) ? ' checked' : '' ?>>
            <label for="is_new">Featured &mdash; show the NEW badge</label>
          </div>
          <div class="field span2"><label>Description</label>
            <textarea name="description" placeholder="Optional one-liner shown with the song"><?= e($edit['description'] ?? '') ?></textarea></div>
        </div>
      </details>

      <p class="hintbox">Songs saved here appear immediately in the public Songs library.</p>

      <div class="form-actions">
        <a class="btn ghost" href="<?= e($base) ?>/<?= e($self) ?>">Cancel</a>
        <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add song' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this song?</h3></div>
    <p class="muted" id="delName">This removes it from the public Songs library.</p>
    <div class="form-actions">
      <button class="btn ghost" type="button" onclick="AFAdmin.modal('delModal', false)">Cancel</button>
      <button class="btn red" type="button" id="delYes">Yes, delete</button>
    </div>
  </div>
</div>

<?php
$albumIdsJs = json_encode($albumSingerIds);
$adminScript = <<<JS
(function(){
  var self = '{$self}', base = '{$base}';

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

  document.querySelectorAll('.color-ctl input[type=color]').forEach(function(ci){
    var hex = ci.parentNode.querySelector('.hex');
    ci.addEventListener('input', function(){ if (hex) hex.textContent = ci.value; });
  });

  /* album dropdown: only Albums-page singers get one, only their own albums */
  var artSel = document.querySelector('select[name="artist_id"]');
  var albSel = document.querySelector('select[name="album_id"]');
  var albumField = document.getElementById('albumField');
  var albumSingerIds = {$albumIdsJs};
  function syncAlbums(isChange){
    if (!artSel || !albSel || !albumField) return;
    var aid = parseInt(artSel.value, 10) || 0;
    var on = aid > 0 && albumSingerIds.indexOf(aid) !== -1;
    albumField.style.display = on ? '' : 'none';
    if (!on) {
      if (isChange) albSel.value = '';   /* singer switched away: saves without album */
      return;
    }
    fetch(base + '/admin/api.php?action=albums.for_artist&artist_id=' + encodeURIComponent(artSel.value), {credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(j){
        var keep = albSel.querySelector('option[data-keep]');
        var cur  = albSel.value;
        albSel.innerHTML = '';
        var o0 = document.createElement('option'); o0.value = ''; o0.textContent = 'No album';
        albSel.appendChild(o0);
        if (keep) albSel.appendChild(keep);
        (j.albums || []).forEach(function(a){
          var o = document.createElement('option');
          o.value = String(a.id); o.textContent = a.title;
          if (o.value === cur) o.selected = true;
          albSel.appendChild(o);
        });
      })
      .catch(function(){});
  }
  if (artSel && albSel) {
    artSel.addEventListener('change', function(){ syncAlbums(true); });
    syncAlbums(false);
  }

  /* file pickers: preview a chosen image / video straight away */
  document.querySelectorAll('[data-drop] input[type=file]').forEach(function(inp){
    inp.addEventListener('change', function(){
      var file = inp.files && inp.files[0];
      if (!file) return;
      var box = inp.closest('[data-drop]');
      var nm = box.querySelector('.drop-name');
      if (nm) nm.textContent = file.name;
      var field = inp.closest('.field');
      if (!field) return;
      if (file.type.indexOf('image/') === 0) {
        var img = field.querySelector('.media-prev');
        if (img) { img.src = URL.createObjectURL(file); img.hidden = false; }
      } else if (file.type.indexOf('video/') === 0 || file.type.indexOf('audio/') === 0) {
        var tag = file.type.indexOf('video/') === 0 ? 'video' : 'audio';
        var cur = field.querySelector('.media-cur');
        if (!cur) {
          cur = document.createElement('div');
          cur.className = 'media-cur';
          cur.innerHTML = (tag === 'video' ? '<video controls></video>' : '<audio controls></audio>') + '<small class="fname"></small>';
          var row = field.querySelector('.media');
          if (row) row.insertBefore(cur, box);
        }
        var player = cur.querySelector(tag);
        if (player) player.src = URL.createObjectURL(file);
        var fn = cur.querySelector('.fname');
        if (fn) fn.textContent = file.name;
      }
    });
    var box = inp.closest('[data-drop]');
    ['dragover','dragenter'].forEach(function(ev){ box.addEventListener(ev, function(e){ e.preventDefault(); box.classList.add('over'); }); });
    ['dragleave','drop'].forEach(function(ev){ box.addEventListener(ev, function(e){ e.preventDefault(); box.classList.remove('over'); }); });
    box.addEventListener('drop', function(e){
      if (e.dataTransfer.files && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; inp.dispatchEvent(new Event('change')); }
    });
  });

  document.querySelectorAll('[data-toggle]').forEach(function(b){
    b.addEventListener('click', function(){
      var d = new FormData();
      d.append('csrf', AFAdmin.csrf); d.append('action', 'song.toggle');
      d.append('id', b.dataset.id); d.append('col', 'is_new');
      AFAdmin.post(d).then(function(j){ if (j.ok) location.reload(); else AFAdmin.toast(j.error || 'Failed', false); });
    });
  });

  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        '"' + (b.dataset.name || 'This item') + '" will be removed from the public Songs library.';
      AFAdmin.modal('delModal', true);
    });
  });
  document.getElementById('delYes').addEventListener('click', function(){
    if (!pending) return;
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', pending.kind + '.delete');
    d.append('id', pending.id);
    AFAdmin.post(d).then(function(j){
      if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
      else AFAdmin.toast(j.error || 'Delete failed', false);
    });
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
