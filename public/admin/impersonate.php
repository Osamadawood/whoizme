<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
// public/admin/impersonate.php
require __DIR__ . '/../../includes/admin_auth.php';
ini_set('display_errors',1); error_reporting(E_ALL);

if (!admin_can('impersonate')) {
  $_SESSION['flash'] = 'You do not have permission to impersonate users.';
  header('Location: /admin/users.php'); exit;
}

if (empty($_GET['id']) || empty($_GET['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $_SESSION['flash'] = 'Invalid request.';
  header('Location: /admin/users.php'); exit;
}

$uid = (int)$_GET['id'];
$st = $db->pdo()->prepare("SELECT id, name, email, is_active FROM users WHERE id=? LIMIT 1");
$st->execute([$uid]);
$u = $st->fetch();

if (!$u) {
  $_SESSION['flash'] = 'User not found.';
  header('Location: /admin/users.php'); exit;
}
if ((int)$u['is_active'] !== 1) {
  $_SESSION['flash'] = 'Cannot impersonate a disabled account.';
  header('Location: /admin/users.php'); exit;
}

/* خزن سيشن الأدمن احتياطيًا (لمرة واحدة) */
if (empty($_SESSION['__impersonator'])) {
  $_SESSION['__impersonator'] = [
    'is_admin'    => $_SESSION['is_admin']    ?? null,
    'admin_id'    => $_SESSION['admin_id']    ?? null,
    'admin_email' => $_SESSION['admin_email'] ?? null,
    'admin_name'  => $_SESSION['admin_name']  ?? null,
    'is_super'    => $_SESSION['is_super']    ?? null,
    'admin_role'  => $_SESSION['admin_role']  ?? null,
  ];
}

/* فعّل سيشن يوزر */
$_SESSION['is_logged_in'] = true;
$_SESSION['user_id']      = (int)$u['id'];
$_SESSION['email']        = $u['email'];
$_SESSION['name']         = $u['name'] ?? '';

$_SESSION['impersonating']         = 1;
$_SESSION['impersonator_admin_id'] = (int)($_SESSION['admin_id'] ?? 0);

/* Log */
admin_log('impersonate_start', $u['id'], ['user_email' => $u['email'] ?? null]);

$_SESSION['flash'] = 'You are now impersonating: '.($u['email']);
header('Location: /dashboard.php'); exit;