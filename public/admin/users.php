<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
require __DIR__ . '/../../includes/admin_auth.php';

// ---- safety bootstrap (avoid blank page if $db or includes not wired) ----
try {
  if (!isset($db) || !is_object($db)) {
    // Load config / db only if not already loaded
    $config = require_once __DIR__ . '/../../app/config.php';
    require_once __DIR__ . '/../../app/database.php';
    if (!isset($db) && class_exists('Database')) {
      $db = new Database($config['db']);
    }
  }
} catch (Throwable $e) {
  // stash error to show a friendly message later
  $page_error = $e->getMessage();
}
// -----------------------------------------------------------------------

// CSRF
if (empty($_SESSION['csrf_admin'])) {
  $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_admin'];

// صلاحيات أساسية
$can_manage = in_array($_SESSION['admin_role'] ?? 'super', ['editor','manager','super']);

// فلاش ميسج
$flash = function($name) {
  if (!empty($_SESSION[$name])) { $m = $_SESSION[$name]; unset($_SESSION[$name]); return $m; }
  return null;
};

// أكشنات POST (تفعيل/إيقاف/حذف/انتحال/إنشاء/جماعية)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $_SESSION['admin_error'] = 'Invalid CSRF token.';
    unset($_SESSION['admin_ok']);
    header('Location: /admin/users.php'); exit;
  }

  // Create user (from modal)
  if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $statusStr = (string)($_POST['status'] ?? 'active');
    $active   = $statusStr === 'inactive' ? 0 : 1;

    if ($name === '' || $email === '' || strlen($password) < 6) {
      $_SESSION['admin_error'] = 'Please fill name, valid email, and password (min 6 chars).';
      unset($_SESSION['admin_ok']);
      header('Location: /admin/users.php'); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION['admin_error'] = 'Invalid email address.';
      unset($_SESSION['admin_ok']);
      header('Location: /admin/users.php'); exit;
    }

    // unique email
    $chk = $db->pdo()->prepare('SELECT COUNT(*) FROM users WHERE email=?');
    $chk->execute([$email]);
    if ($chk->fetchColumn() > 0) {
      $_SESSION['admin_error'] = 'Email already exists.';
      unset($_SESSION['admin_ok']);
      header('Location: /admin/users.php'); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $db->pdo()->prepare('INSERT INTO users (name,email,password_hash,is_active,created_at) VALUES (?,?,?,?,NOW())');
    $ins->execute([$name, $email, $hash, $active]);
    $newId = (int)$db->pdo()->lastInsertId();

    // optional: force password change flag if column exists
    if (!empty($_POST['force_reset'])) {
      try {
        $db->pdo()->prepare('UPDATE users SET password_must_change=1 WHERE id=?')->execute([$newId]);
      } catch (Throwable $e1) {
        try { $db->pdo()->prepare('UPDATE users SET must_change_password=1 WHERE id=?')->execute([$newId]); } catch (Throwable $e2) {}
      }
    }

    // log
    try {
      $lg = $db->pdo()->prepare('INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)');
      $lg->execute([$_SESSION['admin_id'], 'user_create', $newId]);
    } catch (Throwable $e) {}

    $_SESSION['admin_ok'] = 'User created successfully.';
    unset($_SESSION['admin_error']);
    header('Location: /admin/users.php'); exit;
  }

  // Bulk actions (activate / deactivate)
  if (isset($_POST['action']) && $_POST['action'] === 'bulk') {
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (!$ids) { $_SESSION['admin_error'] = 'No users selected.'; unset($_SESSION['admin_ok']); header('Location: /admin/users.php'); exit; }
    $op = $_POST['bulk_op'] ?? '';
    if (!in_array($op, ['activate','deactivate'], true)) { $_SESSION['admin_error'] = 'Invalid bulk action.'; unset($_SESSION['admin_ok']); header('Location: /admin/users.php'); exit; }

    if ($op === 'activate') {
      $in = implode(',', array_fill(0, count($ids), '?'));
      $st = $db->pdo()->prepare("UPDATE users SET is_active=1, inactive_reason=NULL WHERE id IN ($in)");
      $st->execute($ids);
      try { $lg=$db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)"); foreach ($ids as $uid) { $lg->execute([$_SESSION['admin_id'], 'bulk_activate', $uid]); } } catch (Throwable $e) {}
      $_SESSION['admin_ok'] = 'Selected users activated.';
      unset($_SESSION['admin_error']);
    } else { // deactivate
      $reason = (string)($_POST['bulk_reason'] ?? null);
      $in = implode(',', array_fill(0, count($ids), '?'));
      $st = $db->pdo()->prepare("UPDATE users SET is_active=0, inactive_reason=? WHERE id IN ($in)");
      $st->execute(array_merge([$reason], $ids));
      try { $lg=$db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES (?,?,?,?)"); foreach ($ids as $uid) { $lg->execute([$_SESSION['admin_id'], 'bulk_deactivate', $uid, $reason]); } } catch (Throwable $e) {}
      $_SESSION['admin_ok'] = 'Selected users deactivated.';
      unset($_SESSION['admin_error']);
    }
    header('Location: /admin/users.php'); exit;
  }

  $id = (int)($_POST['id'] ?? 0);

  // جلب المستخدم للتأكد (للأكشنات اللي تحتاجه)
  if (isset($_POST['action']) && in_array($_POST['action'], ['toggle','delete','impersonate'], true)) {
    if ($id <= 0) {
      $_SESSION['admin_error'] = 'Invalid user id.';
      unset($_SESSION['admin_ok']);
      header('Location: /admin/users.php'); exit;
    }
    $stmt = $db->pdo()->prepare("SELECT id,is_active FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
      $_SESSION['admin_error'] = 'User not found.';
      unset($_SESSION['admin_ok']);
      header('Location: /admin/users.php'); exit;
    }
  }

  // Toggle active
  if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $newActive = (int)!$user['is_active'];
    $reason = $newActive ? null : (string)($_POST['reason'] ?? null);

    $up = $db->pdo()->prepare("UPDATE users SET is_active=?, inactive_reason=? WHERE id=?");
    $up->execute([$newActive, $reason, $id]);

    // log
    try {
      $lg = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES (?,?,?,?)");
      $lg->execute([$_SESSION['admin_id'], $newActive ? 'user_activate' : 'user_deactivate', $id, $reason]);
    } catch (Throwable $e) {}

    $_SESSION['admin_ok'] = $newActive ? 'User activated.' : 'User deactivated.';
    unset($_SESSION['admin_error']);
    header('Location: /admin/users.php'); exit;
  }

  // Delete
  if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del = $db->pdo()->prepare("DELETE FROM users WHERE id=?");
    $del->execute([$id]);

    try {
      $lg = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)");
      $lg->execute([$_SESSION['admin_id'], 'user_delete', $id]);
    } catch (Throwable $e) {}

    $_SESSION['admin_ok'] = 'User deleted.';
    unset($_SESSION['admin_error']);
    header('Location: /admin/users.php'); exit;
  }

  // Impersonate (ابدأ جلسة باسم المستخدم)
  if (isset($_POST['action']) && $_POST['action'] === 'impersonate') {
    $uStmt = $db->pdo()->prepare("SELECT id,name,email,is_active FROM users WHERE id=?");
    $uStmt->execute([$id]);
    $uRow = $uStmt->fetch();
    if (!$uRow) { $_SESSION['admin_error']='User not found.'; unset($_SESSION['admin_ok']); header('Location: /admin/users.php'); exit; }
    if ((int)$uRow['is_active'] !== 1) { $_SESSION['admin_error']='Cannot impersonate an inactive user.'; unset($_SESSION['admin_ok']); header('Location: /admin/users.php'); exit; }

    // Log start
    try { $lg = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)"); $lg->execute([$_SESSION['admin_id'], 'impersonate_start', $id]); } catch (Throwable $e) {}

    // Reset any existing user session and seed required keys used by user auth
    unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);
    $_SESSION['user_id'] = (int)$uRow['id'];
    $_SESSION['user_email'] = (string)$uRow['email'];
    $_SESSION['user_name'] = (string)$uRow['name'];

    // Keep admin markers to allow returning back
    $_SESSION['impersonate_user_id'] = (int)$uRow['id'];
    $_SESSION['impersonate_admin_id'] = (int)$_SESSION['admin_id'];
    $_SESSION['impersonating'] = true;

    $_SESSION['admin_ok'] = 'Now impersonating the user.';
    unset($_SESSION['admin_error']);
    header('Location: /dashboard.php');
    exit;
  }
}

