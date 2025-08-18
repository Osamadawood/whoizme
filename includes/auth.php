<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// هل اليوزر داخل؟
function auth_is_logged_in(): bool {
  return !empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

// تسجيل الدخول
function auth_login(int $userId, bool $remember = false): void {
  if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
  session_regenerate_id(true);
  $_SESSION['user_id'] = $userId;

  if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember', $token, [
      'expires'  => time() + 60*60*24*30,
      'path'     => '/',
      'secure'   => isset($_SERVER['HTTPS']),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    // احفظ التوكن لو عندك عمود token
    if (isset($GLOBALS['pdo'])) {
      $q = $GLOBALS['pdo']->prepare('UPDATE users SET token = :t WHERE id = :id');
      $q->execute([':t' => $token, ':id' => $userId]);
    }
  }
}

// تسجيل خروج
function auth_logout(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
  setcookie('remember', '', time()-3600, '/');
}

// جارد للصفحات المحمية (استخدمه في dashboard.php)
function auth_require_login(): void {
  if (!auth_is_logged_in()) {
    $return = rawurlencode($_SERVER['REQUEST_URI'] ?? '/dashboard.php');
    header('Location: /login.php?return=' . $return);
    exit;
  }
}