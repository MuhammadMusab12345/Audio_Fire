<?php
/* ==========================================================================
   ADMIN > MESSAGES (admin/messages.php)
   Contact-form inbox.
   ========================================================================== */
require_once __DIR__ . '/includes/boot.php';
af_admin_required();

$q     = trim((string) ($_GET['q'] ?? ''));
$box   = (string) ($_GET['box'] ?? '');     // '' | unread | read
$page  = max(1, (int) ($_GET['page'] ?? 1));
$per   = 15;

$where = ['1=1']; $args = [];
if ($q !== '')      { $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($box === 'unread'){ $where[] = 'is_read = 0'; }
if ($box === 'read')  { $where[] = 'is_read = 1'; }
$w = implode(' AND ', $where);

$total = (int) af_row("SELECT COUNT(*) c FROM contact_messages WHERE $w", $args)['c'];
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$rows = af_all("SELECT * FROM contact_messages WHERE $w ORDER BY is_read ASC, id DESC LIMIT $per OFFSET $off", $args);

$stats = [
    'total'  => (int) af_row('SELECT COUNT(*) c FROM contact_messages')['c'],
    'unread' => (int) af_row('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')['c'],
    'read'   => (int) af_row('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 1')['c'],
    'week'   => (int) af_row('SELECT COUNT(*) c FROM contact_messages WHERE created_at >= ?', [date('Y-m-d', strtotime('-7 days'))])['c'],
];

$adminTitle = 'Messages';
$adminCrumb = 'Community / Contact inbox';
$activeNav  = 'messages';
require __DIR__ . '/includes/header.php';
$base = af_admin_base();
$self = 'admin/messages.php';

function mqs(array $over = [])
{
    global $q, $box, $page;
    $p = array_filter(['q' => $q, 'box' => $box, 'page' => $page]);
    $p = array_merge($p, $over);
    return $p ? '?' . http_build_query($p) : '';
}
?>
<style>.content{display:grid;gap:18px}</style>

<div class="stats">
  <div class="stat" style="--c:#d1477f">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 8 9 6 9-6"/></svg></span>
    <div class="n"><?= $stats['total'] ?></div><div class="t">All messages</div>
  </div>
  <div class="stat" style="--c:#E74C3C">
    <span class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg></span>
    <div class="n"><?= $stats['unread'] ?></div><div class="t">Unread</div>
  </div>
  <div class="stat" style="--c:#1f9d63">
    <span class="ic"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>
    <div class="n"><?= $stats['read'] ?></div><div class="t">Read</div>
  </div>
  <div class="stat" style="--c:#34495E">
    <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
    <div class="n"><?= $stats['week'] ?></div><div class="t">This week</div>
  </div>
</div>

<div class="card">
  <div class="card-h">
    <h2>Inbox</h2>
    <span class="sub"><?= $total ?> message<?= $total === 1 ? '' : 's' ?></span>
    <span class="spacer"></span>
    <?php if ($stats['unread'] > 0): ?>
      <button class="btn sm ghost" type="button" id="readAll">Mark all as read</button>
    <?php endif; ?>
  </div>

  <form method="get" action="<?= e($base) ?>/<?= e($self) ?>" class="filters">
    <div class="search" style="box-shadow:var(--inset-s)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search sender or message...">
    </div>
    <a class="chip<?= $box === '' ? ' on' : '' ?>" href="?">All</a>
    <a class="chip<?= $box === 'unread' ? ' on' : '' ?>" href="?box=unread">Unread</a>
    <a class="chip<?= $box === 'read' ? ' on' : '' ?>" href="?box=read">Read</a>
  </form>

  <?php if (!$rows): ?>
    <div class="empty">
      <span class="ic"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 8 9 6 9-6"/></svg></span>
      <b>No messages</b>
      <p>Contact-form submissions from the website appear here.</p>
    </div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th><th style="text-align:right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="cell-media">
              <span class="ph" style="background:linear-gradient(135deg,#d1477f,#2E86C1)"><?= e(strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
              <span><b><?= e($r['name']) ?></b><small><?= e($r['email']) ?></small></span>
            </div>
          </td>
          <td><?= e($r['subject'] ?: '—') ?></td>
          <td style="max-width:360px"><?= e(mb_strimwidth($r['message'], 0, 160, '...')) ?></td>
          <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
          <td>
            <?php if ($r['is_read']): ?><span class="tag green">Read</span>
            <?php else: ?><span class="tag red">Unread</span><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <a class="btn xs ghost" href="mailto:<?= e($r['email']) ?>?subject=Re: <?= e(rawurlencode($r['subject'] ?: 'Your message to Audio Fire')) ?>">Reply</a>
              <?php if (!$r['is_read']): ?>
                <button class="btn xs ghost" type="button" data-read="<?= (int) $r['id'] ?>">Mark read</button>
              <?php endif; ?>
              <button class="btn xs red" type="button" data-del="message" data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>">Delete</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a href="<?= e(mqs(['page' => $page - 1])) ?>">Prev</a><?php endif; ?>
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e(mqs(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= e(mqs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ============================================================ DELETE ==== -->
<div class="modal" id="delModal">
  <div class="sheet narrow">
    <div class="modal-h"><h3>Delete this message?</h3></div>
    <p class="muted" id="delName">The message is removed from the inbox.</p>
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

  function call(payload){
    var d = new FormData();
    Object.keys(payload).forEach(function(k){ d.append(k, payload[k]); });
    d.append('csrf', AFAdmin.csrf);
    return fetch(base + '/admin/api.php', {method:'POST', body:d, credentials:'same-origin'})
      .then(function(r){ return r.json(); });
  }

  document.querySelectorAll('[data-read]').forEach(function(b){
    b.addEventListener('click', function(){
      call({action:'message.read', id: b.dataset.read})
        .then(function(j){ if (j.ok) location.reload(); else AFAdmin.toast(j.error || 'Failed', false); });
    });
  });

  var all = document.getElementById('readAll');
  if (all) all.addEventListener('click', function(){
    var ids = [].slice.call(document.querySelectorAll('[data-read]')).map(function(b){ return b.dataset.read; });
    Promise.all(ids.map(function(id){ return call({action:'message.read', id:id}); }))
      .then(function(){ AFAdmin.toast('All messages marked as read', true); setTimeout(function(){ location.reload(); }, 600); });
  });

  var pending = null;
  document.querySelectorAll('[data-del]').forEach(function(b){
    b.addEventListener('click', function(){
      pending = {id: b.dataset.id, kind: b.dataset.del};
      document.getElementById('delName').textContent =
        'The message from "' + (b.dataset.name || '') + '" will be permanently deleted.';
      AFAdmin.modal('delModal', true);
    });
  });
  document.getElementById('delYes').addEventListener('click', function(){
    if (!pending) return;
    call({action: pending.kind + '.delete', id: pending.id})
      .then(function(j){
        if (j.ok) { AFAdmin.toast(j.message, true); setTimeout(function(){ location.reload(); }, 550); }
        else AFAdmin.toast(j.error || 'Delete failed', false);
      });
  });
})();
JS;
require __DIR__ . '/includes/footer.php';