// بحث وفلترة
$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all')); // all, active, inactive

$where = [];
$params = [];

if ($q !== '') {
  $where[] = "(name LIKE ? OR email LIKE ?)";
  $params[] = "%{$q}%";
  $params[] = "%{$q}%";
}
if ($status === 'active') {
  $where[] = "is_active=1";
} elseif ($status === 'inactive') {
  $where[] = "is_active=0";
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="users_export.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Name','Email','Active','Inactive Reason','Joined']);
  $csvStmt = $db->pdo()->prepare("SELECT id,name,email,is_active,inactive_reason,created_at FROM users $sqlWhere ORDER BY id DESC");
  $csvStmt->execute($params);
  while ($r = $csvStmt->fetch()) {
    fputcsv($out, [
      (int)$r['id'],
      $r['name'],
      $r['email'],
      $r['is_active'] ? '1' : '0',
      (string)$r['inactive_reason'],
      $r['created_at']
    ]);
  }
  fclose($out);
  exit;
}

// Count total
$countStmt = $db->pdo()->prepare("SELECT COUNT(*) FROM users $sqlWhere");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Pagination
$pageSize = 20;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $pageSize;

// List query with LIMIT/OFFSET
$rows = [];
try {
  $stmt = $db->pdo()->prepare(
    "SELECT id,name,email,is_active,inactive_reason,created_at
     FROM users
     $sqlWhere
     ORDER BY id DESC
     LIMIT :limit OFFSET :offset"
  );
  // bind search params first
  foreach ($params as $k => $v) {
    $stmt->bindValue($k + 1, $v, PDO::PARAM_STR);
  }
  $stmt->bindValue(':limit', (int)$pageSize, PDO::PARAM_INT);
  $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $page_error = $e->getMessage();
}
?>
<?php require __DIR__ . '/../../includes/admin_header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="mb-0">Users</h2>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">+ Create user</button>
</div>

