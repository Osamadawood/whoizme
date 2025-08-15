<?php
require __DIR__ . '/../../includes/admin_auth.php'; // لازم تكون أدمن فعلاً

// لازم يكون في سيشن impersonation
if (empty($_SESSION['impersonating']) || empty($_SESSION['impersonate_admin_id'])) {
  header('Location: /admin/dashboard.php'); exit;
}

// نضيف لوج
try {
  $config = require __DIR__ . '/../../app/config.php';
  require_once __DIR__ . '/../../app/database.php';
  if (!isset($db) && class_exists('Database')) {
    $db = new Database($config['db']);
  }
  if (isset($_SESSION['impersonate_user_id'])) {
    $lg = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id) VALUES (?,?,?)");
    $lg->execute([$_SESSION['admin_id'], 'impersonate_end', (int)$_SESSION['impersonate_user_id']]);
  }
} catch (Throwable $e) {
  // تجاهل
}

// نظف مفاتيح اليوزر فقط، وسيب الأدمن
unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);
unset($_SESSION['impersonating'], $_SESSION['impersonate_admin_id'], $_SESSION['impersonate_user_id']);

$_SESSION['admin_ok'] = 'Impersonation ended. You are back to Admin.';
header('Location: /admin/dashboard.php'); exit;