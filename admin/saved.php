<?php
/* ==========================================================================
   ADMIN > SAVED (admin/saved.php)
   Read-only view of what visitors have saved, with the ability to clear rows.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$q    = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$per  = 20;

$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(s.title LIKE ? OR s.artist LIKE ? OR s.item_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM saved_songs s LEFT JOIN users u ON u.id = s.user_id WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT s.*, u.name AS uname, u.email AS uemail
                FROM saved_songs s LEFT JOIN users u ON u.id = s.user_id
                WHERE $w ORDER BY s.id DESC LIMIT $per OFFSET $off", $args);

$stats = [
    'songs'   => (int) af_row("SELECT COUNT(*) c FROM saved_songs WHERE kind = 'song'")['c'],
    'artists' => (int) af_row("SELECT COUNT(*) c FROM saved_artists")['c'],
    'users'   => (int) af_row('SELECT COUNT(DISTINCT user_id) c FROM saved_songs')['c'],
    'week'    => (int) af_row("SELECT COUNT(*) c FROM saved_songs WHERE created_at >= ?", [date('Y-m-d', strtotime('-7 days'))])['c'],
];

$adminTitle = 'Saved Songs';
$adminCrumb = 'Community / What visitors saved';
$activeNav  = 'saved';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/saved.php';

function sqs(array $over = [])
{
    global $q, $page;
    $p = array_filter(['q' => $q, 'page' => $page]);
    $p = array_merge($p, $over);
    return $p ? '?' . http_build_query($p) : '';
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="stats">
  <div class="stat" style="--c:#0e9ba8">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M6 4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21l-6-4-6 4z"/></svg></span>
    <div class="n"><?= $stats['songs'] ?></div><div class="t">Saved songs</div>
  </div>
  <div class="stat" style="--c:#E74C3C">
    <span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg></span>
    <div class="n"><?= $stats['artists'] ?></div><div class="t">Saved artists</div>
  </div>
  <div class="stat" style="--c:#2E86C1">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
    <div class="n"><?= $stats['users'] ?></div><div class="t">Users with saves</div>
  </div>
  <div class="stat" style="--c:#34495E">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>
    <div class="n"><?= $stats['week'] ?></div><div class="t">New this week</div>
  </div>
</div>

<div class="card">
  <div class="card-h">
    <h2>All saved items</h2>
    <span class="sub"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></span>
    <span class="spacer"></span>
    <span class="hintbox">Only signed-in visitors can save - guests are asked to sign in first.</span>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search song, artist or owner...">
    </div>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== ''): ?><a class="chip" href="<?= e($base) ?>/<?= e($self) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M6 4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21l-6-4-6 4z"/></svg></span>
      <b>Nothing saved yet</b>
      <p>Hearts from signed-in visitors land here.</p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Item</th><th>Owner</th><th>Type</th><th>Saved</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="cell-media">
              <?php if ($r['image_path']): ?>
                <img class="thumb" src="<?= af_src($r['image_path']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <span class="ph"><?= e(strtoupper(mb_substr($r['title'] ?: $r['item_id'], 0, 1))) ?></span>
              <?php endif; ?>
              <span><b><?= e($r['title'] ?: $r['item_id']) ?></b><small><?= e($r['artist'] ?: '—') ?></small></span>
            </div>
          </td>
          <td><b style="font-size:13px"><?= e($r['uname'] ?? 'Deleted user') ?></b><br><small style="color:var(--mut)"><?= e($r['uemail'] ?? '—') ?></small></td>
          <td><span class="tag <?= $r['kind'] === 'artist' ? 'amber' : '' ?>"><?= e($r['kind']) ?></span></td>
          <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
          <td>
            <div class="row-actions">
              <button class="btn xs red" type="button" data-del="saved" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['title'] ?: $r['item_id']) ?>">Remove</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(sqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(sqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(sqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Remove this save?</h3></div>
    <p class="muted" id="delName">The item disappears from that user's saved list.</p>
    <div class="form-actions">
      <button class="btn red" type="button" id="delYes">Yes, remove</button>
      <button class="btn ghost" type="button" onclick="AFAdmin.modal('delModal', false)">Cancel</button>
    </div>
  </div>
</div>

<?php
$adminScript = <<<JS
(function(){
  var base = '{$base}';
  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        '"' + (b.dataset.name || 'This item') + '" will be removed from the owner\'s saved list.';
      AFAdmin.modal('delModal', true);
    });
  });
  document.getElementById('delYes').addEventListener('click', function(){
    if (!pending) return;
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', 'saved.delete');
    d.append('id', pending.id);
    fetch(base + '/admin/api.php', {method:'POST', body:d, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
        else AFAdmin.toast(j.error || 'Delete failed', false);
      });
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
