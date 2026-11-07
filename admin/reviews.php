<?php
/* ==========================================================================
   ADMIN > REVIEWS (admin/reviews.php)
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$q     = trim((string) ($_GET['q'] ?? ''));
$minR  = (int) ($_GET['rating'] ?? 0);
$page  = max(1, (int) ($_GET['page'] ?? 1));
$per   = 15;

$where = ['1=1']; $args = [];
if ($q !== '')   { $where[] = '(name LIKE ? OR comment LIKE ? OR song_id LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($minR > 0)   { $where[] = 'rating >= ?'; $args[] = $minR; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM reviews WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT * FROM reviews WHERE $w ORDER BY id DESC LIMIT $per OFFSET $off", $args);

$stats = [
    'total'   => (int) af_row('SELECT COUNT(*) c FROM reviews')['c'],
    'avg'     => (float) (af_row('SELECT AVG(rating) a FROM reviews')['a'] ?? 0),
    'five'    => (int) af_row('SELECT COUNT(*) c FROM reviews WHERE rating = 5')['c'],
    'week'    => (int) af_row('SELECT COUNT(*) c FROM reviews WHERE created_at >= ?', [date('Y-m-d', strtotime('-7 days'))])['c'],
];

$adminTitle = 'Reviews';
$adminCrumb = 'Community / Reviews';
$activeNav  = 'reviews';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/reviews.php';

function rqs(array $over = [])
{
    global $q, $minR, $page;
    $p = array_filter(['q' => $q, 'rating' => $minR, 'page' => $page]);
    $p = array_merge($p, $over);
    return $p ? '?' . http_build_query($p) : '';
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="stats">
  <div class="stat" style="--c:#c07a12">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z"/></svg></span>
    <div class="n"><?= $stats['total'] ?></div><div class="t">Total reviews</div>
  </div>
  <div class="stat" style="--c:#E74C3C">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
    <div class="n"><?= number_format($stats['avg'], 1) ?></div><div class="t">Average rating</div>
  </div>
  <div class="stat" style="--c:#2E86C1">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M12 3 14.7 8.5l6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9z"/></svg></span>
    <div class="n"><?= $stats['five'] ?></div><div class="t">Five-star reviews</div>
  </div>
  <div class="stat" style="--c:#34495E">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
    <div class="n"><?= $stats['week'] ?></div><div class="t">New this week</div>
  </div>
</div>

<div class="card">
  <div class="card-h">
    <h2>All reviews</h2>
    <span class="sub"><?= $total ?> review<?= $total === 1 ? '' : 's' ?></span>
    <span class="spacer"></span>
    <span class="hintbox">Reviews come from the public Reviews page.</span>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or comment...">
    </div>
    <select class="chip" name="rating" style="box-shadow:var(--inset-s);padding:9px 14px">
      <option value="0">Any rating</option>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <option value="<?= $i ?>"<?= $minR === $i ? ' selected' : '' ?>><?= $i ?>+ stars</option>
      <?php endfor; ?>
    </select>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== '' || $minR > 0): ?><a class="chip" href="<?= e($base) ?>/<?= e($self) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z"/></svg></span>
      <b>No reviews found</b>
      <p>Visitor ratings land here as they are submitted.</p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Reviewer</th><th>Rating</th><th>Comment</th><th>Song</th><th>Date</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="cell-media">
              <span class="ph" style="background:linear-gradient(135deg,#c07a12,#E74C3C)"><?= e(strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
              <span><b><?= e($r['name']) ?></b><small><?= e($r['role'] ?: 'Listener') ?></small></span>
            </div>
          </td>
          <td><span class="tag amber"><?= (int) $r['rating'] ?> / 5</span></td>
          <td style="max-width:340px"><?= e(mb_strimwidth($r['comment'], 0, 150, '...')) ?></td>
          <td class="mono"><?= e($r['song_id'] ?: '—') ?></td>
          <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
          <td>
            <div class="row-actions">
              <button class="btn xs ghost" type="button" data-approve="<?= (int) ($r['approved'] ?? 1) ?>" data-id="<?= (int) $r['id'] ?>">
                <?= ($r['approved'] ?? 1) ? 'Hide' : 'Show' ?>
              </button>
              <button class="btn xs red" type="button" data-del="review" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(rqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(rqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(rqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this review?</h3></div>
    <p class="muted" id="delName">The rating is removed from the public Reviews page.</p>
    <div class="form-actions">
      <button class="btn red" type="button" id="delYes">Yes, delete</button>
      <button class="btn ghost" type="button" onclick="AFAdmin.modal('delModal', false)">Cancel</button>
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

  document.querySelectorAll('[data-approve]').forEach(function(b){
    b.addEventListener('click', function(){
      var d = new FormData();
      d.append('csrf', AFAdmin.csrf); d.append('action', 'review.approve');
      d.append('id', b.dataset.id); d.append('approved', b.dataset.approve === '1' ? '0' : '1');
      fetch(base + '/admin/api.php', {method:'POST', body:d, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(j){ if (j.ok) location.reload(); else AFAdmin.toast(j.error || 'Failed', false); });
    });
  });

  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        'The review by "' + (b.dataset.name || '') + '" will be permanently deleted.';
      AFAdmin.modal('delModal', true);
    });
  });
  document.getElementById('delYes').addEventListener('click', function(){
    if (!pending) return;
    var d = new FormData();
    d.append('csrf', AFAdmin.csrf);
    d.append('action', pending.kind + '.delete');
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
