<?php
// Auth gate + DB boot
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

$config = require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
if (!isset($db)) { $db = new Database($config['db']); }

// Canonical user id
$uid = (int) $_SESSION['user_id'];

// Backward-compat: صفحات قديمة بتستخدم $_SESSION['uid']
$_SESSION['uid'] = $uid;

// Helper صغير لو حبّينا نستخدمه
if (!function_exists('current_user_id')) {
  function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
}