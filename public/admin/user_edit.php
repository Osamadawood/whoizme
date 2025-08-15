<?php
require __DIR__ . '/../../includes/admin_auth.php';
$is_modal = (isset($_GET['modal']) && $_GET['modal'] == '1');
if (!$is_modal) {
    require __DIR__ . '/../../includes/admin_header.php';
} else {
    // Full minimal document for iframe modal (inherits no CSS from parent)
    echo '<!doctype html><html lang="en"><head>'
       . '<meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
       . '<style>body{background:transparent;padding:16px;} .modal-topbar{border-bottom:1px solid #e9ecef;padding-bottom:.5rem;margin-bottom:1rem}</style>'
       . '</head><body>';
    echo '<div class="container-fluid p-0">'
       . '<div class="d-flex align-items-center justify-content-between modal-topbar">'
       . '<h2 class="h5 mb-0">Edit user</h2>'
       . '<button type="button" class="btn-close" onclick="window.top.postMessage({type:\'whoiz-close\'}, \"*\");" aria-label="Close"></button>'
       . '</div>';
}

if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_admin'];
$can_manage = in_array($_SESSION['admin_role'] ?? 'viewer', ['editor','manager','super'], true);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/users.php'); exit; }

// fetch user
$st = $db->pdo()->prepare("SELECT id,name,email,is_active,inactive_reason,created_at FROM users WHERE id=?");
$st->execute([$id]);
$user = $st->fetch();
if (!$user) { require __DIR__.'/../../includes/admin_footer.php'; exit('User not found'); }

// flash helpers
$flash = function($name){ if(!empty($_SESSION[$name])){ $m=$_SESSION[$name]; unset($_SESSION[$name]); return $m; } return null; };

$err = null; $ok = null;

// handle post
if ($_SERVER['REQUEST_METHOD']==='POST' && $can_manage) {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { $err='Invalid CSRF token.'; }
  else {
    try {
      $name   = trim((string)($_POST['name'] ?? $user['name']));
      $email  = trim((string)($_POST['email'] ?? $user['email']));
      $status = (string)($_POST['status'] ?? ($user['is_active'] ? 'active' : 'inactive'));
      $reason = (string)($_POST['inactive_reason'] ?? '');

      if ($name==='') throw new RuntimeException('Name is required.');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid email.');

      // check unique email (exclude self)
      $chk = $db->pdo()->prepare("SELECT COUNT(*) FROM users WHERE email=? AND id<>?");
      $chk->execute([$email, $id]);
      if ($chk->fetchColumn() > 0) throw new RuntimeException('Email already in use.');

      $active = $status === 'inactive' ? 0 : 1;

      // base update
      $up = $db->pdo()->prepare("UPDATE users SET name=?, email=?, is_active=?, inactive_reason=? WHERE id=?");
      $up->execute([$name, $email, $active, $active? null : ($reason?:null), $id]);

      // optional temp password
      if (!empty($_POST['set_temp_password']) && !empty($_POST['temp_password'])) {
        $tp = (string)$_POST['temp_password'];
        if (strlen($tp) < 6) throw new RuntimeException('Temp password must be at least 6 chars.');
        $hash = password_hash($tp, PASSWORD_BCRYPT);
        $db->pdo()->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
        // force password change flag if column exists
        try { $db->pdo()->prepare("UPDATE users SET password_must_change=1 WHERE id=?")->execute([$id]); }
        catch(Throwable $e1){ try { $db->pdo()->prepare("UPDATE users SET must_change_password=1 WHERE id=?")->execute([$id]); } catch(Throwable $e2){} }
      }

      // log
      try {
        $lg = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)");
        $lg->execute([$_SESSION['admin_id'], 'user_update', $id]);
      } catch (Throwable $e) {}

      $_SESSION['admin_ok'] = 'User updated.';

      if ($is_modal) {
        // أغلق الـ modal وخلّي صفحة الـ users تتحدّث
        echo '<script>
          window.top.postMessage({type:"whoiz-refresh"}, "*");
        </script>';
        exit;
      } else {
        header("Location: /admin/users.php");
        exit;
      }

    } catch (Throwable $e) { $err = $e->getMessage(); }
  }
}

// re-fetch for display (in case of errors keep posted values)
if ($err && $_SERVER['REQUEST_METHOD']==='POST') {
  $user['name']            = (string)($_POST['name'] ?? $user['name']);
  $user['email']           = (string)($_POST['email'] ?? $user['email']);
  $user['is_active']       = (($_POST['status'] ?? ($user['is_active']?'active':'inactive'))==='active') ? 1 : 0;
  $user['inactive_reason'] = (string)($_POST['inactive_reason'] ?? $user['inactive_reason']);
}
?>
<?php if(!$is_modal): ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="mb-0">Edit user</h2>
  <a href="/admin/users.php" class="btn btn-outline-secondary">← Back</a>
</div>
<?php endif; ?>

<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert alert-success"><?= htmlspecialchars($ok)  ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <div class="col-12 col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required <?= $can_manage?'':'disabled' ?>>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required <?= $can_manage?'':'disabled' ?>>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" <?= $can_manage?'':'disabled' ?>>
          <option value="active"   <?= $user['is_active']?'selected':'' ?>>Active</option>
          <option value="inactive" <?= !$user['is_active']?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-12 col-md-8">
        <label class="form-label">Inactive reason (optional)</label>
        <input type="text" name="inactive_reason" class="form-control" value="<?= htmlspecialchars((string)$user['inactive_reason']) ?>" <?= $can_manage?'':'disabled' ?>>
      </div>

      <div class="col-12">
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="tmpPass" name="set_temp_password" value="1" <?= $can_manage?'':'disabled' ?>>
          <label class="form-check-label" for="tmpPass">Set a temporary password and require change on next login</label>
        </div>
        <div id="tmpPassWrap" style="display:none">
          <input type="password" name="temp_password" id="tmpPassword" class="form-control" placeholder="Temporary password (min 6)">
          <div class="form-text">User will be forced to change it on next login.</div>
        </div>
      </div>

      <div class="col-12">
        <button class="btn btn-primary" <?= $can_manage?'':'disabled' ?>>Save changes</button>
      </div>
    </form>
    <script>
      (function () {
        var cb = document.getElementById('tmpPass');
        var wrap = document.getElementById('tmpPassWrap');
        var input = document.getElementById('tmpPassword');
        if (!cb || !wrap || !input) return;
        function gen() {
          // ~10 chars, letters+digits
          return Math.random().toString(36).slice(-10);
        }
        function sync() {
          wrap.style.display = cb.checked ? 'block' : 'none';
          if (cb.checked && !input.value) input.value = gen();
        }
        cb.addEventListener('change', sync);
        // initial state on load
        sync();
      })();
    </script>
  </div>
</div>

<?php
if (!$is_modal) {
    require __DIR__ . '/../../includes/admin_footer.php';
} else {
    echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}
?>