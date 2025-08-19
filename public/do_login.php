<?php
/**
 * do_login.php
 * --------------------------
 * Handles ONLY the login POST and then redirects safely.
 * - Reads return from POST or GET.
 * - Prevents redirect loops to /login.php, /register.php, /do_login.php.
 * - Normalizes/decodes nested encoded return values.
 * - Never touches styling; backend only.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
  // Optional CSRF check if available
  if (function_exists('csrf_check')) { csrf_check(); }

  // 1) Collect inputs
  $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
  $remember = !empty($_POST['remember']);

  // return can arrive via GET on the login page, or POST if the form carries it forward
  $returnRaw = $_POST['return'] ?? $_GET['return'] ?? '/dashboard.php';

  // 2) Basic validation
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: /login.php?err=badinput&return=' . urlencode($returnRaw) . '&email=' . urlencode($email));
    exit;
  }

  // 3) Fetch user
  /** @var PDO $pdo */
  $stmt = $pdo->prepare('SELECT id, email, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // 4) Verify password
  if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    header('Location: /login.php?err=badpass&return=' . urlencode($returnRaw) . '&email=' . urlencode($email));
    exit;
  }

  // 5) Account state
  if ((int)$user['is_active'] !== 1) {
    header('Location: /login.php?err=inactive&return=' . urlencode($returnRaw) . '&email=' . urlencode($email));
    exit;
  }

  // 6) Log the user in
  if (function_exists('auth_login')) {
    auth_login((int)$user['id'], $remember);
  } else {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];

    // "Remember me" cookie (no DB write here to avoid schema coupling)
    if ($remember) {
      $token = bin2hex(random_bytes(32));
      setcookie('remember', $token, [
        'expires'  => time() + 60*60*24*30,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
      ]);
    }
  }

  // 7) Safe redirect target
  $ret = $returnRaw;

  // (a) Decode nested encodings safely (max 5 rounds)
  $rounds = 0;
  while ($rounds < 5) {
    $decoded = urldecode($ret);
    if ($decoded === $ret) { break; }
    $ret = $decoded;
    $rounds++;
  }

  // (b) Extract only the path component and normalize
  $path = parse_url($ret, PHP_URL_PATH);
  if (!$path || $path === '' || $path === '/' ) {
    $path = '/dashboard.php';
  }

  // (c) Disallow redirecting back to login/register/this endpoint
  $blocked = ['/login.php', '/register.php', '/do_login.php'];
  if (in_array($path, $blocked, true)) {
    $path = '/dashboard.php';
  }

  // (d) Make sure we only redirect to internal paths
  if ($path[0] !== '/') {
    $path = '/dashboard.php';
  }

  // Final redirect
  header('Location: ' . $path);
  exit;

} catch (Throwable $e) {
  // Avoid leaking details in query string; keep it simple and safe
  $fallback = '/dashboard.php';
  header('Location: /login.php?err=exception&return=' . urlencode($fallback) . '&email=' . urlencode($_POST['email'] ?? ''));
  exit;
}