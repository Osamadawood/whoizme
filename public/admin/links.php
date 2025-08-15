<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
require __DIR__.'/../../includes/admin_auth.php';
require __DIR__ . '/../../includes/admin_header.php';
ini_set('display_errors',1); error_reporting(E_ALL);

/* ===== Permissions ===== */
if (!admin_can('links.view')) {
  $_SESSION['flash'] = 'You do not have permission to view links.';
  header('Location: /admin/dashboard.php'); exit;
}

/* CSRF */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }
function qp($k,$v){ $qs=$_GET; $qs[$k]=$v; return '?'.http_build_query($qs); }

/* ===== POST: Update link target ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_target') {
  if (!admin_can('links.edit')) { $_SESSION['flash'] = 'No permission.'; header('Location:/admin/links.php'); exit; }
  $id = (int)$_POST['id'];
  $target = trim($_POST['target_url'] ?? '');
  $token = $_POST['csrf'] ?? '';
  if ($id && hash_equals($csrf, $token) && $target !== '') {
    $db->pdo()->prepare("UPDATE short_links SET target_url=? WHERE id=?")->execute([$target, $id]);
    admin_log('link_update_target', $id, ['target'=>$target]);
    $_SESSION['flash'] = 'Target updated.';
  }
  header('Location: /admin/links.php'); exit;
}

/* ===== POST: Update short code ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_code') {
  if (!admin_can('links.edit')) { $_SESSION['flash'] = 'No permission.'; header('Location:/admin/links.php'); exit; }
  $id = (int)$_POST['id'];
  $new = trim($_POST['code'] ?? '');
  $token = $_POST['csrf'] ?? '';
  if ($id && hash_equals($csrf, $token)) {
    if (preg_match('~^[A-Za-z0-9_-]{3,32}$~', $new)) {
      $chk = $db->pdo()->prepare("SELECT id FROM short_links WHERE code=? AND id<>? LIMIT 1");
      $chk->execute([$new, $id]);
      if (!$chk->fetch()) {
        try {
          $db->pdo()->prepare("UPDATE short_links SET code=? WHERE id=?")->execute([$new, $id]);
          admin_log('link_update_code', $id, ['code'=>$new]);
          $_SESSION['flash'] = 'Short code updated.';
          header('Location: /admin/links.php'); exit;
        } catch (Throwable $e) {
          $_SESSION['flash'] = 'That short code is already taken.';
          header('Location: /admin/links.php'); exit;
        }
      } else {
        $_SESSION['flash'] = 'That short code is already taken.';
        header('Location: /admin/links.php'); exit;
      }
    } else {
      $_SESSION['flash'] = 'Short code must be 3–32 chars (letters, numbers, - or _).';
      header('Location: /admin/links.php'); exit;
    }
  }
  header('Location: /admin/links.php'); exit;
}

/* ===== GET: Delete link ===== */
if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id']) && hash_equals($csrf, ($_GET['csrf'] ?? ''))) {
  if (!admin_can('links.delete')) { $_SESSION['flash'] = 'No permission.'; header('Location:/admin/links.php'); exit; }
  $id = (int)$_GET['id'];
  $db->pdo()->prepare("DELETE FROM scans WHERE short_link_id=?")->execute([$id]);
  $db->pdo()->prepare("DELETE FROM short_links WHERE id=?")->execute([$id]);
  admin_log('link_delete', $id);
  $_SESSION['flash'] = 'Link deleted.';
  header('Location: /admin/links.php'); exit;
}

/* ===== Search + Pagination ===== */
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 50;
$off  = ($page - 1) * $per;

$where = '';
$params = [];
if ($q !== '') {
  $where = "WHERE u.email LIKE ? OR u.name LIKE ? OR s.code LIKE ? OR s.target_url LIKE ?";
  $like = "%$q%";
  $params = [$like,$like,$like,$like];
}

/* count */
$stCnt = $db->pdo()->prepare("SELECT COUNT(*) FROM short_links s JOIN users u ON u.id=s.user_id $where");
$stCnt->execute($params);
$total = (int)$stCnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));

/* list */
$sql = "
SELECT s.id, s.code, s.target_url, s.created_at, u.id AS user_id, u.name, u.email,
       (SELECT COUNT(*) FROM scans sc WHERE sc.short_link_id = s.id) AS scans_count
FROM short_links s
JOIN users u ON u.id = s.user_id
$where
ORDER BY s.id DESC
LIMIT $per OFFSET $off";
$st = $db->pdo()->prepare($sql);
$st->execute($params);
$links = $st->fetchAll();
$base = rtrim(($config['base_url'] ?? ''),'/');

/* Flash */
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<h2 class="mb-2">Manage Links</h2>
<p><a href="/admin/dashboard.php" class="text-decoration-none">← Back to Admin</a></p>

