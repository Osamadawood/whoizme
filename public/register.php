<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
// public/register.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/helpers.php';
$db = new Database($config['db']);

$next  = $_POST['next'] ?? '/dashboard.php';
$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
  flash('flash_error','Session expired. Please try again.');
  flash('form_mode','signup');
  header('Location: /login.php?mode=signup'); exit;
}

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass1 = $_POST['password']  ?? '';
$pass2 = $_POST['password2'] ?? '';
$agree = isset($_POST['agree']) ? 1 : 0;

set_old($_POST);

// validation
if ($name === '')  add_error('name','Please enter your name.');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) add_error('email','Please enter a valid email.');
if (strlen($pass1) < 8) add_error('password','Password must be at least 8 characters.');
if ($pass1 !== $pass2) add_error('password2','Passwords do not match.');
if ($agree !== 1) add_error('agree','You must agree to the terms.');

if (errors()) {
  flash('form_mode','signup');
  header('Location: /login.php?mode=signup'); exit;
}

// unique email?
$st = $db->pdo()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$st->execute([$email]);
if ($st->fetch()) {
  add_error('email','Email already in use. Try login.');
  flash('form_mode','signup');
  header('Location: /login.php?mode=signup'); exit;
}

// create user
$hash = password_hash($pass1, PASSWORD_BCRYPT);
$now  = date('Y-m-d H:i:s');
$db->pdo()->prepare("
  INSERT INTO users (name, email, password_hash, is_active, must_change_password, created_at)
  VALUES (?, ?, ?, 1, 0, ?)
")->execute([$name, $email, $hash, $now]);

$uid = (int)$db->pdo()->lastInsertId();

// auto login
session_regenerate_id(true);
$_SESSION['is_logged_in'] = true;
$_SESSION['user_id']      = $uid;
$_SESSION['email']        = $email;
$_SESSION['name']         = $name;

clear_old(); clear_errors();
header('Location: ' . $next); exit;