<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
// public/admin/login.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$config = require __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
$db = new Database($config['db']);

// CSRF for login form
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

// لو الأدمن داخل بالفعل، ودّيه للداشبورد
if (!empty($_SESSION['is_admin']) || !empty($_SESSION['admin_id']) || !empty($_SESSION['admin_email'])) {
  header('Location: /admin/dashboard'); exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $err = 'Invalid session. Please try again.';
  } else {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email !== '' && $pass !== '') {
      $st = $db->pdo()->prepare("SELECT id, name, email, password_hash, is_super, role FROM admins WHERE email=? LIMIT 1");
      $st->execute([$email]);
      if ($a = $st->fetch()) {
        if (password_verify($pass, $a['password_hash'])) {
          // ✅ خزّن مفاتيح السيشن القياسية للأدمن
          session_regenerate_id(true);
          $_SESSION['is_admin']    = true;
          $_SESSION['admin_id']    = (int)$a['id'];
          $_SESSION['admin_email'] = $a['email'];
          $_SESSION['admin_name']  = $a['name'] ?? '';
          $_SESSION['is_super']    = (int)$a['is_super'] === 1;
          $_SESSION['admin_role']  = $a['role'] ?: (!empty($_SESSION['is_super']) ? 'super' : 'viewer');

          // امسح أي سيشن يوزر لو كان فيه
          unset($_SESSION['user_id'], $_SESSION['uid'], $_SESSION['email'], $_SESSION['is_logged_in']);

          header('Location: /admin/dashboard'); exit;
        }
      }
      $err = 'Invalid credentials.';
    } else {
      $err = 'Please enter email and password.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f6f7fb; }
    .login-card { max-width: 420px; margin: 8vh auto; }
    .brand { font-weight:700; letter-spacing:.2px; }
  </style>
</head>
<body>
  <div class="container login-card">
    <div class="text-center mb-3">
      <div class="brand fs-4">Admin Panel</div>
      <div class="text-muted">Sign in to continue</div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h5 class="card-title mb-3">Admin Login</h5>
        <?php if ($err): ?>
          <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i> Login</button>
          </div>
        </form>
      </div>
    </div>

    <div class="text-center mt-3">
      <a href="/" class="text-decoration-none text-muted"><i class="bi bi-arrow-left-short"></i> Back to site</a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>