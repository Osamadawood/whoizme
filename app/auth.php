<?php // /includes/auth.php
declare(strict_types=1);

require_once INC_PATH . '/session.php';

function auth_user_id(): int {
  return (int)($_SESSION['uid'] ?? 0);
}
function auth_is_logged_in(): bool {
  return auth_user_id() > 0;
}
function auth_role(): string {
  return (string)($_SESSION['role'] ?? 'user'); // user | admin
}
function auth_require_login(): void {
  if (!auth_is_logged_in()) {
    header('Location: /login.php'); exit;
  }
}
function auth_require_admin(): void {
  if (auth_role() !== 'admin') {
    http_response_code(403);
    echo 'Forbidden'; exit;
  }
}

// للمحلي مؤقتًا (اشيلها في الإنتاج)
if (!auth_is_logged_in()) {
  $_SESSION['uid']  = 1;
  $_SESSION['role'] = 'user'; // أو 'admin'
}