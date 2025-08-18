<?php
// منطق تسجيل الدخول فقط
require_once __DIR__ . '/../includes/bootstrap.php';

try {
  // CSRF (لو متاحة)
  if (function_exists('csrf_check')) { csrf_check(); }

  // 1) استلام القيم
  $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $remember = !empty($_POST['remember']);
  $return   = isset($_POST['return']) ? $_POST['return'] : '/dashboard.php';

  // 2) فحص أساسي
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    header('Location: /login.php?err=badinput&return=' . urlencode($return) . '&email=' . urlencode($email));
    exit;
  }

  // 3) تحضير قاعدة البيانات
  /** @var PDO $pdo */
  $stmt = $pdo->prepare('SELECT id, email, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // 4) التحقق من كلمة المرور
  if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    header('Location: /login.php?err=badpass&return=' . urlencode($return) . '&email=' . urlencode($email));
    exit;
  }

  // 5) الحالة
  if ((int)$user['is_active'] !== 1) {
    header('Location: /login.php?err=inactive&return=' . urlencode($return) . '&email=' . urlencode($email));
    exit;
  }

  // 6) تسجيل الدخول
  if (function_exists('auth_login')) {
    auth_login((int)$user['id'], $remember);
  } else {
    // fallback في حال auth_login غير متوفرة
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];

    if ($remember) {
      // توكن بسيط (استبدله بجدول remember_tokens لو حابب لاحقًا)
      $token = bin2hex(random_bytes(32));
      setcookie('remember', $token, [
        'expires'  => time() + 60*60*24*30,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
      ]);
      // خزن التوكن للمستخدم (اختياري)
      $q = $pdo->prepare('UPDATE users SET token = :t WHERE id = :id');
      $q->execute([':t' => $token, ':id' => (int)$user['id']]);
    }
  }

  // 7) حماية من اللوب: لو return يشير للّوجين أو صفحة عامة، وجّهه للداشبورد
  $ret = $return ?: '/dashboard.php';
  $retPath = parse_url($ret, PHP_URL_PATH);
  if (!$retPath || stripos($retPath, '/login.php') === 0 || stripos($retPath, '/register.php') === 0) {
    $ret = '/dashboard.php';
  }

  header('Location: ' . $ret);
  exit;

} catch (Throwable $e) {
  // لتجنب err=exception بعد الآن، نخليها آمنة
  header('Location: /login.php?err=exception&return=' . urlencode($_POST['return'] ?? '/dashboard.php') . '&email=' . urlencode($_POST['email'] ?? ''));
  exit;
}