<?php if ($flash): ?>
  <div class="alert alert-info"><?= h($flash) ?></div>
<?php endif; ?>

<form method="get" class="row g-2 align-items-center mb-3">
  <div class="col-auto">
    <input name="q" class="form-control" placeholder="Search by user, email, code, or target..." value="<?= h($q) ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
  </div>
</form>

<div class="text-muted mb-2">Total: <?= $total ?> · Page <?= $page ?> / <?= $pages ?></div>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th><th>Code</th><th>Short URL</th><th>Target</th><th>Owner</th><th>Scans</th><th class="text-center" style="width:70px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$links): ?>
        <tr><td colspan="7" class="text-center">No links found.</td></tr>
      <?php else: foreach ($links as $l): ?>
        <tr>
          <td><?= (int)$l['id'] ?></td>
          <td><code><?= h($l['code']) ?></code></td>
          <td><a target="_blank" href="<?= h($base.'/r/'.$l['code']) ?>"><?= h($base.'/r/'.$l['code']) ?></a></td>
          <td style="max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <a target="_blank" href="<?= h($l['target_url']) ?>"><?= h($l['target_url']) ?></a>
          </td>
          <td><?= h($l['name']) ?><br><small class="text-muted"><?= h($l['email']) ?></small></td>
          <td><?= (int)$l['scans_count'] ?></td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php if (admin_can('links.edit')): ?>
                <li>
                  <button class="dropdown-item btn-edit-target"
                          data-id="<?= (int)$l['id'] ?>"
                          data-code="<?= h($l['code']) ?>"
                          data-target="<?= h($l['target_url']) ?>"
                          data-bs-toggle="modal" data-bs-target="#modalTarget">
                    <i class="bi bi-pencil-square me-1"></i> Edit target
                  </button>
                </li>
                <li>
                  <button class="dropdown-item btn-edit-code"
                          data-id="<?= (int)$l['id'] ?>"
                          data-code="<?= h($l['code']) ?>"
                          data-bs-toggle="modal" data-bs-target="#modalCode">
                    <i class="bi bi-hash me-1"></i> Edit code
                  </button>
                </li>
                <?php endif; ?>
                <?php if (admin_can('links.delete')): ?>
                <li>
                  <a class="dropdown-item text-danger"
                     href="/admin/links.php?action=delete&id=<?= (int)$l['id'] ?>&csrf=<?= h($csrf) ?>"
                     onclick="return confirm('Delete this link and its scans?')">
                    <i class="bi bi-trash me-1"></i> Delete
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
  <nav class="mt-2">
    <ul class="pagination">
      <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= qp('page',1) ?>">&laquo; First</a>
      </li>
      <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= qp('page', max(1,$page-1)) ?>">&lsaquo; Prev</a>
      </li>
      <?php $start = max(1, $page-2); $end = min($pages, $page+2); for ($i=$start; $i<=$end; $i++): ?>
        <li class="page-item <?= $i===$page?'active':'' ?>">
          <a class="page-link" href="<?= qp('page',$i) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
        <a class="page-link" href="<?= qp('page', min($pages,$page+1)) ?>">Next &rsaquo;</a>
      </li>
      <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
        <a class="page-link" href="<?= qp('page',$pages) ?>">Last &raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<!-- Modal: Edit target -->
<div class="modal fade" id="modalTarget" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/admin/links.php">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="update_target">
      <input type="hidden" name="id" id="linkIdTarget">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Short Link Target</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 text-muted" id="labelTarget"></div>
        <div class="mb-3">
          <label class="form-label">Target URL</label>
          <input type="url" name="target_url" id="targetUrl" required class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit code -->
<div class="modal fade" id="modalCode" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/admin/links.php" id="codeForm">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="update_code">
      <input type="hidden" name="id" id="linkIdCode">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-hash me-2"></i>Edit Short Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 text-muted" id="labelCode"></div>
        <div class="mb-3">
          <label class="form-label">Short code</label>
          <input type="text" name="code" id="codeInput" required pattern="[A-Za-z0-9_-]{3,32}" class="form-control">
          <div class="form-text">Allowed: 3–32 chars, letters, numbers, “-” or “_”. Example: <code>osama_2025</code></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Fill Edit Target modal
  document.querySelectorAll('.btn-edit-target').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('linkIdTarget').value = btn.dataset.id;
      document.getElementById('targetUrl').value    = btn.dataset.target || '';
      document.getElementById('labelTarget').textContent = 'Code: ' + (btn.dataset.code || '');
    });
  });

  // Fill Edit Code modal
  document.querySelectorAll('.btn-edit-code').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('linkIdCode').value = btn.dataset.id;
      document.getElementById('codeInput').value  = btn.dataset.code || '';
      document.getElementById('labelCode').textContent = 'Current code: ' + (btn.dataset.code || '');
    });
  });
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>