<?php
declare(strict_types=1);

// صفحة معالجة التسجيل – بدون أي HTML
// تعتمد على اتصال PDO من includes/bootstrap.php

require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/flash.php';
require dirname(__DIR__) . '/includes/csrf.php';

// ساعدني أوصل لـ PDO
$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('DB not ready');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php', true, 302);
    exit;
}

// CSRF
if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('reg', 'Security token expired, please try again.', 'danger');
    header('Location: /register.php', true, 302);
    exit;
}

// Inputs
$name     = trim((string)($_POST['name']     ?? ''));
$email    = trim((string)($_POST['email']    ?? ''));
$password = (string)($_POST['password'] ?? '');
$return   = (string)($_POST['return']   ?? '/dashboard.php');

// Basic validation
$errors = [];
if ($name === '' || mb_strlen($name) > 120)    $errors[] = 'Please enter a valid name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
if (mb_strlen($password) < 8)                   $errors[] = 'Password must be at least 8 characters.';

if ($errors) {
    flash_set('reg', implode(' ', $errors), 'danger');
    header('Location: /register.php?err=1', true, 302);
    exit;
}

// Unique email
$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
if ($st->fetchColumn()) {
    flash_set('reg', 'This email is already registered.', 'danger');
    header('Location: /register.php?err=exists', true, 302);
    exit;
}

// Insert
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$st = $pdo->prepare('INSERT INTO users (name, email, pass_hash, is_active, created_at) VALUES (?, ?, ?, 1, NOW())');
$st->execute([$name, $email, $hash]);

$uid = (int)$pdo->lastInsertId();

// Login after register
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['uid']   = $uid;
$_SESSION['email'] = $email;
$_SESSION['name']  = $name;

// Normalize return
$bad = ['', '/', '/index', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php', '/register', '/register.php'];
$path = (string)(parse_url($return, PHP_URL_PATH) ?? '/dashboard.php');
if (in_array($path, $bad, true)) $path = '/dashboard.php';

header('Location: ' . $path, true, 302);
exit;