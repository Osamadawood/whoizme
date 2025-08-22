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
require_once __DIR__ . '/../includes/helpers.php';

try {
  // Optional CSRF check if available
  if (function_exists('csrf_check')) { csrf_check(); }

  // 1) Collect inputs
  $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
  $remember = !empty($_POST['remember']);

  // return can arrive via GET on the login page, or POST if the form carries it forward
  $returnRaw = $_POST['return'] ?? $_GET['return'] ?? '/dashboard';
  $errReturn = '/dashboard';
  if (wz_is_safe_next($returnRaw)) {
    $errReturn = preg_replace('~\.php($|\?)~i', '$1', (string)$returnRaw);
  }

  // 2) Basic validation
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    wz_redirect('/login', ['err'=>'badinput','return'=>$errReturn,'email'=>$email]);
  }

  // 3) Fetch user
  /** @var PDO $pdo */
  $stmt = $pdo->prepare('SELECT id, email, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // 4) Verify password
  if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    wz_redirect('/login', ['err'=>'badpass','return'=>$errReturn,'email'=>$email]);
  }

  // 5) Account state
  if ((int)$user['is_active'] !== 1) {
    wz_redirect('/login', ['err'=>'inactive','return'=>$errReturn,'email'=>$email]);
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

  // 7) Final redirect (pretty, no query)

  // Final redirect (pretty, no query)
  $retParam = $_POST['return'] ?? $_GET['return'] ?? $returnRaw ?? null;
  if (wz_is_safe_next($retParam)) {
    $clean = preg_replace('~\.php($|\?)~i', '$1', (string)$retParam);
    wz_redirect($clean);
  } else {
    wz_redirect('/dashboard');
  }

} catch (Throwable $e) {
  // Avoid leaking details in query string; keep it simple and safe
  $fallback = '/dashboard';
  wz_redirect('/login', ['err'=>'exception','return'=>$fallback,'email'=>($_POST['email'] ?? '')]);
}