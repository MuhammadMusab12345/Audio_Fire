<?php
/* ==========================================================================
   ADMIN > USERS (admin/users.php)
   Registered website accounts: search, edit, suspend, delete.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$q      = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 15;

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = af_row('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $_GET['edit']]);
}
$openNew = isset($_GET['new']);

$where = ['1=1']; $args = [];
if ($q !== '')    { $where[] = '(name LIKE ? OR username LIKE ? OR email LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($status !== ''){ $where[] = 'status = ?'; $args[] = $status; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM users WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT * FROM users WHERE $w ORDER BY id DESC LIMIT $per OFFSET $off", $args);

/* how many songs each visible user has saved (one grouped query) */
$savedCounts = [];
if ($rows) {
    $ids = array_column($rows, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    foreach (af_all("SELECT user_id, COUNT(*) c FROM saved_songs WHERE user_id IN ($ph) GROUP BY user_id", $ids) as $r) {
        $savedCounts[(int) $r['user_id']] = (int) $r['c'];
    }
}

$stats = [
    'total'     => (int) af_row('SELECT COUNT(*) c FROM users')['c'],
    'active'    => (int) af_row("SELECT COUNT(*) c FROM users WHERE status = 'active'")['c'],
    'suspended' => (int) af_row("SELECT COUNT(*) c FROM users WHERE status = 'suspended'")['c'],
    'week'      => (int) af_row('SELECT COUNT(*) c FROM users WHERE created_at >= ?', [date('Y-m-d', strtotime('-7 days'))])['c'],
];

$adminTitle = 'Users';
$adminCrumb = 'Community / Registered users';
$activeNav  = 'users';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/users.php';

function uqs(array $over = [])
{
    global $q, $status, $page;
    $p = array_filter(['q' => $q, 'status' => $status, 'page' => $page]);
    $p = array_merge($p, $over);
    return $p ? '?' . http_build_query($p) : '';
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="stats">
  <div class="stat" style="--c:#2E86C1">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
    <div class="n"><?= $stats['total'] ?></div><div class="t">Total accounts</div>
  </div>
  <div class="stat" style="--c:#1f9d63">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>
    <div class="n"><?= $stats['active'] ?></div><div class="t">Active</div>
  </div>
  <div class="stat" style="--c:#E74C3C">
    <span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg></span>
    <div class="n"><?= $stats['suspended'] ?></div><div class="t">Suspended</div>
  </div>
  <div class="stat" style="--c:#34495E">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
    <div class="n"><?= $stats['week'] ?></div><div class="t">Joined this week</div>
  </div>
</div>

<div class="card">
  <div class="card-h">
    <h2>All accounts</h2>
    <span class="sub"><?= $total ?> user<?= $total === 1 ? '' : 's' ?></span>
    <span class="spacer"></span>
    <a class="btn" href="<?= e($base) ?>/<?= e($self) ?>?new=1">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add user
    </a>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, username, email...">
    </div>
    <select class="chip" name="status" style="box-shadow:var(--inset-s);padding:9px 14px">
      <option value="">Any status</option>
      <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option>
      <option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>>Suspended</option>
    </select>
    <button class="chip on" type="submit">Filter</button>
    <?php if ($q !== '' || $status !== ''): ?><a class="chip" href="<?= e($base) ?>/<?= e($self) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
      <b>No users found</b>
      <p>Accounts appear here as soon as visitors sign up on the website.</p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>User</th><th>Username</th><th>Contact</th><th>Saved</th><th>Joined</th><th>Status</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="cell-media">
              <span class="ph" style="background:linear-gradient(135deg,#1f9d63,#2E86C1)"><?= e(strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
              <span><b><?= e($r['name']) ?></b><small>#<?= (int) $r['id'] ?><?= $r['address'] ? ' &middot; ' . e($r['address']) : '' ?></small></span>
            </div>
          </td>
          <td class="mono"><?= e($r['username']) ?></td>
          <td><b style="font-size:12.8px"><?= e($r['email']) ?></b><?= $r['phone'] ? '<br><small style="color:var(--mut)">' . e($r['phone']) . '</small>' : '' ?></td>
          <td class="num"><?= (int) ($savedCounts[(int) $r['id']] ?? 0) ?></td>
          <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
          <td>
            <?php if ($r['status'] === 'suspended'): ?><span class="tag red">Suspended</span>
            <?php else: ?><span class="tag green">Active</span><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <button class="btn xs ghost" type="button" data-status="<?= $r['status'] === 'active' ? 'suspended' : 'active' ?>" data-id="<?= (int) $r['id'] ?>">
                <?= $r['status'] === 'active' ? 'Suspend' : 'Activate' ?>
              </button>
              <a class="btn xs ghost" href="?edit=<?= (int) $r['id'] ?>">Edit</a>
              <button class="btn xs red" type="button" data-del="user" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(uqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(uqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(uqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ===================================================== ADD / EDIT MODAL = -->
<div class="modal<?= ($edit || $openNew) ? ' on' : '' ?>" id="userModal">
  <div class="sheet">
    <div class="modal-h">
      <h3><?= $edit ? 'Edit user' : 'Add user' ?></h3>
      <span class="spacer"></span>
      <button class="iconbtn" type="button" onclick="AFAdmin.modal('userModal', false)" aria-label="Close">
        <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="post" action="<?= e($base) ?>/admin/api.php">
      <input type="hidden" name="csrf" value="<?= e(af_csrf()) ?>">
      <input type="hidden" name="action" value="user.save">
      <input type="hidden" name="redirect" value="1">
      <input type="hidden" name="back" value="<?= e($self . ($edit ? '?edit=' . (int) $edit['id'] : '?new=1')) ?>">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

      <div class="grid2">
        <div class="field"><label>Full name *</label>
          <input name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
        <div class="field"><label>Username *</label>
          <input name="username" required value="<?= e($edit['username'] ?? '') ?>" placeholder="john_doe"></div>
        <div class="field"><label>Email *</label>
          <input type="email" name="email" required value="<?= e($edit['email'] ?? '') ?>"></div>
        <div class="field"><label>Phone</label>
          <input name="phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
        <div class="field"><label>Address</label>
          <input name="address" value="<?= e($edit['address'] ?? '') ?>"></div>
        <div class="field"><label>Status</label>
          <select name="status">
            <option value="active"<?= ($edit['status'] ?? 'active') === 'active' ? ' selected' : '' ?>>Active</option>
            <option value="suspended"<?= ($edit['status'] ?? '') === 'suspended' ? ' selected' : '' ?>>Suspended</option>
          </select></div>
        <div class="field"><label><?= $edit ? 'Set a new password (optional)' : 'Password *' ?></label>
          <input type="password" name="password" <?= $edit ? '' : 'required' ?> autocomplete="new-password"
                 placeholder="<?= $edit ? 'Leave blank to keep the current one' : 'At least 6 characters' ?>"></div>
      </div>

      <div class="form-actions">
        <button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Add user' ?></button>
        <a class="btn ghost" href="<?= e($base) ?>/<?= e($self) ?>">Cancel</a>
        <span class="hintbox">Deleting a user also removes everything they had saved.</span>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this account?</h3></div>
    <p class="muted" id="delName">Their saved songs and artists are removed too. This cannot be undone.</p>
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

  /* suspend / activate */
  document.querySelectorAll('[data-status]').forEach(function(b){
    b.addEventListener('click', function(){
      var d = new FormData();
      d.append('csrf', AFAdmin.csrf); d.append('action', 'user.status');
      d.append('id', b.dataset.id); d.append('status', b.dataset.status);
      AFAdmin.post(d).then(function(j){ if (j.ok) location.reload(); else AFAdmin.toast(j.error || 'Failed', false); });
    });
  });

  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        'The account for "' + (b.dataset.name || '') + '" and their saved list will be permanently deleted.';
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
      if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 600); }
      else AFAdmin.toast(j.error || 'Delete failed', false);
    });
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
