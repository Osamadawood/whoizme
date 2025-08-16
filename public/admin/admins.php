<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
// public/admin/admins.php
require __DIR__ . '/../../includes/admin_auth.php';
require __DIR__ . '/../../includes/admin_header.php';
ini_set('display_errors', 1); error_reporting(E_ALL);

/* صلاحية الصفحة: سوبر فقط */
if (!admin_can('admins.manage')) {
  $_SESSION['flash'] = 'You do not have permission to manage admins.';
  header('Location: /admin/dashboard'); exit;
}

/* CSRF */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

/* Helpers */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES); }
function current_admin_id(): int {
  if (!empty($_SESSION['admin']['id'])) return (int)$_SESSION['admin']['id'];
  if (!empty($_SESSION['admin_id']))     return (int)$_SESSION['admin_id'];
  return 0;
}

/* الأدوار المتاحة */
$ROLES = ['viewer','editor','manager','super'];

/* ====== POST (create / update) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $token  = $_POST['csrf'] ?? '';
  if (!hash_equals($csrf, $token)) { http_response_code(400); exit('Bad CSRF'); }

  if ($action === 'create') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $role  = $_POST['role']       ?? 'viewer';
    if (!in_array($role, $ROLES, true)) $role = 'viewer';

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 6) {
      // فريدية الإيميل
      $st = $db->pdo()->prepare("SELECT id FROM admins WHERE email=? LIMIT 1");
      $st->execute([$email]);
      if ($st->fetch()) {
        $_SESSION['flash'] = 'Email already exists for another admin.';
      } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $db->pdo()->prepare("INSERT INTO admins (name,email,password_hash,is_super,role) VALUES (?,?,?,?,?)")
          ->execute([$name,$email,$hash, ($role === 'super' ? 1 : 0), $role]);
        $_SESSION['flash'] = 'Admin created successfully.';
      }
    } else {
      $_SESSION['flash'] = 'Please provide valid name, email and password (min 6 chars).';
    }
    header('Location: /admin/admins.php'); exit;
  }

  if ($action === 'update') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role']       ?? 'viewer';
    $pass  = $_POST['password']   ?? ''; // اختياري

    if (!in_array($role, $ROLES, true)) $role = 'viewer';

    if ($id > 0) {
      if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // فريدية الإيميل
        $st = $db->pdo()->prepare("SELECT id FROM admins WHERE email=? AND id<>? LIMIT 1");
        $st->execute([$email, $id]);
        if ($st->fetch()) {
          $_SESSION['flash'] = 'Email already in use by another admin.';
        } else {
          $db->pdo()->prepare("UPDATE admins SET name=?, email=?, role=?, is_super=? WHERE id=?")
            ->execute([$name, $email, $role, ($role === 'super' ? 1 : 0), $id]);

          if ($pass !== '') {
            if (strlen($pass) < 6) {
              $_SESSION['flash'] = 'Password must be at least 6 characters.';
            } else {
              $hash = password_hash($pass, PASSWORD_BCRYPT);
              $db->pdo()->prepare("UPDATE admins SET password_hash=? WHERE id=?")->execute([$hash, $id]);
              $_SESSION['flash'] = 'Admin updated (including password).';
            }
          } else {
            $_SESSION['flash'] = 'Admin updated.';
          }
        }
      } else {
        $_SESSION['flash'] = 'Please provide a valid name and email.';
      }
    }
    header('Location: /admin/admins.php'); exit;
  }
}

/* ====== GET (delete) ====== */
if (($_GET['action'] ?? '') === 'delete') {
  $token = $_GET['csrf'] ?? '';
  $id    = (int)($_GET['id'] ?? 0);
  if (hash_equals($csrf, $token) && $id > 0) {
    // ممنوع تحذف نفسك
    if ($id === current_admin_id()) {
      $_SESSION['flash'] = 'You cannot delete your own admin account.';
    } else {
      $db->pdo()->prepare("DELETE FROM admins WHERE id=?")->execute([$id]);
      $_SESSION['flash'] = 'Admin deleted.';
    }
  }
  header('Location: /admin/admins.php'); exit;
}

/* ====== Data ====== */
$rows = $db->pdo()->query("SELECT id,name,email,is_super,role,created_at FROM admins ORDER BY id ASC")->fetchAll();

/* Flash */
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<h2 class="mb-2">Manage Admins</h2>
<p><a href="/admin/dashboard" class="text-decoration-none">← Back to Admin</a></p>

<?php if ($flash): ?>
  <div class="alert alert-info"><?= h($flash) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted">Total: <?= count($rows) ?></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
    <i class="bi bi-plus-lg me-1"></i> New Admin
  </button>
</div>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th class="text-center" style="width:70px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-center">No admins yet.</td></tr>
      <?php else: foreach ($rows as $a): ?>
        <tr>
          <td><?= (int)$a['id'] ?></td>
          <td><?= h($a['name']) ?></td>
          <td><?= h($a['email']) ?></td>
          <td>
            <?php $role = $a['role'] ?: ($a['is_super'] ? 'super' : 'viewer'); ?>
            <span class="badge bg-secondary text-uppercase"><?= h($role) ?></span>
          </td>
          <td><?= h($a['created_at']) ?></td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <button
                    class="dropdown-item btn-edit-admin"
                    data-id="<?= (int)$a['id'] ?>"
                    data-name="<?= h($a['name']) ?>"
                    data-email="<?= h($a['email']) ?>"
                    data-role="<?= h($a['role'] ?: ($a['is_super'] ? 'super' : 'viewer')) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editAdminModal">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                  </button>
                </li>
                <?php if (current_admin_id() !== (int)$a['id']): ?>
                  <li>
                    <a class="dropdown-item text-danger"
                       href="/admin/admins.php?action=delete&id=<?= (int)$a['id'] ?>&csrf=<?= h($csrf) ?>"
                       onclick="return confirm('Delete this admin?')">
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

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/admin/admins.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>New Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input class="form-control" type="text" name="name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" placeholder="Min 6 characters" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" required>
            <?php foreach ($ROLES as $r): ?>
              <option value="<?= h($r) ?>"><?= h(ucfirst($r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="/admin/admins.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editAdminId">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input class="form-control" type="text" name="name" id="editAdminName" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" id="editAdminEmail" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" id="editAdminRole" required>
            <?php foreach ($ROLES as $r): ?>
              <option value="<?= h($r) ?>"><?= h(ucfirst($r)) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Leave password empty to keep the current one.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password (optional)</label>
          <input class="form-control" type="password" name="password" placeholder="Min 6 characters">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
  // تعبئة مودال التعديل من عنصر القائمة
  document.querySelectorAll('.btn-edit-admin').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editAdminId').value    = btn.dataset.id;
      document.getElementById('editAdminName').value  = btn.dataset.name || '';
      document.getElementById('editAdminEmail').value = btn.dataset.email || '';
      document.getElementById('editAdminRole').value  = btn.dataset.role || 'viewer';
    });
  });
</script>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>