<?php if (!empty($page_error)): ?>
  <div class="alert alert-danger">Error: <?= htmlspecialchars($page_error) ?></div>
<?php endif; ?>

<?php if ($m = $flash('admin_ok')): ?>
  <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
<?php endif; ?>
<?php if ($m = $flash('admin_error')): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($m) ?></div>
<?php endif; ?>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title">Create user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password (min 6)</label>
          <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="force_reset" name="force_reset" value="1">
          <label class="form-check-label" for="force_reset">Require password change on first login</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
<!-- /Create User Modal -->

<!-- Edit User Modal (iframe) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="height:70vh">
        <iframe id="editUserFrame" src="about:blank" style="border:0;width:100%;height:100%"></iframe>
      </div>
    </div>
  </div>
</div>
<!-- /Edit User Modal (iframe) -->

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-12 col-md-4">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Search name or email">
      </div>
      <div class="col-12 col-md-3">
        <select name="status" class="form-select">
          <option value="all" <?= $status==='all'?'selected':'' ?>>All</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <button class="btn btn-outline-secondary w-100">Search</button>
      </div>
    </form>
    <div class="mt-2 d-flex justify-content-between align-items-center">
      <div class="text-muted small">Showing page <?= (int)$page ?> of <?= max(1, (int)ceil($total / $pageSize)) ?> (<?= (int)$total ?> users)</div>
      <a class="btn btn-sm btn-outline-secondary" href="?<?= http_build_query(array_filter(['q'=>$q ?: null,'status'=>$status !== 'all' ? $status : null,'export'=>'csv'])) ?>">
        Export CSV
      </a>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <div id="usersTableWrap">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:44px"><input class="form-check-input" type="checkbox" id="checkAll"></th>
              <th style="width:80px">ID</th>
              <th>Name</th>
              <th>Email</th>
              <th style="width:120px">Status</th>
              <th style="width:220px">Joined</th>
              <th style="width:90px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="7" class="text-center py-4">No users found.</td></tr>
            <?php else: foreach ($rows as $u): ?>
              <tr>
                <td><input class="form-check-input row-check" type="checkbox" form="bulkForm" name="ids[]" value="<?= (int)$u['id'] ?>"></td>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <?php if ($u['is_active']): ?>
                    <span class="badge text-bg-success">Active</span>
                  <?php else: ?>
                    <?php $reasonTip = trim((string)($u['inactive_reason'] ?? '')); ?>
                    <span class="badge text-bg-secondary" <?= $reasonTip !== '' ? 'data-bs-toggle="tooltip" title="'.htmlspecialchars($reasonTip).'"' : '' ?>>Inactive</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <!-- Edit opens modal -->
                      <li><button type="button" class="dropdown-item open-edit" data-url="/admin/user_edit.php?id=<?= (int)$u['id'] ?>&modal=1">Edit</button></li>
                      <li><button type="button" class="dropdown-item copy-email" data-email="<?= htmlspecialchars($u['email']) ?>">Copy email</button></li>
                      <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewModal<?= (int)$u['id'] ?>">Quick view</button></li>
                      <?php if ($can_manage): ?>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ((int)$u['is_active'] === 1): ?>
                          <li>
                            <form method="post" class="px-3 py-1">
                              <input type="hidden" name="csrf" value="<?= $csrf ?>">
                              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                              <input type="hidden" name="action" value="impersonate">
                              <button class="btn btn-link p-0">Impersonate</button>
                            </form>
                          </li>
                        <?php else: ?>
                          <li><span class="dropdown-item disabled" title="User is inactive">Impersonate</span></li>
                        <?php endif; ?>
                        <li>
                          <button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#toggleModal<?= (int)$u['id'] ?>">
                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                          </button>
                        </li>
                        <li>
                          <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.');" class="px-3 py-1">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-link p-0 text-danger">Delete</button>
                          </form>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </div>

                  <!-- Modal Toggle (Deactivate/Activate) -->
                  <div class="modal fade" id="toggleModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <form method="post" class="modal-content">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="toggle">
                        <div class="modal-header">
                          <h5 class="modal-title"><?= $u['is_active'] ? 'Deactivate user' : 'Activate user' ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php if ($u['is_active']): ?>
                            <p class="mb-2">Add a reason (optional):</p>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Reason (optional)"></textarea>
                          <?php else: ?>
                            <p class="mb-0">User will be activated and allowed to login again.</p>
                          <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                  <!-- /Modal -->

                  <!-- Quick View Modal -->
                  <div class="modal fade" id="viewModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">User #<?= (int)$u['id'] ?> — <?= htmlspecialchars($u['name']) ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <dl class="row mb-0">
                            <dt class="col-4">Email</dt><dd class="col-8"><?= htmlspecialchars($u['email']) ?></dd>
                            <dt class="col-4">Status</dt><dd class="col-8"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></dd>
                            <?php if (!$u['is_active'] && !empty($u['inactive_reason'])): ?>
                              <dt class="col-4">Reason</dt><dd class="col-8"><?= nl2br(htmlspecialchars($u['inactive_reason'])) ?></dd>
                            <?php endif; ?>
                            <dt class="col-4">Joined</dt><dd class="col-8"><?= htmlspecialchars($u['created_at']) ?></dd>
                          </dl>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- /Quick View Modal -->
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <!-- Bulk controls form -->
      <form method="post" id="bulkForm" class="p-2 border-top d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="bulk">
        <div class="d-flex gap-2 align-items-center">
          <select name="bulk_op" id="bulk_op" class="form-select form-select-sm" style="width:180px">
            <option value="">Bulk action...</option>
            <option value="activate">Activate</option>
            <option value="deactivate">Deactivate</option>
          </select>
          <input type="text" name="bulk_reason" id="bulk_reason" class="form-control form-control-sm" style="width:260px; display:none" placeholder="Reason (optional)">
          <button type="submit" class="btn btn-sm btn-primary" id="bulkApply" disabled>Apply</button>
        </div>
        <div class="text-muted small">Selected: <span id="selCount">0</span></div>
      </form>
    </div>
  </div>
  <div class="card-footer text-muted small">
    Total users: <?= (int)$total ?>
  </div>
