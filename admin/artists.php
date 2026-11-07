<?php
/* ==========================================================================
   ADMIN > ARTISTS (admin/artists.php)
   One table, five sections shown on the public site:
      console  - home fanned card stack
      home     - home "Featured Artists" grid
      singer   - singers page rotating hero
      feature  - singers page "Featured Artists" grid
      lineup   - singers page "The Full Lineup"
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$SEC = [
    'console' => ['Console stack (home)', 'Home page - the fanned card stack'],
    'home'    => ['Home featured grid',   'Home page - "Featured Artists" row'],
    'singer'  => ['Singers hero (12)',    'Singers page - rotating hero'],
    'feature' => ['Singers featured',     'Singers page - "Featured Artists" grid'],
    'lineup'  => ['Full lineup',          'Singers page - "The Full Lineup" grid'],
];

$sec   = (string) ($_GET['sec'] ?? 'lineup');
if (!isset($SEC[$sec])) $sec = 'lineup';
$q     = trim((string) ($_GET['q'] ?? ''));
$page  = max(1, (int) ($_GET['page'] ?? 1));
$per   = 15;

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = af_row('SELECT * FROM artists WHERE id = ? LIMIT 1', [(int) $_GET['edit']]);
    if ($edit) $sec = $edit['section'];
}
$openNew = isset($_GET['new']);

$where = ['section = ?']; $args = [$sec];
if ($q !== '') { $where[] = '(name LIKE ? OR genre LIKE ? OR tagline LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM artists WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT * FROM artists WHERE $w ORDER BY id DESC LIMIT $per OFFSET $off", $args);
$counts = [];
foreach (af_all('SELECT section, COUNT(*) c FROM artists GROUP BY section') as $r) $counts[$r['section']] = (int) $r['c'];

$catGenres = af_all("SELECT name FROM categories WHERE kind='genre' ORDER BY sort_order, name");
$curG = (string) ($edit['genre'] ?? '');
$keepG = ($curG !== '' && !in_array($curG, array_column($catGenres, 'name'), true));

$adminTitle  = 'Artists';
$adminCrumb  = 'Content / ' . $SEC[$sec][0];
$activeNav   = 'artists';
$adminActions = '<a class="btn" href="' . e(af_admin_base()) . '/admin/artists.php?' . e(http_build_query(['sec' => $sec, 'new' => 1])) . '">'
    . '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add artist</a>';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/artists.php';

function aqs(array $over = [])
{
    global $sec, $q, $page;
    $p = array_filter(['sec' => $sec, 'q' => $q, 'page' => $page]);
    $p = array_merge($p, $over);
    return '?' . http_build_query($p);
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="card">
  <div class="card-h">
    <h2><?= e($SEC[$sec][0]) ?></h2>
    <span class="sub"><?= e($SEC[$sec][1]) ?> &middot; <?= $total ?> artist<?= $total === 1 ? '' : 's' ?></span>
  </div>

  <div class="filters">
    <?php foreach ($SEC as $key => $meta): ?>
      <a class="chip<?= $sec === $key ? ' on' : '' ?>" href="?<?= e(http_build_query(['sec' => $key])) ?>">
        <?= e($meta[0]) ?><?php if (isset($counts[$key])): ?> (<?= $counts[$key] ?>)<?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <input type="hidden" name="sec" value="<?= e($sec) ?>">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, genre, tagline...">
    </div>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== ''): ?><a class="chip" href="?<?= e(http_build_query(['sec' => $sec])) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg></span>
      <b>No artists in this section</b>
      <p>Add one and it appears on the public site straight away.</p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Artist</th><th>Section</th><th>Genre</th><th>Debut</th><th>Label</th><th>Status</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
          <td>
            <div class="cell-media">
              <?php if ($r['image']): ?>
                <img class="thumb" src="<?= af_src($r['image']) ?>" alt="" loading="lazy" style="object-fit:cover">
              <?php else: ?>
                <span class="ph" style="background:linear-gradient(135deg,<?= e($r['accent'] ?: '#2E86C1') ?>,#34495E)"><?= e(strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
              <?php endif; ?>
              <span><b><?= e($r['name']) ?></b><small><?= e($r['role'] ?: $r['tagline'] ?: '—') ?></small></span>
            </div>
          </td>
          <td><span class="tag <?= $sec === 'lineup' ? 'slate' : '' ?>"><?= e($SEC[$r['section']][0] ?? $r['section']) ?></span></td>
          <td><?= e($r['genre'] ?: '—') ?></td>
          <td class="num"><?= e($r['debut'] ?: '—') ?></td>
          <td><?= e($r['label'] ?: '—') ?></td>
          <td>
            <?php if ($r['is_featured']): ?><span class="tag amber">Featured</span>
            <?php else: ?><span class="tag green">Live</span><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <a class="btn xs ghost" href="?<?= e(http_build_query(['sec' => $sec, 'edit' => $r['id']])) ?>">Edit</a>
              <button class="btn xs red" type="button" data-del="artist" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(aqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(aqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(aqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ===================================================== ADD / EDIT MODAL = -->
<div class="modal<?= ($edit || $openNew) ? ' on' : '' ?>" id="artistModal">
  <div class="sheet">
    <div class="modal-h">
      <h3><?= $edit ? 'Edit artist' : 'Add artist' ?></h3>
      <span class="spacer"></span>
      <button class="iconbtn" type="button" onclick="AFAdmin.modal('artistModal', false)" aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="post" action="<?= e($base) ?>/admin/api.php" enctype="multipart/form-data" data-ajax>
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="artist.save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="form-err" hidden></div>

      <div class="fgroup">
        <div class="fgroup-t">Basic info</div>
        <div class="form-grid">
          <div class="field"><label>Name <i class="req">*</i></label>
            <input name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="Arman Veer"></div>
          <div class="field"><label>Section</label>
            <select name="section">
              <?php foreach ($SEC as $key => $meta): ?>
                <option value="<?= e($key) ?>"<?= ($edit['section'] ?? $sec) === $key ? ' selected' : '' ?>><?= e($meta[0]) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Role</label>
            <input name="role" value="<?= e($edit['role'] ?? '') ?>" placeholder="Powerhouse Vocalist"></div>
          <div class="field"><label>Genre</label>
            <select name="genre">
              <option value="">—</option>
              <?php if ($keepG): ?><option value="<?= e($curG) ?>" selected><?= e($curG) ?> (keep)</option><?php endif; ?>
              <?php foreach ($catGenres as $g): ?>
                <option value="<?= e($g['name']) ?>"<?= !$keepG && $curG === $g['name'] ? ' selected' : '' ?>><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Gender</label>
            <select name="gender">
              <?php foreach (['male', 'female', 'other'] as $g): ?>
                <option value="<?= $g ?>"<?= ($edit['gender'] ?? 'male') === $g ? ' selected' : '' ?>><?= ucfirst($g) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field check">
            <input type="checkbox" id="in_albums" name="in_albums" value="1"<?= !empty($edit['in_albums']) ? ' checked' : '' ?>>
            <label for="in_albums">Show on Albums page</label>
          </div>
        </div>
      </div>

      <div class="fgroup">
        <div class="fgroup-t">Media</div>
        <div class="form-grid">
          <div class="field"><label>Photo</label>
            <div class="media">
              <?php if (!empty($edit['image'])): ?>
                <img class="media-prev" src="<?= af_src($edit['image']) ?>" alt="Current photo">
              <?php else: ?>
                <img class="media-prev" alt="" hidden>
              <?php endif; ?>
              <label class="drop" data-drop>
                <input type="file" name="image_file" accept="image/*">
                <span class="drop-btn"><?= $edit ? 'Change' : 'Choose photo' ?></span>
                <small class="drop-name">JPG, PNG or WebP</small>
              </label>
            </div>
          </div>
          <div class="field"><label>Accent colour</label>
            <div class="color-ctl">
              <input type="color" name="accent" value="<?= e($edit['accent'] ?? '#2E86C1') ?>">
              <span class="hex"><?= e($edit['accent'] ?? '#2E86C1') ?></span>
            </div></div>
        </div>
      </div>

      <details class="fmore">
        <summary>More details (optional)</summary>
        <div class="form-grid">
          <div class="field"><label>Tagline</label>
            <input name="tagline" value="<?= e($edit['tagline'] ?? '') ?>" placeholder="AYAAN RAZA"></div>
          <div class="field"><label>Born</label>
            <input name="birth" value="<?= e($edit['birth'] ?? '') ?>" placeholder="14 March 1994"></div>
          <div class="field"><label>Birthplace</label>
            <input name="place" value="<?= e($edit['place'] ?? '') ?>" placeholder="Lahore, Pakistan"></div>
          <div class="field"><label>Debut year</label>
            <input type="number" name="debut" min="1900" max="2100" value="<?= e($edit['debut'] ?? '') ?>" placeholder="2014"></div>
          <div class="field"><label>Label</label>
            <input name="label" value="<?= e($edit['label'] ?? '') ?>" placeholder="Skyline Records"></div>
          <div class="field"><label>Listeners</label>
            <input name="listeners" value="<?= e($edit['listeners'] ?? '') ?>" placeholder="24M"></div>
          <div class="field" id="fieldYears"><label>Active years</label>
            <input name="years" value="<?= e($edit['years'] ?? '') ?>" placeholder="2017, 2019, 2023">
            <small id="yearsHint" style="display:none;font-size:12px;color:var(--mut);margin-top:6px">Full lineup: drives the activity chart on the Singers page</small></div>
          <div class="field"><label>Rating (0-5)</label>
            <input type="number" name="rating" min="0" max="5" step="0.1" value="<?= e($edit['rating'] ?? '0') ?>"></div>
          <div class="field span2"><label>Description / bio</label>
            <textarea name="description" placeholder="A short paragraph shown with the artist"><?= e($edit['description'] ?? '') ?></textarea></div>
          <div class="field check">
            <input type="checkbox" id="is_featured" name="is_featured" value="1"<?= !empty($edit['is_featured']) ? ' checked' : '' ?>>
            <label for="is_featured">Featured artist</label>
          </div>
        </div>
      </details>

      <p class="hintbox">Console + home artists show on the homepage; singer / feature / lineup artists show on the Singers page.</p>

      <div class="form-actions">
        <a class="btn ghost" href="<?= e($base) ?>/<?= e($self) ?>?sec=<?= e($sec) ?>">Cancel</a>
        <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add artist' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this artist?</h3></div>
    <p class="muted" id="delName">The artist disappears from the website.</p>
    <div class="form-actions">
      <button class="btn ghost" type="button" onclick="AFAdmin.modal('delModal', false)">Cancel</button>
      <button class="btn red" type="button" id="delYes">Yes, delete</button>
    </div>
  </div>
</div>

<?php
$adminScript = <<<JS
(function(){
  var base = '{$base}';
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

  /* photo picker: preview instantly, keep the drop label in step */
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

  /* Full lineup: lift "Active years" out of the collapsed More details block
     into Basic info (with a hint) while Section = Full lineup is selected */
  var secSel = document.querySelector('form[data-ajax] select[name="section"]');
  var fy = document.getElementById('fieldYears');
  if (secSel && fy) {
    var hint = document.getElementById('yearsHint');
    var homeGrid = fy.parentNode;
    var ph = document.createComment('years-home');
    homeGrid.insertBefore(ph, fy);
    var basicGrid = document.querySelector('.fgroup .form-grid');
    var syncYears = function(){
      var lineup = secSel.value === 'lineup';
      if (hint) hint.style.display = lineup ? 'block' : 'none';
      if (lineup && basicGrid && fy.parentNode !== basicGrid) basicGrid.appendChild(fy);
      else if (!lineup && fy.parentNode !== homeGrid) homeGrid.insertBefore(fy, ph);
    };
    secSel.addEventListener('change', syncYears);
    syncYears();
  }

  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        '"' + (b.dataset.name || 'This artist') + '" will be removed from the website.';
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
