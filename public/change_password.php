<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
// public/change_password.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['is_logged_in']) || empty($_SESSION['user_id'])) {
  $_SESSION['flash_error'] = 'Please login first.';
  header('Location: /login.php'); exit;
}

$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
$db = new Database($config['db']);

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $err = 'Session expired. Please try again.';
  } else {
    $p1 = $_POST['new_password'] ?? '';
    $p2 = $_POST['new_password2'] ?? '';
    if (strlen($p1) < 8) {
      $err = 'Password must be at least 8 characters.';
    } elseif ($p1 !== $p2) {
      $err = 'Passwords do not match.';
    } else {
      $hash = password_hash($p1, PASSWORD_BCRYPT);
      $st = $db->pdo()->prepare("UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?");
      $st->execute([$hash, (int)$_SESSION['user_id']]);
      $_SESSION['flash'] = 'Password updated successfully.';
      header('Location: /dashboard'); exit;
    }
  }
}

require_once __DIR__ . '/../includes/bootstrap.php';
if (auth_role() === 'admin') {
  include INC_PATH . '/partials/admin_header.php';
} else {
  include INC_PATH . '/partials/user_header.php';
}
?>
<div class="container" style="max-width: 640px; margin-top: 40px; margin-bottom:40px;">
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <h1 class="h4 mb-3">Set a new password</h1>

      <?php if ($err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="mb-3">
          <label class="form-label">New password</label>
          <input class="form-control" type="password" name="new_password" minlength="8" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm new password</label>
          <input class="form-control" type="password" name="new_password2" minlength="8" required>
        </div>
        <div class="d-grid">
          <button class="btn btn-success">Save new password</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>