</div>

<?php
  $pages = max(1, (int)ceil($total / $pageSize));
  if ($pages > 1):
?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php
      $mk = function($p) use ($q,$status) {
        $query = [];
        if ($q !== '') $query['q'] = $q;
        if ($status !== 'all') $query['status'] = $status;
        $query['p'] = $p;
        return '?' . http_build_query($query);
      };
    ?>
    <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $mk(max(1,$page-1)) ?>">Prev</a></li>
    <?php for ($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= $mk($i) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $page>=$pages?'disabled':'' ?>"><a class="page-link" href="<?= $mk(min($pages,$page+1)) ?>">Next</a></li>
  </ul>
</nav>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    // Enable Bootstrap tooltips (inactive reason)
    var t = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    t.forEach(function(el){ new bootstrap.Tooltip(el); });

    // Select all / selection counter
    const checkAll = document.getElementById('checkAll');
    const checks = Array.from(document.querySelectorAll('.row-check'));
    const selCount = document.getElementById('selCount');
    const bulkApply = document.getElementById('bulkApply');
    const bulkOp = document.getElementById('bulk_op');
    const bulkReason = document.getElementById('bulk_reason');

    function updateSel(){
      const n = checks.filter(c=>c.checked).length;
      selCount.textContent = n;
      bulkApply.disabled = n === 0 || !bulkOp.value;
      bulkReason.style.display = (bulkOp.value === 'deactivate') ? 'inline-block' : 'none';
    }
    if (checkAll) {
      checkAll.addEventListener('change', function(){
        checks.forEach(c=>{ c.checked = checkAll.checked; });
        updateSel();
      });
    }
    checks.forEach(c=> c.addEventListener('change', updateSel));
    if (bulkOp) bulkOp.addEventListener('change', updateSel);
    updateSel();

    // Copy email
    document.querySelectorAll('.copy-email').forEach(function(btn){
      btn.addEventListener('click', async function(){
        try {
          await navigator.clipboard.writeText(this.dataset.email);
          btn.textContent = 'Copied!';
          setTimeout(()=>{ btn.textContent = 'Copy email'; }, 1000);
        } catch(e) { alert('Copy failed'); }
      });
    });

    // Edit user in modal (iframe)
    const editModalEl = document.getElementById('editUserModal');
    if (editModalEl) {
      const editModal = new bootstrap.Modal(editModalEl);
      const editFrame = document.getElementById('editUserFrame');
      document.querySelectorAll('.open-edit').forEach(function(btn){
        btn.addEventListener('click', function(){
          const url = this.dataset.url || '';
          if (url) {
            try {
              const u = new URL(url, window.location.origin);
              u.searchParams.set('modal', '1'); // force modal UI inside the iframe
              editFrame.src = u.toString();
            } catch (e) {
              // Fallback in case URL parsing fails, append manually if not present
              editFrame.src = url + (url.includes('?') ? '&' : '?') + 'modal=1';
            }
            editModal.show();
          }
        });
      });
      // Reload list after closing modal to reflect changes
      editModalEl.addEventListener('hidden.bs.modal', function(){
        window.location.reload();
      });
      // Listen to postMessage events coming from the iframe (user_edit.php)
      window.addEventListener('message', function(ev) {
        if (!ev || !ev.data || typeof ev.data !== 'object') return;
        // Close the modal only
        if (ev.data.type === 'whoiz-close' && editModalEl) {
          const m = bootstrap.Modal.getInstance(editModalEl);
          if (m) m.hide();
        }
        // Close + refresh table after save
        if (ev.data.type === 'whoiz-refresh') {
          if (editModalEl) {
            const m = bootstrap.Modal.getInstance(editModalEl);
            if (m) m.hide();
          }
          window.location.reload();
        }
      }, false);
    }
  });
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>