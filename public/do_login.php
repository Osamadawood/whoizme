<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/helpers.php';
$db = new Database($config['db']);

$next  = $_POST['next'] ?? '/dashboard.php';
$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
  flash('flash_error', 'Session expired. Please try again.');
  flash('form_mode', 'login');
  header('Location: /login.php'); exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

set_old($_POST);

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  add_error('email', 'Please enter a valid email.');
}
if ($pass === '') {
  add_error('password', 'Please enter your password.');
}
if (errors()) {
  flash('form_mode', 'login');
  header('Location: /login.php'); exit;
}

// تحقق
$st = $db->pdo()->prepare("SELECT id, name, email, password_hash, is_active FROM users WHERE email=? LIMIT 1");
$st->execute([$email]);
$u = $st->fetch(PDO::FETCH_ASSOC);

if (!$u || !password_verify($pass, $u['password_hash'])) {
  add_error('password', 'Incorrect email or password.');
  flash('form_mode', 'login');
  header('Location: /login.php'); exit;
}
if (empty($u['is_active'])) {
  add_error('email', 'Your account is inactive.');
  flash('form_mode', 'login');
  header('Location: /login.php'); exit;
}

session_regenerate_id(true);
$_SESSION['is_logged_in'] = true;
$_SESSION['user_id']      = (int)$u['id'];
$_SESSION['email']        = $u['email'];
$_SESSION['name']         = $u['name'] ?? '';

clear_old(); clear_errors();
header('Location: ' . $next); exit;