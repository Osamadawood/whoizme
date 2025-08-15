<?php
// public/admin/user_update.php
require __DIR__ . '/../../includes/admin_auth.php';
ini_set('display_errors',1); error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }

// CSRF check
$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  http_response_code(400); exit('Bad request (CSRF)');
}

// Permissions: base edit permission required
if (!function_exists('admin_can') || !admin_can('users.edit')) {
  http_response_code(403); exit('Forbidden');
}

$id     = (int)($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$pass   = trim($_POST['password'] ?? '');
$isAct  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$reason = trim($_POST['disable_reason'] ?? '');

if ($id <= 0 || $name === '' || $email === '') {
  http_response_code(400); exit('Missing required fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422); exit('Invalid email');
}

// Fetch current row to compare status change permissions
$curSt = $db->pdo()->prepare("SELECT is_active FROM users WHERE id=? LIMIT 1");
$curSt->execute([$id]);
$cur = $curSt->fetch();
if (!$cur) { http_response_code(404); exit('User not found'); }

$changingStatus = ((int)$cur['is_active'] !== (int)$isAct);
if ($changingStatus) {
  if ((int)$isAct === 0 && !admin_can('users.deactivate')) { http_response_code(403); exit('Forbidden (deactivate)'); }
  if ((int)$isAct === 1 && !admin_can('users.activate'))   { http_response_code(403); exit('Forbidden (activate)'); }
}

// Ensure email is unique
$chk = $db->pdo()->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
$chk->execute([$email, $id]);
if ($chk->fetch()) {
  http_response_code(409); exit('Email already in use by another user');
}

// Normalize reason when active
if ((int)$isAct === 1) { $reason = null; }

// Validate password (optional)
if ($pass !== '' && strlen($pass) < 6) {
  http_response_code(422); exit('Password must be at least 6 characters');
}

// Build query
if ($pass !== '') {
  $hash = password_hash($pass, PASSWORD_BCRYPT);
  $sql = "UPDATE users SET name=?, email=?, password_hash=?, is_active=?, disable_reason=? WHERE id=?";
  $params = [$name, $email, $hash, $isAct, $reason, $id];
} else {
  $sql = "UPDATE users SET name=?, email=?, is_active=?, disable_reason=? WHERE id=?";
  $params = [$name, $email, $isAct, $reason, $id];
}

$db->pdo()->prepare($sql)->execute($params);

// Flash + redirect back
$_SESSION['flash'] = 'User updated.';
header('Location: /admin/users.php');
exit;