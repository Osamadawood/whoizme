<?php
declare(strict_types=1);

// معالجة طلب "نسيت كلمة المرور"
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/flash.php';
require dirname(__DIR__) . '/includes/csrf.php';

$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('DB not ready');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /forgot.php', true, 302);
    exit;
}

if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('forgot', 'Security token expired, please try again.', 'danger');
    header('Location: /forgot.php?err=csrf', true, 302);
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('forgot', 'Please enter a valid email.', 'danger');
    header('Location: /forgot.php?err=1', true, 302);
    exit;
}

// Ensure table exists (idempotent)
$pdo->exec("
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(token),
  INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Find user
$st = $pdo->prepare('SELECT id, is_active FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user || !(int)$user['is_active']) {
    // لا نفصح
    flash_set('forgot', 'If that email exists, we sent a reset link.', 'info');
    header('Location: /forgot.php?sent=1', true, 302);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expires = (new DateTime('+60 minutes'))->format('Y-m-d H:i:s');

$st = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
$st->execute([(int)$user['id'], $token, $expires]);

$resetLink = '/reset.php?token=' . urlencode($token);

// بما إننا على لوكال: سجّل اللينك في لوج
$logDir = dirname(__DIR__) . '/../storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
@file_put_contents($logDir . '/mail.log', '[' . date('c') . "] RESET $email $resetLink\n", FILE_APPEND);

flash_set('forgot', 'We’ve sent a reset link (check logs).', 'success');
header('Location: /forgot.php?sent=1', true, 302);
exit;