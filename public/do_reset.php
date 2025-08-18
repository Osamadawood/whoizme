<?php
declare(strict_types=1);

// معالجة إعادة تعيين كلمة المرور
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/flash.php';
require dirname(__DIR__) . '/includes/csrf.php';

$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('DB not ready');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /reset.php', true, 302);
    exit;
}

if (!csrf_check($_POST['csrf'] ?? null)) {
    flash_set('reset', 'Security token expired, please try again.', 'danger');
    header('Location: /reset.php?err=csrf&token=' . urlencode((string)($_POST['token'] ?? '')), true, 302);
    exit;
}

$token = trim((string)($_POST['token'] ?? ''));
$pass1 = (string)($_POST['password'] ?? '');
$pass2 = (string)($_POST['password_confirm'] ?? '');

if ($token === '' || $pass1 === '' || $pass2 === '' || $pass1 !== $pass2 || mb_strlen($pass1) < 8) {
    flash_set('reset', 'Please enter a valid password and confirmation.', 'danger');
    header('Location: /reset.php?err=1&token=' . urlencode($token), true, 302);
    exit;
}

// Locate token
$st = $pdo->prepare('SELECT r.id, r.user_id, r.expires_at, r.used FROM password_resets r WHERE r.token = ? LIMIT 1');
$st->execute([$token]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['used']) {
    flash_set('reset', 'Invalid or used reset link.', 'danger');
    header('Location: /forgot.php?err=invalid', true, 302);
    exit;
}
if (new DateTime() > new DateTime((string)$row['expires_at'])) {
    flash_set('reset', 'Reset link expired. Please request a new one.', 'danger');
    header('Location: /forgot.php?err=expired', true, 302);
    exit;
}

$hash = password_hash($pass1, PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->beginTransaction();
try {
    $st = $pdo->prepare('UPDATE users SET pass_hash = ? WHERE id = ?');
    $st->execute([$hash, (int)$row['user_id']]);

    $st = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $st->execute([(int)$row['id']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('reset', 'Unexpected error. Please try again.', 'danger');
    header('Location: /reset.php?err=exception&token=' . urlencode($token), true, 302);
    exit;
}

// Auto-login
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['uid'] = (int)$row['user_id'];

header('Location: /dashboard.php', true, 302);
exit;