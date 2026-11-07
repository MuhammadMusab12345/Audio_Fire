<?php
/* ==========================================================================
   ADMIN > CATEGORIES (admin/categories.php)
   Genre / language / region filters used across the public site.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$kind = (string) ($_GET['kind'] ?? 'genre');
if (!in_array($kind, ['genre', 'language', 'region'], true)) $kind = 'genre';

$KINDS = [
    'genre'    => ['Genres',    'Filter chips on Music / Songs pages'],
    'language' => ['Languages', 'Language filter + song field'],
    'region'   => ['Regions',   'Regional filter on the Songs library'],
];

$rows = af_all('SELECT * FROM categories WHERE kind = ? ORDER BY sort_order ASC, id ASC', [$kind]);
$counts = [];
foreach (af_all('SELECT kind, COUNT(*) c FROM categories GROUP BY kind') as $r) $counts[$r['kind']] = (int) $r['c'];

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = af_row('SELECT * FROM categories WHERE id = ? LIMIT 1', [(int) $_GET['edit']]);
    if ($edit) $kind = $edit['kind'];
}
$openNew = isset($_GET['new']);

$adminTitle = 'Categories';
$adminCrumb = 'Content / ' . $KINDS[$kind][0];
$activeNav  = 'categories';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/categories.php';
?>
<style>.content{display:grid;gap:18px}</style>

<div class="card">
  <div class="card-h">
    <h2><?= e($KINDS[$kind][0]) ?></h2>
    <span class="sub"><?= e($KINDS[$kind][1]) ?> &middot; <?= count($rows) ?> entr<?= count($rows) === 1 ? 'y' : 'ies' ?></span>
    <span class="spacer"></span>
    <a class="btn" href="?<?= e(http_build_query(['kind' => $kind, 'new' => 1])) ?>">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add <?= e(rtrim($kind, 's')) ?>
    </a>
  </div>

  <div class="filters">
    <?php foreach ($KINDS as $key => $meta): ?>
      <a class="chip<?= $kind === $key ? ' on' : '' ?>" href="?kind=<?= e($key) ?>">
        <?= e($meta[0]) ?><?php if (isset($counts[$key])): ?> (<?= $counts[$key] ?>)<?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
      <b>No <?= e($kind) ?> entries yet</b>
      <p>Add one and it becomes a filter option on the public site.</p>
      <div style="margin-top:18px"><a class="btn" href="?<?= e(http_build_query(['kind' => $kind, 'new' => 1])) ?>">Add one</a></div>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Name</th><th>Type</th><th>Sort</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
          <td><b><?= e($r['name']) ?></b></td>
          <td><span class="tag"><?= e($r['kind']) ?></span></td>
          <td class="num"><?= (int) $r['sort_order'] ?></td>
          <td>
            <div class="row-actions">
              <a class="btn xs ghost" href="?<?= e(http_build_query(['kind' => $kind, 'edit' => $r['id']])) ?>">Edit</a>
              <button class="btn xs red" type="button" data-del="category" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ===================================================== ADD / EDIT MODAL = -->
<div class="modal<?= ($edit || $openNew) ? ' on' : '' ?>" id="catModal">
  <div class="sheet narrow">
    <div class="modal-h">
      <h3><?= $edit ? 'Edit entry' : 'Add entry' ?></h3>
      <span class="spacer"></span>
      <button class="iconbtn" type="button" onclick="AFAdmin.modal('catModal', false)" aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="post" action="<?= e($base) ?>/admin/api.php" data-ajax>
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="category.save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="form-err" hidden></div>

      <div class="fgroup">
        <div class="fgroup-t">Basic info</div>
        <div class="form-grid">
          <div class="field"><label>Type</label>
            <select name="kind">
              <?php foreach ($KINDS as $key => $meta): ?>
                <option value="<?= e($key) ?>"<?= ($edit['kind'] ?? $kind) === $key ? ' selected' : '' ?>><?= e($meta[0]) ?></option>
              <?php endforeach; ?>
            </select></div>

          <div class="field"><label>Name <i class="req">*</i></label>
            <input name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="Pop"></div>
        </div>
      </div>

      <p class="hintbox">Renaming a category only changes the filter label - songs keep the genre / language stored on them.</p>

      <div class="form-actions">
        <a class="btn ghost" href="?kind=<?= e($kind) ?>">Cancel</a>
        <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add entry' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this entry?</h3></div>
    <p class="muted" id="delName">The filter option disappears from the public site.</p>
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
  var pending = null;

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

  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        '"' + (b.dataset.name || '') + '" will be removed from the filters.';
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
