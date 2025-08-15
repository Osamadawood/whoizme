<?php
// includes/admin_auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ===== Load config + DB ===== */
$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
$db = new Database($config['db']);

/* ===== Abilities per role (بس لو مش موجودة بالفعل) ===== */
if (!function_exists('admin_can')) {
  function admin_can(string $ability): bool {
    if (!isset($_SESSION['admin_role'])) {
      if (!empty($_SESSION['is_super'])) return true;
      return false;
    }
    $role = $_SESSION['admin_role'];

    $abilities = [
      'viewer'  => ['users.view','links.view'],
      'editor'  => ['users.view','users.edit','links.view','links.edit'],
      'manager' => ['users.view','users.edit','users.create','users.deactivate','users.activate','users.delete','users.reset_temp','links.view','links.edit','links.delete','impersonate'],
      'super'   => ['*'],
    ];

    if (!isset($abilities[$role])) return !empty($_SESSION['is_super']);
    if (in_array('*', $abilities[$role], true)) return true;
    return in_array($ability, $abilities[$role], true);
  }
}

/* ===== Admin auth gate ===== */
if (empty($_SESSION['is_admin']) || empty($_SESSION['admin_id'])) {
  header('Location: /admin/login.php'); exit;
}

/* ===== CSRF seed ===== */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

/* ===== Activity Log helper ===== */
if (!function_exists('admin_log')) {
  function admin_log(string $action, $target_id = null, array $details = []): void {
    try {
      global $db;
      if (!isset($db) || !method_exists($db,'pdo')) return;

      $aid = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
      $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
      $ua  = $_SERVER['HTTP_USER_AGENT'] ?? null;

      $json = json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
      if ($json === false) $json = json_encode(['_raw' => (string)print_r($details, true)]);

      $stmt = $db->pdo()->prepare("INSERT INTO admin_logs (admin_id, action, target_id, details, ip, user_agent) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$aid, $action, $target_id, $json, $ip, $ua]);
    } catch (Throwable $e) {
      // تجاهل أخطاء اللوج
    }
  }
}